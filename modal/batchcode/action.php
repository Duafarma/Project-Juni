<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$conn	= $base->open();
	$catat	= date('Y-m-d H:i:s');
	$admin	= $secu->injection(@$_COOKIE['adminkuy']);
	$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
	$act	= $secu->injection(@$_GET['act']);
	switch($act){
		case "input":
			$kode	= $data->bcode('MBC', 'id_mbc', 'master_batchcode');
			$code	= $secu->injection($_POST['kode']);
			$tgl	= $secu->injection($_POST['tanggal']);
			$save	= $conn->prepare("INSERT INTO master_batchcode VALUES(:kode, :code, :tgl, :catat, :admin, :catat, :admin)");
			$save->bindParam(":kode", $kode, PDO::PARAM_STR);
			$save->bindParam(":code", $code, PDO::PARAM_STR);
			$save->bindParam(":tgl", $tgl, PDO::PARAM_STR);
			$save->bindParam(":catat", $catat, PDO::PARAM_STR);
			$save->bindParam(":admin", $admin, PDO::PARAM_STR);
			$save->execute();
			$hasil	= ($save==true) ? "success" : "error";
			echo($hasil);
		break;
		case "update":
			$kode	= $secu->injection($_POST['keycode']);
			$tgl	= $secu->injection($_POST['tanggal']);
			$code	= $secu->injection($_POST['kode']);
			$edit	= $conn->prepare("UPDATE master_batchcode SET kode_mbc=:code, tgl_mbc=:tgl, updated_at=:catat, updated_by=:admin WHERE id_mbc=:kode");
			$edit->bindParam(":kode", $kode, PDO::PARAM_STR);
			$edit->bindParam(":code", $code, PDO::PARAM_STR);
			$edit->bindParam(":tgl", $tgl, PDO::PARAM_STR);
			$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
			$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
			$edit->execute();
			$hasil	= ($edit==true) ? "success" : "error";
			echo($hasil);
		break;
		case "delete":
			$kode	= $secu->injection($_POST['keycode']);
			$dele	= $conn->prepare("DELETE FROM master_batchcode WHERE id_mbc=:kode");
			$dele->bindParam(":kode", $kode, PDO::PARAM_STR);
			$dele->execute();
			$hasil	= ($dele==true) ? "success" : "error";
			echo($hasil);
		break;
	}
?>
