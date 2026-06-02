<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	require_once('../../config/function/thumb.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$file	= new File;
	$conn	= $base->open();
	$catat	= date('Y-m-d H:i:s');
	$admin	= $secu->injection(@$_COOKIE['adminkuy']);
	$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
	$act	= $secu->injection(@$_GET['act']);
	switch($act){
		case "input":
			$kode	= $data->basecode('', 1, 'id_pre', 'pembayaran_receive');
			$order	= $secu->injection($_POST['kodeorder']);
			$bank	= $secu->injection($_POST['bank']);
			$norek	= $secu->injection($_POST['norek']);
			$nama	= $secu->injection($_POST['nama']);
			$bayar	= str_replace('.', '', $_POST['bayar']);
			$sisa	= str_replace('.', '', $_POST['sisa']);
			$tgl	= $secu->injection($_POST['tanggal']);

			$loga	= $_FILES["foto1"]['tmp_name'];
			$tiga	= $_FILES["foto1"]['type'];
			$naga	= $_FILES["foto1"]['name'];
			$fofe	= pathinfo($naga, PATHINFO_EXTENSION);
			$unik	= "porder".time().".".$fofe;
			$file->uploadfoto('porder', 'foto1', $unik, 400, 500);
			//UPDATE
			$status	= ($bayar==$sisa) ? 'Lunas' : 'Bayar';
			$edit	= $conn->prepare("UPDATE transaksi_receive SET status_tre=:status, updated_at=:catat, updated_by=:admin WHERE id_tre=:order");
			$edit->bindParam(":order", $order, PDO::PARAM_STR);
			$edit->bindParam(":status", $status, PDO::PARAM_STR);
			$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
			$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
			$edit->execute();
			//SAVE
			$save	= $conn->prepare("INSERT INTO pembayaran_receive VALUES(:id, :order, :bank, :norek, :nama, :bayar, :unik, :tgl, :catat, :admin, :catat, :admin)");
			$save->bindParam(":id", $id, PDO::PARAM_STR);
			$save->bindParam(":order", $order, PDO::PARAM_STR);
			$save->bindParam(":bank", $bank, PDO::PARAM_STR);
			$save->bindParam(":norek", $norek, PDO::PARAM_STR);
			$save->bindParam(":nama", $nama, PDO::PARAM_STR);
			$save->bindParam(":bayar", $bayar, PDO::PARAM_INT);
			$save->bindParam(":unik", $unik, PDO::PARAM_STR);
			$save->bindParam(":tgl", $tgl, PDO::PARAM_STR);
			$save->bindParam(":catat", $catat, PDO::PARAM_STR);
			$save->bindParam(":admin", $admin, PDO::PARAM_STR);
			$save->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Pembayaran Supllier', 'Create', '', '$catat', '$admin')");
			$hasil	= ($save==true) ? "success" : "error";
			echo($hasil);
		break;
		case "update":
			$kode	= $secu->injection($_POST['keycode']);
			$order	= $secu->injection($_POST['kodeorder']);
			$bank	= $secu->injection($_POST['bank']);
			$norek	= $secu->injection($_POST['norek']);
			$nama	= $secu->injection($_POST['nama']);
			$bayar	= str_replace('.', '', $_POST['bayar']);
			$sisa	= str_replace('.', '', $_POST['sisa']);
			$tgl	= $secu->injection($_POST['tanggal']);
			//FOTO
			$loga	= $_FILES["foto1"]['tmp_name'];
			$tiga	= $_FILES["foto1"]['type'];
			$naga	= $_FILES["foto1"]['name'];
			$fofe	= pathinfo($naga, PATHINFO_EXTENSION);
			$unik	= "porder".time().".".$fofe;
			//SUMMARY
			$mbayar	= $conn->prepare("SELECT SUM(jumlah_pre) AS total FROM pembayaran_receive WHERE id_pre!=:kode");
			$mbayar->bindParam(':kode', $kode, PDO::PARAM_STR);
			$mbayar->execute();
			$hbayar	= $mbayar->fetch(PDO::FETCH_ASSOC);
			//CEK DATA
			$read	= $conn->prepare("SELECT A.id_tre, A.file_pre, A.jumlah_pre, B.total_tre FROM pembayaran_receive AS A LEFT JOIN transaksi_receive AS B ON A.id_tre=B.id_tre WHERE A.id_pre=:kode");
			$read->bindParam(':kode', $kode, PDO::PARAM_STR);
			$read->execute();
			$view	= $read->fetch(PDO::FETCH_ASSOC);
			$sisa	= $view['total_tre'] - ($hbayar['total'] + $bayar);
			$status	= empty($sisa) ? 'Lunas' : 'Bayar';
			if($bayar!=$view['jumlah_pre']){
				$edit	= $conn->prepare("UPDATE transaksi_receive SET status_tre=:status, updated_at=:catat, updated_by=:admin WHERE id_tre=:kode");
				$edit->bindParam(":kode", $view['id_tre'], PDO::PARAM_STR);
				$edit->bindParam(":status", $status, PDO::PARAM_STR);
				$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
				$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
				$edit->execute();
			}
			if(!empty($loga)){
				unlink("../../berkas/porder/$view[file_pre]");
				$file->uploadfoto('porder', 'foto1', $unik, 400, 500);
				$edit	= $conn->prepare("UPDATE pembayaran_receive SET file_pre=:unik, updated_at=:catat, updated_by=:admin WHERE id_pre=:kode");
				$edit->bindParam(":kode", $kode, PDO::PARAM_STR);
				$edit->bindParam(":unik", $unik, PDO::PARAM_STR);
				$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
				$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
				$edit->execute();
			}
			//EDIT
			$edit	= $conn->prepare("UPDATE pembayaran_receive SET bank_pre=:bank, norek_pre=:norek, anam_pre=:nama, jumlah_pre=:bayar, tgl_pre=:tgl, updated_at=:catat, updated_by=:admin WHERE id_pre=:kode");
			$edit->bindParam(":kode", $kode, PDO::PARAM_STR);
			$edit->bindParam(":bank", $bank, PDO::PARAM_STR);
			$edit->bindParam(":norek", $norek, PDO::PARAM_STR);
			$edit->bindParam(":nama", $nama, PDO::PARAM_STR);
			$edit->bindParam(":bayar", $bayar, PDO::PARAM_INT);
			$edit->bindParam(":tgl", $tgl, PDO::PARAM_STR);
			$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
			$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
			$edit->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Pembayaran Supllier', 'Update', '', '$catat', '$admin')");
			$hasil	= ($edit==true) ? "success" : "error";
			echo($hasil);
		break;
		case "delete":
			$kode	= $secu->injection($_POST['keycode']);
			$bayar	= 0;
			$mbayar	= $conn->prepare("SELECT SUM(jumlah_pre) AS total FROM pembayaran_receive WHERE id_pre!=:kode");
			$mbayar->bindParam(':kode', $kode, PDO::PARAM_STR);
			$mbayar->execute();
			$hbayar	= $mbayar->fetch(PDO::FETCH_ASSOC);
			//CEK DATA
			$read	= $conn->prepare("SELECT A.id_tre, A.file_pre, A.jumlah_pre, B.total_tre FROM pembayaran_receive AS A LEFT JOIN transaksi_receive AS B ON A.id_tre=B.id_tre WHERE A.id_pre=:kode");
			$read->bindParam(':kode', $kode, PDO::PARAM_STR);
			$read->execute();
			$view	= $read->fetch(PDO::FETCH_ASSOC);
			$sisa	= $view['total_tre'] - ($hbayar['total'] + $bayar);
			$status	= empty($sisa) ? 'Lunas' : 'Bayar';
			if($bayar!=$view['jumlah_pre']){
				$edit	= $conn->prepare("UPDATE transaksi_receive SET status_tre=:status, updated_at=:catat, updated_by=:admin WHERE id_tre=:kode");
				$edit->bindParam(":kode", $view['id_tre'], PDO::PARAM_STR);
				$edit->bindParam(":status", $status, PDO::PARAM_STR);
				$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
				$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
				$edit->execute();
			}
			//DELETE
			unlink("../../berkas/porder/$view[file_pre]");
			$delete	= $conn->prepare("DELETE FROM pembayaran_receive WHERE id_pre=:kode");
			$delete->bindParam(":kode", $kode, PDO::PARAM_STR);
			$delete->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Pembayaran Supllier', 'Delete', '', '$catat', '$admin')");
			$hasil	= ($delete==true) ? "success" : "error";
			echo($hasil);
		break;
	}
?>
