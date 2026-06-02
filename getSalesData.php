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
error_log("Sales API Request: " . date('Y-m-d H:i:s') . " - " . $_SERVER['REQUEST_URI']);

// Function to fetch sales from remote cabang API
function fetchCabangSales($cabang_info, $cari, $conn) {
    if ($cabang_info['self_apl'] == 1) {
        return ['source' => 'local', 'cabang_id' => $cabang_info['id_apl']];
    }
    
    $remote_url = rtrim($cabang_info['base_url_apl'], '/') . '/api/getSalesData.php';
    $remote_key = $cabang_info['key_apl'];
    $remote_params = [
        'key' => $remote_key,
        'action' => 'sales_list',
        'cari' => $cari
    ];
    
    $query_string = http_build_query($remote_params);
    $full_url = $remote_url . '?' . $query_string;
    
    error_log("Fetching sales from {$cabang_info['nama_apl']}: $full_url");
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $full_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Sales API Client v1.0');
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($http_code !== 200 || !$response) {
        error_log("Failed to fetch sales from {$cabang_info['nama_apl']}: HTTP $http_code, Error: $curl_error");
        return null;
    }
    
    $data = json_decode($response, true);
    if (!$data || !isset($data['status']) || $data['status'] !== 'success') {
        error_log("Invalid sales response from {$cabang_info['nama_apl']}");
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

if ($action !== 'sales_list') {
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
    'cabang' => ($cabang_id === 'ALL') ? 'ALL CABANG' : 'Medan',
    'cabang_id' => $cabang_id,
    'search' => $cari,
    'data_source' => $db_connected ? 'database' : 'dummy',
    'data' => []
];

// Check if specific cabang selected
$selected_cabang = null;
if ($cabang_id !== 'ALL' && $cabang_id !== 'JABODETABEK' && $db_connected && $conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM aplikasi WHERE id_apl = :id_apl AND active_apl = 1");
        $stmt->bindParam(':id_apl', $cabang_id, PDO::PARAM_STR);
        $stmt->execute();
        $selected_cabang = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($selected_cabang) {
            $response['cabang'] = $selected_cabang['nama_apl'];
            error_log("Selected cabang for sales: " . $selected_cabang['nama_apl']);
        }
    } catch(Exception $e) {
        error_log("Failed to fetch cabang data: " . $e->getMessage());
    }
}

// Handle special cabang groups - AGGREGATE DATA FROM MULTIPLE BRANCHES
if ($cabang_id === 'ALL' || $cabang_id === 'JABODETABEK') {
    error_log("Aggregation mode: $cabang_id");
    
    $all_sales = [];
    $cabang_sources = [];
    $no_counter = 1;
    
    // Get list of active cabang
    try {
        $query = "SELECT * FROM aplikasi WHERE active_apl = 1";
        if ($cabang_id === 'JABODETABEK') {
            $query .= " AND (id_apl IN ('APL01', 'APL02', 'APL03', 'APL04', 'APL05', 'APL06') OR nama_apl LIKE '%Jakarta%' OR nama_apl LIKE '%Bogor%' OR nama_apl LIKE '%Depok%' OR nama_apl LIKE '%Tangerang%' OR nama_apl LIKE '%Bekasi%')";
        }
        $query .= " ORDER BY nama_apl ASC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $cabang_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Found " . count($cabang_list) . " active branches for $cabang_id");
        
        foreach ($cabang_list as $cabang_info) {
            error_log("Fetching sales from: " . $cabang_info['nama_apl']);
            
            if ($cabang_info['self_apl'] == 1) {
                if ($cabang_id === 'JABODETABEK') {
                    error_log("  -> Skipping Medan for JABODETABEK");
                    continue;
                } else {
                    $cabang_sources[] = $cabang_info['nama_apl'] . " (local)";
                    error_log("  -> Local data, will fetch from database");
                    continue;
                }
            }
            
            // Fetch from remote API
            $remote_data = fetchCabangSales($cabang_info, $cari, $conn);
            
            if ($remote_data && isset($remote_data['data']['sales_list'])) {
                $remote_items = $remote_data['data']['sales_list'];
                error_log("  -> Got " . count($remote_items) . " items from remote");
                
                foreach ($remote_items as $item) {
                    $item['no'] = $no_counter++;
                    
                    // Check if JABODETABEK region
                    $isJabodetabek = in_array($cabang_info['id_apl'], ['APL01', 'APL02', 'APL03', 'APL04', 'APL05', 'APL06']) 
                                     || stripos($cabang_info['nama_apl'], 'Bekasi') !== false
                                     || stripos($cabang_info['nama_apl'], 'Cibinong') !== false
                                     || stripos($cabang_info['nama_apl'], 'Puri') !== false;
                    
                    if ($isJabodetabek) {
                        $item['source_cabang'] = 'JABODETABEK';
                        $item['source_cabang_detail'] = $cabang_info['nama_apl'];
                    } else {
                        $item['source_cabang'] = $cabang_info['nama_apl'];
                    }
                    
                    $item['source_cabang_id'] = $cabang_info['id_apl'];
                    $all_sales[] = $item;
                }
                
                $cabang_sources[] = $cabang_info['nama_apl'] . " (" . count($remote_items) . " items)";
            } else {
                error_log("  -> Failed to fetch from " . $cabang_info['nama_apl']);
                $cabang_sources[] = $cabang_info['nama_apl'] . " (failed)";
            }
        }
        
    } catch(Exception $e) {
        error_log("Error in aggregation: " . $e->getMessage());
    }
    
    $response['cabang'] = ($cabang_id === 'ALL') ? 'ALL CABANG' : 'JABODETABEK';
    $response['aggregation_mode'] = true;
    $response['aggregated_from'] = $cabang_sources;
}

