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
	$menu	= $secu->injection(@$_POST['namamenu']);
	$valid	= $secu->validadmin($admin, $kunci);
	if($valid==false){
		$url	= 'signout';
	} else {
		$conn	= $base->open();
		switch($menu){
			case 'input':
				$id		        = '';
				$kode	        = "RO".time();
				$id_trs	        = $secu->injection($_POST['id_transfer']);
				$transer_tre	= $secu->injection($_POST['transfer_tre']);
				$status	        = $secu->injection($_POST['status']);
				// $stotal	        = str_replace('.', '', $_POST['pstotal']);
				// $ppn	= str_replace('.', '', $_POST['pppn']);
				// $gtotal	= str_replace('.', '', $_POST['pgtotal']);
				// $faktur	= $secu->injection($_POST['faktur']);
				// $tglfak	= $secu->injection($_POST['tglfaktur']);
				// $tgltrm	= $secu->injection($_POST['tglterima']);
				// $status	= 'Tagihan';
				// $terima	= 'Terima';
				$nol	= 0;
				//CEK DATA
				// $read	= $conn->prepare("SELECT A.id_sup, A.status_tor, A.proses_tor, C.top_sdi FROM transaksi_order AS A LEFT JOIN supplier AS B ON A.id_sup=B.id_sup LEFT JOIN supplier_diskon AS C ON B.id_sup=C.id_sup WHERE A.id_tor=:order");
				// $read->bindParam(':order', $order, PDO::PARAM_STR);
				// $read->execute();
				// $view	= $read->fetch(PDO::FETCH_ASSOC);
				while($no<$jum){
						$produk	= $secu->injection($_POST['product'][$no]);
						$bcode	= $secu->injection($_POST['batchcode_trd'][$no]);
						$tbcode	= $secu->injection($_POST['tbcode_trd'][$no]);
                        $gudang	= $secu->injection($_POST['gudang'][$no]);
						$jumlah	= str_replace('.', '', $_POST['jumlah_trd'][$no]);
						$jum	= count($_POST['product']);
						$no		= 0;
						if(!empty($jumlah)){
							$save	= $conn->prepare("INSERT INTO transfer_in_detail VALUES(:id, :kode, :produk, :bcode, :tbcode, :jumlah, :gudang, :catat, :admin, :catat, :admin)");
							$save->bindParam(':id', $id, PDO::PARAM_STR);
							$save->bindParam(':kode', $kode, PDO::PARAM_STR);
							$save->bindParam(':produk', $produk, PDO::PARAM_STR);
							$save->bindParam(':bcode', $bcode, PDO::PARAM_STR);
							$save->bindParam(':tbcode', $tbcode, PDO::PARAM_STR);
							$save->bindParam(':jumlah', $jumlah, PDO::PARAM_STR);
							$save->bindParam(':gudang', $gudang, PDO::PARAM_STR);
							// $save->bindParam(':total', $total, PDO::PARAM_STR);
							$save->bindParam(':catat', $catat, PDO::PARAM_STR);
							$save->bindParam(':admin', $admin, PDO::PARAM_STR);
							$save->execute();
						}		
					$no++;
					}
					//STOK
					$save	= $conn->prepare("INSERT INTO produk_stokdetail SELECT '', id_trid, id_pro, bcode_trd, tbcode_trd, jumlah_trd, 0, jumlah_trd, created_at, created_by, updated_at, updated_by FROM transaksi_receivedetail WHERE id_transfer=:kode");
					$save->bindParam(':kode', $kode, PDO::PARAM_STR);
					$save->execute();
					//RECEIVE ORDER
					$save	= $conn->prepare("INSERT INTO transfer_in VALUES(:kode, :faktur, :tglfak, :status, :catat, :admin, :catat, :admin)");
					$save->bindParam(':kode', $kode, PDO::PARAM_STR);
					$save->bindParam(':transfer_tre', $faktur, PDO::PARAM_STR);
					$save->bindParam(':status', $tglfak, PDO::PARAM_STR);
					// $save->bindParam(':limit', $limit, PDO::PARAM_STR);
					$save->bindParam(':status', $status, PDO::PARAM_STR);
					$save->bindParam(':catat', $catat, PDO::PARAM_STR);
					$save->bindParam(':admin', $admin, PDO::PARAM_STR);
					$save->execute();
					//EDIT
					// $edit	= $conn->prepare("UPDATE transaksi_order SET status_tor=:terima, updated_at=:catat, updated_by=:admin WHERE id_tor=:order");
					// $edit->bindParam(':order', $order, PDO::PARAM_STR);
					// $edit->bindParam(':terima', $terima, PDO::PARAM_STR);
					// $edit->bindParam(':catat', $catat, PDO::PARAM_STR);
					// $edit->bindParam(':admin', $admin, PDO::PARAM_STR);
					// $edit->execute();
					if($save==true){
						//RIWAYAT
						$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Penerimaan Barang', 'Create', '', '$catat', '$admin')");
						setcookie("info", "success", time() + 5, "/");
						setcookie("pesan", "Penerimaan barang berhasil ditambahkan...", time() + 5, "/");
					} else {
						setcookie("info", "danger", time() + 5, "/");
						setcookie("pesan", "Penerimaan barang gagal ditambahkan...", time() + 5, "/");
					} 

				$url	= 'tfstok';
			break;
			case "faktur":
				$uniq	= $secu->injection($_POST['keycode']);
				$kode	= base64_decode($uniq);
				$nomor	= $secu->injection($_POST['nomor']);
				$tglfak	= $secu->injection($_POST['tglfaktur']);
				$tgltem	= $secu->injection($_POST['tgltempo']);
				$tglter	= $secu->injection($_POST['tglterima']);
				//EDIT
				$edit	= $conn->prepare("UPDATE transaksi_receive SET fak_tre=:nomor, tglfak_tre=:tglfak, tgl_limit=:tgltem, tgl_tre=:tglter, updated_at=:catat, updated_by=:admin WHERE id_tre=:kode");
				$edit->bindParam(':kode', $kode, PDO::PARAM_STR);
				$edit->bindParam(':nomor', $nomor, PDO::PARAM_STR);
				$edit->bindParam(':tglfak', $tglfak, PDO::PARAM_STR);
				$edit->bindParam(':tgltem', $tgltem, PDO::PARAM_STR);
				$edit->bindParam(':tglter', $tglter, PDO::PARAM_STR);
				$edit->bindParam(':catat', $catat, PDO::PARAM_STR);
				$edit->bindParam(':admin', $admin, PDO::PARAM_STR);
				$edit->execute();			
				if($edit==true){
					//RIWAYAT
					$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Penerimaan Barang', 'Update', 'Faktur $nomor', '$catat', '$admin')");
					setcookie("info", "success", time() + 5, "/");
					setcookie("pesan", "Nomor faktur berhasil diupdate...", time() + 5, "/");
				} else {
					setcookie("info", "danger", time() + 5, "/");
					setcookie("pesan", "Nomor gagal berhasil diupdate...", time() + 5, "/");
				}
				$url	= 'rorder';
			break;
			case "inputitem":
				$id		= '';
				$uniq	= $secu->injection($_POST['keycode']);
				$kode	= base64_decode($uniq);
				$code	= $data->bcode('', 'id_trd', 'transaksi_receivedetail');
				$produk	= $secu->injection($_POST['produk']);
				$bcode	= $secu->injection($_POST['nobcode']);
				$tbcode	= $secu->injection($_POST['texpired']);
				$jumlah	= str_replace('.', '', $_POST['jumlah']);
				$harga	= str_replace('.', '', $_POST['harga']);
				$diskon	= $secu->injection($_POST['diskon']);
				$total	= str_replace('.', '', $_POST['total']);
				//SAVE
				$save	= $conn->prepare("INSERT INTO transaksi_receivedetail VALUES(:code, :kode, :produk, :bcode, :tbcode, :jumlah, :harga, :diskon, :total, :catat, :admin, :catat, :admin)");
				$save->bindParam(':code', $code, PDO::PARAM_STR);
				$save->bindParam(':kode', $kode, PDO::PARAM_STR);
				$save->bindParam(':produk', $produk, PDO::PARAM_STR);
				$save->bindParam(':bcode', $bcode, PDO::PARAM_STR);
				$save->bindParam(':tbcode', $tbcode, PDO::PARAM_STR);
				$save->bindParam(':jumlah', $jumlah, PDO::PARAM_STR);
				$save->bindParam(':harga', $harga, PDO::PARAM_STR);
				$save->bindParam(':diskon', $diskon, PDO::PARAM_STR);
				$save->bindParam(':total', $total, PDO::PARAM_STR);
				$save->bindParam(':catat', $catat, PDO::PARAM_STR);
				$save->bindParam(':admin', $admin, PDO::PARAM_STR);
				$save->execute();
				//STOK
				$save	= $conn->prepare("INSERT INTO produk_stokdetail SELECT '', A.id_trd, A.id_pro, A.bcode_trd, A.tbcode_trd, B.tgl_tre, A.jumlah_trd, 0, A.jumlah_trd, A.created_at, A.created_by, A.updated_at, A.updated_by FROM transaksi_receivedetail AS A LEFT JOIN transaksi_receive AS B ON A.id_tre=B.id_tre WHERE A.id_trd=:code");
				$save->bindParam(':code', $code, PDO::PARAM_STR);
				$save->execute();
				if($save==true){
					//RIWAYAT
					$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Penerimaan Barang', 'Update', '', '$catat', '$admin')");
					setcookie("info", "success", time() + 5, "/");
					setcookie("pesan", "Item penerimaan barang berhasil ditambahkan...", time() + 5, "/");
				} else {
					setcookie("info", "danger", time() + 5, "/");
					setcookie("pesan", "Item penerimaan barang gagal ditambahkan...", time() + 5, "/");
				}
				$url	= "rorder/v/$uniq";
			break;
			case "editdetail":
				$uniq	= $secu->injection($_POST['keycode']);
				$kode	= base64_decode($uniq);
				$bcode	= $secu->injection($_POST['nobcode']);
				$tglexp	= $secu->injection($_POST['tglexpired']);
				$masuk	= str_replace('.', '', $_POST['masuk']);
				$keluar	= str_replace('.', '', $_POST['keluar']);
				$sisa	= str_replace('.', '', $_POST['sisa']);
				//CEK DATA
				$read	= $conn->prepare("SELECT id_tre, id_pro, bcode_trd, tbcode_trd FROM transaksi_receivedetail WHERE id_trd=:kode");
				$read->bindParam(':kode', $kode, PDO::PARAM_STR);
				$read->execute();
				$view	= $read->fetch(PDO::FETCH_ASSOC);
				//EDIT
				$edit	= $conn->prepare("UPDATE transaksi_receivedetail SET bcode_trd=:bcode, tbcode_trd=:tglexp, jumlah_trd=:masuk, updated_at=:catat, updated_by=:admin WHERE id_trd=:kode");
				$edit->bindParam(':kode', $kode, PDO::PARAM_STR);
				$edit->bindParam(':masuk', $masuk, PDO::PARAM_INT);
				$edit->bindParam(':bcode', $bcode, PDO::PARAM_STR);
				$edit->bindParam(':tglexp', $tglexp, PDO::PARAM_STR);
				$edit->bindParam(':catat', $catat, PDO::PARAM_STR);
				$edit->bindParam(':admin', $admin, PDO::PARAM_STR);
				$edit->execute();
				//EDIT
				$edit	= $conn->prepare("UPDATE produk_stokdetail SET no_bcode=:bcode, tgl_expired=:tglexp, masuk_psd=:masuk, keluar_psd=:keluar, sisa_psd=:sisa, updated_at=:catat, updated_by=:admin WHERE id_trd=:kode");
				$edit->bindParam(':kode', $kode, PDO::PARAM_STR);
				$edit->bindParam(':bcode', $bcode, PDO::PARAM_STR);
				$edit->bindParam(':tglexp', $tglexp, PDO::PARAM_STR);
				$edit->bindParam(':masuk', $masuk, PDO::PARAM_INT);
				$edit->bindParam(':keluar', $keluar, PDO::PARAM_INT);
				$edit->bindParam(':sisa', $sisa, PDO::PARAM_INT);
				$edit->bindParam(':catat', $catat, PDO::PARAM_STR);
				$edit->bindParam(':admin', $admin, PDO::PARAM_STR);
				$edit->execute();
				if($edit==true){
					//RIWAYAT
					$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$view[id_tre]', 'Penerimaan Barang', 'Update', '', '$catat', '$admin')");
					setcookie("info", "success", time() + 5, "/");
					setcookie("pesan", "Item penerimaan barang berhasil ditambahkan...", time() + 5, "/");
				} else {
					setcookie("info", "danger", time() + 5, "/");
					setcookie("pesan", "Item penerimaan barang gagal ditambahkan...", time() + 5, "/");
				}
				$url	= "rorder/v/".base64_encode($view['id_tre']);
			break;
			case "delete":
				$uniq	= $secu->injection($_POST['keycode']);
				$kode	= base64_decode($uniq);
				$status	= 'Proses';
				$proses	= 'Open';
				//CEK DATA
				$read	= $conn->prepare("SELECT id_tor FROM transaksi_receive WHERE id_tre=:kode");
				$read->bindParam(':kode', $kode, PDO::PARAM_STR);
				$read->execute();
				$view	= $read->fetch(PDO::FETCH_ASSOC);
				//EDIT
				$edit	= $conn->prepare("UPDATE transaksi_order SET status_tor=:status, proses_tor=:proses, updated_at=:catat, updated_by=:admin WHERE id_tor=:kode");
				$edit->bindParam(':kode', $view['id_tor'], PDO::PARAM_STR);
				$edit->bindParam(':status', $status, PDO::PARAM_STR);
				$edit->bindParam(':proses', $proses, PDO::PARAM_STR);
				$edit->bindParam(':catat', $catat, PDO::PARAM_STR);
				$edit->bindParam(':admin', $admin, PDO::PARAM_STR);
				$edit->execute();			
				//DELETE
				$delete	= $conn->prepare("DELETE A, B, C FROM transaksi_receive AS A LEFT JOIN transaksi_receivedetail AS B ON A.id_tre=B.id_tre LEFT JOIN produk_stokdetail AS C ON B.id_trd=C.id_trd WHERE A.id_tre=:kode");
				$delete->bindParam(':kode', $kode, PDO::PARAM_STR);
				$delete->execute();
				if($delete==true){
					//RIWAYAT
					$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Penerimaan Barang', 'Delete', '', '$catat', '$admin')");
					setcookie("info", "success", time() + 5, "/");
					setcookie("pesan", "Penerimaan barang berhasil dihapus...", time() + 5, "/");
				} else {
					setcookie("info", "danger", time() + 5, "/");
					setcookie("pesan", "Penerimaan barang gagal dihapus...", time() + 5, "/");
				}
				$url	= 'rorder';
			break;
		}
		$conn	= $base->close();
	}
	echo($url);
?>