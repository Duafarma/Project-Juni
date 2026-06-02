<?php
/**
 * API Endpoint: getPenjualanCashback
 * Menyediakan data transaksi_faktur + pembayaran_faktur untuk cabang lain.
 * Endpoint ini di-deploy di semua cabang agar bisa saling pull data.
 *
 * Auth: POST id_apl + encrypt = md5(date('Y-m-d') . '#' . key_apl_target)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once(__DIR__ . '/../config/connection/connection.php');
require_once(__DIR__ . '/../config/connection/security.php');
require_once(__DIR__ . '/../config/function/data.php');

$secu = new Security;
$base = new DB;
$data = new Data;

function logFakturApi(string $message, array $ctx = []): void {
    $dir = __DIR__ . '/../logs';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    @file_put_contents(
        $dir . '/api_penjualan_cashback.log',
        json_encode(['time' => date('Y-m-d H:i:s'), 'msg' => $message, 'ctx' => $ctx,
                     'ip'   => $_SERVER['REMOTE_ADDR'] ?? '']) . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}

function tableExistsFp(PDO $conn, string $table): bool {
    try {
        $q = $conn->prepare("SHOW TABLES LIKE :t");
        $q->bindValue(':t', $table, PDO::PARAM_STR);
        $q->execute();
        return (bool)$q->fetch(PDO::FETCH_NUM);
    } catch (Throwable $e) {
        return false;
    }
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'method_not_allowed']);
        exit;
    }

    $tgl     = date('Y-m-d');
    $idApl   = trim($secu->injection($_POST['id_apl']   ?? ''));
    $encrypt = trim($secu->injection($_POST['encrypt']   ?? ''));
    $tglFrom  = trim($secu->injection($_POST['tgl_from']  ?? ''));
    $tglTo    = trim($secu->injection($_POST['tgl_to']    ?? ''));
    $idOut    = trim($secu->injection($_POST['id_out']    ?? ''));
    // Tidak ada batasan jumlah data — ambil semua

    if (empty($idApl) || empty($encrypt)) {
        http_response_code(400);
        echo json_encode(['error' => 'bad_request', 'detail' => 'id_apl dan encrypt wajib diisi']);
        exit;
    }

    $conn = $base->open();

    // Ambil self key untuk validasi
    $selfApl = $data->self_apl();
    if (!$selfApl) {
        logFakturApi('self_apl tidak ditemukan');
        http_response_code(500);
        echo json_encode(['error' => 'server_error', 'detail' => 'Konfigurasi aplikasi tidak ditemukan']);
        exit;
    }

    // Validasi encrypt: caller harus pakai md5(date + '#' + key_apl REMOTE/target ini)
    $expectedEncrypt = md5($tgl . '#' . $selfApl['key_apl']);
    if (!hash_equals($expectedEncrypt, $encrypt)) {
        logFakturApi('Enkripsi tidak valid', ['id_apl' => $idApl]);
        http_response_code(401);
        echo json_encode(['error' => 'unauthorized', 'detail' => 'Enkripsi tidak valid']);
        exit;
    }

    // Pastikan pengirim terdaftar
    $stmCaller = $conn->prepare("SELECT id_apl, nama_apl FROM aplikasi WHERE id_apl = :id AND active_apl = 1 LIMIT 1");
    $stmCaller->bindValue(':id', $idApl, PDO::PARAM_STR);
    $stmCaller->execute();
    $caller = $stmCaller->fetch(PDO::FETCH_ASSOC);
    if (!$caller) {
        logFakturApi('Pengirim tidak dikenal', ['id_apl' => $idApl]);
        http_response_code(401);
        echo json_encode(['error' => 'unauthorized', 'detail' => 'Aplikasi pengirim tidak dikenal']);
        exit;
    }

    logFakturApi('Request OK', ['caller' => $idApl, 'tgl_from' => $tglFrom, 'tgl_to' => $tglTo]);

    // Detect tabel
    $tfkSources = [];
    if (tableExistsFp($conn, 'transaksi_faktur'))
        $tfkSources[] = "SELECT id_tfk, kode_tfk, id_out, tgl_tfk, subtot_tfk, total_tfk FROM transaksi_faktur";
    if (tableExistsFp($conn, 'transaksi_faktur_pim'))
        $tfkSources[] = "SELECT id_tfk, kode_tfk, id_out, tgl_tfk, subtot_tfk, total_tfk FROM transaksi_faktur_pim";

    if (empty($tfkSources)) {
        $base->close();
        echo json_encode([
            'status'    => 'success',
            'id_apl'    => (string)$selfApl['id_apl'],
            'nama_apl'  => (string)$selfApl['nama_apl'],
            'count'     => 0,
            'data'      => [],
            'synced_at' => date('Y-m-d H:i:s'),
        ]);
        exit;
    }

    $tfkUnion = '(' . implode(' UNION ALL ', $tfkSources) . ')';

    // Build filter dinamis
    $whereParts = [];
    $bindParams = [];

    if ($tglFrom && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglFrom)) {
        $whereParts[] = "tfk.tgl_tfk >= :tgl_from";
        $bindParams[':tgl_from'] = $tglFrom;
    }
    if ($tglTo && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglTo)) {
        $whereParts[] = "tfk.tgl_tfk <= :tgl_to";
        $bindParams[':tgl_to'] = $tglTo;
    }
    if ($idOut !== '') {
        $whereParts[] = "tfk.id_out = :id_out";
        $bindParams[':id_out'] = $idOut;
    }

    $whereStr = empty($whereParts) ? '' : 'WHERE ' . implode(' AND ', $whereParts);

    // Cek pembayaran_faktur ada
    $hasPf = tableExistsFp($conn, 'pembayaran_faktur');
    $pfSelect = $hasPf
        ? "IF(MAX(pf.id_pfk) IS NOT NULL, 1, 0) AS ada_pfk,
           MAX(pf.jumlah_pfk) AS jumlah_pfk,
           MAX(pf.tgl_pfk)    AS tgl_pfk,
           MAX(pf.bank_pfk)   AS bank_pfk"
        : "0 AS ada_pfk, 0 AS jumlah_pfk, NULL AS tgl_pfk, '' AS bank_pfk";
    $pfJoin = $hasPf ? "LEFT JOIN pembayaran_faktur pf ON pf.id_tfk = tfk.id_tfk" : "";

    $sql = "
        SELECT
            tfk.id_tfk,
            COALESCE(tfk.kode_tfk, tfk.id_tfk) AS kode_tfk,
            tfk.id_out,
            COALESCE(o.nama_out, tfk.id_out) AS nama_out,
            tfk.tgl_tfk,
            COALESCE(tfk.subtot_tfk, 0) AS subtot_tfk,
            COALESCE(tfk.total_tfk, 0)  AS total_tfk,
            {$pfSelect}
        FROM {$tfkUnion} tfk
        LEFT JOIN outlet o ON o.id_out = tfk.id_out
        {$pfJoin}
        {$whereStr}
        GROUP BY tfk.id_tfk
        ORDER BY tfk.tgl_tfk DESC
    ";

    $stmt = $conn->prepare($sql);
    foreach ($bindParams as $k => $v) {
        $stmt->bindValue($k, $v, PDO::PARAM_STR);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = [];
    foreach ($rows as $r) {
        $result[] = [
            'id_tfk'     => (string)($r['id_tfk']     ?? ''),
            'kode_tfk'   => (string)($r['kode_tfk']   ?? ''),
            'id_out'     => (string)($r['id_out']      ?? ''),
            'nama_out'   => (string)($r['nama_out']    ?? ''),
            'tgl_tfk'    => (string)($r['tgl_tfk']     ?? ''),
            'subtot_tfk' => (float) ($r['subtot_tfk']  ?? 0),
            'total_tfk'  => (float) ($r['total_tfk']   ?? 0),
            'ada_pfk'    => (int)   ($r['ada_pfk']     ?? 0),
            'jumlah_pfk' => (float) ($r['jumlah_pfk']  ?? 0),
            'tgl_pfk'    => (string)($r['tgl_pfk']     ?? ''),
            'bank_pfk'   => (string)($r['bank_pfk']    ?? ''),
        ];
    }

    $base->close();

    logFakturApi('Response OK', ['caller' => $idApl, 'count' => count($result)]);
    http_response_code(200);
    echo json_encode([
        'status'    => 'success',
        'id_apl'    => (string)$selfApl['id_apl'],
        'nama_apl'  => (string)$selfApl['nama_apl'],
        'count'     => count($result),
        'has_more'  => false,
        'data'      => $result,
        'synced_at' => date('Y-m-d H:i:s'),
    ]);

} catch (Throwable $e) {
    logFakturApi('Exception', ['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['error' => 'server_error', 'detail' => $e->getMessage()]);
}
