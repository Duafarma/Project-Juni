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
	$read	= $conn->prepare("SELECT B.top_sdi, B.parameter_sdi, B.diskon1_sdi, B.diskon2_sdi FROM supplier AS A LEFT JOIN supplier_diskon AS B ON A.id_sup=B.id_sup WHERE A.id_sup=:kode");
	$read->bindParam(':kode', $kode, PDO::PARAM_STR);
	$read->execute();
	$view	= $read->fetch(PDO::FETCH_ASSOC);
	$gabung	= '.01.01/SJ/'.$data->romawi(date('m')).'/'.date('y');
	$gobong	= '.01.01/FKT/'.$data->romawi(date('m')).'/'.date('y');
	$inv	= $data->transcodeorder($gabung, 'sj_fkr', 'faktur_retur');
	$fak	= $data->transcodeorder($gobong, 'kode_fkr', 'faktur_retur');
	$limit	= date("Y-m-d", strtotime("+$view[top_sdi] Days", strtotime($catat)));
	$conn	= $base->close();
	$json	= array("kosup" => $inv, "fksup" => $fak, "minorder" => $view['parameter_sdi'], "diskon1" => $view['diskon1_sdi'], "diskon2" => $view['diskon2_sdi'], "jatuhtempo" => $limit);
	http_response_code(200);
	header('Access-Control-Allow-Origin: *');
	header("Content-type: application/json; charset=utf-8");
	//header('Content-type: text/html; charset=UTF-8');
	echo(json_encode($json));
?>