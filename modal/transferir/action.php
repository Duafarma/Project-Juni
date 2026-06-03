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
	$menu	= $secu->injection(@$_POST['namamenu'] ?? '');

	header('Content-Type: application/json');

	if($secu->validadmin($admin, $kunci) == false) {
		echo json_encode(['status'=>'error','message'=>'Session habis, silakan login ulang.']);
		exit;
	}

	// -------------------------------------------------------
	// Generate nomor transfer
	// -------------------------------------------------------
	function generateNoTir($conn) {
		$tgl = date('Ymd');
		$q   = $conn->prepare("SELECT COUNT(*) FROM transfer_ir WHERE DATE(created_at)=CURDATE()");
		$q->execute();
		$urut = (int)$q->fetchColumn() + 1;
		return 'TIR'.$tgl.str_pad($urut, 4, '0', STR_PAD_LEFT);
	}

	switch($menu) {

		// -------------------------------------------------------
		// SAVE DRAFT / SUBMIT LANGSUNG
		// -------------------------------------------------------
		case 'save_transfer':
			$keterangan = $secu->injection(@$_POST['keterangan'] ?? '');
			$status     = in_array(@$_POST['status'], ['draft','pending']) ? $_POST['status'] : 'draft';
			$sumber_apl = $secu->injection(@$_POST['sumber_apl'] ?? 'lokal'); // id_apl atau 'lokal'
			$itemsRaw   = @$_POST['items'] ?? '[]';
			$items      = json_decode($itemsRaw, true);

			if(!is_array($items) || count($items) === 0) {
				echo json_encode(['status'=>'error','message'=>'Tidak ada item untuk ditransfer.']);
				exit;
			}

			// Validasi: hanya jika sumber lokal
			if($sumber_apl === 'lokal') {
				foreach($items as $it) {
					$id_psd = (int)($it['id_psd'] ?? 0);
					$jml    = (int)($it['jumlah'] ?? 0);
					if($id_psd < 1 || $jml < 1) {
						echo json_encode(['status'=>'error','message'=>'Data item tidak valid.']);
						exit;
					}
					$chk = $conn->prepare("SELECT sisa FROM inventory_retur WHERE id_i_r=:id");
					$chk->bindParam(':id', $id_psd, PDO::PARAM_INT);
					$chk->execute();
					$row = $chk->fetch(PDO::FETCH_ASSOC);
					if(!$row) {
						echo json_encode(['status'=>'error','message'=>'Stok inventory retur id='.$id_psd.' tidak ditemukan.']);
						exit;
					}
					if($jml > (int)$row['sisa']) {
						echo json_encode(['status'=>'error','message'=>'Jumlah transfer ('.$jml.') melebihi stok inventory retur (sisa: '.(int)$row['sisa'].').']);
						exit;
					}
				}
			}

			try {
				$conn->beginTransaction();

				$no_tir = generateNoTir($conn);
				$submitted_at = ($status === 'pending') ? $catat : null;
				$submitted_by = ($status === 'pending') ? $admin : '';

				$ins = $conn->prepare("INSERT INTO transfer_ir (no_tir, keterangan, status_tir, sumber_apl, created_at, created_by, submitted_at, submitted_by)
				                       VALUES (:no_tir, :ket, :status, :sumber, :catat, :admin, :sub_at, :sub_by)");
				$ins->bindParam(':no_tir', $no_tir,       PDO::PARAM_STR);
				$ins->bindParam(':ket',    $keterangan,   PDO::PARAM_STR);
				$ins->bindParam(':status', $status,       PDO::PARAM_STR);
				$ins->bindParam(':sumber', $sumber_apl,   PDO::PARAM_STR);
				$ins->bindParam(':catat',  $catat,        PDO::PARAM_STR);
				$ins->bindParam(':admin',  $admin,        PDO::PARAM_STR);
				$ins->bindValue(':sub_at', $submitted_at);
				$ins->bindParam(':sub_by', $submitted_by, PDO::PARAM_STR);
				$ins->execute();

				$id_tir = (int)$conn->lastInsertId();

				$insD = $conn->prepare("INSERT INTO transfer_ir_detail (id_tir, id_psd, id_pro, no_bcode, tgl_expired, gudang, jumlah, created_at, created_by)
				                        VALUES (:id_tir, :id_psd, :id_pro, :bcode, :expired, :gudang, :jumlah, :catat, :admin)");
				$updIR = $conn->prepare("UPDATE inventory_retur SET keluar=keluar+:jml, sisa=sisa-:jml, updated_at=:catat, updated_by=:admin WHERE id_i_r=:id AND sisa>=:jml2");
				foreach($items as $it) {
					$id_psd   = (int)$it['id_psd']; // id_i_r
					$id_pro   = $secu->injection($it['id_pro']   ?? '');
					$bcode    = $secu->injection($it['no_bcode'] ?? '');
					$expired  = $secu->injection($it['tgl_expired'] ?? '');
					$gudang   = $secu->injection($it['gudang']   ?? '');
					$jumlah   = (int)$it['jumlah'];
					// Insert detail
					$insD->bindParam(':id_tir',  $id_tir,  PDO::PARAM_INT);
					$insD->bindParam(':id_psd',  $id_psd,  PDO::PARAM_INT);
					$insD->bindParam(':id_pro',  $id_pro,  PDO::PARAM_STR);
					$insD->bindParam(':bcode',   $bcode,   PDO::PARAM_STR);
					$insD->bindParam(':expired', $expired, PDO::PARAM_STR);
					$insD->bindParam(':gudang',  $gudang,  PDO::PARAM_STR);
					$insD->bindParam(':jumlah',  $jumlah,  PDO::PARAM_INT);
					$insD->bindParam(':catat',   $catat,   PDO::PARAM_STR);
					$insD->bindParam(':admin',   $admin,   PDO::PARAM_STR);
					$insD->execute();
					// Kurangi sisa inventory_retur (kunci stok)
					$updIR->bindParam(':jml',   $jumlah,  PDO::PARAM_INT);
					$updIR->bindParam(':jml2',  $jumlah,  PDO::PARAM_INT);
					$updIR->bindParam(':catat', $catat,   PDO::PARAM_STR);
					$updIR->bindParam(':admin', $admin,   PDO::PARAM_STR);
					$updIR->bindParam(':id',    $id_psd,  PDO::PARAM_INT);
					$updIR->execute();
					if($updIR->rowCount() === 0) throw new Exception('Stok inventory retur tidak mencukupi atau tidak ditemukan (id_i_r='.$id_psd.').');
				}

				$conn->commit();
				$msg = ($status === 'pending') ? 'Transfer berhasil diajukan. Stok inventory retur telah dikunci.' : 'Draft berhasil disimpan.';
				echo json_encode(['status'=>'ok','message'=>$msg,'id_tir'=>$id_tir]);
			} catch(Exception $e) {
				$conn->rollBack();
				echo json_encode(['status'=>'error','message'=>'Terjadi kesalahan: '.$e->getMessage()]);
			}
			break;

		// -------------------------------------------------------
		// SUBMIT PENDING (dari draft → pending)
		// -------------------------------------------------------
		case 'submit_pending':
			$id_tir = (int)$secu->injection(@$_POST['id_tir'] ?? 0);
			if($id_tir < 1) { echo json_encode(['status'=>'error','message'=>'ID tidak valid.']); exit; }

			$chk = $conn->prepare("SELECT status_tir FROM transfer_ir WHERE id_tir=:id");
			$chk->bindParam(':id', $id_tir, PDO::PARAM_INT);
			$chk->execute();
			$row = $chk->fetch(PDO::FETCH_ASSOC);
			if(!$row || $row['status_tir'] !== 'draft') {
				echo json_encode(['status'=>'error','message'=>'Transfer tidak ditemukan atau status bukan draft.']);
				exit;
			}

			$upd = $conn->prepare("UPDATE transfer_ir SET status_tir='pending', submitted_at=:at, submitted_by=:by, updated_at=:catat, updated_by=:admin WHERE id_tir=:id");
			$upd->bindParam(':at',    $catat, PDO::PARAM_STR);
			$upd->bindParam(':by',    $admin, PDO::PARAM_STR);
			$upd->bindParam(':catat', $catat, PDO::PARAM_STR);
			$upd->bindParam(':admin', $admin, PDO::PARAM_STR);
			$upd->bindParam(':id',    $id_tir, PDO::PARAM_INT);
			$upd->execute();

			echo json_encode(['status'=>'ok','message'=>'Transfer berhasil diajukan ke approve.']);
			break;

		// -------------------------------------------------------
		// APPROVE: pindahkan stok
		// -------------------------------------------------------
		case 'approve':
			$id_tir = (int)$secu->injection(@$_POST['id_tir'] ?? 0);
			if($id_tir < 1) { echo json_encode(['status'=>'error','message'=>'ID tidak valid.']); exit; }

			$chk = $conn->prepare("SELECT T.*, A.base_url_apl, A.key_apl, A.nama_apl
			                       FROM transfer_ir T
			                       LEFT JOIN aplikasi A ON T.sumber_apl = A.id_apl
			                       WHERE T.id_tir=:id");
			$chk->bindParam(':id', $id_tir, PDO::PARAM_INT);
			$chk->execute();
			$row = $chk->fetch(PDO::FETCH_ASSOC);
			if(!$row || $row['status_tir'] !== 'pending') {
				echo json_encode(['status'=>'error','message'=>'Transfer tidak ditemukan atau status bukan pending.']);
				exit;
			}
			$sumber_apl    = $row['sumber_apl'] ?? 'lokal';
			$remote_url    = $row['base_url_apl'] ?? '';
			$remote_key    = $row['key_apl'] ?? '';
			$remote_nama   = $row['nama_apl'] ?? '';
			$is_remote     = ($sumber_apl !== 'lokal' && $sumber_apl !== '' && $remote_url !== '');

			// Ambil semua detail
			$qD = $conn->prepare("SELECT * FROM transfer_ir_detail WHERE id_tir=:id");
			$qD->bindParam(':id', $id_tir, PDO::PARAM_INT);
			$qD->execute();
			$details = $qD->fetchAll(PDO::FETCH_ASSOC);

			if(empty($details)) {
				echo json_encode(['status'=>'error','message'=>'Tidak ada item untuk ditransfer.']);
				exit;
			}

			try {
				$conn->beginTransaction();

				foreach($details as $d) {
					$id_i_r = (int)$d['id_psd'];
					$jumlah = (int)$d['jumlah'];

					if($is_remote) {
						// Sumber remote: id_psd adalah id_i_r di app sumber
						// Tambahkan langsung ke produk_stokdetail pakai data dari detail
						$id_pro  = $secu->injection($d['id_pro']     ?? '');
						$bcode   = $secu->injection($d['no_bcode']   ?? '');
						$ed      = $secu->injection($d['tgl_expired'] ?? '');
						$gudang  = $secu->injection($d['gudang']     ?? '');

						$cekPsd = $conn->prepare("SELECT id_psd FROM produk_stokdetail WHERE id_pro=:id_pro AND no_bcode=:bcode AND tgl_expired=:ed AND gudang=:gudang LIMIT 1");
						$cekPsd->bindParam(':id_pro', $id_pro,  PDO::PARAM_STR);
						$cekPsd->bindParam(':bcode',  $bcode,   PDO::PARAM_STR);
						$cekPsd->bindParam(':ed',     $ed,      PDO::PARAM_STR);
						$cekPsd->bindParam(':gudang', $gudang,  PDO::PARAM_STR);
						$cekPsd->execute();
						$psd = $cekPsd->fetch(PDO::FETCH_ASSOC);

						if($psd) {
							$upd = $conn->prepare("UPDATE produk_stokdetail SET masuk_psd=masuk_psd+:jml, sisa_psd=sisa_psd+:jml, updated_at=:catat, updated_by=:admin WHERE id_psd=:id");
							$upd->bindParam(':jml',   $jumlah,        PDO::PARAM_INT);
							$upd->bindParam(':catat', $catat,         PDO::PARAM_STR);
							$upd->bindParam(':admin', $admin,         PDO::PARAM_STR);
							$upd->bindParam(':id',    $psd['id_psd'], PDO::PARAM_INT);
							$upd->execute();
						} else {
							$ins = $conn->prepare("INSERT INTO produk_stokdetail (id_pro, no_bcode, tgl_expired, gudang, masuk_psd, keluar_psd, sisa_psd, created_at, created_by)
							                       VALUES (:id_pro, :bcode, :ed, :gudang, :jml, 0, :jml, :catat, :admin)");
							$ins->bindParam(':id_pro', $id_pro,  PDO::PARAM_STR);
							$ins->bindParam(':bcode',  $bcode,   PDO::PARAM_STR);
							$ins->bindParam(':ed',     $ed,      PDO::PARAM_STR);
							$ins->bindParam(':gudang', $gudang,  PDO::PARAM_STR);
							$ins->bindParam(':jml',    $jumlah,  PDO::PARAM_INT);
							$ins->bindParam(':catat',  $catat,   PDO::PARAM_STR);
							$ins->bindParam(':admin',  $admin,   PDO::PARAM_STR);
							$ins->execute();
						}
					} else {
						// Sumber lokal: ambil data dari inventory_retur
						$ir = $conn->prepare("SELECT id_i_r, id_pro, no_bcode, ed, gudang FROM inventory_retur WHERE id_i_r=:id");
						$ir->bindParam(':id', $id_i_r, PDO::PARAM_INT);
						$ir->execute();
						$stokRetur = $ir->fetch(PDO::FETCH_ASSOC);

						if(!$stokRetur) throw new Exception('Data inventory retur id='.$id_i_r.' tidak ditemukan.');

						$cekPsd = $conn->prepare("SELECT id_psd FROM produk_stokdetail WHERE id_pro=:id_pro AND no_bcode=:bcode AND tgl_expired=:ed AND gudang=:gudang LIMIT 1");
						$cekPsd->bindParam(':id_pro', $stokRetur['id_pro'],   PDO::PARAM_STR);
						$cekPsd->bindParam(':bcode',  $stokRetur['no_bcode'], PDO::PARAM_STR);
						$cekPsd->bindParam(':ed',     $stokRetur['ed'],       PDO::PARAM_STR);
						$cekPsd->bindParam(':gudang', $stokRetur['gudang'],   PDO::PARAM_STR);
						$cekPsd->execute();
						$psd = $cekPsd->fetch(PDO::FETCH_ASSOC);

						if($psd) {
							$updPsd = $conn->prepare("UPDATE produk_stokdetail SET masuk_psd=masuk_psd+:jml, sisa_psd=sisa_psd+:jml, updated_at=:catat, updated_by=:admin WHERE id_psd=:id");
							$updPsd->bindParam(':jml',   $jumlah,        PDO::PARAM_INT);
							$updPsd->bindParam(':catat', $catat,         PDO::PARAM_STR);
							$updPsd->bindParam(':admin', $admin,         PDO::PARAM_STR);
							$updPsd->bindParam(':id',    $psd['id_psd'], PDO::PARAM_INT);
							$updPsd->execute();
						} else {
							$insPsd = $conn->prepare("INSERT INTO produk_stokdetail (id_pro, no_bcode, tgl_expired, gudang, masuk_psd, keluar_psd, sisa_psd, created_at, created_by)
							                          VALUES (:id_pro, :bcode, :ed, :gudang, :jml, 0, :jml, :catat, :admin)");
							$insPsd->bindParam(':id_pro', $stokRetur['id_pro'],   PDO::PARAM_STR);
							$insPsd->bindParam(':bcode',  $stokRetur['no_bcode'], PDO::PARAM_STR);
							$insPsd->bindParam(':ed',     $stokRetur['ed'],       PDO::PARAM_STR);
							$insPsd->bindParam(':gudang', $stokRetur['gudang'],   PDO::PARAM_STR);
							$insPsd->bindParam(':jml',    $jumlah,                PDO::PARAM_INT);
							$insPsd->bindParam(':catat',  $catat,                 PDO::PARAM_STR);
							$insPsd->bindParam(':admin',  $admin,                 PDO::PARAM_STR);
							$insPsd->execute();
						}
					}
				}

				// Update status transfer_ir → approved
				$updTir = $conn->prepare("UPDATE transfer_ir SET status_tir='approved', approved_at=:at, approved_by=:by, updated_at=:catat, updated_by=:admin WHERE id_tir=:id");
				$updTir->bindParam(':at',    $catat,  PDO::PARAM_STR);
				$updTir->bindParam(':by',    $admin,  PDO::PARAM_STR);
				$updTir->bindParam(':catat', $catat,  PDO::PARAM_STR);
				$updTir->bindParam(':admin', $admin,  PDO::PARAM_STR);
				$updTir->bindParam(':id',    $id_tir, PDO::PARAM_INT);
				$updTir->execute();

				$conn->commit();

				// Jika sumber remote: kirim notifikasi ke app sumber untuk catat history
				if($is_remote) {
					$self    = $data->self_apl();
					$encrypt = md5(date('Y-m-d') . '#' . $remote_key);
					$apiUrl  = rtrim($remote_url, '/') . '/api/postTransferIR.php?encrypt=' . urlencode($encrypt);

					// Siapkan items untuk dikirim
					$itemsForRemote = [];
					foreach($details as $d) {
						$itemsForRemote[] = [
							'id_i_r'      => (int)$d['id_psd'],
							'id_pro'      => $d['id_pro'],
							'no_bcode'    => $d['no_bcode'],
							'tgl_expired' => $d['tgl_expired'],
							'gudang'      => $d['gudang'],
							'jumlah'      => (int)$d['jumlah'],
						];
					}

					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $apiUrl);
					curl_setopt($ch, CURLOPT_POST, true);
					curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
						'action'       => 'save',
						'no_tir'       => $row['no_tir'],
						'keterangan'   => $row['keterangan'],
						'tujuan_apl'   => $self['id_apl'],
						'tujuan_nama'  => $self['nama_apl'],
						'approved_by'  => $admin,
						'items'        => json_encode($itemsForRemote),
					]));
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_TIMEOUT, 8);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
					curl_exec($ch);
					curl_close($ch);
					// Tidak block approve meski remote gagal
				}

				echo json_encode(['status'=>'ok','message'=>'Transfer berhasil di-approve. Stok telah dipindahkan ke Inventory Penjualan.']);
			} catch(Exception $e) {
				$conn->rollBack();
				echo json_encode(['status'=>'error','message'=>'Gagal: '.$e->getMessage()]);
			}
			break;

		// -------------------------------------------------------
		// REJECT
		// -------------------------------------------------------
		case 'reject':
			$id_tir = (int)$secu->injection(@$_POST['id_tir'] ?? 0);
			$notes  = $secu->injection(@$_POST['notes'] ?? '');
			if($id_tir < 1) { echo json_encode(['status'=>'error','message'=>'ID tidak valid.']); exit; }

			$chk = $conn->prepare("SELECT T.*, A.base_url_apl, A.key_apl FROM transfer_ir T LEFT JOIN aplikasi A ON T.sumber_apl=A.id_apl WHERE T.id_tir=:id");
			$chk->bindParam(':id', $id_tir, PDO::PARAM_INT);
			$chk->execute();
			$row = $chk->fetch(PDO::FETCH_ASSOC);
			if(!$row || $row['status_tir'] !== 'pending') {
				echo json_encode(['status'=>'error','message'=>'Transfer tidak ditemukan atau status bukan pending.']);
				exit;
			}
			$rej_sumber_apl = $row['sumber_apl'] ?? 'lokal';
			$rej_remote_url = $row['base_url_apl'] ?? '';
			$rej_remote_key = $row['key_apl'] ?? '';
			$rej_is_remote  = ($rej_sumber_apl !== 'lokal' && $rej_sumber_apl !== '' && $rej_remote_url !== '');

			// Kembalikan stok ke inventory_retur (hanya jika lokal)
			try {
				$conn->beginTransaction();

				$qD = $conn->prepare("SELECT id_psd, jumlah FROM transfer_ir_detail WHERE id_tir=:id");
				$qD->bindParam(':id', $id_tir, PDO::PARAM_INT);
				$qD->execute();
				$detailsReject = $qD->fetchAll(PDO::FETCH_ASSOC);

				if(!$rej_is_remote) {
					$restoreIR = $conn->prepare("UPDATE inventory_retur SET keluar=keluar-:jml, sisa=sisa+:jml, updated_at=:catat, updated_by=:admin WHERE id_i_r=:id");
					foreach($detailsReject as $dr) {
						$jml = (int)$dr['jumlah'];
						$idr = (int)$dr['id_psd'];
						$restoreIR->bindParam(':jml',   $jml,   PDO::PARAM_INT);
						$restoreIR->bindParam(':catat', $catat, PDO::PARAM_STR);
						$restoreIR->bindParam(':admin', $admin, PDO::PARAM_STR);
						$restoreIR->bindParam(':id',    $idr,   PDO::PARAM_INT);
						$restoreIR->execute();
					}
				}

				$upd = $conn->prepare("UPDATE transfer_ir SET status_tir='rejected', notes_tir=:notes, approved_at=:at, approved_by=:by, updated_at=:catat, updated_by=:admin WHERE id_tir=:id");
				$upd->bindParam(':notes', $notes,  PDO::PARAM_STR);
				$upd->bindParam(':at',    $catat,  PDO::PARAM_STR);
				$upd->bindParam(':by',    $admin,  PDO::PARAM_STR);
				$upd->bindParam(':catat', $catat,  PDO::PARAM_STR);
				$upd->bindParam(':admin', $admin,  PDO::PARAM_STR);
				$upd->bindParam(':id',    $id_tir, PDO::PARAM_INT);
				$upd->execute();

				$conn->commit();

				// Jika sumber remote: kirim notifikasi reject agar mereka kembalikan stok
				if($rej_is_remote) {
					$encrypt = md5(date('Y-m-d') . '#' . $rej_remote_key);
					$apiUrl  = rtrim($rej_remote_url, '/') . '/api/postTransferIR.php?encrypt=' . urlencode($encrypt);
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $apiUrl);
					curl_setopt($ch, CURLOPT_POST, true);
					curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
						'action'  => 'reject',
						'no_tir'  => $row['no_tir'],
						'notes'   => $notes,
					]));
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_TIMEOUT, 8);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
					curl_exec($ch);
					curl_close($ch);
				}

				echo json_encode(['status'=>'ok','message'=>'Transfer ditolak. Stok telah dikembalikan ke Inventory Retur.']);
			} catch(Exception $e) {
				$conn->rollBack();
				echo json_encode(['status'=>'error','message'=>'Gagal menolak: '.$e->getMessage()]);
			}
			break;

		default:
			echo json_encode(['status'=>'error','message'=>'Action tidak dikenal.']);
			break;
	}
?>
