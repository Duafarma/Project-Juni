<?php
require_once('../config/connection/connection.php');
require_once('../config/connection/security.php');
require_once('../config/function/data.php');

header('Access-Control-Allow-Origin: *');
header("Content-type: application/json; charset=utf-8");
ini_set('display_errors', 0);

$secu = new Security;
$base = new DB;
$data = new Data;
$conn = $base->open();

$request  = ($_SERVER['REQUEST_METHOD'] === 'POST') ? $_POST : $_GET;
$tgl      = date('Y-m-d');
$cari     = $secu->injection(@$request['caridata']);
$page     = (int)($secu->injection(@$request['halaman']) ?: 1);
$maxi     = (int)($secu->injection(@$request['maximal']) ?: 15);
$encrypt  = $secu->injection(@$request['encrypt']);
$idAplReq = $secu->injection(@$request['id_apl']);
$paginate = isset($request['paginate']) ? (int)$secu->injection(@$request['paginate']) : 1;
$mulai    = ($page > 1) ? (($page * $maxi) - $maxi) : 0;

$source    = $data->self_apl();
$sourceKey = $source['key_apl'] ?? '';
$id_apl    = $source['id_apl'] ?? '';
$nama_apl  = $source['nama_apl'] ?? '';

// ─── Helpers ────────────────────────────────────────────────────────────────

function respondStockOpnameJson($payload, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode($payload);
}

function canDisplayStockOpname($currentDate) {
    return (int)date('j', strtotime($currentDate)) >= 25;
}

function isValidStockOpnameEncrypt($encrypt, $tgl, $sourceKey, $authSourceKey) {
    $candidates = array();
    if ($sourceKey !== '') {
        $candidates[] = md5($tgl . '#' . $sourceKey);
    }
    if ($authSourceKey !== '' && $authSourceKey !== $sourceKey) {
        $candidates[] = md5($tgl . '#' . $authSourceKey);
    }
    return in_array($encrypt, $candidates, true);
}

function resolveStockOpnameAuthSource($conn, $source, $requestedApl) {
    $requestedApl = trim((string)$requestedApl);
    if ($requestedApl === '' || strtolower($requestedApl) === 'all' || $requestedApl === (string)($source['id_apl'] ?? '')) {
        return $source;
    }
    $stmt = $conn->prepare('SELECT id_apl, nama_apl, key_apl, base_url_apl FROM aplikasi WHERE id_apl = :id_apl AND active_apl = 1 LIMIT 1');
    $stmt->bindValue(':id_apl', $requestedApl, PDO::PARAM_STR);
    $stmt->execute();
    $apl = $stmt->fetch(PDO::FETCH_ASSOC);
    return $apl ?: $source;
}

function fetchLocalStockOpname($conn, $sourceInfo, $cari, $offset = null, $limit = null) {
    $cariLike = '%' . $cari . '%';

    $qCount = "SELECT COUNT(A.id_psd) AS total
               FROM produk_stokdetail AS A
               LEFT JOIN produk AS B ON A.id_pro = B.id_pro
               WHERE A.sisa_psd <> A.qty_so
               AND B.nama_pro LIKE :cari";

    $stmtCount = $conn->prepare($qCount);
    $stmtCount->bindValue(':cari', $cariLike, PDO::PARAM_STR);
    $stmtCount->execute();
    $total = (int)($stmtCount->fetchColumn() ?? 0);

    $qMaster = "SELECT
                    :id_apl   AS id_apl,
                    :nama_apl AS nama_apl,
                    A.id_psd  AS id,
                    A.id_psd,
                    A.id_pro,
                    B.nama_pro,
                    A.gudang,
                    COALESCE(C.nama_inventory, A.gudang) AS nama_inventory,
                    A.no_bcode,
                    A.sisa_psd        AS qty,
                    COALESCE((
                        SELECT S.bcode_so
                        FROM stock AS S
                        WHERE S.id_psd = A.id_psd AND TRIM(S.bcode_so) <> ''
                        ORDER BY S.id DESC
                        LIMIT 1
                    ), A.no_bcode) AS bcode_so,
                    A.qty_so,
                    (A.qty_so - A.sisa_psd) AS selisih,
                    A.created_at,
                    A.status          AS status_psd
                FROM produk_stokdetail AS A
                LEFT JOIN produk AS B ON A.id_pro = B.id_pro
                LEFT JOIN master_inventory AS C ON A.gudang = C.id_inventory
                WHERE A.sisa_psd <> A.qty_so
                AND B.nama_pro LIKE :cari
                ORDER BY A.id_pro ASC";

    if ($offset !== null && $limit !== null) {
        $qMaster .= ' LIMIT :mulai, :maxi';
    }

    $stmt = $conn->prepare($qMaster);
    $stmt->bindValue(':id_apl',   $sourceInfo['id_apl'],   PDO::PARAM_STR);
    $stmt->bindValue(':nama_apl', $sourceInfo['nama_apl'], PDO::PARAM_STR);
    $stmt->bindValue(':cari',     $cariLike,               PDO::PARAM_STR);
    if ($offset !== null && $limit !== null) {
        $stmt->bindValue(':mulai', (int)$offset, PDO::PARAM_INT);
        $stmt->bindValue(':maxi',  (int)$limit,  PDO::PARAM_INT);
    }
    $stmt->execute();

    return array(
        'total' => $total,
        'data'  => $stmt->fetchAll(PDO::FETCH_ASSOC)
    );
}

