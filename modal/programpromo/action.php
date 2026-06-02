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
			$kode_program   = strtolower(trim($secu->injection($_POST['kode_program'])));
			$nama_program   = $secu->injection($_POST['nama_program']);
			$deskripsi      = $secu->injection($_POST['deskripsi']);
			$icon_class     = $secu->injection($_POST['icon_class']);
			$status_program = $secu->injection($_POST['status_program']);
			$urutan         = intval($_POST['urutan']);
			
			// Cek duplikat kode_program
			$cek = $conn->prepare("SELECT id_program FROM program_promo WHERE kode_program=:kode");
			$cek->bindParam(":kode", $kode_program, PDO::PARAM_STR);
			$cek->execute();
			if($cek->rowCount() > 0) {
				echo("duplicate");
				exit;
			}
			
			$save = $conn->prepare("INSERT INTO program_promo (kode_program, nama_program, deskripsi, icon_class, status_program, urutan, created_at, updated_at) VALUES(:kode_program, :nama_program, :deskripsi, :icon_class, :status_program, :urutan, :catat, :catat)");
			$save->bindParam(":kode_program", $kode_program, PDO::PARAM_STR);
			$save->bindParam(":nama_program", $nama_program, PDO::PARAM_STR);
			$save->bindParam(":deskripsi", $deskripsi, PDO::PARAM_STR);
			$save->bindParam(":icon_class", $icon_class, PDO::PARAM_STR);
			$save->bindParam(":status_program", $status_program, PDO::PARAM_STR);
			$save->bindParam(":urutan", $urutan, PDO::PARAM_INT);
			$save->bindParam(":catat", $catat, PDO::PARAM_STR);
			$save->execute();
			
			// RIWAYAT
			$riwayat = $conn->query("INSERT INTO riwayat VALUES('', '$kode_program', 'Program Promo', 'Create', '', '$catat', '$admin')");
			$hasil = ($save==true) ? "success" : "error";
			echo($hasil);
		break;
		
		case "update":
			$id             = $secu->injection($_POST['keycode']);
			$kode_program   = strtolower(trim($secu->injection($_POST['kode_program'])));
			$nama_program   = $secu->injection($_POST['nama_program']);
			$deskripsi      = $secu->injection($_POST['deskripsi']);
			$icon_class     = $secu->injection($_POST['icon_class']);
			$status_program = $secu->injection($_POST['status_program']);
			$urutan         = intval($_POST['urutan']);
			
			// Cek duplikat kode_program (exclude current record)
			$cek = $conn->prepare("SELECT id_program FROM program_promo WHERE kode_program=:kode AND id_program!=:id");
			$cek->bindParam(":kode", $kode_program, PDO::PARAM_STR);
			$cek->bindParam(":id", $id, PDO::PARAM_INT);
			$cek->execute();
			if($cek->rowCount() > 0) {
				echo("duplicate");
				exit;
			}
			
			$edit = $conn->prepare("UPDATE program_promo SET kode_program=:kode_program, nama_program=:nama_program, deskripsi=:deskripsi, icon_class=:icon_class, status_program=:status_program, urutan=:urutan, updated_at=:catat WHERE id_program=:id");
			$edit->bindParam(":id", $id, PDO::PARAM_INT);
			$edit->bindParam(":kode_program", $kode_program, PDO::PARAM_STR);
			$edit->bindParam(":nama_program", $nama_program, PDO::PARAM_STR);
			$edit->bindParam(":deskripsi", $deskripsi, PDO::PARAM_STR);
			$edit->bindParam(":icon_class", $icon_class, PDO::PARAM_STR);
			$edit->bindParam(":status_program", $status_program, PDO::PARAM_STR);
			$edit->bindParam(":urutan", $urutan, PDO::PARAM_INT);
			$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
			$edit->execute();
			
			// RIWAYAT
			$riwayat = $conn->query("INSERT INTO riwayat VALUES('', '$id', 'Program Promo', 'Update', '', '$catat', '$admin')");
			$hasil = ($edit==true) ? "success" : "error";
			echo($hasil);
		break;
		
		case "delete":
			$id = $secu->injection($_POST['keycode']);
			
			$dele = $conn->prepare("DELETE FROM program_promo WHERE id_program=:id");
			$dele->bindParam(":id", $id, PDO::PARAM_INT);
			$dele->execute();
			
			// RIWAYAT
			$riwayat = $conn->query("INSERT INTO riwayat VALUES('', '$id', 'Program Promo', 'Delete', '', '$catat', '$admin')");
			$hasil = ($dele==true) ? "success" : "error";
			echo($hasil);
		break;
	}
	
	$conn = $base->close();
?>
