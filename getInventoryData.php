<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

error_reporting(E_ALL);
ini_set('display_errors', 0);
error_log("Inventory API Request: " . date('Y-m-d H:i:s') . " - " . $_SERVER['REQUEST_URI']);

// Function to fetch inventory from remote cabang API
function fetchCabangInventory($cabang_info, $cari, $conn) {
    if ($cabang_info['self_apl'] == 1) {
        // This is self (Medan) - return indicator to use local data
        return ['source' => 'local', 'cabang_id' => $cabang_info['id_apl']];
    }
    
    $remote_url = rtrim($cabang_info['base_url_apl'], '/') . '/api/getInventoryData.php';
    $remote_key = $cabang_info['key_apl'];
    $remote_params = [
        'key' => $remote_key,
        'action' => 'inventory_list',
        'cari' => $cari
    ];
    
    $query_string = http_build_query($remote_params);
    $full_url = $remote_url . '?' . $query_string;
    
    error_log("Fetching inventory from {$cabang_info['nama_apl']}: $full_url");
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $full_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Inventory API Client v1.0');
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    $curl_info = curl_getinfo($ch);
    curl_close($ch);
    
    if ($http_code !== 200 || !$response) {
        error_log("Failed to fetch inventory from {$cabang_info['nama_apl']}: HTTP $http_code, Error: $curl_error");
        return null;
    }
    
    $data = json_decode($response, true);
    if (!$data || !isset($data['status']) || $data['status'] !== 'success') {
        error_log("Invalid inventory response from {$cabang_info['nama_apl']}");
        return null;
    }
    
    return $data;
}

// Get parameters
$key = isset($_GET['key']) ? $_GET['key'] : '';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$cabang_id = isset($_GET['cabang']) ? $_GET['cabang'] : 'ALL';
$cari = isset($_GET['cari']) ? $_GET['cari'] : '';

// Validate API key
$valid_keys = ['YbcJSFjkdsfb', 'zdfsdfsdfsd', 'posjfbboj', 'sadd323213', 'medan34509'];

if (!in_array($key, $valid_keys)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid API key']);
    exit;
}

if ($action !== 'inventory_list') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    exit;
}

// Database connection
$conn = null;
$db_connected = false;

try {
    if (file_exists('../config/connection/connection.php')) {
        require_once('../config/connection/connection.php');
        if (class_exists('DB')) {
            $base = new DB();
            $conn = $base->open();
            $db_connected = true;
        }
    }
} catch(Exception $e) {
    error_log("Config connection failed: " . $e->getMessage());
}

if (!$db_connected) {
    try {
        $conn = new PDO("mysql:host=localhost;dbname=medan", "root", "");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db_connected = true;
    } catch(Exception $e) {
        error_log("Direct PDO connection failed: " . $e->getMessage());
    }
}

// Initialize response
$response = [
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s'),
    'cabang' => 'Medan',
    'cabang_id' => $cabang_id,
    'search' => $cari,
    'data_source' => $db_connected ? 'database' : 'dummy',
    'data' => []
];

// Check if specific cabang selected
$selected_cabang = null;
if ($cabang_id !== 'ALL' && $db_connected && $conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM aplikasi WHERE id_apl = :id_apl AND active_apl = 1");
        $stmt->bindParam(':id_apl', $cabang_id, PDO::PARAM_STR);
        $stmt->execute();
        $selected_cabang = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($selected_cabang) {
            $response['cabang'] = $selected_cabang['nama_apl'];
            error_log("Selected cabang for inventory: " . $selected_cabang['nama_apl']);
        }
    } catch(Exception $e) {
        error_log("Failed to fetch cabang data: " . $e->getMessage());
    }
}

// If selected cabang is not self (Medan) and has external API, fetch from remote
if ($selected_cabang && $selected_cabang['self_apl'] == 0 && !empty($selected_cabang['base_url_apl'])) {
    $remote_data = fetchCabangInventory($selected_cabang, $cari, $conn);
    if ($remote_data && isset($remote_data['data'])) {
        // Return remote inventory data
        $remote_data['data_source_note'] = "Inventory data from {$selected_cabang['nama_apl']} via remote API";
        echo json_encode($remote_data);
        exit;
    } else {
        // If remote fetch fails, fall back to error or local data
        echo json_encode([
            'status' => 'error',
            'message' => "Gagal mengambil data inventory dari {$selected_cabang['nama_apl']}",
            'cabang' => $selected_cabang['nama_apl'],
            'suggestion' => 'Coba pilih cabang lain atau gunakan ALL CABANG'
        ]);
        exit;
    }
}

// Get local inventory data (Medan or ALL)
$inventory_data = [];
$total_items = 0;