function resolveBranchBaseUrlStockOpname($branch) {
    $baseUrl = trim($branch['base_url_apl'] ?? '');
    if ($baseUrl !== '') {
        return rtrim($baseUrl, '/');
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . '/monitoring';
}

function callStockOpnameBranchApi($branch, $payload, $method = 'GET') {
    $apiUrl = resolveBranchBaseUrlStockOpname($branch) . '/api/getStockOpnameAp.php';
    $method = strtoupper($method);

    if ($method === 'GET') {
        $apiUrl .= '?' . http_build_query($payload);
    }

    if (function_exists('curl_init')) {
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        }

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($response === false || $error !== '') {
            return array('ok' => false, 'message' => $error ?: 'Curl request gagal', 'http_code' => $httpCode);
        }

        $json = json_decode($response, true);
        if (!is_array($json)) {
            return array('ok' => false, 'message' => 'Response bukan JSON valid', 'http_code' => $httpCode, 'body' => $response);
        }

        return array('ok' => ($httpCode >= 200 && $httpCode < 300), 'http_code' => $httpCode, 'json' => $json, 'body' => $response);
    }

    // Fallback: file_get_contents
    $options = array(
        'http' => array(
            'method'        => $method,
            'timeout'       => 20,
            'ignore_errors' => true,
            'header'        => "Content-Type: application/x-www-form-urlencoded\r\nAccept: application/json\r\n"
        )
    );
    if ($method === 'POST') {
        $options['http']['content'] = http_build_query($payload);
    }

    $context  = stream_context_create($options);
    $response = @file_get_contents($apiUrl, false, $context);
    if ($response === false) {
        return array('ok' => false, 'message' => 'HTTP request gagal');
    }

    $httpCode = 200;
    if (!empty($http_response_header) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $match)) {
        $httpCode = (int)$match[1];
    }

    $json = json_decode($response, true);
    if (!is_array($json)) {
        return array('ok' => false, 'message' => 'Response bukan JSON valid', 'http_code' => $httpCode, 'body' => $response);
    }

    return array('ok' => ($httpCode >= 200 && $httpCode < 300), 'http_code' => $httpCode, 'json' => $json, 'body' => $response);
}

function resolveStockOpnameRemoteMessage($remoteResponse) {
    if (!empty($remoteResponse['message'])) {
        return $remoteResponse['message'];
    }
    if (!empty($remoteResponse['json']['message'])) {
        return $remoteResponse['json']['message'];
    }
    return 'HTTP ' . ($remoteResponse['http_code'] ?? 0);
}

// ─── Validate encrypt ────────────────────────────────────────────────────────

$authSource    = resolveStockOpnameAuthSource($conn, $source, $idAplReq);
$authSourceKey = $authSource['key_apl'] ?? $sourceKey;

if (!isValidStockOpnameEncrypt($encrypt, $tgl, $sourceKey, $authSourceKey)) {
    respondStockOpnameJson(array(
        'status'        => 'error',
        'message'       => 'Unauthorized: encrypt tidak valid',
        'self_id_apl'   => $id_apl,
        'self_nama_apl' => $nama_apl,
        'id_apl'        => $authSource['id_apl']   ?? $id_apl,
        'nama_apl'      => $authSource['nama_apl'] ?? $nama_apl
    ), 401);
    $conn = $base->close();
    exit;
}

// ─── Dispatch ────────────────────────────────────────────────────────────────

