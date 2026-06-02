<?php
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		http_response_code(405);
		header('Access-Control-Allow-Origin: *');
		header("Content-type: application/json; charset=utf-8");
		echo "Method Not Allowed";
	} else {
		require_once('../../config/connection/connection.php');
		require_once('../../config/connection/security.php');
		require_once('../../config/function/data.php');
		require_once('../../config/function/paging.php');
		$base	= new DB;
		$secu	= new Security;	
		$data	= new Data;
		$kode	= $secu->injection($_POST['id_pro']);
		$tgl1	= $secu->injection($_POST['tgl1']);
		$tgl2	= $secu->injection($_POST['tgl2']);
		$stok	= 0;
		$awal	= $data->stokawal($kode, $tgl1);
		$in		= $data->stokin($kode, $tgl1, $tgl2);
		$out	= $data->stokout($kode, $tgl1, $tgl2);
		$akhir	= ($awal + $in) - $out;
		$conn	= $base->close();
		if ($akhir != 0) {
			$stok	= $akhir;
		}
		$json	= array("stok" => $stok);
		http_response_code(200);
		header('Access-Control-Allow-Origin: *');
		header("Content-type: application/json; charset=utf-8");
		echo json_encode($json);
	}
?>