<?php
// Prevent any unexpected output
ob_start();
error_reporting(E_ALL);

require_once('../config/connection/connection.php');
require_once('../config/connection/security.php');
require_once('../config/function/data.php');

$secu = new Security;
$base = new DB;
$data = new Data;
$conn = $base->open();

// Auto-cleanup booking draft kadaluarsa (lebih dari 24 jam) - berjalan periodik
try {
    $conn->exec("DELETE FROM transaksi_booking_draft WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
} catch (PDOException $e) {
    // Silent - tabel mungkin belum ada, tidak perlu error
}

// Safely get POST parameters with defaults
$principleId = isset($_POST['principle']) ? $secu->injection($_POST['principle']) : '';
$mitra = isset($_POST['mitra']) ? $secu->injection($_POST['mitra']) : '';
$cart = isset($_POST['cart']) ? $secu->injection($_POST['cart']) : '';
$nomor = isset($_POST['nomor']) ? $secu->injection($_POST['nomor']) : '';
$searchTerm = isset($_POST['search']) ? $secu->injection($_POST['search']) : '';
$dariKonsinyasi = isset($_POST['dari_konsinyasi']) ? $secu->injection($_POST['dari_konsinyasi']) : 'tidak';
$idFakturKonsinyasi = isset($_POST['id_tfk_konsinyasi']) ? $secu->injection($_POST['id_tfk_konsinyasi']) : '';
$idTfkCurrent = isset($_POST['id_tfk']) ? $secu->injection($_POST['id_tfk']) : ''; // ID faktur aktif (untuk exclude booking sendiri)

$notin = empty($cart) ? "A.id_psd!=''" : "A.id_psd NOT IN('".str_replace("-", "', '", $cart)."')";

try {
    $status = 'Active';
    $gudang = 'Puri';
    
    // Build query with principle filter and search
    $principleFilter = "";
    $searchFilter = "";
    $konsinyasiFilter = "";
    
    if(!empty($principleId)) {
        // Use principle ID directly since nama_p in produk table contains principle ID
        $principleFilter = " AND B.nama_p = :principle ";
    }
    
    if(!empty($searchTerm)) {
        $searchFilter = " AND (B.nama_pro LIKE :search OR B.kode_pro LIKE :search OR A.no_bcode LIKE :search) ";
    }
    
    // Pilih table berdasarkan sumber stok
    if($dariKonsinyasi === 'ya' && !empty($idFakturKonsinyasi)) {
        // Load dari produk_stokdetail_konsinyasi
        $konsinyasiFilter = " AND A.id_tfk = :id_tfk_konsinyasi ";
        $query = "SELECT A.id_psd, A.no_bcode, A.tgl_expired, A.created_at, A.gudang, A.tgl_psd, A.sisa_psd, 
                    GREATEST(0, A.sisa_psd - COALESCE(BK.total_booked, 0)) AS effective_sisa,
                    B.id_pro, B.kode_pro, B.nama_pro, B.berat_pro, B.nama_p, C.harga_phg, D.nama_kpr, D.satuan_kpr, E.nama_spr, F.persen_pds 
                  FROM produk_stokdetail_konsinyasi AS A 
                  LEFT JOIN produk AS B ON A.id_pro=B.id_pro 
                  LEFT JOIN produk_harga_detail AS C ON B.id_pro=C.id_pro 
                  LEFT JOIN kategori_produk AS D ON B.id_kpr=D.id_kpr 
                  LEFT JOIN satuan_produk AS E ON B.id_spr=E.id_spr 
                  LEFT JOIN produk_diskon AS F ON B.id_pro=F.id_pro
                  LEFT JOIN (
                      SELECT id_psd, SUM(jumlah) AS total_booked 
                      FROM transaksi_booking_draft 
                      WHERE id_tfk != :id_tfk_current
                      GROUP BY id_psd
                  ) AS BK ON A.id_psd = BK.id_psd
                  WHERE $notin AND A.sisa_psd>0 AND C.id_out=:mitra AND F.id_out=:mitra $konsinyasiFilter $principleFilter $searchFilter 
                  HAVING effective_sisa > 0
                  ORDER BY B.nama_pro, A.tgl_expired, A.created_at ASC";
    } else {
        // Load dari produk_stokdetail normal
        $query = "SELECT A.id_psd, A.no_bcode, A.tgl_expired, A.created_at, A.gudang, A.tgl_psd, A.sisa_psd,
                    GREATEST(0, A.sisa_psd - COALESCE(BK.total_booked, 0)) AS effective_sisa,
                    B.id_pro, B.kode_pro, B.nama_pro, B.berat_pro, B.nama_p, C.harga_phg, D.nama_kpr, D.satuan_kpr, E.nama_spr, F.persen_pds 
                  FROM produk_stokdetail AS A 
                  LEFT JOIN produk AS B ON A.id_pro=B.id_pro 
                  LEFT JOIN produk_harga_detail AS C ON B.id_pro=C.id_pro 
                  LEFT JOIN kategori_produk AS D ON B.id_kpr=D.id_kpr 
                  LEFT JOIN satuan_produk AS E ON B.id_spr=E.id_spr 
                  LEFT JOIN produk_diskon AS F ON B.id_pro=F.id_pro
                  LEFT JOIN (
                      SELECT id_psd, SUM(jumlah) AS total_booked 
                      FROM transaksi_booking_draft 
                      WHERE id_tfk != :id_tfk_current
                      GROUP BY id_psd
                  ) AS BK ON A.id_psd = BK.id_psd
                  WHERE $notin AND A.sisa_psd>0 AND C.id_out=:mitra AND F.id_out=:mitra AND A.gudang = :gudang $principleFilter $searchFilter 
                  HAVING effective_sisa > 0
                  ORDER BY B.nama_pro, A.tgl_expired, A.created_at ASC";
    }
    
    $master = $conn->prepare($query);
    $master->bindParam(':mitra', $mitra, PDO::PARAM_STR);
    $master->bindParam(':id_tfk_current', $idTfkCurrent, PDO::PARAM_STR);
    
    if($dariKonsinyasi !== 'ya') {
        $master->bindParam(':gudang', $gudang);
    }
    
    if($dariKonsinyasi === 'ya' && !empty($idFakturKonsinyasi)) {
        $master->bindParam(':id_tfk_konsinyasi', $idFakturKonsinyasi, PDO::PARAM_STR);
    }
    
    if(!empty($principleId)) {
        $master->bindParam(':principle', $principleId, PDO::PARAM_STR);
    }
    
    if(!empty($searchTerm)) {
        $searchParam = "%".$searchTerm."%";
        $master->bindParam(':search', $searchParam, PDO::PARAM_STR);
    }
    
    $master->execute();
    $products = $master->fetchAll(PDO::FETCH_ASSOC);
    
    if(count($products) > 0) {
        foreach($products as $hasil) {
            // Gunakan effective_sisa (sudah dikurangi booking draft faktur lain) untuk display & validasi
            $efSisa = isset($hasil['effective_sisa']) ? (int)$hasil['effective_sisa'] : (int)$hasil['sisa_psd'];
            $badgeColor = $efSisa > 50 ? 'success' : ($efSisa > 10 ? 'warning' : 'danger');
            $efSisaDisplay = $efSisa . ($efSisa < $hasil['sisa_psd'] ? ' <small style="color:#dc3545;" title="Ada '.($hasil['sisa_psd']-$efSisa).' di-booking faktur lain">*</small>' : '');
            echo '<tr onclick="getproductsales(\''.$nomor.'\', \''.$hasil['id_psd'].'\', \''.$hasil['id_pro'].'\', \''.htmlspecialchars($hasil['nama_pro'], ENT_QUOTES).'\', \''.$hasil['kode_pro'].'\', \''.$hasil['harga_phg'].'\', \''.$hasil['berat_pro'].'\', \''.htmlspecialchars($hasil['nama_kpr'], ENT_QUOTES).'\', \''.$hasil['satuan_kpr'].'\', \''.$hasil['nama_spr'].'\', \''.$hasil['no_bcode'].'\', \''.$hasil['tgl_expired'].'\', \''.$hasil['gudang'].'\', \''.$efSisa.'\', \''.$hasil['persen_pds'].'\')" style="cursor: pointer;">';
            echo '<td><strong>'.$hasil['nama_pro'].'</strong><br><small class="text-muted">'.$hasil['kode_pro'].'</small></td>';
            echo '<td class="text-center"><span class="badge badge-secondary">'.$hasil['no_bcode'].'</span></td>';
            echo '<td class="text-center"><span class="badge badge-info">'.$hasil['gudang'].'</span></td>';
            echo '<td class="text-center">'.$hasil['tgl_expired'].'</td>';
            echo '<td class="text-right"><span class="badge badge-'.$badgeColor.'">'.$efSisaDisplay.'</span></td>';
            echo '<td class="text-right"><strong>'.$data->angka($hasil['harga_phg']).'</strong></td>';
            echo '</tr>';
        }
    } else {
        $message = !empty($searchTerm) ? 'Tidak ada produk yang cocok dengan pencarian "'.$searchTerm.'"' : 'Tidak ada produk untuk principle ini';
        echo '<tr><td colspan="6" class="text-center text-muted py-4">';
        echo '<i class="fas fa-'.(!empty($searchTerm) ? 'search' : 'box-open').'"></i><br>';
        echo $message;
        if(!empty($searchTerm)) {
            echo '<br><small>Coba kata kunci yang berbeda</small>';
        }
        echo '</td></tr>';
    }
    
} catch(PDOException $e) {
    ob_clean();
    error_log("LoadProducts PDO Error: " . $e->getMessage());
    echo '<tr><td colspan="6" class="text-center text-danger py-4"><i class="fas fa-exclamation-triangle"></i> Database connection error</td></tr>';
} catch(Exception $e) {
    ob_clean();
    error_log("LoadProducts Error: " . $e->getMessage());
    echo '<tr><td colspan="6" class="text-center text-danger py-4"><i class="fas fa-exclamation-triangle"></i> System error</td></tr>';
}

$conn = $base->close();
ob_end_flush();
?>