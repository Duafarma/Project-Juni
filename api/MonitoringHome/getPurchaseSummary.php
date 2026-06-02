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
            http_response_code(400); echo json_encode(["error"=>"Invalid periode"]); exit;
        }
    } else {
        if (empty($from) || empty($to) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            http_response_code(400); echo json_encode(["error"=>"Invalid from/to"]); exit;
        }
        if (strtotime($from) > strtotime($to)) { $tmp=$from; $from=$to; $to=$tmp; }
    }

    $stmt = $conn->prepare("SELECT key_apl FROM aplikasi WHERE id_apl = :id AND active_apl = 1");
    $stmt->execute([':id' => $id_apl]);
    $apl = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$apl) { http_response_code(404); echo json_encode(["error"=>"Aplikasi tidak ditemukan"]); exit; }

    $expectedEncrypt = ($mode === 'month')
        ? ($principle !== '' ? md5($periode . "|" . $principle . "#" . $apl['key_apl']) : md5($periode . "#" . $apl['key_apl']))
        : ($principle !== '' ? md5($from . "|" . $to . "|" . $principle . "#" . $apl['key_apl']) : md5($from . "|" . $to . "#" . $apl['key_apl']));

    if ($encrypt !== $expectedEncrypt) {
        http_response_code(403); echo json_encode(["error"=>"Invalid encryption"]); exit;
    }

    $dateExpr = "CASE WHEN tre.tgl_tre IS NULL OR tre.tgl_tre='0000-00-00' THEN tre.created_at ELSE tre.tgl_tre END";

    // periode / range calculation
    if ($mode === 'month') {
        list($y,$m) = explode('-', $periode);
        $year=(int)$y; $month=(int)$m;
        if ($principle === '') {
            $stmt = $conn->prepare("SELECT COALESCE(SUM(tre.total_tre),0) as total_pembelian_bulan FROM transaksi_receive tre WHERE YEAR($dateExpr)=:y AND MONTH($dateExpr)=:m");
            $stmt->execute([':y'=>$year,':m'=>$month]);
            $totalBulan = floatval($stmt->fetchColumn() ?? 0);
        } else {
            $stmt = $conn->prepare("SELECT COALESCE(SUM(d.total_trd),0) as total_pembelian_bulan FROM transaksi_receive tre INNER JOIN transaksi_receivedetail d ON d.id_tre = tre.id_tre INNER JOIN produk p ON p.id_pro = d.id_pro WHERE YEAR($dateExpr)=:y AND MONTH($dateExpr)=:m AND p.nama_p = :principle");
            $stmt->execute([':y'=>$year,':m'=>$month,':principle'=>$principle]);
            $totalBulan = floatval($stmt->fetchColumn() ?? 0);
        }

        // YTD for that month (from Jan 1 of same year to end of that month)
        $jan1 = sprintf('%04d-01-01',$year);
        $end = sprintf('%04d-%02d-%02d',$year,$month,cal_days_in_month(CAL_GREGORIAN,$month,$year));
        if ($principle === '') {
            $stmt = $conn->prepare("SELECT COALESCE(SUM(tre.total_tre),0) as total_pembelian_tahun FROM transaksi_receive tre WHERE DATE($dateExpr) BETWEEN :from AND :to");
            $stmt->execute([':from'=>$jan1,':to'=>$end]);
            $totalYtd = floatval($stmt->fetchColumn() ?? 0);
        } else {
            $stmt = $conn->prepare("SELECT COALESCE(SUM(d.total_trd),0) as total_pembelian_tahun FROM transaksi_receive tre INNER JOIN transaksi_receivedetail d ON d.id_tre = tre.id_tre INNER JOIN produk p ON p.id_pro = d.id_pro WHERE DATE($dateExpr) BETWEEN :from AND :to AND p.nama_p = :principle");
            $stmt->execute([':from'=>$jan1,':to'=>$end,':principle'=>$principle]);
            $totalYtd = floatval($stmt->fetchColumn() ?? 0);
        }

        echo json_encode([
            "status"=>"success",
            "result"=>[
                "total_pembelian_bulan"=>$totalBulan,
                "total_pembelian_tahun"=>$totalYtd
            ]
        ]);
        exit;
    } else {
        // range mode
        if ($principle === '') {
            $stmt = $conn->prepare("SELECT COALESCE(SUM(tre.total_tre),0) as total_pembelian_periode FROM transaksi_receive tre WHERE DATE($dateExpr) BETWEEN :from AND :to");
            $stmt->execute([':from'=>$from,':to'=>$to]);
            $totalPeriode = floatval($stmt->fetchColumn() ?? 0);
        } else {
            $stmt = $conn->prepare("SELECT COALESCE(SUM(d.total_trd),0) as total_pembelian_periode FROM transaksi_receive tre INNER JOIN transaksi_receivedetail d ON d.id_tre = tre.id_tre INNER JOIN produk p ON p.id_pro = d.id_pro WHERE DATE($dateExpr) BETWEEN :from AND :to AND p.nama_p = :principle");
            $stmt->execute([':from'=>$from,':to'=>$to,':principle'=>$principle]);
            $totalPeriode = floatval($stmt->fetchColumn() ?? 0);
        }

        // YTD for 'to' year
        $yearTo = (int)substr($to,0,4);
        $jan1 = sprintf('%04d-01-01',$yearTo);
        if ($principle === '') {
            $stmt = $conn->prepare("SELECT COALESCE(SUM(tre.total_tre),0) as total_pembelian_tahun FROM transaksi_receive tre WHERE DATE($dateExpr) BETWEEN :from AND :to");
            $stmt->execute([':from'=>$jan1,':to'=>$to]);
            $totalYtd = floatval($stmt->fetchColumn() ?? 0);
        } else {
            $stmt = $conn->prepare("SELECT COALESCE(SUM(d.total_trd),0) as total_pembelian_tahun FROM transaksi_receive tre INNER JOIN transaksi_receivedetail d ON d.id_tre = tre.id_tre INNER JOIN produk p ON p.id_pro = d.id_pro WHERE DATE($dateExpr) BETWEEN :from AND :to AND p.nama_p = :principle");
            $stmt->execute([':from'=>$jan1,':to'=>$to,':principle'=>$principle]);
            $totalYtd = floatval($stmt->fetchColumn() ?? 0);
        }

        echo json_encode([
            "status"=>"success",
            "result"=>[
                "total_pembelian_periode"=>$totalPeriode,
                "total_pembelian_tahun"=>$totalYtd
            ]
        ]);
        exit;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error"=>"Server error: " . $e->getMessage()]);
} finally {
    if (isset($base) && method_exists($base,'close')) $base->close();
}
?>