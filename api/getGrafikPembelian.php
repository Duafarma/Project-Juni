<?php
	require_once('../config/connection/connection.php');
	require_once('../config/connection/security.php');
	require_once('../config/function/data.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$tgl	= date('Y-m-d');
	$conn	= $base->open();
	$hasil 	= "Error";
	
	// checking encrypt
	$encrypt= $secu->injection($_GET['encrypt']);
	$source = $data->self_apl();
	$sourceKey  = $source['key_apl'];
	if (md5($tgl. "#" . $sourceKey) == $encrypt) {
		// checking params
		$cari	= $secu->injection(@$_GET['key']);
		$pecah	= explode('_', $cari);
		$tahun1	= empty($pecah[0]) ? (date('Y') - 2) : $pecah[0];
		$tahun2	= empty($pecah[1]) ? date('Y') : $pecah[1];
		
		// Query untuk ambil data pembelian berdasarkan subtot_tre dari transaksi_receive
		$qMaster = "SELECT
				YEAR(tgl_tre) as tahun,
				MONTH(tgl_tre) as bulan,
				COALESCE(SUM(subtot_tre), 0) as total_pembelian
			FROM
				transaksi_receive
			WHERE
				YEAR(tgl_tre) BETWEEN :tahun1 AND :tahun2
			GROUP BY
				YEAR(tgl_tre), MONTH(tgl_tre)
			ORDER BY
				YEAR(tgl_tre), MONTH(tgl_tre)";
		
		$master	= $conn->prepare($qMaster);
		$master->bindValue(':tahun1', $tahun1, PDO::PARAM_INT);
		$master->bindValue(':tahun2', $tahun2, PDO::PARAM_INT);
		$master->execute();
		
		$hasil = $master->fetchAll(PDO::FETCH_ASSOC);
		http_response_code(200);
	} else {
		http_response_code(401);
	}
	
	// Set header
	header('Access-Control-Allow-Origin: *');
	header("Content-type: application/json; charset=utf-8");
	
	// Return JSON
	echo json_encode(array("result" => $hasil));
	
	$base->close();
?>
