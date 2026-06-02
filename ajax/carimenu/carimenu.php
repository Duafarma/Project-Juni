<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	require_once('../../config/function/paging.php');
	$base	= new DB;
	$secu	= new Security;
	$data	= new Data;
	$paging	= new Paging;
	
	$kode	= $secu->injection($_GET['keycode']);
	$tabel	= '<option value="">-- Select Menu --</option>';
	$conn	= $base->open();
	$master	= $conn->prepare("SELECT id_menu, nama_menu FROM menu WHERE id_kmu=:kode ORDER BY urutan_menu ASC");
	$master->bindParam(':kode', $kode, PDO::PARAM_STR);
	$master->execute();
	$conn	= $base->close();
	while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
		$tabel	.= '<option value="'.$hasil['id_menu'].'">'.$hasil['nama_menu'].'</option>';
	}
	$json	= array("tabel" => $tabel);
	http_response_code(200);
	header("Access-Control-Allow-Origin: *");
	header("Content-type: application/json; charset=utf-8");
	//header('content-type: application/json');
	//header('Content-type: text/html; charset=UTF-8');
	echo(json_encode($json));
?>