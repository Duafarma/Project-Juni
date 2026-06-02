<?php
/**
 * API: Get Detail Transaksi Outlet
 * Untuk mengambil detail transaksi outlet berdasarkan ID dan periode
 */

require_once('../config/connection/connection.php');
require_once('../config/connection/security.php');
require_once('../config/function/data.php');

$base = new DB;
$secu = new Security;
$data = new Data;
$conn = $base->open();
$tanggal = date('Y-m-d');

// Get parameters
$encrypt = $secu->injection(@$_GET['encrypt']);
$id_out = $secu->injection(@$_GET['id_out']);
$bulan = $secu->injection(@$_GET['bulan']); // Format: YYYY-MM

// Response structure
$response = [
    'status' => 'error',
    'message' => '',
    'data' => [],
    'summary' => [
        'total_qty' => 0,
        'total_amount' => 0,
        'total_transactions' => 0
    ]
];

// Validation
if (empty($encrypt)) {
    $response['message'] = 'Missing encryption parameter';
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

if (empty($id_out)) {
    $response['message'] = 'Missing outlet ID parameter';
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Validate encryption
$validKey = md5($tanggal . '#' . $data->sistem('key_apl'));
if ($encrypt !== $validKey) {
    $response['message'] = 'Invalid encryption key';
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Set default bulan if not provided
if (empty($bulan)) {
    $bulan = date('Y-m');
}

// Validate bulan format
if (!preg_match('/^\d{4}-\d{2}$/', $bulan)) {
    $response['message'] = 'Invalid month format. Use YYYY-MM';
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

try {
    // Calculate date range
    $startDate = $bulan . '-01';
    $endDate = date('Y-m-d', strtotime($startDate . ' +1 month'));
    
    // Get outlet name first
    $qOutlet = $conn->prepare("SELECT nama_out FROM outlet WHERE id_out = ?");
    $qOutlet->execute([$id_out]);
    $rOutlet = $qOutlet->fetch(PDO::FETCH_ASSOC);
    
    if (!$rOutlet) {
        $response['message'] = 'Outlet not found';
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    $namaOutlet = $rOutlet['nama_out'];
    
    // Query transaksi
    $sqlFaktur = "SELECT id_tfk, kode_tfk, tgl_tfk, total_tfk 
                  FROM transaksi_faktur_pim 
                  WHERE id_out = ? 
                  AND tgl_tfk >= ? 
                  AND tgl_tfk < ? 
                  ORDER BY tgl_tfk DESC";
    
    $stmtFaktur = $conn->prepare($sqlFaktur);
    $stmtFaktur->execute([$id_out, $startDate, $endDate]);
    
    $transactions = [];
    $totalQty = 0;
    $totalAmount = 0;
    
    while ($faktur = $stmtFaktur->fetch(PDO::FETCH_ASSOC)) {
        // Get detail per faktur
        $sqlDetail = "SELECT D.nama_pro, C.jumlah_tfd, C.harga_tfd 
                     FROM transaksi_fakturdetail_pim C 
                     LEFT JOIN produk D ON C.id_pro = D.id_pro 
                     WHERE C.id_tfk = ?";
        
        $stmtDetail = $conn->prepare($sqlDetail);
        $stmtDetail->execute([$faktur['id_tfk']]);
        
        $details = [];
        while ($detail = $stmtDetail->fetch(PDO::FETCH_ASSOC)) {
            $subtotal = $detail['jumlah_tfd'] * $detail['harga_tfd'];
            $totalQty += $detail['jumlah_tfd'];
            
            $details[] = [
                'nama_produk' => $detail['nama_pro'],
                'qty' => (int)$detail['jumlah_tfd'],
                'harga' => (int)$detail['harga_tfd'],
                'subtotal' => (int)$subtotal
            ];
        }
        
        $totalAmount += $faktur['total_tfk'];
        
        $transactions[] = [
            'kode_faktur' => $faktur['kode_tfk'],
            'tgl_faktur' => $faktur['tgl_tfk'],
            'total_faktur' => (int)$faktur['total_tfk'],
            'details' => $details
        ];
    }
    
    $response['status'] = 'success';
    $response['message'] = 'Data retrieved successfully';
    $response['data'] = [
        'outlet_id' => $id_out,
        'outlet_name' => $namaOutlet,
        'periode' => $bulan,
        'transactions' => $transactions
    ];
    $response['summary'] = [
        'total_qty' => (int)$totalQty,
        'total_amount' => (int)$totalAmount,
        'total_transactions' => count($transactions)
    ];
    
    http_response_code(200);
    
} catch (Exception $e) {
    $response['status'] = 'error';
    $response['message'] = 'Database error: ' . $e->getMessage();
    http_response_code(500);
}

$conn = $base->close();
header('Content-Type: application/json');
echo json_encode($response);
?>
