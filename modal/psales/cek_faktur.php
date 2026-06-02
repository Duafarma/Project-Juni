<?php
require_once('../../config/connection/connection.php');
require_once('../../config/connection/security.php');
require_once('../../config/function/data.php');

$secu	= new Security;
$base	= new DB;
$data	= new Data;
$conn	= $base->open();
$admin	= $secu->injection(@$_COOKIE['adminkuy']);
$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);

header('Content-Type: application/json');

if($secu->validadmin($admin, $kunci)==false){
	echo json_encode(['status' => 'error', 'message' => 'Session habis']);
	exit;
}

$kode_faktur = $secu->injection(@$_POST['kode_faktur']);

if(empty($kode_faktur)){
	echo json_encode(['status' => 'error', 'message' => 'Kode faktur tidak boleh kosong']);
	exit;
}

// Cek faktur
$cek = $conn->prepare("SELECT id_tfk, kode_tfk, total_tfk, status_tfk FROM transaksi_faktur WHERE kode_tfk = :kode");
$cek->bindParam(':kode', $kode_faktur, PDO::PARAM_STR);
$cek->execute();
$faktur = $cek->fetch(PDO::FETCH_ASSOC);

if(!$faktur){
	echo json_encode([
		'status' => 'error', 
		'message' => 'Faktur tidak ditemukan',
		'kode_dicari' => $kode_faktur
	]);
	exit;
}

// Hitung total pembayaran
$bayar = $conn->prepare("SELECT 
	IFNULL(SUM(jumlah_pfk), 0) AS total_bayar,
	COUNT(*) AS jumlah_bayar
	FROM pembayaran_faktur 
	WHERE id_tfk = :id");
$bayar->bindParam(':id', $faktur['id_tfk'], PDO::PARAM_STR);
$bayar->execute();
$pembayaran = $bayar->fetch(PDO::FETCH_ASSOC);

$total_faktur = $faktur['total_tfk'];
$total_bayar = $pembayaran['total_bayar'];
$sisa = $total_faktur - $total_bayar;

echo json_encode([
	'status' => 'success',
	'data' => [
		'kode_faktur' => $faktur['kode_tfk'],
		'id_tfk' => $faktur['id_tfk'],
		'status_faktur' => $faktur['status_tfk'],
		'total_faktur' => number_format($total_faktur, 0, ',', '.'),
		'total_faktur_raw' => $total_faktur,
		'sudah_dibayar' => number_format($total_bayar, 0, ',', '.'),
		'sudah_dibayar_raw' => $total_bayar,
		'sisa_tagihan' => number_format($sisa, 0, ',', '.'),
		'sisa_tagihan_raw' => $sisa,
		'jumlah_pembayaran' => $pembayaran['jumlah_bayar'],
		'max_bayar' => $sisa > 0 ? $sisa : 0
	]
]);

$conn = $base->close();
?>
