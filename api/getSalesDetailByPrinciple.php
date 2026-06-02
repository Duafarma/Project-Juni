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
		$outlet		= $secu->injection($_POST['outlet'] ?? '');
		$produk		= $secu->injection($_POST['produk'] ?? '');
		$tgl_start	= $secu->injection($_POST['tgl_start'] ?? '');
		$tgl_end	= $secu->injection($_POST['tgl_end'] ?? '');
		$limit		= (int)($secu->injection($_POST['limit'] ?? 100));
		$offset		= (int)($secu->injection($_POST['offset'] ?? 0));
		
		// checking encrypt
		$source = $data->self_apl();
		$sourceKey  = $source['key_apl'];
		
		if (md5($tgl . "#" . $sourceKey) == $encrypt) {
			// Build WHERE conditions
			$whereConditions = ["A.id_tfd != ''"];
			$params = [];
			
			// Filter by principle (default MP0000000002)
			$whereConditions[] = "C.nama_p = :principle";
			$params[':principle'] = $principle;
			
			// Filter by outlet if provided
			if (!empty($outlet)) {
				$whereConditions[] = "B.id_out = :outlet";
				$params[':outlet'] = $outlet;
			}
			
			// Filter by produk if provided
			if (!empty($produk)) {
				$whereConditions[] = "A.id_pro = :produk";
				$params[':produk'] = $produk;
			}
			
			// Filter by start date if provided
			if (!empty($tgl_start)) {
				$whereConditions[] = "B.tgl_tfk >= :tgl_start";
				$params[':tgl_start'] = $tgl_start;
			}
			
			// Filter by end date if provided
			if (!empty($tgl_end)) {
				$whereConditions[] = "B.tgl_tfk <= :tgl_end";
				$params[':tgl_end'] = $tgl_end;
			}
			
			$whereClause = implode(' AND ', $whereConditions);
			
			$qMaster = "SELECT
							A.id_tfd,
							A.id_tfk,
							A.id_psd,
							A.id_pro,
							A.jumlah_tfd,
							A.harga_tfd,
							A.diskon_tfd,
							A.total_tfd,
							B.kode_tfk,
							B.tgl_tfk,
							B.status_tfk,
							C.id_pro,
							C.kode_pro,
							C.nama_pro,
							C.berat_pro,
							C.kategori_obat,
							C.kode_produk_jadi,
							C.nama_p AS principle_id,
							D.id_out,
							D.kode_out,
							D.kode_rs,
							D.nama_out,
							D.ofcode_out,
							E.no_bcode,
							E.tgl_expired,
							E.gudang,
							F.pengiriman_ola,
							G.kode_kot,
							H.nama_rkb,
							I.nama_principle
						FROM
							transaksi_fakturdetail AS A
						LEFT JOIN transaksi_faktur AS B ON
							A.id_tfk = B.id_tfk
						LEFT JOIN produk AS C ON
							A.id_pro = C.id_pro
						LEFT JOIN outlet AS D ON
							B.id_out = D.id_out
						LEFT JOIN produk_stokdetail AS E ON
							A.id_psd = E.id_psd
						LEFT JOIN outlet_alamat AS F ON
							D.id_out = F.id_out
						LEFT JOIN kategori_outlet AS G ON
							D.id_kot = G.id_kot
						LEFT JOIN regional_kabupaten AS H ON
							F.id_rkb = H.id_rkb
						LEFT JOIN master_principle AS I ON
							C.nama_p = I.id_mp
						WHERE
							$whereClause
						ORDER BY
							B.tgl_tfk DESC, B.kode_tfk DESC, C.nama_pro ASC
						LIMIT :limit OFFSET :offset";
			
			$master	= $conn->prepare($qMaster);
			
			// Bind all parameters
			foreach ($params as $key => $value) {
				$master->bindValue($key, $value, PDO::PARAM_STR);
			}
			$master->bindValue(':limit', $limit, PDO::PARAM_INT);
			$master->bindValue(':offset', $offset, PDO::PARAM_INT);
			
			$master->execute();
			
			if ($master) {
				$hasil = $master->fetchAll(PDO::FETCH_ASSOC);
				
				// Get total count
				$qCount = "SELECT COUNT(A.id_tfd) as total
						   FROM transaksi_fakturdetail AS A
						   LEFT JOIN transaksi_faktur AS B ON A.id_tfk = B.id_tfk
						   LEFT JOIN produk AS C ON A.id_pro = C.id_pro
						   LEFT JOIN outlet AS D ON B.id_out = D.id_out
						   WHERE $whereClause";
				
				$countStmt = $conn->prepare($qCount);
				foreach ($params as $key => $value) {
					$countStmt->bindValue($key, $value, PDO::PARAM_STR);
				}
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
