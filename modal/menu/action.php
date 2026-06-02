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
			$kode	= $data->basecode('MNU', 3, 'id_menu', 'menu');
			$nama	= $secu->injection($_POST['nama']);
			$kate	= $secu->injection($_POST['kategori']);
			$ikon	= $secu->injection($_POST['ikon']);
			$urut	= str_replace('.', '', $_POST['urutan']);
			$save	= $conn->prepare("INSERT INTO menu VALUES(:kode, :kate, :ikon, :nama, :urut, :catat, :admin, :catat, :admin)");
			$save->bindParam(":kode", $kode, PDO::PARAM_STR);
			$save->bindParam(":kate", $kate, PDO::PARAM_STR);
			$save->bindParam(":ikon", $ikon, PDO::PARAM_STR);
			$save->bindParam(":nama", $nama, PDO::PARAM_STR);
			$save->bindParam(":urut", $urut, PDO::PARAM_INT);
			$save->bindParam(":catat", $catat, PDO::PARAM_STR);
			$save->bindParam(":admin", $admin, PDO::PARAM_STR);
			$save->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Menu', 'Create', '', '$catat', '$admin')");
			$hasil	= ($save==true) ? "success" : "error";
			echo($hasil);
		break;
		case "update":
			$kode	= $secu->injection($_POST['keycode']);
			$nama	= $secu->injection($_POST['nama']);
			$kate	= $secu->injection($_POST['kategori']);
			$ikon	= $secu->injection($_POST['ikon']);
			$urut	= str_replace('.', '', $_POST['urutan']);
			$edit	= $conn->prepare("UPDATE menu SET id_kmu=:kate, id_icon=:ikon, nama_menu=:nama, urutan_menu=:urut, updated_at=:catat, updated_by=:admin WHERE id_menu=:kode");
			$edit->bindParam(":kode", $kode, PDO::PARAM_STR);
			$edit->bindParam(":kate", $kate, PDO::PARAM_STR);
			$edit->bindParam(":ikon", $ikon, PDO::PARAM_STR);
			$edit->bindParam(":nama", $nama, PDO::PARAM_STR);
			$edit->bindParam(":urut", $urut, PDO::PARAM_INT);
			$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
			$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
			$edit->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Menu', 'Update', '', '$catat', '$admin')");
			$hasil	= ($edit==true) ? "success" : "error";
			echo($hasil);
		break;
		case "delete":
			$kode	= $secu->injection($_POST['keycode']);
			$dele	= $conn->prepare("DELETE FROM menu WHERE id_menu=:kode");
			$dele->bindParam(":kode", $kode, PDO::PARAM_STR);
			$dele->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Menu', 'Delete', '', '$catat', '$admin')");
			$hasil	= ($dele==true) ? "success" : "error";
			echo($hasil);
		break;
	}
?>
