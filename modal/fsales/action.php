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
				
				$outlet	= isset($_POST['outlet']) ? $secu->injection($_POST['outlet']) : '';
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
			$ccp	= isset($_POST['ccp']) ? $secu->injection($_POST['ccp']) : 'tidak ada';
			$cito	= isset($_POST['cito']) ? $secu->injection($_POST['cito']) : 'ga cito';
			$ket	= 'ket';
			$id_mr	= isset($_POST['id_mr']) ? (int)$_POST['id_mr'] : 0;
			$ket_mr	= isset($_POST['ket_mr']) ? $secu->injection($_POST['ket_mr']) : '';
			// Handle konsinyasi
			$dari_konsinyasi = isset($_POST['dari_konsinyasi']) ? $secu->injection($_POST['dari_konsinyasi']) : 'tidak';
			$id_faktur_konsinyasi = isset($_POST['id_faktur_konsinyasi']) ? $secu->injection($_POST['id_faktur_konsinyasi']) : '';
			
			// Handle program promo - simpan program spesifik yang dipilih
			$program_promo = isset($_POST['program_promo']) ? $secu->injection($_POST['program_promo']) : 'tidak';
			if ($program_promo === 'ya' && isset($_POST['selected_programs']) && is_array($_POST['selected_programs'])) {
				// Gabungkan program yang dipilih dengan koma (misal: "program_vb,program_diskon")
				$selected = array_map(function($p) use ($secu) { return $secu->injection($p); }, $_POST['selected_programs']);
				$program = implode(',', $selected);
			} else {
				$program = 'tidak'; // Tidak ada program yang dipilih
			}
				// refresh no invoice, faktur & checking no po
				try {
					$qRead = "SELECT * 
								FROM transaksi_faktur
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
								FROM transaksi_faktur
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
										transaksi_faktur (
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
											ket,
											program,
											dari_konsinyasi,
											id_tfk_konsinyasi,
                                                                              id_mr,
                                                                              ket_mr,
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
										:ket,
										:program,
										:dari_konsinyasi,
										:id_faktur_konsinyasi,
										:id_mr,
										:ket_mr,
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
							$save->bindParam(':ket', $ket, PDO::PARAM_STR);
							$save->bindParam(':program', $program, PDO::PARAM_STR);
							$save->bindParam(':dari_konsinyasi', $dari_konsinyasi, PDO::PARAM_STR);
							$save->bindParam(':id_faktur_konsinyasi', $id_faktur_konsinyasi, PDO::PARAM_STR);
                                                       $save->bindParam(':id_mr', $id_mr, PDO::PARAM_INT);
                                                       $save->bindParam(':ket_mr', $ket_mr, PDO::PARAM_STR);
							$save->bindParam(':catat', $catat, PDO::PARAM_STR);
							$save->bindParam(':admin', $admin, PDO::PARAM_STR);
							$save->execute();
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
												'Faktur Penjualan', 
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
					
						try {
								$delBook = $conn->prepare("DELETE FROM faktur_booking WHERE sj_booked=:sj OR fk_booked=:fk");
								$delBook->execute([':sj' => $kode, ':fk' => $nofak]);
							} catch (PDOException $e) { /* abaikan */ }
							// Hapus booking nomor faktur karena sudah disimpan
							try {
								$delNomor = $conn->prepare("DELETE FROM nomor_faktur_booking WHERE id_tfk=:code");
								$delNomor->bindParam(':code', $code, PDO::PARAM_STR);
								$delNomor->execute();
							} catch (PDOException $e) { /* abaikan */ }
							$url	= "itemsales/$uniq";
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
					$qLimit = "SELECT O.`platform` AS limit_outlet, O.`status_pembayaran` AS status_pembayaran
							   FROM transaksi_faktur F
							   LEFT JOIN outlet O ON F.id_out = O.id_out
							   WHERE F.id_tfk = :code
							   LIMIT 1";
					$stmtLimit = $conn->prepare($qLimit);
					$stmtLimit->bindParam(':code', $code, PDO::PARAM_STR);
					$stmtLimit->execute();
					$rowLimit = $stmtLimit->fetch(PDO::FETCH_ASSOC);

					$limitOutlet = isset($rowLimit['limit_outlet']) ? (int)$rowLimit['limit_outlet'] : 0;
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
				
				// Cek apakah faktur ini dari konsinyasi
				$qCekKonsi = "SELECT dari_konsinyasi, id_tfk_konsinyasi FROM transaksi_faktur WHERE id_tfk=:code";
				$cekKonsi = $conn->prepare($qCekKonsi);
				$cekKonsi->bindParam(':code', $code, PDO::PARAM_STR);
				$cekKonsi->execute();
				$dataKonsi = $cekKonsi->fetch(PDO::FETCH_ASSOC);
				$dari_konsinyasi = $dataKonsi['dari_konsinyasi'];
				$id_tfk_konsinyasi = $dataKonsi['id_tfk_konsinyasi'];
				
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
									INTO transaksi_fakturdetail
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
						//EDIT STOK - Pilih table berdasarkan sumber
						if (empty($msgBugs)) {
							if($dari_konsinyasi === 'ya') {
								// Update stok di produk_stokdetail_konsinyasi
								$qEdit = "UPDATE 
											produk_stokdetail_konsinyasi 
											SET 
												keluar_psd = keluar_psd+:jumlah, 
												sisa_psd = sisa_psd-:jumlah,
												updated_at = :catat,
												updated_by = :admin
											WHERE 
												id_psd = :kodestok 
												AND id_tfk = :id_tfk_konsi";
								
								try {
									$edit	= $conn->prepare($qEdit);
									$edit->bindParam(':kodestok', $kodestok, PDO::PARAM_STR);
									$edit->bindParam(':jumlah', $jumlah, PDO::PARAM_INT);
									$edit->bindParam(':id_tfk_konsi', $id_tfk_konsinyasi, PDO::PARAM_STR);
									$edit->bindParam(':catat', $catat, PDO::PARAM_STR);
									$edit->bindParam(':admin', $admin, PDO::PARAM_STR);
									$edit->execute();
								} catch (PDOException $e) {
									array_push($msgBugs, $e->getMessage());
								}
								
								// Update transaksi_fakturdetail_konsinyasi (terjual_tfd dan sisa_tfd)
								if (empty($msgBugs) && !empty($id_tfk_konsinyasi)) {
									try {
										$qUpdateDetail = "UPDATE transaksi_fakturdetail_konsinyasi 
															SET terjual_tfd = terjual_tfd + :jumlah,
																sisa_tfd = sisa_tfd - :jumlah,
																updated_at = :catat,
																updated_by = :admin
															WHERE id_tfk = :id_tfk_konsi 
																AND id_psd = :kodestok";
										$updateDetail = $conn->prepare($qUpdateDetail);
										$updateDetail->bindParam(':jumlah', $jumlah, PDO::PARAM_INT);
										$updateDetail->bindParam(':id_tfk_konsi', $id_tfk_konsinyasi, PDO::PARAM_STR);
										$updateDetail->bindParam(':kodestok', $kodestok, PDO::PARAM_STR);
										$updateDetail->bindParam(':catat', $catat, PDO::PARAM_STR);
										$updateDetail->bindParam(':admin', $admin, PDO::PARAM_STR);
										$updateDetail->execute();
									} catch (PDOException $e) {
										array_push($msgBugs, $e->getMessage());
									}
								}
								
								// Update status transaksi_faktur_konsinyasi
								if (empty($msgBugs) && !empty($id_tfk_konsinyasi)) {
									try {
										// Cek total sisa dari transaksi_fakturdetail_konsinyasi
										$qCekSisa = "SELECT SUM(sisa_tfd) as total_sisa 
													FROM transaksi_fakturdetail_konsinyasi 
													WHERE id_tfk = :id_tfk_konsi";
										$cekSisa = $conn->prepare($qCekSisa);
										$cekSisa->bindParam(':id_tfk_konsi', $id_tfk_konsinyasi, PDO::PARAM_STR);
										$cekSisa->execute();
										$dataSisa = $cekSisa->fetch(PDO::FETCH_ASSOC);
										$totalSisa = $dataSisa['total_sisa'];
										
										// Tentukan status berdasarkan sisa stok
										if ($totalSisa <= 0) {
											$statusKonsi = 'Selesai'; // Semua habis terjual
										} else {
											$statusKonsi = 'Sebagian'; // Sebagian terjual
										}
										
										// Update status di transaksi_faktur_konsinyasi
										$qUpdateStatus = "UPDATE transaksi_faktur_konsinyasi 
															SET status_tfk = :status,
																updated_at = :catat,
																updated_by = :admin
															WHERE id_tfk = :id_tfk_konsi";
										$updateStatus = $conn->prepare($qUpdateStatus);
										$updateStatus->bindParam(':status', $statusKonsi, PDO::PARAM_STR);
										$updateStatus->bindParam(':id_tfk_konsi', $id_tfk_konsinyasi, PDO::PARAM_STR);
										$updateStatus->bindParam(':catat', $catat, PDO::PARAM_STR);
										$updateStatus->bindParam(':admin', $admin, PDO::PARAM_STR);
										$updateStatus->execute();
									} catch (PDOException $e) {
										array_push($msgBugs, $e->getMessage());
									}
								}
							} else {
								// Update stok di produk_stokdetail normal
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
					}
					$no++;
				}
				//EDIT
				if (empty($msgBugs)) {
					$qEdit = "UPDATE 
									transaksi_faktur
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
				// Hapus semua booking draft untuk faktur ini karena sudah disimpan & stok sudah dipotong
				if (empty($msgBugs)) {
					try {
						$delBooking = $conn->prepare("DELETE FROM transaksi_booking_draft WHERE id_tfk = :code");
						$delBooking->bindParam(':code', $code, PDO::PARAM_STR);
						$delBooking->execute();
					} catch (PDOException $e) {
						// Tidak fatal - silent ignore jika tabel tidak ada
						error_log('Hapus booking draft error: ' . $e->getMessage());
					}
				}
				$url	= 'fsales';
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
				$ket	= $secu->injection($_POST['ket']);
				$processTitle	= 'Update Faktur  '.$nofak;
				// Validasi duplikat nomor PO dan faktur
				try {
					$qRead = "SELECT * 
								FROM transaksi_faktur
								WHERE
									(po_tfk=:po_tfk OR kode_tfk=:kode_tfk)
									AND id_tfk != :id_tfk
								LIMIT 1";
					$read	= $conn->prepare($qRead);
					$read->bindParam(':po_tfk', $nopo, PDO::PARAM_STR);
					$read->bindParam(':kode_tfk', $nofak, PDO::PARAM_STR);
					$read->bindParam(':id_tfk', $kode, PDO::PARAM_STR);
					$read->execute();
					$dRead  = $read->fetch(PDO::FETCH_ASSOC);
                    if (is_array($dRead)) {
						array_push($msgBugs, "Nomor PO ".$nopo." atau Nomor Faktur ".$nofak." sudah pernah digunakan!");
					}
				} catch (PDOException $e) {
					array_push($msgBugs, $e->getMessage());
				}
				if (empty($msgBugs)) {
					try {
					$edit	= $conn->prepare("UPDATE transaksi_faktur SET kode_tfk=:nofak, tgl_tfk=:tglfak, sj_tfk=:nosj, tglsj_tfk=:tglsj, po_tfk=:nopo, tglpo_tfk=:tglpo, tgl_limit=:jatuh,ket=:ket, updated_at=:catat, updated_by=:admin WHERE id_tfk=:kode");
					$edit->bindParam(':kode', $kode, PDO::PARAM_STR);
					$edit->bindParam(':nofak', $nofak, PDO::PARAM_STR);
					$edit->bindParam(':tglfak', $tglfak, PDO::PARAM_STR);
					$edit->bindParam(':nosj', $nosj, PDO::PARAM_STR);
					$edit->bindParam(':tglsj', $tglsj, PDO::PARAM_STR);
					$edit->bindParam(':nopo', $nopo, PDO::PARAM_STR);
					$edit->bindParam(':tglpo', $tglpo, PDO::PARAM_STR);
					$edit->bindParam(':jatuh', $jatuh, PDO::PARAM_STR);
					$edit->bindParam(':ket', $ket, PDO::PARAM_STR);
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
				}
				$url	= "fsales";
			break;
		case 'delete':
				$kode	= $secu->injection($_POST['keycode']);
				$nofak	= $secu->injection($_POST['nomorfaktur']);
				$keterangan_revisi = $secu->injection(@$_POST['keterangan_revisi']);
				$processTitle	= 'Delete Faktur '.$nofak;

				if (empty(trim($keterangan_revisi))) {
					array_push($msgBugs, 'Keterangan wajib diisi untuk penghapusan faktur');
				}
				
				// Cek apakah faktur ini dari konsinyasi dan ambil tanggal faktur
				$dari_konsinyasi = 'tidak';
				$id_tfk_konsinyasi = '';
				$tgl_faktur = null;
				$kode_faktur = '';
				$is_different_month = false;
				
				if (empty($msgBugs)) {
					try {
						$checkFaktur = $conn->prepare("SELECT dari_konsinyasi, id_tfk_konsinyasi, tgl_tfk, kode_tfk FROM transaksi_faktur WHERE id_tfk = :kode");
						$checkFaktur->bindParam(':kode', $kode, PDO::PARAM_STR);
						$checkFaktur->execute();
						$fakturData = $checkFaktur->fetch(PDO::FETCH_ASSOC);
						if($fakturData) {
							$dari_konsinyasi = $fakturData['dari_konsinyasi'] ?? 'tidak';
							$id_tfk_konsinyasi = $fakturData['id_tfk_konsinyasi'] ?? '';
							$tgl_faktur = $fakturData['tgl_tfk'];
							$kode_faktur = $fakturData['kode_tfk'];
							
							// Cek apakah bulan faktur berbeda dengan bulan berjalan
							$bulan_faktur = date('Y-m', strtotime($tgl_faktur));
							$bulan_sekarang = date('Y-m');
							$is_different_month = ($bulan_faktur !== $bulan_sekarang);
							
							error_log("=== DELETE FAKTUR CHECK ===");
							error_log("Kode Faktur: $kode_faktur");
							error_log("Tanggal Faktur: $tgl_faktur");
							error_log("Bulan Faktur: $bulan_faktur");
							error_log("Bulan Sekarang: $bulan_sekarang");
							error_log("Dari Konsinyasi: $dari_konsinyasi");
							error_log("Beda Bulan: " . ($is_different_month ? 'YA (akan masuk cancel table)' : 'TIDAK (akan kembalikan stok)'));
							error_log("===========================");
						} else {
							error_log("DELETE FAKTUR ERROR - Faktur tidak ditemukan untuk kode: $kode");
							array_push($msgBugs, "Faktur tidak ditemukan");
						}
					} catch (PDOException $e) {
						error_log("DELETE FAKTUR CHECK ERROR - " . $e->getMessage());
						array_push($msgBugs, "Error mengambil data faktur: " . $e->getMessage());
					}
				}

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
							po_tfk, tglpo_tfk, subtot_tfk, ppn_tfk, total_tfk, 'Delete',
							:keterangan_revisi, 'Delete Faktur Penjualan', :admin, :catat
						FROM transaksi_faktur
						WHERE id_tfk = :kode
					");
					$backupFaktur->bindParam(':kode', $kode, PDO::PARAM_STR);
					$backupFaktur->bindParam(':admin', $admin, PDO::PARAM_STR);
					$backupFaktur->bindParam(':catat', $catat, PDO::PARAM_STR);
					$backupFaktur->bindParam(':keterangan_revisi', $keterangan_revisi, PDO::PARAM_STR);
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
								'Before Delete', :admin, :catat
							FROM transaksi_fakturdetail
							WHERE id_tfk = :kode
						");
						$backupDetail->bindParam(':junk_id', $junkFakturId, PDO::PARAM_INT);
						$backupDetail->bindParam(':kode', $kode, PDO::PARAM_STR);
						$backupDetail->bindParam(':admin', $admin, PDO::PARAM_STR);
						$backupDetail->bindParam(':catat', $catat, PDO::PARAM_STR);
						$backupDetail->execute();
					}

					error_log("DELETE FAKTUR BACKUP SUCCESS - Faktur $kode ($nofak) backed up to junk ID $junkFakturId");
				} catch (PDOException $e) {
					error_log("DELETE FAKTUR BACKUP FAILED - Faktur $kode ($nofak) - " . $e->getMessage());
				}
				}
				// RETURN STOK - Ambil detail item faktur
				try {
					$master	= $conn->prepare("SELECT tfd.id_psd, tfd.jumlah_tfd, tfd.id_pro,
								psd.no_bcode, psd.tgl_expired, psd.tgl_psd, psd.gudang, psd.id_trd
							FROM transaksi_fakturdetail tfd
							LEFT JOIN produk_stokdetail psd ON tfd.id_psd = psd.id_psd
							WHERE tfd.id_tfk=:kode");
					$master->bindParam(':kode', $kode, PDO::PARAM_STR);
					$master->execute();
					
					$itemCount = 0;
					while($hasil = $master->fetch(PDO::FETCH_ASSOC)){
						$itemCount++;
						error_log("DELETE FAKTUR - Processing item #$itemCount - ID PSD: " . $hasil['id_psd'] . ", Jumlah: " . $hasil['jumlah_tfd']);
						
						if($dari_konsinyasi === 'ya' && !empty($id_tfk_konsinyasi)) {
							// Return stock ke produk_stokdetail_konsinyasi
							error_log("DELETE FAKTUR - KONSINYASI - ID PSD: " . $hasil['id_psd']);
							try {
								$edit = $conn->prepare("UPDATE produk_stokdetail_konsinyasi 
									SET keluar_psd=keluar_psd-:jumlah, sisa_psd=sisa_psd+:jumlah,
										updated_at=:catat, updated_by=:admin
									WHERE id_psd=:kode AND id_tfk=:id_tfk_konsi");
								$edit->bindParam(':jumlah', $hasil['jumlah_tfd'], PDO::PARAM_INT);
								$edit->bindParam(':kode', $hasil['id_psd'], PDO::PARAM_STR);
								$edit->bindParam(':id_tfk_konsi', $id_tfk_konsinyasi, PDO::PARAM_STR);
								$edit->bindParam(':catat', $catat, PDO::PARAM_STR);
								$edit->bindParam(':admin', $admin, PDO::PARAM_STR);
								$edit->execute();
								error_log("DELETE FAKTUR - KONSINYASI - Stok dikembalikan ke produk_stokdetail_konsinyasi");
							} catch (PDOException $e) {
								error_log("DELETE FAKTUR - KONSINYASI ERROR: " . $e->getMessage());
							}
							
							try {
								$qUpdateDetail = "UPDATE transaksi_fakturdetail_konsinyasi 
													SET terjual_tfd = terjual_tfd - :jumlah,
														sisa_tfd = sisa_tfd + :jumlah,
														updated_at = :catat,
														updated_by = :admin
													WHERE id_tfk = :id_tfk_konsi 
														AND id_psd = :kodestok";
								$updateDetail = $conn->prepare($qUpdateDetail);
								$updateDetail->bindParam(':jumlah', $hasil['jumlah_tfd'], PDO::PARAM_INT);
								$updateDetail->bindParam(':id_tfk_konsi', $id_tfk_konsinyasi, PDO::PARAM_STR);
								$updateDetail->bindParam(':kodestok', $hasil['id_psd'], PDO::PARAM_STR);
								$updateDetail->bindParam(':catat', $catat, PDO::PARAM_STR);
								$updateDetail->bindParam(':admin', $admin, PDO::PARAM_STR);
								$updateDetail->execute();
								error_log("DELETE FAKTUR - KONSINYASI - Detail updated");
							} catch (PDOException $e) {
								error_log("DELETE FAKTUR - KONSINYASI DETAIL ERROR: " . $e->getMessage());
							}
						} else {
							// FAKTUR REGULER (BUKAN KONSINYASI)
							error_log("DELETE FAKTUR - REGULER - ID PSD: " . $hasil['id_psd'] . ", is_different_month: " . ($is_different_month ? 'TRUE' : 'FALSE'));
							
							// Cek apakah bulan faktur berbeda dengan bulan berjalan
							if ($is_different_month) {
								// Bulan berbeda: Insert ke produk_stockdetail_cancel, TIDAK kembalikan stok ke produk_stokdetail
								try {
									$insertCancel = $conn->prepare("INSERT INTO produk_stockdetail_cancel 
										(id_psd, id_trd, id_pro, id_tfk, kode_faktur, tgl_faktur, 
										no_bcode, tgl_expired, tgl_psd, jumlah_cancel, gudang, 
										keterangan_cancel, dari_konsinyasi, id_tfk_konsinyasi, status,
										cancel_at, cancel_by, created_at, created_by)
										VALUES 
										(:id_psd, :id_trd, :id_pro, :id_tfk, :kode_faktur, :tgl_faktur,
										:no_bcode, :tgl_expired, :tgl_psd, :jumlah_cancel, :gudang,
										:keterangan_cancel, :dari_konsinyasi, :id_tfk_konsinyasi, 'cancel',
										:cancel_at, :cancel_by, :created_at, :created_by)");
									$insertCancel->bindParam(':id_psd', $hasil['id_psd'], PDO::PARAM_STR);
									$insertCancel->bindParam(':id_trd', $hasil['id_trd'], PDO::PARAM_STR);
									$insertCancel->bindParam(':id_pro', $hasil['id_pro'], PDO::PARAM_STR);
									$insertCancel->bindParam(':id_tfk', $kode, PDO::PARAM_STR);
									$insertCancel->bindParam(':kode_faktur', $kode_faktur, PDO::PARAM_STR);
									$insertCancel->bindParam(':tgl_faktur', $tgl_faktur, PDO::PARAM_STR);
									$insertCancel->bindParam(':no_bcode', $hasil['no_bcode'], PDO::PARAM_STR);
									$insertCancel->bindParam(':tgl_expired', $hasil['tgl_expired'], PDO::PARAM_STR);
									$insertCancel->bindParam(':tgl_psd', $hasil['tgl_psd'], PDO::PARAM_STR);
									$insertCancel->bindParam(':jumlah_cancel', $hasil['jumlah_tfd'], PDO::PARAM_INT);
									$insertCancel->bindParam(':gudang', $hasil['gudang'], PDO::PARAM_STR);
									$insertCancel->bindParam(':keterangan_cancel', $keterangan_revisi, PDO::PARAM_STR);
									$insertCancel->bindParam(':dari_konsinyasi', $dari_konsinyasi, PDO::PARAM_STR);
									$insertCancel->bindParam(':id_tfk_konsinyasi', $id_tfk_konsinyasi, PDO::PARAM_STR);
									$insertCancel->bindParam(':cancel_at', $catat, PDO::PARAM_STR);
									$insertCancel->bindParam(':cancel_by', $admin, PDO::PARAM_STR);
									$insertCancel->bindParam(':created_at', $catat, PDO::PARAM_STR);
									$insertCancel->bindParam(':created_by', $admin, PDO::PARAM_STR);
									$insertCancel->execute();
									
									error_log("DELETE FAKTUR BEDA BULAN - Stok masuk ke produk_stockdetail_cancel, ID PSD: " . $hasil['id_psd'] . ", Jumlah: " . $hasil['jumlah_tfd']);
								} catch (PDOException $e) {
									error_log("DELETE FAKTUR BEDA BULAN ERROR - " . $e->getMessage());
								}
							} else {
								// Bulan sama: Return stock ke produk_stokdetail normal
								error_log("DELETE FAKTUR BULAN SAMA - Akan kembalikan stok ID PSD: " . $hasil['id_psd'] . ", Jumlah: " . $hasil['jumlah_tfd']);
								
								try {
									$edit = $conn->prepare("UPDATE produk_stokdetail 
										SET keluar_psd = keluar_psd - :jumlah, 
											sisa_psd = sisa_psd + :jumlah,
											updated_at = :catat,
											updated_by = :admin
										WHERE id_psd = :kode");
									$edit->bindParam(':jumlah', $hasil['jumlah_tfd'], PDO::PARAM_INT);
									$edit->bindParam(':kode', $hasil['id_psd'], PDO::PARAM_STR);
									$edit->bindParam(':catat', $catat, PDO::PARAM_STR);
									$edit->bindParam(':admin', $admin, PDO::PARAM_STR);
									$edit->execute();
									
									$rowsAffected = $edit->rowCount();
									error_log("DELETE FAKTUR BULAN SAMA - SUCCESS - Stok dikembalikan, Rows affected: $rowsAffected, ID PSD: " . $hasil['id_psd'] . ", Jumlah: " . $hasil['jumlah_tfd']);
								} catch (PDOException $e) {
									error_log("DELETE FAKTUR BULAN SAMA ERROR - " . $e->getMessage());
								}
							}
						}
					}
					
					error_log("DELETE FAKTUR - Total items processed: $itemCount");
					
				} catch (PDOException $e) {
					array_push($msgBugs, "Error loading faktur items: " . $e->getMessage());
					error_log("DELETE FAKTUR - ERROR loading items: " . $e->getMessage());
				}
				
				// Update status transaksi_faktur_konsinyasi jika dari konsinyasi
				if (empty($msgBugs) && $dari_konsinyasi === 'ya' && !empty($id_tfk_konsinyasi)) {
					try {
						// Cek total sisa dari transaksi_fakturdetail_konsinyasi
						$qCekSisa = "SELECT SUM(sisa_tfd) as total_sisa, SUM(jumlah_tfd) as total_jumlah
									FROM transaksi_fakturdetail_konsinyasi 
									WHERE id_tfk = :id_tfk_konsi";
						$cekSisa = $conn->prepare($qCekSisa);
						$cekSisa->bindParam(':id_tfk_konsi', $id_tfk_konsinyasi, PDO::PARAM_STR);
						$cekSisa->execute();
						$dataSisa = $cekSisa->fetch(PDO::FETCH_ASSOC);
						$totalSisa = $dataSisa['total_sisa'];
						$totalJumlah = $dataSisa['total_jumlah'];
						
						// Tentukan status berdasarkan sisa stok
						if ($totalSisa >= $totalJumlah) {
							$statusKonsi = 'Konsinyasi'; // Semua kembali full
						} else if ($totalSisa <= 0) {
							$statusKonsi = 'Selesai'; // Semua habis terjual
						} else {
							$statusKonsi = 'Sebagian'; // Sebagian terjual
						}
						
						// Update status di transaksi_faktur_konsinyasi
						$qUpdateStatus = "UPDATE transaksi_faktur_konsinyasi 
											SET status_tfk = :status,
												updated_at = :catat,
												updated_by = :admin
											WHERE id_tfk = :id_tfk_konsi";
						$updateStatus = $conn->prepare($qUpdateStatus);
						$updateStatus->bindParam(':status', $statusKonsi, PDO::PARAM_STR);
						$updateStatus->bindParam(':id_tfk_konsi', $id_tfk_konsinyasi, PDO::PARAM_STR);
						$updateStatus->bindParam(':catat', $catat, PDO::PARAM_STR);
						$updateStatus->bindParam(':admin', $admin, PDO::PARAM_STR);
						$updateStatus->execute();
					} catch (PDOException $e) {
						array_push($msgBugs, $e->getMessage());
					}
				}
			
				if (empty($msgBugs)) {
					try {
						$remove	= $conn->prepare("DELETE A, B, C FROM transaksi_faktur AS A LEFT JOIN transaksi_fakturdetail AS B ON A.id_tfk=B.id_tfk LEFT JOIN pembayaran_faktur AS C ON A.id_tfk=C.id_tfk WHERE A.id_tfk=:kode");
						$remove->bindParam(':kode', $kode, PDO::PARAM_STR);
						$remove->execute();
					} catch (PDOException $e) {
						array_push($msgBugs, $e->getMessage());
					}
				}
				// RIWAYAT (simpan alasan delete)
				if (empty($msgBugs)) {
					try {
						$riw = $conn->prepare("INSERT INTO riwayat (kode_riwayat, menu_riwayat, status_riwayat, ket_riwayat, created_at, created_by) VALUES(:kode, 'Faktur Penjualan', 'Delete', :ket, :catat, :admin)");
						$riw->bindParam(':kode', $kode, PDO::PARAM_STR);
						$riw->bindParam(':ket', $keterangan_revisi, PDO::PARAM_STR);
						$riw->bindParam(':catat', $catat, PDO::PARAM_STR);
						$riw->bindParam(':admin', $admin, PDO::PARAM_STR);
						$riw->execute();
					} catch (PDOException $e) {
						array_push($msgBugs, $e->getMessage());
					}
				}
				$url	= "fsales";
			break;
			
			case 'manual_revision':
				$kode	= $secu->injection($_POST['keycode']);
				$subtot	= str_replace('.', '', $_POST['subtotal']);
				$ppn	= str_replace('.', '', $_POST['ppn']);
				$total	= str_replace('.', '', $_POST['total']);
				$status_manual	= 'Revisi Faktur Manual';
				$processTitle	= 'Revisi Faktur Manual';

				try {
					$edit = $conn->prepare("UPDATE transaksi_faktur SET subtot_tfk=:subtot, ppn_tfk=:ppn, total_tfk=:total, status_tfk=:status_baru, updated_at=:catat, updated_by=:admin WHERE id_tfk=:kode");
					$edit->bindParam(':kode', $kode, PDO::PARAM_STR);
					$edit->bindParam(':subtot', $subtot, PDO::PARAM_STR);
					$edit->bindParam(':ppn', $ppn, PDO::PARAM_STR);
					$edit->bindParam(':total', $total, PDO::PARAM_STR);
					$edit->bindParam(':status_baru', $status_manual, PDO::PARAM_STR);
					$edit->bindParam(':catat', $catat, PDO::PARAM_STR);
					$edit->bindParam(':admin', $admin, PDO::PARAM_STR);
					$edit->execute();

					// RIWAYAT
					$conn->query("INSERT INTO riwayat (kode_riwayat, menu_riwayat, status_riwayat, ket_riwayat, created_at, created_by) VALUES('$kode', 'Faktur Penjualan', 'Update', 'Revisi Faktur Manual', '$catat', '$admin')");
				} catch (PDOException $e) {
					array_push($msgBugs, $e->getMessage());
				}
				$url = "fsales";
			break;
			case 'edit_item':
				// Proteksi double processing
				$lockKey = 'edit_item_' . md5(base64_decode($secu->injection($_POST['keycode'])));
				if (isset($_SESSION[$lockKey]) && $_SESSION[$lockKey] + 5 > time()) {
					array_push($msgBugs, "Proses edit sedang berlangsung, harap tunggu sebentar");
					break;
				}
				$_SESSION[$lockKey] = time();
				
				$uniq	= $secu->injection($_POST['keycode']);
				$code	= base64_decode($uniq);
				$stotal	= str_replace('.', '', $_POST['pstotal']);
				$gtotal	= str_replace('.', '', $_POST['pgtotal']);
				$ppn	= str_replace('.', '', $_POST['pppn']);
				$nofak	= $secu->injection($_POST['nomorfaktur']);
				$keterangan_revisi = $secu->injection($_POST['keterangan_revisi']);
				$status	= 'Revisi';
				$processTitle	= 'Edit Item Faktur '.$nofak;
				
				if (empty(trim($keterangan_revisi))) {
					array_push($msgBugs, "Keterangan revisi wajib diisi!");
				}
				
				$dataLama = array();
				if (empty($msgBugs)) {
					try {
						$master	= $conn->prepare("SELECT id_tfd, id_psd, jumlah_tfd FROM transaksi_fakturdetail WHERE id_tfk=:kode");
						$master->bindParam(':kode', $code, PDO::PARAM_STR);
						$master->execute();
						
						while($hasil = $master->fetch(PDO::FETCH_ASSOC)){
							$dataLama[$hasil['id_psd']] = $hasil['jumlah_tfd'];
						}
						
						error_log("=== EDIT ITEM START === Faktur: $nofak, Data lama: " . json_encode($dataLama));
					} catch (PDOException $e) {
						array_push($msgBugs, $e->getMessage());
					}
				}
				
				if (empty($msgBugs)) {
					try {
						error_log("BACKUP JUNK - Memulai backup untuk faktur: $nofak, ID: $code");
						
						$backupFaktur = $conn->prepare("
							INSERT INTO transaksi_faktur_junk (
								id_tfk_junk, id_tfk_original, sj_tfk, id_out, kode_tfk, tgl_tfk, 
								po_tfk, tglpo_tfk, subtot_tfk, ppn_tfk, total_tfk, status_tfk,
								keterangan_revisi, backup_reason, backup_by, backup_at
							)
							SELECT 
								NULL, id_tfk, sj_tfk, id_out, kode_tfk, tgl_tfk, 
								po_tfk, tglpo_tfk, subtot_tfk, ppn_tfk, total_tfk, status_tfk,
								:keterangan_revisi, 'Before Edit Item', :admin, :catat
							FROM transaksi_faktur 
							WHERE id_tfk = :code
						");
						$backupFaktur->bindParam(':code', $code, PDO::PARAM_STR);
						$backupFaktur->bindParam(':keterangan_revisi', $keterangan_revisi, PDO::PARAM_STR);
						$backupFaktur->bindParam(':admin', $admin, PDO::PARAM_STR);
						$backupFaktur->bindParam(':catat', $catat, PDO::PARAM_STR);
						$backupFaktur->execute();
						
						$junkFakturId = $conn->lastInsertId();
						error_log("BACKUP JUNK - Faktur ORIGINAL backed up with ID: $junkFakturId");
						
						$backupDetail = $conn->prepare("
							INSERT INTO transaksi_fakturdetail_junk (
								id_tfd_junk, id_tfk_junk, id_tfd_original, id_tfk_original, 
								id_psd, id_pro, jumlah_tfd, harga_tfd, diskon_tfd, total_tfd,
								backup_reason, backup_by, backup_at
							)
							SELECT 
								NULL, :junk_faktur_id, id_tfd, id_tfk,
								id_psd, id_pro, jumlah_tfd, harga_tfd, diskon_tfd, total_tfd,
								'Before Edit Item', :admin, :catat
							FROM transaksi_fakturdetail 
							WHERE id_tfk = :code
						");
						$backupDetail->bindParam(':junk_faktur_id', $junkFakturId, PDO::PARAM_INT);
						$backupDetail->bindParam(':code', $code, PDO::PARAM_STR);
						$backupDetail->bindParam(':admin', $admin, PDO::PARAM_STR);
						$backupDetail->bindParam(':catat', $catat, PDO::PARAM_STR);
						$backupDetail->execute();
						
						$backupDetailCount = $backupDetail->rowCount();
						error_log("BACKUP JUNK - $backupDetailCount ORIGINAL detail items backed up");
						
						// Log backup success
						error_log("=== BACKUP COMPLETED === Faktur: $nofak, Junk ID: $junkFakturId, Details: $backupDetailCount items (BEFORE EDIT)");
						
					} catch (PDOException $e) {
						// Jika backup gagal, tetap lanjutkan proses edit (tidak memblokir)
						error_log("BACKUP JUNK ERROR: " . $e->getMessage());
						error_log("BACKUP GAGAL - Tapi proses edit tetap dilanjutkan");
					}
				}
				
				if (empty($msgBugs)) {
					try {
						$delete	= $conn->prepare("DELETE FROM transaksi_fakturdetail WHERE id_tfk=:kode");
						$delete->bindParam(':kode', $code, PDO::PARAM_STR);
						$delete->execute();
					} catch (PDOException $e) {
						array_push($msgBugs, $e->getMessage());

					}
				}
				
				if (empty($msgBugs) && isset($_POST['kodestok'])) {
					$jum	= count($_POST['kodestok']);
					$no		= 0;

					
					error_log("EDIT ITEM DEBUG - Processing " . $jum . " items");
					
					while($no<$jum){
						if (empty($msgBugs)) {
							$kodestok= $secu->injection($_POST['kodestok'][$no]);
							$produk	= $secu->injection($_POST['product'][$no]);
							$jumlah	= str_replace('.', '', $_POST['jumlah'][$no]);
							$harga	= str_replace('.', '', $_POST['harga'][$no]);
							$diskon	= $secu->injection($_POST['diskon'][$no]);
							$total	= str_replace('.', '', $_POST['total'][$no]);
							
							//INSERT DATA BARU
							$qSave = "INSERT 
										INTO transaksi_fakturdetail (
											id_tfd,
											id_tfk, 
											id_psd, 
											id_pro, 
											jumlah_tfd, 
											harga_tfd, 
											diskon_tfd, 
											total_tfd, 
											created_at, 
											created_by, 
											updated_at, 
											updated_by)
										VALUES (
											NULL,
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
							
							//UPDATE STOK BERDASARKAN SELISIH
							if (empty($msgBugs)) {
								$jumlahLama = isset($dataLama[$kodestok]) ? $dataLama[$kodestok] : 0;
								$selisih = $jumlah - $jumlahLama; // Positif = tambah penggunaan, Negatif = kurangi penggunaan
								
								// Debug logging 
								error_log("EDIT ITEM DEBUG - Kodestok: $kodestok, Jumlah Lama: $jumlahLama, Jumlah Baru: $jumlah, Selisih: $selisih");
								
								if ($selisih != 0) {
									// Hanya update sisa_psd (stok tersedia), tidak perlu update keluar_psd
									$qEdit = "UPDATE 
												produk_stokdetail 
												SET 
													sisa_psd = sisa_psd - :selisih 
												WHERE 
													id_psd = :kodestok";
									try {
										$edit	= $conn->prepare($qEdit);
										$edit->bindParam(':kodestok', $kodestok, PDO::PARAM_STR);
										$edit->bindParam(':selisih', $selisih, PDO::PARAM_INT);
										$edit->execute();
										
										// Debug logging
										error_log("EDIT ITEM DEBUG - Stok updated for $kodestok with selisih: $selisih (sisa_psd -= $selisih)");
									} catch (PDOException $e) {
										array_push($msgBugs, $e->getMessage());
									}
								} else {
									error_log("EDIT ITEM DEBUG - No stock change needed for $kodestok (selisih = 0)");
								}
								
								// Hapus dari array data lama untuk tracking
								unset($dataLama[$kodestok]);
							}
						}
						$no++;
					}
				}
				
				// Kembalikan stok untuk item yang dihapus (tidak ada di data baru)
				if (empty($msgBugs)) {
					foreach($dataLama as $kodestok => $jumlahLama) {
						try {
							// Debug logging
							error_log("EDIT ITEM DEBUG - Returning stock for removed item $kodestok: +$jumlahLama");
							
							// Hanya update sisa_psd (stok tersedia), tidak perlu update keluar_psd
							$qEdit = "UPDATE 
										produk_stokdetail 
										SET 
											sisa_psd = sisa_psd + :jumlah 
										WHERE 
											id_psd = :kodestok";
							$edit	= $conn->prepare($qEdit);
							$edit->bindParam(':kodestok', $kodestok, PDO::PARAM_STR);
							$edit->bindParam(':jumlah', $jumlahLama, PDO::PARAM_INT);
							$edit->execute();
							
							error_log("EDIT ITEM DEBUG - Stock returned for removed item $kodestok: sisa_psd += $jumlahLama");
						} catch (PDOException $e) {
							array_push($msgBugs, $e->getMessage());
						}
					}
					
					// Debug logging
					error_log("=== EDIT ITEM END === Stock update process completed for faktur: $nofak");
				}
				
				//UPDATE TOTAL FAKTUR
				if (empty($msgBugs)) {
					$qEdit = "UPDATE 
									transaksi_faktur
								SET 
									subtot_tfk=:stotal, 
									ppn_tfk=:ppn, 
									total_tfk=:gtotal, 
									status_tfk=:status,
									status_dokumen='belum balik',
									status_failing='belum failing',
									ket=:keterangan,
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
						$edit->bindParam(':keterangan', $keterangan_revisi, PDO::PARAM_STR);
						$edit->bindParam(':catat', $catat, PDO::PARAM_STR);
						$edit->bindParam(':admin', $admin, PDO::PARAM_STR);
						$edit->execute();
					} catch (PDOException $e) {
						array_push($msgBugs, $e->getMessage());
					}
				}
				
				//RIWAYAT
				if (empty($msgBugs)) {
					try {
						$riwayat = $conn->query("INSERT INTO riwayat (kode_riwayat, menu_riwayat, status_riwayat, ket_riwayat, created_at, created_by) VALUES('$code', 'Faktur Penjualan', 'Edit Item', '$keterangan_revisi', '$catat', '$admin')");
					} catch (PDOException $e) {
						array_push($msgBugs, $e->getMessage());
					}
				}
				
				// Hapus lock setelah selesai
				if (isset($lockKey)) {
					unset($_SESSION[$lockKey]);
				}
				
				$url	= 'fsales';
			break;
		}
	}
	if (!empty($msgBugs)) {
		$res = array(
			"status" => "Error",
			"message" => implode(", ",$msgBugs),
			"url" => "fsales"
		);
	} else {
		// Set default processTitle if not defined
		if (!isset($processTitle)) {
			$processTitle = "Proses Faktur";
		}
		
		// Pesan sukses dengan informasi backup untuk edit item
		if (isset($processTitle) && strpos($processTitle, 'Edit Item') !== false) {
			$backupMessage = "Data berhasil di-backup ke tabel junk sebelum edit";
			setcookie('info', 'success', time() + 5, '/');
			setcookie('pesan', "Sukses ".$processTitle." - ".$backupMessage, time() + 5, '/');
			$res = array(
				"status" => "Success",
				"message" => "Sukses ".$processTitle."\n\n✅ ".$backupMessage."\n📝 Keterangan: ".$keterangan_revisi,
				"backup_info" => $backupMessage,
				"url" => $url
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
	}
	$conn	= $base->close();
	echo(json_encode($res));
?>
