<?php
// Clean output - no errors, no warnings
error_reporting(0);
ini_set('display_errors', 0);

// Start clean buffer
ob_clean();
ob_start();

require_once('../config/connection/connection.php');
require_once('../config/connection/security.php');
require_once('../config/function/data.php');

$secu = new Security;
$base = new DB;
$data = new Data;
$conn = $base->open();

// Get parameters
$mitra = isset($_POST['mitra']) ? $secu->injection($_POST['mitra']) : '';
$cart = isset($_POST['cart']) ? $secu->injection($_POST['cart']) : '';
$nomor = isset($_POST['nomor']) ? $secu->injection($_POST['nomor']) : '';
$searchTerm = isset($_POST['search']) ? $secu->injection($_POST['search']) : '';
$page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;

// Debug log
error_log("=== LOADPRODUCTS RETUR ===");
error_log("Outlet: " . $mitra);
error_log("Search: " . $searchTerm);
error_log("Page: " . $page);

$notin = empty($cart) ? "A.id_psd!=''" : "A.id_psd NOT IN('".str_replace("-", "', '", $cart)."')";

try {
    $status = 'Active';
    $gudang = 'Puri';
    $itemsPerPage = 10;
    $offset = ($page - 1) * $itemsPerPage;
    
    // Build search filter
    $searchFilter = "";
    if(!empty($searchTerm)) {
        $searchFilter = " AND (B.nama_pro LIKE :search OR B.kode_pro LIKE :search OR A.no_bcode LIKE :search) ";
    }
    
    // Query produk
    $query = "SELECT 
                A.id_psd, 
                A.no_bcode, 
                A.tgl_expired, 
                A.gudang, 
                A.sisa_psd, 
                B.id_pro, 
                B.kode_pro, 
                B.nama_pro, 
                B.berat_pro, 
                C.harga_phg, 
                D.nama_kpr, 
                D.satuan_kpr, 
                E.nama_spr, 
                COALESCE(F.persen_pds, 0) as persen_pds 
              FROM 
                produk_stokdetail AS A 
                LEFT JOIN produk AS B ON A.id_pro=B.id_pro 
                LEFT JOIN produk_harga_detail AS C ON B.id_pro=C.id_pro 
                LEFT JOIN kategori_produk AS D ON B.id_kpr=D.id_kpr 
                LEFT JOIN satuan_produk AS E ON B.id_spr=E.id_spr 
                LEFT JOIN produk_diskon AS F ON B.id_pro=F.id_pro AND F.id_out=:mitra
              WHERE 
                $notin 
                AND A.sisa_psd > 0 
                AND C.id_out = :mitra 
                AND A.gudang = :gudang 
                $searchFilter 
              ORDER BY 
                B.nama_pro, A.tgl_expired ASC 
              LIMIT :limit OFFSET :offset";
    
    $master = $conn->prepare($query);
    $master->bindParam(':mitra', $mitra, PDO::PARAM_STR);
    $master->bindParam(':gudang', $gudang, PDO::PARAM_STR);
    $master->bindParam(':limit', $itemsPerPage, PDO::PARAM_INT);
    $master->bindParam(':offset', $offset, PDO::PARAM_INT);
    
    if(!empty($searchTerm)) {
        $searchParam = "%".$searchTerm."%";
        $master->bindParam(':search', $searchParam, PDO::PARAM_STR);
    }
    
    $master->execute();
    $products = $master->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) as total 
                   FROM produk_stokdetail AS A 
                   LEFT JOIN produk AS B ON A.id_pro=B.id_pro 
                   LEFT JOIN produk_harga_detail AS C ON B.id_pro=C.id_pro 
                   WHERE $notin 
                   AND A.sisa_psd > 0 
                   AND C.id_out = :mitra 
                   AND A.gudang = :gudang 
                   $searchFilter";
    
    $countStmt = $conn->prepare($countQuery);
    $countStmt->bindParam(':mitra', $mitra, PDO::PARAM_STR);
    $countStmt->bindParam(':gudang', $gudang, PDO::PARAM_STR);
    
    if(!empty($searchTerm)) {
        $countStmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
    }
    
    $countStmt->execute();
    $totalItems = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalItems / $itemsPerPage);
    
    error_log("Products found: " . count($products) . " / Total: " . $totalItems . " / Pages: " . $totalPages);
    
    if(count($products) > 0) {
        foreach($products as $hasil) {
            // Build onclick with proper escaping using JSON parameters
            // IMPORTANT: Using getproductsales() function for fsalesr to match fpenggantianbarang
            $params = array(
                $nomor,                    // param 1: nomor
                $hasil['id_psd'],          // param 2: kode (id_psd)
                $hasil['id_pro'],          // param 3: produk (id_pro)
                $hasil['nama_pro'],        // param 4: nama
                $hasil['kode_pro'],        // param 5: code
                $hasil['harga_phg'],       // param 6: harga
                $hasil['berat_pro'],       // param 7: berat
                $hasil['nama_kpr'],        // param 8: kategori (nama_kpr)
                $hasil['satuan_kpr'],      // param 9: satuanqty
                $hasil['nama_spr'],        // param 10: satuan (nama_spr)
                $hasil['no_bcode'],        // param 11: bcode
                $hasil['tgl_expired'],     // param 12: tgled
                $hasil['gudang'],          // param 13: gudang
                $hasil['sisa_psd'],        // param 14: stok
                $hasil['persen_pds']       // param 15: diskon
            );
            
            // Build onclick string with proper escaping
            // CRITICAL: Using getproductsales for fsalesr to match fpenggantianbarang
            $onclickParams = array();
            foreach($params as $param) {
                // Escape single quotes and backslashes in strings
                $escaped = str_replace("\\", "\\\\", (string)$param);
                $escaped = str_replace("'", "\\'", $escaped);
                $onclickParams[] = "'" . $escaped . "'";
            }
            $onclickAttr = "getproductsales(" . implode(", ", $onclickParams) . ")";
            
            echo '<tr onclick="' . $onclickAttr . '" style="cursor:pointer;">';
            echo '<td><strong>'.$hasil['nama_pro'].'</strong><br><small class="text-muted">'.$hasil['kode_pro'].'</small></td>';
            echo '<td class="text-center"><span class="badge badge-secondary">'.$hasil['no_bcode'].'</span></td>';
            echo '<td class="text-center"><span class="badge badge-info">'.$hasil['gudang'].'</span></td>';
            echo '<td class="text-center">'.$hasil['tgl_expired'].'</td>';
            echo '<td class="text-right"><span class="badge badge-'.($hasil['sisa_psd'] > 50 ? 'success' : ($hasil['sisa_psd'] > 10 ? 'warning' : 'danger')).'">'.$hasil['sisa_psd'].'</span></td>';
            echo '<td class="text-right"><strong>'.$data->angka($hasil['harga_phg']).'</strong></td>';
            echo '</tr>';
        }
        
        // Add pagination info as hidden row
        echo '<tr style="display:none;" id="paginationData">';
        echo '<td data-page="'.$page.'" data-total-pages="'.$totalPages.'" data-total-items="'.$totalItems.'" data-items-per-page="'.$itemsPerPage.'">pagination-data</td>';
        echo '</tr>';
        
    } else {
        $message = !empty($searchTerm) ? 'Tidak ada produk yang cocok dengan pencarian "'.$searchTerm.'"' : 'Tidak ada produk tersedia';
        echo '<tr><td colspan="6" class="text-center text-muted py-4">';
        echo '<i class="fas fa-'.(!empty($searchTerm) ? 'search' : 'box-open').'"></i><br>';
        echo $message;
        echo '</td></tr>';
        
        // Add pagination data even when no results
        echo '<tr style="display:none;" id="paginationData">';
        echo '<td data-page="1" data-total-pages="1" data-total-items="0" data-items-per-page="'.$itemsPerPage.'">pagination-data</td>';
        echo '</tr>';
    }
    
} catch(PDOException $e) {
    error_log("LoadProducts Retur Error: " . $e->getMessage());
    echo '<tr><td colspan="6" class="text-center text-danger py-4">';
    echo '<i class="fas fa-exclamation-triangle"></i> Database error<br>';
    echo '<small>' . $e->getMessage() . '</small>';
    echo '</td></tr>';
}

$conn = $base->close();

// Get buffer and clean it
$output = ob_get_clean();

// Remove BOM and extra whitespace
$output = trim($output);
$output = preg_replace('/^\xEF\xBB\xBF/', '', $output);

// Output clean HTML
echo $output;
exit;
?>
