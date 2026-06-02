<?php
require_once('../config/connection/connection.php');
require_once('../config/connection/security.php');
require_once('../config/function/data.php');

header('Access-Control-Allow-Origin: *');
header("Content-type: application/json; charset=utf-8");

$secu = new Security;
$base = new DB;
$data = new Data;
$conn = $base->open();

$request      = ($_SERVER['REQUEST_METHOD'] === 'POST') ? $_POST : $_GET;
$tgl          = date('Y-m-d');
$cari         = $secu->injection(@$request['caridata']);
$page         = (int)($secu->injection(@$request['halaman']) ?: 1);
$maxi         = (int)($secu->injection(@$request['maximal']) ?: 15);
$encrypt      = $secu->injection(@$request['encrypt']);
$idAplReq     = $secu->injection(@$request['id_apl']);
$idOut        = $secu->injection(@$request['id_out']);
$statusFilter = $secu->injection(@$request['statusfilter']);
$action       = $secu->injection(@$request['action']);
if ($action === '') {
    $action = $secu->injection(@$request['act']);
}
$keycode    = $secu->injection(@$request['keycode']);
$status     = $secu->injection(@$request['status']);
$note       = $secu->injection(@$request['note']);
$actorAdmin = $secu->injection(@$request['admin']);
$paginate   = isset($request['paginate']) ? (int)$secu->injection(@$request['paginate']) : 1;
$mulai      = ($page > 1) ? (($page * $maxi) - $maxi) : 0;

$source    = $data->self_apl();
$sourceKey = $source['key_apl'] ?? '';
$id_apl    = $source['id_apl'] ?? '';
$nama_apl  = $source['nama_apl'] ?? '';

function respondJson($payload, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode($payload);
}

function buildOutletBaruWhereClause($statusFilter, $cari, $idOut, &$params) {
    $whereParts = array('1=1', 'C.diskon_odi > 10');

    if ($statusFilter === 'merah') {
        $whereParts[] = "LOWER(TRIM(COALESCE(A.status_pembayaran, ''))) = 'merah'";
    } elseif ($statusFilter === 'inactive') {
        $whereParts[] = "A.status_out = 'Inactive'";
    } elseif ($statusFilter === 'approval') {
        $whereParts[] = "A.status_out = 'Menunggu Approval SPV'";
    } elseif ($statusFilter !== '') {
        $whereParts[] = 'A.status_out = :statusfilter';
        $params[':statusfilter'] = $statusFilter;
    }

    if ($cari !== '') {
        $whereParts[] = '(A.kode_out LIKE :cari OR A.nama_out LIKE :cari OR A.ofcode_out LIKE :cari OR D.nama_kot LIKE :cari)';
        $params[':cari'] = '%' . $cari . '%';
    }

    if ($idOut !== '') {
        $whereParts[] = 'A.id_out = :id_out';
        $params[':id_out'] = $idOut;
    }

    return implode(' AND ', $whereParts);
}

