<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	require_once('../../config/function/thumb.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$file	= new File;
	$conn	= $base->open();
	$catat	= date('Y-m-d H:i:s');
	$admin	= $secu->injection(@$_COOKIE['adminkuy']);
	$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
	$menu	= $secu->injection(@$_POST['namamenu']);
	switch($menu){
		case "update":
			$uniq	= $secu->injection($_POST['keycode']);
			$kode	= base64_decode($uniq);
			$nama	= $secu->injection($_POST['nama']);
			//FOTO		
			$loga	= $_FILES["foto1"]['tmp_name'];
			$tiga	= $_FILES["foto1"]['type'];
			$naga	= $_FILES["foto1"]['name'];
			$fofe	= pathinfo($naga, PATHINFO_EXTENSION);
			$unik	= str_shuffle("adminz".time()).".".$fofe;
			//CEK DATA
			if(!empty($loga)){
				$read	= $conn->prepare("SELECT foto_adm FROM adminz WHERE id_adm=:kode");
				$read->bindParam(':kode', $kode, PDO::PARAM_STR);
				$read->execute();
				$view	= $read->fetch(PDO::FETCH_ASSOC);
				if(!empty($view['foto_adm'])){
					unlink("../../berkas/adminz/$view[foto_adm]");
				}			
				$file->uploadfoto('adminz', 'foto1', $unik, 200, 225);
				$edit	= $conn->prepare("UPDATE adminz SET foto_adm=:unik, updated_at=:catat, updated_by=:admin WHERE id_adm=:kode");
				$edit->bindParam(":kode", $kode, PDO::PARAM_STR);
				$edit->bindParam(":unik", $unik, PDO::PARAM_STR);
				$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
				$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
				$edit->execute();
			}
			$edit	= $conn->prepare("UPDATE adminz SET nama_adm=:nama, updated_at=:catat, updated_by=:admin WHERE id_adm=:kode");
			$edit->bindParam(":kode", $kode, PDO::PARAM_STR);
			$edit->bindParam(":nama", $nama, PDO::PARAM_STR);
			$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
			$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
			$edit->execute();
			if($edit==true){
				//RIWAYAT
				$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Account', 'Update', '', '$catat', '$admin')");
				setcookie("info", "success", time() + 5, "/");
				setcookie("pesan", "Data account berhasil diupdate...", time() + 5, "/");
			} else {
				setcookie("info", "danger", time() + 5, "/");
				setcookie("pesan", "Data account gagal diupdate...", time() + 5, "/");
			}
		break;
		case "email":
			$uniq	= $secu->injection($_POST['keycode']);
			$kode	= base64_decode($uniq);
			$emailz	= $secu->injection($_POST['emailz']);
			//CEK DATA
			$read	= $conn->prepare("SELECT COUNT(id_adm) AS total FROM adminz WHERE id_adm!=:kode AND email_adm=:emailz");
			$read->bindParam(':kode', $kode, PDO::PARAM_STR);
			$read->bindParam(':emailz', $emailz, PDO::PARAM_STR);
			$read->execute();
			$view	= $read->fetch(PDO::FETCH_ASSOC);
			if(empty($view['total'])){
				$edit	= $conn->prepare("UPDATE adminz SET email_adm=:emailz, updated_at=:catat, updated_by=:admin WHERE id_adm=:kode");
				$edit->bindParam(":kode", $kode, PDO::PARAM_STR);
				$edit->bindParam(":emailz", $emailz, PDO::PARAM_STR);
				$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
				$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
				$edit->execute();
				if($edit==true){
					//RIWAYAT
					$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Account', 'Update', '', '$catat', '$admin')");
					setcookie("info", "success", time() + 5, "/");
					setcookie("pesan", "Data email berhasil diupdate...", time() + 5, "/");
				} else {
					setcookie("info", "danger", time() + 5, "/");
					setcookie("pesan", "Data email gagal diupdate...", time() + 5, "/");
				}
			} else {
				setcookie("info", "danger", time() + 5, "/");
				setcookie("pesan", "Email sudah digunakan...", time() + 5, "/");
			}
		break;
		case "password":
			$uniq	= $secu->injection($_POST['keycode']);
			$kode	= base64_decode($uniq);
			$passz	= $secu->injection($_POST['passz']);
			$unix	= md5($passz);
			$kunci	= $secu->enkripsi($passz);
			$edit	= $conn->prepare("UPDATE adminz_account SET password_aac=:unix, kunci_aac=:kunci, updated_at=:catat, updated_by=:admin WHERE id_adm=:kode");
			$edit->bindParam(":kode", $kode, PDO::PARAM_STR);
			$edit->bindParam(":unix", $unix, PDO::PARAM_STR);
			$edit->bindParam(":kunci", $kunci, PDO::PARAM_STR);
			$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
			$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
			$edit->execute();
			if($edit==true){
				//RIWAYAT
				$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Account', 'Update', '', '$catat', '$admin')");
				setcookie("info", "success", time() + 5, "/");
				setcookie("pesan", "Data password berhasil diupdate...", time() + 5, "/");
			} else {
				setcookie("info", "danger", time() + 5, "/");
				setcookie("pesan", "Data password gagal diupdate...", time() + 5, "/");
			}
		break;
	}
	echo('account');
?>