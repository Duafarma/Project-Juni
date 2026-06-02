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
		
// 			$outlet	= empty($pecah[0]) ? "" : "AND B.id_out='$pecah[0]'";
// 			$produk	= empty($pecah[1]) ? "" : "AND A.id_pro='$pecah[1]'";
// 			$tgl1	= empty($pecah[2]) ? "" : "AND B.tgl_tfk>='$pecah[2]'";
// 			$tgl2	= empty($pecah[3]) ? "" : "AND B.tgl_tfk<='$pecah[3]'";
			// get query
// 			$active		= 'Active';
			$qMaster = "SELECT 'A' as source, A.id,A.nama_pro,A.no_bcode,A.qty,A.bcode_so,A.qty_so, A.created_at, B.tgl_expired,C.kategori_obat,C.kode_produk_jadi FROM so AS A
					LEFT JOIN produk_stokdetail AS B ON A.id_psd = B.id_psd LEFT JOIN produk AS C ON A.id_pro=C.id_pro WHERE A.id ORDER BY A.created_at DESC, A.nama_pro DESC";
    					 
			$master	= $conn->prepare($qMaster);
// 			$master->bindParam(':active', $active, PDO::PARAM_STR);
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
