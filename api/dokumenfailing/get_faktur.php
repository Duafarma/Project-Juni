<?php
header('Content-Type: application/json; charset=utf-8');

require_once('../../config/connection/connection.php');
require_once('../../config/connection/security.php');

$secu = new Security;
$base = new DB;

$admin = $secu->injection(@$_COOKIE['adminkuy']);
$kunci = $secu->injection(@$_COOKIE['kuncikuy']);

$is_remote = isset($_GET['is_remote']) && $_GET['is_remote'] == 'true';

// Validasi hanya jika bukan request antar server
if (!$is_remote && !$secu->validadmin($admin, $kunci)) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$conn = $base->open();
$search = isset($_GET['q']) ? $_GET['q'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 30;
$offset = ($page - 1) * $limit;

$status = 'belum failing';
$where = "WHERE A.status_failing = :status AND YEAR(A.tgl_tfk) IN (2025, 2026)";
if (!empty($search)) {
    $where .= " AND (A.kode_tfk LIKE :search OR B.nama_out LIKE :search)";
}

// 1. Ambil Data Lokal
$query = "
    SELECT A.id_tfk, A.kode_tfk, A.tgl_tfk, B.nama_out, 'Cendo' AS sumber
    FROM transaksi_faktur AS A 
    INNER JOIN outlet AS B ON A.id_out = B.id_out 
    $where

    UNION ALL

    SELECT A.id_tfk, A.kode_tfk, A.tgl_tfk, B.nama_out, 'PIM' AS sumber
    FROM transaksi_faktur_pim AS A 
    INNER JOIN outlet AS B ON A.id_out = B.id_out 
    $where
    ORDER BY id_tfk DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $conn->prepare($query);
$stmt->bindValue(':status', $status, PDO::PARAM_STR);
if (!empty($search)) {
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$results = [];
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Tambahkan label Lokal untuk data sendiri
    $label_cabang = $is_remote ? "" : ""; 
    // Format ID: sumber|id_tfk|kode_tfk|nama_out|tgl_tfk
    $combined_id = implode('|', [
        $row['sumber'],
        $row['id_tfk'],
        $row['kode_tfk'],
        $row['nama_out'],
        $row['tgl_tfk']
    ]);
    
    $results[] = [
        "id" => $combined_id,
        "text" => $row['kode_tfk'] . " — " . $row['nama_out'] . " [" . $row['sumber'] . "]" . $label_cabang
    ];
}

// 2. Ambil Data dari API Cabang Lain (Hanya jika ini request lokal dari browser)
if (!$is_remote) {
    $stmt_rem = $conn->query("SELECT nama_apl, base_url_apl FROM aplikasi WHERE active_apl = 1 AND self_apl = 0");
    while ($rem = $stmt_rem->fetch()) {
        if (empty($rem['base_url_apl'])) continue;
        
        $remoteUrl = rtrim($rem['base_url_apl'], '/') . '/api/dokumenfailing/get_faktur.php?' . http_build_query([
            'is_remote' => 'true',
            'q' => $search,
            'page' => $page
        ]);

        $ch = curl_init($remoteUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2); // Timeout pendek agar dropdown tidak loading lama
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $res = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 200 && $res) {
            $remote_data = json_decode($res, true);
            if (isset($remote_data['results']) && is_array($remote_data['results'])) {
                foreach ($remote_data['results'] as $r) {
                    // Tambahkan indikator nama cabang asal pada text dropdown
                    $r['text'] .= " (Cabang " . $rem['nama_apl'] . ")";
                    $results[] = $r;
                }
            }
        }
    }
}

// 3. Hitung Total Lokal untuk Pagination
$count_query = "
    SELECT COUNT(*) as total FROM (
        SELECT A.id_tfk FROM transaksi_faktur AS A INNER JOIN outlet AS B ON A.id_out = B.id_out $where
        UNION ALL
        SELECT A.id_tfk FROM transaksi_faktur_pim AS A INNER JOIN outlet AS B ON A.id_out = B.id_out $where
        UNION ALL
        SELECT A.id_tfk FROM transaksi_faktur_c AS A INNER JOIN outlet AS B ON A.id_out = B.id_out $where
    ) as count_table
";
$stmt_count = $conn->prepare($count_query);
$stmt_count->bindValue(':status', $status, PDO::PARAM_STR);
if (!empty($search)) {
    $stmt_count->bindValue(':search', "%$search%", PDO::PARAM_STR);
}
$stmt_count->execute();
$total = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];

echo json_encode([
    "results" => $results,
    "pagination" => [
        "more" => ($page * $limit) < $total
    ]
]);
$base->close();
?>