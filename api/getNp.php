<?php
	// API khusus untuk Penjualan P - hanya ambil kolom yang diperlukan untuk efisiensi
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
			$cari	= $secu->injection(@$_GET['key']);
			$pecah	= explode('_', $cari);
			$tahun1	= empty($pecah[0]) ? 2023 : $pecah[0];
			$tahun2	= empty($pecah[1]) ? date('Y') : $pecah[1];
			
			// Query dengan UNION ALL transaksi_faktur dan transaksi_faktur_pim
			$qMaster = "SELECT
					subtot_tfk as total_tfd,
					tgl_tfk
				FROM
					transaksi_faktur
				WHERE
					id_tfk != '' 
					AND pajak_tfkt = 'penjualan'
					AND YEAR(tgl_tfk) >= :tahun1 
					AND YEAR(tgl_tfk) <= :tahun2
				UNION ALL
				SELECT
					subtot_tfk as total_tfd,
					tgl_tfk
				FROM
					transaksi_faktur_pim
				WHERE
					id_tfk != '' 
					AND pajak_tfkt = 'penjualan'
					AND YEAR(tgl_tfk) >= :tahun1 
					AND YEAR(tgl_tfk) <= :tahun2";
			
			$master	= $conn->prepare($qMaster);
			$master->bindValue(':tahun1', $tahun1, PDO::PARAM_INT);
			$master->bindValue(':tahun2', $tahun2, PDO::PARAM_INT);
			$master->execute();
			$hasil= $master->fetchAll(PDO::FETCH_ASSOC);
			http_response_code(200);
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
