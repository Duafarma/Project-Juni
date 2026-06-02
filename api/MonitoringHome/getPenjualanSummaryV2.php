<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); header('Content-Type: application/json');
    echo json_encode(["error"=>"Method Not Allowed"]); exit;
}

require_once('../../config/connection/connection.php');
require_once('../../config/connection/security.php');

$secu = new Security;
$base = new DB;

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

$periode = isset($_POST['periode']) ? trim($_POST['periode']) : '';
$encrypt = isset($_POST['encrypt']) ? $secu->injection($_POST['encrypt']) : '';
$id_apl  = isset($_POST['id_apl'])  ? $secu->injection($_POST['id_apl'])  : '';

if (empty($periode) || !preg_match('/^\d{4}-\d{2}$/', $periode) || $encrypt === '' || $id_apl === '') {
    http_response_code(400);
    echo json_encode(["error"=>"Parameter 'periode','encrypt','id_apl' wajib. Periode format YYYY-MM"]);
    exit;
}
list($year, $month) = explode('-', $periode);
$year = (int)$year; $month = (int)$month;

try {
    $conn = $base->open();

    $stmtApl = $conn->prepare("SELECT key_apl, nama_apl FROM aplikasi WHERE id_apl = :id_apl AND active_apl = 1");
    $stmtApl->execute([':id_apl' => $id_apl]);
    $apl = $stmtApl->fetch(PDO::FETCH_ASSOC);
    if (!$apl) { http_response_code(404); echo json_encode(["error"=>"Aplikasi tidak ditemukan"]); exit; }

    $sourceKey = $apl['key_apl'] ?? '';
    if (md5($periode . "#" . $sourceKey) !== $encrypt) {
        http_response_code(401); echo json_encode(["error"=>"Unauthorized - Invalid encryption"]); exit;
    }

    // Hitung dari header (subtot_tfk) dan header total (total_tfk) — konsisten dengan query lokal
    $stmt = $conn->prepare("
        SELECT
            COALESCE(COUNT(DISTINCT tf.id_out), 0) AS total_transaksi,
            COALESCE(SUM(tf.subtot_tfk), 0) AS total_sebelum_ppn,
            COALESCE(SUM(tf.total_tfk), 0) AS total_sesudah_ppn
        FROM (
            SELECT id_out, IFNULL(subtot_tfk,0) AS subtot_tfk, IFNULL(total_tfk,0) AS total_tfk,
                   CASE WHEN tgl_tfk IS NULL OR tgl_tfk='0000-00-00' THEN created_at ELSE tgl_tfk END AS tgl_eff
            FROM transaksi_faktur
            UNION ALL
            SELECT id_out, IFNULL(subtot_tfk,0) AS subtot_tfk, IFNULL(total_tfk,0) AS total_tfk,
                   CASE WHEN tgl_tfk IS NULL OR tgl_tfk='0000-00-00' THEN created_at ELSE tgl_tfk END AS tgl_eff
            FROM transaksi_faktur_c
        ) tf
        WHERE YEAR(tf.tgl_eff) = :y AND MONTH(tf.tgl_eff) = :m
    ");
    $stmt->execute([':y'=>$year, ':m'=>$month]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $result = [
        'periode' => $periode,
        'total_transaksi'   => (int)($row['total_transaksi'] ?? 0),
        'total_sebelum_ppn' => (float)($row['total_sebelum_ppn'] ?? 0),
        'total_sesudah_ppn' => (float)($row['total_sesudah_ppn'] ?? 0),
    ];

    echo json_encode(['result'=>$result, 'id_apl'=>$id_apl, 'nama_apl'=>$apl['nama_apl'] ?? '', 'timestamp'=>date('c')]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error'=>'Server error']);
} finally {
    if (isset($base) && method_exists($base,'close')) $base->close();
}