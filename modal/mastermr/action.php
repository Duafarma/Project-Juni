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
	$act	= $secu->injection(@$_GET['act']);

	$conn->exec("CREATE TABLE IF NOT EXISTS `master_mr_baru` (
		`id_mr` int(11) NOT NULL AUTO_INCREMENT,
		`nama_mr` varchar(255) NOT NULL DEFAULT '',
		`area` varchar(255) NOT NULL DEFAULT '',
		`ket` text DEFAULT NULL,
		`created_at` datetime NOT NULL,
		`created_by` varchar(100) NOT NULL,
		`updated_at` datetime DEFAULT NULL,
		`updated_by` varchar(100) DEFAULT NULL,
		PRIMARY KEY (`id_mr`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

	switch($act){
		case "input":
			$nama_mr = $secu->injection(trim($_POST['nama_mr']));
			$area = $secu->injection(trim($_POST['area']));
			$ket = $secu->injection(trim($_POST['ket']));

			if($nama_mr === '' || $area === ''){
				echo('error');
				exit;
			}

			$save = $conn->prepare("INSERT INTO master_mr_baru (nama_mr, area, ket, created_at, created_by, updated_at, updated_by) VALUES(:nama_mr, :area, :ket, :catat, :admin, :catat, :admin)");
			$save->bindParam(':nama_mr', $nama_mr, PDO::PARAM_STR);
			$save->bindParam(':area', $area, PDO::PARAM_STR);
			$save->bindParam(':ket', $ket, PDO::PARAM_STR);
			$save->bindParam(':catat', $catat, PDO::PARAM_STR);
			$save->bindParam(':admin', $admin, PDO::PARAM_STR);
			$save->execute();
			$kode = $conn->lastInsertId();
			$conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Master MR', 'Create', '', '$catat', '$admin')");
			echo(($save==true) ? 'success' : 'error');
		break;

		case "update":
			$kode = intval($_POST['keycode']);
			$nama_mr = $secu->injection(trim($_POST['nama_mr']));
			$area = $secu->injection(trim($_POST['area']));
			$ket = $secu->injection(trim($_POST['ket']));

			if($kode <= 0 || $nama_mr === '' || $area === ''){
				echo('error');
				exit;
			}

			$edit = $conn->prepare("UPDATE master_mr_baru SET nama_mr=:nama_mr, area=:area, ket=:ket, updated_at=:catat, updated_by=:admin WHERE id_mr=:kode");
			$edit->bindParam(':kode', $kode, PDO::PARAM_INT);
			$edit->bindParam(':nama_mr', $nama_mr, PDO::PARAM_STR);
			$edit->bindParam(':area', $area, PDO::PARAM_STR);
			$edit->bindParam(':ket', $ket, PDO::PARAM_STR);
			$edit->bindParam(':catat', $catat, PDO::PARAM_STR);
			$edit->bindParam(':admin', $admin, PDO::PARAM_STR);
			$edit->execute();
			$conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Master MR', 'Update', '', '$catat', '$admin')");
			echo(($edit==true) ? 'success' : 'error');
		break;

		case "delete":
			$kode = intval($_POST['keycode']);
			$dele = $conn->prepare("DELETE FROM master_mr_baru WHERE id_mr=:kode");
			$dele->bindParam(':kode', $kode, PDO::PARAM_INT);
			$dele->execute();
			$conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Master MR', 'Delete', '', '$catat', '$admin')");
			echo(($dele==true) ? 'success' : 'error');
		break;
	}
?>