try {
    $requestedApl = trim($idAplReq);

    if (!canDisplayStockOpname($tgl)) {
        $responseIdApl = ($requestedApl !== '') ? $requestedApl : $id_apl;
        $responseNamaApl = (strtolower($requestedApl) === 'all') ? 'Semua Cabang' : $nama_apl;

        respondStockOpnameJson(array(
            'status'   => 'success',
            'id_apl'   => $responseIdApl,
            'nama_apl' => $responseNamaApl,
            'total'    => 0,
            'halaman'  => $page,
            'maximal'  => $maxi,
            'data'     => array(),
            'errors'   => array(),
            'message'  => 'Data stock opname baru tampil mulai tanggal 25 setiap bulan'
        ));
        $conn = $base->close();
        exit;
    }

    // ── SINGLE BRANCH READ ───────────────────────────────────────────────────
    if ($requestedApl !== '' && strtolower($requestedApl) !== 'all' && $requestedApl !== $id_apl) {
        $stmtBranch = $conn->prepare('SELECT id_apl, nama_apl, base_url_apl, key_apl FROM aplikasi WHERE id_apl = :id_apl AND active_apl = 1 LIMIT 1');
        $stmtBranch->bindValue(':id_apl', $requestedApl, PDO::PARAM_STR);
        $stmtBranch->execute();
        $branch = $stmtBranch->fetch(PDO::FETCH_ASSOC);

        if (!$branch) {
            respondStockOpnameJson(array('status' => 'error', 'message' => 'Cabang tidak ditemukan'), 404);
            $conn = $base->close();
            exit;
        }

        $remoteResponse = callStockOpnameBranchApi($branch, array(
            'encrypt'  => md5($tgl . '#' . $branch['key_apl']),
            'id_apl'   => $branch['id_apl'],
            'caridata' => $cari,
            'halaman'  => $page,
            'maximal'  => $maxi,
            'paginate' => $paginate
        ), 'GET');

        if (!$remoteResponse['ok']) {
            respondStockOpnameJson(array(
                'status'  => 'error',
                'message' => resolveStockOpnameRemoteMessage($remoteResponse)
            ), 502);
            $conn = $base->close();
            exit;
        }

        respondStockOpnameJson($remoteResponse['json'], $remoteResponse['http_code'] ?? 200);
        $conn = $base->close();
        exit;
    }

    // ── ALL BRANCHES ─────────────────────────────────────────────────────────
    if (strtolower($requestedApl) === 'all') {
        $stmtAplikasi = $conn->query('SELECT id_apl, nama_apl, self_apl, base_url_apl, key_apl FROM aplikasi WHERE active_apl = 1 ORDER BY nama_apl ASC');
        $aplikasiList = $stmtAplikasi ? $stmtAplikasi->fetchAll(PDO::FETCH_ASSOC) : array();

        $mergedRows = array();
        $errors     = array();

        foreach ($aplikasiList as $aplInfo) {
            if ((string)$aplInfo['id_apl'] === (string)$id_apl || (int)$aplInfo['self_apl'] === 1) {
                $localResult = fetchLocalStockOpname($conn, array('id_apl' => $id_apl, 'nama_apl' => $nama_apl), $cari, null, null);
                foreach ($localResult['data'] as $row) {
                    $mergedRows[] = $row;
                }
                continue;
            }

            $remoteResponse = callStockOpnameBranchApi($aplInfo, array(
                'encrypt'  => md5($tgl . '#' . $aplInfo['key_apl']),
                'id_apl'   => $aplInfo['id_apl'],
                'caridata' => $cari,
                'halaman'  => 1,
                'maximal'  => $maxi,
                'paginate' => 0
            ), 'GET');

            if (!$remoteResponse['ok'] || ($remoteResponse['json']['status'] ?? '') !== 'success') {
                $errors[] = array(
                    'id_apl'   => $aplInfo['id_apl'],
                    'nama_apl' => $aplInfo['nama_apl'],
                    'message'  => !empty($remoteResponse['json']['message'])
                                    ? $remoteResponse['json']['message']
                                    : resolveStockOpnameRemoteMessage($remoteResponse)
                );
                continue;
            }

            foreach (($remoteResponse['json']['data'] ?? array()) as $row) {
                $mergedRows[] = $row;
            }
        }

        // Urutkan berdasarkan nama_pro
        usort($mergedRows, function ($a, $b) {
            return strcmp((string)($a['nama_pro'] ?? ''), (string)($b['nama_pro'] ?? ''));
        });

        $total = count($mergedRows);
        $rows  = ($paginate === 0) ? $mergedRows : array_slice($mergedRows, $mulai, $maxi);

        respondStockOpnameJson(array(
            'status'   => 'success',
            'id_apl'   => 'all',
            'nama_apl' => 'Semua Cabang',
            'total'    => $total,
            'halaman'  => $page,
            'maximal'  => $maxi,
            'data'     => array_values($rows),
            'errors'   => $errors
        ));
        $conn = $base->close();
        exit;
    }

    // ── LOCAL READ ───────────────────────────────────────────────────────────
    $localResult = fetchLocalStockOpname(
        $conn,
        array('id_apl' => $id_apl, 'nama_apl' => $nama_apl),
        $cari,
        ($paginate === 0 ? null : $mulai),
        ($paginate === 0 ? null : $maxi)
    );

    respondStockOpnameJson(array(
        'status'   => 'success',
        'id_apl'   => $id_apl,
        'nama_apl' => $nama_apl,
        'total'    => $localResult['total'],
        'halaman'  => $page,
        'maximal'  => $maxi,
        'data'     => $localResult['data']
    ));

} catch (PDOException $e) {
    respondStockOpnameJson(array(
        'status'  => 'error',
        'message' => $e->getMessage()
    ), 500);
}

$conn = $base->close();
?>