function fetchLocalOutletBaru($conn, $sourceInfo, $statusFilter, $cari, $idOut, $offset = null, $limit = null) {
    $params = array();
    $whereStr = buildOutletBaruWhereClause($statusFilter, $cari, $idOut, $params);

    $qCount = "SELECT COUNT(A.id_out) AS total
               FROM outlet AS A
               INNER JOIN outlet_alamat AS B ON A.id_out = B.id_out
               INNER JOIN outlet_diskon AS C ON A.id_out = C.id_out
               INNER JOIN kategori_outlet AS D ON A.id_kot = D.id_kot
               WHERE $whereStr";
    $stmtCount = $conn->prepare($qCount);
    foreach ($params as $key => $value) {
        $stmtCount->bindValue($key, $value, PDO::PARAM_STR);
    }
    $stmtCount->execute();
    $total = (int)$stmtCount->fetchColumn();

    $qMaster = "SELECT
                    :id_apl AS id_apl,
                    :nama_apl AS nama_apl,
                    A.id_out,
                    A.id_kot,
                    A.id_mg,
                    A.kode_out,
                    A.created_at,
                    A.nama_out,
                    A.resmi_out,
                    A.npwp_out,
                    A.ofcode_out,
                    A.ket_out,
                    A.status_out,
                    A.status_pembayaran,
                    B.telp_ola,
                    B.hp_ola,
                    B.fax_ola,
                    B.email_ola,
                    B.picp_ola,
                    B.picpk_ola,
                    B.picf_ola,
                    B.picfk_ola,
                    B.jatuk_ola,
                    B.syatuk_ola,
                    B.kantor_ola,
                    B.pengiriman_ola,
                    B.atuk_ola,
                    B.kopos_ola,
                    B.id_rpo,
                    B.id_rkb,
                    C.parameter_odi,
                    C.top_odi,
                    C.diskon_odi,
                    D.nama_kot
                FROM outlet AS A
                INNER JOIN outlet_alamat AS B ON A.id_out = B.id_out
                INNER JOIN outlet_diskon AS C ON A.id_out = C.id_out
                INNER JOIN kategori_outlet AS D ON A.id_kot = D.id_kot
                WHERE $whereStr
                ORDER BY A.created_at DESC";

    if ($offset !== null && $limit !== null) {
        $qMaster .= ' LIMIT :mulai, :maxi';
    }

    $stmt = $conn->prepare($qMaster);
    $stmt->bindValue(':id_apl', $sourceInfo['id_apl'], PDO::PARAM_STR);
    $stmt->bindValue(':nama_apl', $sourceInfo['nama_apl'], PDO::PARAM_STR);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
    }

    if ($offset !== null && $limit !== null) {
        $stmt->bindValue(':mulai', (int)$offset, PDO::PARAM_INT);
        $stmt->bindValue(':maxi', (int)$limit, PDO::PARAM_INT);
    }

    $stmt->execute();

    return array(
        'total' => $total,
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    );
}

function filterOutletBaruRowsByStatusFilter($rows, $statusFilter) {
    $statusFilter = trim((string)$statusFilter);
    if ($statusFilter === '') {
        return $rows;
    }

    $filtered = array();
    foreach ($rows as $row) {
        $statusOut = trim((string)($row['status_out'] ?? ''));
        $statusPembayaran = strtolower(trim((string)($row['status_pembayaran'] ?? '')));

        if ($statusFilter === 'merah') {
            if ($statusPembayaran === 'merah') {
                $filtered[] = $row;
            }
            continue;
        }

        if ($statusFilter === 'inactive') {
            if ($statusOut === 'Inactive') {
                $filtered[] = $row;
            }
            continue;
        }

        if ($statusFilter === 'approval') {
            if ($statusOut === 'Menunggu Approval SPV') {
                $filtered[] = $row;
            }
            continue;
        }

        if ($statusOut === $statusFilter) {
            $filtered[] = $row;
        }
    }

    return $filtered;
}

function fetchLocalOutletLegalRows($conn, $idOut) {
    $stmt = $conn->prepare('SELECT id_klg, ket_ole, expired_ole FROM outlet_legal WHERE id_out = :id_out ORDER BY id_ole ASC');
    $stmt->bindValue(':id_out', $idOut, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function sanitizeOutletRevisionNote($value) {
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }

    $value = preg_replace('/^\s*-\s*Note\s*:\s*/i', '', $value);
    $value = preg_replace('/^\s*Note\s*:\s*/i', '', $value);
    return trim($value);
}

function fetchLocalLatestOutletRevisionNote($conn, $idOut) {
    if ($idOut === '') {
        return '';
    }

    try {
        $stmt = $conn->prepare("SELECT * FROM riwayat WHERE keycode_riw = :kode AND tipe_riw = 'Outlet' ORDER BY id_riw DESC LIMIT 20");
        $stmt->bindValue(':kode', $idOut, PDO::PARAM_STR);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $aksi = trim((string)($row['aksi_riw'] ?? ''));
            $catatan = '';

            foreach (array('ket_riw', 'keterangan_riw', 'note_riw') as $field) {
                if (!empty($row[$field])) {
                    $catatan = sanitizeOutletRevisionNote($row[$field]);
                    if ($catatan !== '') {
                        break;
                    }
                }
            }

            if (stripos($aksi, 'Revised') !== false || stripos($aksi, 'Need Revision') !== false) {
                if ($catatan !== '') {
                    return $catatan;
                }

                if (preg_match('/(?:Revised|Need Revision)\s*-\s*(.+)$/i', $aksi, $match)) {
                    $catatan = sanitizeOutletRevisionNote($match[1]);
                    if ($catatan !== '') {
                        return $catatan;
                    }
                }
            }
        }
    } catch (PDOException $e) {
        return '';
    }

    return '';
}

