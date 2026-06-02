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
	$read	= $conn->prepare("SELECT A.total_tre, A.tglfak_tre, A.tgl_limit, B.nama_sup FROM transaksi_receive AS A LEFT JOIN supplier AS B ON A.id_sup=B.id_sup WHERE A.id_tre=:kode");
	$read->bindParam(':kode', $kode, PDO::PARAM_STR);
	$read->execute();
	$view	= $read->fetch(PDO::FETCH_ASSOC);

	$mbayar	= $conn->prepare("SELECT SUM(jumlah_pre) AS total FROM pembayaran_receive WHERE id_tre=:kode");
	$mbayar->bindParam(':kode', $kode, PDO::PARAM_STR);
	$mbayar->execute();
	$hbayar	= $mbayar->fetch(PDO::FETCH_ASSOC);

	$sisa	= $view['total_tre'] - $hbayar['total'];
	$conn	= $base->close();

	$json	= array("namasup" => $view['nama_sup'], "tglterima" => $view['tglfak_tre'], "tgltempo" => $view['tgl_limit'], "tagihan" => $data->angka($view['total_tre']), "dibayar" => $data->angka($hbayar['total']), "sisa" => $data->angka($sisa));
	http_response_code(200);
	header("Access-Control-Allow-Origin: *");
	header("Content-type: application/json; charset=utf-8");
	//header('content-type: application/json');
	//header('Content-type: text/html; charset=UTF-8');
	echo(json_encode($json));
?>