// If selected cabang is not self (Medan) and has external API, fetch from remote
if ($selected_cabang && $selected_cabang['self_apl'] == 0 && !empty($selected_cabang['base_url_apl']) && $cabang_id !== 'ALL' && $cabang_id !== 'JABODETABEK') {
    $remote_data = fetchCabangSales($selected_cabang, $cari, $conn);
    if ($remote_data && isset($remote_data['data'])) {
        $remote_data['data_source_note'] = "Sales data from {$selected_cabang['nama_apl']} via remote API";
        echo json_encode($remote_data);
        exit;
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => "Gagal mengambil data penjualan dari {$selected_cabang['nama_apl']}",
            'cabang' => $selected_cabang['nama_apl'],
            'cabang_id' => $cabang_id,
            'suggestion' => 'Coba pilih cabang lain atau gunakan ALL CABANG'
        ]);
        exit;
    }
} else if ($cabang_id !== 'ALL' && $cabang_id !== 'JABODETABEK' && $cabang_id !== 'APL08' && !$selected_cabang) {
    echo json_encode([
        'status' => 'error',
        'message' => "Cabang dengan ID $cabang_id tidak ditemukan",
        'cabang_id' => $cabang_id,
        'suggestion' => 'Pilih cabang yang tersedia dari dropdown'
    ]);
    exit;
}

// Get local sales data (Medan or ALL)
$sales_data = [];
$total_items = 0;

