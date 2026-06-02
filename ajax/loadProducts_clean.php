<?php
// Clean output buffer start
ob_start();
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

header('Content-Type: text/html; charset=UTF-8');

require_once('../config/connection/connection.php');
require_once('../config/connection/security.php');
require_once('../config/function/data.php');

$secu = new Security;
$base = new DB;
$data = new Data;
$conn = $base->open();

// Get and validate parameters
$principleId = isset($_POST['principle']) ? trim($secu->injection($_POST['principle'])) : '';
$mitra = isset($_POST['mitra']) ? trim($secu->injection($_POST['mitra'])) : '';
$cart = isset($_POST['cart']) ? trim($secu->injection($_POST['cart'])) : '';
$nomor = isset($_POST['nomor']) ? trim($secu->injection($_POST['nomor'])) : '';
$searchTerm = isset($_POST['search']) ? trim($secu->injection($_POST['search'])) : '';

// Validate required parameters
if (empty($principleId) || empty($mitra)) {
    echo '<tr><td colspan="6" class="text-center text-warning py-4"><i class="fas fa-exclamation-triangle"></i> Invalid parameters</td></tr>';
    exit;
}

$notin = empty($cart) ? "A.id_psd!=''" : "A.id_psd NOT IN('".str_replace("-", "', '", $cart)."')";

