<?php
// filepath: c:\xampp\htdocs\192.268.908.09\api\getmonitoringoppendingall.php
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

// Validasi parameter
$halaman = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$maximal = isset($_GET['maximal']) ? (int)$_GET['maximal'] : 50000;
$periode_dari = isset($_GET['periode_dari']) ? trim($_GET['periode_dari']) : '';
$periode_sampai = isset($_GET['periode_sampai']) ? trim($_GET['periode_sampai']) : '';

// Set default periode (3 bulan terakhir jika tidak ada filter)
if (empty($periode_dari) && empty($periode_sampai)) {
    $periode_sampai = date('Y-m-d');
    $periode_dari = date('Y-m-d', strtotime('-3 months'));
}

try {
    $conn = $base->open();
    
    // Build WHERE clause with period filter
    $whereClause = "WHERE A.id_tfk IS NOT NULL";
    $params = [];
    
    if (!empty($periode_dari)) {
        $whereClause .= " AND A.tgl_tfk >= :periode_dari";
        $params[':periode_dari'] = $periode_dari;
    }
    
    if (!empty($periode_sampai)) {
        $whereClause .= " AND A.tgl_tfk <= :periode_sampai";
        $params[':periode_sampai'] = $periode_sampai;
    }
    
    // Filter untuk hanya status pending
    $whereClause .= " AND NOT (
                        A.status_tfkkb = 'Sudah Dikirim'
                        AND A.status_dokumen = 'sudah balik'
                        AND COALESCE(A.status_tfkkf, 'Belum Dikirim') = 'Sudah Dikirim'
                    )";
    
    // Get local data
    $localQuery = "SELECT 'local' as source,
                    A.id_tfk,
                    A.kode_tfk,
                    A.id_out,
                    A.tgl_tfk,
                    A.cito,
                    A.status_ceklis,
                    A.created_at,
                    A.status_failing,
                    A.status_dokumen,
                    A.upload_f_pajak,
                    A.status_tfkkb,
                    COALESCE(A.status_tfkkf, 'Belum Dikirim') AS status_tfkkf,
                    B.nama_out,
                    'Cendo & DPE' as jenis
                FROM transaksi_faktur AS A 
                LEFT JOIN outlet AS B ON A.id_out = B.id_out
                $whereClause
                
                UNION ALL
                
                SELECT 'local' as source,
                    A.id_tfk,
                    A.kode_tfk,
                    A.id_out,
                    A.tgl_tfk,
                    A.cito,
                    A.status_ceklis,
                    A.created_at,
                    A.status_failing,
                    A.status_dokumen,
                    A.upload_f_pajak,
                    A.status_tfkkb,
                    COALESCE(A.status_tfkkf, 'Belum Dikirim') AS status_tfkkf,
                    B.nama_out,
                    'PIM' as jenis
                FROM transaksi_faktur_pim AS A
                LEFT JOIN outlet AS B ON A.id_out = B.id_out
                $whereClause
                
                ORDER BY STR_TO_DATE(tgl_tfk, '%d-%m-%Y') ASC, created_at ASC";
    
    $localStmt = $conn->prepare($localQuery);
    foreach ($params as $param => $value) {
        $localStmt->bindValue($param, $value, PDO::PARAM_STR);
    }
    $localStmt->execute();
    $localData = $localStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get aplikasi lain
    $local_id_apl = 'APL01';
    $stmtApl = $conn->prepare("SELECT id_apl, base_url_apl, key_apl, nama_apl FROM aplikasi WHERE id_apl != :local_id_apl AND active_apl = 1");
    $stmtApl->bindParam(':local_id_apl', $local_id_apl, PDO::PARAM_STR);
    $stmtApl->execute();
    $aplikasi_list = $stmtApl->fetchAll(PDO::FETCH_ASSOC);
    
    // Get data dari API aplikasi lain
    $apiData = [];
    
    foreach ($aplikasi_list as $apl) {
        $targetUrl = $apl['base_url_apl'] ?? '';
        $targetKey = $apl['key_apl'] ?? '';
        $nama_apl = $apl['nama_apl'] ?? '';
        $id_apl = $apl['id_apl'] ?? '';
        
        if (empty($targetUrl) || empty($targetKey)) {
            continue;
        }
        
        // Create encrypt
        $today = date('Y-m-d');
        $encrypt = md5($today . "#" . $targetKey);
        
        // Build parameters compatible with api/getFaktur_pending.php
        $tgl = $periode_sampai ?: date('Y-m-d');
        $tgl_3_months_ago = date('Y-m-d', strtotime($tgl . ' -3 months'));
        
        $postData = [
            'encrypt' => $encrypt,
            'tgl' => $tgl,
            'tgl_3_months_ago' => $tgl_3_months_ago,
            'key' => '', // optional search key
            'id_apl' => $id_apl,
            'filter_pending' => 'true'
        ];
        
        $ch = curl_init();
        // remote endpoint tersedia: /api/getFaktur_pending.php
        curl_setopt($ch, CURLOPT_URL, rtrim($targetUrl, '/') . "/api/getFaktur_pending.php");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Cache-Control: no-cache, no-store, must-revalidate',
            'Pragma: no-cache',
            'Expires: 0',
            'Content-Type: application/x-www-form-urlencoded'
        ]);
         
         $res = curl_exec($ch);
         $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
         $curl_error = curl_error($ch);
         curl_close($ch);
         
         if ($http_code == 200 && $res !== false && empty($curl_error)) {
            $api_response = json_decode($res, true);
            if ($api_response && (isset($api_response['data']) || isset($api_response['result']))) {
                $items = $api_response['data'] ?? $api_response['result'];
                if (is_array($items)) {
                    foreach ($items as $item) {
                        $item['source'] = 'api';
                        $item['nama_cabang'] = $nama_apl;
                        $apiData[] = $item;
                    }
                }
            }
         }
     }
    
    // Gabungkan data
    $allData = array_merge($localData, $apiData);
    
    // Add nama_cabang untuk data lokal
    foreach ($allData as &$item) {
        if ($item['source'] === 'local') {
            $item['nama_cabang'] = 'Puri';
        }
    }
    unset($item);

    // Normalisasi tgl_tfk untuk semua record dan tambahkan timestamp untuk sorting
    $dateFormats = ['d-m-Y', 'd/m/Y', 'Y-m-d', 'Y/m/d'];
    foreach ($allData as &$it) {
        $it['tgl_tfk'] = trim($it['tgl_tfk']);
        $timestamp = PHP_INT_MAX;
        foreach ($dateFormats as $fmt) {
            $dt = DateTime::createFromFormat($fmt, substr($it['tgl_tfk'], 0, 10));
            if ($dt && $dt->format($fmt) === substr($it['tgl_tfk'], 0, 10)) {
                $timestamp = $dt->getTimestamp();
                break;
            }
        }
        $it['tgl_tfk_ts'] = $timestamp;
    }
    unset($it);

    // Persingkat kode_tfk untuk semua record: ambil sampai dan termasuk "/FKT/"
    foreach ($allData as &$it) {
        if (isset($it['kode_tfk'])) {
            $raw = trim($it['kode_tfk']);
            $pos = stripos($raw, '/FKT/');
            if ($pos !== false) {
                $it['kode_tfk'] = substr($raw, 0, $pos + strlen('/FKT/'));
            } else {
                // fallback jika hanya ada "/FKT" tanpa slash akhir
                $pos2 = stripos($raw, '/FKT');
                if ($pos2 !== false) {
                    $short = substr($raw, 0, $pos2 + strlen('/FKT'));
                    $it['kode_tfk'] = rtrim($short, '/') . '/';
                } else {
                    // tidak ditemukan pola, biarkan apa adanya
                    $it['kode_tfk'] = $raw;
                }
            }
        }
    }
    unset($it);
 
    usort($allData, function($a, $b) {
        $dateCompare = $a['tgl_tfk_ts'] <=> $b['tgl_tfk_ts'];
        if ($dateCompare === 0) {
            return strtotime($a['created_at'] ?? 'now') <=> strtotime($b['created_at'] ?? 'now');
        }
        return $dateCompare;
    });

    // Limit data sesuai parameter
    $totalData = count($allData);
    $offset = ($halaman - 1) * $maximal;
    $limitedData = array_slice($allData, $offset, $maximal);
    
    echo json_encode([
        'success' => true,
        'data' => $limitedData,
        'total' => $totalData,
        'halaman' => $halaman,
        'maximal' => $maximal,
        'periode_dari' => $periode_dari,
        'periode_sampai' => $periode_sampai
    ]);
    
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'data' => []
    ]);
} finally {
    if (isset($conn)) {
        $base->close();
    }
}
?>