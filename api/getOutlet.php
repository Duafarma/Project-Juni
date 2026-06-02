<?php
// Matikan output error untuk production
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Log untuk debugging
$debug_log = '../logs/api_debug_' . date('Y-m-d') . '.log';
function debugLog($message) {
    global $debug_log;
    if (!file_exists('../logs/')) {
        mkdir('../logs/', 0755, true);
    }
    file_put_contents($debug_log, date('Y-m-d H:i:s') . " - getOutlet.php - " . $message . "\n", FILE_APPEND);
}

try {
    debugLog("=== REQUEST GETOUTLET ===");
    debugLog("Method: " . $_SERVER['REQUEST_METHOD']);
    debugLog("POST data: " . json_encode($_POST));

    // Validasi metode request
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
    $encrypt = isset($_POST['encrypt']) ? trim($_POST['encrypt']) : '';
    $id_apl = isset($_POST['id_apl']) ? trim($_POST['id_apl']) : '';
    $key = isset($_POST['key']) ? trim($_POST['key']) : '';

    debugLog("Params - tgl: $tgl, encrypt: $encrypt, id_apl: $id_apl, key: $key");

    // Validasi input wajib
    if (empty($tgl) || empty($encrypt) || empty($id_apl)) {
        debugLog("ERROR: Parameter wajib kosong");
        http_response_code(400);
        echo json_encode(["error" => "Parameter 'tgl', 'encrypt', dan 'id_apl' wajib diisi"]);
        exit;
    }

    if (empty($key)) {
        debugLog("ERROR: Parameter key kosong");
        http_response_code(400);
        echo json_encode(["error" => "Parameter 'key' wajib diisi"]);
        exit;
    }

    // Validasi format tanggal
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl)) {
        debugLog("ERROR: Format tanggal tidak valid");
        http_response_code(400);
        echo json_encode(["error" => "Format tanggal tidak valid. Gunakan YYYY-MM-DD"]);
        exit;
    }

    // Validasi tanggal dengan DateTime
    $date_obj = DateTime::createFromFormat('Y-m-d', $tgl);
    if (!$date_obj || $date_obj->format('Y-m-d') !== $tgl) {
        debugLog("ERROR: Tanggal tidak valid");
        http_response_code(400);
        echo json_encode(["error" => "Tanggal tidak valid"]);
        exit;
    }

    $conn = $base->open();
    
    // Ambil data aplikasi
    $stmtApl = $conn->prepare("SELECT key_apl, base_url_apl, nama_apl FROM aplikasi WHERE id_apl = :id_apl AND active_apl = 1");
    $stmtApl->bindParam(':id_apl', $id_apl, PDO::PARAM_STR);
    $stmtApl->execute();
    $apl = $stmtApl->fetch(PDO::FETCH_ASSOC);
    
    debugLog("Aplikasi ditemukan: " . ($apl ? 'Ya' : 'Tidak'));
    
    if (!$apl) {
        debugLog("ERROR: Aplikasi $id_apl tidak ditemukan atau tidak aktif");
        http_response_code(401);
        echo json_encode(["error" => "Aplikasi tidak ditemukan atau tidak aktif"]);
        exit;
    }
    
    $sourceKey = $apl['key_apl'];
    $expectedEncrypt = md5($tgl . "#" . $sourceKey);
    
    debugLog("Key aplikasi: " . substr($sourceKey, 0, 10) . "...");
    debugLog("Expected encrypt: $expectedEncrypt");
    debugLog("Received encrypt: $encrypt");
    
    // Validasi encrypt
    if ($expectedEncrypt !== $encrypt) {
        debugLog("ERROR: Authentication gagal - encrypt tidak cocok");
        http_response_code(401);
        echo json_encode([
            "error" => "Unauthorized - Invalid encryption"
        ]);
        exit;
    }
    
    debugLog("Authentication berhasil");
    
    // Cek struktur tabel outlet terlebih dahulu
    $checkColumns = $conn->query("SHOW COLUMNS FROM outlet");
    $columns = [];
    while ($column = $checkColumns->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $column['Field'];
    }
    
    // Buat query berdasarkan kolom yang ada
    $selectFields = ['id_out', 'nama_out'];
    $whereFields = ['nama_out'];
    
    // Tambahkan kolom alamat jika ada
    if (in_array('alamat_out', $columns)) {
        $selectFields[] = 'alamat_out';
        $whereFields[] = 'alamat_out';
    } elseif (in_array('alamat', $columns)) {
        $selectFields[] = 'alamat AS alamat_out';
        $whereFields[] = 'alamat';
    } else {
        $selectFields[] = "'' AS alamat_out";
    }
    
    // Tambahkan kolom ofcode jika ada
    if (in_array('ofcode_out', $columns)) {
        $selectFields[] = 'ofcode_out';
        $whereFields[] = 'ofcode_out';
    } elseif (in_array('kode_out', $columns)) {
        $selectFields[] = 'kode_out AS ofcode_out';
        $whereFields[] = 'kode_out';
    } else {
        $selectFields[] = "'' AS ofcode_out";
    }
    
    // Cek kolom status outlet
    $statusField = 'active_out';
    if (in_array('status_out', $columns)) {
        $statusField = 'status_out';
    }
    
    // Buat WHERE clause
    $whereClause = '(' . implode(' LIKE :key OR ', $whereFields) . ' LIKE :key)';
    
    $query = "SELECT " . implode(', ', $selectFields) . "
            FROM outlet 
            WHERE $statusField IN ('Active', '1', 'active')
            AND " . $whereClause . "
            ORDER BY nama_out ASC
            LIMIT 20";
    
    debugLog("Query: " . $query);
    
    $stmt = $conn->prepare($query);
    $searchKey = '%' . $key . '%';
    $stmt->bindParam(':key', $searchKey, PDO::PARAM_STR);
    $stmt->execute();
    $hasil = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Standardisasi output
    foreach ($hasil as &$item) {
        // Standarisasi field
        $item['alamat_out'] = $item['alamat_out'] ?? ($item['alamat'] ?? '');
        $item['ofcode_out'] = $item['ofcode_out'] ?? ($item['kode_out'] ?? '');
        $item['source'] = $nama_apl;
    }
    
    debugLog("Query berhasil, mengembalikan " . count($hasil) . " record");
    
    echo json_encode([
        "status" => "success",
        "result" => $hasil,
        "base_url_apl" => $baseUrl,
        "id_apl" => $id_apl,
        "nama_apl" => $nama_apl,
        "total_records" => count($hasil)
    ]);

} catch (PDOException $e) {
    debugLog("PDO Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "error" => "Database error",
        "message" => "Koneksi database bermasalah"
    ]);
} catch (Exception $e) {
    debugLog("General Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "error" => "Server error",
        "message" => "Terjadi kesalahan pada server"
    ]);
} finally {
    if (isset($conn)) {
        try {
            $base->close();
        } catch (Exception $e) {
            debugLog("Error closing connection: " . $e->getMessage());
        }
    }
}

debugLog("=== END REQUEST ===");
?>