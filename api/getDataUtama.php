<?php

require_once('../config/connection/connection.php');
require_once('../config/connection/security.php');
require_once('../config/function/data.php');

$base = new DB;
$secu = new Security;
$data = new Data;
$conn = $base->open();
$tanggal = date('Y-m-d');
$tahun = date('Y');

// Validate encryption
$encrypt = $secu->injection(@$_GET['encrypt']);
$target_key = $secu->injection(@$_GET['key']);

// Get target aplikasi key
$key_valid = false;
try {
    $stmt = $conn->prepare("SELECT key_apl FROM aplikasi WHERE key_apl = :key AND active_apl = 1");
    $stmt->bindParam(':key', $target_key, PDO::PARAM_STR);
    $stmt->execute();
    $apl_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($apl_data) {
        $expected_encrypt = md5($tanggal . '#' . $apl_data['key_apl']);
        if ($encrypt === $expected_encrypt) {
            $key_valid = true;
        }
    }
} catch(Exception $e) {
    error_log("API getDataUtama validation error: " . $e->getMessage());
}

if (!$key_valid) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid encryption or key'
    ]);
    exit;
}

// Get parameters
$bulan1 = $secu->injection(@$_GET['bulan1']); // Bulan berjalan
$bulan2 = $secu->injection(@$_GET['bulan2']); // Bulan lalu
$principle = $secu->injection(@$_GET['principle']); // Optional principle filter

// Set defaults if empty
if (empty($bulan1)) {
    $bulan1 = date('Y-m');
}
if (empty($bulan2)) {
    $bulan2 = date('Y-m', strtotime('-1 month'));
}

$response = [
    'status' => 'success',
    'data' => [
        'statistics' => [
            'total_sales' => 0,
            'total_products' => 0,
            'min_stock_products' => 0,
            'expired_products' => 0,
            'unpaid_receive' => 0,
            'unpaid_sales' => 0,
            'sipa_expire' => 0,
            'sia_expire' => 0,
            'nie_expire' => 0
        ],
        'top_outlets_bulan_lalu' => [],
        'top_outlets_bulan_berjalan' => [],
        'chart_data' => [
            'categories' => [],
            'series' => []
        ]
    ],
    'source' => 'API',
    'bulan1' => $bulan1,
    'bulan2' => $bulan2
];

