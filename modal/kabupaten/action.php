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
			$kode	= $data->bcode('RKB', 'id_rkb', 'regional_kabupaten');
			$prov	= $secu->injection($_POST['provinsi']);
			$nama	= $secu->injection($_POST['nama']);
			$kode_k	= $secu->injection($_POST['kode_k']);

			$save	= $conn->prepare("INSERT INTO regional_kabupaten VALUES(:prov, :kode, :nama, :kode_k, :catat, :admin, :catat, :admin)");
			$save->bindParam(":prov", $prov, PDO::PARAM_STR);
			$save->bindParam(":kode", $kode, PDO::PARAM_STR);
			$save->bindParam(":nama", $nama, PDO::PARAM_STR);
			$save->bindParam(":kode_k", $kode_k, PDO::PARAM_STR);

			$save->bindParam(":catat", $catat, PDO::PARAM_STR);
			$save->bindParam(":admin", $admin, PDO::PARAM_STR);
			$save->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Kabupaten', 'Create', '', '$catat', '$admin')");
			$hasil	= ($save==true) ? "success" : "error";
			echo($hasil);
		break;
		case "update":
			$kode	= $secu->injection($_POST['keycode']);
			$prov	= $secu->injection($_POST['provinsi']);
			$nama	= $secu->injection($_POST['nama']);
			$kode_k	= $secu->injection($_POST['kode_k']);

			$edit	= $conn->prepare("UPDATE regional_kabupaten SET id_rpo=:prov, nama_rkb=:nama, kode_k=:kode_k, updated_at=:catat, updated_by=:admin WHERE id_rkb=:kode");
			$edit->bindParam(":kode", $kode, PDO::PARAM_STR);
			$edit->bindParam(":prov", $prov, PDO::PARAM_STR);
			$edit->bindParam(":nama", $nama, PDO::PARAM_STR);
			$edit->bindParam(":kode_k", $kode_k, PDO::PARAM_STR);
			$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
			$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
			$edit->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Kabupaten', 'Update', '', '$catat', '$admin')");
			$hasil	= ($edit==true) ? "success" : "error";
			echo($hasil);
		break;
		case "delete":
			$kode	= $secu->injection($_POST['keycode']);
			$dele	= $conn->prepare("DELETE FROM regional_kabupaten WHERE id_rkb=:kode");
			$dele->bindParam(":kode", $kode, PDO::PARAM_STR);
			$dele->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Kabupaten', 'Delete', '', '$catat', '$admin')");
			$hasil	= ($dele==true) ? "success" : "error";
			echo($hasil);
		break;
	}
?>