function attachOutletDetailMetadata($conn, $rows) {
    foreach ($rows as &$row) {
        $row['legal_rows'] = fetchLocalOutletLegalRows($conn, $row['id_out'] ?? '');
        $row['legal'] = count($row['legal_rows']);
        $row['revision_note'] = fetchLocalLatestOutletRevisionNote($conn, $row['id_out'] ?? '');
    }
    unset($row);
    return $rows;
}

function resolveBranchBaseUrl($branch) {
    $baseUrl = trim($branch['base_url_apl'] ?? '');
    if ($baseUrl !== '') {
        return rtrim($baseUrl, '/');
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . '/monitoring';
}

function callOutletBaruBranchApi($branch, $payload, $method = 'GET') {
    $apiUrl = resolveBranchBaseUrl($branch) . '/api/getOutletBaru.php';
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
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $error !== '') {
            return array('ok' => false, 'message' => $error !== '' ? $error : 'Curl request gagal', 'http_code' => $httpCode);
        }

        $json = json_decode($response, true);
        if (!is_array($json)) {
            return array('ok' => false, 'message' => 'Response cabang bukan JSON valid', 'http_code' => $httpCode, 'body' => $response);
        }

        return array('ok' => ($httpCode >= 200 && $httpCode < 300), 'http_code' => $httpCode, 'json' => $json, 'body' => $response);
    }

    $options = array(
        'http' => array(
            'method' => $method,
            'timeout' => 20,
            'ignore_errors' => true,
            'header' => "Content-Type: application/x-www-form-urlencoded\r\nAccept: application/json\r\n"
        )
    );

    if ($method === 'POST') {
        $options['http']['content'] = http_build_query($payload);
    }

    $context = stream_context_create($options);
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
        return array('ok' => false, 'message' => 'Response cabang bukan JSON valid', 'http_code' => $httpCode, 'body' => $response);
    }

    return array('ok' => ($httpCode >= 200 && $httpCode < 300), 'http_code' => $httpCode, 'json' => $json, 'body' => $response);
}

function normalizeOutletStatusAction($action, $status) {
    $action = trim((string)$action);
    $status = trim((string)$status);

    if ($action === 'updateStatusApprove' && $status === '') {
        return 'Active';
    }
    if ($action === 'updateStatusReject' && $status === '') {
        return 'Rejected';
    }
    if ($action === 'updateStatusRevision' && $status === '') {
        return 'Need Revision';
    }
    if ($action === 'updateStatusRevised' && $status === '') {
        return 'Revised';
    }

    return $status;
}

function labelOutletStatusAction($status) {
    if ($status === 'Active' || $status === 'Approved' || $status === 'Rekomendasi SPV') {
        return 'Approve';
    }
    if ($status === 'Rejected') {
        return 'Reject';
    }
    if ($status === 'Revised' || $status === 'Need Revision') {
        return 'Revised';
    }
    return 'Update Status';
}

