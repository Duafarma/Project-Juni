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
				$supplier	= $secu->injection($_POST['supplier']);
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
				$processTitle	= 'Tambah Faktur '.$nofak;
				// refresh no invoice, faktur & checking no po
				try {
					$qRead = "SELECT * 
								FROM faktur_retur
								WHERE
									po_fkr=:po_fkr OR
									kode_fkr=:kode_fkr
								LIMIT 1";
					$read	= $conn->prepare($qRead);
					$read->bindParam(':po_fkr', $nomorpo, PDO::PARAM_STR);
					$read->bindParam(':kode_fkr', $nofak, PDO::PARAM_STR);
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
								FROM faktur_retur
								WHERE id_fkr=:code";
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
										faktur_retur (
											id_fkr,
											id_tsl,
											id_sup,
											sj_fkr,
											tglsj_fkr,
											po_fkr,
											tglpo_fkr,
											kode_fkr,
											tgl_tfk,
											tgl_limit,
											subtot_fkr,
											ppn_fkr,
											total_fkr,
											status_fkr,
											created_at,
											created_by,
											updated_at,
											updated_by
											)
									VALUES(
										:code, 
										:id, 
										:supplier, 
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
							$save->bindParam(':supplier', $supplier, PDO::PARAM_STR);
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
												'Faktur Retur', 
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
							$url	= "itemretur/$uniq";
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
									INTO faktur_returdetail
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
										inventory_retur 
										SET 
											keluar = keluar+:jumlah, 
											sisa = sisa-:jumlah 
										WHERE 
											id_i_r =:kodestok";
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
									faktur_retur
								SET 
									subtot_fkr=:stotal, 
									ppn_fkr=:ppn, 
									total_fkr=:gtotal, 
									status_fkr=:status, 
									updated_at=:catat, 
									updated_by=:admin
								WHERE id_fkr=:code";
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
				$url	= 'faktur_retur';
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
					$edit	= $conn->prepare("UPDATE faktur_retur SET kode_fkr=:nofak, tgl_tfk=:tglfak, sj_fkr=:nosj, tglsj_fkr=:tglsj, po_fkr=:nopo, tglpo_fkr=:tglpo, tgl_limit=:jatuh, updated_at=:catat, updated_by=:admin WHERE id_fkr=:kode");
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
						$riwayat = $conn->query("INSERT INTO riwayat (kode_riwayat, menu_riwayat, status_riwayat, ket_riwayat, created_at, created_by) VALUES('$kode', 'Faktur Retur', 'Update', '', '$catat', '$admin')");
					} catch (PDOException $e) {
						array_push($msgBugs, $e->getMessage());
					}
				}
				$url	= "faktur_retur";
			break;
			case 'delete':
				$kode	= $secu->injection($_POST['keycode']);
				$nofak	= $secu->injection($_POST['nomorfaktur']);
				$processTitle	= 'Delete Faktur '.$nofak;
				try {
					$master	= $conn->prepare("SELECT id_i_r, jumlah_fkrd FROM faktur_returdetail WHERE id_fkr=:kode");
					$master->bindParam(':kode', $kode, PDO::PARAM_STR);
					$master->execute();
				} catch (PDOException $e) {
					array_push($msgBugs, $e->getMessage());
				}
				if (empty($msgBugs)) {
					while($hasil = $master->fetch(PDO::FETCH_ASSOC)){
						if (empty($msgBugs)) {
							try {
								$edit	= $conn->prepare("UPDATE inventory_retur SET keluar=keluar-:jumlah, sisa=sisa+:jumlah WHERE id_i_r=:kode");
								$edit->bindParam(':jumlah', $hasil['jumlah_fkrd'], PDO::PARAM_INT);
								$edit->bindParam(':kode', $hasil['id_i_r'], PDO::PARAM_STR);
								$edit->execute();
							} catch (PDOException $e) {
								array_push($msgBugs, $e->getMessage());
							}
						}
					}
				}
				/*
				$delete	= $conn->prepare("DELETE FROM pembayaran_faktur WHERE id_fkr=:kode");
				$delete->bindParam(':kode', $kode, PDO::PARAM_STR);
				$delete->execute();
				$delete	= $conn->prepare("DELETE FROM faktur_returdetail_c WHERE id_fkr=:kode");
				$delete->bindParam(':kode', $kode, PDO::PARAM_STR);
				$delete->execute();
				$delete	= $conn->prepare("DELETE FROM faktur_retur_c WHERE id_fkr=:kode");
				$delete->bindParam(':kode', $kode, PDO::PARAM_STR);
				$delete->execute();
				*/
				if (empty($msgBugs)) {
					try {
						$remove	= $conn->prepare("DELETE A, B, C FROM faktur_retur AS A LEFT JOIN faktur_returdetail AS B ON A.id_fkr=B.id_fkr LEFT JOIN pembayaran_faktur_retur AS C ON A.id_fkr=C.id_fkr WHERE A.id_fkr=:kode");
						$remove->bindParam(':kode', $kode, PDO::PARAM_STR);
						$remove->execute();
					} catch (PDOException $e) {
						array_push($msgBugs, $e->getMessage());
					}
				}
				// RIWAYAT
				if (empty($msgBugs)) {
					try {
						$riwayat= $conn->query("INSERT INTO riwayat (kode_riwayat, menu_riwayat, status_riwayat, ket_riwayat, created_at, created_by) VALUES('$kode', 'Faktur Retur', 'Delete', '', '$catat', '$admin')");
					} catch (PDOException $e) {
						array_push($msgBugs, $e->getMessage());
					}
				}
				$url	= "faktur_retur";
			break;
		}
	}
	if (!empty($msgBugs)) {
		$res = array(
			"status" => "Error",
			"message" => implode(", ",$msgBugs),
			"url" => "faktur_retur"
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
