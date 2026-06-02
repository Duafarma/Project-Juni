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
		$base	= new DB;
		$data	= new Data;
		$tgl	= date('Y-m-d');
		$conn	= $base->open();
		$hasil 	= "Error";
		
		// Get parameters
		$encrypt	= $secu->injection($_GET['encrypt'] ?? '');
		$principle	= $secu->injection($_POST['principle'] ?? 'MP0000000002');
		$status		= $secu->injection($_POST['status'] ?? 'Active');
		$limit		= (int)($secu->injection($_POST['limit'] ?? 100));
		$offset		= (int)($secu->injection($_POST['offset'] ?? 0));
		
		// checking encrypt
		$source = $data->self_apl();
		$sourceKey  = $source['key_apl'];
		
		if (md5($tgl . "#" . $sourceKey) == $encrypt) {
			$qMaster = "SELECT
							A.id_pro,
							A.kode_pro,
							A.nama_pro,
							A.nama_p AS principle_id,
							A.berat_pro,
							A.rak_pro,
							A.section_pro,
							A.minstok_pro,
							A.status_pro,
							A.kategori_obat,
							A.kode_produk_jadi,
							A.no_nie,
							A.tgl_nie,
							A.zat_pre,
							A.kekuatan,
							A.ccp,
							B.nama_kpr,
							B.satuan_kpr,
							C.nama_spr,
							D.nama_principle,
							E.harga_phg
						FROM
							produk AS A
						LEFT JOIN kategori_produk AS B ON
							A.id_kpr = B.id_kpr
						LEFT JOIN satuan_produk AS C ON
							A.id_spr = C.id_spr
						LEFT JOIN master_principle AS D ON
							A.nama_p = D.id_mp
						LEFT JOIN produk_harga AS E ON
							A.id_pro = E.id_pro AND E.status_phg = :status
						WHERE
							A.nama_p = :principle AND
							A.status_pro = :status
						ORDER BY
							A.nama_pro ASC
						LIMIT :limit OFFSET :offset";
			
			$master	= $conn->prepare($qMaster);
			$master->bindValue(':principle', $principle, PDO::PARAM_STR);
			$master->bindValue(':status', $status, PDO::PARAM_STR);
			$master->bindValue(':limit', $limit, PDO::PARAM_INT);
			$master->bindValue(':offset', $offset, PDO::PARAM_INT);
			
			$master->execute();
			
			if ($master) {
				$hasil = $master->fetchAll(PDO::FETCH_ASSOC);
				
				// Get total count
				$qCount = "SELECT COUNT(A.id_pro) as total
						   FROM produk AS A
						   WHERE A.nama_p = :principle AND A.status_pro = :status";
				
				$countStmt = $conn->prepare($qCount);
				$countStmt->bindValue(':principle', $principle, PDO::PARAM_STR);
				$countStmt->bindValue(':status', $status, PDO::PARAM_STR);
				$countStmt->execute();
				$totalData = $countStmt->fetch(PDO::FETCH_ASSOC);
				
				$response = [
					"status" => "success",
					"data" => $hasil,
					"total" => (int)$totalData['total'],
					"limit" => $limit,
					"offset" => $offset,
					"principle" => $principle
				];
				
				http_response_code(200);
				header('Access-Control-Allow-Origin: *');
				header("Content-type: application/json; charset=utf-8");
				echo json_encode($response);
			} else {
				http_response_code(500);
				header('Access-Control-Allow-Origin: *');
				header("Content-type: application/json; charset=utf-8");
				echo json_encode(["error" => "Database query failed"]);
			}
		} else {
			http_response_code(401);
			header('Access-Control-Allow-Origin: *');
			header("Content-type: application/json; charset=utf-8");
			echo json_encode(["error" => "Unauthorized - Invalid encryption key"]);
		}
		
		$conn = $base->close();
	}
?>
