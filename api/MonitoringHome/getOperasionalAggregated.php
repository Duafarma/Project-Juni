<?php
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET','POST'])) {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(["error"=>"Method Not Allowed"]);
    exit;
}

require_once('../../config/connection/connection.php');
require_once('../../config/connection/security.php');

$base = new DB;
$secu = new Security;

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

try {
    $conn = $base->open();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $conn->query("SELECT id_apl, nama_apl, base_url_apl FROM aplikasi WHERE active_apl = 1 ORDER BY nama_apl");
        $branches = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        echo json_encode(["status"=>"success","branches"=>$branches]);
        exit;
    }

    $periode = isset($_POST['periode']) ? trim($_POST['periode']) : '';
    $from    = isset($_POST['from']) ? trim($_POST['from']) : '';
    $to      = isset($_POST['to']) ? trim($_POST['to']) : '';
    $id_apl  = isset($_POST['id_apl'])  ? trim($_POST['id_apl'])  : 'all';
    $selectedIds = [];
    if ($id_apl !== '' && strtolower($id_apl) !== 'all') {
        foreach (explode(',', $id_apl) as $item) {
            $item = $secu->injection(trim($item));
            if ($item === '' || strtolower($item) === 'all') continue;
            $selectedIds[$item] = $item;
        }
    }

    // OPTIONAL: principle
    $principle = isset($_POST['principle']) ? $secu->injection(trim($_POST['principle'])) : '';
    if ($principle === 'all') $principle = '';

    $mode = 'month';
    if($from !== '' || $to !== '') $mode = 'range';

    if($mode === 'month') {
        if (empty($periode) || !preg_match('/^\d{4}-\d{2}$/', $periode)) {
            http_response_code(400);
            echo json_encode(["error"=>"Parameter 'periode' wajib dalam format YYYY-MM"]);
            exit;
        }
    } else {
        if (empty($from) || empty($to) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            http_response_code(400);
            echo json_encode(["error"=>"Parameter 'from' dan 'to' wajib dalam format YYYY-MM-DD"]);
            exit;
        }
        if (strtotime($from) > strtotime($to)) { $tmp=$from; $from=$to; $to=$tmp; }
    }

    function accumulateNumericDeep(array &$acc, $data): void {
        if (!is_array($data)) return;
        foreach ($data as $k => $v) {
            if (is_array($v)) { accumulateNumericDeep($acc, $v); continue; }
            if (is_numeric($v)) $acc[$k] = ($acc[$k] ?? 0) + (float)$v;
        }
    }

    $stmtAll = $conn->query("SELECT id_apl, nama_apl, base_url_apl, key_apl FROM aplikasi WHERE active_apl = 1 ORDER BY nama_apl");
    $branches = $stmtAll ? $stmtAll->fetchAll(PDO::FETCH_ASSOC) : [];
    if (!empty($selectedIds)) {
        $branches = array_values(array_filter($branches, static function ($branch) use ($selectedIds) {
            return isset($selectedIds[$branch['id_apl'] ?? '']);
        }));
    }

    if (empty($branches)) {
        http_response_code(empty($selectedIds) ? 200 : 404);
        echo json_encode(["status"=>empty($selectedIds) ? "success" : "error","periode"=>$periode,"from"=>$from,"to"=>$to,"branches"=>[],"per_branch"=>[],"aggregate"=>[],"error"=>empty($selectedIds) ? null : "Cabang tidak ditemukan"]);
        exit;
    }

    $mh      = curl_multi_init();
    $handles = [];

    foreach ($branches as $idx => $b) {
        // PASTIKAN encrypt include principle jika ada
        if ($principle !== '') {
            $encrypt = ($mode === 'month')
                ? md5($periode . "|" . $principle . "#" . ($b['key_apl'] ?? ''))
                : md5($from . "|" . $to . "|" . $principle . "#" . ($b['key_apl'] ?? ''));
        } else {
            $encrypt = ($mode === 'month')
                ? md5($periode . "#" . ($b['key_apl'] ?? ''))
                : md5($from . "|" . $to . "#" . ($b['key_apl'] ?? ''));
        }

        $baseUrl = trim($b['base_url_apl'] ?? '');
        if ($baseUrl === '') {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $baseUrl  = $scheme . '://' . $host . '/monitoring';
        }

        $apiUrl  = rtrim($baseUrl, '/') . '/api/MonitoringHome/getOperasionalSummary.php';

        $payload = ['encrypt'=>$encrypt,'id_apl'=>$b['id_apl']];
        if($mode === 'month') $payload['periode'] = $periode;
        else { $payload['from']=$from; $payload['to']=$to; }

        // PENTING: Kirim principle ke setiap cabang
        if ($principle !== '') $payload['principle'] = $principle;

        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        curl_multi_add_handle($mh, $ch);
        $handles[$idx] = $ch;
    }

    // === Extra calls: Malang B dan Medan B (keduanya dari Puri B) ===
    $specialBranchMeta = [];
    $specialIdx = count($branches) + 1000;
    $specialDefs = [];
    foreach ($branches as $b) {
        if ($b['nama_apl'] === 'Puri B') {
            $specialDefs[] = [$b, 'malang_b', 'Malang B'];
            $specialDefs[] = [$b, 'medan_b',  'Medan B'];
        }
    }
    foreach ($specialDefs as [$b, $sMode, $sName]) {

        $sEncrypt = ($principle !== '')
            ? (($mode === 'month') ? md5($periode . "|" . $principle . "#" . ($b['key_apl'] ?? '')) : md5($from . "|" . $to . "|" . $principle . "#" . ($b['key_apl'] ?? '')))
            : (($mode === 'month') ? md5($periode . "#" . ($b['key_apl'] ?? ''))                   : md5($from . "|" . $to . "#" . ($b['key_apl'] ?? '')));

        $sBaseUrl = trim($b['base_url_apl'] ?? '');
        if ($sBaseUrl === '') {
            $sScheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $sBaseUrl = $sScheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/monitoring';
        }
        $sApiUrl = rtrim($sBaseUrl, '/') . '/api/MonitoringHome/getOperasionalSummary.php';

        $sPayload = ['encrypt' => $sEncrypt, 'id_apl' => $b['id_apl'], 'special_mode' => $sMode];
        if ($mode === 'month') $sPayload['periode'] = $periode;
        else { $sPayload['from'] = $from; $sPayload['to'] = $to; }
        if ($principle !== '') $sPayload['principle'] = $principle;

        $sCh = curl_init($sApiUrl);
        curl_setopt_array($sCh, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($sPayload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        curl_multi_add_handle($mh, $sCh);
        $handles[$specialIdx] = $sCh;
        $specialBranchMeta[$specialIdx] = ['id_apl' => $b['id_apl'] . '_' . $sMode, 'nama_apl' => $sName];
        $specialIdx++;
    }

    $running = null;
    do {
        $status = curl_multi_exec($mh, $running);
        if ($running) {
            $selected = curl_multi_select($mh, 0.5);
            if ($selected === -1) {
                usleep(100000);
            }
        }
    } while ($running > 0 && $status === CURLM_OK);

    $aggregate  = [];
    $per_branch = [];
    $sumTotalFakturNominal = 0.0;

    foreach ($handles as $idx => $ch) {
        $b = isset($specialBranchMeta[$idx]) ? $specialBranchMeta[$idx] : $branches[$idx];
        $resp = curl_multi_getcontent($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);

        if ($http !== 200 || !$resp) {
            $per_branch[] = ["id_apl"=>$b['id_apl'],"nama_apl"=>$b['nama_apl'],"ok"=>false,"error"=>"http_$http"];
            continue;
        }

        $json = json_decode($resp, true);
        $res  = (is_array($json) && isset($json['result']) && is_array($json['result'])) ? $json['result'] : null;
        if (!is_array($res)) {
            $per_branch[] = ["id_apl"=>$b['id_apl'],"nama_apl"=>$b['nama_apl'],"ok"=>false,"error"=>"no_result"];
            continue;
        }

        $per_branch[] = ["id_apl"=>$b['id_apl'],"nama_apl"=>$b['nama_apl'],"ok"=>true,"summary"=>$res];

        $nominalValue = floatval($res['sum_total_faktur_nominal'] ?? 0);
        $sumTotalFakturNominal += $nominalValue;
        accumulateNumericDeep($aggregate, $res);
    }
    curl_multi_close($mh);

    $aggregate['sum_total_faktur_nominal'] = (float)$sumTotalFakturNominal;

    echo json_encode([
        "status"     => "success",
        "mode"       => $mode,
        "periode"    => $periode,
        "from"       => $from,
        "to"         => $to,
        "id_apl"     => empty($selectedIds) ? 'all' : implode(',', array_keys($selectedIds)),
        "principle"  => ($principle === '' ? 'all' : $principle),
        "branches"   => $branches,
        "per_branch" => $per_branch,
        "aggregate"  => $aggregate,
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error"=>"Server error: " . $e->getMessage()]);
} finally {
    if (isset($base) && method_exists($base,'close')) $base->close();
}
?>