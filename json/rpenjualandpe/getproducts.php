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
	$search = $secu->injection(@$_GET['search']);
	$type = $secu->injection(@$_GET['type']); // Add type parameter
	
	//READ DATA
	if($valid==false){
		$response = array("status" => "error", "message" => "Session login anda habis...");
	} else {
		$items = [];
		
		// Filter by type if specified
		if($type === 'produk') {
			// Get unique products from DPE principle (nama_p = MP0000000002) and VISION BLU products
			$sql_products = "SELECT DISTINCT C.nama_pro, 'produk' as type
					FROM produk AS C 
					WHERE (C.nama_p = 'MP0000000002' OR C.nama_pro LIKE '%VISION BLU%') 
					AND C.status_pro = 'Active' 
					" . (!empty($search) ? "AND C.nama_pro LIKE '%$search%'" : "") . "
					ORDER BY C.nama_pro ASC";
			
			$master_products = $conn->prepare($sql_products);
			$master_products->execute();
			while($hasil = $master_products->fetch(PDO::FETCH_ASSOC)){
				$items[] = array(
					'name' => $hasil['nama_pro'],
					'type' => 'produk'
				);
			}
		} else if($type === 'outlet') {
			// Get unique outlets that have DPE or VISION BLU transactions
			$sql_outlets = "SELECT DISTINCT D.nama_out, 'outlet' as type
					FROM transaksi_fakturdetail AS A 
					LEFT JOIN transaksi_faktur AS B ON A.id_tfk = B.id_tfk
					LEFT JOIN produk AS C ON A.id_pro = C.id_pro
					LEFT JOIN outlet AS D ON B.id_out = D.id_out
					WHERE (C.nama_p = 'MP0000000002' OR C.nama_pro LIKE '%VISION BLU%') 
					AND D.status_out = 'Active' 
					" . (!empty($search) ? "AND D.nama_out LIKE '%$search%'" : "") . "
					ORDER BY D.nama_out ASC";
			
			$master_outlets = $conn->prepare($sql_outlets);
			$master_outlets->execute();
			while($hasil = $master_outlets->fetch(PDO::FETCH_ASSOC)){
				$items[] = array(
					'name' => $hasil['nama_out'],
					'type' => 'outlet'
				);
			}
		} else {
			// Get both products and outlets (default behavior)
			// Get unique products from DPE principle (nama_p = MP0000000002) and VISION BLU products
			$sql_products = "SELECT DISTINCT C.nama_pro, 'produk' as type
					FROM produk AS C 
					WHERE (C.nama_p = 'MP0000000002' OR C.nama_pro LIKE '%VISION BLU%') 
					AND C.status_pro = 'Active' 
					" . (!empty($search) ? "AND C.nama_pro LIKE '%$search%'" : "") . "
					ORDER BY C.nama_pro ASC";
			
			// Get unique outlets that have DPE or VISION BLU transactions
			$sql_outlets = "SELECT DISTINCT D.nama_out, 'outlet' as type
					FROM transaksi_fakturdetail AS A 
					LEFT JOIN transaksi_faktur AS B ON A.id_tfk = B.id_tfk
					LEFT JOIN produk AS C ON A.id_pro = C.id_pro
					LEFT JOIN outlet AS D ON B.id_out = D.id_out
					WHERE (C.nama_p = 'MP0000000002' OR C.nama_pro LIKE '%VISION BLU%') 
					AND D.status_out = 'Active' 
					" . (!empty($search) ? "AND D.nama_out LIKE '%$search%'" : "") . "
					ORDER BY D.nama_out ASC";
			
			// Get products
			$master_products = $conn->prepare($sql_products);
			$master_products->execute();
			while($hasil = $master_products->fetch(PDO::FETCH_ASSOC)){
				$items[] = array(
					'name' => $hasil['nama_pro'],
					'type' => 'produk',
					'display' => '[PRODUK] ' . $hasil['nama_pro']
				);
			}
			
			// Get outlets
			$master_outlets = $conn->prepare($sql_outlets);
			$master_outlets->execute();
			while($hasil = $master_outlets->fetch(PDO::FETCH_ASSOC)){
				$items[] = array(
					'name' => $hasil['nama_out'],
					'type' => 'outlet',
					'display' => '[OUTLET] ' . $hasil['nama_out']
				);
			}
		}
		
		$response = array(
			"status" => "success", 
			"data" => $items,
			"message" => "Data loaded successfully",
			"search_term" => $search,
			"type_filter" => $type
		);
	}
	
	$conn = $base->close();
	http_response_code(200);
	header('Access-Control-Allow-Origin: *');
	header("Content-type: application/json; charset=utf-8");
	echo(json_encode($response));
?>
