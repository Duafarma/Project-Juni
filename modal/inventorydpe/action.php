<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$conn	= $base->open();
	$admin	= $secu->injection(@$_COOKIE['adminkuy']);
	$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
	$level	= $secu->injection(@$_COOKIE['jeniskuy']);
	$catat	= date('Y-m-d H:i:s');
	$act	= $secu->injection(@$_GET['act']);
	switch($act){
		case "update":
			$kode	= $secu->injection($_POST['keycode']);
			$bcode	= $secu->injection($_POST['nobcode']);
			$tglexp	= $secu->injection($_POST['tglexpired']);
			$gudang = $secu->injection($_POST['gudang']);
			$masuk	= str_replace('.', '', $_POST['masuk']);
			$keluar	= str_replace('.', '', $_POST['keluar']);
			$sisa	= str_replace('.', '', $_POST['sisa']);
			//CEK DATA
			$read	= $conn->prepare("SELECT id_trd FROM produk_stokdetail WHERE id_psd=:kode");
			$read->bindParam(':kode', $kode, PDO::PARAM_STR);
			$read->execute();
			$view	= $read->fetch(PDO::FETCH_ASSOC);
			//UPDATE
			$edit	= $conn->prepare("UPDATE transaksi_receivedetail SET bcode_trd=:bcode, gudang=:gudang, tbcode_trd=:tglexp, jumlah_trd=:masuk, updated_at=:catat, updated_by=:admin WHERE id_trd=:kode");
			$edit->bindParam(':kode', $view['id_trd'], PDO::PARAM_STR);
			$edit->bindParam(':masuk', $masuk, PDO::PARAM_INT);
			$edit->bindParam(':bcode', $bcode, PDO::PARAM_STR);
			$edit->bindParam(':gudang', $gudang, PDO::PARAM_STR);
			$edit->bindParam(':tglexp', $tglexp, PDO::PARAM_STR);
			$edit->bindParam(':catat', $catat, PDO::PARAM_STR);
			$edit->bindParam(':admin', $admin, PDO::PARAM_STR);
			$edit->execute();
			//SAVE
			$edit	= $conn->prepare("UPDATE produk_stokdetail SET no_bcode=:bcode, gudang=:gudang, tgl_expired=:tglexp, masuk_psd=:masuk, keluar_psd=:keluar, sisa_psd=:sisa, updated_at=:catat, updated_by=:admin WHERE id_psd=:kode");
			$edit->bindParam(":kode", $kode, PDO::PARAM_STR);
			$edit->bindParam(":bcode", $bcode, PDO::PARAM_STR);
			$edit->bindParam(":gudang", $gudang, PDO::PARAM_STR);
			$edit->bindParam(":tglexp", $tglexp, PDO::PARAM_STR);
			$edit->bindParam(":masuk", $masuk, PDO::PARAM_INT);
			$edit->bindParam(":keluar", $keluar, PDO::PARAM_INT);
			$edit->bindParam(":sisa", $sisa, PDO::PARAM_INT);
			$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
			$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
			$edit->execute();
			$hasil	= ($edit==true) ? "success" : "error";
			echo($hasil);
		break;
	}
?>
