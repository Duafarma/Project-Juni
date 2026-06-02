<?php
// Enable error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('../../../config/connection/connection.php');
require_once('../../../config/connection/security.php');
require_once('../../../config/function/data.php');

$secu = new Security;
$base = new DB;
$data = new Data;

try {
    $conn = $base->open();
} catch (Exception $e) {
    die("Connection Error: " . $e->getMessage());
}

// Set header untuk download Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Laporan_SO_2025_" . date('Y-m-d_His') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Stock Opname 2025</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 30px;
        }
        th, td {
            border: 1px solid black;
            padding: 5px;
            text-align: left;
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
        .month-header {
            background-color: #2196F3;
            color: white;
            font-weight: bold;
            font-size: 14px;
            padding: 10px;
            margin-top: 20px;
        }
        .total-row {
            font-weight: bold;
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div class="header-info">
        <h2>LAPORAN STOCK OPNAME TAHUN 2025</h2>
        <p>Per Bulan (Januari - Desember 2025)</p>
        <p>Tanggal Cetak: <?php echo date('d/m/Y H:i:s'); ?></p>
    </div>

<?php
try {
    // Array nama bulan
    $bulan_names = [
        1 => 'JAN', 2 => 'FEB', 3 => 'MAR', 4 => 'APR',
        5 => 'MEI', 6 => 'JUN', 7 => 'JUL', 8 => 'AGT',
        9 => 'SEP', 10 => 'OKT', 11 => 'NOV', 12 => 'DES'
    ];
    
    // Ambil semua produk yang pernah ada SO di tahun 2025 beserta harga
    $queryProducts = "SELECT DISTINCT TRIM(p.id_pro) as id_pro, p.nama_pro, ph.harga_phg, 
                      COALESCE(mp.nama_principle, '-') as nama_principle
                      FROM so s
                      LEFT JOIN produk p ON TRIM(s.id_pro) = TRIM(p.id_pro)
                      LEFT JOIN produk_harga ph ON TRIM(p.id_pro) = TRIM(ph.id_pro) AND ph.status_phg = 'Active'
                      LEFT JOIN master_principle mp ON p.nama_p = mp.id_mp
                      WHERE YEAR(s.created_at) = 2025
                      ORDER BY p.nama_pro ASC";
    
    $stmtProducts = $conn->query($queryProducts);
    $products = $stmtProducts->fetchAll(PDO::FETCH_ASSOC);
    
    // Buat array untuk menyimpan data per produk per bulan
    $data_matrix = [];
    
    foreach($products as $product) {
        $id_pro = $product['id_pro'];
        $nama_pro = $product['nama_pro'];
        $harga_phg = $product['harga_phg'] ?? 0;
        $nama_principle = $product['nama_principle'];
        
        $data_matrix[$id_pro] = [
            'nama_pro' => $nama_pro,
            'harga_phg' => $harga_phg,
            'nama_principle' => $nama_principle,
            'bulan' => []
        ];
        
        // Inisialisasi semua bulan dengan 0
        for($b = 1; $b <= 12; $b++) {
            $data_matrix[$id_pro]['bulan'][$b] = [
                'qty' => 0,
                'nilai' => 0
            ];
        }
    }
    
    // Ambil data SO per produk per bulan (sum qty_so untuk produk yang sama di bulan yang sama)
    $queryData = "SELECT 
                    TRIM(s.id_pro) as id_pro,
                    MONTH(s.created_at) as bulan,
                    SUM(s.qty_so) as total_qty_so
                  FROM so s
                  WHERE YEAR(s.created_at) = 2025
                  GROUP BY TRIM(s.id_pro), MONTH(s.created_at)";
    
    $stmtData = $conn->query($queryData);
    $dataRows = $stmtData->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($dataRows as $row) {
        $id_pro = $row['id_pro'];
        $bulan = $row['bulan'];
        $qty = $row['total_qty_so'];
        
        if(isset($data_matrix[$id_pro])) {
            $harga = $data_matrix[$id_pro]['harga_phg'];
            $nilai = $qty * $harga;
            
            $data_matrix[$id_pro]['bulan'][$bulan] = [
                'qty' => $qty,
                'nilai' => $nilai
            ];
        }
    }
    
    // Tampilkan tabel horizontal
    echo "<table>";
    echo "<thead>";
    echo "<tr>";
    echo "<th rowspan='2'>No</th>";
    echo "<th rowspan='2'>Nama Produk</th>";
    echo "<th rowspan='2'>Principle</th>";
    echo "<th rowspan='2'>Harga</th>";
    
    for($b = 1; $b <= 12; $b++) {
        echo "<th colspan='2' class='text-center'>" . $bulan_names[$b] . "</th>";
    }
    
    echo "<th colspan='2' class='text-center'>TOTAL</th>";
    echo "</tr>";
    echo "<tr>";
    
    for($b = 1; $b <= 12; $b++) {
        echo "<th class='text-right'>Qty</th>";
        echo "<th class='text-right'>Nilai</th>";
    }
    
    echo "<th class='text-right'>Qty</th>";
    echo "<th class='text-right'>Nilai</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    
    $no = 1;
    $total_per_bulan_qty = array_fill(1, 12, 0);
    $total_per_bulan_nilai = array_fill(1, 12, 0);
    $grand_total_qty = 0;
    $grand_total_nilai = 0;
    
    foreach($data_matrix as $id_pro => $data) {
        echo "<tr>";
        echo "<td class='text-center'>" . $no++ . "</td>";
        echo "<td>" . htmlspecialchars($data['nama_pro']) . "</td>";
        echo "<td>" . htmlspecialchars($data['nama_principle']) . "</td>";
        echo "<td class='text-right'>" . number_format($data['harga_phg'], 0, ',', '.') . "</td>";
        
        $total_produk_qty = 0;
        $total_produk_nilai = 0;
        
        for($b = 1; $b <= 12; $b++) {
            $qty = $data['bulan'][$b]['qty'];
            $nilai = $data['bulan'][$b]['nilai'];
            
            echo "<td class='text-right'>" . number_format($qty, 0, ',', '.') . "</td>";
            echo "<td class='text-right'>" . number_format($nilai, 0, ',', '.') . "</td>";
            
            $total_produk_qty += $qty;
            $total_produk_nilai += $nilai;
            $total_per_bulan_qty[$b] += $qty;
            $total_per_bulan_nilai[$b] += $nilai;
        }
        
        echo "<td class='text-right'><strong>" . number_format($total_produk_qty, 0, ',', '.') . "</strong></td>";
        echo "<td class='text-right'><strong>" . number_format($total_produk_nilai, 0, ',', '.') . "</strong></td>";
        echo "</tr>";
        
        $grand_total_qty += $total_produk_qty;
        $grand_total_nilai += $total_produk_nilai;
    }
    
    // Baris total
    echo "<tr class='total-row'>";
    echo "<td colspan='4' class='text-right'><strong>TOTAL</strong></td>";
    
    for($b = 1; $b <= 12; $b++) {
        echo "<td class='text-right'><strong>" . number_format($total_per_bulan_qty[$b], 0, ',', '.') . "</strong></td>";
        echo "<td class='text-right'><strong>" . number_format($total_per_bulan_nilai[$b], 0, ',', '.') . "</strong></td>";
    }
    
    echo "<td class='text-right'><strong>" . number_format($grand_total_qty, 0, ',', '.') . "</strong></td>";
    echo "<td class='text-right'><strong>" . number_format($grand_total_nilai, 0, ',', '.') . "</strong></td>";
    echo "</tr>";
    
    echo "</tbody>";
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<div style='color: red; padding: 20px; border: 2px solid red;'>";
    echo "<h3>Database Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='color: red; padding: 20px; border: 2px solid red;'>";
    echo "<h3>Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

$conn = $base->close();
?>
</body>
</html>
