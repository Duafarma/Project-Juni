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
		case "update":
			$uniq	= $secu->injection($_POST['keycode']);
			$kode	= base64_decode($uniq);
			$jumlah	= $secu->injection($_POST['jumlah']);
			//Read Data
			$read	= $conn->prepare("SELECT id_out FROM produk_diskon WHERE id_pds=:kode");
			$read->bindParam(":kode", $kode, PDO::PARAM_STR);
			$read->execute();
			$view	= $read->fetch(PDO::FETCH_ASSOC);
			$unik	= base64_encode($view['id_out']);
			//Edit Data
			$edit	= $conn->prepare("UPDATE produk_diskon SET persen_pds=:jumlah, updated_at=:catat, updated_by=:admin WHERE id_pds=:kode");
			$edit->bindParam(":kode", $kode, PDO::PARAM_STR);
			$edit->bindParam(":jumlah", $jumlah, PDO::PARAM_STR);
			$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
			$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
			$edit->execute();
			$edit->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Produk Diskon', 'Update', '', '$catat', '$admin')");
			if($edit==true){
				setcookie('info', 'success', time() + 5, '/');
				setcookie('pesan', 'Data diskon produk berhasil diupdate.', time() + 5, '/');
			} else {
				setcookie('info', 'danger', time() + 5, '/');
				setcookie('pesan', 'Data diskon produk gagal diupdate.', time() + 5, '/');
			}
			$url	= "dispro/out=$unik&cari=";
		break;
	}
	echo($url);
?>