function updateLocalOutletStatus($conn, $idOut, $status, $note, $catat, $admin) {
    if ($idOut === '') {
        return array('status' => 'error', 'message' => 'ID outlet tidak ditemukan');
    }
    if ($status === '') {
        return array('status' => 'error', 'message' => 'Status outlet tidak ditemukan');
    }

    $edit = $conn->prepare('UPDATE outlet SET status_out = :status, updated_at = :catat, updated_by = :admin WHERE id_out = :code');
    $edit->bindValue(':code', $idOut, PDO::PARAM_STR);
    $edit->bindValue(':status', $status, PDO::PARAM_STR);
    $edit->bindValue(':catat', $catat, PDO::PARAM_STR);
    $edit->bindValue(':admin', $admin, PDO::PARAM_STR);
    $edit->execute();

    if ($edit->rowCount() < 1) {
        $check = $conn->prepare('SELECT COUNT(*) FROM outlet WHERE id_out = :code');
        $check->bindValue(':code', $idOut, PDO::PARAM_STR);
        $check->execute();
        if ((int)$check->fetchColumn() < 1) {
            return array('status' => 'error', 'message' => 'Data outlet tidak ditemukan');
        }
    }

    $aksiRiwayat = labelOutletStatusAction($status);
    $catatanRiwayat = ($note !== '') ? ' - Note: ' . $note : '';
    $riwayat = $conn->prepare("INSERT INTO riwayat VALUES('', :kode, 'Outlet', :aksi, :catatan, :catat, :admin)");
    $riwayat->bindValue(':kode', $idOut, PDO::PARAM_STR);
    $riwayat->bindValue(':aksi', $aksiRiwayat, PDO::PARAM_STR);
    $riwayat->bindValue(':catatan', $catatanRiwayat, PDO::PARAM_STR);
    $riwayat->bindValue(':catat', $catat, PDO::PARAM_STR);
    $riwayat->bindValue(':admin', $admin, PDO::PARAM_STR);
    $riwayat->execute();

    return array('status' => 'success', 'message' => 'Status outlet berhasil diperbarui');
}

function deleteLocalOutlet($conn, $idOut, $catat, $admin) {
    if ($idOut === '') {
        return array('status' => 'error', 'message' => 'ID outlet tidak ditemukan');
    }

    $check = $conn->prepare('SELECT COUNT(*) FROM outlet WHERE id_out = :code');
    $check->bindValue(':code', $idOut, PDO::PARAM_STR);
    $check->execute();
    if ((int)$check->fetchColumn() < 1) {
        return array('status' => 'error', 'message' => 'Data outlet tidak ditemukan');
    }

    $tables = array('outlet_legal', 'outlet_diskon', 'outlet_alamat', 'outlet');
    foreach ($tables as $table) {
        $delete = $conn->prepare("DELETE FROM $table WHERE id_out = :code");
        $delete->bindValue(':code', $idOut, PDO::PARAM_STR);
        $delete->execute();
    }

    $riwayat = $conn->prepare("INSERT INTO riwayat VALUES('', :kode, 'Outlet', 'Delete', '', :catat, :admin)");
    $riwayat->bindValue(':kode', $idOut, PDO::PARAM_STR);
    $riwayat->bindValue(':catat', $catat, PDO::PARAM_STR);
    $riwayat->bindValue(':admin', $admin, PDO::PARAM_STR);
    $riwayat->execute();

    return array('status' => 'success', 'message' => 'Outlet berhasil dihapus');
}

