<?php
/**
 * AJAX: Search Faktur dari transaksi_faktur
 * GET: q = keyword pencarian
 */
session_start();
require_once('../../config/connection/connection.php');
require_once('../../config/connection/security.php');
require_once('../../config/function/data.php');

header('Content-Type: application/json');

$secu = new Security;
$base = new DB;
$data = new Data;
$conn = $base->open();

$admin = $secu->injection(@$_COOKIE['adminkuy']);
$kunci = $secu->injection(@$_COOKIE['kuncikuy']);
if($secu->validadmin($admin, $kunci) == false){
    echo json_encode(['status' => 'error', 'message' => 'Session tidak valid']);
    exit;
}

$q    = $secu->injection(@$_GET['q']);
$like = "%$q%";

$qFaktur = "SELECT 
                A.id_tfk,
                A.kode_tfk,
                A.tgl_tfk,
                A.tgl_limit,
                A.total_tfk,
                A.status_tfk,
                B.nama_out
            FROM transaksi_faktur AS A
            LEFT JOIN outlet AS B ON A.id_out = B.id_out
            WHERE (A.kode_tfk LIKE :like OR B.nama_out LIKE :like)
            ORDER BY A.tgl_tfk DESC
            LIMIT 30";

$stmt = $conn->prepare($qFaktur);
$stmt->bindParam(':like', $like, PDO::PARAM_STR);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$result = [];
foreach($rows as $row){
    $row['tgl_tfk_fmt']   = !empty($row['tgl_tfk']) ? date('d-m-Y', strtotime($row['tgl_tfk'])) : '-';
    $row['total_tfk_fmt'] = 'Rp ' . number_format($row['total_tfk'], 0, ',', '.');
    $result[] = $row;
}

echo json_encode(['status' => 'success', 'data' => $result]);
