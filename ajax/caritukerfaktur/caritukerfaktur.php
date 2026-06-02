<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	require_once('../../config/function/paging.php');
	$base	= new DB;
	$secu	= new Security;
	$data	= new Data;
	$paging	= new Paging;
	$conn	= $base->open();
	
	$kode	= $secu->injection($_POST['x']);
	$read	= $conn->prepare("SELECT A.tgl_tfk, A.tgl_limit, B.nama_out FROM transaksi_faktur AS A LEFT JOIN outlet AS B ON A.id_out=B.id_out WHERE A.id_tfk=:kode");
	$read->bindParam(':kode', $kode, PDO::PARAM_STR);
	$read->execute();
	$view	= $read->fetch(PDO::FETCH_ASSOC);

	// $mbayar	= $conn->prepare("SELECT * FROM tuker_faktur WHERE id_tkf_t=:kode");
	// $mbayar->bindParam(':kode', $kode, PDO::PARAM_STR);
	// $mbayar->execute();
	// $hbayar	= $mbayar->fetch(PDO::FETCH_ASSOC);

	$conn	= $base->close();

	$json	= array("namaoutlet" => $view['nama_out'], "tglfaktur" => $view['tgl_tfk'], "tgltempo" => $view['tgl_limit']);
	http_response_code(200);
	header("Access-Control-Allow-Origin: *");
	header("Content-type: application/json; charset=utf-8");
	//header('content-type: application/json');
	//header('Content-type: text/html; charset=UTF-8');
	echo(json_encode($json));
?>