function updateLocalOutletData($conn, $request, $catat, $admin) {
    $code = trim((string)($request['keycode'] ?? ''));
    if ($code === '') {
        return array('status' => 'error', 'message' => 'ID outlet tidak ditemukan');
    }

    $legalItems = isset($request['legal']) && is_array($request['legal']) ? $request['legal'] : array();
    $ketLegalItems = isset($request['ketlegal']) && is_array($request['ketlegal']) ? $request['ketlegal'] : array();
    $tglLegalItems = isset($request['tgllegal']) && is_array($request['tgllegal']) ? $request['tgllegal'] : array();

    $status = 'Menunggu Approval SPV';
    $kategori = trim((string)($request['kategori'] ?? ''));
    $idMasterGrup = trim((string)($request['id_mg'] ?? ''));
    $namaOutlet = trim((string)($request['namaoutlet'] ?? ''));
    $namaResmi = trim((string)($request['namaresmi'] ?? ''));
    $npwp = trim((string)($request['npwp'] ?? ''));
    $limit = trim((string)($request['limit'] ?? ''));
    $keterangan = trim((string)($request['kete'] ?? ''));
    $telepon = trim((string)($request['telp'] ?? ''));
    $handphone = trim((string)($request['hape'] ?? ''));
    $email = trim((string)($request['email'] ?? ''));
    $provinsi = trim((string)($request['provinsi'] ?? ''));
    $kabupaten = trim((string)($request['kabupaten'] ?? ''));
    $kodePos = trim((string)($request['kopos'] ?? ''));
    $jadwal = trim((string)($request['jadwal'] ?? ''));
    $syarat = trim((string)($request['syarat'] ?? ''));
    $alamatKantor = trim((string)($request['alamatkantor'] ?? ''));
    $alamatKirim = trim((string)($request['alamatkirim'] ?? ''));
    $alamatTukar = trim((string)($request['alamattukar'] ?? ''));
    $picProcurement = trim((string)($request['picp'] ?? ''));
    $picProcurementKontak = trim((string)($request['picpk'] ?? ''));
    $picFinance = trim((string)($request['picf'] ?? ''));
    $picFinanceKontak = trim((string)($request['picfk'] ?? ''));
    $diskon = str_replace(',', '.', trim((string)($request['diskon'] ?? '0')));

    $editOutlet = $conn->prepare("UPDATE outlet SET id_kot=:kate, id_mg=:id_mg, nama_out=:namao, resmi_out=:namar, npwp_out=:npwp, ket_out=:kete, status_out=:status, updated_at=:catat, updated_by=:admin WHERE id_out=:code");
    $editOutlet->bindValue(':code', $code, PDO::PARAM_STR);
    $editOutlet->bindValue(':kate', $kategori, PDO::PARAM_STR);
    $editOutlet->bindValue(':id_mg', $idMasterGrup, PDO::PARAM_STR);
    $editOutlet->bindValue(':namao', $namaOutlet, PDO::PARAM_STR);
    $editOutlet->bindValue(':namar', $namaResmi, PDO::PARAM_STR);
    $editOutlet->bindValue(':npwp', $npwp, PDO::PARAM_STR);
    $editOutlet->bindValue(':kete', $keterangan, PDO::PARAM_STR);
    $editOutlet->bindValue(':status', $status, PDO::PARAM_STR);
    $editOutlet->bindValue(':catat', $catat, PDO::PARAM_STR);
    $editOutlet->bindValue(':admin', $admin, PDO::PARAM_STR);
    $editOutlet->execute();

    $editAlamat = $conn->prepare("UPDATE outlet_alamat SET telp_ola=:telp, hp_ola=:hape, email_ola=:email, id_rpo=:prov, id_rkb=:kab, kopos_ola=:kopos, picp_ola=:picp, picpk_ola=:picpk, picf_ola=:picf, picfk_ola=:picfk, jatuk_ola=:jadwal, syatuk_ola=:syarat, kantor_ola=:altor, pengiriman_ola=:alkir, atuk_ola=:altuk, updated_at=:catat, updated_by=:admin WHERE id_out=:code");
    $editAlamat->bindValue(':code', $code, PDO::PARAM_STR);
    $editAlamat->bindValue(':telp', $telepon, PDO::PARAM_STR);
    $editAlamat->bindValue(':hape', $handphone, PDO::PARAM_STR);
    $editAlamat->bindValue(':email', $email, PDO::PARAM_STR);
    $editAlamat->bindValue(':prov', $provinsi, PDO::PARAM_STR);
    $editAlamat->bindValue(':kab', $kabupaten, PDO::PARAM_STR);
    $editAlamat->bindValue(':kopos', $kodePos, PDO::PARAM_STR);
    $editAlamat->bindValue(':picp', $picProcurement, PDO::PARAM_STR);
    $editAlamat->bindValue(':picpk', $picProcurementKontak, PDO::PARAM_STR);
    $editAlamat->bindValue(':picf', $picFinance, PDO::PARAM_STR);
    $editAlamat->bindValue(':picfk', $picFinanceKontak, PDO::PARAM_STR);
    $editAlamat->bindValue(':jadwal', $jadwal, PDO::PARAM_STR);
    $editAlamat->bindValue(':syarat', $syarat, PDO::PARAM_STR);
    $editAlamat->bindValue(':altor', $alamatKantor, PDO::PARAM_STR);
    $editAlamat->bindValue(':alkir', $alamatKirim, PDO::PARAM_STR);
    $editAlamat->bindValue(':altuk', $alamatTukar, PDO::PARAM_STR);
    $editAlamat->bindValue(':catat', $catat, PDO::PARAM_STR);
    $editAlamat->bindValue(':admin', $admin, PDO::PARAM_STR);
    $editAlamat->execute();

    $editDiskon = $conn->prepare('UPDATE outlet_diskon SET top_odi=:limit, diskon_odi=:diskon, updated_at=:catat, updated_by=:admin WHERE id_out=:code');
    $editDiskon->bindValue(':code', $code, PDO::PARAM_STR);
    $editDiskon->bindValue(':limit', $limit, PDO::PARAM_STR);
    $editDiskon->bindValue(':diskon', $diskon, PDO::PARAM_STR);
    $editDiskon->bindValue(':catat', $catat, PDO::PARAM_STR);
    $editDiskon->bindValue(':admin', $admin, PDO::PARAM_STR);
    $editDiskon->execute();

    $deleteLegal = $conn->prepare('DELETE FROM outlet_legal WHERE id_out=:code');
    $deleteLegal->bindValue(':code', $code, PDO::PARAM_STR);
    $deleteLegal->execute();

    $saveLegal = $conn->prepare('INSERT INTO outlet_legal VALUES(:id, :code, :legal, :klegal, :tlegal, :catat, :admin, :catat, :admin)');
    foreach ($legalItems as $index => $legalId) {
        $legalId = trim((string)$legalId);
        if ($legalId === '') {
            continue;
        }

        $emptyId = '';
        $saveLegal->bindValue(':id', $emptyId, PDO::PARAM_STR);
        $saveLegal->bindValue(':code', $code, PDO::PARAM_STR);
        $saveLegal->bindValue(':legal', $legalId, PDO::PARAM_STR);
        $saveLegal->bindValue(':klegal', trim((string)($ketLegalItems[$index] ?? '')), PDO::PARAM_STR);
        $saveLegal->bindValue(':tlegal', trim((string)($tglLegalItems[$index] ?? '')), PDO::PARAM_STR);
        $saveLegal->bindValue(':catat', $catat, PDO::PARAM_STR);
        $saveLegal->bindValue(':admin', $admin, PDO::PARAM_STR);
        $saveLegal->execute();
    }

    $riwayat = $conn->prepare("INSERT INTO riwayat VALUES('', :kode, 'Outlet', 'Update', 'Data telah direvisi dan dikirim kembali untuk approval', :catat, :admin)");
    $riwayat->bindValue(':kode', $code, PDO::PARAM_STR);
    $riwayat->bindValue(':catat', $catat, PDO::PARAM_STR);
    $riwayat->bindValue(':admin', $admin, PDO::PARAM_STR);
    $riwayat->execute();

    return array('status' => 'success', 'message' => 'Data outlet berhasil diperbarui');
}

