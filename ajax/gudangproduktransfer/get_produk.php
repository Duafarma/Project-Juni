<?php
// Add CORS headers for browser compatibility
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Content-Type: application/json');

// Include required files
require_once('../../config/connection/connection.php');
require_once('../../config/connection/security.php');
require_once('../../config/function/data.php');

// Initialize objects
$secu = new Security;
$base = new DB;
$data = new Data;
$catat = date('Y-m-d H:i:s');

// Get authentication data
$admin = $secu->injection(@$_COOKIE['adminkuy']);
$kunci = $secu->injection(@$_COOKIE['kuncikuy']);

// Get warehouse ID from either POST or GET
$warehouse_id = isset($_POST['warehouse_id']) ?
    $secu->injection($_POST['warehouse_id']) :
    $secu->injection($_GET['warehouse_id']);

// Validate admin (commented out for testing if needed)
$valid = $secu->validadmin($admin, $kunci);

try {
    // Check if warehouse ID is provided
    if (empty($warehouse_id)) {
        throw new Exception("Warehouse ID is required");
    }

    $conn = $base->open();

    $sql = "
        SELECT 
            p.id_pro,
            p.kode_pro, 
            p.nama_pro, 
            p.berat_pro, 
            p.status_pro, 
            p.kategori_obat,
            ps.id_psd,
            ps.no_bcode,
            ps.tgl_expired,
            ps.masuk_psd,
            ps.keluar_psd,
            ps.sisa_psd,
            ps.gudang,
            ps.id_trd,
            ps.tgl_psd,
            ph.harga_phg,
            kp.nama_kpr,
            kp.satuan_kpr,
            sp.nama_spr
        FROM 
            produk_stokdetail ps
        LEFT JOIN 
            produk p ON ps.id_pro = p.id_pro
        LEFT JOIN 
            produk_harga ph ON p.id_pro = ph.id_pro
        LEFT JOIN 
            kategori_produk kp ON p.id_kpr = kp.id_kpr
        LEFT JOIN 
            satuan_produk sp ON p.id_spr = sp.id_spr
        WHERE 
            ps.gudang = :warehouse_id
            AND ps.sisa_psd > 0 
            AND ps.tgl_expired > CURDATE()
            AND ph.status_phg = 'Active'
        GROUP BY 
            ps.id_psd,
            ps.no_bcode,
            ps.tgl_expired,
            ps.sisa_psd,
            p.id_pro,
            p.kode_pro,
            p.nama_pro,
            p.berat_pro,
            ph.harga_phg,
            kp.nama_kpr,
            kp.satuan_kpr,
            sp.nama_spr,
            ps.id_trd,
            ps.tgl_psd,
            ps.gudang
        ORDER BY 
            ps.tgl_expired ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':warehouse_id', $warehouse_id, PDO::PARAM_STR);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get the warehouse name
    $warehouseSql = "SELECT nama_inventory FROM master_inventory WHERE id_inventory = :id";
    $warehouseStmt = $conn->prepare($warehouseSql);
    $warehouseStmt->bindParam(':id', $warehouse_id, PDO::PARAM_STR);
    $warehouseStmt->execute();
    $warehouseInfo = $warehouseStmt->fetch(PDO::FETCH_ASSOC);

    $base->close();

    // Format the products data for better client usage
    $formattedProducts = [];
    foreach ($products as $product) {
        // Calculate days until expiry
        $expiryDate = new DateTime($product['tgl_expired']);
        $today = new DateTime();
        $daysUntilExpiry = $today->diff($expiryDate)->days;

        // Format dates for display
        $formattedExpiryDate = date('d/m/Y', strtotime($product['tgl_expired']));
        $formattedStockDate = date('d/m/Y', strtotime($product['tgl_psd']));

        // Add to formatted products array
        $formattedProducts[] = [
            'id' => $product['id_pro'],
            'kode' => $product['kode_pro'],
            'nama' => $product['nama_pro'],
            'kategori' => $product['kategori_obat'],
            'stock_detail' => [
                'id' => $product['id_psd'],
                'batch' => $product['no_bcode'],
                'expired' => [
                    'raw' => $product['tgl_expired'],
                    'formatted' => $formattedExpiryDate,
                    'days_left' => $daysUntilExpiry,
                    'status' => $daysUntilExpiry <= 90 ? 'warning' : 'normal'
                ],
                'stock' => [
                    'masuk' => (int)$product['masuk_psd'],
                    'keluar' => (int)$product['keluar_psd'],
                    'sisa' => (int)$product['sisa_psd']
                ],
                'gudang' => $product['gudang'],
                'transaction_id' => $product['id_trd'],
                'tanggal' => [
                    'raw' => $product['tgl_psd'],
                    'formatted' => $formattedStockDate
                ]
            ],
            'info' => [
                'harga' => (float)$product['harga_phg'],
                'berat' => $product['berat_pro'],
                'status' => $product['status_pro'],
                'kategori_detail' => $product['nama_kpr'],
                'satuan' => [
                    'name' => $product['satuan_kpr'],
                    'detail' => $product['nama_spr']
                ]
            ]
        ];
    }

    // Prepare the final response
    $response = [
        'success' => true,
        'timestamp' => $catat,
        'warehouse' => [
            'id' => $warehouse_id,
            'name' => $warehouseInfo['nama_inventory'] ?? 'Unknown Warehouse'
        ],
        'products' => $formattedProducts,
        'count' => count($formattedProducts),
        'metadata' => [
            'query_time' => date('Y-m-d H:i:s'),
            'filters' => [
                'only_available' => true,
                'not_expired' => true
            ]
        ]
    ];

    // Output the structured response
    echo json_encode($response, JSON_PRETTY_PRINT);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Application error',
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
