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

try {
    $conn = $base->open();
    
    $sql = "SELECT 
                id_pj,
                nama_produk_jadi,
                created_at,
                updated_at
            FROM 
                kategori_produk_jadi
            ORDER BY 
                nama_produk_jadi ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $base->close();

    // Format the categories data
    $formattedCategories = [];
    foreach ($categories as $category) {
        $formattedCategories[] = [
            'id' => $category['id_pj'],
            'name' => $category['nama_produk_jadi'],
            'metadata' => [
                'created_at' => $category['created_at'],
                'updated_at' => $category['updated_at']
            ]
        ];
    }

    // Prepare the final response
    $response = [
        'success' => true,
        'timestamp' => $catat,
        'categories' => $formattedCategories,
        'count' => count($formattedCategories),
        'metadata' => [
            'query_time' => date('Y-m-d H:i:s')
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
