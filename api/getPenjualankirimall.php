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
	    $tanggal= date('Y-m-d');
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
			// get query
			$qMaster = "SELECT 'A' as source, 
			                    B.created_at, 
			                    B.kode_tfk, 
			                    B.tgl_tfk,
			                    B.status_tfkkb,
			                    B.status_tfkkf, 
			                    B.po_tfk,
			                    B.status_dokumen,
			                    B.status_f_pajak, 
			                    B.status_failing, 
			                    B.tglpo_tfk, 
			                    B.tgl_limit, 
			                    B.status_tfk, TIMESTAMPDIFF(DAY, B.tgl_limit, :tanggal) AS jarak, 
			                    D.nama_out, 
			                    D.ofcode_out 
			                    FROM transaksi_faktur AS B LEFT JOIN outlet AS D ON B.id_out=D.id_out 
			                    WHERE MONTH(B.tgl_tfk) = MONTH(CURRENT_DATE()) AND YEAR(B.tgl_tfk) = YEAR(CURRENT_DATE()) AND B.kode_tfk LIKE '%$cari%' 
			                    ORDER BY B.tgl_tfk DESC, B.kode_tfk DESC";
			$master	= $conn->prepare($qMaster);
			$master->bindParam(':tanggal', $tanggal, PDO::PARAM_STR);
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
