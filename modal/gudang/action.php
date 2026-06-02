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
				$kode	= $data->basecode('CBG', 3, 'id_gudang', 'gudang');
				$nama	= $secu->injection($_POST['gudang']);
				$save	= $conn->prepare("INSERT INTO gudang VALUES(:kode, :gudang, :catat, :admin, :catat, :admin)");
				$save->bindParam(":kode", $kode, PDO::PARAM_STR);
				$save->bindParam(":gudang", $nama, PDO::PARAM_STR);
				$save->bindParam(":catat", $catat, PDO::PARAM_STR);
				$save->bindParam(":admin", $admin, PDO::PARAM_STR);
				$save->execute();
				//RIWAYAT
				$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'gudang', 'Create', '', '$catat', '$admin')");
				if($save==true){
					setcookie('info', 'success', time() + 5, '/');
					setcookie('pesan', 'Data gudang berhasil diinput.', time() + 5, '/');
				} else {
					setcookie('info', 'danger', time() + 5, '/');
					setcookie('pesan', 'Data gudang gagal diinput.', time() + 5, '/');
				}
			break;
			case "update":
				$uniq	= $secu->injection($_POST['keycode']);
				$kode	= base64_decode($uniq);
				$nama	= $secu->injection($_POST['gudang']);
				//EDIT
				$edit	= $conn->prepare("UPDATE gudang SET gudang=:gudang, updated_at=:catat, updated_by=:admin WHERE id_gudang=:kode");
				$edit->bindParam(":kode", $kode, PDO::PARAM_STR);
				$edit->bindParam(":gudang", $nama, PDO::PARAM_STR);
				$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
				$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
				$edit->execute();
				//RIWAYAT
				$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'gudang', 'Update', '', '$catat', '$admin')");
				if($edit==true){
					setcookie('info', 'success', time() + 5, '/');
					setcookie('pesan', 'Data gudang berhasil diupdate.', time() + 5, '/');
				} else {
					setcookie('info', 'danger', time() + 5, '/');
					setcookie('pesan', 'Data gudang gagal diupdate.', time() + 5, '/');
				}
			break;
			case "delete":
				$uniq	= $secu->injection($_POST['keycode']);
				$kode	= base64_decode($uniq);
				//REMOVE
				$remove	= $conn->prepare("DELETE FROM gudang WHERE id_gudang=:kode");
				$remove->bindParam(":kode", $kode, PDO::PARAM_STR);
				$remove->execute();
				//RIWAYAT
				$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'gudang', 'Delete', '', '$catat', '$admin')");
				if($remove==true){
					setcookie('info', 'success', time() + 5, '/');
					setcookie('pesan', 'Data gudang berhasil dihapus.', time() + 5, '/');
				} else {
					setcookie('info', 'danger', time() + 5, '/');
					setcookie('pesan', 'Data gudang gagal dihapus.', time() + 5, '/');
				}
			break;
		}
		$url	= 'gudang';
	}
	$conn	= $base->close();
	echo($url);
?>