if (md5($tgl . '#' . $sourceKey) !== $encrypt) {
    respondJson(array(
        'status' => 'error',
        'message' => 'Unauthorized: encrypt tidak valid'
    ), 401);
    $conn = $base->close();
    exit;
}

try {
    $requestedApl = trim($idAplReq);
    $requestedKey = ($keycode !== '') ? $keycode : $idOut;

    if ($action !== '') {
        $normalizedStatus = normalizeOutletStatusAction($action, $status);
        $actorName = ($actorAdmin !== '') ? $actorAdmin : 'system-api';

        if ($requestedApl !== '' && strtolower($requestedApl) !== 'all' && $requestedApl !== $id_apl) {
            $stmtBranch = $conn->prepare('SELECT id_apl, nama_apl, base_url_apl, key_apl FROM aplikasi WHERE id_apl = :id_apl AND active_apl = 1 LIMIT 1');
            $stmtBranch->bindValue(':id_apl', $requestedApl, PDO::PARAM_STR);
            $stmtBranch->execute();
            $branch = $stmtBranch->fetch(PDO::FETCH_ASSOC);

            if (!$branch) {
                respondJson(array('status' => 'error', 'message' => 'Cabang tidak ditemukan'), 404);
                $conn = $base->close();
                exit;
            }

            $remotePayload = $request;
            $remotePayload['encrypt'] = md5($tgl . '#' . $branch['key_apl']);
            $remotePayload['id_apl'] = $branch['id_apl'];
            $remotePayload['action'] = $action;
            $remotePayload['keycode'] = $requestedKey;
            $remotePayload['status'] = $normalizedStatus;
            $remotePayload['note'] = $note;
            $remotePayload['admin'] = $actorName;

            $remoteResponse = callOutletBaruBranchApi($branch, $remotePayload, 'POST');

            if (!$remoteResponse['ok']) {
                respondJson(array(
                    'status' => 'error',
                    'message' => $remoteResponse['message'] ?? ('HTTP ' . ($remoteResponse['http_code'] ?? 0))
                ), 502);
                $conn = $base->close();
                exit;
            }

            respondJson($remoteResponse['json'], $remoteResponse['http_code'] ?? 200);
            $conn = $base->close();
            exit;
        }

        if ($action === 'update') {
            $result = updateLocalOutletData($conn, $request, date('Y-m-d H:i:s'), $actorName);
            respondJson($result, ($result['status'] === 'success') ? 200 : 400);
            $conn = $base->close();
            exit;
        }

        if ($action === 'delete') {
            $result = deleteLocalOutlet($conn, $requestedKey, date('Y-m-d H:i:s'), $actorName);
            respondJson($result, ($result['status'] === 'success') ? 200 : 400);
            $conn = $base->close();
            exit;
        }

        $result = updateLocalOutletStatus($conn, $requestedKey, $normalizedStatus, $note, date('Y-m-d H:i:s'), $actorName);
        respondJson($result, ($result['status'] === 'success') ? 200 : 400);
        $conn = $base->close();
        exit;
    }

    if ($requestedApl !== '' && strtolower($requestedApl) !== 'all' && $requestedApl !== $id_apl) {
        $stmtBranch = $conn->prepare('SELECT id_apl, nama_apl, base_url_apl, key_apl FROM aplikasi WHERE id_apl = :id_apl AND active_apl = 1 LIMIT 1');
        $stmtBranch->bindValue(':id_apl', $requestedApl, PDO::PARAM_STR);
        $stmtBranch->execute();
        $branch = $stmtBranch->fetch(PDO::FETCH_ASSOC);

        if (!$branch) {
            respondJson(array('status' => 'error', 'message' => 'Cabang tidak ditemukan'), 404);
            $conn = $base->close();
            exit;
        }

        $remoteResponse = callOutletBaruBranchApi($branch, array(
            'encrypt' => md5($tgl . '#' . $branch['key_apl']),
            'id_apl' => $branch['id_apl'],
            'caridata' => $cari,
            'halaman' => $page,
            'maximal' => $maxi,
            'statusfilter' => $statusFilter,
            'id_out' => $idOut,
            'paginate' => $paginate
        ), 'GET');

        if (!$remoteResponse['ok']) {
            respondJson(array(
                'status' => 'error',
                'message' => $remoteResponse['message'] ?? ('HTTP ' . ($remoteResponse['http_code'] ?? 0))
            ), 502);
            $conn = $base->close();
            exit;
        }

        respondJson($remoteResponse['json'], $remoteResponse['http_code'] ?? 200);
        $conn = $base->close();
        exit;
    }

    if (strtolower($requestedApl) === 'all') {
        $stmtAplikasi = $conn->query('SELECT id_apl, nama_apl, self_apl, base_url_apl, key_apl FROM aplikasi WHERE active_apl = 1 ORDER BY nama_apl ASC');
        $aplikasiList = $stmtAplikasi ? $stmtAplikasi->fetchAll(PDO::FETCH_ASSOC) : array();
        $mergedRows = array();
        $errors = array();

        foreach ($aplikasiList as $aplInfo) {
            if ((string)$aplInfo['id_apl'] === (string)$id_apl || (int)$aplInfo['self_apl'] === 1) {
                $localResult = fetchLocalOutletBaru($conn, array('id_apl' => $id_apl, 'nama_apl' => $nama_apl), $statusFilter, $cari, $idOut, null, null);
                foreach ($localResult['data'] as $row) {
                    $mergedRows[] = $row;
                }
                continue;
            }

            $remoteResponse = callOutletBaruBranchApi($aplInfo, array(
                'encrypt' => md5($tgl . '#' . $aplInfo['key_apl']),
                'id_apl' => $aplInfo['id_apl'],
                'caridata' => $cari,
                'halaman' => 1,
                'maximal' => $maxi,
                'statusfilter' => ($statusFilter === 'merah' ? '' : $statusFilter),
                'id_out' => $idOut,
                'paginate' => 0
            ), 'GET');

            if (!$remoteResponse['ok'] || ($remoteResponse['json']['status'] ?? '') !== 'success') {
                $errors[] = array(
                    'id_apl' => $aplInfo['id_apl'],
                    'nama_apl' => $aplInfo['nama_apl'],
                    'message' => $remoteResponse['message'] ?? ($remoteResponse['json']['message'] ?? 'Gagal memuat cabang')
                );
                continue;
            }

            foreach (($remoteResponse['json']['data'] ?? array()) as $row) {
                $mergedRows[] = $row;
            }
        }

        $mergedRows = filterOutletBaruRowsByStatusFilter($mergedRows, $statusFilter);

        usort($mergedRows, function($left, $right) {
            return strcmp((string)($right['created_at'] ?? ''), (string)($left['created_at'] ?? ''));
        });

        $total = count($mergedRows);
        $rows = ($paginate === 0) ? $mergedRows : array_slice($mergedRows, $mulai, $maxi);

        respondJson(array(
            'status' => 'success',
            'id_apl' => 'all',
            'nama_apl' => 'Semua Cabang',
            'total' => $total,
            'halaman' => $page,
            'maximal' => $maxi,
            'data' => $rows,
            'errors' => $errors
        ));
        $conn = $base->close();
        exit;
    }

    $localResult = fetchLocalOutletBaru($conn, array('id_apl' => $id_apl, 'nama_apl' => $nama_apl), $statusFilter, $cari, $idOut, ($paginate === 0 ? null : $mulai), ($paginate === 0 ? null : $maxi));
    if ($idOut !== '') {
        $localResult['data'] = attachOutletDetailMetadata($conn, $localResult['data']);
    }

    respondJson(array(
        'status' => 'success',
        'id_apl' => $id_apl,
        'nama_apl' => $nama_apl,
        'total' => $localResult['total'],
        'halaman' => $page,
        'maximal' => $maxi,
        'data' => $localResult['data']
    ));
} catch (PDOException $e) {
    respondJson(array(
        'status' => 'error',
        'message' => $e->getMessage()
    ), 500);
}

$conn = $base->close();
?>