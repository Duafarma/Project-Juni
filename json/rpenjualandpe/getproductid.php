<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	$base	= new DB;
	$secu	= new Security;
	$data	= new Data;
	$conn	= $base->open();
	
	//ACCESS DATA
	$admin	= $secu->injection(@$_COOKIE['adminkuy']);
	$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
	$level	= $secu->injection(@$_COOKIE['jeniskuy']);
	$valid	= $secu->validadmin($admin, $kunci);
	
	//POST DATA
	$nama_produk = $secu->injection(@$_GET['nama_produk']);
	
	//READ DATA
	if($valid==false){
		$response = array("status" => "error", "message" => "Session login anda habis...");
	} else {
		// Get product ID by product name from DPE products and VISION BLU products
		$sql = "SELECT C.id_pro 
				FROM produk AS C 
				WHERE C.nama_pro = :nama_produk 
				AND (C.nama_p = 'MP0000000002' OR C.nama_pro LIKE '%VISION BLU%') 
				AND C.status_pro = 'Active'
				LIMIT 1";
		
		$master	= $conn->prepare($sql);
		$master->bindParam(':nama_produk', $nama_produk, PDO::PARAM_STR);
		$master->execute();
		
		$result = $master->fetch(PDO::FETCH_ASSOC);
		
		if($result) {
			$response = array(
				"status" => "success", 
				"id_produk" => $result['id_pro'],
				"nama_produk" => $nama_produk,
				"message" => "Product ID found successfully"
			);
		} else {
			$response = array(
				"status" => "error", 
				"message" => "Product not found"
			);
		}
	}
	
	$conn = $base->close();
	http_response_code(200);
	header('Access-Control-Allow-Origin: *');
	header("Content-type: application/json; charset=utf-8");
	echo(json_encode($response));
?>
