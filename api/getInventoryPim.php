<?php
	// must add request validation
	if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
		http_response_code(405);
		header('Access-Control-Allow-Origin: *');
		header("Content-type: application/json; charset=utf-8");
		echo "Method Not Allowed";
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
		
		// checking encrypt
		$encrypt= $secu->injection($_GET['encrypt']);
		$source = $data->self_apl();
		$sourceKey  = $source['key_apl'];
		if (md5($tgl. "#" . $sourceKey) == $encrypt) {
			// checking params
			$cari	= $secu->injection(@$_GET['caridata']);
			$cabang	= $secu->injection(@$_GET['cabang']) ?: 'ALL';
			
			// get query for Inventory PIM
			$active		= 'Active';
			$nama_p		= 'MP0000000003'; // PIM principle
			
			$qMaster = "SELECT
					'MDN' as source,
					A.id_psd,
					A.no_bcode, 
					A.tgl_expired,
					A.created_at,
					A.gudang, 
					A.tgl_psd, 
					A.sisa_psd,
					B.nama_pro, 
					B.berat_pro,
					B.minstok_pro, 
					C.harga_phg, 
					C.hargap_phg, 
					E.nama_spr 
				FROM 
					produk_stokdetail AS A 
					LEFT JOIN produk AS B ON A.id_pro=B.id_pro 
					LEFT JOIN produk_harga AS C ON B.id_pro=C.id_pro 
					LEFT JOIN kategori_produk AS D ON B.id_kpr=D.id_kpr 
					LEFT JOIN satuan_produk AS E ON B.id_spr=E.id_spr 
				WHERE A.sisa_psd>0 
					AND C.status_phg=:active 
					AND B.nama_p = :nama_p";
			
			// Add search filter if provided
			if (!empty($cari)) {
				$qMaster .= " AND B.nama_pro LIKE :cari";
			}
			
			$qMaster .= " ORDER BY B.nama_pro ASC LIMIT 1000";
			
			$master	= $conn->prepare($qMaster);
			$master->bindParam(':active', $active, PDO::PARAM_STR);
			$master->bindParam(':nama_p', $nama_p, PDO::PARAM_STR);
			
			if (!empty($cari)) {
				$searchTerm = '%' . $cari . '%';
				$master->bindParam(':cari', $searchTerm, PDO::PARAM_STR);
			}
			
			$master->execute();
			
			if ($master) {
				$results = $master->fetchAll(PDO::FETCH_ASSOC);
				
				// Format hasil dengan DOI calculation
				$formatted_results = [];
				$no = 1;
				foreach ($results as $row) {
					// Calculate DOI (Days On Inventory)
					$doi_days = 0;
					$doi_text = 'N/A';
					if (!empty($row['created_at'])) {
						$created_date = date_create($row['created_at']);
						$current_date = date_create();
						$doi_diff = date_diff($created_date, $current_date);
						$doi_days = $doi_diff->days;
						$doi_text = $doi_days . ' Hari';
					}
					
					// Format expired date
					$expired_formatted = 'N/A';
					if (!empty($row['tgl_expired']) && $row['tgl_expired'] != '0000-00-00') {
						$expired_formatted = date('Y-m', strtotime($row['tgl_expired']));
					}
					
					$formatted_results[] = [
						'no' => $no++,
						'source' => $row['source'],
						'source_cabang' => 'Medan',
						'id_psd' => $row['id_psd'],
						'no_bcode' => $row['no_bcode'],
						'nama_produk' => $row['nama_pro'],
						'expired_date' => $expired_formatted,
						'gudang' => $row['gudang'],
						'sisa_stok' => (int)$row['sisa_psd'],
						'sisa_stok_formatted' => number_format($row['sisa_psd'], 0, ',', '.'),
						'doi' => $doi_text,
						'doi_days' => $doi_days,
						'harga' => (int)$row['harga_phg'],
						'harga_formatted' => number_format($row['harga_phg'], 0, ',', '.'),
						'satuan' => $row['nama_spr']
					];
				}
				
				$hasil = [
					'status' => 'success',
					'timestamp' => date('Y-m-d H:i:s'),
					'cabang' => 'Medan',
					'total_items' => count($formatted_results),
					'data' => $formatted_results
				];
				http_response_code(200);
			} else {
				$hasil = ['status' => 'error', 'message' => 'Query failed'];
				http_response_code(500);
			}
		} else {
			$hasil = ['status' => 'error', 'message' => 'Unauthorized'];
			http_response_code(401);
		}
		$conn	= $base->close();
		header('Access-Control-Allow-Origin: *');
		header("Content-type: application/json; charset=utf-8");
		echo json_encode($hasil);
	}
?>
