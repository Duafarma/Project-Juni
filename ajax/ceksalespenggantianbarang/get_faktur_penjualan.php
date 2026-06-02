<?php
header('Content-Type: application/json');
require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$sistem	= $data->sistem('url_sis');
	$catat	= date('Y-m-d H:i:s');
	$admin	= $secu->injection(@$_COOKIE['adminkuy']);
	$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
	$kode	= $secu->injection(@$_POST['x']);


$outletId = isset($_POST['outlet_id']) ? $_POST['outlet_id'] : '';

// Prepare the SQL query to fetch No Fkt Penjualan based on the selected outlet
$sql = "
    SELECT id_tfk, kode_tfk
    FROM transaksi_faktur
    WHERE id_out = :outlet_id
    ORDER BY kode_tfk ASC
";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':outlet_id', $outletId, PDO::PARAM_INT);
$stmt->execute();

$result = $query->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($result);
?>
