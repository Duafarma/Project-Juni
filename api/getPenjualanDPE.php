<?php
    require_once('../config/connection/connection.php');
    require_once('../config/connection/security.php');
    require_once('../config/function/data.php');

    $base = new DB;
    $secu = new Security;
    $data = new Data;
    $conn = $base->open();

    // Validate API key
    $api_key = $secu->injection($_GET['key'] ?? '');
    $valid_key = false;
    
    $qkey = "SELECT key_apl FROM aplikasi WHERE key_apl = :key AND active_apl = 1";
    $key_stmt = $conn->prepare($qkey);
    $key_stmt->bindValue(':key', $api_key, PDO::PARAM_STR);
    $key_stmt->execute();
    $valid_key = $key_stmt->rowCount() > 0;

    if (!$valid_key) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid API key']);
        exit;
    }

    // GET parameters
    $outlet = $secu->injection($_GET['outlet'] ?? '');
    $produk = $secu->injection($_GET['produk'] ?? '');
    $tgl1 = $secu->injection($_GET['tgl1'] ?? '');
    $tgl2 = $secu->injection($_GET['tgl2'] ?? '');
    $page = (int)($secu->injection($_GET['halaman'] ?? 1));
    $maxi = (int)($secu->injection($_GET['maximal'] ?? 10));
    $mulai = ($page > 1) ? (($page * $maxi) - $maxi) : 0;

    // Build WHERE conditions
    $where_conditions = ["A.id_tfd != ''", "(D.nama_p = 'MP0000000002' OR D.nama_pro LIKE '%VISION BLU%')"];
    $params = [];
    
    if (!empty($outlet)) {
        $where_conditions[] = "C.id_out = :outlet";
        $params[':outlet'] = $outlet;
    }
    
    if (!empty($produk)) {
        $where_conditions[] = "A.id_pro = :produk";
        $params[':produk'] = $produk;
    }
    
    if (!empty($tgl1)) {
        $where_conditions[] = "B.tgl_tfk >= :tgl1";
        $params[':tgl1'] = $tgl1;
    }
    
    if (!empty($tgl2)) {
        $where_conditions[] = "B.tgl_tfk <= :tgl2";
        $params[':tgl2'] = $tgl2;
    }

    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

    // Debug logging
    error_log("DPEA API Params received - Outlet: $outlet, Produk: $produk, TGL1: $tgl1, TGL2: $tgl2");
    error_log("DPEA WHERE clause: $where_clause");
    error_log("DPEA Bound params: " . json_encode($params));

    try {
        // Count total records
        $qjumlah = "SELECT COUNT(A.id_tfd) AS total 
                    FROM transaksi_fakturdetail AS A
                    LEFT JOIN transaksi_faktur AS B ON A.id_tfk = B.id_tfk
                    LEFT JOIN produk AS D ON A.id_pro = D.id_pro
                    LEFT JOIN outlet AS C ON B.id_out = C.id_out
                    LEFT JOIN kategori_produk AS E ON D.id_kpr = E.id_kpr
                        LEFT JOIN outlet_alamat AS F ON C.id_out = F.id_out
                             LEFT JOIN regional_kabupaten AS G ON F.id_rkb = G.id_rkb
                    $where_clause";
        
        $stmt = $conn->prepare($qjumlah);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();
        $jumlah = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fetch records
        $qmaster = "SELECT A.jumlah_tfd, A.harga_tfd, A.diskon_tfd, A.total_tfd,
                           B.kode_tfk, B.tgl_tfk, 
                           C.nama_out, D.nama_pro, E.nama_kpr, G.nama_rkb
                    FROM transaksi_fakturdetail AS A
                    LEFT JOIN transaksi_faktur AS B ON A.id_tfk = B.id_tfk
                    LEFT JOIN produk AS D ON A.id_pro = D.id_pro
                    LEFT JOIN outlet AS C ON B.id_out = C.id_out
                    LEFT JOIN kategori_produk AS E ON D.id_kpr = E.id_kpr
                        LEFT JOIN outlet_alamat AS F ON C.id_out = F.id_out
                             LEFT JOIN regional_kabupaten AS G ON F.id_rkb = G.id_rkb
                    $where_clause
                    ORDER BY B.tgl_tfk DESC, B.kode_tfk DESC
                    LIMIT :mulai, :maxi";
        
        $master = $conn->prepare($qmaster);
        foreach ($params as $key => $value) {
            $master->bindValue($key, $value, PDO::PARAM_STR);
        }
        $master->bindValue(':mulai', $mulai, PDO::PARAM_INT);
        $master->bindValue(':maxi', $maxi, PDO::PARAM_INT);
        $master->execute();

        $data_array = [];
        while ($hasil = $master->fetch(PDO::FETCH_ASSOC)) {
            $data_array[] = $hasil;
        }

        $response = [
            'status' => 'success',
            'data' => $data_array,
            'total' => $jumlah['total'],
            'page' => $page,
            'max_per_page' => $maxi
        ];

        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode($response);

    } catch (Exception $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }

    $conn = $base->close();
?>
