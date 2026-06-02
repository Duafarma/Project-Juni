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

    $periode = isset($_POST['periode']) ? trim($_POST['periode']) : '';
    $from    = isset($_POST['from']) ? trim($_POST['from']) : '';
    $to      = isset($_POST['to']) ? trim($_POST['to']) : '';
    $id_apl  = isset($_POST['id_apl']) ? trim($_POST['id_apl']) : 'all';
    $selectedIds = [];
    if ($id_apl !== '' && strtolower($id_apl) !== 'all') {
        foreach (explode(',', $id_apl) as $item) {
            $item = $secu->injection(trim($item));
            if ($item === '' || strtolower($item) === 'all') continue;
            $selectedIds[$item] = $item;
        }
    }
    $principle = isset($_POST['principle']) ? trim($_POST['principle']) : '';
    if ($principle === 'all') $principle = '';

    $mode = ($from !== '' || $to !== '') ? 'range' : 'month';

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

    $stmt = $conn->query("SELECT id_apl, nama_apl, base_url_apl, key_apl FROM aplikasi WHERE active_apl = 1 ORDER BY nama_apl");
    $branches = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    if (!empty($selectedIds)) {
        $branches = array_values(array_filter($branches, static function ($branch) use ($selectedIds) {
            return isset($selectedIds[$branch['id_apl'] ?? '']);
        }));
    }

    if (empty($branches)) {
        http_response_code(empty($selectedIds) ? 200 : 404);
        echo json_encode(["status"=>empty($selectedIds) ? "success" : "error","mode"=>$mode,"periode"=>$periode,"from"=>$from,"to"=>$to,"branches"=>[],"suppliers"=>[],"error"=>empty($selectedIds) ? null : "Cabang tidak ditemukan"]);
        exit;
    }

    $mh = curl_multi_init();
    $handles = [];

    foreach ($branches as $idx => $b) {
        if ($principle !== '') {
            $encrypt = ($mode === 'month')
                ? md5($periode . "|" . $principle . "#" . $b['key_apl'])
                : md5($from . "|" . $to . "|" . $principle . "#" . $b['key_apl']);
        } else {
            $encrypt = ($mode === 'month')
                ? md5($periode . "#" . $b['key_apl'])
                : md5($from . "|" . $to . "#" . $b['key_apl']);
        }

        $baseUrl = trim($b['base_url_apl'] ?? '');
        if ($baseUrl === '') {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $baseUrl = $scheme . '://' . $host . '/monitoring';
        }

        $apiUrl = rtrim($baseUrl, '/') . '/api/MonitoringHome/getPurchaseSupplierSummary.php';

        $payload = ['encrypt'=>$encrypt,'id_apl'=>$b['id_apl']];
        if($mode === 'month') $payload['periode'] = $periode;
        else { $payload['from']=$from; $payload['to']=$to; }
        
        if ($principle !== '') $payload['principle'] = $principle;

        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        curl_multi_add_handle($mh, $ch);
        $handles[$idx] = $ch;
    }

    $running = null;
    do {
        curl_multi_exec($mh, $running);
        curl_multi_select($mh, 0.5);
    } while ($running > 0);

    $supplierAggregated = [];

    foreach ($handles as $idx => $ch) {
        $resp = curl_multi_getcontent($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);

        if ($http === 200 && $resp) {
            $json = json_decode($resp, true);
            if (isset($json['suppliers']) && is_array($json['suppliers'])) {
                foreach($json['suppliers'] as $sup) {
                    $nama = $sup['nama_sup'];
                    if(!isset($supplierAggregated[$nama])) {
                        $supplierAggregated[$nama] = [
                            'nama_sup' => $nama,
                            'total_faktur' => 0,
                            'total_nominal' => 0,
                            'total_sebelum' => 0
                        ];
                    }
                    $supplierAggregated[$nama]['total_faktur']  += intval($sup['total_faktur'] ?? 0);
                    $supplierAggregated[$nama]['total_nominal'] += floatval($sup['total_nominal'] ?? 0);
                    $supplierAggregated[$nama]['total_sebelum'] += floatval($sup['total_sebelum'] ?? 0);
                }
            }
        }
    }
    curl_multi_close($mh);

    $suppliers = array_values($supplierAggregated);
    usort($suppliers, fn($a,$b) => $b['total_nominal'] <=> $a['total_nominal']);

    echo json_encode([
        "status" => "success",
        "mode" => $mode,
        "periode" => $periode,
        "from" => $from,
        "to" => $to,
        "id_apl" => empty($selectedIds) ? 'all' : implode(',', array_keys($selectedIds)),
        "branches" => $branches,
        "suppliers" => $suppliers
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error"=>"Server error: " . $e->getMessage()]);
} finally {
    if (isset($base) && method_exists($base,'close')) $base->close();
}
?>