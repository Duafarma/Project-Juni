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

    $encrypt = isset($_POST['encrypt']) ? trim($_POST['encrypt']) : '';
    $id_apl  = isset($_POST['id_apl']) ? trim($_POST['id_apl']) : '';
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

    $stmt = $conn->prepare("SELECT key_apl FROM aplikasi WHERE id_apl = :id AND active_apl = 1");
    $stmt->execute([':id' => $id_apl]);
    $apl = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$apl) {
        http_response_code(404);
        echo json_encode(["error"=>"Aplikasi tidak ditemukan"]);
        exit;
    }

    // FIX: Update expected encrypt untuk mendukung principle
    $expectedEncrypt = ($mode === 'month')
        ? ($principle !== '' ? md5($periode . "|" . $principle . "#" . $apl['key_apl']) : md5($periode . "#" . $apl['key_apl']))
        : ($principle !== '' ? md5($from . "|" . $to . "|" . $principle . "#" . $apl['key_apl']) : md5($from . "|" . $to . "#" . $apl['key_apl']));

    if ($encrypt !== $expectedEncrypt) {
        http_response_code(403);
        echo json_encode(["error"=>"Invalid encryption"]);
        exit;
    }

    $dateExpr = "CASE WHEN tre.tgl_tre IS NULL OR tre.tgl_tre='0000-00-00' THEN tre.created_at ELSE tre.tgl_tre END";
    $invoicePpnExpr = "CASE WHEN COALESCE(tre.ppn_tre, 0) > 100 THEN COALESCE(tre.ppn_tre, 0) ELSE (COALESCE(tre.subtot_tre, 0) * COALESCE(tre.ppn_tre, 0)) / 100 END";
    $detailPpnExpr = "CASE WHEN COALESCE(tre.subtot_tre, 0) > 0 THEN (COALESCE(trd.total_trd, 0) / COALESCE(tre.subtot_tre, 0)) * ($invoicePpnExpr) ELSE 0 END";

    if($mode === 'month') {
        list($year, $month) = explode('-', $periode);
        $where = "YEAR($dateExpr)=:year AND MONTH($dateExpr)=:month";
        $bind  = [':year'=>$year, ':month'=>$month];
    } else {
        $where = "DATE($dateExpr) BETWEEN :from AND :to";
        $bind  = [':from'=>$from, ':to'=>$to];
    }

    // FIX: Tambahkan filter principle jika ada
    if ($principle !== '') {
        $stmt = $conn->prepare("
            SELECT 
                COALESCE(s.nama_sup, 'Unknown Supplier') as nama_sup,
                COUNT(DISTINCT tre.id_tre) as total_faktur,
                COALESCE(SUM(trd.total_trd), 0) as total_sebelum,
                COALESCE(SUM($detailPpnExpr), 0) as total_ppn,
                COALESCE(SUM(trd.total_trd + ($detailPpnExpr)), 0) as total_nominal
            FROM transaksi_receive tre
            LEFT JOIN supplier s ON tre.id_sup = s.id_sup
            INNER JOIN transaksi_receivedetail trd ON trd.id_tre = tre.id_tre
            INNER JOIN produk p ON p.id_pro = trd.id_pro
            WHERE {$where} AND TRIM(p.nama_p) = :principle
            GROUP BY COALESCE(tre.id_sup, 0), COALESCE(s.nama_sup, 'Unknown Supplier')
            ORDER BY total_nominal DESC
        ");
        $bind[':principle'] = $principle;
    } else {
        $stmt = $conn->prepare("
            SELECT 
                COALESCE(s.nama_sup, 'Unknown Supplier') as nama_sup,
                COUNT(tre.id_tre) as total_faktur,
                COALESCE(SUM(tre.subtot_tre), 0) as total_sebelum,
                        COALESCE(SUM(COALESCE(tre.total_tre, 0) - COALESCE(tre.subtot_tre, 0)), 0) as total_ppn,
                COALESCE(SUM(tre.total_tre), 0) as total_nominal
            FROM transaksi_receive tre
            LEFT JOIN supplier s ON tre.id_sup = s.id_sup
            WHERE {$where}
            GROUP BY COALESCE(tre.id_sup, 0), COALESCE(s.nama_sup, 'Unknown Supplier')
            ORDER BY total_nominal DESC
        ");
    }
    $stmt->execute($bind);
    $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "mode" => $mode,
        "periode" => $periode,
        "from" => $from,
        "to" => $to,
        "principle" => ($principle === '' ? 'all' : $principle),
        "suppliers" => $suppliers
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error"=>"Server error: " . $e->getMessage()]);
} finally {
    if (isset($base) && method_exists($base,'close')) $base->close();
}
?>