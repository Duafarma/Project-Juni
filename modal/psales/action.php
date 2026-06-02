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
			$kode	= $data->basecode('', 1, 'id_pfk', 'pembayaran_faktur');
			$sales	= $secu->injection($_POST['kodesales']);
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
			$unik	= "pfaktur".time().".".$fofe;
			// $file->uploadfoto('pfaktur', 'foto1', $unik, 400, 500);
			$status	= ($bayar==$sisa) ? 'Lunas' : 'Bayar';
			//EDIT
			$edit	= $conn->prepare("UPDATE transaksi_faktur SET status_tfk=:status, updated_at=:catat, updated_by=:admin WHERE id_tfk=:sales");
			$edit->bindParam(":sales", $sales, PDO::PARAM_STR);
			$edit->bindParam(":status", $status, PDO::PARAM_STR);
			$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
			$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
			$edit->execute();
			//SAVE
			$save	= $conn->prepare("INSERT INTO pembayaran_faktur VALUES(:id, :sales, :bank, :norek, :nama, :bayar, :unik, :tgl, :catat, :admin, :catat, :admin)");
			$save->bindParam(":id", $id, PDO::PARAM_STR);
			$save->bindParam(":sales", $sales, PDO::PARAM_STR);
			$save->bindParam(":bank", $bank, PDO::PARAM_STR);
			$save->bindParam(":norek", $norek);
			$save->bindParam(":nama", $nama);
			$save->bindParam(":bayar", $bayar, PDO::PARAM_INT);
			$save->bindParam(":unik", $unik);
			$save->bindParam(":tgl", $tgl, PDO::PARAM_STR);
			$save->bindParam(":catat", $catat, PDO::PARAM_STR);
			$save->bindParam(":admin", $admin, PDO::PARAM_STR);
			$save->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Pembayaran Outlet', 'Create', '', '$catat', '$admin')");
			$hasil	= ($save==true) ? "success" : "error";
			echo($hasil);
		break;
		case "update":
			$kode	= $secu->injection($_POST['keycode']);
			$sales	= $secu->injection($_POST['kodesales']);
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
			$unik	= "pfaktur".time().".".$fofe;
			//SUMMARY
			$mbayar	= $conn->prepare("SELECT SUM(jumlah_pfk) AS total FROM pembayaran_faktur WHERE id_pfk!=:kode");
			$mbayar->bindParam(':kode', $kode, PDO::PARAM_STR);
			$mbayar->execute();
			$hbayar	= $mbayar->fetch(PDO::FETCH_ASSOC);
			//CEK DATA
			$read	= $conn->prepare("SELECT A.id_tfk, A.file_pfk, A.jumlah_pfk, B.total_tfk FROM pembayaran_faktur AS A LEFT JOIN transaksi_faktur AS B ON A.id_tfk=B.id_tfk WHERE A.id_pfk=:kode");
			$read->bindParam(':kode', $kode, PDO::PARAM_STR);
			$read->execute();
			$view	= $read->fetch(PDO::FETCH_ASSOC);
			$sisa	= $view['total_tfk'] - ($hbayar['total'] + $bayar);
			$status	= empty($sisa) ? 'Lunas' : 'Bayar';
			if($bayar!=$view['jumlah_pfk']){
				$edit	= $conn->prepare("UPDATE transaksi_faktur SET status_tfk=:status, updated_at=:catat, updated_by=:admin WHERE id_tfk=:kode");
				$edit->bindParam(":kode", $view['id_tfk'], PDO::PARAM_STR);
				$edit->bindParam(":status", $status, PDO::PARAM_STR);
				$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
				$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
				$edit->execute();
			}
			if(!empty($loga)){
				unlink("../../berkas/pfaktur/$view[file_pfk]");
				$file->uploadfoto('pfaktur', 'foto1', $unik, 400, 500);
				$edit	= $conn->prepare("UPDATE pembayaran_faktur SET file_pfk=:unik, updated_at=:catat, updated_by=:admin WHERE id_pfk=:kode");
				$edit->bindParam(":kode", $kode, PDO::PARAM_STR);
				$edit->bindParam(":unik", $unik, PDO::PARAM_STR);
				$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
				$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
				$edit->execute();
			}
			//EDIT
			$edit	= $conn->prepare("UPDATE pembayaran_faktur SET bank_pfk=:bank, norek_pfk=:norek, anam_pfk=:nama, jumlah_pfk=:bayar, tgl_pfk=:tgl, updated_at=:catat, updated_by=:admin WHERE id_pfk=:kode");
			$edit->bindParam(":kode", $kode, PDO::PARAM_STR);
			$edit->bindParam(":bank", $bank, PDO::PARAM_STR);
			$edit->bindParam(":norek", $norek, PDO::PARAM_STR);
			$edit->bindParam(":nama", $nama, PDO::PARAM_STR);
			$edit->bindParam(":bayar", $bayar, PDO::PARAM_INT);
			$edit->bindParam(":tgl", $tgl, PDO::PARAM_STR);
			$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
			$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
			$edit->execute();
			$hasil	= ($edit==true) ? "success" : "error";
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Pembayaran Outlet', 'UPDATE', '', '$catat', '$admin')");
			echo($hasil);
		break;
		case "delete":
			$kode	= $secu->injection($_POST['keycode']);
			$bayar	= 0;
			//SUMMARY
			$mbayar	= $conn->prepare("SELECT SUM(jumlah_pfk) AS total FROM pembayaran_faktur WHERE id_pfk!=:kode");
			$mbayar->bindParam(':kode', $kode, PDO::PARAM_STR);
			$mbayar->execute();
			$hbayar	= $mbayar->fetch(PDO::FETCH_ASSOC);
			//CEK DATA
			$read	= $conn->prepare("SELECT A.id_tfk, A.file_pfk, A.jumlah_pfk, B.total_tfk FROM pembayaran_faktur AS A LEFT JOIN transaksi_faktur AS B ON A.id_tfk=B.id_tfk WHERE A.id_pfk=:kode");
			$read->bindParam(':kode', $kode, PDO::PARAM_STR);
			$read->execute();
			$view	= $read->fetch(PDO::FETCH_ASSOC);
			$sisa	= $view['total_tfk'] - ($hbayar['total'] + $bayar);
			$status	= empty($sisa) ? 'Lunas' : 'Bayar';
			if($bayar!=$view['jumlah_pfk']){
				$edit	= $conn->prepare("UPDATE transaksi_faktur SET status_tfk=:status, updated_at=:catat, updated_by=:admin WHERE id_tfk=:kode");
				$edit->bindParam(":kode", $view['id_tfk'], PDO::PARAM_STR);
				$edit->bindParam(":status", $status, PDO::PARAM_STR);
				$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
				$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
				$edit->execute();
			}
			//DELETE
			unlink("../../berkas/pfaktur/$view[file_pfk]");
			$delete	= $conn->prepare("DELETE FROM pembayaran_faktur WHERE id_pfk=:kode");
			$delete->bindParam(":kode", $kode, PDO::PARAM_STR);
			$delete->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Pembayaran Outlet', 'Delete', '', '$catat', '$admin')");
			$hasil	= ($delete==true) ? "success" : "error";
			echo($hasil);
		break;
	}
?>
