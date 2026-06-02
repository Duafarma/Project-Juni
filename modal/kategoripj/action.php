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
			$kode	            = $data->bcode('PJ', 'id_pj', 'kategori_produk_jadi');
			$nama_produk_jadi	= $secu->injection($_POST['nama_produk_jadi']);
			$save	= $conn->prepare("INSERT INTO kategori_produk_jadi VALUES(:kode, :nama_produk_jadi, :catat, :admin, :catat, :admin)");
			$save->bindParam(":kode", $kode, PDO::PARAM_STR);
			$save->bindParam(":nama_produk_jadi", $nama_produk_jadi, PDO::PARAM_STR);
			$save->bindParam(":catat", $catat, PDO::PARAM_STR);
			$save->bindParam(":admin", $admin, PDO::PARAM_STR);
			$save->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'nama_produk_jadi', 'Create', '', '$catat', '$admin')");
			$hasil	= ($save==true) ? "success" : "error";
			echo($hasil);
		break;
		case "update":
			$kode	            = $secu->injection($_POST['keycode']);
			$nama_produk_jadi	= $secu->injection($_POST['nama_produk_jadi']);
			$edit	= $conn->prepare("UPDATE kategori_produk_jadi SET nama_produk_jadi=:nama_produk_jadi, updated_at=:catat, updated_by=:admin WHERE id_pj=:kode");
			$edit->bindParam(":kode", $kode, PDO::PARAM_STR);
			$edit->bindParam(":nama_produk_jadi", $nama_produk_jadi, PDO::PARAM_STR);
			$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
			$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
			$edit->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'nama_produk_jadi', 'Update', '', '$catat', '$admin')");
			$hasil	= ($edit==true) ? "success" : "error";
			echo($hasil);
		break;
		case "delete":
			$kode	= $secu->injection($_POST['keycode']);
			$dele	= $conn->prepare("DELETE FROM kategori_produk_jadi WHERE id_pj=:kode");
			$dele->bindParam(":kode", $kode, PDO::PARAM_STR);
			$dele->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'nama_produk_jadi', 'Delete', '', '$catat', '$admin')");
			$hasil	= ($dele==true) ? "success" : "error";
			echo($hasil);
		break;
	}
?>
