<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$conn	= $base->open();
	$catat	= date('Y-m-d H:i:s');
	$admin	= 'SYS';
	$act	= $secu->injection(@$_GET['act']);
	switch($act){
		case "input":
			$kode	= $data->basecode('', 1, 'id_phg', 'produk_harga');
			$produk	= $secu->injection($_POST['keycode']);
			$harga	= str_replace('.', '', $_POST['harga']);
			$hargap	= str_replace('.', '', $_POST['hargappn']);
			$active	= 'Active';
			$inact	= 'Inactive';
			//UPDATE
			$edit	= $conn->prepare("UPDATE produk_harga SET status_phg=:inact, updated_at=:catat, updated_by=:admin WHERE id_pro=:produk");
			$edit->bindParam(":produk", $produk, PDO::PARAM_STR);
			$edit->bindParam(":inact", $inact, PDO::PARAM_STR);
			$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
			$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
			$edit->execute();
			//SAVE
			$save	= $conn->prepare("INSERT INTO produk_harga VALUES(:kode, :produk, :harga, :hargap, :active, :catat, :admin, :catat, :admin)");
			$save->bindParam(":kode", $kode, PDO::PARAM_STR);
			$save->bindParam(":produk", $produk, PDO::PARAM_STR);
			$save->bindParam(":harga", $harga, PDO::PARAM_INT);
			$save->bindParam(":hargap", $hargap, PDO::PARAM_INT);
			$save->bindParam(":active", $active, PDO::PARAM_STR);
			$save->bindParam(":catat", $catat, PDO::PARAM_STR);
			$save->bindParam(":admin", $admin, PDO::PARAM_STR);
			$save->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Harga Produk', 'Create', '', '$catat', '$admin')");
			$hasil	= ($save==true) ? "success" : "error";
			echo($hasil);
			$url	= 'produk';
		break;
	}
?>