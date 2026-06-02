<?php
	// must add request validation
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
		$encrypt= $secu->injection($_GET['encrypt']);
		// $cart	= $secu->injection($_POST['cart']);
		// $notin 	= empty($cart) ? "A.id_psd!=''" : "A.id_psd NOT IN('".str_replace("-", "', '", $cart)."')";
		// checking encrypt
		$source = $data->self_apl();
		$sourceKey  = $source['key_apl'];
		if (md5($tgl. "#" . $sourceKey) == $encrypt) {
			$qMaster 		= "SELECT 
								'A' as source,
								A.id_tfk,
                                A.kode_tfk,
                                A.id_out,
								A.status_failing,
								A.status_dokumen,
                                A.tgl_tfk,
                                A.status_ceklis,
                                A.status_tfkkb, 
								B.id_out,
								B.nama_out
								FROM transaksi_faktur AS A 
                                LEFT JOIN outlet AS B ON 
								A.id_out = B.id_out 
								WHERE A.id_tfk 
							    AND DATE(A.tgl_tfk) = :tgl
								ORDER BY A.tgl_tfk DESC";
			$master	= $conn->prepare($qMaster);
			$master->bindParam(':tgl', $tgl, PDO::PARAM_STR); // Binding tanggal hari ini

			$master->execute();
			if ($master) {
				$hasil= $master->fetchAll(PDO::FETCH_ASSOC);
				http_response_code(200);
			} else {
				$hasil = $save->error;
				 (500);
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
