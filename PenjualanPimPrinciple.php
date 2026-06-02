<?php
// Match getInventoryPim style: method check, encrypt validation, optional search, no LIMIT
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Access-Control-Allow-Origin: *');
    header('Content-type: application/json; charset=utf-8');
    echo "Method Not Allowed";
} else {
    require_once('../config/connection/connection.php');
    require_once('../config/connection/security.php');
    require_once('../config/function/data.php');

    $secu   = new Security;
    $base   = new DB;
    $data   = new Data;
    $tgl    = date('Y-m-d');
    $conn   = $base->open();
    $hasil  = "Error";

    // Validate encrypt using local self_apl key (same as getInventoryPim)
    $encrypt = $secu->injection(@$_GET['encrypt']);
    $source  = $data->self_apl();
    $sourceKey = $source['key_apl'] ?? '';

    if (md5($tgl . '#' . $sourceKey) == $encrypt) {
        // Params
        $cari   = $secu->injection(@$_GET['caridata']);
        $cabang = $secu->injection(@$_GET['cabang']) ?: 'ALL';

        // Build query: Sales PIM detail (no LIMIT)
        $q = "SELECT
                'MDN' AS source,
                A.kode_tfk,
                A.tgl_tfk,
                B.nama_out,
                COALESCE(C.nama_pro, 'Produk tidak tersedia') AS nama_pro,
                COALESCE(D.jumlah_tfd, 0) AS jumlah_tfd,
                COALESCE(D.harga_tfd, 0) AS harga_tfd,
                COALESCE(E.nama_spr, '-') AS satuan
              FROM transaksi_faktur_pim AS A
              LEFT JOIN outlet AS B ON A.id_out = B.id_out
              LEFT JOIN transaksi_fakturdetail_pim AS D ON A.id_tfk = D.id_tfk
              LEFT JOIN produk AS C ON D.id_pro = C.id_pro
              LEFT JOIN satuan_produk AS E ON C.id_spr = E.id_spr
              WHERE 1=1";

        // Add search filter if provided (same pattern as getInventoryPim)
        $params = [];
        if (!empty($cari)) {
            $q .= " AND (A.kode_tfk LIKE :cari OR B.nama_out LIKE :cari OR C.nama_pro LIKE :cari)";
            $params[':cari'] = '%' . $cari . '%';
        }

        $q .= " ORDER BY A.tgl_tfk DESC, A.kode_tfk DESC";

        $stmt = $conn->prepare($q);
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
        $stmt->execute();

        if ($stmt) {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format results to mirror getInventoryPim response conventions
            $formatted = [];
            $no = 1;
            foreach ($results as $row) {
                $formatted[] = [
                    'no' => $no++,
                    'source' => $row['source'],
                    'source_cabang' => 'Medan',
                    'no_faktur' => $row['kode_tfk'],
                    'tgl_faktur' => date('Y-m-d', strtotime($row['tgl_tfk'])),
                    'nama_outlet' => $row['nama_out'],
                    'nama_produk' => $row['nama_pro'],
                    'qty' => (int)$row['jumlah_tfd'],
                    'qty_formatted' => number_format($row['jumlah_tfd'], 0, ',', '.'),
                    'satuan' => $row['satuan'],
                    'harga' => (int)$row['harga_tfd'],
                    'harga_formatted' => number_format($row['harga_tfd'], 0, ',', '.')
                ];
            }

            $hasil = [
                'status' => 'success',
                'timestamp' => date('Y-m-d H:i:s'),
                'cabang' => 'Medan',
                'total_items' => count($formatted),
                'data' => $formatted
            ];
            http_response_code(200);
        } else {
            $hasil = ['status' => 'error', 'message' => 'Query failed'];
            http_response_code(500);
        }
    } else {
        $hasil = ['status' => 'error', 'message' => 'Unauthorized'];
        http_response_code(401);
    }

    $conn = $base->close();
    header('Access-Control-Allow-Origin: *');
    header('Content-type: application/json; charset=utf-8');
    echo json_encode($hasil);
}
?>