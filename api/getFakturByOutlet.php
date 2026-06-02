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

// Params
$tgl       = isset($_POST['tgl']) ? trim($_POST['tgl']) : '';
$key       = isset($_POST['key']) ? trim($_POST['key']) : '';
$nama_out  = isset($_POST['nama_out']) ? trim($_POST['nama_out']) : '';
$ofcode_out = isset($_POST['ofcode_out']) ? trim($_POST['ofcode_out']) : ''; // NEW
$encrypt   = isset($_POST['encrypt']) ? $secu->injection($_POST['encrypt']) : '';
$id_apl    = isset($_POST['id_apl']) ? $secu->injection($_POST['id_apl']) : '';
$aggregate = isset($_POST['aggregate']) ? (int)$_POST['aggregate'] : 1; // 1 = gabung eksternal, 0 = lokal saja

// NEW: mode filter
$byOutletOnly = isset($_POST['by_outlet_only']) ? (int)$_POST['by_outlet_only'] : 0;

// NEW: limit result (biar tidak berat)
$limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 300;
if ($limit < 1) $limit = 1;
if ($limit > 1000) $limit = 1000;

if (empty($encrypt) || empty($id_apl)) {
    http_response_code(400);
    echo json_encode(["error" => "Parameter 'encrypt' dan 'id_apl' wajib diisi"]);
    exit;
}

/**
 * Catatan:
 * tgl tetap divalidasi karena dipakai untuk skema encrypt md5(tgl#key_apl),
 * tapi kalau by_outlet_only=1, tgl TIDAK dipakai untuk filter query.
 */
if (empty($tgl) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl)) {
    http_response_code(400);
    echo json_encode(["error" => "Format tanggal tidak valid. Gunakan YYYY-MM-DD"]);
    exit;
}

$date_obj = DateTime::createFromFormat('Y-m-d', $tgl);
if (!$date_obj || $date_obj->format('Y-m-d') !== $tgl) {
    http_response_code(400);
    echo json_encode(["error" => "Tanggal tidak valid"]);
    exit;
}

if (empty($nama_out) && empty($ofcode_out)) {
    http_response_code(400);
    echo json_encode(["error" => "Parameter 'nama_out' atau 'ofcode_out' wajib diisi"]);
    exit;
}

function postJson($url, array $postFields, $timeoutSeconds = 8) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST            => true,
        CURLOPT_POSTFIELDS      => http_build_query($postFields),
        CURLOPT_RETURNTRANSFER  => true,
        CURLOPT_CONNECTTIMEOUT  => $timeoutSeconds,
        CURLOPT_TIMEOUT         => $timeoutSeconds,
        CURLOPT_SSL_VERIFYPEER  => false,
        CURLOPT_SSL_VERIFYHOST  => false,
        CURLOPT_HTTPHEADER      => ['Accept: application/json'],
    ]);
    $body = curl_exec($ch);
    $err  = curl_error($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false) {
        return ['ok' => false, 'code' => 0, 'error' => $err ?: 'cURL error', 'json' => null];
    }

    $json = json_decode($body, true);
    if (!is_array($json)) {
        return ['ok' => false, 'code' => $code, 'error' => 'Invalid JSON response', 'json' => null];
    }

    return ['ok' => ($code >= 200 && $code < 300), 'code' => $code, 'error' => null, 'json' => $json];
}

