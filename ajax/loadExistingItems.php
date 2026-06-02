<?php
require_once('../config/connection/connection.php');
require_once('../config/connection/security.php');
require_once('../config/function/data.php');

$secu = new Security;
$base = new DB;
$data = new Data;
$conn = $base->open();

$idTfk = $secu->injection(@$_POST['id_tfk']);

// Query untuk get existing items
$query = $conn->prepare("
    SELECT 
        A.id_tfd,
        A.id_psd,
        A.id_pro,
        A.harga_tfd,
        A.jumlah_tfd,
        A.diskon_tfd,
        A.total_tfd,
        B.kode_pro,
        B.nama_pro,
        B.berat_pro,
        C.no_bcode,
        C.tgl_expired,
        C.gudang,
        C.sisa_psd,
        D.nama_kpr,
        D.satuan_kpr
    FROM transaksi_fakturdetail AS A
    LEFT JOIN produk AS B ON A.id_pro = B.id_pro
    LEFT JOIN produk_stokdetail AS C ON A.id_psd = C.id_psd
    LEFT JOIN kategori_produk AS D ON B.id_kpr = D.id_kpr
    WHERE A.id_tfk = :idTfk
    ORDER BY A.id_tfd ASC
");

$query->bindParam(':idTfk', $idTfk, PDO::PARAM_STR);
$query->execute();

$items = array();

while($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $items[] = array(
        'id_tfd' => $row['id_tfd'],
        'id_psd' => $row['id_psd'],
        'id_pro' => $row['id_pro'],
        'kode_pro' => $row['kode_pro'],
        'nama_pro' => $row['nama_pro'],
        'berat_pro' => $row['berat_pro'],
        'no_bcode' => $row['no_bcode'],
        'tgl_expired' => date('d/m/Y', strtotime($row['tgl_expired'])),
        'gudang' => $row['gudang'],
        'sisa_psd' => $row['sisa_psd'],
        'nama_kpr' => $row['nama_kpr'],
        'satuan_kpr' => $row['satuan_kpr'],
        'harga_tfd' => $row['harga_tfd'],
        'jumlah_tfd' => $row['jumlah_tfd'],
        'diskon_tfd' => $row['diskon_tfd'],
        'total_tfd' => $row['total_tfd']
    );
}

$conn = $base->close();

// Return JSON
header('Content-Type: application/json');
echo json_encode($items);
?>
