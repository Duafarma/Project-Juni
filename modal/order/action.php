<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$catat	= date('Y-m-d H:i:s');
	$admin	= $secu->injection(@$_COOKIE['adminkuy']);
	$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
	$act	= $secu->injection(@$_GET['act']);
	$secu->validadmin($admin, $kunci);
	if($secu->validadmin($admin, $kunci)==false){ header('location:'.$data->sistem('url_sis').'/signout'); } else {
	$conn	= $base->open();
	switch($act){
		case "input":
			$id		= '';
			$code	= 'ODR'.time();
			//$unik	= "/PO/".$data->romawi(date('m')).'/'.date('y');
			//$kode	= $data->transcode($unik, "kode_tor", "transaksi_order");
			$kode	= $secu->injection($_POST['kode']);
			$sup	= $secu->injection($_POST['supplier']);
			$tgl	= $secu->injection($_POST['tanggal']);
			$kete	= $secu->injection($_POST['keterangan']);
			$id_pp	= !empty($_POST['id_pp']) ? intval($_POST['id_pp']) : null;
			$proses	= 'Open';
			$status	= 'Proses';
			$jum	= count($_POST['product']);
			$no		= 0;
			while($no<$jum){
				$produk	= $secu->injection($_POST['product'][$no]);
				$jumlah	= str_replace('.', '', $_POST['jumlah'][$no]);
				$save	= $conn->prepare("INSERT INTO transaksi_orderdetail VALUES(:id, :code, :produk, :jumlah, :catat, :admin, :catat, :admin)");
				$save->bindParam(':id', $id, PDO::PARAM_STR);
				$save->bindParam(':code', $code, PDO::PARAM_STR);
				$save->bindParam(':produk', $produk, PDO::PARAM_STR);
				$save->bindParam(':jumlah', $jumlah, PDO::PARAM_INT);
				$save->bindParam(':catat', $catat, PDO::PARAM_STR);
				$save->bindParam(':admin', $admin, PDO::PARAM_STR);
				$save->execute();			
			$no++;
			}
			//SAVE
			$save	= $conn->prepare("INSERT INTO transaksi_order (id_tor, kode_tor, id_sup, tgl_tor, ket_tor, id_pp, status_tor, proses_tor, created_at, created_by, updated_at, updated_by) VALUES(:code, :kode, :sup, :tgl, :kete, :id_pp, :status, :proses, :catat, :admin, :catat, :admin)");
			$save->bindParam(':code', $code, PDO::PARAM_STR);
			$save->bindParam(':kode', $kode, PDO::PARAM_STR);
			$save->bindParam(':sup', $sup, PDO::PARAM_STR);
			$save->bindParam(':tgl', $tgl, PDO::PARAM_STR);
			$save->bindParam(':kete', $kete, PDO::PARAM_STR);
			$save->bindParam(':id_pp', $id_pp, PDO::PARAM_INT);
			$save->bindParam(':status', $status, PDO::PARAM_STR);
			$save->bindParam(':proses', $proses, PDO::PARAM_STR);
			$save->bindParam(':catat', $catat, PDO::PARAM_STR);
			$save->bindParam(':admin', $admin, PDO::PARAM_STR);
			$save->execute();			
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$code', 'Order Produk', 'Create', '', '$catat', '$admin')");
			$hasil	= ($save==true) ? "success" : "error";
			echo($hasil);
		break;
		case "update":
			$kode		= $secu->injection($_POST['keycode']);
			$nomor		= $secu->injection($_POST['nomorpo']);
			$tgl_tor	= $secu->injection($_POST['tgl_tor']);
			$ket_tor	= $secu->injection($_POST['ket_tor']);
			$edit		= $conn->prepare("UPDATE transaksi_order SET kode_tor=:nomor, tgl_tor=:tgl_tor, ket_tor=:ket_tor  WHERE id_tor=:kode");
			$edit->bindParam(':kode', $kode, PDO::PARAM_STR);
			$edit->bindParam(':nomor', $nomor, PDO::PARAM_STR);
			$edit->bindParam(':tgl_tor', $tgl_tor, PDO::PARAM_STR);
			$edit->bindParam(':ket_tor', $ket_tor, PDO::PARAM_STR);
			$edit->execute();			
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Order Produk', 'Update', 'Nomor Faktur', '$catat', '$admin')");
			$hasil	= ($edit==true) ? "success" : "error";
			echo($hasil);
		break;
		case "delete":
			$kode	= $secu->injection($_POST['keycode']);
			$delete	= $conn->prepare("DELETE FROM transaksi_orderdetail WHERE id_tor=:kode");
			$delete->bindParam(':kode', $kode, PDO::PARAM_STR);
			$delete->execute();			
			//DELETE
			$delete	= $conn->prepare("DELETE FROM transaksi_order WHERE id_tor=:kode");
			$delete->bindParam(':kode', $kode, PDO::PARAM_STR);
			$delete->execute();			
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Order Produk', 'Delete', '', '$catat', '$admin')");
			$hasil	= ($delete==true) ? "success" : "error";
			echo($hasil);
		break;
		case "tutup":
			$kode	= $secu->injection($_POST['keycode']);
			$proses	= 'Close';
			$edit= $conn->prepare("UPDATE transaksi_order SET proses_tor=:proses, updated_at=:catat, updated_by=:admin WHERE id_tor=:kode");
			$edit->bindParam(':kode', $kode, PDO::PARAM_STR);
			$edit->bindParam(':proses', $proses, PDO::PARAM_STR);
			$edit->bindParam(':catat', $catat, PDO::PARAM_STR);
			$edit->bindParam(':admin', $admin, PDO::PARAM_STR);
			$edit->execute();			
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Order Produk', 'Update', 'Tutup Order Produk', '$catat', '$admin')");
			$hasil	= ($edit==true) ? "success" : "error";
			echo($hasil);
		break;
	}
	$conn	= $base->close();
	}
?>