if ($db_connected && $conn) {
    try {
        require_once('../config/function/data.php');
        $data = new Data;
        
        $active = 'Active';
        $nama_p = 'MP0000000003';
        
        // Prepare query with search
        $search_condition = '';
        $params = [':active' => $active, ':nama_p' => $nama_p];
        
        if (!empty($cari)) {
            $search_condition = 'AND B.nama_pro LIKE :cari';
            $params[':cari'] = '%' . $cari . '%';
        }
        
        $query = "SELECT A.id_psd, A.no_bcode, A.tgl_expired, A.created_at, B.id_pro, A.gudang, A.tgl_psd, A.sisa_psd, B.nama_p, B.nama_pro, B.berat_pro, B.minstok_pro, C.harga_phg, C.hargap_phg, E.nama_spr 
                 FROM produk_stokdetail AS A 
                 LEFT JOIN produk AS B ON A.id_pro=B.id_pro 
                 LEFT JOIN produk_harga AS C ON B.id_pro=C.id_pro 
                 LEFT JOIN kategori_produk AS D ON B.id_kpr=D.id_kpr 
                 LEFT JOIN satuan_produk AS E ON B.id_spr=E.id_spr 
                 WHERE A.sisa_psd>0 $search_condition AND C.status_phg=:active AND B.nama_p = :nama_p 
                 ORDER BY B.nama_pro ASC";
        
        $master = $conn->prepare($query);
        foreach ($params as $key => $value) {
            $master->bindValue($key, $value);
        }
        $master->execute();
        
        $no = 1;
        $previousName = '';
        $previousProductId = '';
        
        while($hasil = $master->fetch(PDO::FETCH_ASSOC)) {
            // Hitung DOI (Days On Inventory)
            $doi_days = 0;
            $doi_text = 'N/A';
            if (!empty($hasil['created_at'])) {
                $created_date = date_create($hasil['created_at']);
                $current_date = date_create();
                $doi_diff = date_diff($created_date, $current_date);
                $doi_days = $doi_diff->days;
                $doi_text = ($doi_days == 0) ? '0 hari' : $doi_days . ' hari';
            }
            
            // Format expired date
            $expired_formatted = 'N/A';
            if (!empty($hasil['tgl_expired']) && $hasil['tgl_expired'] != '0000-00-00') {
                $expired_formatted = date('Y-m', strtotime($hasil['tgl_expired']));
            }
            
            // Check nama produk untuk tampilan
            $nameColumn = ($previousName !== $hasil['nama_pro']) ? $hasil['nama_pro'] : '';
            $currentProductId = $hasil['id_pro'];
            
            $inventory_data[] = [
                'no' => $no,
                'nama_produk' => $nameColumn,
                'expired_date' => $expired_formatted,
                'sisa_stok' => intval($hasil['sisa_psd']),
                'sisa_stok_formatted' => $data->angka($hasil['sisa_psd']),
                'doi' => $doi_text,
                'doi_days' => $doi_days,
                'harga' => intval($hasil['harga_phg']),
                'harga_formatted' => $data->angka($hasil['harga_phg']),
                'id_psd' => $hasil['id_psd'],
                'id_pro' => $hasil['id_pro'],
                'kode_produk' => $hasil['no_bcode'] ?? '',
                'gudang' => $hasil['gudang'] ?? '',
                'minstok' => intval($hasil['minstok_pro'])
            ];
            
            $previousName = $hasil['nama_pro'];
            $previousProductId = $currentProductId;
            $no++;
        }
        
        $total_items = count($inventory_data);
        
    } catch(Exception $e) {
        error_log("Inventory query error: " . $e->getMessage());
    }
}

// If no data or not connected, provide demo data
if (empty($inventory_data)) {
    $inventory_data = [
        [
            'no' => 1,
            'nama_produk' => 'Contoh Produk Demo',
            'expired_date' => '2024-12',
            'sisa_stok' => 100,
            'sisa_stok_formatted' => '100',
            'doi' => '25 hari',
            'doi_days' => 25,
            'harga' => 15000,
            'harga_formatted' => '15,000',
            'id_psd' => 'demo1',
            'id_pro' => 'demo_pro1'
        ],
        [
            'no' => 2,
            'nama_produk' => 'Sample Product Test',
            'expired_date' => '2025-01',
            'sisa_stok' => 75,
            'sisa_stok_formatted' => '75',
            'doi' => '95 hari',
            'doi_days' => 95,
            'harga' => 25000,
            'harga_formatted' => '25,000',
            'id_psd' => 'demo2',
            'id_pro' => 'demo_pro2'
        ]
    ];
    $total_items = 2;
}

$response['data'] = [
    'inventory_list' => $inventory_data,
    'total_items' => $total_items,
    'search_term' => $cari,
    'cabang_info' => [
        'id' => $cabang_id,
        'name' => $response['cabang']
    ]
];

$response['data_source_note'] = "Inventory data from " . $response['cabang'];

// Close database connection
if ($conn) {
    $conn = null;
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>