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
			$msgBugs = array();
			$hasil 	= "Error";
			$id		= $_POST['id'] = 'TTR'.time();
			$kode	= $secu->injection($_POST['kode']);
			$type	= $secu->injection($_POST['transfer_apl_type']);
			$tgl	= $secu->injection($_POST['tanggal']);
			$from	= $secu->injection($_POST['transfer_apl_from']);
			$to		= $secu->injection($_POST['transfer_apl_to']);
			$ket	= $secu->injection($_POST['keterangan']);
			$status	= 'Process';
			$jum	= count($_POST['product']);
			$no		= 0;
			// save transfer product detail
			while($no<$jum){
				if (count($msgBugs) == 0) {
					$produk	= $secu->injection($_POST['product'][$no]);
					$jumlah	= str_replace('.', '', $_POST['jumlah'][$no]);
					$idpsd = $secu->injection($_POST['idpsd'][$no]);
					$qSave = "INSERT
								INTO
								transaksi_transferstockdetail (
									id_ttr, 
									id_psd, 
									id_pro, 
									jumlah_ttd, 
									created_at, 
									created_by)
								VALUES(
									:id, 
									:id_psd,
									:produk, 
									:jumlah, 
									:catat, 
									:admin)";
					try {
						$save	= $conn->prepare($qSave);
						$save->bindParam(':id', $id, PDO::PARAM_STR);
						$save->bindParam(':produk', $produk, PDO::PARAM_STR);
						$save->bindParam(':jumlah', $jumlah, PDO::PARAM_INT);
						$save->bindParam(':catat', $catat, PDO::PARAM_STR);
						$save->bindParam(':admin', $admin, PDO::PARAM_STR);
						$save->bindParam(':id_psd', $idpsd, PDO::PARAM_STR);
						$save->execute();
					} catch (PDOException $e) {
						array_push($msgBugs, $e->getMessage());
					}
					$no++;
				}
			}
			// save transfer product
			if (count($msgBugs) == 0) {
				$qSave = "INSERT
							INTO
							transaksi_transferstock (
								id_ttr, 
								kode_ttr, 
								tipe_ttr, 
								id_app_from, 
								id_app_to, 
								tgl_ttr, 
								ket_ttr, 
								status_ttr, 
								created_at, 
								created_by)
							VALUES (
								:id, 
								:kode, 
								:type, 
								:id_app_from, 
								:id_app_to, 
								:tgl_ttr, 
								:ket, 
								:status, 
								:catat, 
								:admin)";
				try {
					$save	= $conn->prepare($qSave);
					$save->bindParam(':id', $id, PDO::PARAM_STR);
					$save->bindParam(':kode', $kode, PDO::PARAM_STR);
					$save->bindParam(':type', $type, PDO::PARAM_STR);
					$save->bindParam(':id_app_from', $from, PDO::PARAM_STR);
					$save->bindParam(':id_app_to', $to, PDO::PARAM_STR);
					$save->bindParam(':tgl_ttr', $tgl, PDO::PARAM_STR);
					$save->bindParam(':ket', $ket, PDO::PARAM_STR);
					$save->bindParam(':status', $status, PDO::PARAM_STR);
					$save->bindParam(':catat', $catat, PDO::PARAM_STR);
					$save->bindParam(':admin', $admin, PDO::PARAM_STR);
					$save->execute();
				} catch (PDOException $e) {
					array_push($msgBugs, $e->getMessage());
				}
			}
			// save riwayat
			if (count($msgBugs) == 0) {
				$qRiwayat = "INSERT
								INTO
								riwayat (
									kode_riwayat, 
									menu_riwayat, 
									status_riwayat, 
									ket_riwayat,
									created_at, 
									created_by)
								VALUES (
									'$id', 
									'Transfer Stok', 
									'Create',
									'$status', 
									'$catat',
									'$admin')";
				try {
					$riwayat = $conn->query($qRiwayat);
					$riwayat->execute();
				} catch (PDOException $th) {
					array_push($msgBugs, $e->getMessage());
				}
			}
			// proses transfer OUT
			if (count($msgBugs) == 0) {
				$target 	= $data->get_apl($to);
				$targetUrl 	= $target[0]['base_url_apl'];
				$targetKey  = $target[0]['key_apl'];
				$encrypt    = md5(md5($id . "#" . $kode) . "#" . $targetKey);
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $targetUrl . "/api/postProductTransfer.php?encrypt=" . $encrypt);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($_POST));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$res = curl_exec($ch);
				// curl handling error
				if (curl_errno($ch)) {
					$error_msg = curl_error($ch);
				}
				curl_close ($ch);
				if (isset($error_msg)) {
					$hasil = $error_msg;
				} else {
					$json = json_decode($res);
					$hasil	= ucfirst($json->result);
					$now = date('Y-m-d h:i:s');
					if ($hasil == 'Success') {
						// update produk stock detail
						$no		= 0;
						while($no<$jum){
							if (count($msgBugs) == 0) {
								$idpsd = $secu->injection($_POST['idpsd'][$no]);
								$jumlah	= (int)str_replace('.', '', $_POST['jumlah'][$no]);
								if ($type == 'IN') {
									$set = "masuk_psd = masuk_psd + $jumlah, sisa_psd = sisa_psd + $jumlah";
								} else {
									$set = "keluar_psd = keluar_psd + $jumlah, sisa_psd = sisa_psd - $jumlah";
								}
								$qUpdate = "UPDATE
												produk_stokdetail
											SET
												$set,
												updated_at = '$now',
												updated_by = '$admin'
											WHERE
												id_psd = '$idpsd'";
								try {
									$update	= $conn->prepare($qUpdate);
									$update->execute();	
								} catch (PDOException $th) {
									array_push($msgBugs, $e->getMessage());
								}
							}		
							$no++;
						}
					}
					// update transfer
					if (count($msgBugs) == 0) {
						$qUpdate = "UPDATE
										transaksi_transferstock
									SET
										status_ttr = '$hasil',
										updated_at = '$now',
										updated_by = '$admin'
									WHERE
										id_ttr = '$id'";
						try {
							$update	= $conn->prepare($qUpdate);
							$update->execute();	
						} catch (PDOException $th) {
							array_push($msgBugs, $e->getMessage());
						}
					}
					// save riwayat
					if (count($msgBugs) == 0) {
						$qRiwayat = "INSERT
										INTO
										riwayat (
											kode_riwayat, 
											menu_riwayat, 
											status_riwayat,
											ket_riwayat,
											created_at, 
											created_by)
										VALUES (
											'$id', 
											'Transfer Stok', 
											'Create', 
											'$hasil',
											'$catat',
											'$admin')";
						try {
							$riwayat = $conn->query($qRiwayat);
							$riwayat->execute();
						} catch (PDOException $th) {
							array_push($msgBugs, $e->getMessage());
						}
					}
				}
			}
			$conn	= $base->close();
			// send response
			echo(json_encode(array("result" => $hasil)));
		break;
		case "update":
			$kode	= $secu->injection($_POST['keycode']);
			$nomor	= $secu->injection($_POST['nomorpo']);
			$edit	= $conn->prepare("UPDATE transaksi_order SET kode_tor=:nomor WHERE id_tor=:kode");
			$edit->bindParam(':kode', $kode, PDO::PARAM_STR);
			$edit->bindParam(':nomor', $nomor, PDO::PARAM_STR);
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