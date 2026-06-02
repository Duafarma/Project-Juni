<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(["error" => "Method Not Allowed"]);
    exit;
}

require_once('../config/connection/connection.php');
require_once('../config/connection/security.php');
require_once('../config/function/data.php');

$secu = new Security;
$base = new DB;
$data = new Data;

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$periode = isset($_POST['periode']) ? trim($_POST['periode']) : '';
$encrypt = isset($_POST['encrypt']) ? $secu->injection($_POST['encrypt']) : '';
$id_apl = isset($_POST['id_apl']) ? $secu->injection($_POST['id_apl']) : '';
$special_mode = isset($_POST['special_mode']) ? trim($_POST['special_mode']) : '';
$search = isset($_POST['search']) ? trim($_POST['search']) : '';
$page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
$limit = isset($_POST['limit']) ? max(1, min(500, intval($_POST['limit']))) : 100;

if (empty($periode) || !preg_match('/^\d{4}-\d{2}$/', $periode)) {
    http_response_code(400);
    echo json_encode(["error" => "Parameter 'periode' wajib dalam format YYYY-MM"]);
    exit;
}

if (empty($encrypt) || empty($id_apl)) {
    http_response_code(400);
    echo json_encode(["error" => "Parameter 'encrypt' dan 'id_apl' wajib diisi"]);
    exit;
}

list($year, $month) = explode('-', $periode);
$year = intval($year);
$month = intval($month);
$offset = ($page - 1) * $limit;

try {
    $conn = $base->open();

    $apl = null;
    $stmtApl = $conn->prepare(
        "SELECT id_apl, key_apl, base_url_apl, nama_apl
         FROM aplikasi
         WHERE id_apl = :id_apl AND active_apl = 1
         LIMIT 1"
    );
    $stmtApl->bindParam(':id_apl', $id_apl, PDO::PARAM_STR);
    $stmtApl->execute();
    $apl = $stmtApl->fetch(PDO::FETCH_ASSOC);

    if (!$apl) {
        $stmtSelf = $conn->query(
            "SELECT id_apl, key_apl, base_url_apl, nama_apl
             FROM aplikasi
             WHERE active_apl = 1
             ORDER BY id_apl ASC
             LIMIT 1"
        );
        $apl = $stmtSelf ? $stmtSelf->fetch(PDO::FETCH_ASSOC) : null;
    }

    if (!$apl) {
        http_response_code(404);
        echo json_encode(["error" => "Konfigurasi aplikasi (key) tidak ditemukan di cabang"]);
        exit;
    }

    $sourceKey = $apl['key_apl'];

    if (md5($periode . "#" . $sourceKey) !== $encrypt) {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized - Invalid encryption"]);
        exit;
    }

    if ($special_mode === 'malang_b') {
        $tukarFakturSourceSql = "
            SELECT id_tfk, kode_tfk, tgl_tfk, id_out, status_dokumen
            FROM transaksi_faktur_np_malang
            UNION ALL
            SELECT id_tfk, kode_tfk, tgl_tfk, id_out, status_dokumen
            FROM transaksi_faktur_np_malang_b
            UNION ALL
            SELECT id_tfk, kode_tfk, tgl_tfk, id_out, status_dokumen
            FROM transaksi_faktur_np_malang_c
        ";
    } elseif ($special_mode === 'medan_b') {
        $tukarFakturSourceSql = "
            SELECT id_tfk, kode_tfk, tgl_tfk, id_out, status_dokumen
            FROM transaksi_faktur_np_medan
            UNION ALL
            SELECT id_tfk, kode_tfk, tgl_tfk, id_out, status_dokumen
            FROM transaksi_faktur_b_medan
            UNION ALL
            SELECT id_tfk, kode_tfk, tgl_tfk, id_out, status_dokumen
            FROM transaksi_faktur_c_medan
        ";
    } else {
        $tukarFakturSourceSql = "
            SELECT id_tfk, kode_tfk, tgl_tfk, id_out, status_balik AS status_dokumen
            FROM transaksi_faktur
            UNION ALL
            SELECT id_tfk, kode_tfk, tgl_tfk, id_out, status_balik AS status_dokumen
            FROM transaksi_faktur_pim
        ";
    }

    $searchSql = '';
    $params = [':m' => $month, ':y' => $year];
    if ($search !== '') {
        $searchSql = " AND (
            tf.kode_tfk LIKE :search OR
            CAST(tf.id_tfk AS CHAR) LIKE :search OR
            COALESCE(o.nama_out, '') LIKE :search
        )";
        $params[':search'] = '%' . $search . '%';
    }

    $countSql = "
        SELECT COUNT(*)
        FROM (
            SELECT DISTINCT id_tfk
            FROM transaksi_faktur_kirim_f
            WHERE status_tfkkf = 'Sudah Dikirim'
              AND MONTH(created_at)=:m
              AND YEAR(created_at)=:y
        ) kirim
        INNER JOIN (
            {$tukarFakturSourceSql}
        ) tf ON tf.id_tfk = kirim.id_tfk
        LEFT JOIN outlet o ON o.id_out = tf.id_out
        WHERE TRIM(LOWER(COALESCE(tf.status_dokumen, ''))) = 'belum balik'
        {$searchSql}
    ";
    $stmtCount = $conn->prepare($countSql);
    foreach ($params as $key => $value) {
        $stmtCount->bindValue($key, $value, PDO::PARAM_STR);
    }
    $stmtCount->execute();
    $total = intval($stmtCount->fetchColumn() ?? 0);

    $dataSql = "
        SELECT tf.id_tfk, tf.kode_tfk, tf.tgl_tfk, COALESCE(o.nama_out, '-') AS nama_out,
               DATEDIFF(CURDATE(), DATE(tf.tgl_tfk)) AS umur_hari
        FROM (
            SELECT DISTINCT id_tfk
            FROM transaksi_faktur_kirim_f
            WHERE status_tfkkf = 'Sudah Dikirim'
              AND YEAR(created_at)=:y
        ) kirim
        INNER JOIN (
            {$tukarFakturSourceSql}
        ) tf ON tf.id_tfk = kirim.id_tfk
        LEFT JOIN outlet o ON o.id_out = tf.id_out
        WHERE TRIM(LOWER(COALESCE(tf.status_dokumen, ''))) = 'belum balik'
        {$searchSql}
        ORDER BY tf.tgl_tfk ASC
        LIMIT :limit OFFSET :offset
    ";
    $stmtData = $conn->prepare($dataSql);
    $stmtData->bindValue(':y', $year, PDO::PARAM_STR);
    if ($search !== '') {
        $stmtData->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
    }
    $stmtData->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmtData->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmtData->execute();
    $rows = $stmtData->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'data' => $rows,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'special_mode' => $special_mode,
        'periode_requested' => $periode,
        'timestamp' => date('c'),
    ]);
} catch (Exception $e) {
    error_log('getTukarFakturTerlama Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Database error"]);
} finally {
    if (isset($base) && method_exists($base, 'close')) {
        $base->close();
    }
}
?>