<?php
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
			// Format konsinyasi: 001/KON/bulan-romawi/tahun-2digit
			$gabung	= '/KON/'.$data->romawi(date('m')).'/'.date('y');
			$gobong	= '/KON/'.$data->romawi(date('m')).'/'.date('y');
				$kode	= $invoice;
				$nofak	= $secu->injection($_POST['nomorfaktur']);
				$tglfak	= $secu->injection($_POST['tglfaktur']);
				$nomorpo= $secu->injection($_POST['nomorpo']);
				$tglpo	= $secu->injection($_POST['tglpo']);
				$tglsj	= $secu->injection($_POST['tglsales']);
				$jatuh	= $secu->injection($_POST['jatuhtempo']);
			$status	= 'Faktur';
				$processTitle	= 'Tambah Faktur Konsinyasi '.$nofak;
				// refresh no invoice, faktur & checking no po
				try {
					$qRead = "SELECT * 
								FROM transaksi_faktur_konsinyasi
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
						array_push($msgBugs, "Nomor PO ".$nomorpo." atau Nomor Faktur sudah pernah digunakan!");
					}
				} catch (PDOException $e) {
					array_push($msgBugs, $e->getMessage());
				}
				if (empty($msgBugs)) {
					$qMcek = "SELECT *
								FROM transaksi_faktur_konsinyasi
								WHERE id_tfk=:code";
					try {
						$mcek	= $conn->prepare($qMcek);
						$mcek->bindParam(':code', $code, PDO::PARAM_STR);
						$mcek->execute();
						$hcek	= $mcek->fetch(PDO::FETCH_ASSOC);
						if (is_array($hcek)) {
							array_push($msgBugs, "ID Faktur Konsinyasi tidak dapat diproses!");
						}
					} catch (PDOException $e) {
						array_push($msgBugs, $e->getMessage());
					}
					if (empty($msgBugs)) {
						$qSave = "INSERT
									INTO
										transaksi_faktur_konsinyasi (
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
							$save->bindParam(':status', $status, PDO::PARAM_STR);
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
												'Faktur Konsinyasi', 
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
							$url	= "itemsalesk/$uniq";
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
				$status	= 'Konsinyasi';
				$processTitle	= 'Tambah Item Faktur Konsinyasi '.$nofak;
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
						$terjual = 0;
						$sisa = $jumlah;
						//INPUT
						$qSave = "INSERT 
									INTO transaksi_fakturdetail_konsinyasi (
										id_tfd,
										id_tfk,
										id_psd,
										id_pro,
										jumlah_tfd,
										terjual_tfd,
										sisa_tfd,
										harga_tfd,
										diskon_tfd,
										total_tfd,
										created_at,
										created_by,
										updated_at,
										updated_by)
									VALUES (
										:id, 
										:code, 
										:kodestok, 
										:pro, 
										:jumlah,
										:terjual,
										:sisa,
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
							$save->bindParam(':terjual', $terjual, PDO::PARAM_INT);
							$save->bindParam(':sisa', $sisa, PDO::PARAM_STR);
							$save->bindParam(':harga', $harga, PDO::PARAM_STR);
							$save->bindParam(':diskon', $diskon, PDO::PARAM_STR);
							$save->bindParam(':total', $total, PDO::PARAM_STR);
							$save->bindParam(':catat', $catat, PDO::PARAM_STR);
							$save->bindParam(':admin', $admin, PDO::PARAM_STR);
							$save->execute();
						} catch (PDOException $e) {
							array_push($msgBugs, $e->getMessage());
						}
						//EDIT STOK - UPDATE produk_stokdetail
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
						//INSERT KE produk_stokdetail_konsinyasi
						if (empty($msgBugs)) {
							// Ambil data dari produk_stokdetail
							$qGetStok = "SELECT id_pro, no_bcode, tgl_expired, gudang, sisa_psd 
										FROM produk_stokdetail 
										WHERE id_psd = :kodestok";
							try {
								$getStok = $conn->prepare($qGetStok);
								$getStok->bindParam(':kodestok', $kodestok, PDO::PARAM_STR);
								$getStok->execute();
								$stokData = $getStok->fetch(PDO::FETCH_ASSOC);
								
								if ($stokData) {
									$tglPsd = date('Y-m-d');
									$masukPsd = $jumlah;
									$keluarPsd = 0;
									$sisaPsd = $jumlah;
									$qtyso = 0;
									$statusBarang = 'active';
									
									$qInsertKonsinyasi = "INSERT INTO produk_stokdetail_konsinyasi 
														(id_psd, id_pro, id_tfk, no_bcode, tgl_expired, tgl_psd, 
														masuk_psd, keluar_psd, sisa_psd, gudang, qty_so, status_barang, 
														created_at, created_by, updated_at, updated_by)
														VALUES (:id_psd, :id_pro, :id_tfk, :no_bcode, :tgl_expired, :tgl_psd,
														:masuk_psd, :keluar_psd, :sisa_psd, :gudang, :qty_so, :status_barang,
														:catat, :admin, :catat, :admin)";
									$insertKonsinyasi = $conn->prepare($qInsertKonsinyasi);
									$insertKonsinyasi->bindParam(':id_psd', $kodestok, PDO::PARAM_STR);
									$insertKonsinyasi->bindParam(':id_pro', $stokData['id_pro'], PDO::PARAM_STR);
									$insertKonsinyasi->bindParam(':id_tfk', $code, PDO::PARAM_STR);
									$insertKonsinyasi->bindParam(':no_bcode', $stokData['no_bcode'], PDO::PARAM_STR);
									$insertKonsinyasi->bindParam(':tgl_expired', $stokData['tgl_expired'], PDO::PARAM_STR);
									$insertKonsinyasi->bindParam(':tgl_psd', $tglPsd, PDO::PARAM_STR);
									$insertKonsinyasi->bindParam(':masuk_psd', $masukPsd, PDO::PARAM_INT);
									$insertKonsinyasi->bindParam(':keluar_psd', $keluarPsd, PDO::PARAM_INT);
									$insertKonsinyasi->bindParam(':sisa_psd', $sisaPsd, PDO::PARAM_INT);
									$insertKonsinyasi->bindParam(':gudang', $stokData['gudang'], PDO::PARAM_STR);
									$insertKonsinyasi->bindParam(':qty_so', $qtyso, PDO::PARAM_INT);
									$insertKonsinyasi->bindParam(':status_barang', $statusBarang, PDO::PARAM_STR);
									$insertKonsinyasi->bindParam(':catat', $catat, PDO::PARAM_STR);
									$insertKonsinyasi->bindParam(':admin', $admin, PDO::PARAM_STR);
									$insertKonsinyasi->execute();
								}
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
									transaksi_faktur_konsinyasi
								SET 
									subtot_tfk=:stotal, 
									ppn_tfk=:ppn, 
									total_tfk=:gtotal, 
									status_tfk=:status, 
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
						$edit->bindParam(':catat', $catat, PDO::PARAM_STR);
						$edit->bindParam(':admin', $admin, PDO::PARAM_STR);
						$edit->execute();
					} catch (PDOException $e) {
						array_push($msgBugs, $e->getMessage());
					}
				}
				$url	= 'fsalesk';
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
				$processTitle	= 'Update Faktur Konsinyasi '.$nofak;
				try {
					$edit	= $conn->prepare("UPDATE transaksi_faktur_konsinyasi SET kode_tfk=:nofak, tgl_tfk=:tglfak, sj_tfk=:nosj, tglsj_tfk=:tglsj, po_tfk=:nopo, tglpo_tfk=:tglpo, tgl_limit=:jatuh, updated_at=:catat, updated_by=:admin WHERE id_tfk=:kode");
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
						$riwayat = $conn->query("INSERT INTO riwayat (kode_riwayat, menu_riwayat, status_riwayat, ket_riwayat, created_at, created_by) VALUES('$kode', 'Faktur Konsinyasi', 'Update', '', '$catat', '$admin')");
					} catch (PDOException $e) {
						array_push($msgBugs, $e->getMessage());
					}
				}
				$url	= "fsalesk";
			break;
			case 'delete':
				$kode	= $secu->injection($_POST['keycode']);
				$nofak	= $secu->injection($_POST['nomorfaktur']);
				$processTitle	= 'Delete Faktur Konsinyasi '.$nofak;
				try {
					$master	= $conn->prepare("SELECT id_psd, jumlah_tfd FROM transaksi_fakturdetail_konsinyasi WHERE id_tfk=:kode");
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
				// Hapus dari produk_stokdetail_konsinyasi
				if (empty($msgBugs)) {
					try {
						$removeKonsinyasi = $conn->prepare("DELETE FROM produk_stokdetail_konsinyasi WHERE id_tfk=:kode");
						$removeKonsinyasi->bindParam(':kode', $kode, PDO::PARAM_STR);
						$removeKonsinyasi->execute();
					} catch (PDOException $e) {
						array_push($msgBugs, $e->getMessage());
					}
				}
				if (empty($msgBugs)) {
					try {
						$remove	= $conn->prepare("DELETE A, B FROM transaksi_faktur_konsinyasi AS A LEFT JOIN transaksi_fakturdetail_konsinyasi AS B ON A.id_tfk=B.id_tfk WHERE A.id_tfk=:kode");
						$remove->bindParam(':kode', $kode, PDO::PARAM_STR);
						$remove->execute();
					} catch (PDOException $e) {
						array_push($msgBugs, $e->getMessage());
					}
				}
				// RIWAYAT
				if (empty($msgBugs)) {
					try {
						$riwayat= $conn->query("INSERT INTO riwayat (kode_riwayat, menu_riwayat, status_riwayat, ket_riwayat, created_at, created_by) VALUES('$kode', 'Faktur Konsinyasi', 'Delete', '', '$catat', '$admin')");
					} catch (PDOException $e) {
						array_push($msgBugs, $e->getMessage());
					}
				}
				$url	= "fsalesk";
			break;
		}
	}
	if (!empty($msgBugs)) {
		$res = array(
			"status" => "Error",
			"message" => implode(", ",$msgBugs),
			"url" => "fsalesk"
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
