<?php
require_once('../../config/connection/connection.php');
require_once('../../config/connection/security.php');
require_once('../../config/function/data.php');

$secu   = new Security;
$base   = new DB;
$data   = new Data;
$conn   = $base->open();

// Cek koneksi database
if (!$conn) {
    die(json_encode(["error" => "Koneksi database gagal!"]));
}

// Cek parameter id_pro
if (!isset($_GET['id_pro'])) {
    die(json_encode(["error" => "Parameter tidak lengkap!", "id_pro" => $_GET['id_pro'] ?? null]));
}

$kode = htmlspecialchars($_GET['id_pro']); // ID produk dari dropdown
$tahun_ini = date('Y');
$tahun1 = $tahun_ini - 2;
$tahun2 = $tahun_ini - 1;
$tahun3 = $tahun_ini;

// Fungsi untuk mengambil data transaksi faktur per bulan
function transaksiFaktur($conn, $kode, $tahun1, $tahun3) {
    $query = "
        SELECT YEAR(B.tgl_tfk) AS tahun, MONTH(B.tgl_tfk) AS bulan, IFNULL(SUM(A.jumlah_tfd), 0) AS total 
        FROM transaksi_fakturdetail AS A 
        INNER JOIN transaksi_faktur AS B ON A.id_tfk = B.id_tfk 
        WHERE A.id_pro = :kode 
        AND YEAR(B.tgl_tfk) BETWEEN :tahun1 AND :tahun3
        GROUP BY tahun, bulan
        ORDER BY tahun ASC, bulan ASC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':kode', $kode, PDO::PARAM_STR);
    $stmt->bindParam(':tahun1', $tahun1, PDO::PARAM_INT);
    $stmt->bindParam(':tahun3', $tahun3, PDO::PARAM_INT);
    $stmt->execute();

    $data = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $data[$row['tahun']][$row['bulan']] = $row['total'];
    }

    return $data;
}

// Fungsi untuk mengambil data stok sisa per bulan
function stoksisa($conn, $kode, $tahun) {
    $query = "
        SELECT MONTH(created_at) AS bulan, IFNULL(SUM(sisa_psd), 0) AS total 
        FROM produk_stokdetail 
        WHERE id_pro = :kode 
        AND YEAR(created_at) = :tahun
        GROUP BY MONTH(created_at)
    ";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':kode', $kode, PDO::PARAM_STR);
    $stmt->bindParam(':tahun', $tahun, PDO::PARAM_INT);
    $stmt->execute();

    $dataBulan = array_fill(1, 12, 0); // Default 12 bulan dengan nilai 0
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $dataBulan[$row['bulan']] = $row['total'];
    }

    return $dataBulan;
}

// Ambil data transaksi faktur dan stok sisa
$dataTransaksi = transaksiFaktur($conn, $kode, $tahun1, $tahun3);
$stokSisa1 = stoksisa($conn, $kode, $tahun1);
$stokSisa2 = stoksisa($conn, $kode, $tahun2);
$stokSisa3 = stoksisa($conn, $kode, $tahun3);

// Pastikan setiap bulan memiliki data, jika tidak set default 0
$finalData = [];
foreach ([$tahun1, $tahun2, $tahun3] as $thn) {
    $finalData[$thn] = array_fill(1, 12, 0);
    if (!empty($dataTransaksi[$thn])) {
        foreach ($dataTransaksi[$thn] as $bulan => $total) {
            $finalData[$thn][$bulan] = $total;
        }
    }
}

// Kirim data dalam format JSON
header('Content-Type: application/json');
echo json_encode([
    "labels" => ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"],
    "tahun1_label" => $tahun1,
    "tahun2_label" => $tahun2,
    "tahun3_label" => $tahun3,
    "tahun1" => array_values($finalData[$tahun1]),
    "tahun2" => array_values($finalData[$tahun2]),
    "tahun3" => array_values($finalData[$tahun3]),
    "stokSisa1" => array_values($stokSisa1),
    "stokSisa2" => array_values($stokSisa2),
    "stokSisa3" => array_values($stokSisa3)
]);

$conn = $base->close();

?>