if ($db_connected && $conn) {
    try {
        require_once('../config/function/data.php');
        $data = new Data;
        
        // Prepare search condition
        $search = $data->cekcari($cari, '-', ' ');
        $search_condition = '';
        $params = [];
        
        if (!empty($search)) {
            $search_condition = "AND (A.kode_tfk LIKE :search OR B.nama_out LIKE :search OR C.nama_pro LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        // Query penjualan PIM detail
        $query = "SELECT A.id_tfk, A.kode_tfk, A.tgl_tfk, 
                         B.nama_out,
                         COALESCE(C.nama_pro, 'Produk tidak tersedia') as nama_pro,
                         COALESCE(C.kode_pro, '-') as kode_pro,
                         COALESCE(D.jumlah_tfd, 0) as jumlah_tfd,
                         COALESCE(D.harga_tfd, 0) as harga_tfd,
                         D.id_tfd
                  FROM transaksi_faktur_pim AS A 
                  LEFT JOIN outlet AS B ON A.id_out = B.id_out
                  LEFT JOIN transaksi_fakturdetail_pim AS D ON A.id_tfk = D.id_tfk
                  LEFT JOIN produk AS C ON D.id_pro = C.id_pro
                  WHERE 1=1 $search_condition
                  ORDER BY A.tgl_tfk DESC, A.kode_tfk ASC
                  LIMIT 500";
        
        $master = $conn->prepare($query);
        foreach ($params as $key => $value) {
            $master->bindValue($key, $value);
        }
        $master->execute();
        
        $no = 1;
        
        while($hasil = $master->fetch(PDO::FETCH_ASSOC)) {
            // Determine numbering
            $item_no = ($cabang_id === 'ALL' || $cabang_id === 'JABODETABEK') ? $no_counter++ : $no;
            
            $item_data = [
                'no' => $item_no,
                'nama_outlet' => $hasil['nama_out'] ?: 'N/A',
                'tgl_faktur' => $hasil['tgl_tfk'] ?: 'N/A',
                'no_faktur' => $hasil['kode_tfk'] ?: 'N/A',
                'nama_produk' => $hasil['nama_pro'] ?: 'N/A',
                'qty' => intval($hasil['jumlah_tfd']),
                'qty_formatted' => $data->angka($hasil['jumlah_tfd']),
                'harga' => intval($hasil['harga_tfd']),
                'harga_formatted' => $data->angka($hasil['harga_tfd']),
                'id_tfd' => $hasil['id_tfd']
            ];
            
            // Add source info if aggregation mode
            if ($cabang_id === 'ALL') {
                $item_data['source_cabang'] = 'Medan';
                $item_data['source_cabang_id'] = 'APL08';
            }
            
            $sales_data[] = $item_data;
            $no++;
        }
        
        $total_items = count($sales_data);
        
    } catch(Exception $e) {
        error_log("Sales query error: " . $e->getMessage());
    }
}

// Merge aggregated data if in aggregation mode
if (($cabang_id === 'ALL' || $cabang_id === 'JABODETABEK') && isset($all_sales)) {
    if ($cabang_id === 'ALL') {
        $all_sales = array_merge($all_sales, $sales_data);
    }
    
    // Sort by date and no_faktur
    usort($all_sales, function($a, $b) {
        $dateCompare = strcmp($b['tgl_faktur'] ?? '', $a['tgl_faktur'] ?? '');
        if ($dateCompare !== 0) return $dateCompare;
        return strcmp($b['no_faktur'] ?? '', $a['no_faktur'] ?? '');
    });
    
    // Renumber
    $counter = 1;
    foreach ($all_sales as &$item) {
        $item['no'] = $counter++;
    }
    
    $sales_data = $all_sales;
    $total_items = count($sales_data);
    
    error_log("Total aggregated sales items: $total_items");
    $response['data_source_note'] = "Agregasi data dari " . count($cabang_sources) . " cabang: " . implode(', ', $cabang_sources);
}

$response['data'] = [
    'sales_list' => $sales_data,
    'total_items' => $total_items,
    'search_term' => $cari,
    'cabang_info' => [
        'id' => $cabang_id,
        'name' => $response['cabang']
    ]
];

if (!isset($response['data_source_note']) || empty($response['data_source_note'])) {
    $response['data_source_note'] = "Sales data from " . $response['cabang'];
}

error_log("DEBUG: Final response for {$cabang_id} - Items: {$total_items}, Cabang: {$response['cabang']}");

if ($conn) {
    $conn = null;
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>
