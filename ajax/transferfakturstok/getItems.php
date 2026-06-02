<?php
/**
 * AJAX: Get Items dari transaksi_fakturdetail untuk faktur tertentu
 * GET: id_tfk = ID Faktur
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

$id_tfk = $secu->injection(@$_GET['id_tfk']);
if(empty($id_tfk)){
    echo json_encode(['status' => 'error', 'message' => 'id_tfk tidak ditemukan']);
    exit;
}

// Ambil detail faktur header
$qHeader = "SELECT A.id_tfk, A.kode_tfk, A.tgl_tfk, A.total_tfk, A.status_tfk, B.nama_out
            FROM transaksi_faktur AS A
            LEFT JOIN outlet AS B ON A.id_out = B.id_out
            WHERE A.id_tfk = :id_tfk";
$stmtHeader = $conn->prepare($qHeader);
$stmtHeader->bindParam(':id_tfk', $id_tfk, PDO::PARAM_STR);
$stmtHeader->execute();
$header = $stmtHeader->fetch(PDO::FETCH_ASSOC);

if(!$header){
    echo json_encode(['status' => 'error', 'message' => 'Faktur tidak ditemukan']);
    exit;
}

// Ambil items dari transaksi_fakturdetail join produk dan produk_stokdetail
$qItems = "SELECT 
                A.id_tfd,
                A.id_tfk,
                A.id_psd,
                A.id_pro,
                A.jumlah_tfd,
                A.harga_tfd,
                A.diskon_tfd,
                A.total_tfd,
                B.nama_pro,
                B.kode_produk_jadi,
                C.no_bcode,
                C.tgl_expired,
                C.gudang,
                C.sisa_psd
            FROM transaksi_fakturdetail AS A
            LEFT JOIN produk AS B ON A.id_pro = B.id_pro
            LEFT JOIN produk_stokdetail AS C ON A.id_psd = C.id_psd
            WHERE A.id_tfk = :id_tfk
              AND A.jumlah_tfd > 0
            ORDER BY B.nama_pro ASC";

$stmtItems = $conn->prepare($qItems);
$stmtItems->bindParam(':id_tfk', $id_tfk, PDO::PARAM_STR);
$stmtItems->execute();
$items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

if(empty($items)){
    echo json_encode(['status' => 'error', 'message' => 'Tidak ada item pada faktur ini']);
    exit;
}

// Format tambahan
foreach($items as &$item){
    $item['tgl_expired_fmt'] = !empty($item['tgl_expired']) ? date('d-m-Y', strtotime($item['tgl_expired'])) : '-';
    $item['harga_fmt']       = number_format($item['harga_tfd'], 0, ',', '.');
    $item['total_fmt']       = number_format($item['total_tfd'], 0, ',', '.');
    $item['no_bcode']        = $item['no_bcode'] ?? '';
    $item['gudang']          = $item['gudang'] ?? '';
    $item['tgl_expired']     = $item['tgl_expired'] ?? '';
}
unset($item);

echo json_encode([
    'status' => 'success',
    'header' => $header,
    'data'   => $items
]);
