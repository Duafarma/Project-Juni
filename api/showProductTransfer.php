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
		$cart	= $secu->injection($_POST['cart']);
		$notin 	= empty($cart) ? "A.id_psd!=''" : "A.id_psd NOT IN('".str_replace("-", "', '", $cart)."')";
		// checking encrypt
		$source = $data->self_apl();
		$sourceKey  = $source['key_apl'];
		if (md5($tgl. "#" . $sourceKey) == $encrypt) {
			$status	= 'Active';
			$qMaster = "SELECT
							A.id_psd,
							A.no_bcode,
							A.tgl_expired,
							A.sisa_psd,
							B.id_pro,
							B.kode_pro,
							B.nama_pro,
							B.berat_pro,
							C.harga_phg,
							D.nama_kpr,
							D.satuan_kpr,
							E.nama_spr,
							A.id_trd,
							A.tgl_psd,
							A.gudang
						FROM
							produk_stokdetail AS A
						LEFT JOIN produk AS B ON
							A.id_pro = B.id_pro
						LEFT JOIN produk_harga AS C ON
							B.id_pro = C.id_pro
						LEFT JOIN kategori_produk AS D ON
							B.id_kpr = D.id_kpr
						LEFT JOIN satuan_produk AS E ON
							B.id_spr = E.id_spr
						WHERE
							$notin AND 
							A.sisa_psd > 0 AND 
							C.status_phg =:status
						GROUP BY
							A.id_psd,
							A.no_bcode,
							A.tgl_expired,
							A.sisa_psd,
							B.id_pro,
							B.kode_pro,
							B.nama_pro,
							B.berat_pro,
							C.harga_phg,
							D.nama_kpr,
							D.satuan_kpr,
							E.nama_spr,
							A.id_trd,
							A.tgl_psd,
							A.gudang";
			$master	= $conn->prepare($qMaster);
			$master->bindParam(':status', $status, PDO::PARAM_STR);
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