try {
    $status = 'Active';
    $gudang = 'Puri';
    
    // Build query
    $principleFilter = " AND B.nama_p = :principle ";
    $searchFilter = "";
    
    if(!empty($searchTerm)) {
        $searchFilter = " AND (B.nama_pro LIKE :search OR B.kode_pro LIKE :search OR A.no_bcode LIKE :search) ";
    }
    
    $query = "SELECT A.id_psd, A.no_bcode, A.tgl_expired, A.created_at, A.gudang, A.tgl_psd, A.sisa_psd, 
                     B.id_pro, B.kode_pro, B.nama_pro, B.berat_pro, B.nama_p, 
                     C.harga_phg, D.nama_kpr, D.satuan_kpr, E.nama_spr, F.persen_pds 
              FROM produk_stokdetail AS A 
              LEFT JOIN produk AS B ON A.id_pro=B.id_pro 
              LEFT JOIN produk_harga_detail AS C ON B.id_pro=C.id_pro 
              LEFT JOIN kategori_produk AS D ON B.id_kpr=D.id_kpr 
              LEFT JOIN satuan_produk AS E ON B.id_spr=E.id_spr 
              LEFT JOIN produk_diskon AS F ON B.id_pro=F.id_pro 
              WHERE $notin AND A.sisa_psd>0 AND C.id_out=:mitra AND F.id_out=:mitra AND A.gudang = :gudang $principleFilter $searchFilter 
              ORDER BY B.nama_pro, A.tgl_expired, A.created_at ASC";
    
    $master = $conn->prepare($query);
    $master->bindParam(':mitra', $mitra, PDO::PARAM_STR);
    $master->bindParam(':gudang', $gudang);
    $master->bindParam(':principle', $principleId, PDO::PARAM_STR);
    
    if(!empty($searchTerm)) {
        $searchParam = "%".$searchTerm."%";
        $master->bindParam(':search', $searchParam, PDO::PARAM_STR);
    }
    
    $master->execute();
    $products = $master->fetchAll(PDO::FETCH_ASSOC);
    
    if(count($products) > 0) {
        foreach($products as $hasil) {
            // Ultra-safe data cleaning
            $id_psd = intval($hasil['id_psd'] ?? 0);
            $id_pro = intval($hasil['id_pro'] ?? 0);
            $nomor_clean = intval($nomor);
            
            // Clean text fields - remove any problematic characters
            $nama_pro = preg_replace('/[^\w\s\-\.\(\)]/', '', trim($hasil['nama_pro'] ?? ''));
            $kode_pro = preg_replace('/[^A-Za-z0-9\-]/', '', trim($hasil['kode_pro'] ?? ''));
            $no_bcode = preg_replace('/[^A-Za-z0-9\-]/', '', trim($hasil['no_bcode'] ?? ''));
            $gudang = preg_replace('/[^A-Za-z0-9]/', '', trim($hasil['gudang'] ?? ''));
            $tgl_expired = preg_replace('/[^0-9\-]/', '', trim($hasil['tgl_expired'] ?? ''));
            
            // Clean numeric fields
            $harga_phg = floatval($hasil['harga_phg'] ?? 0);
            $sisa_psd = intval($hasil['sisa_psd'] ?? 0);
            $berat_pro = floatval($hasil['berat_pro'] ?? 0);
            $persen_pds = floatval($hasil['persen_pds'] ?? 0);
            
            // Additional safety fields
            $nama_kpr = preg_replace('/[^\w\s]/', '', trim($hasil['nama_kpr'] ?? ''));
            $satuan_kpr = preg_replace('/[^A-Za-z0-9]/', '', trim($hasil['satuan_kpr'] ?? ''));
            $nama_spr = preg_replace('/[^A-Za-z0-9]/', '', trim($hasil['nama_spr'] ?? ''));
            
            // Build ultra-clean HTML without any special characters that could cause JS errors
            echo '<tr class="product-row"';
            echo ' data-nomor="' . $nomor_clean . '"';
            echo ' data-id-psd="' . $id_psd . '"';
            echo ' data-id-pro="' . $id_pro . '"';
            echo ' data-nama-pro="' . $nama_pro . '"';
            echo ' data-kode-pro="' . $kode_pro . '"';
            echo ' data-harga="' . $harga_phg . '"';
            echo ' data-berat="' . $berat_pro . '"';
            echo ' data-nama-kpr="' . $nama_kpr . '"';
            echo ' data-satuan-kpr="' . $satuan_kpr . '"';
            echo ' data-nama-spr="' . $nama_spr . '"';
            echo ' data-bcode="' . $no_bcode . '"';
            echo ' data-expired="' . $tgl_expired . '"';
            echo ' data-gudang="' . $gudang . '"';
            echo ' data-stok="' . $sisa_psd . '"';
            echo ' data-diskon="' . $persen_pds . '"';
            echo ' style="cursor: pointer;">';
            
            // Safe HTML content
            echo '<td><strong>' . $nama_pro . '</strong><br><small class="text-muted">' . $kode_pro . '</small></td>';
            echo '<td class="text-center"><span class="badge badge-secondary">' . $no_bcode . '</span></td>';
            echo '<td class="text-center"><span class="badge badge-info">' . $gudang . '</span></td>';
            echo '<td class="text-center">' . $tgl_expired . '</td>';
            
            $stock_class = ($sisa_psd > 50) ? 'success' : (($sisa_psd > 10) ? 'warning' : 'danger');
            echo '<td class="text-right"><span class="badge badge-' . $stock_class . '">' . $sisa_psd . '</span></td>';
            echo '<td class="text-right"><strong>' . number_format($harga_phg, 0, ',', '.') . '</strong></td>';
            echo '</tr>';
        }
    } else {
        $message = !empty($searchTerm) ? 'No products match search' : 'No products found for this principle';
        echo '<tr><td colspan="6" class="text-center text-muted py-4">';
        echo '<i class="fas fa-box-open"></i><br>';
        echo $message;
        echo '</td></tr>';
    }
    
} catch(PDOException $e) {
    error_log("LoadProducts Error: " . $e->getMessage());
    echo '<tr><td colspan="6" class="text-center text-danger py-4"><i class="fas fa-exclamation-triangle"></i> Database error</td></tr>';
} catch(Exception $e) {
    error_log("LoadProducts General Error: " . $e->getMessage());
    echo '<tr><td colspan="6" class="text-center text-danger py-4"><i class="fas fa-exclamation-triangle"></i> System error</td></tr>';
}

$conn = $base->close();
ob_end_flush();
?>