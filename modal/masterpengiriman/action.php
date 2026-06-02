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
			$kode	        = $data->bcode('MPENG', 'id_pengiriman', 'master_pengiriman');
			$tahap_pengiriman	= $secu->injection($_POST['tahap_pengiriman']);
			$status_pengiriman	= $secu->injection($_POST['status_pengiriman']);
			$save	= $conn->prepare("INSERT INTO master_pengiriman (id_pengiriman, tahap_pengiriman, status_pengiriman, created_at, created_by, updated_at, updated_by) VALUES(:kode, :tahap_pengiriman, :status_pengiriman, :catat, :admin, :catat, :admin)");
			$save->bindParam(":kode", $kode, PDO::PARAM_STR);
			$save->bindParam(":tahap_pengiriman", $tahap_pengiriman, PDO::PARAM_STR);
			$save->bindParam(":status_pengiriman", $status_pengiriman, PDO::PARAM_STR);
			$save->bindParam(":catat", $catat, PDO::PARAM_STR);
			$save->bindParam(":admin", $admin, PDO::PARAM_STR);
			$save->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'master_pengiriman', 'Create', '', '$catat', '$admin')");
			$hasil	= ($save==true) ? "success" : "error";
			echo($hasil);
		break;
		
		case "update":
			$kode	        = $secu->injection($_POST['keycode']);
			$tahap_pengiriman	= $secu->injection($_POST['tahap_pengiriman']);
			$status_pengiriman	= $secu->injection($_POST['status_pengiriman']);
			$edit	= $conn->prepare("UPDATE master_pengiriman SET tahap_pengiriman=:tahap_pengiriman, status_pengiriman=:status_pengiriman, updated_at=:catat, updated_by=:admin WHERE id_pengiriman=:kode");
			$edit->bindParam(":kode", $kode, PDO::PARAM_STR);
			$edit->bindParam(":tahap_pengiriman", $tahap_pengiriman, PDO::PARAM_STR);
			$edit->bindParam(":status_pengiriman", $status_pengiriman, PDO::PARAM_STR);
			$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
			$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
			$edit->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'master_pengiriman', 'Update', '', '$catat', '$admin')");
			$hasil	= ($edit==true) ? "success" : "error";
			echo($hasil);
		break;
		
		case "delete":
			$kode	= $secu->injection($_POST['keycode']);
			$dele	= $conn->prepare("DELETE FROM master_pengiriman WHERE id_pengiriman=:kode");
			$dele->bindParam(":kode", $kode, PDO::PARAM_STR);
			$dele->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'master_pengiriman', 'Delete', '', '$catat', '$admin')");
			$hasil	= ($dele==true) ? "success" : "error";
			echo($hasil);
		break;
	}
?>
