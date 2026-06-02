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
				$kode	= $data->basecode('CBG', 3, 'id_cabang', 'cabang');
				$nama	= $secu->injection($_POST['nama']);
				$save	= $conn->prepare("INSERT INTO cabang VALUES(:kode, :nama, :catat, :admin, :catat, :admin)");
				$save->bindParam(":kode", $kode, PDO::PARAM_STR);
				$save->bindParam(":nama", $nama, PDO::PARAM_STR);
				$save->bindParam(":catat", $catat, PDO::PARAM_STR);
				$save->bindParam(":admin", $admin, PDO::PARAM_STR);
				$save->execute();
				//RIWAYAT
				$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Cabang', 'Create', '', '$catat', '$admin')");
				if($save==true){
					setcookie('info', 'success', time() + 5, '/');
					setcookie('pesan', 'Data cabang berhasil diinput.', time() + 5, '/');
				} else {
					setcookie('info', 'danger', time() + 5, '/');
					setcookie('pesan', 'Data cabang gagal diinput.', time() + 5, '/');
				}
			break;
			case "update":
				$uniq	= $secu->injection($_POST['keycode']);
				$kode	= base64_decode($uniq);
				$nama	= $secu->injection($_POST['nama']);
				//EDIT
				$edit	= $conn->prepare("UPDATE cabang SET nama_cabang=:nama, updated_at=:catat, updated_by=:admin WHERE id_cabang=:kode");
				$edit->bindParam(":kode", $kode, PDO::PARAM_STR);
				$edit->bindParam(":nama", $nama, PDO::PARAM_STR);
				$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
				$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
				$edit->execute();
				//RIWAYAT
				$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Cabang', 'Update', '', '$catat', '$admin')");
				if($edit==true){
					setcookie('info', 'success', time() + 5, '/');
					setcookie('pesan', 'Data cabang berhasil diupdate.', time() + 5, '/');
				} else {
					setcookie('info', 'danger', time() + 5, '/');
					setcookie('pesan', 'Data cabang gagal diupdate.', time() + 5, '/');
				}
			break;
			case "delete":
				$uniq	= $secu->injection($_POST['keycode']);
				$kode	= base64_decode($uniq);
				//REMOVE
				$remove	= $conn->prepare("DELETE FROM cabang WHERE id_cabang=:kode");
				$remove->bindParam(":kode", $kode, PDO::PARAM_STR);
				$remove->execute();
				//RIWAYAT
				$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Cabang', 'Delete', '', '$catat', '$admin')");
				if($remove==true){
					setcookie('info', 'success', time() + 5, '/');
					setcookie('pesan', 'Data cabang berhasil dihapus.', time() + 5, '/');
				} else {
					setcookie('info', 'danger', time() + 5, '/');
					setcookie('pesan', 'Data cabang gagal dihapus.', time() + 5, '/');
				}
			break;
		}
		$url	= 'cabang';
	}
	$conn	= $base->close();
	echo($url);
?>