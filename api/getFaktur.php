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

// Validasi parameter
$tgl = isset($_POST['tgl']) ? trim($_POST['tgl']) : '';
$key = isset($_POST['key']) ? trim($_POST['key']) : '';
$encrypt = isset($_POST['encrypt']) ? $secu->injection($_POST['encrypt']) : '';
$id_apl = isset($_POST['id_apl']) ? $secu->injection($_POST['id_apl']) : '';

// Validasi input
if (empty($encrypt) || empty($id_apl)) {
    http_response_code(400);
    echo json_encode(["error" => "Parameter 'encrypt' dan 'id_apl' wajib diisi"]);
    exit;
}

// Validasi format tanggal
if (empty($tgl) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl)) {
    http_response_code(400);
    echo json_encode(["error" => "Format tanggal tidak valid. Gunakan YYYY-MM-DD"]);
    exit;
}

// Validasi tanggal dengan DateTime
$date_obj = DateTime::createFromFormat('Y-m-d', $tgl);
if (!$date_obj || $date_obj->format('Y-m-d') !== $tgl) {
    http_response_code(400);
    echo json_encode(["error" => "Tanggal tidak valid"]);
    exit;
}

try {
    $conn = $base->open();
    
    // Ambil data aplikasi
    $stmtApl = $conn->prepare("SELECT key_apl, base_url_apl, nama_apl FROM aplikasi WHERE id_apl = :id_apl AND active_apl = 1");
    $stmtApl->bindParam(':id_apl', $id_apl, PDO::PARAM_STR);
    $stmtApl->execute();
    $apl = $stmtApl->fetch(PDO::FETCH_ASSOC);
    
    if (!$apl) {
        throw new Exception("Aplikasi tidak ditemukan atau tidak aktif");
    }
    
    $sourceKey = $apl['key_apl'];
    $baseUrl = $apl['base_url_apl'];
    $nama_apl = $apl['nama_apl'];
    
    // Validasi encrypt
    if (md5($tgl . "#" . $sourceKey) !== $encrypt) {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized - Invalid encryption"]);
        exit;
    }
    
    // Query data dengan tanggal yang sudah divalidasi
    $whereClause = "";
    $params = [':tgl' => $tgl];
    
    if (!empty($key)) {
        $whereClause = " AND (A.kode_tfk LIKE :key OR B.nama_out LIKE :key)";
        $params[':key'] = '%' . $key . '%';
    }
    
    $query = "SELECT 
                'api' as source,
                A.id_tfk,
                A.kode_tfk,
                A.id_out,
                A.cito,
                A.status_failing,
                A.status_dokumen,
                A.tgl_tfk,
                A.created_at,
                A.status_ceklis,
                A.status_tfkkb AS status_tfkkb_a,                        
                B.nama_out,
                B.ofcode_out, -- Pastikan field ini ada
                C.status_tfkkb AS status_tfkkb_c,
                'Cendo & DPE' as jenis_faktur
            FROM transaksi_faktur AS A 
            LEFT JOIN outlet AS B ON A.id_out = B.id_out 
            LEFT JOIN transaksi_faktur_kirim_b AS C ON A.id_tfk = C.id_tfk
            WHERE A.id_tfk IS NOT NULL
            AND DATE(A.tgl_tfk) = :tgl" . $whereClause . "
            
            UNION ALL
            
            SELECT 
                'api' as source,
                A.id_tfk,
                A.kode_tfk,
                A.id_out,
                A.cito,
                A.status_failing,
                A.status_dokumen,
                A.tgl_tfk,
                A.created_at,
                A.status_ceklis,
                A.status_tfkkb AS status_tfkkb_a,                        
                B.nama_out,
                B.ofcode_out,
                C.status_tfkkb AS status_tfkkb_c,
                'PIM' as jenis_faktur
            FROM transaksi_faktur_pim AS A 
            LEFT JOIN outlet AS B ON A.id_out = B.id_out 
            LEFT JOIN transaksi_faktur_kirim_b AS C ON A.id_tfk = C.id_tfk
            WHERE A.id_tfk IS NOT NULL
            AND DATE(A.tgl_tfk) = :tgl" . $whereClause . "
            
            ORDER BY created_at DESC";
    
    $master = $conn->prepare($query);
    
    // Bind parameters
    foreach ($params as $param => $value) {
        $master->bindValue($param, $value, PDO::PARAM_STR);
    }
    
    $master->execute();
    $hasil = $master->fetchAll(PDO::FETCH_ASSOC);
    
    // Add nama_apl to each result item
    foreach ($hasil as &$item) {
        $item['nama_apl'] = $nama_apl;
    }
    
    // Response sukses
    echo json_encode([
        "result" => $hasil,
        "base_url_apl" => $baseUrl,
        "id_apl" => $id_apl,
        "nama_apl" => $nama_apl,
        "date_requested" => $tgl,
        "total_records" => count($hasil)
    ]);
    
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $base->close();
    }
}
?>
