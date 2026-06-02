<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$sistem	= $data->sistem('url_sis');
	$catat	= date('Y-m-d H:i:s');
	$admin	= $secu->injection(@$_COOKIE['adminkuy']);
	$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
	$kode	= $secu->injection(@$_POST['x']);
	$conn	= $base->open();
	//READ DATA
	$read	= $conn->prepare("SELECT
										tf.id_tfk,
										tf.id_out,
										tf.created_at,
										o.id_out,
										o.kode_out,
										o.nama_out
									FROM transaksi_faktur AS tf 
									LEFT JOIN outlet AS o
										ON tf.id_out = o.id_out
									WHERE tf.id_tfk=:kode");
	$read->bindParam(':kode', $kode, PDO::PARAM_STR);
	$read->execute();
	$view	= $read->fetch(PDO::FETCH_ASSOC);
	$conn	= $base->close();
	$json	= array("id_out" => $view['id_out'], "kode_out" => $view['kode_out'], "nama_out" => $view['nama_out'], "tf_created" => date("Y-m-d", strtotime($view['created_at'])));
	http_response_code(200);
	header('Access-Control-Allow-Origin: *');
	header("Content-type: application/json; charset=utf-8");
	//header('Content-type: text/html; charset=UTF-8');
	echo(json_encode($json));
?>