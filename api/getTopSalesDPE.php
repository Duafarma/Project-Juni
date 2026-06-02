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
    $category = $secu->injection($_GET['category'] ?? '');
    $limit = (int)($secu->injection($_GET['limit'] ?? 5));

    // Build WHERE conditions
    $where_conditions = [];
    $params = [];
    
    if (!empty($outlet)) {
        $where_conditions[] = "D.id_out = :outlet";
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

    // Add condition based on category
    if ($category === 'dpe_general') {
        $where_conditions[] = "C.nama_p = 'MP0000000002'";
    } elseif ($category === 'vision_blu') {
        $where_conditions[] = "C.nama_pro LIKE '%VISION BLU 7 ML%' AND C.nama_pro NOT LIKE '%EXTRA%'";
    } elseif ($category === 'vision_blu_extra') {
        $where_conditions[] = "C.nama_pro LIKE '%VISION BLU EXTRA 7 ML%'";
    } elseif ($category === 'penjualan_data') {
        // Filter only DPE products for outlet totals
        $where_conditions[] = "C.nama_p = 'MP0000000002'";
    } else {
        // Default: DPE products including VISION BLU
        $where_conditions[] = "(C.nama_p = 'MP0000000002' OR C.nama_pro LIKE '%VISION BLU%')";
    }

    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : 'WHERE 1=1';
    
    // Determine GROUP BY and ORDER BY based on category
    $group_by = "D.id_out, C.id_pro";
    $order_by = "total_qty DESC";
    
    if ($category === 'dpe_general') {
        $group_by = "C.id_pro";
    } elseif ($category === 'penjualan_data') {
        $group_by = "D.id_out";
        $order_by = "total_amount DESC";
    } elseif ($category === 'vision_blu' || $category === 'vision_blu_extra') {
        $group_by = "D.id_out";
    }

    try {
        // Query untuk top sales berdasarkan total per outlet+product
        $qmaster = "SELECT D.nama_out, C.nama_pro, E.nama_kpr,
                           SUM(A.total_tfd) as total_sales,
                           SUM(A.jumlah_tfd) as total_qty,
                           SUM(A.total_tfd) as total_amount,
                           COUNT(A.id_tfd) as total_transaksi
                    FROM transaksi_fakturdetail AS A
                    LEFT JOIN transaksi_faktur AS B ON A.id_tfk = B.id_tfk
                    LEFT JOIN produk AS C ON A.id_pro = C.id_pro
                    LEFT JOIN outlet AS D ON B.id_out = D.id_out
                    LEFT JOIN kategori_produk AS E ON C.id_kpr = E.id_kpr
                    $where_clause
                    GROUP BY $group_by
                    ORDER BY $order_by
                    LIMIT :limit";
        
        $master = $conn->prepare($qmaster);
        foreach ($params as $key => $value) {
            $master->bindValue($key, $value, PDO::PARAM_STR);
        }
        $master->bindValue(':limit', $limit, PDO::PARAM_INT);
        $master->execute();

        $data_array = [];
        while ($hasil = $master->fetch(PDO::FETCH_ASSOC)) {
           
            $data_array[] = $hasil;
        }

        $response = [
            'status' => 'success',
            'data' => $data_array,
            'message' => 'Top sales data retrieved successfully'
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
