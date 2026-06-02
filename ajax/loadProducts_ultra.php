<?php
// Ultra-clean output - no output buffer issues
while (ob_get_level()) ob_end_clean();
ob_start();

// Disable all error output
error_reporting(0);
ini_set('display_errors', 0);

// Set clean headers
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-cache');

require_once('../config/connection/connection.php');
require_once('../config/connection/security.php');
require_once('../config/function/data.php');

$secu = new Security;
$base = new DB;
$data = new Data;
$conn = $base->open();

// Get parameters - ultra-safe
$principleId = filter_var($_POST['principle'] ?? '', FILTER_SANITIZE_STRING);
$mitra = filter_var($_POST['mitra'] ?? '', FILTER_SANITIZE_STRING);
$cart = filter_var($_POST['cart'] ?? '', FILTER_SANITIZE_STRING);
$nomor = filter_var($_POST['nomor'] ?? '1', FILTER_SANITIZE_NUMBER_INT);
$searchTerm = filter_var($_POST['search'] ?? '', FILTER_SANITIZE_STRING);

// Basic validation
if (empty($principleId) || empty($mitra)) {
    echo '<tr><td colspan="6" class="text-center text-warning">Missing parameters</td></tr>';
    exit;
}

$notin = empty($cart) ? "A.id_psd!=''" : "A.id_psd NOT IN('".str_replace("-", "', '", $cart)."')";

try {
    // Simple query
    $query = "SELECT A.id_psd, A.no_bcode, A.tgl_expired, A.gudang, A.sisa_psd, 
                     B.id_pro, B.kode_pro, B.nama_pro, B.berat_pro, 
                     C.harga_phg, D.nama_kpr, E.nama_spr, F.persen_pds 
              FROM produk_stokdetail AS A 
              LEFT JOIN produk AS B ON A.id_pro=B.id_pro 
              LEFT JOIN produk_harga_detail AS C ON B.id_pro=C.id_pro 
              LEFT JOIN kategori_produk AS D ON B.id_kpr=D.id_kpr 
              LEFT JOIN satuan_produk AS E ON B.id_spr=E.id_spr 
              LEFT JOIN produk_diskon AS F ON B.id_pro=F.id_pro 
              WHERE $notin AND A.sisa_psd>0 AND C.id_out=? AND F.id_out=? 
              AND A.gudang = 'Puri' AND B.nama_p = ?";
    
    // Add search if provided
    if (!empty($searchTerm)) {
        $query .= " AND (B.nama_pro LIKE ? OR B.kode_pro LIKE ? OR A.no_bcode LIKE ?)";
    }
    
    $query .= " ORDER BY B.nama_pro LIMIT 50";
    
    $stmt = $conn->prepare($query);
    $params = [$mitra, $mitra, $principleId];
    
    if (!empty($searchTerm)) {
        $search = "%$searchTerm%";
        $params[] = $search;
        $params[] = $search; 
        $params[] = $search;
    }
    
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($products) > 0) {
        foreach ($products as $p) {
            // Ultra-safe data extraction
            $id_psd = (int) ($p['id_psd'] ?? 0);
            $id_pro = (int) ($p['id_pro'] ?? 0);
            $harga = (float) ($p['harga_phg'] ?? 0);
            $stok = (int) ($p['sisa_psd'] ?? 0);
            $berat = (float) ($p['berat_pro'] ?? 0);
            $diskon = (float) ($p['persen_pds'] ?? 0);
            
            // Clean text - only alphanumeric and basic chars
            $nama = preg_replace('/[^\w\s\-\.]/', '', substr($p['nama_pro'] ?? '', 0, 50));
            $kode = preg_replace('/[^\w\-]/', '', substr($p['kode_pro'] ?? '', 0, 20));
            $bcode = preg_replace('/[^\w\-]/', '', substr($p['no_bcode'] ?? '', 0, 20));
            $gudang = preg_replace('/[^\w]/', '', substr($p['gudang'] ?? '', 0, 10));
            $expired = preg_replace('/[^\d\-]/', '', substr($p['tgl_expired'] ?? '', 0, 10));
            $kategori = preg_replace('/[^\w\s]/', '', substr($p['nama_kpr'] ?? '', 0, 30));
            $satuan = preg_replace('/[^\w]/', '', substr($p['nama_spr'] ?? '', 0, 10));
            
            // Simple HTML output - no complex attributes
            echo '<tr class="product-row"';
            echo ' data-nomor="' . $nomor . '"';
            echo ' data-id-psd="' . $id_psd . '"';
            echo ' data-id-pro="' . $id_pro . '"';
            echo ' data-nama-pro="' . $nama . '"';
            echo ' data-kode-pro="' . $kode . '"';
            echo ' data-harga="' . $harga . '"';
            echo ' data-berat="' . $berat . '"';
            echo ' data-kategori="' . $kategori . '"';
            echo ' data-satuan="' . $satuan . '"';
            echo ' data-bcode="' . $bcode . '"';
            echo ' data-expired="' . $expired . '"';
            echo ' data-gudang="' . $gudang . '"';
            echo ' data-stok="' . $stok . '"';
            echo ' data-diskon="' . $diskon . '"';
            echo '>';
            
            echo '<td><strong>' . $nama . '</strong><br><small>' . $kode . '</small></td>';
            echo '<td class="text-center">' . $bcode . '</td>';
            echo '<td class="text-center">' . $gudang . '</td>';
            echo '<td class="text-center">' . $expired . '</td>';
            echo '<td class="text-right">' . $stok . '</td>';
            echo '<td class="text-right">' . number_format($harga, 0) . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="6" class="text-center">No products found</td></tr>';
    }
    
} catch (Exception $e) {
    echo '<tr><td colspan="6" class="text-center text-danger">Database error</td></tr>';
}

$conn = null;
ob_end_flush();
?>