try {
    // 1. Total Sales (year to date)
    $stmt = $conn->prepare("SELECT SUM(total_tfk) AS total FROM transaksi_faktur_pim WHERE YEAR(tgl_tfk) = :tahun");
    $stmt->bindParam(':tahun', $tahun, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['data']['statistics']['total_sales'] = (int)($result['total'] ?? 0);
    
    // 2. Total Products in stock
    $stmt = $conn->query("SELECT COUNT(DISTINCT A.id_pro) AS total 
        FROM (SELECT id_pro, SUM(sisa_psd) AS jumlah FROM produk_stokdetail GROUP BY id_pro) AS A 
        WHERE A.jumlah > 0");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['data']['statistics']['total_products'] = (int)($result['total'] ?? 0);
    
    // 3. Min stock products
    $stmt = $conn->query("SELECT COUNT(B.id_pro) AS total 
        FROM (SELECT id_pro, SUM(sisa_psd) AS jumlah FROM produk_stokdetail GROUP BY id_pro) AS A 
        LEFT JOIN produk AS B ON A.id_pro = B.id_pro 
        WHERE A.jumlah <= B.minstok_pro AND A.jumlah > 0");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['data']['statistics']['min_stock_products'] = (int)($result['total'] ?? 0);
    
    // 4. Expired products
    $limit_expired = $data->sistem('limit_expired');
    $stmt = $conn->prepare("SELECT COUNT(id_psd) AS total FROM produk_stokdetail 
        WHERE TIMESTAMPDIFF(DAY, :tanggal, tgl_expired) <= :limit 
        OR TIMESTAMPDIFF(DAY, tgl_expired, :tanggal) > 0");
    $stmt->bindParam(':tanggal', $tanggal, PDO::PARAM_STR);
    $stmt->bindParam(':limit', $limit_expired, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['data']['statistics']['expired_products'] = (int)($result['total'] ?? 0);
    
    // 5. Unpaid Receive
    $limit_supplier = $data->sistem('limit_supplier');
    $stmt = $conn->prepare("SELECT COUNT(id_tre) AS total FROM transaksi_receive 
        WHERE status_tre != 'Lunas' 
        AND (TIMESTAMPDIFF(DAY, :tanggal, tgl_limit) <= :limit 
        OR TIMESTAMPDIFF(DAY, tgl_limit, :tanggal) > 0)");
    $stmt->bindParam(':tanggal', $tanggal, PDO::PARAM_STR);
    $stmt->bindParam(':limit', $limit_supplier, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['data']['statistics']['unpaid_receive'] = (int)($result['total'] ?? 0);
    
    // 6. Unpaid Sales
    $limit_outlet = $data->sistem('limit_outlet');
    $stmt = $conn->prepare("SELECT COUNT(id_tfk) AS total FROM transaksi_faktur_pim 
        WHERE status_tfk != 'Lunas' 
        AND (TIMESTAMPDIFF(DAY, :tanggal, tgl_limit) <= :limit 
        OR TIMESTAMPDIFF(DAY, tgl_limit, :tanggal) > 0)");
    $stmt->bindParam(':tanggal', $tanggal, PDO::PARAM_STR);
    $stmt->bindParam(':limit', $limit_outlet, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['data']['statistics']['unpaid_sales'] = (int)($result['total'] ?? 0);
    
    // 7. SIPA Expire (KLG01)
    $stmt = $conn->prepare("SELECT COUNT(A.id_out) AS total 
        FROM outlet AS A 
        INNER JOIN outlet_legal AS B ON A.id_out = B.id_out 
        INNER JOIN kategori_legal AS C ON B.id_klg = C.id_klg 
        WHERE C.id_klg = 'KLG01' 
        AND TIMESTAMPDIFF(MONTH, :tanggal, B.expired_ole) <= C.parameter_klg");
    $stmt->bindParam(':tanggal', $tanggal, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['data']['statistics']['sipa_expire'] = (int)($result['total'] ?? 0);
    
    // 8. SIA Expire (KLG02)
    $stmt = $conn->prepare("SELECT COUNT(A.id_out) AS total 
        FROM outlet AS A 
        INNER JOIN outlet_legal AS B ON A.id_out = B.id_out 
        INNER JOIN kategori_legal AS C ON B.id_klg = C.id_klg 
        WHERE C.id_klg = 'KLG02' 
        AND TIMESTAMPDIFF(MONTH, :tanggal, B.expired_ole) <= C.parameter_klg");
    $stmt->bindParam(':tanggal', $tanggal, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['data']['statistics']['sia_expire'] = (int)($result['total'] ?? 0);
    
    // 9. NIE Expire
    $stmt = $conn->prepare("SELECT COUNT(id_pro) AS total FROM produk 
        WHERE TIMESTAMPDIFF(DAY, :tanggal, tgl_nie) <= :limit 
        OR TIMESTAMPDIFF(DAY, tgl_nie, :tanggal) > 0");
    $stmt->bindParam(':tanggal', $tanggal, PDO::PARAM_STR);
    $stmt->bindParam(':limit', $limit_expired, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['data']['statistics']['nie_expire'] = (int)($result['total'] ?? 0);
    
    // 10. Top Outlets Bulan Lalu
    if (!empty($principle)) {
        $stmt = $conn->prepare("SELECT B.id_out, B.nama_out, SUM(A.total_tfk) AS total 
            FROM transaksi_faktur_pim AS A 
            LEFT JOIN outlet AS B ON A.id_out = B.id_out 
            LEFT JOIN transaksi_fakturdetail_pim AS D ON A.id_tfk = D.id_tfk 
            LEFT JOIN produk AS P ON D.id_pro = P.id_pro 
            WHERE LEFT(A.tgl_tfk, 7) = :bulan2 
            AND P.nama_p = :principle 
            GROUP BY B.id_out 
            ORDER BY total DESC 
            LIMIT 10");
        $stmt->bindParam(':bulan2', $bulan2, PDO::PARAM_STR);
        $stmt->bindParam(':principle', $principle, PDO::PARAM_STR);
    } else {
        $stmt = $conn->prepare("SELECT B.id_out, B.nama_out, SUM(A.total_tfk) AS total 
            FROM transaksi_faktur_pim AS A 
            LEFT JOIN outlet AS B ON A.id_out = B.id_out 
            WHERE LEFT(A.tgl_tfk, 7) = :bulan2 
            GROUP BY B.id_out 
            ORDER BY total DESC 
            LIMIT 10");
        $stmt->bindParam(':bulan2', $bulan2, PDO::PARAM_STR);
    }
    $stmt->execute();
    $response['data']['top_outlets_bulan_lalu'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 11. Top Outlets Bulan Berjalan
    if (!empty($principle)) {
        $stmt = $conn->prepare("SELECT B.id_out, B.nama_out, SUM(A.total_tfk) AS total 
            FROM transaksi_faktur_pim AS A 
            LEFT JOIN outlet AS B ON A.id_out = B.id_out 
            LEFT JOIN transaksi_fakturdetail_pim AS D ON A.id_tfk = D.id_tfk 
            LEFT JOIN produk AS P ON D.id_pro = P.id_pro 
            WHERE LEFT(A.tgl_tfk, 7) = :bulan1 
            AND P.nama_p = :principle 
            GROUP BY B.id_out 
            ORDER BY total DESC 
            LIMIT 10");
        $stmt->bindParam(':bulan1', $bulan1, PDO::PARAM_STR);
        $stmt->bindParam(':principle', $principle, PDO::PARAM_STR);
    } else {
        $stmt = $conn->prepare("SELECT B.id_out, B.nama_out, SUM(A.total_tfk) AS total 
            FROM transaksi_faktur_pim AS A 
            LEFT JOIN outlet AS B ON A.id_out = B.id_out 
            WHERE LEFT(A.tgl_tfk, 7) = :bulan1 
            GROUP BY B.id_out 
            ORDER BY total DESC 
            LIMIT 10");
        $stmt->bindParam(':bulan1', $bulan1, PDO::PARAM_STR);
    }
    $stmt->execute();
    $response['data']['top_outlets_bulan_berjalan'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 12. Top Products Bulan Lalu (Desember 2025)
    $stmt = $conn->prepare("SELECT P.id_pro, P.nama_pro, K.nama_kpr, SUM(D.jumlah_tfd) AS qty 
        FROM transaksi_fakturdetail_pim AS D 
        LEFT JOIN produk AS P ON D.id_pro = P.id_pro 
        LEFT JOIN kategori_produk AS K ON P.id_kpr = K.id_kpr 
        LEFT JOIN transaksi_faktur_pim AS F ON D.id_tfk = F.id_tfk 
        WHERE LEFT(F.tgl_tfk, 7) = :bulan2 
        GROUP BY P.id_pro 
        ORDER BY qty DESC 
        LIMIT 10");
    $stmt->bindParam(':bulan2', $bulan2, PDO::PARAM_STR);
    $stmt->execute();
    $response['data']['top_products_bulan_lalu'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 13. Top Products Bulan Berjalan (Januari 2026)
    $stmt = $conn->prepare("SELECT P.id_pro, P.nama_pro, K.nama_kpr, SUM(D.jumlah_tfd) AS qty 
        FROM transaksi_fakturdetail_pim AS D 
        LEFT JOIN produk AS P ON D.id_pro = P.id_pro 
        LEFT JOIN kategori_produk AS K ON P.id_kpr = K.id_kpr 
        LEFT JOIN transaksi_faktur_pim AS F ON D.id_tfk = F.id_tfk 
        WHERE LEFT(F.tgl_tfk, 7) = :bulan1 
        GROUP BY P.id_pro 
        ORDER BY qty DESC 
        LIMIT 10");
    $stmt->bindParam(':bulan1', $bulan1, PDO::PARAM_STR);
    $stmt->execute();
    $response['data']['top_products_bulan_berjalan'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 14. Chart Data - Monthly sales for 3 years (current year and 2 previous years)
    $tiga = date('Y', strtotime('-2 years')); // 2024
    $tahun_lalu = date('Y', strtotime('-1 year')); // 2025
    $tahun_sekarang = $tahun; // 2026
    
    $categories = [];
    $series_2024 = [];
    $series_2025 = [];
    $series_2026 = [];
    
    for ($m = 1; $m <= 12; $m++) {
        $categories[] = date('M', mktime(0, 0, 0, $m, 1));
        
        // Data for 2024 - direct from transaksi_faktur_pim
        $month_key = $tiga . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);
        $stmt = $conn->prepare("SELECT COALESCE(SUM(total_tfk), 0) AS total 
            FROM transaksi_faktur_pim 
            WHERE LEFT(tgl_tfk, 7) = :month_key");
        $stmt->bindParam(':month_key', $month_key, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $series_2024[] = (int)($result['total'] ?? 0);
        
        // Data for 2025 - direct from transaksi_faktur_pim
        $month_key = $tahun_lalu . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);
        $stmt = $conn->prepare("SELECT COALESCE(SUM(total_tfk), 0) AS total 
            FROM transaksi_faktur_pim 
            WHERE LEFT(tgl_tfk, 7) = :month_key");
        $stmt->bindParam(':month_key', $month_key, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $series_2025[] = (int)($result['total'] ?? 0);
        
        // Data for 2026 - direct from transaksi_faktur_pim
        $month_key = $tahun_sekarang . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);
        $stmt = $conn->prepare("SELECT COALESCE(SUM(total_tfk), 0) AS total 
            FROM transaksi_faktur_pim 
            WHERE LEFT(tgl_tfk, 7) = :month_key");
        $stmt->bindParam(':month_key', $month_key, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $series_2026[] = (int)($result['total'] ?? 0);
    }
    
    $response['data']['chart_data'] = [
        'categories' => $categories,
        'series' => [
            ['name' => '2024', 'data' => $series_2024],
            ['name' => '2025', 'data' => $series_2025],
            ['name' => '2026', 'data' => $series_2026]
        ]
    ];
    
} catch(Exception $e) {
    error_log("API getDataUtama query error: " . $e->getMessage());
    $response['status'] = 'error';
    $response['message'] = 'Database error: ' . $e->getMessage();
}

$conn = $base->close();

http_response_code(200);
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
echo json_encode($response);
?>
