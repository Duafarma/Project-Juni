<?php
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
			$supp	= empty($pecah[0]) ? "" : "AND B.id_sup='$pecah[0]'"; 
			$produk	= empty($pecah[1]) ? "" : "AND A.id_pro='$pecah[1]'"; 
			$tgl1	= empty($pecah[2]) ? "" : "AND C.tgl_tor>='$pecah[2]'"; 
			$tgl2	= empty($pecah[3]) ? "" : "AND C.tgl_tor<='$pecah[3]'"; 
			// get query
			$status	= 'Active';
			$qMaster = "SELECT 
							'A' as source,
							A.bcode_trd, 
							A.tbcode_trd, 
							A.jumlah_trd, 
							A.harga_trd, 
							A.diskon_trd, 
							A.total_trd, 
							B.fak_tre, 
							B.tglfak_tre, 
							B.tgl_limit, 
							B.status_tre, 
							TIMESTAMPDIFF(DAY, B.tgl_limit, :tanggal) AS jarak, 
							C.tgl_tor,
							C.kode_tor, 
							D.nama_pro, 
							D.kategori_obat, 
							D.kode_produk_jadi, 
							D.kode_pro,
							F.nama_sup 
						FROM 
							transaksi_receivedetail AS A 
						INNER JOIN transaksi_receive AS B ON 
							A.id_tre=B.id_tre 
						LEFT JOIN transaksi_order AS C ON 
							B.id_tor=C.id_tor 
						LEFT JOIN produk AS D ON 
							A.id_pro=D.id_pro 
						LEFT JOIN supplier AS F ON 
							B.id_sup=F.id_sup 
						WHERE 
							A.id_trd!='' $supp $produk $tgl1 $tgl2 
						ORDER BY 
							B.tgl_tre DESC, 
							A.created_at DESC";
			$master	= $conn->prepare($qMaster);
			$master->bindParam(':tanggal', $tgl, PDO::PARAM_STR);
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