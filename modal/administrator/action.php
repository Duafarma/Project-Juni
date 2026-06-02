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
			$id		= '';
			$kode	= 'ADM'.time();
			$cabang	= $secu->injection($_POST['cabang']);
			$nama	= $secu->injection($_POST['nama']);
			$jenis	= $secu->injection($_POST['jenis']);
			$email	= $secu->injection($_POST['emailz']);
			$passz	= $secu->injection($_POST['passz']);
			$unix	= md5($passz);
			$kunci	= $secu->enkripsi($passz);
			$active	= 'Active';
			$foto	= '';
			$kode_sales = 'A';
			//CEK DATA
			$read	= $conn->prepare("SELECT COUNT(id_adm) AS total FROM adminz WHERE email_adm=:email");
			$read->bindParam(':email', $email, PDO::PARAM_STR);
			$read->execute();
			$view	= $read->fetch(PDO::FETCH_ASSOC);
			if(empty($view['total'])){
				$save	= $conn->prepare("INSERT INTO adminz VALUES(:kode, :cabang, :jenis, :nama, :email, :foto, :active, :kode_sales, :catat, :admin, :catat, :admin)");
				$save->bindParam(":kode", $kode, PDO::PARAM_STR);
				$save->bindParam(":cabang", $cabang, PDO::PARAM_STR);
				$save->bindParam(":jenis", $jenis, PDO::PARAM_STR);
				$save->bindParam(":nama", $nama, PDO::PARAM_STR);
				$save->bindParam(":email", $email, PDO::PARAM_STR);
				$save->bindParam(":foto", $foto, PDO::PARAM_STR);
				$save->bindParam(":active", $active, PDO::PARAM_STR);
				$save->bindParam(":kode_sales", $kode_sales, PDO::PARAM_STR);

				$save->bindParam(":catat", $catat, PDO::PARAM_STR);
				$save->bindParam(":admin", $admin, PDO::PARAM_STR);
				$save->execute();
				//ROLE MENU
				$jumlah	= count($_POST['submenu']);
				$nomor	= 0;
				while($nomor<$jumlah){
					$submenu= $secu->injection(@$_POST['submenu'][$nomor]);
					$tambah	= empty($_POST["t$submenu"]) ? 'Non Active' : 'Active';
					$lihat	= empty($_POST["l$submenu"]) ? 'Non Active' : 'Active';
					$ubah	= empty($_POST["u$submenu"]) ? 'Non Active' : 'Active';
					$hapus	= empty($_POST["h$submenu"]) ? 'Non Active' : 'Active';
					$save	= $conn->prepare("INSERT INTO role_menu VALUES(:id, :kode, :submenu, :tambah, :lihat, :ubah, :hapus)");
					$save->bindParam(":id", $id, PDO::PARAM_STR);
					$save->bindParam(":kode", $kode, PDO::PARAM_STR);
					$save->bindParam(":submenu", $submenu, PDO::PARAM_STR);
					$save->bindParam(":tambah", $tambah, PDO::PARAM_STR);
					$save->bindParam(":lihat", $lihat, PDO::PARAM_STR);
					$save->bindParam(":ubah", $ubah, PDO::PARAM_STR);
					$save->bindParam(":hapus", $hapus, PDO::PARAM_STR);
					$save->execute();
				$nomor++;
				}
				//ACCOUNT
				$save	= $conn->prepare("INSERT INTO adminz_account VALUES(:id, :kode, :unix, :kunci, :catat, :admin, :catat, :admin)");
				$save->bindParam(":id", $id, PDO::PARAM_STR);
				$save->bindParam(":kode", $kode, PDO::PARAM_STR);
				$save->bindParam(":unix", $unix, PDO::PARAM_STR);
				$save->bindParam(":kunci", $kunci, PDO::PARAM_STR);
				$save->bindParam(":active", $active, PDO::PARAM_STR);
				$save->bindParam(":catat", $catat, PDO::PARAM_STR);
				$save->bindParam(":admin", $admin, PDO::PARAM_STR);
				$save->execute();
				//RIWAYAT
				$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Administrator', 'Create', '', '$catat', '$admin')");
				$hasil	= ($save==true) ? "success" : "error";
			} else {
				$hasil	= "error";
			}
			echo($hasil);
		break;
		case "update":
			$uniq	= $secu->injection($_POST['keycode']);
			$kode	= base64_decode($uniq);
			$cabang	= $secu->injection($_POST['cabang']);
			$nama	= $secu->injection($_POST['nama']);
			$jenis	= $secu->injection($_POST['jenis']);
			$email	= $secu->injection($_POST['emailz']);
			$passz	= $secu->injection($_POST['passz']);
			$unix	= md5($passz);
			$kunci	= $secu->enkripsi($passz);
			//CEK DATA
			$read	= $conn->prepare("SELECT COUNT(id_adm) AS total FROM adminz WHERE id_adm!=:kode AND email_adm=:email");
			$read->bindParam(':kode', $kode, PDO::PARAM_STR);
			$read->bindParam(':email', $email, PDO::PARAM_STR);
			$read->execute();
			$view	= $read->fetch(PDO::FETCH_ASSOC);
			if(empty($view['total'])){
				$edit	= $conn->prepare("UPDATE adminz SET id_cabang=:cabang, jenis_adm=:jenis, nama_adm=:nama, email_adm=:email, updated_at=:catat, updated_by=:admin WHERE id_adm=:kode");
				$edit->bindParam(":kode", $kode, PDO::PARAM_STR);
				$edit->bindParam(":cabang", $cabang, PDO::PARAM_STR);
				$edit->bindParam(":jenis", $jenis, PDO::PARAM_STR);
				$edit->bindParam(":nama", $nama, PDO::PARAM_STR);
				$edit->bindParam(":email", $email, PDO::PARAM_STR);
				$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
				$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
				$edit->execute();
				//DELETE
				$remove	= $conn->prepare("DELETE FROM role_menu WHERE id_adm=:kode");
				$remove->bindParam(":kode", $kode, PDO::PARAM_STR);
				$remove->execute();
				//ROLE MENU
				$jumlah	= count($_POST['submenu']);
				$nomor	= 0;
				while($nomor<$jumlah){
					$submenu= $secu->injection(@$_POST['submenu'][$nomor]);
					$tambah	= empty($_POST["t$submenu"]) ? 'Non Active' : 'Active';
					$lihat	= empty($_POST["l$submenu"]) ? 'Non Active' : 'Active';
					$ubah	= empty($_POST["u$submenu"]) ? 'Non Active' : 'Active';
					$hapus	= empty($_POST["h$submenu"]) ? 'Non Active' : 'Active';
					$edit	= $conn->prepare("INSERT INTO role_menu VALUES(:id, :kode, :submenu, :tambah, :lihat, :ubah, :hapus)");
					$edit->bindParam(":id", $id, PDO::PARAM_STR);
					$edit->bindParam(":kode", $kode, PDO::PARAM_STR);
					$edit->bindParam(":submenu", $submenu, PDO::PARAM_STR);
					$edit->bindParam(":tambah", $tambah, PDO::PARAM_STR);
					$edit->bindParam(":lihat", $lihat, PDO::PARAM_STR);
					$edit->bindParam(":ubah", $ubah, PDO::PARAM_STR);
					$edit->bindParam(":hapus", $hapus, PDO::PARAM_STR);
					$edit->execute();
				$nomor++;
				}
				//ACCOUNT
				if(!empty($passz)){
					$edit	= $conn->prepare("UPDATE adminz_account SET password_aac=:unix, kunci_aac=:kunci, updated_at=:catat, updated_by=:admin WHERE id_adm=:kode");
					$edit->bindParam(":kode", $kode, PDO::PARAM_STR);
					$edit->bindParam(":unix", $unix, PDO::PARAM_STR);
					$edit->bindParam(":kunci", $kunci, PDO::PARAM_STR);
					$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
					$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
					$edit->execute();
				}
				//RIWAYAT
				$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Administrator', 'Update', '', '$catat', '$admin')");
				$hasil	= ($edit==true) ? "success" : "error";
			} else {
				$hasil	= "error";
			}
			echo($hasil);
		break;
		case "delete":
			$kode	= $secu->injection($_POST['keycode']);
			$remove	= $conn->prepare("DELETE A, B, C FROM adminz AS A LEFT JOIN adminz_account AS B ON A.id_adm=B.id_adm LEFT JOIN role_menu AS C ON A.id_adm=C.id_adm WHERE A.id_adm=:kode");
			$remove->bindParam(":kode", $kode, PDO::PARAM_STR);
			$remove->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Administrator', 'Delete', '', '$catat', '$admin')");
			$hasil	= ($remove==true) ? "success" : "error";
			echo($hasil);
		break;
	}
?>
