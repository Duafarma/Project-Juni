<?php
	error_reporting(0);
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$admin	= $secu->injection(@$_COOKIE['adminkuy']);
	$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
	$catat	= date('Y-m-d H:i:s');
	$act	= $secu->injection(@$_GET['act']);
	$secu->validadmin($admin, $kunci);
	if($secu->validadmin($admin, $kunci)==false){ header('location:'.$data->sistem('url_sis').'/signout'); } else {
	$conn	= $base->open();
	switch($act){
		case "input":
			// Basic
			$id	= '';
			$id_tfk		            = $secu->injection($_POST['id_tfk']);
			$nomor_seri 			= $secu->injection($_POST['nomor_seri']);
			$tanggal 		    	= $secu->injection($_POST['tanggal']);
			$keterangan 			= $secu->injection($_POST['keterangan']);


			
			$save	= $conn->prepare("INSERT INTO faktur_pajak VALUES(:id, :id_tfk, :nomor_seri,  :tanggal, :keterangan, 'sudah terbit' ,:catat, :admin, :catat, :admin)");
			$save->bindParam(":id", $id, PDO::PARAM_STR);
			$save->bindParam(":id_tfk", $id_tfk, PDO::PARAM_STR);
			$save->bindParam(":nomor_seri", $nomor_seri, PDO::PARAM_STR);
			$save->bindParam(":tanggal", $tanggal, PDO::PARAM_STR);
			$save->bindParam(":keterangan", $keterangan, PDO::PARAM_STR);
			$save->bindParam(":catat", $catat, PDO::PARAM_STR);
			$save->bindParam(":admin", $admin, PDO::PARAM_STR);
			$save->execute();

            $update_tfk = $conn->prepare("UPDATE transaksi_faktur SET status_f_pajak = 'sudah terbit' WHERE id_tfk=:id_tfk");
            $update_tfk->bindParam(":id_tfk", $id_tfk, PDO::PARAM_STR);
            $update_tfk->execute();
			// Save Alamat
			
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$id', 'Input Data Faktur Pajak ', 'Create', '', '$catat', '$admin')");
			$hasil	= ($save==true) ? "success" : "error";
			echo($hasil);
		break;
		
			case "update":
			// Basic
			$code   	= $secu->injection($_POST['keycode']);
			$keterangan	= $secu->injection($_POST['keterangan']);
			$nomor_seri	= $secu->injection($_POST['nomor_seri']);
			$tanggal	= $secu->injection($_POST['tanggal']);
		
			
			$edit	= $conn->prepare("UPDATE faktur_pajak SET keterangan=:keterangan, nomor_seri=:nomor_seri, tanggal=:tanggal, updated_at=:catat, updated_by=:admin WHERE id_f_p=:code");
			$edit->bindParam(":code", $code, PDO::PARAM_STR);
			$edit->bindParam(":keterangan", $keterangan, PDO::PARAM_STR);
			$edit->bindParam(":nomor_seri", $nomor_seri, PDO::PARAM_STR);
			$edit->bindParam(":tanggal", $tanggal, PDO::PARAM_STR);
		
			$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
			$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
			$edit->execute();
			
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$code', 'Faktur Pajak', 'Update', '', '$catat', '$admin')");
			$hasil	= ($edit==true) ? "success" : "error";
			echo($hasil);
		break;
		
	
	}
	$conn	= $base->close();
	}
?>