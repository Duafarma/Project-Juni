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
	switch($menu){
		case "input":
			$id		= '';
			$uniq	= $secu->injection($_POST['keycode']);
			$kode	= base64_decode($uniq);
			$produk	= $secu->injection($_POST['produk']);
			$jumlah	= str_replace('.', '', $_POST['jumlah']);
			//Edit
			$save	= $conn->prepare("INSERT INTO transaksi_orderdetail VALUES(:id, :kode, :produk, :jumlah, :catat, :admin, :catat, :admin)");
			$save->bindParam(":id", $id, PDO::PARAM_STR);
			$save->bindParam(":kode", $kode, PDO::PARAM_STR);
			$save->bindParam(":produk", $produk, PDO::PARAM_STR);
			$save->bindParam(":jumlah", $jumlah, PDO::PARAM_INT);
			$save->bindParam(":catat", $catat, PDO::PARAM_STR);
			$save->bindParam(":admin", $admin, PDO::PARAM_STR);
			$save->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Detail Pembelian', 'Input', '', '$catat', '$admin')");
			if($save==true){
				setcookie('info', 'success', time() + 5, '/');
				setcookie('pesan', 'Data item pembelian berhasil ditambahkan.', time() + 5, '/');
			} else {
				setcookie('info', 'danger', time() + 5, '/');
				setcookie('pesan', 'Data item pembelian gagal ditambahkan.', time() + 5, '/');
			}
			$url	= "order/v/$kode";
		break;
		case "update":
			$uniq	= $secu->injection($_POST['keycode']);
			$kode	= base64_decode($uniq);
			$produk	= $secu->injection($_POST['produk']);
			$jumlah	= str_replace('.', '', $_POST['jumlah']);
			//Read Data
			$read	= $conn->prepare("SELECT id_tor FROM transaksi_orderdetail WHERE id_tod=:kode");
			$read->bindParam(":kode", $kode, PDO::PARAM_STR);
			$read->execute();
			$view	= $read->fetch(PDO::FETCH_ASSOC);
			//Edit
			$edit	= $conn->prepare("UPDATE transaksi_orderdetail SET id_pro=:produk, jumlah_tod=:jumlah, updated_at=:catat, updated_by=:admin WHERE id_tod=:kode");
			$edit->bindParam(":kode", $kode, PDO::PARAM_STR);
			$edit->bindParam(":produk", $produk, PDO::PARAM_STR);
			$edit->bindParam(":jumlah", $jumlah, PDO::PARAM_INT);
			$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
			$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
			$edit->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Detail Pembelian', 'Update', '', '$catat', '$admin')");
			if($edit==true){
				setcookie('info', 'success', time() + 5, '/');
				setcookie('pesan', 'Data item pembelian berhasil diupdate.', time() + 5, '/');
			} else {
				setcookie('info', 'danger', time() + 5, '/');
				setcookie('pesan', 'Data item pembelian gagal diupdate.', time() + 5, '/');
			}
			$url	= "order/v/$view[id_tor]";
		break;
		case "delete":
			$uniq	= $secu->injection($_POST['keycode']);
			$kode	= base64_decode($uniq);
			//Read Data
			$read	= $conn->prepare("SELECT id_tor FROM transaksi_orderdetail WHERE id_tod=:kode");
			$read->bindParam(":kode", $kode, PDO::PARAM_STR);
			$read->execute();
			$view	= $read->fetch(PDO::FETCH_ASSOC);
			//Remove
			$remove	= $conn->prepare("DELETE FROM transaksi_orderdetail WHERE id_tod=:kode");
			$remove->bindParam(":kode", $kode, PDO::PARAM_STR);
			$remove->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Detail Pembelian', 'Delete', '', '$catat', '$admin')");
			if($remove==true){
				setcookie('info', 'success', time() + 5, '/');
				setcookie('pesan', 'Data item pembelian berhasil diupdate.', time() + 5, '/');
			} else {
				setcookie('info', 'danger', time() + 5, '/');
				setcookie('pesan', 'Data item pembelian gagal diupdate.', time() + 5, '/');
			}
			$url	= "order/v/$view[id_tor]";
		break;
	}
	echo($url);
?>