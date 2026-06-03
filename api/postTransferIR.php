<?php
/**
 * API: Terima notifikasi Transfer Inventory Retur ke Penjualan dari app lain
 *
 * Dipanggil saat app tujuan melakukan APPROVE transfer.
 * App sumber akan:
 *   1. Catat history di tabel transfer_ir + transfer_ir_detail
 *   2. Kurangi sisa inventory_retur (jika belum dikurangi saat simpan)
 *
 * Method : POST
 * Auth   : ?encrypt=md5(date('Y-m-d').'#'.key_apl)
 *
 * POST body:
 *   action          : 'save' | 'approve' | 'reject'
 *   no_tir          : nomor transfer dari app tujuan (sebagai ref)
 *   keterangan      : keterangan transfer
 *   tujuan_apl      : id_apl app tujuan
 *   tujuan_nama     : nama app tujuan
 *   approved_by     : admin yang approve (di app tujuan)
 *   items           : JSON array [{id_i_r, id_pro, no_bcode, tgl_expired, gudang, jumlah}]
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
	exit;
}

require_once('../config/connection/connection.php');
require_once('../config/connection/security.php');
require_once('../config/function/data.php');

$secu = new Security;
$base = new DB;
$data = new Data;
$conn = $base->open();

// Validasi encrypt
$encrypt   = $secu->injection(@$_GET['encrypt'] ?? '');
$tgl       = date('Y-m-d');
$source    = $data->self_apl();
$sourceKey = $source['key_apl'];

if (md5($tgl . '#' . $sourceKey) !== $encrypt) {
	http_response_code(401);
	echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
	exit;
}

$action       = $secu->injection(@$_POST['action']      ?? '');
$no_tir_ref   = $secu->injection(@$_POST['no_tir']      ?? '');
$keterangan   = $secu->injection(@$_POST['keterangan']  ?? '');
$tujuan_apl   = $secu->injection(@$_POST['tujuan_apl']  ?? '');
$tujuan_nama  = $secu->injection(@$_POST['tujuan_nama'] ?? '');
$approved_by  = $secu->injection(@$_POST['approved_by'] ?? '');
$itemsRaw     = @$_POST['items'] ?? '[]';
$items        = json_decode($itemsRaw, true);
$catat        = date('Y-m-d H:i:s');

if (!in_array($action, ['save', 'approve', 'reject'])) {
	echo json_encode(['status' => 'error', 'message' => 'Action tidak valid.']);
	exit;
}

// -------------------------------------------------------
// SAVE: Catat history keluar + kurangi inventory_retur
// -------------------------------------------------------
if ($action === 'save') {
	if (!is_array($items) || count($items) === 0) {
		echo json_encode(['status' => 'error', 'message' => 'Tidak ada item.']);
		exit;
	}

	// Generate nomor TIR lokal (di sisi app sumber = keluar)
	$qNo  = $conn->prepare("SELECT COUNT(*) FROM transfer_ir WHERE DATE(created_at)=CURDATE()");
	$qNo->execute();
	$urut = (int)$qNo->fetchColumn() + 1;
	$no_tir_local = 'TIR' . date('Ymd') . str_pad($urut, 4, '0', STR_PAD_LEFT);

	try {
		$conn->beginTransaction();

		// Insert header
		$ins = $conn->prepare("INSERT INTO transfer_ir
			(no_tir, keterangan, status_tir, sumber_apl, tujuan_apl, ref_tir_remote, created_at, created_by, submitted_at, submitted_by)
			VALUES (:no_tir, :ket, 'approved', :sumber, :tujuan, :ref, :catat, :admin, :catat2, :admin2)");
		$sumber_id = $source['id_apl'];
		$ins->bindParam(':no_tir',  $no_tir_local, PDO::PARAM_STR);
		$ins->bindParam(':ket',     $keterangan,   PDO::PARAM_STR);
		$ins->bindParam(':sumber',  $sumber_id,    PDO::PARAM_STR);
		$ins->bindParam(':tujuan',  $tujuan_apl,   PDO::PARAM_STR);
		$ins->bindParam(':ref',     $no_tir_ref,   PDO::PARAM_STR);
		$ins->bindParam(':catat',   $catat,        PDO::PARAM_STR);
		$ins->bindParam(':admin',   $tujuan_nama,  PDO::PARAM_STR); // dicatat sebagai "dikirim ke app X"
		$ins->bindParam(':catat2',  $catat,        PDO::PARAM_STR);
		$ins->bindParam(':admin2',  $approved_by,  PDO::PARAM_STR);
		$ins->execute();
		$id_tir = (int)$conn->lastInsertId();

		// Insert detail + kurangi inventory_retur
		$insD = $conn->prepare("INSERT INTO transfer_ir_detail
			(id_tir, id_psd, id_pro, no_bcode, tgl_expired, gudang, jumlah, created_at, created_by)
			VALUES (:id_tir, :id_psd, :id_pro, :bcode, :expired, :gudang, :jumlah, :catat, :admin)");
		$updIR = $conn->prepare("UPDATE inventory_retur
			SET keluar=keluar+:jml, sisa=sisa-:jml, updated_at=:catat, updated_by=:admin
			WHERE id_i_r=:id AND sisa>=:jml2");

		foreach ($items as $it) {
			$id_i_r  = (int)($it['id_i_r']      ?? 0);
			$id_pro  = $secu->injection($it['id_pro']      ?? '');
			$bcode   = $secu->injection($it['no_bcode']    ?? '');
			$expired = $secu->injection($it['tgl_expired'] ?? '');
			$gudang  = $secu->injection($it['gudang']      ?? '');
			$jumlah  = (int)($it['jumlah']       ?? 0);

			if ($id_i_r < 1 || $jumlah < 1) continue;

			// Kurangi inventory_retur
			$updIR->bindParam(':jml',   $jumlah, PDO::PARAM_INT);
			$updIR->bindParam(':jml2',  $jumlah, PDO::PARAM_INT);
			$updIR->bindParam(':catat', $catat,  PDO::PARAM_STR);
			$updIR->bindParam(':admin', $approved_by, PDO::PARAM_STR);
			$updIR->bindParam(':id',    $id_i_r, PDO::PARAM_INT);
			$updIR->execute();

			if ($updIR->rowCount() === 0) {
				throw new Exception('Stok inventory_retur tidak mencukupi atau tidak ditemukan (id_i_r=' . $id_i_r . ').');
			}

			// Insert detail
			$insD->bindParam(':id_tir',  $id_tir,  PDO::PARAM_INT);
			$insD->bindParam(':id_psd',  $id_i_r,  PDO::PARAM_INT);
			$insD->bindParam(':id_pro',  $id_pro,  PDO::PARAM_STR);
			$insD->bindParam(':bcode',   $bcode,   PDO::PARAM_STR);
			$insD->bindParam(':expired', $expired, PDO::PARAM_STR);
			$insD->bindParam(':gudang',  $gudang,  PDO::PARAM_STR);
			$insD->bindParam(':jumlah',  $jumlah,  PDO::PARAM_INT);
			$insD->bindParam(':catat',   $catat,   PDO::PARAM_STR);
			$insD->bindParam(':admin',   $approved_by, PDO::PARAM_STR);
			$insD->execute();
		}

		$conn->commit();
		echo json_encode([
			'status'       => 'ok',
			'message'      => 'History transfer berhasil dicatat.',
			'no_tir_local' => $no_tir_local,
			'id_tir'       => $id_tir,
		]);

	} catch (Exception $e) {
		$conn->rollBack();
		echo json_encode(['status' => 'error', 'message' => 'Gagal: ' . $e->getMessage()]);
	}
	exit;
}

// -------------------------------------------------------
// REJECT: Kembalikan stok di sisi app sumber
// -------------------------------------------------------
if ($action === 'reject') {
	if (!is_array($items) || count($items) === 0) {
		echo json_encode(['status' => 'error', 'message' => 'Tidak ada item.']);
		exit;
	}

	try {
		$conn->beginTransaction();

		$restoreIR = $conn->prepare("UPDATE inventory_retur
			SET keluar=keluar-:jml, sisa=sisa+:jml, updated_at=:catat, updated_by=:admin
			WHERE id_i_r=:id");

		foreach ($items as $it) {
			$id_i_r = (int)($it['id_i_r'] ?? 0);
			$jumlah = (int)($it['jumlah'] ?? 0);
			if ($id_i_r < 1 || $jumlah < 1) continue;

			$restoreIR->bindParam(':jml',   $jumlah,     PDO::PARAM_INT);
			$restoreIR->bindParam(':catat', $catat,      PDO::PARAM_STR);
			$restoreIR->bindParam(':admin', $approved_by, PDO::PARAM_STR);
			$restoreIR->bindParam(':id',    $id_i_r,     PDO::PARAM_INT);
			$restoreIR->execute();
		}

		// Update status transfer_ir lokal jika ada ref
		if ($no_tir_ref !== '') {
			$updStatus = $conn->prepare("UPDATE transfer_ir SET status_tir='rejected', approved_at=:at, approved_by=:by, updated_at=:catat WHERE ref_tir_remote=:ref OR no_tir=:ref2");
			$updStatus->bindParam(':at',   $catat,      PDO::PARAM_STR);
			$updStatus->bindParam(':by',   $approved_by, PDO::PARAM_STR);
			$updStatus->bindParam(':catat',$catat,      PDO::PARAM_STR);
			$updStatus->bindParam(':ref',  $no_tir_ref, PDO::PARAM_STR);
			$updStatus->bindParam(':ref2', $no_tir_ref, PDO::PARAM_STR);
			$updStatus->execute();
		}

		$conn->commit();
		echo json_encode(['status' => 'ok', 'message' => 'Stok dikembalikan ke inventory retur.']);

	} catch (Exception $e) {
		$conn->rollBack();
		echo json_encode(['status' => 'error', 'message' => 'Gagal: ' . $e->getMessage()]);
	}
	exit;
}
?>
