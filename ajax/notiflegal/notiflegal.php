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
	$sistem	= $data->sistem('url_sis');
	//ACCESS DATA
	$admin	= $secu->injection(@$_COOKIE['adminkuy']);
	$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
	$level	= $secu->injection(@$_COOKIE['jeniskuy']);
	$valid	= $secu->validadmin($admin, $kunci);
	//POST DATA
	$kode	= $secu->injection(@$_POST['c']);
	$tanggal= date('Y-m-d');
	//READ DATA
	$nomor	= 1;
	if($valid==false){
		$tabel	= '<tr><td colspan="4">Session login anda habis...</td></tr>';
	} else {
		$tabel	= '';
		//Kategori
		$read	= $conn->prepare("SELECT nama_klg, parameter_klg FROM kategori_legal WHERE id_klg=:kode");
		$read->bindParam(':kode', $kode, PDO::PARAM_STR);
		$read->execute();
		$view	= $read->fetch(PDO::FETCH_ASSOC);
		//Outlet
		$master	= $conn->prepare("SELECT D.expired_ole, D.parameter_klg, D.selisih, D.nama_out FROM(SELECT A.id_ole, A.expired_ole, B.parameter_klg, B.notif_klg, TIMESTAMPDIFF(MONTH, :tanggal, A.expired_ole) AS selisih, C.nama_out FROM outlet_legal AS A LEFT JOIN kategori_legal AS B ON A.id_klg=B.id_klg LEFT JOIN outlet AS C ON A.id_out=C.id_out) AS D WHERE D.notif_klg='Active' AND D.selisih<=D.parameter_klg ORDER BY D.nama_out ASC");
		$master->bindParam(':tanggal', $tanggal, PDO::PARAM_STR);
		$master->execute();
		while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			$tabel	.= '<tr><td><center>'.$nomor.'</center></td><td>'.$hasil['nama_out'].'</td><td>'.$hasil['expired_ole'].'</td><td>'.$hasil['selisih'].' bulan</td></tr>';
		$nomor++;
		}
	}
	$conn	= $base->close();
	$json	= array("tabel" => $tabel, "nama" => "<code>$view[nama_klg] ($view[parameter_klg] bulan)</code>");
	http_response_code(200);
	header('Access-Control-Allow-Origin: *');
	header("Content-type: application/json; charset=utf-8");
	//header('Content-type: text/html; charset=UTF-8');
	echo(json_encode($json));
?>