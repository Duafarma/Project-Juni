<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once(__DIR__ . '/../config/connection/connection.php');
require_once(__DIR__ . '/../config/connection/security.php');

$secu = new Security;
$base = new DB;

function logApi(string $message, array $context = []): void {
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
    $logFile = $logDir . '/api_cashback.log';
    $entry = [
        'time' => date('Y-m-d H:i:s'),
        'message' => $message,
        'context' => $context,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'post' => $_POST,
    ];
    @file_put_contents($logFile, json_encode($entry, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
}

try {
    // Validasi method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'method_not_allowed', 'detail' => 'Only POST method allowed']);
        exit;
    }

    // Ambil parameter POST
    $nama_out = trim($secu->injection($_POST['nama_out'] ?? ''));
    $encrypt  = trim($secu->injection($_POST['encrypt'] ?? ''));
    $id_apl   = trim($secu->injection($_POST['id_apl'] ?? ''));

    // Log request
    logApi('Request received', [
        'nama_out' => $nama_out,
        'id_apl' => $id_apl,
    ]);

    // Validasi parameter
    if (empty($nama_out)) {
        logApi('Missing nama_out');
        http_response_code(400);
        echo json_encode(['error' => 'bad_request', 'detail' => 'Parameter nama_out wajib diisi']);
        exit;
    }

    if (empty($encrypt)) {
        logApi('Missing encrypt');
        http_response_code(400);
        echo json_encode(['error' => 'bad_request', 'detail' => 'Parameter encrypt wajib diisi']);
        exit;
    }

    if (empty($id_apl)) {
        logApi('Missing id_apl');
        http_response_code(400);
        echo json_encode(['error' => 'bad_request', 'detail' => 'Parameter id_apl wajib diisi']);
        exit;
    }

    $conn = $base->open();

    // Query aplikasi dengan self_apl = 1 (ini yang punya database lokal remote ini)
    $stmtApl = $conn->prepare("
        SELECT id_apl, key_apl, nama_apl 
        FROM aplikasi 
        WHERE id_apl = :id_apl 
          AND self_apl = 1 
          AND active_apl = 1 
        LIMIT 1
    ");
    $stmtApl->bindValue(':id_apl', $id_apl, PDO::PARAM_STR);
    $stmtApl->execute();
    $aplData = $stmtApl->fetch(PDO::FETCH_ASSOC);

    if (!$aplData) {
        logApi('Invalid application ID or not self', ['id_apl' => $id_apl]);
        http_response_code(401);
        echo json_encode(['error' => 'unauthorized', 'detail' => 'Aplikasi tidak valid atau bukan self']);
        exit;
    }

    // Validasi enkripsi
    $expectedEncrypt = md5($nama_out . '#' . $aplData['key_apl']);
    if ($encrypt !== $expectedEncrypt) {
        logApi('Invalid encryption', [
            'nama_out' => $nama_out,
            'expected' => $expectedEncrypt,
            'received' => $encrypt
        ]);
        http_response_code(401);
        echo json_encode(['error' => 'unauthorized', 'detail' => 'Enkripsi tidak valid']);
        exit;
    }

    // Hitung range 3 bulan terakhir
    $firstOfThisMonth = new DateTime(date('Y-m-01'));
    $months = [];
    for ($i = 0; $i <= 2; $i++) {
        $d = clone $firstOfThisMonth;
        $d->modify("-{$i} month");
        $start = $d->format('Y-m-01');
        $endDt = (clone $d)->modify('last day of this month');
        $end = $endDt->format('Y-m-d');
        $months[$i] = ['start' => $start, 'end' => $end];
    }

    // Query total_tfk dari transaksi_faktur dan transaksi_faktur_pim per bulan
    // HANYA dari database remote (server ini)
    $q = $conn->prepare("
        SELECT
            o.nama_out,
            SUM(CASE WHEN DATE(g.created_at) BETWEEN :s0_date AND :e0_date THEN COALESCE(g.total_tfk,0) ELSE 0 END) AS total_m0,
            SUM(CASE WHEN DATE(g.created_at) BETWEEN :s1_date AND :e1_date THEN COALESCE(g.total_tfk,0) ELSE 0 END) AS total_m1,
            SUM(CASE WHEN DATE(g.created_at) BETWEEN :s2_date AND :e2_date THEN COALESCE(g.total_tfk,0) ELSE 0 END) AS total_m2
        FROM (
            SELECT id_out, created_at, total_tfk FROM transaksi_faktur
            UNION ALL
            SELECT id_out, created_at, total_tfk FROM transaksi_faktur_pim
        ) AS g
        LEFT JOIN outlet o ON g.id_out = o.id_out
        WHERE LOWER(TRIM(o.nama_out)) = LOWER(TRIM(:nama_out))
        GROUP BY o.nama_out
    ");

    $q->bindValue(':nama_out', $nama_out, PDO::PARAM_STR);
    $q->bindValue(':s0_date', $months[0]['start'], PDO::PARAM_STR);
    $q->bindValue(':e0_date', $months[0]['end'], PDO::PARAM_STR);
    $q->bindValue(':s1_date', $months[1]['start'], PDO::PARAM_STR);
    $q->bindValue(':e1_date', $months[1]['end'], PDO::PARAM_STR);
    $q->bindValue(':s2_date', $months[2]['start'], PDO::PARAM_STR);
    $q->bindValue(':e2_date', $months[2]['end'], PDO::PARAM_STR);
    $q->execute();

    $result = [];
    while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
        $result[] = [
            'nama_out' => (string)($row['nama_out'] ?? ''),
            'total_m0' => (float)($row['total_m0'] ?? 0),
            'total_m1' => (float)($row['total_m1'] ?? 0),
            'total_m2' => (float)($row['total_m2'] ?? 0),
            'source' => 'remote', // Menandakan data dari remote
            'nama_apl' => $aplData['nama_apl']
        ];
    }

    $base->close();

    // Jika tidak ada data, tetap return success dengan array kosong
    // (bukan 404, karena mungkin outlet tidak punya transaksi di remote)
    logApi('Success', ['nama_out' => $nama_out, 'count' => count($result)]);
    http_response_code(200);
    echo json_encode([
        'status' => 'success', 
        'result' => $result,
        'source' => 'remote',
        'nama_apl' => $aplData['nama_apl']
    ]);

} catch (Throwable $e) {
    logApi('Exception', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
    http_response_code(500);
    echo json_encode(['error' => 'server_error', 'detail' => 'Terjadi kesalahan server: ' . $e->getMessage()]);
}
?>