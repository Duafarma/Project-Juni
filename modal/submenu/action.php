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
			$kode	= $data->basecode('SMU', 4, 'id_smu', 'sub_menu');
			$nama	= $secu->injection($_POST['nama']);
			$menu	= $secu->injection($_POST['menu']);
			$urls	= $secu->injection($_POST['urls']);
			$urut	= str_replace('.', '', $_POST['urutan']);
			$save	= $conn->prepare("INSERT INTO sub_menu VALUES(:kode, :menu, :nama, :urls, :urut, :catat, :admin, :catat, :admin)");
			$save->bindParam(":kode", $kode, PDO::PARAM_STR);
			$save->bindParam(":menu", $menu, PDO::PARAM_STR);
			$save->bindParam(":nama", $nama, PDO::PARAM_STR);
			$save->bindParam(":urls", $urls, PDO::PARAM_STR);
			$save->bindParam(":urut", $urut, PDO::PARAM_INT);
			$save->bindParam(":catat", $catat, PDO::PARAM_STR);
			$save->bindParam(":admin", $admin, PDO::PARAM_STR);
			$save->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Sub Menu', 'Create', '', '$catat', '$admin')");
			$hasil	= ($save==true) ? "success" : "error";
			echo($hasil);
		break;
		case "update":
			$kode	= $secu->injection($_POST['keycode']);
			$nama	= $secu->injection($_POST['nama']);
			$menu	= $secu->injection($_POST['menu']);
			$urls	= $secu->injection($_POST['urls']);
			$urut	= str_replace('.', '', $_POST['urutan']);
			$edit	= $conn->prepare("UPDATE sub_menu SET id_menu=:menu, nama_smu=:nama, url_smu=:urls, urutan_smu=:urut, updated_at=:catat, updated_by=:admin WHERE id_smu=:kode");
			$edit->bindParam(":kode", $kode, PDO::PARAM_STR);
			$edit->bindParam(":menu", $menu, PDO::PARAM_STR);
			$edit->bindParam(":nama", $nama, PDO::PARAM_STR);
			$edit->bindParam(":urls", $urls, PDO::PARAM_STR);
			$edit->bindParam(":urut", $urut, PDO::PARAM_INT);
			$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
			$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
			$edit->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Sub Menu', 'Update', '', '$catat', '$admin')");
			$hasil	= ($edit==true) ? "success" : "error";
			echo($hasil);
		break;
		case "delete":
			$kode	= $secu->injection($_POST['keycode']);
			$dele	= $conn->prepare("DELETE FROM sub_menu WHERE id_smu=:kode");
			$dele->bindParam(":kode", $kode, PDO::PARAM_STR);
			$dele->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Sub Menu', 'Delete', '', '$catat', '$admin')");
			$hasil	= ($dele==true) ? "success" : "error";
			echo($hasil);
		break;
	}
?>
