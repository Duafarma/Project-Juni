<?php
	session_start();
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$conn	= $base->open();
	$admin	= $secu->injection(@$_COOKIE['adminkuy']);
	$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
	$catat	= date('Y-m-d H:i:s');
	$menu	= $secu->injection(@$_POST['namamenu']);

	header('Content-Type: application/json');

	if($secu->validadmin($admin, $kunci) == false) {
		echo json_encode(['status'=>'error','message'=>'Session habis, silakan login ulang.']);
		exit;
	}

	switch($menu) {
		case 'transfer_retur':
			$id_psd    = (int)$secu->injection($_POST['id_psd']);
			$jumlah    = (int)$secu->injection($_POST['jumlah']);
			$keterangan = $secu->injection($_POST['keterangan']);

			if($id_psd < 1 || $jumlah < 1) {
				echo json_encode(['status'=>'error','message'=>'Data tidak valid.']);
				exit;
			}

			// Ambil data produk dari produk_stokdetail
			$qPsd = $conn->prepare("SELECT A.id_psd, A.id_pro, A.no_bcode, A.tgl_expired, A.gudang, A.sisa_psd, A.keluar_psd FROM produk_stokdetail A WHERE A.id_psd=:id AND A.sisa_psd>0");
			$qPsd->bindParam(':id', $id_psd, PDO::PARAM_INT);
			$qPsd->execute();
			$psd = $qPsd->fetch(PDO::FETCH_ASSOC);

			if(!$psd) {
				echo json_encode(['status'=>'error','message'=>'Stok tidak ditemukan.']);
				exit;
			}
			if($jumlah > (int)$psd['sisa_psd']) {
				echo json_encode(['status'=>'error','message'=>'Jumlah melebihi stok tersedia ('.$psd['sisa_psd'].').']);
				exit;
			}

			try {
				$conn->beginTransaction();

				// 1. Generate nomor bukti transfer
				$tgl_ymd = date('Ymd');
				$cekNo = $conn->query("SELECT COUNT(*) FROM transfer_inventory_retur WHERE DATE(created_at)=CURDATE()");
				$urut  = (int)$cekNo->fetchColumn() + 1;
				$no_tir = 'TIR'.$tgl_ymd.str_pad($urut, 4, '0', STR_PAD_LEFT);

				// 2. Simpan bukti transfer
				$ins = $conn->prepare("INSERT INTO transfer_inventory_retur (no_tir, id_psd, id_pro, no_bcode, tgl_expired, gudang, jumlah, keterangan, created_at, created_by) VALUES (:no_tir, :id_psd, :id_pro, :bcode, :expired, :gudang, :jumlah, :ket, :catat, :admin)");
				$ins->bindParam(':no_tir',   $no_tir,              PDO::PARAM_STR);
				$ins->bindParam(':id_psd',   $id_psd,              PDO::PARAM_INT);
				$ins->bindParam(':id_pro',   $psd['id_pro'],       PDO::PARAM_STR);
				$ins->bindParam(':bcode',    $psd['no_bcode'],     PDO::PARAM_STR);
				$ins->bindParam(':expired',  $psd['tgl_expired'],  PDO::PARAM_STR);
				$ins->bindParam(':gudang',   $psd['gudang'],       PDO::PARAM_STR);
				$ins->bindParam(':jumlah',   $jumlah,              PDO::PARAM_INT);
				$ins->bindParam(':ket',      $keterangan,          PDO::PARAM_STR);
				$ins->bindParam(':catat',    $catat,               PDO::PARAM_STR);
				$ins->bindParam(':admin',    $admin,               PDO::PARAM_STR);
				$ins->execute();

				// 3. Kurangi sisa di produk_stokdetail
				$keluar_baru = (int)$psd['keluar_psd'] + $jumlah;
				$sisa_baru   = (int)$psd['sisa_psd'] - $jumlah;
				$upd = $conn->prepare("UPDATE produk_stokdetail SET keluar_psd=:keluar, sisa_psd=:sisa, updated_at=:catat, updated_by=:admin WHERE id_psd=:id");
				$upd->bindParam(':keluar', $keluar_baru, PDO::PARAM_INT);
				$upd->bindParam(':sisa',   $sisa_baru,   PDO::PARAM_INT);
				$upd->bindParam(':catat',  $catat,       PDO::PARAM_STR);
				$upd->bindParam(':admin',  $admin,       PDO::PARAM_STR);
				$upd->bindParam(':id',     $id_psd,      PDO::PARAM_INT);
				$upd->execute();

				// 4. Cek apakah sudah ada record di inventory_retur dengan bcode+id_pro sama
				$qIr = $conn->prepare("SELECT id_i_r, sisa, keluar FROM inventory_retur WHERE id_pro=:id_pro AND no_bcode=:bcode AND ed=:expired LIMIT 1");
				$qIr->bindParam(':id_pro',  $psd['id_pro'],      PDO::PARAM_STR);
				$qIr->bindParam(':bcode',   $psd['no_bcode'],    PDO::PARAM_STR);
				$qIr->bindParam(':expired', $psd['tgl_expired'], PDO::PARAM_STR);
				$qIr->execute();
				$ir = $qIr->fetch(PDO::FETCH_ASSOC);

				if($ir) {
					// Update sisa inventory_retur
					$sisa_ir = (int)$ir['sisa'] + $jumlah;
					$masuk_ir_new = $jumlah; // tidak perlu update masuk — cukup sisa
					$updIr = $conn->prepare("UPDATE inventory_retur SET sisa=sisa+:jumlah, masuk=masuk+:jumlah2, updated_at=:catat, updated_by=:admin WHERE id_i_r=:id");
					$updIr->bindParam(':jumlah',  $jumlah, PDO::PARAM_INT);
					$updIr->bindParam(':jumlah2', $jumlah, PDO::PARAM_INT);
					$updIr->bindParam(':catat',   $catat,  PDO::PARAM_STR);
					$updIr->bindParam(':admin',   $admin,  PDO::PARAM_STR);
					$updIr->bindParam(':id',      $ir['id_i_r'], PDO::PARAM_INT);
					$updIr->execute();
				} else {
					// Insert baru ke inventory_retur
					$tgl_hari = date('Y-m-d');
					$insIr = $conn->prepare("INSERT INTO inventory_retur (id_r_d, id_pro, no_bcode, ed, tanggal, gudang, masuk, keluar, sisa, created_at, created_by) VALUES (:id_r_d, :id_pro, :bcode, :expired, :tgl, :gudang, :masuk, 0, :sisa, :catat, :admin)");
					$insIr->bindParam(':id_r_d',  $no_tir,              PDO::PARAM_STR);
					$insIr->bindParam(':id_pro',  $psd['id_pro'],       PDO::PARAM_STR);
					$insIr->bindParam(':bcode',   $psd['no_bcode'],     PDO::PARAM_STR);
					$insIr->bindParam(':expired', $psd['tgl_expired'],  PDO::PARAM_STR);
					$insIr->bindParam(':tgl',     $tgl_hari,            PDO::PARAM_STR);
					$insIr->bindParam(':gudang',  $psd['gudang'],       PDO::PARAM_STR);
					$insIr->bindParam(':masuk',   $jumlah,              PDO::PARAM_INT);
					$insIr->bindParam(':sisa',    $jumlah,              PDO::PARAM_INT);
					$insIr->bindParam(':catat',   $catat,               PDO::PARAM_STR);
					$insIr->bindParam(':admin',   $admin,               PDO::PARAM_STR);
					$insIr->execute();
				}

				$conn->commit();
				echo json_encode(['status'=>'success','message'=>'Transfer berhasil! No. Bukti: '.$no_tir]);

			} catch(PDOException $e) {
				$conn->rollBack();
				echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
			}
		break;

		default:
			echo json_encode(['status'=>'error','message'=>'Aksi tidak dikenali.']);
		break;
	}
?>
