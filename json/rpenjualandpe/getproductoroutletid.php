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
	$search_input = $secu->injection(@$_GET['search_input']);
	
	//READ DATA
	if($valid==false){
		$response = array("status" => "error", "message" => "Session login anda habis...");
	} else {
		$result = array();
		
		// Check if input starts with [PRODUK] or [OUTLET]
		if(strpos($search_input, '[PRODUK]') === 0) {
			// Extract product name (remove [PRODUK] prefix)
			$product_name = trim(str_replace('[PRODUK]', '', $search_input));
			
			// Get product ID
			$sql = "SELECT id_pro FROM produk WHERE nama_pro = :nama_pro AND status_pro = 'Active'";
			$stmt = $conn->prepare($sql);
			$stmt->bindValue(':nama_pro', $product_name, PDO::PARAM_STR);
			$stmt->execute();
			
			if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$result = array(
					"status" => "success",
					"type" => "produk",
					"id_produk" => $row['id_pro'],
					"nama_produk" => $product_name
				);
			} else {
				$result = array("status" => "error", "message" => "Produk tidak ditemukan");
			}
			
		} elseif(strpos($search_input, '[OUTLET]') === 0) {
			// Extract outlet name (remove [OUTLET] prefix)
			$outlet_name = trim(str_replace('[OUTLET]', '', $search_input));
			
			// Get outlet ID
			$sql = "SELECT id_out FROM outlet WHERE nama_out = :nama_out AND status_out = 'Active'";
			$stmt = $conn->prepare($sql);
			$stmt->bindValue(':nama_out', $outlet_name, PDO::PARAM_STR);
			$stmt->execute();
			
			if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$result = array(
					"status" => "success",
					"type" => "outlet",
					"id_outlet" => $row['id_out'],
					"nama_outlet" => $outlet_name
				);
			} else {
				$result = array("status" => "error", "message" => "Outlet tidak ditemukan");
			}
			
		} else {
			// Try to search as product name first
			$sql = "SELECT id_pro FROM produk WHERE nama_pro = :nama_pro AND status_pro = 'Active'";
			$stmt = $conn->prepare($sql);
			$stmt->bindValue(':nama_pro', $search_input, PDO::PARAM_STR);
			$stmt->execute();
			
			if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$result = array(
					"status" => "success",
					"type" => "produk",
					"id_produk" => $row['id_pro'],
					"nama_produk" => $search_input
				);
			} else {
				// Try to search as outlet name
				$sql = "SELECT id_out FROM outlet WHERE nama_out = :nama_out AND status_out = 'Active'";
				$stmt = $conn->prepare($sql);
				$stmt->bindValue(':nama_out', $search_input, PDO::PARAM_STR);
				$stmt->execute();
				
				if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
					$result = array(
						"status" => "success",
						"type" => "outlet",
						"id_outlet" => $row['id_out'],
						"nama_outlet" => $search_input
					);
				} else {
					$result = array("status" => "error", "message" => "Produk atau outlet tidak ditemukan");
				}
			}
		}
		
		$response = $result;
	}
	
	header('Content-Type: application/json');
	echo json_encode($response);
	
	$conn = $base->close();
?>
