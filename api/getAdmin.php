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
$tgl = isset($_POST['tgl']) ? trim($_POST['tgl']) : date('Y-m-d');
$encrypt = isset($_POST['encrypt']) ? $secu->injection($_POST['encrypt']) : '';
$id_apl = isset($_POST['id_apl']) ? $secu->injection($_POST['id_apl']) : '';
$keyword = isset($_POST['keyword']) ? $secu->injection($_POST['keyword']) : '';

// Validasi input wajib
if (empty($encrypt) || empty($id_apl)) {
    http_response_code(400);
    echo json_encode(["error" => "Parameter 'encrypt' dan 'id_apl' wajib diisi"]);
    exit;
}

// Validasi format tanggal
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl)) {
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
    
    // Query untuk mencari admin/kurir
    $whereClause = "";
    $params = [];
    
    if (!empty($keyword)) {
        $whereClause = " AND (nama_adm LIKE :keyword OR username_adm LIKE :keyword2)";
        $params[':keyword'] = '%' . $keyword . '%';
        $params[':keyword2'] = '%' . $keyword . '%';
    }
    
    $query = "SELECT 
                id_adm, 
                nama_adm,
                username_adm,
                email_adm,
                jenis_adm,
                active_adm
            FROM adminz 
            WHERE active_adm = '1'" . $whereClause . "
            ORDER BY nama_adm ASC 
            LIMIT 50";
    
    $master = $conn->prepare($query);
    
    // Bind parameters jika ada
    foreach ($params as $param => $value) {
        $master->bindValue($param, $value, PDO::PARAM_STR);
    }
    
    $master->execute();
    $hasil = $master->fetchAll(PDO::FETCH_ASSOC);
    
    // Add nama_apl to each result item dan format data
    foreach ($hasil as &$item) {
        $item['nama_apl'] = $nama_apl;
        $item['id_apl'] = $id_apl;
        $item['display_name'] = $item['nama_adm'] . ' (' . $item['username_adm'] . ')';
        $item['jenis_display'] = ucfirst($item['jenis_adm']);
    }
    
    // Response sukses
    echo json_encode([
        "status" => "success",
        "result" => $hasil,
        "base_url_apl" => $baseUrl,
        "id_apl" => $id_apl,
        "nama_apl" => $nama_apl,
        "keyword" => $keyword,
        "date_requested" => $tgl,
        "total_records" => count($hasil)
    ]);
    
} catch (Exception $e) {
    error_log("API Error getAdmin: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn = $base->close();
    }
}
?>