try {
    $conn = $base->open();

    // Ambil data aplikasi (self) untuk validasi encrypt
    $stmtApl = $conn->prepare("SELECT id_apl, key_apl, base_url_apl, nama_apl
                               FROM aplikasi
                               WHERE id_apl = :id_apl AND active_apl = 1");
    $stmtApl->bindParam(':id_apl', $id_apl, PDO::PARAM_STR);
    $stmtApl->execute();
    $apl = $stmtApl->fetch(PDO::FETCH_ASSOC);

    if (!$apl) {
        http_response_code(404);
        echo json_encode(["error" => "Aplikasi tidak ditemukan atau tidak aktif"]);
        exit;
    }

    $sourceKey = $apl['key_apl'];
    $selfName  = $apl['nama_apl'];

    // Skema encrypt sama seperti getFaktur.php lama: md5(tgl#key_apl)
    if (md5($tgl . "#" . $sourceKey) !== $encrypt) {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized - Invalid encryption"]);
        exit;
    }

    // --- Query lokal berdasarkan outlet (+ optional tanggal) ---
    $whereKey = "";
    $params = [];

    if (!empty($key)) {
        $whereKey = " AND (A.kode_tfk LIKE :key)";
        $params[':key'] = '%' . $key . '%';
    }

    $whereTanggal = "";
    if ($byOutletOnly !== 1) {
        $whereTanggal = " AND DATE(A.tgl_tfk) = :tgl ";
        $params[':tgl'] = $tgl;
    }

    // NEW: filter outlet prioritas ofcode_out, fallback nama_out
    $whereOutlet = "";
    if (!empty($ofcode_out)) {
        $whereOutlet = " AND B.ofcode_out = :ofcode_out ";
        $params[':ofcode_out'] = $ofcode_out;
    } else {
        $whereOutlet = " AND B.nama_out = :nama_out ";
        $params[':nama_out'] = $nama_out;
    }

    // NEW: exclude already documented faktur
    $whereDok = " AND (LOWER(TRIM(COALESCE(A.status_dokumentasi, ''))) <> 'sudah siap') ";

    $queryLocal = "
        SELECT
            'local' AS source,
            A.id_tfk,
            A.kode_tfk,
            A.total_tfk,
            A.id_out,
            A.tgl_tfk,
            A.created_at,
            B.nama_out,
            B.ofcode_out,
            'Cendo' AS jenis_faktur,
            :self_name AS nama_apl
        FROM transaksi_faktur AS A
        LEFT JOIN outlet AS B ON A.id_out = B.id_out
        WHERE A.id_tfk IS NOT NULL
          {$whereTanggal}
          {$whereOutlet}
          {$whereDok}
          {$whereKey}

        UNION ALL

        SELECT
            'local' AS source,
            A.id_tfk,
            A.kode_tfk,
            A.total_tfk,
            A.id_out,
            A.tgl_tfk,
            A.created_at,
            B.nama_out,
            B.ofcode_out,
            'PIM' AS jenis_faktur,
            :self_name AS nama_apl
        FROM transaksi_faktur_pim AS A
        LEFT JOIN outlet AS B ON A.id_out = B.id_out
        WHERE A.id_tfk IS NOT NULL
          {$whereTanggal}
          {$whereOutlet}
          {$whereDok}
          {$whereKey}

        ORDER BY created_at DESC
        LIMIT {$limit}
    ";

    $stmt = $conn->prepare($queryLocal);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v, PDO::PARAM_STR);
    }
    $stmt->bindValue(':self_name', $selfName, PDO::PARAM_STR);
    $stmt->execute();
    $local = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $external = [];
    $errors = [];

    // --- Gabung dari aplikasi lain (opsional) ---
    if ($aggregate === 1) {
        $stmtOther = $conn->prepare("SELECT id_apl, key_apl, base_url_apl, nama_apl
                                     FROM aplikasi
                                     WHERE active_apl = 1 AND id_apl <> :self_id");
        $stmtOther->bindParam(':self_id', $id_apl, PDO::PARAM_STR);
        $stmtOther->execute();
        $others = $stmtOther->fetchAll(PDO::FETCH_ASSOC);

        foreach ($others as $o) {
            $baseUrl = rtrim((string)$o['base_url_apl'], '/');
            if ($baseUrl === '') continue;

            // Endpoint di cabang lain harus ada juga file ini
            $url = $baseUrl . '/api/getFakturByOutlet.php';

            $remoteEncrypt = md5($tgl . "#" . $o['key_apl']);

            $resp = postJson($url, [
                'tgl'            => $tgl,
                'key'            => $key,
                'nama_out'       => $nama_out,
                'ofcode_out'     => $ofcode_out, // NEW: teruskan
                'encrypt'        => $remoteEncrypt,
                'id_apl'         => $o['id_apl'],
                'aggregate'      => 0,
                'by_outlet_only' => $byOutletOnly,
                'limit'          => $limit,
            ]);

            if (!$resp['ok']) {
                $errors[] = [
                    'nama_apl' => $o['nama_apl'],
                    'base_url' => $baseUrl,
                    'http'     => $resp['code'],
                    'error'    => $resp['error'] ?: 'Request failed',
                ];
                continue;
            }

            $remoteRows = isset($resp['json']['result']) && is_array($resp['json']['result'])
                ? $resp['json']['result']
                : [];

            // Pastikan source kebaca "api"
            foreach ($remoteRows as &$r) {
                $r['source'] = 'api';
            }
            unset($r);

            $external = array_merge($external, $remoteRows);
        }
    }

    $merged = array_merge($local, $external);

    echo json_encode([
        "result"           => $merged,
        "filters"          => ["tgl" => $tgl, "nama_out" => $nama_out, "key" => $key, "by_outlet_only" => $byOutletOnly, "limit" => $limit],
        "total_records"    => count($merged),
        "local_records"    => count($local),
        "external_records" => count($external),
        "errors"           => $errors,
    ]);

} catch (Exception $e) {
    error_log("API Error getFakturByOutlet: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Server error: " . $e->getMessage()]);
} finally {
    if (isset($conn)) $base->close();
}
?>