<?php
// Error handling improved
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Access-Control-Allow-Origin: *');
    header("Content-type: application/json; charset=utf-8");
    echo json_encode(["success" => false, "message" => "Method Not Allowed"]);
    exit;
}

require_once('../config/connection/connection.php');
require_once('../config/connection/security.php');
require_once('../config/function/data.php');
$secu = new Security;
$base = new DB;
$data = new Data;
$conn = $base->open();

$encrypt = $secu->injection($_GET['encrypt'] ?? '');
$tgl = date('Y-m-d');
$source = $data->self_apl();
$id_apl = $source['id_apl'];
$nama_apl = $source['nama_apl'];
$sourceKey = $source['key_apl'];

// Log file untuk debugging
$log_file = __DIR__ . "/branch_api_" . date('Ymd') . ".log";
file_put_contents($log_file, date('Y-m-d H:i:s') . " API Called with encrypt=$encrypt\n", FILE_APPEND);

// Validasi key
if (md5($tgl . "#" . $sourceKey) != $encrypt) {
    http_response_code(401);
    header('Access-Control-Allow-Origin: *');
    header("Content-type: application/json; charset=utf-8");
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized",
        "data" => []
    ]);
    exit;
}

try {
    // 1. Dapatkan data lokal dengan nama outlet yang dinormalisasi
    file_put_contents($log_file, date('Y-m-d H:i:s') . " Getting local data\n", FILE_APPEND);
    
    $where = "A.status_out = 'Active'";
    $qData = "SELECT 
            A.id_out,
            A.ofcode_out,
            TRIM(UPPER(A.nama_out)) AS nama_out_key,
            A.nama_out,
            A.status_faktur AS tipe_faktur,
            C.diskon_odi AS diskon,
            A.profit AS modal,
            D.kode_kot AS kategori_outlet
        FROM outlet AS A 
        INNER JOIN outlet_diskon AS C ON A.id_out=C.id_out 
        LEFT JOIN kategori_outlet AS D ON A.id_kot=D.id_kot 
        WHERE $where
        ORDER BY A.nama_out ASC";
    $stmt = $conn->prepare($qData);
    $stmt->execute();

    $localData = [];
    $no = 1;
    $year = date('Y');
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $profit_margin = floatval($row['modal']) - floatval($row['diskon']);
        $id_out = $row['id_out'];

        // Get monthly data
        $monthly_data = $data->get_all_months_data($id_out);
        
        // Get subtotal faktur
        $query_subtot = "
            SELECT SUM(subtot_tfk) AS total FROM (
                SELECT subtot_tfk FROM transaksi_faktur WHERE id_out='$id_out' AND YEAR(tgl_tfk)='$year'
                UNION ALL
                SELECT subtot_tfk FROM transaksi_faktur_pim WHERE id_out='$id_out' AND YEAR(tgl_tfk)='$year'
                UNION ALL
                SELECT subtot_tfk FROM transaksi_faktur_c WHERE id_out='$id_out' AND YEAR(tgl_tfk)='$year'
                UNION ALL
                SELECT subtot_tfk FROM transaksi_faktur_np_medan WHERE id_out='$id_out' AND YEAR(tgl_tfk)='$year'
            ) AS all_faktur
        ";
        $stmt_subtot = $conn->query($query_subtot);
        $subtot_data = $stmt_subtot->fetch(PDO::FETCH_ASSOC);
        $subtot_tfk = $subtot_data ? $subtot_data['total'] : 0;

        $localData[] = [
            "no" => $no++,
            "id_out" => $row['id_out'],
            "ofcode_out" => $row['ofcode_out'],
            "nama_out" => $row['nama_out'],
            "nama_out_key" => $row['nama_out_key'],
            "tipe_faktur" => $row['tipe_faktur'],
            "diskon" => $row['diskon'],
            "modal" => $row['modal'],
            "profit_margin" => $profit_margin,
            "kategori_outlet" => $row['kategori_outlet'],
            "id_apl" => $id_apl,
            "cabang" => $nama_apl,
            "subtotal_tfk" => $subtot_tfk,
            "monthly_data" => $monthly_data
        ];
    }

    file_put_contents($log_file, date('Y-m-d H:i:s') . " Found " . count($localData) . " local outlets\n", FILE_APPEND);

    // 2. Ambil data dari cabang lain jika bukan dipanggil dari API cabang lain
    $branchesData = [];
    $branches_info = [];
    
    // Cek apakah API dipanggil dari aplikasi utama
    $from_api = isset($_GET['from_api']) && $_GET['from_api'] == 1;
    
    if (!$from_api) {
        // Tambahkan ini untuk melihat semua cabang di log
        $all_branches_query = "SELECT id_apl, nama_apl, base_url_apl, key_apl, active_apl, self_apl FROM aplikasi";
        $all_branches_stmt = $conn->query($all_branches_query);
        $all_branches = $all_branches_stmt->fetchAll(PDO::FETCH_ASSOC);
        file_put_contents($log_file, date('Y-m-d H:i:s') . " All branches in DB: " . print_r($all_branches, true) . "\n", FILE_APPEND);
        
        // Mendapatkan semua cabang aktif kecuali diri sendiri (perbaiki query)
        $branch_query = "SELECT id_apl, base_url_apl, nama_apl, key_apl FROM aplikasi WHERE active_apl = 1 AND self_apl = 0";
        $branch_stmt = $conn->query($branch_query);
        $branches = $branch_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        file_put_contents($log_file, date('Y-m-d H:i:s') . " Found " . count($branches) . " branches to query\n", FILE_APPEND);
        
        foreach ($branches as $branch) {
            $branch_url = $branch['base_url_apl'];
            $branch_name = $branch['nama_apl'];
            $branch_id = $branch['id_apl'];
            $branch_key = $branch['key_apl'];
            
            file_put_contents($log_file, date('Y-m-d H:i:s') . " Processing branch: $branch_name (ID: $branch_id) with key: $branch_key\n", FILE_APPEND);
            
            if (empty($branch_url)) {
                file_put_contents($log_file, date('Y-m-d H:i:s') . " Branch $branch_name has no URL, skipping\n", FILE_APPEND);
                continue;
            }
            
            // Pastikan URL memiliki format yang benar
            if (!preg_match("~^(?:f|ht)tps?://~i", $branch_url)) {
                $branch_url = "http://" . $branch_url;
            }
            
            // Pastikan URL diakhiri dengan /
            $branch_url = rtrim($branch_url, '/') . '/';
            
            file_put_contents($log_file, date('Y-m-d H:i:s') . " Final branch URL: $branch_url\n", FILE_APPEND);
            
            $branches_info[] = [
                "id" => $branch_id,
                "name" => $branch_name,
                "url" => $branch_url
            ];
            
            // Generate encrypt key untuk cabang ini
            $branch_encrypt = md5($tgl . "#" . $branch_key);
            file_put_contents($log_file, date('Y-m-d H:i:s') . " Branch encrypt key: $branch_encrypt (using date $tgl and key $branch_key)\n", FILE_APPEND);
            
            $apiUrl = $branch_url . 'api/getProfitMarginAll.php?encrypt=' . $branch_encrypt . '&from_api=1';
            file_put_contents($log_file, date('Y-m-d H:i:s') . " Calling API: $apiUrl\n", FILE_APPEND);
            
            // Panggil API dengan timeout yang lebih lama
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 180); // 3 menit
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
            
            // Menambahkan header yang lebih lengkap
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'Content-Type: application/json',
                'Connection: Keep-Alive'
            ]);
            
            $response = curl_exec($ch);
            $curl_error = curl_error($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            file_put_contents($log_file, date('Y-m-d H:i:s') . " API Response: Code=$http_code, Error=$curl_error\n", FILE_APPEND);
            
            if ($response === false || $http_code !== 200) {
                file_put_contents($log_file, date('Y-m-d H:i:s') . " API call failed\n", FILE_APPEND);
                continue;
            }
            
            $apiData = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                file_put_contents($log_file, date('Y-m-d H:i:s') . " JSON decode error: " . json_last_error_msg() . "\n", FILE_APPEND);
                file_put_contents($log_file, date('Y-m-d H:i:s') . " Raw response: " . substr($response, 0, 1000) . "\n", FILE_APPEND);
                continue;
            }
            
            if ($apiData && isset($apiData['success']) && $apiData['success'] && !empty($apiData['data'])) {
                $branchData = $apiData['data'];
                file_put_contents($log_file, date('Y-m-d H:i:s') . " Got " . count($branchData) . " outlets from branch: $branch_name\n", FILE_APPEND);
                
                // Tambahkan nama cabang ke setiap record
                foreach ($branchData as &$outlet) {
                    $outlet['cabang'] = $branch_name;
                    
                    // Normalisasi nama outlet untuk konsistensi
                    if (!isset($outlet['nama_out_key'])) {
                        $outlet['nama_out_key'] = strtoupper(trim($outlet['nama_out']));
                    }
                }
                
                $branchesData = array_merge($branchesData, $branchData);
            } else {
                file_put_contents($log_file, date('Y-m-d H:i:s') . " No valid data in response\n", FILE_APPEND);
            }
        }
    }

    // Kombinasikan data lokal dengan data cabang
    $allData = array_merge($localData, $branchesData);
    file_put_contents($log_file, date('Y-m-d H:i:s') . " Combined data count: " . count($allData) . " (Local: " . count($localData) . ", Branches: " . count($branchesData) . ")\n", FILE_APPEND);

    // Kelompokkan outlet berdasarkan nama
    $grouped = [];
    foreach ($allData as $row) {
        if (empty($row['nama_out'])) continue;
        
        // Normalisasi nama outlet untuk pencocokan konsisten
        $nama_out_key = isset($row['nama_out_key']) ? 
            strtoupper(trim($row['nama_out_key'])) : 
            strtoupper(trim($row['nama_out']));
        
        // Debug informasi
        file_put_contents($log_file, date('Y-m-d H:i:s') . " Processing: " . $nama_out_key . " from " . $row['cabang'] . "\n", FILE_APPEND);
        
        if (!isset($grouped[$nama_out_key])) {
            // Inisialisasi grup baru
            $grouped[$nama_out_key] = [
                'id_out' => [$row['id_out']],
                'ofcode_out' => $row['ofcode_out'] ?? '',
                'nama_out' => $row['nama_out'],
                'nama_out_key' => $nama_out_key,
                'tipe_faktur' => $row['tipe_faktur'] ?? '',
                'diskon' => floatval($row['diskon'] ?? 0),
                'modal' => floatval($row['modal'] ?? 0),
                'profit_margin' => floatval($row['profit_margin'] ?? 0),
                'kategori_outlet' => $row['kategori_outlet'] ?? '',
                'cabang' => [$row['cabang']],
                'subtotal_tfk' => floatval($row['subtotal_tfk'] ?? 0),
                'monthly_data' => [
                    'januari' => floatval($row['monthly_data']['januari'] ?? 0),
                    'februari' => floatval($row['monthly_data']['februari'] ?? 0),
                    'maret' => floatval($row['monthly_data']['maret'] ?? 0),
                    'april' => floatval($row['monthly_data']['april'] ?? 0),
                    'mei' => floatval($row['monthly_data']['mei'] ?? 0),
                    'juni' => floatval($row['monthly_data']['juni'] ?? 0),
                    'juli' => floatval($row['monthly_data']['juli'] ?? 0),
                    'agustus' => floatval($row['monthly_data']['agustus'] ?? 0),
                    'september' => floatval($row['monthly_data']['september'] ?? 0),
                    'oktober' => floatval($row['monthly_data']['oktober'] ?? 0),
                    'november' => floatval($row['monthly_data']['november'] ?? 0),
                    'desember' => floatval($row['monthly_data']['desember'] ?? 0)
                ]
            ];
        } else {
            // Gabungkan data ke grup yang sudah ada
            if (!in_array($row['id_out'], $grouped[$nama_out_key]['id_out'])) {
                $grouped[$nama_out_key]['id_out'][] = $row['id_out'];
            }
            
            if (!in_array($row['cabang'], $grouped[$nama_out_key]['cabang'])) {
                $grouped[$nama_out_key]['cabang'][] = $row['cabang'];
            }
            
            // Jumlahkan subtotal_tfk
            $old_subtot = $grouped[$nama_out_key]['subtotal_tfk'];
            $new_subtot = floatval($row['subtotal_tfk'] ?? 0);
            $grouped[$nama_out_key]['subtotal_tfk'] = $old_subtot + $new_subtot;
            
            // Log penjumlahan
            file_put_contents($log_file, date('Y-m-d H:i:s') . " Adding subtotal: $old_subtot + $new_subtot = " . ($old_subtot + $new_subtot) . "\n", FILE_APPEND);
            
            // Jumlahkan nilai bulanan
            if (isset($row['monthly_data']) && is_array($row['monthly_data'])) {
                foreach ($row['monthly_data'] as $month => $value) {
                    if (isset($grouped[$nama_out_key]['monthly_data'][$month])) {
                        $old_val = $grouped[$nama_out_key]['monthly_data'][$month];
                        $new_val = floatval($value);
                        $grouped[$nama_out_key]['monthly_data'][$month] = $old_val + $new_val;
                        
                        // Log penjumlahan bulanan
                        file_put_contents($log_file, date('Y-m-d H:i:s') . " Adding $month: $old_val + $new_val = " . ($old_val + $new_val) . "\n", FILE_APPEND);
                    }
                }
            }
        }
    }
    
    // Konversi data yang dikelompokkan kembali ke array terindeks
    $result = [];
    foreach ($grouped as $key => $data) {
        $data['cabang_display'] = implode(', ', $data['cabang']);
        $result[] = $data;
    }

    file_put_contents($log_file, date('Y-m-d H:i:s') . " Final grouped data count: " . count($result) . "\n", FILE_APPEND);
    
    // Urutkan hasil berdasarkan nama outlet
    usort($result, function($a, $b) {
        return strcmp($a['nama_out'], $b['nama_out']);
    });

    // Kembalikan data yang dikelompokkan
    header('Access-Control-Allow-Origin: *');
    header("Content-type: application/json; charset=utf-8");
    echo json_encode([
        "success" => true,
        "main_branch" => [
            "id" => $id_apl,
            "name" => $nama_apl
        ],
        "branches" => $branches_info,
        "data" => $result
    ]);
} catch (Exception $e) {
    // Tangani exception
    file_put_contents($log_file, date('Y-m-d H:i:s') . " ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    file_put_contents($log_file, date('Y-m-d H:i:s') . " ERROR Trace: " . $e->getTraceAsString() . "\n", FILE_APPEND);
    http_response_code(500);
    header('Access-Control-Allow-Origin: *');
    header("Content-type: application/json; charset=utf-8");
    echo json_encode([
        "success" => false,
        "message" => "Server error: " . $e->getMessage(),
        "data" => []
    ]);
}
?>