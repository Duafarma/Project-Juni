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
			$kode	= $data->basecode('', 1, 'id_aak', 'adminz_akses');
			$menu	= $secu->injection($_POST['namamenu']);
			$jenis	= $secu->injection($_POST['jenisadmin']);
			$hak1	= $secu->injection($_POST['tambahdata']);
			$hak2	= $secu->injection($_POST['editdata']);
			$hak3	= $secu->injection($_POST['deletedata']);
			$save	= $conn->prepare("INSERT INTO adminz_akses VALUES(:kode, :jenis, :menu, :hak1, :hak2, :hak3, :catat, :admin, :catat, :admin)");
			$save->bindParam(":kode", $kode, PDO::PARAM_STR);
			$save->bindParam(":jenis", $jenis, PDO::PARAM_STR);
			$save->bindParam(":menu", $menu, PDO::PARAM_STR);
			$save->bindParam(":hak1", $hak1, PDO::PARAM_STR);
			$save->bindParam(":hak2", $hak2, PDO::PARAM_STR);
			$save->bindParam(":hak3", $hak3, PDO::PARAM_STR);
			$save->bindParam(":catat", $catat, PDO::PARAM_STR);
			$save->bindParam(":admin", $admin, PDO::PARAM_STR);
			$save->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Hak Akses', 'Create', '', '$catat', '$admin')");
			$hasil	= ($save==true) ? "success" : "error";
			echo($hasil);
		break;
		case "update":
			$kode	= $secu->injection($_POST['keycode']);
			$menu	= $secu->injection($_POST['namamenu']);
			$jenis	= $secu->injection($_POST['jenisadmin']);
			$hak1	= $secu->injection($_POST['tambahdata']);
			$hak2	= $secu->injection($_POST['editdata']);
			$hak3	= $secu->injection($_POST['deletedata']);
			$edit	= $conn->prepare("UPDATE adminz_akses SET jenis_adm=:jenis, nama_menu=:menu, tambah_data=:hak1, edit_data=:hak2, delete_data=:hak3, updated_at=:catat, updated_by=:admin WHERE id_aak=:kode");
			$edit->bindParam(":kode", $kode, PDO::PARAM_STR);
			$edit->bindParam(":jenis", $jenis, PDO::PARAM_STR);
			$edit->bindParam(":menu", $menu, PDO::PARAM_STR);
			$edit->bindParam(":hak1", $hak1, PDO::PARAM_STR);
			$edit->bindParam(":hak2", $hak2, PDO::PARAM_STR);
			$edit->bindParam(":hak3", $hak3, PDO::PARAM_STR);
			$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
			$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
			$edit->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Hak Akses', 'Update', '', '$catat', '$admin')");
			$hasil	= ($edit==true) ? "success" : "error";
			echo($hasil);
		break;
		case "delete":
			$kode	= $secu->injection($_POST['keycode']);
			$dele	= $conn->prepare("DELETE FROM adminz_akses WHERE id_aak=:kode");
			$dele->bindParam(":kode", $kode, PDO::PARAM_STR);
			$dele->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Hak Akses', 'Delete', '', '$catat', '$admin')");
			$hasil	= ($dele==true) ? "success" : "error";
			echo($hasil);
		break;
	}
?>
