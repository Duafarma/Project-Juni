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
			$kode	        = $data->bcode('MG', 'id_mg', 'master_grup');
			$nama_mg	= $secu->injection($_POST['nama_mg']);
			$kode_mg	= $secu->injection($_POST['kode_mg']);
			$save	= $conn->prepare("INSERT INTO master_grup VALUES(:kode, :nama_mg, :kode_mg, :catat, :admin, :catat, :admin)");
			$save->bindParam(":kode", $kode, PDO::PARAM_STR);
			$save->bindParam(":nama_mg", $nama_mg, PDO::PARAM_STR);
			$save->bindParam(":kode_mg", $kode_mg, PDO::PARAM_STR);
			$save->bindParam(":catat", $catat, PDO::PARAM_STR);
			$save->bindParam(":admin", $admin, PDO::PARAM_STR);
			$save->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Master Grup', 'Create', '', '$catat', '$admin')");
			$hasil	= ($save==true) ? "success" : "error";
			echo($hasil);
		break;
		case "update":
			$kode	        = $secu->injection($_POST['keycode']);
			$nama_mg	= $secu->injection($_POST['nama_mg']);
			$kode_mg	= $secu->injection($_POST['kode_mg']);

			$edit	= $conn->prepare("UPDATE master_grup SET nama_mg=:nama_mg, kode_mg=:kode_mg, updated_at=:catat, updated_by=:admin WHERE id_mg=:kode");
			$edit->bindParam(":kode", $kode, PDO::PARAM_STR);
			$edit->bindParam(":nama_mg", $nama_mg, PDO::PARAM_STR);
			$edit->bindParam(":kode_mg", $kode_mg, PDO::PARAM_STR);
			$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
			$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
			$edit->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Master Grup', 'Update', '', '$catat', '$admin')");
			$hasil	= ($edit==true) ? "success" : "error";
			echo($hasil);
		break;
		case "delete":
			$kode	= $secu->injection($_POST['keycode']);
			$dele	= $conn->prepare("DELETE FROM master_grup WHERE id_mg=:kode");
			$dele->bindParam(":kode", $kode, PDO::PARAM_STR);
			$dele->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Master Grup', 'Delete', '', '$catat', '$admin')");
			$hasil	= ($dele==true) ? "success" : "error";
			echo($hasil);
		break;
	}
?>
