<?php
	// must add request validation
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		http_response_code(405);
		header('Access-Control-Allow-Origin: *');
		header("Content-type: application/json; charset=utf-8");
		echo json_encode(["error" => "Method Not Allowed"]);
		exit;
	} else {
		require_once('../config/connection/connection.php');
		require_once('../config/connection/security.php');
		require_once('../config/function/data.php');
		
		$secu	= new Security;
		$data	= new Data;
		$tgl	= date('Y-m-d');
		
		// Get parameters
		$encrypt	= $secu->injection($_POST['encrypt'] ?? ($_GET['encrypt'] ?? ''));
		$principle	= $secu->injection($_POST['principle'] ?? 'MP0000000002');
		$outlet		= $secu->injection($_POST['outlet'] ?? '');
		$produk		= $secu->injection($_POST['produk'] ?? '');
		$tgl_start	= $secu->injection($_POST['tgl_start'] ?? '');
		$tgl_end	= $secu->injection($_POST['tgl_end'] ?? '');
		$page		= (int)($secu->injection($_POST['page'] ?? 1));
		$limit		= (int)($secu->injection($_POST['limit'] ?? 100));
		
		// Get source key from database
		$appData = $data->active_apl_config();
		$sourceKey = $appData['key_apl'] ?? '';
		
		if (empty($sourceKey)) {
			http_response_code(500);
			header('Access-Control-Allow-Origin: *');
			header("Content-type: application/json; charset=utf-8");
			echo json_encode([
				"status" => 500,
				"message" => "Source key not found in database"
			]);
			exit;
		}
		
		if (md5($tgl . "#" . $sourceKey) == $encrypt) {
			try {
				$response = $data->get_sales_by_principle($principle, $outlet, $tgl_start, $tgl_end, $page, $limit, $produk);
					
					http_response_code(200);
					header('Access-Control-Allow-Origin: *');
					header("Content-type: application/json; charset=utf-8");
					echo json_encode($response);
				
			} catch (Exception $e) {
				http_response_code(500);
				header('Access-Control-Allow-Origin: *');
				header("Content-type: application/json; charset=utf-8");
				echo json_encode([
					"status" => 500,
					"message" => "Database error: " . $e->getMessage()
				]);
			}
		} else {
			http_response_code(401);
			header('Access-Control-Allow-Origin: *');
			header("Content-type: application/json; charset=utf-8");
			echo json_encode([
				"status" => 401,
				"message" => "Unauthorized - Invalid encryption key"
			]);
		}
	}
?>
