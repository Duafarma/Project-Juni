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
	$cari = $secu->injection(@$_POST['cari']);
	$type = $secu->injection(@$_POST['type']);
	
	//READ DATA
	if($valid==false){
		$response = array("status" => "error", "message" => "Session login anda habis...");
	} else {
		if(empty($cari)) {
			$response = array("status" => "error", "message" => "Parameter pencarian tidak boleh kosong");
		} else {
			if($type === 'outlet') {
				// Search outlet by name and get the outlet ID
				$sql = "SELECT id_out, nama_out FROM outlet WHERE nama_out LIKE '%$cari%' AND status_out = 'Active' LIMIT 1";
				$stmt = $conn->prepare($sql);
				$stmt->execute();
				$result = $stmt->fetch(PDO::FETCH_ASSOC);
				
				if($result) {
					$response = array(
						"status" => "success", 
						"id_outlet" => $result['id_out'],
						"nama_outlet" => $result['nama_out'],
						"type" => "outlet",
						"message" => "Outlet found successfully"
					);
				} else {
					$response = array("status" => "error", "message" => "Outlet tidak ditemukan");
				}
			} else if($type === 'produk') {
				// Search product by name and get the product ID
				$sql = "SELECT id_pro, nama_pro FROM produk WHERE nama_pro LIKE '%$cari%' AND status_pro = 'Active' LIMIT 1";
				$stmt = $conn->prepare($sql);
				$stmt->execute();
				$result = $stmt->fetch(PDO::FETCH_ASSOC);
				
				if($result) {
					$response = array(
						"status" => "success", 
						"id_produk" => $result['id_pro'],
						"nama_produk" => $result['nama_pro'],
						"type" => "produk",
						"message" => "Produk found successfully"
					);
				} else {
					$response = array("status" => "error", "message" => "Produk tidak ditemukan");
				}
			} else {
				$response = array("status" => "error", "message" => "Tipe pencarian tidak valid");
			}
		}
	}
	
	$conn = $base->close();
	http_response_code(200);
	header('Access-Control-Allow-Origin: *');
	header("Content-type: application/json; charset=utf-8");
	echo(json_encode($response));
?>
