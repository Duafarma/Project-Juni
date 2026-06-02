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
		$url = 'signout';
	} else {
		switch($menu){
			case 'save_manual':
				$uniq	= $secu->injection($_POST['keycode']);
				$code	= base64_decode($uniq);
				$kode_manual = $secu->injection($_POST['kode_manual']);
				$id_mr	= isset($_POST['id_mr']) ? (int)$_POST['id_mr'] : 0;
				$ket_mr	= isset($_POST['ket_mr']) ? $secu->injection($_POST['ket_mr']) : '';
				$stotal	= (int)str_replace('.', '', $_POST['pstotal']);
				$ppn	= (int)str_replace('.', '', $_POST['pppn']);
				$gtotal	= (int)str_replace('.', '', $_POST['pgtotal']);
				$processTitle = 'Simpan Faktur Manual';

				// Cek faktur asli ada
				try {
					$qFaktur = $conn->prepare("SELECT id_tfk FROM transaksi_faktur WHERE id_tfk=:code");
					$qFaktur->bindParam(':code', $code, PDO::PARAM_STR);
					$qFaktur->execute();
					$faktur = $qFaktur->fetch(PDO::FETCH_ASSOC);
					if(!$faktur) {
						array_push($msgBugs, "Faktur asli tidak ditemukan!");
					}
				} catch(PDOException $e) {
					array_push($msgBugs, $e->getMessage());
				}

				// Insert ke transaksi_faktur_manual
				if(empty($msgBugs)) {
					try {
						$qSave = "INSERT INTO transaksi_faktur_manual 
									(id_tfk, kode_tfk, id_mr, ket_mr, subtot_tfm, ppn_tfm, total_tfm, status_tfm, created_at, created_by) 
								  VALUES 
									(:id_tfk, :kode_tfk, :id_mr, :ket_mr, :subtot, :ppn, :total, 'Manual', :catat, :admin)";
						$save = $conn->prepare($qSave);
						$save->bindParam(':id_tfk', $code, PDO::PARAM_STR);
						$save->bindParam(':kode_tfk', $kode_manual, PDO::PARAM_STR);
						$save->bindParam(':id_mr', $id_mr, PDO::PARAM_INT);
						$save->bindParam(':ket_mr', $ket_mr, PDO::PARAM_STR);
						$save->bindParam(':subtot', $stotal, PDO::PARAM_INT);
						$save->bindParam(':ppn', $ppn, PDO::PARAM_INT);
						$save->bindParam(':total', $gtotal, PDO::PARAM_INT);
						$save->bindParam(':catat', $catat, PDO::PARAM_STR);
						$save->bindParam(':admin', $admin, PDO::PARAM_STR);
						$save->execute();
						$idManual = $conn->lastInsertId();
					} catch(PDOException $e) {
						array_push($msgBugs, $e->getMessage());
					}
				}

				// Insert detail items
				if(empty($msgBugs) && isset($_POST['id_psd'])) {
					$jum = count($_POST['id_psd']);
					for($i = 0; $i < $jum; $i++) {
						$id_psd = $secu->injection($_POST['id_psd'][$i]);
						$id_pro = $secu->injection($_POST['id_pro'][$i]);
						$jumlah = (int)str_replace('.', '', $_POST['jumlah'][$i]);
						$harga  = (int)str_replace('.', '', $_POST['harga'][$i]);
						$diskon = $secu->injection($_POST['diskon'][$i]);
						$total  = (int)str_replace('.', '', $_POST['total'][$i]);

						try {
							$qDetail = "INSERT INTO transaksi_faktur_manual_detail 
										(id_tfm, id_tfk, id_pro, id_psd, jumlah_tfmd, harga_tfmd, diskon_tfmd, total_tfmd, created_at, created_by)
									   VALUES
										(:id_tfm, :id_tfk, :id_pro, :id_psd, :jumlah, :harga, :diskon, :total, :catat, :admin)";
							$detail = $conn->prepare($qDetail);
							$detail->bindParam(':id_tfm', $idManual, PDO::PARAM_INT);
							$detail->bindParam(':id_tfk', $code, PDO::PARAM_STR);
							$detail->bindParam(':id_pro', $id_pro, PDO::PARAM_STR);
							$detail->bindParam(':id_psd', $id_psd, PDO::PARAM_STR);
							$detail->bindParam(':jumlah', $jumlah, PDO::PARAM_INT);
							$detail->bindParam(':harga', $harga, PDO::PARAM_INT);
							$detail->bindParam(':diskon', $diskon, PDO::PARAM_STR);
							$detail->bindParam(':total', $total, PDO::PARAM_INT);
							$detail->bindParam(':catat', $catat, PDO::PARAM_STR);
							$detail->bindParam(':admin', $admin, PDO::PARAM_STR);
							$detail->execute();
						} catch(PDOException $e) {
							array_push($msgBugs, $e->getMessage());
							break;
						}
					}
				}

				$url = 'fsales/daftarmanual';
			break;
		}
	}

	if (!empty($msgBugs)) {
		$res = array(
			"status" => "Error",
			"message" => implode(", ", $msgBugs),
			"url" => "fsales"
		);
	} else {
		if (!isset($processTitle)) $processTitle = "Proses Manual";
		setcookie('info', 'success', time() + 5, '/');
		setcookie('pesan', "Sukses " . $processTitle, time() + 5, '/');
		$res = array(
			"status" => "Success",
			"message" => "Sukses " . $processTitle,
			"url" => $url
		);
	}
	$conn = $base->close();
	echo(json_encode($res));
?>
