<?php
require_once('../../config/connection/connection.php');
require_once('../../config/connection/security.php');

$base = new DB;
$secu = new Security;
$conn = $base->open();

// Validasi input
$outlet_id = $secu->injection(@$_POST['outlet_id']);
$principle_id = $secu->injection(@$_POST['principle_id']);

if (empty($outlet_id) || empty($principle_id)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Parameter tidak lengkap'
    ]);
    exit;
}

try {
    // Query untuk mengambil produk berdasarkan principle yang ada di outlet
    $query = "SELECT p.id_pro, p.nama_pro, p.berat_pro,
                     COALESCE(pd.persen_pds, 0) as diskon_current
              FROM produk p
              INNER JOIN produk_diskon pd ON p.id_pro = pd.id_pro
              WHERE pd.id_out = :outlet_id 
              AND p.nama_p = :principle_id
              ORDER BY p.nama_pro";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':outlet_id', $outlet_id, PDO::PARAM_STR);
    $stmt->bindParam(':principle_id', $principle_id, PDO::PARAM_STR);
    $stmt->execute();
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($products) === 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Tidak ada produk ditemukan untuk principle ini di outlet tersebut.'
        ]);
        exit;
    }
    
    $html = '';
    $no = 1;
    
    foreach ($products as $product) {
        $html .= '<tr>';
        $html .= '<td><center>' . $no . '</center></td>';
        $html .= '<td>' . htmlspecialchars($product['nama_pro']) . '</td>';
        $html .= '<td>';
        $html .= '<input type="hidden" name="product_id[]" value="' . $product['id_pro'] . '" />';
        $html .= '<input type="text" name="diskon[]" class="form-control diskon-input" ';
        $html .= 'value="' . floatval($product['diskon_current']) . '" ';
        $html .= 'placeholder="0" />';
        $html .= '</td>';
        $html .= '</tr>';
        $no++;
    }
    
    echo json_encode([
        'status' => 'success',
        'data' => $html,
        'count' => count($products),
        'debug' => [
            'outlet_id' => $outlet_id,
            'principle_id' => $principle_id,
            'query' => $query
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ]);
}

$conn = $base->close();
?>