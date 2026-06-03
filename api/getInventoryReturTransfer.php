<?php
/**
 * API: Get Inventory Retur untuk Transfer ke Penjualan
 * Dipanggil oleh aplikasi lain via cURL (proxy)
 * Auth: md5(date('Y-m-d') . '#' . key_apl)
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

$cari = $secu->injection(@$_POST['cari'] ?? '');

$whereExtra = '';
$like = null;
if ($cari !== '') {
	$like = '%' . $cari . '%';
	$whereExtra = 'AND (P.nama_pro LIKE :cari OR IR.no_bcode LIKE :cari)';
}

$sql = "
	SELECT
		IR.id_i_r,
		IR.id_pro,
		IR.no_bcode,
		IR.ed         AS tgl_expired,
		IR.gudang,
		IR.sisa,
		P.nama_pro,
		P.berat_pro,
		S.nama_spr
	FROM inventory_retur IR
	LEFT JOIN produk P         ON IR.id_pro = P.id_pro
	LEFT JOIN satuan_produk S  ON P.id_spr  = S.id_spr
	WHERE IR.sisa > 0
	  AND P.id_pro IS NOT NULL
	  $whereExtra
	ORDER BY P.nama_pro, IR.ed
	LIMIT 200
";

$q = $conn->prepare($sql);
if ($like !== null) {
	$q->bindValue(':cari', $like);
}
$q->execute();
$rows = $q->fetchAll(PDO::FETCH_ASSOC);

$out = [];
foreach ($rows as $r) {
	$satuan = trim(($r['berat_pro'] ?? '') . ' ' . ($r['nama_spr'] ?? ''));
	$out[] = [
		'id_i_r'      => (int)$r['id_i_r'],
		'id_pro'      => $r['id_pro'],
		'nama_pro'    => $r['nama_pro'] . ($satuan ? ' ' . $satuan : ''),
		'no_bcode'    => $r['no_bcode'],
		'tgl_expired' => $r['tgl_expired'],
		'gudang'      => $r['gudang'],
		'sisa'        => (int)$r['sisa'],
	];
}

http_response_code(200);
echo json_encode(['status' => 'ok', 'data' => $out]);
?>
