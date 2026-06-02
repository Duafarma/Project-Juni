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
	$msgBugs = array();
	switch($act){
		case "input":
			$hasil 	= "Error";
			$id		= $_POST['id'] = 'TRF'.time();
			$kode	= $secu->injection(@$_POST['kode']);
			$type	= $secu->injection(@$_POST['transfer_apl_type']);
			$tgl	= $secu->injection(@$_POST['tanggal']);
			$from	= $secu->injection(@$_POST['transfer_apl_from']);
			$to		= $secu->injection(@$_POST['transfer_apl_to']);
			$ket	= $secu->injection(@$_POST['keterangan']);
			$status	= 'Process';
			$jum	= count(@$_POST['product']);
			$no		= 0;
			if ($type == 'OUT') {
				// proses transfer OUT
				if (count($msgBugs) == 0) {
					$target 	= $data->get_apl($to);
					$targetUrl 	= $target[0]['base_url_apl'];
					$targetKey  = $target[0]['key_apl'];
					$encrypt    = md5(md5($id . "#" . $kode) . "#" . $targetKey);
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $targetUrl . "/api/postProductTransfer.php?encrypt=" . $encrypt . "&act=" .$act);
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
						array_push($msgBugs, $hasil);
					} else {
						$json = json_decode($res);
						$hasil	= ucfirst($json->result);
						if ($hasil != 'Success') {
							array_push($msgBugs, $hasil);
						}
					}
				}
				// save transfer product detail
				if (count($msgBugs) == 0) {
					while($no<$jum){
						if (count($msgBugs) == 0) {
							$produk	= $secu->injection(@$_POST['product'][$no]);
							$jumlah	= str_replace('.', '', @$_POST['jumlah'][$no]);
							$idpsd = $secu->injection(@$_POST['idpsd'][$no]);
							// save
							if (count($msgBugs) == 0) {
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
							}
							$no++;
						} else {
							break;
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
							$riwayat = $conn->prepare($qRiwayat);
							$riwayat->execute();
						} catch (PDOException $th) {
							array_push($msgBugs, $e->getMessage());
						}
					}
				}
			}
			if ($type == 'IN') {
				// check produk exists or not
				while($no<$jum){
					if (count($msgBugs) == 0) {
						$produkExt			= $secu->injection($_POST['product'][$no]);
						$idpsdExt 			= $secu->injection($_POST['idpsd'][$no]);
						$produk 			=	 $idpsd = null;
						$jumlah				=	 str_replace('.', '', $_POST['jumlah'][$no]);
						$namaproduct 		= $secu->injection($_POST['namaproduct'][$no]);
						$bcode 				= $secu->injection($_POST['bcode'][$no]);
						$id_trd 			= $secu->injection($_POST['id_trd'][$no]);
                        $tgl_expired 		=	 $secu->injection($_POST['tgl_expired'][$no]);
                        $tgl_psd 			= $secu->injection($_POST['tgl_psd'][$no]);
                        $gudang 			= $secu->injection($_POST['gudang'][$no]);
						// check product
						$qSearch = "SELECT * 
									FROM produk
									WHERE
										nama_pro = '$namaproduct' AND
										minstok_pro > 0 AND
										status_pro = 'Active'
									LIMIT 1";
						try {
							$search	= $conn->prepare($qSearch);
							$search->execute();
							$dataTf  = $search->fetch(PDO::FETCH_ASSOC);
							if (!is_array($dataTf)) {
								array_push($msgBugs, "Produk ".$namaproduct." belum tersedia pada system penerima!");
							} else {
								$produk = $dataTf['id_pro'];
							}
						} catch (PDOException $e) {
							array_push($msgBugs, $e->getMessage());
						}
						// check product stok detail
						if (empty($msgBugs)) {
							$qSearch = "SELECT * 
										FROM produk_stokdetail
										WHERE
											id_pro = '$produk' AND
											no_bcode = '$bcode'
										ORDER BY sisa_psd ASC
										LIMIT 1";
							try {
								$search	= $conn->prepare($qSearch);
								$search->execute();
								$dataTf  = $search->fetch(PDO::FETCH_ASSOC);
								if (!is_array($dataTf)) {
									$qSave = "INSERT 
												INTO produk_stokdetail (
													id_trd,
													id_pro,
													no_bcode,
													tgl_expired,
													tgl_psd,
													masuk_psd,
													keluar_psd,
													sisa_psd,
													gudang,
													created_at, 
													created_by,
                                                    updated_at, 
                                                    updated_by)
												VALUES (
													:id_trd,
													:id_pro,
													:no_bcode,
													:tgl_expired,
													:tgl_psd,
													0,
													0,
													0,
													:gudang,
													:catat, 
													'System',
													:catat, 
													'System')";
									try {
										$save	= $conn->prepare($qSave);
										$save->bindParam(':id_trd', $id_trd, PDO::PARAM_STR);
										$save->bindParam(':id_pro', $produk, PDO::PARAM_STR);
										$save->bindParam(':no_bcode', $bcode, PDO::PARAM_STR);
										$save->bindParam(':tgl_expired', $tgl_expired, PDO::PARAM_STR);
										$save->bindParam(':tgl_psd', $tgl_psd, PDO::PARAM_STR);
										$save->bindParam(':gudang', $gudang, PDO::PARAM_STR);
										$save->bindParam(':catat', $catat, PDO::PARAM_STR);
										$save->execute();
										$idpsd = $conn->lastInsertId();
									} catch (PDOException $e) {
										array_push($msgBugs, $e->getMessage());
									}
								} else {
									$idpsd = $dataTf['id_psd'];
								}
							} catch (PDOException $e) {
								array_push($msgBugs, $e->getMessage());
							}
						}
						if (count($msgBugs) == 0) {
							$qSave = "INSERT
										INTO
										transaksi_transferstockdetail (
											id_ttr, 
											id_psd, 
											id_ext_psd, 
											id_pro, 
											id_ext_pro,
											jumlah_ttd, 
											created_at, 
											created_by)
										VALUES(
											:id, 
											:id_psd,
											:id_ext_psd, 
											:produk, 
											:id_ext_pro,
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
								$save->bindParam(':id_ext_psd', $idpsdExt, PDO::PARAM_STR);
								$save->bindParam(':id_ext_pro', $produkExt, PDO::PARAM_STR);
								$save->execute();
							} catch (PDOException $e) {
								array_push($msgBugs, $e->getMessage());
							}
						} else {
							break;
						}
					}
					$no++;
				}
				if (count($msgBugs) == 0) {
					// save transfer product
					$status	= 'Process';
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
							$riwayat = $conn->prepare($qRiwayat);
							$riwayat->execute();
						} catch (PDOException $e) {
							array_push($msgBugs, $e->getMessage());
						}
					}
				}
				if (count($msgBugs) == 0) {
					// proses transfer IN
					$source 	= $data->get_apl($from);
					$sourceUrl 	= $source[0]['base_url_apl'];
					$sourceKey  = $source[0]['key_apl'];
					$encrypt    = md5(md5($id . "#" . $kode) . "#" . $sourceKey);
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $sourceUrl . "/api/postProductTransfer.php?encrypt=" . $encrypt . "&act=" .$act);
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
						$status = 'Canceled';
						array_push($msgBugs, $hasil);
					} else {
						$json = json_decode($res);
						$hasil	= ucfirst($json->result);
						if ($hasil != 'Success') {
							$status = 'Canceled';
							array_push($msgBugs, $hasil);
						}
						if ($hasil == 'Success') {
							$status	= $hasil;
						}
					}
					if ($status == 'Canceled') {
						$ket    = implode(",", $msgBugs);
						$y 		= ob_implicit_flush('');
						// update transfer
						$qUpdate = "UPDATE
										transaksi_transferstock
									SET
										status_ttr = '$status',
										updated_at = '$catat',
										updated_by = 'System'
									WHERE
										id_ttr = '$id'";

						try {
							$update	= $conn->prepare($qUpdate);
							$update->execute();	
						} catch (PDOException $e) {
							array_push($msgBugs, $e->getMessage());
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
												'Update', 
												'$status.': '.$ket',
												'$catat',
												'System')";
							try {
								$riwayat = $conn->prepare($qRiwayat);
								$riwayat->execute();
							} catch (PDOException $e) {
								array_push($msgBugs, $e->getMessage());
							}
						}
					}
				}
			}
			$conn	= $base->close();
			// send response
			echo(json_encode(array("result" => $hasil)));
			break;
		case "approval":
			$hasil 		= "Error";
			$kode		= $secu->injection(@$_POST['kode']);
			$kodeExt	= $secu->injection(@$_POST['kode_ext']);
			$id			= $secu->injection(@$_POST['id']);
			$type		= $secu->injection(@$_POST['type']);
			$from		= $secu->injection(@$_POST['id_app_from']);
			$to			= $secu->injection(@$_POST['id_app_to']);
			$approval	= $secu->injection(@$_POST['approval']);
			$status 	= ($secu->injection(@$_POST['approval']) == "1") ? 'Approved' : 'Rejected';
			$selfApl	= $data->self_apl();
			// check transfer type
			if (($type == 'IN' && $from == $selfApl['id_apl']) || ($type == 'OUT' && $from != $selfApl['id_apl'])) {
				array_push($msgBugs, "Ada kesalahan pada proses transfer");
			}
			if (count($msgBugs) == 0) {
				$targetApl	= ($type == 'IN') ? $from : $to;
				$target 	= $data->get_apl($targetApl);
				$targetKey  = $target[0]['key_apl'];
				$targetUrl 	= $target[0]['base_url_apl'];
				$encrypt    = md5(md5($id . "#" . $kode) . "#" . $targetKey);
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $targetUrl . "/api/postProductTransfer.php?encrypt=" . $encrypt . "&act=" .$act);
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
					array_push($msgBugs, $hasil);
				} else {
					$json = json_decode($res);
					$hasil	= $json->result;
					if ($hasil != 'Success') {
						array_push($msgBugs, $hasil);
					}
					if ($hasil == 'Success') {
						// if approval is true
						if ($status == 'Approved') {
							// get transfer detail
							$qRead = "SELECT * FROM transaksi_transferstockdetail WHERE id_ttr=:id_ttr";
							try {
								$read	= $conn->prepare($qRead);
								$read->bindParam(':id_ttr', $id, PDO::PARAM_STR);
								$read->execute();
							} catch (PDOException $e) {
								array_push($msgBugs, $e->getMessage());
							}
							if (count($msgBugs) == 0) {
								$datas  = $read->fetchAll(PDO::FETCH_ASSOC);
								// save transfer product detail
								foreach ($datas as $key => $data) {
									if (count($msgBugs) == 0) {
										// update product detail stok
										$idpsd	= $data['id_psd'];
										$jumlah	= (int)$data['jumlah_ttd'];
										if ($type == 'IN') {
											$set = "masuk_psd = masuk_psd + $jumlah, sisa_psd = sisa_psd + $jumlah";
										} else {
											$set = "keluar_psd = keluar_psd + $jumlah, sisa_psd = sisa_psd - $jumlah";
										}
										$qUpdate = "UPDATE
														produk_stokdetail
													SET
														$set,
														updated_at =:catat,
														updated_by =:admin
													WHERE
														id_psd =:idpsd";
										try {
											$update	= $conn->prepare($qUpdate);
											$update->bindParam(':catat', $catat, PDO::PARAM_STR);
											$update->bindParam(':admin', $admin, PDO::PARAM_STR);
											$update->bindParam(':idpsd', $idpsd, PDO::PARAM_STR);
											$update->execute();	
										} catch (PDOException $e) {
											array_push($msgBugs, $e->getMessage());
										}
									} else {
										break;
									}
								}
							}
						}
						// update transfer
						if (count($msgBugs) == 0) {
							$qUpdate = "UPDATE 
											transaksi_transferstock
										SET
											status_ttr=:status,
											updated_at=:catat,
											updated_by=:admin
										WHERE
											id_ttr=:id";
							try {
								$update	= $conn->prepare($qUpdate);
								$update->bindParam(':status', $status, PDO::PARAM_STR);
								$update->bindParam(':catat', $catat, PDO::PARAM_STR);
								$update->bindParam(':admin', $admin, PDO::PARAM_STR);
								$update->bindParam(':id', $id, PDO::PARAM_STR);
								$update->execute();
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
												'Update',
												'$status', 
												'$catat',
												'$admin')";
							try {
								$riwayat = $conn->prepare($qRiwayat);
								$riwayat->bindParam(':status', $status, PDO::PARAM_STR);
								$riwayat->bindParam(':catat', $catat, PDO::PARAM_STR);
								$riwayat->bindParam(':admin', $admin, PDO::PARAM_STR);
								$riwayat->bindParam(':id', $id, PDO::PARAM_STR);
								$riwayat->execute();
							} catch (PDOException $e) {
								array_push($msgBugs, $e->getMessage());
							}
						}
						if (empty($msgBugs)) {
							// update notif transfer
							$qNotif = "UPDATE
											notifications
										SET status_notif = 'R'
										WHERE id_datanotif =:id AND
											status_notif = 'U'";
							try {
								$notif= $conn->prepare($qNotif);
								$notif->bindParam(':id', $id, PDO::PARAM_STR);
								$notif->execute();
							} catch (PDOException $e) {
								array_push($msgBugs, $e->getMessage());
							}
						}
					}
				}
			}
			// send response
			echo(json_encode(array("result" => $hasil)));
			break;
		case "delete":
			$kode	= $secu->injection(@$_POST['kode']);
			$id		= $secu->injection(@$_POST['id']);
			$status = 'Canceled';
			// check current
			$qRead  = "SELECT * FROM transaksi_transferstock WHERE id_ttr = '$id'";
			try {
				$read	= $conn->prepare($qRead);
				$read->execute();
			} catch (PDOException $e) {
				array_push($msgBugs, $e->getMessage());
			}
			// update transfer
			if (count($msgBugs) == 0) {
				$dataTf   = $read->fetch(PDO::FETCH_ASSOC);
				if (in_array($dataTf['status_ttr'],array('Process'))) {
					// cancel tf system target
					$targetApl	= ($dataTf['tipe_ttr'] == 'IN') ? $dataTf['id_app_from'] : $dataTf['id_app_to'];
					$target 	= $data->get_apl($targetApl);
					$targetKey  = $target[0]['key_apl'];
					$targetUrl 	= $target[0]['base_url_apl'];
					$encrypt    = md5(md5($id . "#" . $kode) . "#" . $targetKey);
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $targetUrl . "/api/postProductTransfer.php?encrypt=" . $encrypt . "&act=" .$act);
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
						array_push($msgBugs, $hasil);
					} else {
						$json = json_decode($res);
						$hasil	= $json->result;
						if ($hasil != 'Success') {
							array_push($msgBugs, $hasil);
						}
					}
					// update tf stok
					if (empty($msgBugs)) {
						$qUpdate = "UPDATE
										transaksi_transferstock
									SET
										status_ttr = '$status',
										updated_at = '$catat',
										updated_by = 'System'
									WHERE
										id_ttr = '$id'";
						try {
							$update	= $conn->prepare($qUpdate);
							$update->execute();	
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
											'Update', 
											'$status',
											'$catat',
											'System')";
						try {
							$riwayat = $conn->prepare($qRiwayat);
							$riwayat->execute();
						} catch (PDOException $e) {
							array_push($msgBugs, $e->getMessage());
						}
					}
				} else {
					array_push($msgBugs, "Action delete transfer stok sudah tidak dapat diproses");
				}
			}
			// send response
			if (empty($msgBugs)) {
				$hasil = "success";
			} else {
				$hasil = implode(", ",$msgBugs);
			}
			echo($hasil);
			break;
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
			$riwayat= $conn->prepare("INSERT INTO riwayat VALUES('', '$kode', 'Order Produk', 'Update', 'Tutup Order Produk', '$catat', '$admin')");
			$hasil	= ($edit==true) ? "success" : "error";
			echo($hasil);
		break;
	}
	$conn	= $base->close();
	}
?>
