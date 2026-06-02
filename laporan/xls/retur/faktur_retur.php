<?php
require_once('../../../config/connection/connection.php');
require_once('../../../config/connection/security.php');
require_once('../../../config/function/data.php');

$secu = new Security;
$base = new DB;
$data = new Data;
$conn = $base->open();

// Get filter parameter
$key = isset($_GET['key']) ? $secu->injection($_GET['key']) : '';

// Set header untuk download Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Laporan_Faktur_Retur_" . date('Y-m-d_His') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Faktur Retur</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid black;
            padding: 5px;
            text-align: left;
            font-size: 11px;
        }
        th {
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .header-info {
            margin-bottom: 20px;
        }
        .header-info h2 {
            margin: 5px 0;
        }
        .header-info p {
            margin: 3px 0;
        }
    </style>
</head>
<body>
    <div class="header-info">
        <h2>LAPORAN FAKTUR RETUR BARANG</h2>
        <p>Tanggal Cetak: <?php echo date('d/m/Y H:i:s'); ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2">No</th>
                <th rowspan="2">Tanggal SJ</th>
                <th rowspan="2">No. Faktur</th>
                <th rowspan="2">No. SJ</th>
                <th rowspan="2">No. PO</th>
                <th rowspan="2">Supplier</th>
                <th rowspan="2">NPWP</th>
                <th colspan="7">Detail Produk</th>
                <th rowspan="2">Subtotal</th>
                <th rowspan="2">PPN</th>
                <th rowspan="2">Total</th>
            </tr>
            <tr>
                <th>Nama Produk</th>
                <th>Kategori Produk</th>
                <th>Kode Produk Jadi</th>

                <th>Qty</th>
                <th>Harga</th>
                <th>Diskon (%)</th>
                <th>Total Item</th>
            </tr>
        </thead>
        <tbody>
<?php
try {
    // Build query dengan JOIN
    $query = "SELECT 
                fr.id_fkr,
                fr.kode_fkr,
                fr.sj_fkr,
                fr.tglsj_fkr,
                fr.po_fkr,
                fr.tglpo_fkr,
                fr.subtot_fkr,
                fr.ppn_fkr,
                fr.total_fkr,
                fr.status_fkr,
                fr.pajak_fkr,
                s.nama_sup,
                s.npwp_sup,
                frd.id_fkrd,
                frd.id_pro,
                frd.jumlah_fkrd,
                frd.harga_fkrd,
                frd.diskon_fkrd,
                frd.total_fkrd,
                p.nama_pro,
                p.kode_pro,
                p.kategori_obat,
                p.kode_produk_jadi
            FROM faktur_retur fr
            LEFT JOIN supplier s ON fr.id_sup = s.id_sup
            LEFT JOIN faktur_returdetail frd ON fr.id_fkr = frd.id_fkr
            LEFT JOIN produk p ON frd.id_pro = p.id_pro
            WHERE 1=1";
    
    // Filter berdasarkan key (bisa nama supplier, nomor faktur, dll)
    if(!empty($key)){
        $query .= " AND (
            s.nama_sup LIKE :key OR 
            fr.kode_fkr LIKE :key OR 
            fr.sj_fkr LIKE :key OR 
            fr.po_fkr LIKE :key OR
            p.nama_pro LIKE :key
        )";
    }
    
    $query .= " ORDER BY fr.tglsj_fkr DESC, fr.kode_fkr, frd.id_fkrd";
    
    $stmt = $conn->prepare($query);
    
    if(!empty($key)){
        $searchKey = "%$key%";
        $stmt->bindParam(':key', $searchKey, PDO::PARAM_STR);
    }
    
    $stmt->execute();
    
    $no = 1;
    $current_faktur = '';
    $grand_subtotal = 0;
    $grand_ppn = 0;
    $grand_total = 0;
    $total_qty = 0;
    $faktur_count = 0;
    
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        $is_new_faktur = ($current_faktur != $row['id_fkr']);
        
        if($is_new_faktur){
            $current_faktur = $row['id_fkr'];
            $faktur_count++;
            
            // Get row count for this faktur to determine rowspan
            $countStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM faktur_returdetail WHERE id_fkr = :id_fkr");
            $countStmt->bindParam(':id_fkr', $row['id_fkr'], PDO::PARAM_STR);
            $countStmt->execute();
            $countData = $countStmt->fetch(PDO::FETCH_ASSOC);
            $rowspan = $countData['cnt'] > 0 ? $countData['cnt'] : 1;
        }
        
        echo "<tr>";
        
        if($is_new_faktur){
            echo "<td class='text-center' rowspan='" . $rowspan . "'>" . $no++ . "</td>";
            echo "<td rowspan='" . $rowspan . "'>" . date('d/m/Y', strtotime($row['tglsj_fkr'])) . "</td>";
            echo "<td rowspan='" . $rowspan . "'>" . htmlspecialchars($row['kode_fkr']) . "</td>";
            echo "<td rowspan='" . $rowspan . "'>" . htmlspecialchars($row['sj_fkr']) . "</td>";
            echo "<td rowspan='" . $rowspan . "'>" . htmlspecialchars($row['po_fkr']) . "</td>";
            echo "<td rowspan='" . $rowspan . "'>" . htmlspecialchars($row['nama_sup']) . "</td>";
            echo "<td rowspan='" . $rowspan . "'>" . htmlspecialchars($row['npwp_sup']) . "</td>";
        }
        
        // Detail produk (always shown per row)
        echo "<td>" . htmlspecialchars($row['nama_pro'] ?? '-') . "</td>";
                echo "<td>" . htmlspecialchars($row['kategori_obat'] ?? '-') . "</td>";
        echo "<td>" . htmlspecialchars($row['kode_produk_jadi'] ?? '-') . "</td>";

        echo "<td class='text-right'>" . number_format($row['jumlah_fkrd'], 0, ',', '.') . "</td>";
        echo "<td class='text-right'>Rp " . number_format($row['harga_fkrd'], 0, ',', '.') . "</td>";
        echo "<td class='text-right'>" . htmlspecialchars($row['diskon_fkrd']) . "%</td>";
        echo "<td class='text-right'>Rp " . number_format($row['total_fkrd'], 0, ',', '.') . "</td>";
        
        if($is_new_faktur){
            echo "<td class='text-right' rowspan='" . $rowspan . "'>Rp " . number_format($row['subtot_fkr'], 0, ',', '.') . "</td>";
            echo "<td class='text-right' rowspan='" . $rowspan . "'>Rp " . number_format($row['ppn_fkr'], 0, ',', '.') . "</td>";
            echo "<td class='text-right' rowspan='" . $rowspan . "'>Rp " . number_format($row['total_fkr'], 0, ',', '.') . "</td>";

            $grand_subtotal += $row['subtot_fkr'];
            $grand_ppn += $row['ppn_fkr'];
            $grand_total += $row['total_fkr'];
        }
        
        echo "</tr>";
        
        $total_qty += $row['jumlah_fkrd'];
    }
    
    // Grand Total row
    echo "<tr style='font-weight: bold; background-color: #f2f2f2;'>";
    echo "<td colspan='10' class='text-right'>GRAND TOTAL (" . $faktur_count . " Faktur)</td>";
    echo "<td class='text-right'>" . number_format($total_qty, 0, ',', '.') . "</td>";
    echo "<td colspan='3'></td>";
    echo "<td class='text-right'>Rp " . number_format($grand_subtotal, 0, ',', '.') . "</td>";
    echo "<td class='text-right'>Rp " . number_format($grand_ppn, 0, ',', '.') . "</td>";
    echo "<td class='text-right'>Rp " . number_format($grand_total, 0, ',', '.') . "</td>";
    // echo "<td></td>";
    echo "</tr>";
    
} catch (PDOException $e) {
    echo "<tr><td colspan='16' style='color: red;'>Error: " . $e->getMessage() . "</td></tr>";
}

$conn = $base->close();
?>
        </tbody>
    </table>
</body>
</html>
