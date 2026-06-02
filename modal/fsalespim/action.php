<?php
	session_start();
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
	$msgBugs = array();
	$secu->validadmin($admin, $kunci);
	if($secu->validadmin($admin, $kunci)==false){
		//header('location:'.$data->sistem('url_sis').'/signout');
		$url	= 'signout';
	} else {
		switch($menu){
			case 'faktur':
				$id		= 0;
				$uniq	= $secu->injection($_POST['keycode']);
				$code	= base64_decode($uniq);
				$outlet	= $secu->injection($_POST['outlet']);
				$invoice= $secu->injection($_POST['invoice']);
				$pecah	= explode("/", $invoice);
				$gabung	= 'B/SJ/'.$pecah[2].'/'.$data->romawi(date('m')).'/'.date('y');
				$gobong	= 'B/FKT/'.$pecah[2].'/'.$data->romawi(date('m')).'/'.date('y');
				$kode	= $invoice;
				$nofak	= $secu->injection($_POST['nomorfaktur']);
				$tglfak	= $secu->injection($_POST['tglfaktur']);
				$nomorpo= $secu->injection($_POST['nomorpo']);
				$tglpo	= $secu->injection($_POST['tglpo']);
				$tglsj	= $secu->injection($_POST['tglsales']);
				$jatuh	= $secu->injection($_POST['jatuhtempo']);
				$status	= 'Faktur';
				$ccp	= $secu->injection($_POST['ccp']);
				$cito	= $secu->injection($_POST['cito']);
				$hargapim	= $secu->injection($_POST['hargapim']);
				$processTitle	= 'Tambah Faktur '.$nofak;
				// refresh no invoice, faktur & checking no po
				try {
					$qRead = "SELECT * 
								FROM transaksi_faktur_pim
								WHERE
									po_tfk=:po_tfk OR
									kode_tfk=:kode_tfk
								LIMIT 1";
					$read	= $conn->prepare($qRead);
					$read->bindParam(':po_tfk', $nomorpo, PDO::PARAM_STR);
					$read->bindParam(':kode_tfk', $nofak, PDO::PARAM_STR);
					$read->execute();
					$dRead  = $read->fetch(PDO::FETCH_ASSOC);
                    if (is_array($dRead)) {
						array_push($msgBugs, "Nomor PO ".$nomorpo." atau Nomor Faktur ".$nofak." sudah pernah digunakan!");
					}
				} catch (PDOException $e) {
					array_push($msgBugs, $e->getMessage());
				}
				if (empty($msgBugs)) {
					$qMcek = "SELECT *
								FROM transaksi_faktur_pim
								WHERE id_tfk=:code";
					try {
						$mcek	= $conn->prepare($qMcek);
						$mcek->bindParam(':code', $code, PDO::PARAM_STR);
						$mcek->execute();
						$hcek	= $mcek->fetch(PDO::FETCH_ASSOC);
						if (is_array($hcek)) {
							array_push($msgBugs, "ID Faktur tidak dapat diproses!");
						}
					} catch (PDOException $e) {
						array_push($msgBugs, $e->getMessage());
					}
					if (empty($msgBugs)) {
						$qSave = "INSERT
									INTO
										transaksi_faktur_pim (
											id_tfk,
											id_tsl,
											id_out,
											sj_tfk,
											tglsj_tfk,
											po_tfk,
											tglpo_tfk,
											kode_tfk,
											tgl_tfk,
											tgl_limit,
											subtot_tfk,
											ppn_tfk,
											total_tfk,
											status_tfk,
											ccp,
											cito,
											hargapim,
											created_at,
											created_by,
											updated_at,
											updated_by
											)
									VALUES(
										:code, 
										:id, 
										:outlet, 
										:kode, 
										:tglsj, 
										:nomorpo, 
										:tglpo, 
										:nofak, 
										:tglfak, 
										:jatuh,
										:id, 
										:id, 
										:id, 
										:status,
										:ccp,
										:cito,
										:hargapim,
										:catat, 
										:admin, 
										:catat, 
										:admin)";
						try {
							$save	= $conn->prepare($qSave);
							$save->bindParam(':code', $code, PDO::PARAM_STR);
							$save->bindParam(':id', $id, PDO::PARAM_STR);
							$save->bindParam(':outlet', $outlet, PDO::PARAM_STR);
							$save->bindParam(':kode', $kode, PDO::PARAM_STR);
							$save->bindParam(':tglsj', $tglsj, PDO::PARAM_STR);
							$save->bindParam(':nomorpo', $nomorpo, PDO::PARAM_STR);
							$save->bindParam(':tglpo', $tglpo, PDO::PARAM_STR);
							$save->bindParam(':nofak', $nofak, PDO::PARAM_STR);
							$save->bindParam(':tglfak', $tglfak, PDO::PARAM_STR);
							$save->bindParam(':jatuh', $jatuh, PDO::PARAM_STR);
							$save->bindParam(':stotal', $stotal, PDO::PARAM_STR);
							$save->bindParam(':ppn', $ppn, PDO::PARAM_STR);
							$save->bindParam(':gtotal', $gtotal, PDO::PARAM_STR);
							$save->bindParam(':status', $status, PDO::PARAM_STR);
							$save->bindParam(':ccp', $ccp, PDO::PARAM_STR);
							$save->bindParam(':cito', $cito, PDO::PARAM_STR);
							$save->bindParam(':hargapim', $hargapim, PDO::PARAM_STR);
							$save->bindParam(':catat', $catat, PDO::PARAM_STR);
							$save->bindParam(':admin', $admin, PDO::PARAM_STR);
							$save->execute();
							// Cek apakah data sudah ada di database
						$check = $conn->prepare("SELECT id_jhp FROM jenis_hargapim WHERE id_out = :id_out");
						$check->bindParam(':id_out', $outlet, PDO::PARAM_STR);  // Perbaiki bind parameter
						$check->execute();

						if ($check->rowCount() > 0) {
							// Jika data sudah ada, lakukan UPDATE
							$update = $conn->prepare("UPDATE jenis_hargapim SET hargapim = :hargapim, updated_at = :catat, updated_by = :admin WHERE id_out = :outlet");
							$update->bindParam(':hargapim', $hargapim, PDO::PARAM_STR);
							$update->bindParam(':outlet', $outlet, PDO::PARAM_STR);
							$update->bindParam(':catat', $catat, PDO::PARAM_STR);
							$update->bindParam(':admin', $admin, PDO::PARAM_STR);

							$update->execute();
						} else {
							// Jika data belum ada, lakukan INSERT
							$insert = $conn->prepare("INSERT INTO jenis_hargapim (
										id_out, 
										hargapim,
										created_at,
										created_by,
										updated_at,
										updated_by
										) VALUES 
										(:outlet, 
										:hargapim,
										:catat, 
										:admin, 
										:catat, 
										:admin
										)");
							$insert->bindParam(':outlet', $outlet, PDO::PARAM_STR);
							$insert->bindParam(':hargapim', $hargapim, PDO::PARAM_STR);
							$insert->bindParam(':catat', $catat, PDO::PARAM_STR);
							$insert->bindParam(':admin', $admin, PDO::PARAM_STR);
							
							$insert->execute();
						}
						} catch (PDOException $e) {
							array_push($msgBugs, $e->getMessage());
						}
						// riwayat
						if (empty($msgBugs)) {
							$qRiwayat = "INSERT
											INTO riwayat (
												kode_riwayat, 
												menu_riwayat, 
												status_riwayat, 
												ket_riwayat, 
												created_at, 
												created_by)
											VALUES(
												'$code', 
												'Faktur Penjualan PIM', 
												'Create', 
												'', 
												'$catat', 
												'$admin')";
							try {
								$riwayat= $conn->query($qRiwayat);
							} catch (PDOException $e) {
								array_push($msgBugs, $e->getMessage());
							}
						}
						if (empty($msgBugs)) {
							$url	= "itemsalespim/$uniq";
						}
					}
				}
			break;
			case 'items':
				$uniq	= $secu->injection($_POST['keycode']);
				$code	= base64_decode($uniq);
				$stotal	= str_replace('.', '', $_POST['pstotal']);
				$gtotal	= str_replace('.', '', $_POST['pgtotal']);
				$ppn	= str_replace('.', '', $_POST['pppn']);
				$nofak	= $secu->injection($_POST['nomorfaktur']);
				$status	= 'Tagihan';
				// HITUNG status_limit DI SERVER new platform
				$status_limit = '';
				try {
					// Cek transaksi_faktur_pim dulu (PIM), fallback ke transaksi_faktur (Cendo)
					$rowLimit = null;
					$q1 = $conn->prepare("SELECT A.id_out FROM transaksi_faktur_pim A WHERE A.id_tfk = :code LIMIT 1");
					$q1->bindValue(':code', $code, PDO::PARAM_STR);
					$q1->execute();
					$r1 = $q1->fetch(PDO::FETCH_ASSOC);
					if ($r1 && !empty($r1['id_out'])) {
						$qOut = $conn->prepare("SELECT platform AS limit_outlet, status_pembayaran FROM outlet WHERE id_out = :id_out LIMIT 1");
						$qOut->bindValue(':id_out', $r1['id_out'], PDO::PARAM_STR);
						$qOut->execute();
						$rowLimit = $qOut->fetch(PDO::FETCH_ASSOC);
					} else {
						$q2 = $conn->prepare("SELECT A.id_out FROM transaksi_faktur A WHERE A.id_tfk = :code LIMIT 1");
						$q2->bindValue(':code', $code, PDO::PARAM_STR);
						$q2->execute();
						$r2 = $q2->fetch(PDO::FETCH_ASSOC);
						if ($r2 && !empty($r2['id_out'])) {
							$qOut = $conn->prepare("SELECT platform AS limit_outlet, status_pembayaran FROM outlet WHERE id_out = :id_out LIMIT 1");
							$qOut->bindValue(':id_out', $r2['id_out'], PDO::PARAM_STR);
							$qOut->execute();
							$rowLimit = $qOut->fetch(PDO::FETCH_ASSOC);
						}
					}
					$limitOutlet = isset($rowLimit['limit_outlet']) ? (int) preg_replace('/[^\d]/','',$rowLimit['limit_outlet']) : 0;
					$statusPembayaran = isset($rowLimit['status_pembayaran']) ? strtolower(trim($rowLimit['status_pembayaran'])) : '';
					// Jika status_pembayaran merah => override jadi 'merah'
					if ($statusPembayaran === 'merah') {
						$status_limit = 'merah';
					} else if ($statusPembayaran === 'orens') {
						$status_limit = 'orange';
					} else if ($statusPembayaran === 'kunung' || $statusPembayaran === 'kuning') {
						$status_limit = 'kuning';
					} else if ($limitOutlet > 0 && (int)$stotal > $limitOutlet) {
						$status_limit = 'limit';
					}
				} catch (PDOException $e) {
					// kalau gagal baca limit, jangan blok proses simpan
					// (opsional) array_push($msgBugs, $e->getMessage());
				}
				//end limit
				$processTitle	= 'Tambah Item Faktur '.$nofak;
				$jum	= count($_POST['kodestok']);
				$no		= 0;
				while($no<$jum){
					if (empty($msgBugs)) {
						$kodestok= $secu->injection($_POST['kodestok'][$no]);
						$produk	= $secu->injection($_POST['product'][$no]);
						$jumlah	= str_replace('.', '', $_POST['jumlah'][$no]);
						$harga	= str_replace('.', '', $_POST['harga'][$no]);
						$diskon	= $secu->injection($_POST['diskon'][$no]);
						$total	= str_replace('.', '', $_POST['total'][$no]);
						//INPUT
						$qSave = "INSERT 
									INTO transaksi_fakturdetail_pim
									VALUES (:id, 
										:code, 
										:kodestok, 
										:pro, 
										:jumlah, 
										:harga, 
										:diskon, 
										:total, 
										:catat, 
										:admin, 
										:catat, 
										:admin)";
						try {
							$save	= $conn->prepare($qSave);
							$save->bindParam(':id', $id, PDO::PARAM_STR);
							$save->bindParam(':code', $code, PDO::PARAM_STR);
							$save->bindParam(':kodestok', $kodestok, PDO::PARAM_STR);
							$save->bindParam(':pro', $produk, PDO::PARAM_STR);
							$save->bindParam(':jumlah', $jumlah, PDO::PARAM_STR);
							$save->bindParam(':harga', $harga, PDO::PARAM_STR);
							$save->bindParam(':diskon', $diskon, PDO::PARAM_STR);
							$save->bindParam(':total', $total, PDO::PARAM_STR);
							$save->bindParam(':catat', $catat, PDO::PARAM_STR);
							$save->bindParam(':admin', $admin, PDO::PARAM_STR);
							$save->execute();
						} catch (PDOException $e) {
							array_push($msgBugs, $e->getMessage());
						}
						//EDIT
						if (empty($msgBugs)) {
							$qEdit = "UPDATE 
										produk_stokdetail 
										SET 
											keluar_psd = keluar_psd+:jumlah, 
											sisa_psd = sisa_psd-:jumlah 
										WHERE 
											id_psd =:kodestok";
							try {
								$edit	= $conn->prepare($qEdit);
								$edit->bindParam(':kodestok', $kodestok, PDO::PARAM_STR);
								$edit->bindParam(':jumlah', $jumlah, PDO::PARAM_INT);
								$edit->execute();
							} catch (PDOException $e) {
								array_push($msgBugs, $e->getMessage());
							}
						}
					}
					$no++;
				}
				//EDIT
				if (empty($msgBugs)) {
					$qEdit = "UPDATE 
									transaksi_faktur_pim
								SET 
									subtot_tfk=:stotal, 
									ppn_tfk=:ppn, 
									total_tfk=:gtotal, 
									status_tfk=:status,
									status_limit=:status_limit, 
									updated_at=:catat, 
									updated_by=:admin
								WHERE id_tfk=:code";
					try {
						$edit	= $conn->prepare($qEdit);
						$edit->bindParam(':code', $code, PDO::PARAM_STR);
						$edit->bindParam(':stotal', $stotal, PDO::PARAM_STR);
						$edit->bindParam(':ppn', $ppn, PDO::PARAM_STR);
						$edit->bindParam(':gtotal', $gtotal, PDO::PARAM_STR);
						$edit->bindParam(':status', $status, PDO::PARAM_STR);
						$edit->bindParam(':status_limit', $status_limit, PDO::PARAM_STR);
						$edit->bindParam(':catat', $catat, PDO::PARAM_STR);
						$edit->bindParam(':admin', $admin, PDO::PARAM_STR);
						$edit->execute();
					} catch (PDOException $e) {
						array_push($msgBugs, $e->getMessage());
					}
				}
				$url	= 'fsalespim';
			break;
			case 'update':
				$kode	= $secu->injection($_POST['keycode']);
				$nofak	= $secu->injection($_POST['nomorfaktur']);
				$tglfak	= $secu->injection($_POST['tglfak']);
				$nosj	= $secu->injection($_POST['nomorsj']);
				$tglsj	= $secu->injection($_POST['tglsj']);
				$nopo	= $secu->injection($_POST['nomorpo']);
				$tglpo	= $secu->injection($_POST['tglpo']);
				$jatuh	= $secu->injection($_POST['jatuhtempo']);
				$processTitle	= 'Update Faktur  '.$nofak;
				try {
					$edit	= $conn->prepare("UPDATE transaksi_faktur_pim SET kode_tfk=:nofak, tgl_tfk=:tglfak, sj_tfk=:nosj, tglsj_tfk=:tglsj, po_tfk=:nopo, tglpo_tfk=:tglpo, tgl_limit=:jatuh, updated_at=:catat, updated_by=:admin WHERE id_tfk=:kode");
					$edit->bindParam(':kode', $kode, PDO::PARAM_STR);
					$edit->bindParam(':nofak', $nofak, PDO::PARAM_STR);
					$edit->bindParam(':tglfak', $tglfak, PDO::PARAM_STR);
					$edit->bindParam(':nosj', $nosj, PDO::PARAM_STR);
					$edit->bindParam(':tglsj', $tglsj, PDO::PARAM_STR);
					$edit->bindParam(':nopo', $nopo, PDO::PARAM_STR);
					$edit->bindParam(':tglpo', $tglpo, PDO::PARAM_STR);
					$edit->bindParam(':jatuh', $jatuh, PDO::PARAM_STR);
					$edit->bindParam(':catat', $catat, PDO::PARAM_STR);
					$edit->bindParam(':admin', $admin, PDO::PARAM_STR);
					$edit->execute();
				} catch (PDOException $e) {
					array_push($msgBugs, $e->getMessage());
				}
				//RIWAYAT
				if (empty($msgBugs)) {
					try {
						$riwayat = $conn->query("INSERT INTO riwayat (kode_riwayat, menu_riwayat, status_riwayat, ket_riwayat, created_at, created_by) VALUES('$kode', 'Faktur Penjualan', 'Update', '', '$catat', '$admin')");
					} catch (PDOException $e) {
						array_push($msgBugs, $e->getMessage());
					}
				}
				$url	= "fsales";
			break;
			case 'delete':
				$kode	= $secu->injection($_POST['keycode']);
				$nofak	= $secu->injection($_POST['nomorfaktur']);
				$processTitle	= 'Delete Faktur '.$nofak;
				try {
					$master	= $conn->prepare("SELECT id_psd, jumlah_tfd FROM transaksi_fakturdetail_pim WHERE id_tfk=:kode");
					$master->bindParam(':kode', $kode, PDO::PARAM_STR);
					$master->execute();
				} catch (PDOException $e) {
					array_push($msgBugs, $e->getMessage());
				}
				if (empty($msgBugs)) {
					while($hasil = $master->fetch(PDO::FETCH_ASSOC)){
						if (empty($msgBugs)) {
							try {
								$edit	= $conn->prepare("UPDATE produk_stokdetail SET keluar_psd=keluar_psd-:jumlah, sisa_psd=sisa_psd+:jumlah WHERE id_psd=:kode");
								$edit->bindParam(':jumlah', $hasil['jumlah_tfd'], PDO::PARAM_INT);
								$edit->bindParam(':kode', $hasil['id_psd'], PDO::PARAM_STR);
								$edit->execute();
							} catch (PDOException $e) {
								array_push($msgBugs, $e->getMessage());
							}
						}
					}
				}
				/*
				$delete	= $conn->prepare("DELETE FROM pembayaran_faktur WHERE id_tfk=:kode");
				$delete->bindParam(':kode', $kode, PDO::PARAM_STR);
				$delete->execute();
				$delete	= $conn->prepare("DELETE FROM transaksi_fakturdetail_c WHERE id_tfk=:kode");
				$delete->bindParam(':kode', $kode, PDO::PARAM_STR);
				$delete->execute();
				$delete	= $conn->prepare("DELETE FROM transaksi_faktur_c WHERE id_tfk=:kode");
				$delete->bindParam(':kode', $kode, PDO::PARAM_STR);
				$delete->execute();
				*/
				if (empty($msgBugs)) {
					try {
						//$remove	= $conn->prepare("DELETE A, B, C FROM transaksi_faktur_pim AS A LEFT JOIN transaksi_fakturdetail_pim AS B ON A.id_tfk=B.id_tfk LEFT JOIN pembayaran_faktur AS C ON A.id_tfk=C.id_tfk WHERE A.id_tfk=:kode");
						$remove	= $conn->prepare("DELETE A, B FROM transaksi_faktur_pim AS A LEFT JOIN transaksi_fakturdetail_pim AS B ON A.id_tfk=B.id_tfk WHERE A.id_tfk=:kode");
						$remove->bindParam(':kode', $kode, PDO::PARAM_STR);
						$remove->execute();
					} catch (PDOException $e) {
						array_push($msgBugs, $e->getMessage());
					}
				}
				// RIWAYAT
				if (empty($msgBugs)) {
					try {
						$riwayat= $conn->query("INSERT INTO riwayat (kode_riwayat, menu_riwayat, status_riwayat, ket_riwayat, created_at, created_by) VALUES('$kode', 'Faktur Penjualan', 'Delete', '', '$catat', '$admin')");
					} catch (PDOException $e) {
						array_push($msgBugs, $e->getMessage());
					}
				}
				$url	= "fsalespim";
			break;
		case 'edit_item':
				// Proteksi double processing (mirip fsales)
				$uniq = $secu->injection($_POST['keycode']);
				$code = base64_decode($uniq);
				$lockKey = 'edit_item_pim_' . md5($code);

				if (isset($_SESSION[$lockKey]) && $_SESSION[$lockKey] + 5 > time()) {
					array_push($msgBugs, "Proses edit sedang berlangsung, harap tunggu sebentar");
					break;
				}
				$_SESSION[$lockKey] = time();

				$stotal	= str_replace('.', '', $_POST['pstotal']);
				$gtotal	= str_replace('.', '', $_POST['pgtotal']);
				$ppn	= str_replace('.', '', $_POST['pppn']);
				$nofak	= $secu->injection($_POST['nomorfaktur']);
				$keterangan_revisi = $secu->injection($_POST['keterangan_revisi']);
				$status	= 'Revisi';
				$processTitle = 'Edit Item Faktur '.$nofak;

				if (empty(trim($keterangan_revisi))) {
					array_push($msgBugs, "Keterangan revisi wajib diisi!");
				}

				// HITUNG status_limit (pakai logika yang sama dengan case items di fsalespim)
				$status_limit = '';
				if (empty($msgBugs)) {
					try {
						$rowLimit = null;

						$q1 = $conn->prepare("SELECT A.id_out FROM transaksi_faktur_pim A WHERE A.id_tfk = :code LIMIT 1");
						$q1->bindValue(':code', $code, PDO::PARAM_STR);
						$q1->execute();
						$r1 = $q1->fetch(PDO::FETCH_ASSOC);

						if ($r1 && !empty($r1['id_out'])) {
							$qOut = $conn->prepare("SELECT platform AS limit_outlet, status_pembayaran FROM outlet WHERE id_out = :id_out LIMIT 1");
							$qOut->bindValue(':id_out', $r1['id_out'], PDO::PARAM_STR);
							$qOut->execute();
							$rowLimit = $qOut->fetch(PDO::FETCH_ASSOC);
						}

						$limitOutlet = isset($rowLimit['limit_outlet']) ? (int) preg_replace('/[^\d]/','',$rowLimit['limit_outlet']) : 0;
						$statusPembayaran = isset($rowLimit['status_pembayaran']) ? strtolower(trim($rowLimit['status_pembayaran'])) : '';
						
    					// Jika status_pembayaran merah => override jadi 'merah'
    					if ($statusPembayaran === 'merah') {
    						$status_limit = 'merah';
    					} elseif ($statusPembayaran === 'orens') {
    						$status_limit = 'orange';
    					} else if ($limitOutlet > 0 && (int)$stotal > $limitOutlet) {
    						$status_limit = 'limit';
    					}
					} catch (PDOException $e) {
						// gagal baca limit => jangan blok proses edit
					}
				}

				// Ambil data lama (jumlah per id_psd) untuk hitung selisih stok
				$dataLama = array();
				if (empty($msgBugs)) {
					try {
						$master = $conn->prepare("SELECT id_psd, jumlah_tfd FROM transaksi_fakturdetail_pim WHERE id_tfk=:kode");
						$master->bindParam(':kode', $code, PDO::PARAM_STR);
						$master->execute();
						while($hasil = $master->fetch(PDO::FETCH_ASSOC)){
							$dataLama[$hasil['id_psd']] = (int)$hasil['jumlah_tfd'];
						}
					} catch (PDOException $e) {
						array_push($msgBugs, $e->getMessage());
					}
				}

				// Backup ke tabel junk (kalau tabel ada). Jika gagal, tidak memblokir proses edit.
				if (empty($msgBugs)) {
					try {
						$backupFaktur = $conn->prepare("
							INSERT INTO transaksi_faktur_junk (
								id_tfk_junk, id_tfk_original, sj_tfk, id_out, kode_tfk, tgl_tfk,
								po_tfk, tglpo_tfk, subtot_tfk, ppn_tfk, total_tfk, status_tfk,
								keterangan_revisi, backup_reason, backup_by, backup_at
							)
							SELECT
								NULL, id_tfk, sj_tfk, id_out, kode_tfk, tgl_tfk,
								po_tfk, tglpo_tfk, subtot_tfk, ppn_tfk, total_tfk, status_tfk,
								:keterangan_revisi, 'Before Edit Item (PIM)', :admin, :catat
							FROM transaksi_faktur_pim
							WHERE id_tfk = :code
						");
						$backupFaktur->bindParam(':code', $code, PDO::PARAM_STR);
						$backupFaktur->bindParam(':keterangan_revisi', $keterangan_revisi, PDO::PARAM_STR);
						$backupFaktur->bindParam(':admin', $admin, PDO::PARAM_STR);
						$backupFaktur->bindParam(':catat', $catat, PDO::PARAM_STR);
						$backupFaktur->execute();

						$junkFakturId = $conn->lastInsertId();

						if ($junkFakturId) {
							$backupDetail = $conn->prepare("
								INSERT INTO transaksi_fakturdetail_junk (
									id_tfd_junk, id_tfk_junk, id_tfd_original, id_tfk_original,
									id_psd, id_pro, jumlah_tfd, harga_tfd, diskon_tfd, total_tfd,
									backup_reason, backup_by, backup_at
								)
								SELECT
									NULL, :junk_id, id_tfd, id_tfk,
									id_psd, id_pro, jumlah_tfd, harga_tfd, diskon_tfd, total_tfd,
									'Before Edit Item (PIM)', :admin, :catat
								FROM transaksi_fakturdetail_pim
								WHERE id_tfk = :code
							");
							$backupDetail->bindParam(':junk_id', $junkFakturId, PDO::PARAM_INT);
							$backupDetail->bindParam(':code', $code, PDO::PARAM_STR);
							$backupDetail->bindParam(':admin', $admin, PDO::PARAM_STR);
							$backupDetail->bindParam(':catat', $catat, PDO::PARAM_STR);
							$backupDetail->execute();
						}
					} catch (PDOException $e) {
						// tidak memblokir proses
					}
				}

				// Hapus detail lama
				if (empty($msgBugs)) {
					try {
						$delete = $conn->prepare("DELETE FROM transaksi_fakturdetail_pim WHERE id_tfk=:kode");
						$delete->bindParam(':kode', $code, PDO::PARAM_STR);
						$delete->execute();
					} catch (PDOException $e) {
						array_push($msgBugs, $e->getMessage());
					}
				}

				// Insert detail baru + update stok berdasarkan selisih
				if (empty($msgBugs)) {
					if (!isset($_POST['kodestok']) || !is_array($_POST['kodestok']) || count($_POST['kodestok']) === 0) {
						array_push($msgBugs, "Item tidak ditemukan. Silakan muat/tambahkan item terlebih dahulu.");
					}
				}

				if (empty($msgBugs)) {
					$jum = count($_POST['kodestok']);
					for ($no = 0; $no < $jum; $no++) {
						$kodestok= $secu->injection($_POST['kodestok'][$no]);
						$produk	= $secu->injection($_POST['product'][$no]);
						$jumlah	= (int) str_replace('.', '', $_POST['jumlah'][$no]);
						$harga	= str_replace('.', '', $_POST['harga'][$no]);
						$diskon	= $secu->injection($_POST['diskon'][$no]);
						$total	= str_replace('.', '', $_POST['total'][$no]);

						// INSERT detail baru (pakai kolom eksplisit)
						$qSave = "INSERT INTO transaksi_fakturdetail_pim (
									id_tfd, id_tfk, id_psd, id_pro,
									jumlah_tfd, harga_tfd, diskon_tfd, total_tfd,
									created_at, created_by, updated_at, updated_by
								) VALUES (
									NULL, :code, :kodestok, :pro,
									:jumlah, :harga, :diskon, :total,
									:catat, :admin, :catat, :admin
								)";
						try {
							$save = $conn->prepare($qSave);
							$save->bindParam(':code', $code, PDO::PARAM_STR);
							$save->bindParam(':kodestok', $kodestok, PDO::PARAM_STR);
							$save->bindParam(':pro', $produk, PDO::PARAM_STR);
							$save->bindParam(':jumlah', $jumlah, PDO::PARAM_INT);
							$save->bindParam(':harga', $harga, PDO::PARAM_STR);
							$save->bindParam(':diskon', $diskon, PDO::PARAM_STR);
							$save->bindParam(':total', $total, PDO::PARAM_STR);
							$save->bindParam(':catat', $catat, PDO::PARAM_STR);
							$save->bindParam(':admin', $admin, PDO::PARAM_STR);
							$save->execute();
						} catch (PDOException $e) {
							array_push($msgBugs, $e->getMessage());
							break;
						}

						// Update stok (mirip fsales: adjust sisa_psd berdasarkan selisih)
						if (empty($msgBugs)) {
							$jumlahLama = isset($dataLama[$kodestok]) ? (int)$dataLama[$kodestok] : 0;
							$selisih = $jumlah - $jumlahLama; // (+) tambah pakai stok, (-) kurangi pakai stok

							if ($selisih != 0) {
								try {
									$qEdit = "UPDATE produk_stokdetail
											  SET sisa_psd = sisa_psd - :selisih
											  WHERE id_psd = :kodestok";
									$edit = $conn->prepare($qEdit);
									$edit->bindParam(':kodestok', $kodestok, PDO::PARAM_STR);
									$edit->bindParam(':selisih', $selisih, PDO::PARAM_INT);
									$edit->execute();
								} catch (PDOException $e) {
									array_push($msgBugs, $e->getMessage());
									break;
								}
							}

							unset($dataLama[$kodestok]); // sudah diproses
						}
					}
				}

				// Kembalikan stok untuk item yang dihapus (yang tersisa di $dataLama)
				if (empty($msgBugs)) {
					foreach($dataLama as $kodestok => $jumlahLama) {
						try {
							$qBack = "UPDATE produk_stokdetail
									  SET sisa_psd = sisa_psd + :jumlah
									  WHERE id_psd = :kodestok";
							$back = $conn->prepare($qBack);
							$back->bindParam(':kodestok', $kodestok, PDO::PARAM_STR);
							$back->bindParam(':jumlah', $jumlahLama, PDO::PARAM_INT);
							$back->execute();
						} catch (PDOException $e) {
							array_push($msgBugs, $e->getMessage());
							break;
						}
					}
				}

				// Update header faktur
				if (empty($msgBugs)) {
					try {
						$qEditHeader = "UPDATE transaksi_faktur_pim
										SET subtot_tfk=:stotal,
											ppn_tfk=:ppn,
											total_tfk=:gtotal,
											status_tfk=:status,
											status_limit=:status_limit,
											updated_at=:catat,
											updated_by=:admin
										WHERE id_tfk=:code";
						$editH = $conn->prepare($qEditHeader);
						$editH->bindParam(':code', $code, PDO::PARAM_STR);
						$editH->bindParam(':stotal', $stotal, PDO::PARAM_STR);
						$editH->bindParam(':ppn', $ppn, PDO::PARAM_STR);
						$editH->bindParam(':gtotal', $gtotal, PDO::PARAM_STR);
						$editH->bindParam(':status', $status, PDO::PARAM_STR);
						$editH->bindParam(':status_limit', $status_limit, PDO::PARAM_STR);
						$editH->bindParam(':catat', $catat, PDO::PARAM_STR);
						$editH->bindParam(':admin', $admin, PDO::PARAM_STR);
						$editH->execute();
					} catch (PDOException $e) {
						array_push($msgBugs, $e->getMessage());
					}
				}

				// Riwayat (simpan keterangan revisi)
				if (empty($msgBugs)) {
					try {
						$riw = $conn->prepare("INSERT INTO riwayat
							(kode_riwayat, menu_riwayat, status_riwayat, ket_riwayat, created_at, created_by)
							VALUES
							(:kode, 'Faktur Penjualan PIM', 'Edit Item', :ket, :catat, :admin)");
						$riw->bindParam(':kode', $code, PDO::PARAM_STR);
						$riw->bindParam(':ket', $keterangan_revisi, PDO::PARAM_STR);
						$riw->bindParam(':catat', $catat, PDO::PARAM_STR);
						$riw->bindParam(':admin', $admin, PDO::PARAM_STR);
						$riw->execute();
					} catch (PDOException $e) {
						array_push($msgBugs, $e->getMessage());
					}
				}

				// Hapus lock
				unset($_SESSION[$lockKey]);

				$url = 'fsalespim';
			break;
		}
	}
	if (!empty($msgBugs)) {
		$res = array(
			"status" => "Error",
			"message" => implode(", ",$msgBugs),
			"url" => "fsalespim"
		);
	} else {
		setcookie('info', 'success', time() + 5, '/');
		setcookie('pesan', "Sukses ".$processTitle, time() + 5, '/');
		$res = array(
			"status" => "Success",
			"message" => "Sukses ".$processTitle,
			"url" => $url
		);
	}
	$conn	= $base->close();
	echo(json_encode($res));
?>
