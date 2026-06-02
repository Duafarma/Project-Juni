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
			// get query
			$active		= 'Active';
			$qMaster = "SELECT
					'A' as source,
					 A.id_psd, 
					 A.no_bcode, 
					 A.tgl_expired, 
					 A.gudang, 
					 A.tgl_psd, 
				     SUM(A.sisa_psd) AS jumlah,
					 B.nama_pro, 
					 B.berat_pro,
					 B.minstok_pro, 
					 C.harga_phg, 
					 C.hargap_phg, 
					 E.nama_spr 
					 FROM 
    					 produk_stokdetail AS A LEFT JOIN 
    					 produk AS B ON A.id_pro=B.id_pro LEFT JOIN 
    					 produk_harga AS C ON B.id_pro=C.id_pro LEFT JOIN 
    					 kategori_produk AS D ON B.id_kpr=D.id_kpr LEFT JOIN 
    					 satuan_produk AS E ON B.id_spr=E.id_spr 
					 WHERE A.sisa_psd>0 AND B.nama_pro LIKE '%$cari%' AND C.status_phg=:active 
					 GROUP BY B.nama_pro
					 ORDER BY B.nama_pro ASC";
			$master	= $conn->prepare($qMaster);
			$master->bindParam(':active', $active, PDO::PARAM_STR);
			$master->execute();
			if ($master) {
				$hasil= $master->fetchAll(PDO::FETCH_ASSOC);
				http_response_code(200);
			} else {
				$hasil = $save->error;
				http_response_code(500);
			}
		} else {
			$hasil = "Unauthorized";
			http_response_code(401);
		}
		$conn	= $base->close();
		header('Access-Control-Allow-Origin: *');
		header("Content-type: application/json; charset=utf-8");
		echo json_encode(array("result" => $hasil));
	}
?>
