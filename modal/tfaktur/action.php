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
	$menu	= $secu->injection(@$_POST['namamenu']);
	$secu->validadmin($admin, $kunci);
	if($secu->validadmin($admin, $kunci)==false){
		//header('location:'.$data->sistem('url_sis').'/signout');
		$url	= 'signout';
	} else {
		switch($menu){
			case "input":
				// Api Post
				$id				= '';
				$kode			= $data->basecode('TKF', 5, 'id_tkf_t', 'tuker_faktur');
				$id_kot			= $secu->injection($_POST['id_kot']);
				$id_tfk			= $secu->injection($_POST['id_tfk']);
				$tanggal_tkf	= $secu->injection($_POST['tanggal_tkf']);
				$status			= $secu->injection($_POST['status']);
				$note			= $secu->injection($_POST['note']);
				// save data ke database
				$save			= $conn->prepare("INSERT INTO tuker_faktur VALUES(:kode,:id_kot, :id_tfk,  :tanggal_tkf, :status, :note, :catat, :admin, :catat, :admin)");
				$save->bindParam(":kode", $kode, PDO::PARAM_STR);
				$save->bindParam(":id_kot", $id_kot, PDO::PARAM_STR);
				$save->bindParam(":id_tfk", $id_tfk, PDO::PARAM_STR);
				$save->bindParam(":tanggal_tkf", $tanggal_tkf, PDO::PARAM_STR);
				$save->bindParam(":status", $status, PDO::PARAM_STR);
				$save->bindParam(":note", $note, PDO::PARAM_STR);
				$save->bindParam(":catat", $catat, PDO::PARAM_STR);
				$save->bindParam(":admin", $admin, PDO::PARAM_STR);
				$save->execute();

				//RIWAYAT Sistem 
				$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Produk', 'Create', '', '$catat', '$admin')");
				// Save
				if($save==true){
					setcookie('info', 'success', time() + 5, '/');
					setcookie('pesan', 'Data Tuker Faktur berhasil diinput.', time() + 5, '/');
				} else {
					setcookie('info', 'danger', time() + 5, '/');
					setcookie('pesan', 'Data Tuker Faktur  gagal diinput.', time() + 5, '/');
				}
			break;
			// API UPDATE
			case "update":
				$kode			= $secu->injection($_POST['keycode']);
				$id_kot			= $secu->injection($_POST['id_kot']);
				$id_tfk			= $secu->injection($_POST['id_tfk']);
				$tanggal_tkf	= $secu->injection($_POST['tanggal_tkf']);
				$status			= $secu->injection($_POST['status']);
				$note			= $secu->injection($_POST['note']);
				$edit			= $conn->prepare("UPDATE tuker_faktur SET id_kot=:id_kot, id_tfk=:id_tfk, tanggal_tkf=:tanggal_tkf, status=:status, note=:note, updated_at=:catat, updated_by=:admin WHERE id_tkf_t=:kode");
				$edit->bindParam(":kode", $kode, PDO::PARAM_STR);
				$edit->bindParam(":id_kot", $id_kot, PDO::PARAM_STR);
				$edit->bindParam(":id_tfk", $id_tfk, PDO::PARAM_STR);
				$edit->bindParam(":tanggal_tkf", $tanggal_tkf, PDO::PARAM_STR);
				$edit->bindParam(":status", $status, PDO::PARAM_STR);
				$edit->bindParam(":note", $note, PDO::PARAM_STR);
				$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
				$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
				$edit->execute();
				//RIWAYAT
				$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'tfaktur', 'Update', '', '$catat', '$admin')");
				if($edit==true){
					setcookie('info', 'success', time() + 5, '/');
					setcookie('pesan', 'Data Tuker Faktur berhasil diupdate.', time() + 5, '/');
				} else {
					setcookie('info', 'danger', time() + 5, '/');
					setcookie('pesan', 'Data Tuker Faktur gagal diupdate.', time() + 5, '/');
				}
			break;
			case "delete":
				$kode	= $secu->injection($_POST['keycode']);
				$remove	= $conn->prepare("DELETE FROM tuker_faktur WHERE id_tkf_t=:kode");
				$remove->bindParam(":kode", $kode, PDO::PARAM_STR);
				$remove->execute();
				/*
				$dele	= $conn->prepare("DELETE FROM produk_stok WHERE id_pro=:kode");
				$dele->bindParam(":kode", $kode, PDO::PARAM_STR);
				$dele->execute();
				*/
				//RIWAYAT
				$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'tfaktur', 'Delete', '', '$catat', '$admin')");
				if($remove==true){
					setcookie('info', 'success', time() + 5, '/');
					setcookie('pesan', 'Data Tuker Faktur berhasil dihapus.', time() + 5, '/');
				} else {
					setcookie('info', 'danger', time() + 5, '/');
					setcookie('pesan', 'Data Tuker Faktur gagal dihapus.', time() + 5, '/');
				}
			break;
		}
		$url	= 'tfaktur';
	}
	$conn	= $base->close();
	echo($url);
?>