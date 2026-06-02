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
			$kode	= $data->bcode('APO', 'id_apoteker', 'apoteker');
			$nomor	= $secu->injection($_POST['nomor']);
			$nama	= $secu->injection($_POST['nama']);
			$save	= $conn->prepare("INSERT INTO apoteker VALUES(:kode, :nomor, :nama, :catat, :admin, :catat, :admin)");
			$save->bindParam(":kode", $kode, PDO::PARAM_STR);
			$save->bindParam(":nomor", $nomor, PDO::PARAM_STR);
			$save->bindParam(":nama", $nama, PDO::PARAM_STR);
			$save->bindParam(":catat", $catat, PDO::PARAM_STR);
			$save->bindParam(":admin", $admin, PDO::PARAM_STR);
			$save->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Apoteker', 'Create', '', '$catat', '$admin')");
			$hasil	= ($save==true) ? "success" : "error";
			echo($hasil);
		break;
		case "update":
			$kode	= $secu->injection($_POST['keycode']);
			$nomor	= $secu->injection($_POST['nomor']);
			$nama	= $secu->injection($_POST['nama']);
			$edit	= $conn->prepare("UPDATE apoteker SET kode_apoteker=:nomor, nama_apoteker=:nama, updated_at=:catat, updated_by=:admin WHERE id_apoteker=:kode");
			$edit->bindParam(":kode", $kode, PDO::PARAM_STR);
			$edit->bindParam(":nomor", $nomor, PDO::PARAM_STR);
			$edit->bindParam(":nama", $nama, PDO::PARAM_STR);
			$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
			$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
			$edit->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Apoteker', 'Update', '', '$catat', '$admin')");
			$hasil	= ($edit==true) ? "success" : "error";
			echo($hasil);
		break;
		case "delete":
			$kode	= $secu->injection($_POST['keycode']);
			$dele	= $conn->prepare("DELETE FROM apoteker WHERE id_apoteker=:kode");
			$dele->bindParam(":kode", $kode, PDO::PARAM_STR);
			$dele->execute();
			$hasil	= ($dele==true) ? "success" : "error";
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Apoteker', 'Delete', '', '$catat', '$admin')");
			echo($hasil);
		break;
	}
?>
