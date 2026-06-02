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
	if ($secu->validadmin($admin, $kunci)==false) {
		header('location:'.$data->sistem('url_sis').'/signout');
	} else {
		$conn	= $base->open();
		switch($act){	
			case "input":
				$id		= '';
				$hasError = false;
				$jumlah	= count(@$_POST['id_pro'] ?: []);
				$nomor	= 0;

				// Kumpulkan unique principle dari data yang di-submit
				$submittedPrinciples = array();

				// Jika tidak ada baris selisih di form, cek filter_principle untuk kasus semua selisih=0
				$filterPrinciple = $secu->injection(@$_POST['filter_principle'] ?? '');
				if(!empty($filterPrinciple)){
					// Filter principle tertentu → tambahkan langsung
					if(!in_array($filterPrinciple, $submittedPrinciples)){
						$submittedPrinciples[] = $filterPrinciple;
					}
				} else {
					// Tidak ada filter (semua principle) → ambil semua principle pending dari DB
					$stmtAllPrinciple = $conn->prepare("SELECT DISTINCT B.nama_p FROM produk_stokdetail AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro LEFT JOIN produk_harga AS C ON B.id_pro=C.id_pro WHERE (A.sisa_psd > 0 OR (A.qty_so > 0)) AND A.id_psd NOT IN (SELECT id_psd FROM stock WHERE id_psd IS NOT NULL) AND C.status_phg='Active'");
					$stmtAllPrinciple->execute();
					$submittedPrinciples = $stmtAllPrinciple->fetchAll(PDO::FETCH_COLUMN);
				}
				while($nomor<$jumlah){
						$id_psd			= $secu->injection(@$_POST['id_psd'][$nomor]);
						// Cek apakah id_psd sudah pernah di-approve
						$cekDuplikat = $conn->prepare("SELECT COUNT(*) FROM stock WHERE id_psd = :id_psd");
						$cekDuplikat->bindParam(':id_psd', $id_psd, PDO::PARAM_STR);
						$cekDuplikat->execute();
						if((int)$cekDuplikat->fetchColumn() > 0){ $nomor++; continue; }

						$id_pro			= $secu->injection(@$_POST['id_pro'][$nomor]);
						$nama_pro		= $secu->injection(@$_POST['nama_pro'][$nomor]);
						$no_bcode		= $secu->injection(@$_POST['no_bcode'][$nomor]);
						$qty			= $secu->injection(@$_POST['qty'][$nomor]);
						$bcode_so		= $secu->injection(@$_POST['bcode_so'][$nomor]);
						$qty_so			= $secu->injection(@$_POST['qty_so'][$nomor]);
						$selisih		= $secu->injection(@$_POST['selisih'][$nomor]);
						$id_principle	= $secu->injection(@$_POST['id_principle'][$nomor]);

						// Simpan principle untuk diproses selisih=0 setelahnya
						if(!empty($id_principle) && !in_array($id_principle, $submittedPrinciples)){
							$submittedPrinciples[] = $id_principle;
						}

						$stmtIns = $conn->prepare("INSERT INTO stock VALUES(:id,:id_psd, :id_pro, :nama_pro, :no_bcode, :qty, :bcode_so, :qty_so, :selisih,:catat, :admin, :catat, :admin)");
						$stmtIns->bindParam(":id", $id, PDO::PARAM_STR);
						$stmtIns->bindParam(":id_psd", $id_psd, PDO::PARAM_STR);
						$stmtIns->bindParam(":id_pro", $id_pro, PDO::PARAM_STR);
						$stmtIns->bindParam(":nama_pro", $nama_pro, PDO::PARAM_STR);
						$stmtIns->bindParam(":no_bcode", $no_bcode, PDO::PARAM_STR);
						$stmtIns->bindParam(":qty", $qty, PDO::PARAM_STR);
						$stmtIns->bindParam(":bcode_so", $bcode_so, PDO::PARAM_STR);
						$stmtIns->bindParam(":qty_so", $qty_so, PDO::PARAM_STR);
						$stmtIns->bindParam(":selisih", $selisih, PDO::PARAM_STR);
						$stmtIns->bindParam(':catat', $catat, PDO::PARAM_STR);
						$stmtIns->bindParam(':admin', $admin, PDO::PARAM_STR);
						if(!$stmtIns->execute()) $hasError = true;

						$update_tfk = $conn->prepare("UPDATE produk_stokdetail SET sisa_psd = :qty_so, gudang='Puri', awal=:qty_so, status='belum so' WHERE id_psd= :id_psd");
						$update_tfk->bindParam(':id_psd', $id_psd, PDO::PARAM_STR);
						$update_tfk->bindParam(':qty_so', $qty_so, PDO::PARAM_STR);
						$update_tfk->execute();

						$nomor++;
					}

				// Simpan semua item selisih=0 dari principle yang sama (tidak ditampilkan tapi harus tersimpan)
				foreach($submittedPrinciples as $prinsip){
					$qZero = $conn->prepare("SELECT A.id_psd, A.id_pro, A.no_bcode, A.sisa_psd, A.qty_so, B.nama_pro FROM produk_stokdetail AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro WHERE B.nama_p = :prinsip AND A.qty_so = A.sisa_psd AND (A.sisa_psd > 0 OR A.qty_so > 0) AND A.id_psd NOT IN (SELECT id_psd FROM stock WHERE id_psd IS NOT NULL)");
					$qZero->bindParam(':prinsip', $prinsip, PDO::PARAM_STR);
					$qZero->execute();
					while($row = $qZero->fetch(PDO::FETCH_ASSOC)){
						$selisihNol = 0;
						$ins = $conn->prepare("INSERT INTO stock VALUES(:id,:id_psd,:id_pro,:nama_pro,:no_bcode,:qty,:bcode_so,:qty_so,:selisih,:catat,:admin,:catat,:admin)");
						$ins->bindValue(":id", '');
						$ins->bindValue(":id_psd", $row['id_psd']);
						$ins->bindValue(":id_pro", $row['id_pro']);
						$ins->bindValue(":nama_pro", $row['nama_pro']);
						$ins->bindValue(":no_bcode", $row['no_bcode']);
						$ins->bindValue(":qty", $row['sisa_psd']);
						$ins->bindValue(":bcode_so", $row['no_bcode']);
						$ins->bindValue(":qty_so", $row['qty_so']);
						$ins->bindValue(":selisih", $selisihNol);
						$ins->bindValue(':catat', $catat);
						$ins->bindValue(':admin', $admin);
						if(!$ins->execute()) $hasError = true;
					}
				}

				//RIWAYAT
				$conn->query("INSERT INTO riwayat VALUES('', '', 'Administrator', 'Create', '', '$catat', '$admin')");
				$hasil = $hasError ? "error" : "success";

				echo($hasil);
				$url = "stockopnameap/v";
			break;
		}
	}
?>