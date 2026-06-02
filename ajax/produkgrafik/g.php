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

// Cek apakah parameter id_pro dan tahun tersedia
if (!isset($_GET['id_pro']) || !isset($_GET['tahun'])) {
    die(json_encode(["error" => "Parameter tidak lengkap!", "id_pro" => $_GET['id_pro'] ?? null, "tahun" => $_GET['tahun'] ?? null]));
}

$kode  = htmlspecialchars($_GET['id_pro']); // ID produk dari dropdown
$tahun = is_numeric($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');
$id_out = isset($_GET['id_out']) ? $_GET['id_out'] : null; // Tambahkan ini

// Fungsi untuk mengambil bulan dengan penjualan tertinggi
function outallmonth($conn, $kode, $tahun) {
    $query = "
        SELECT MONTH(A.tgl_tfk) AS bulan, IFNULL(SUM(B.jumlah_tfd), 0) AS total 
        FROM transaksi_faktur AS A 
        INNER JOIN transaksi_fakturdetail AS B ON A.id_tfk = B.id_tfk 
        WHERE B.id_pro = :kode AND YEAR(A.tgl_tfk) = :tahun
        GROUP BY bulan
        ORDER BY total DESC 
        LIMIT 1
    ";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':kode', $kode, PDO::PARAM_STR);
    $stmt->bindParam(':tahun', $tahun, PDO::PARAM_INT);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? [$result['bulan'] => $result['total']] : [];
}

// Fungsi untuk mengambil data stok sisa per bulan
function stoksisa($conn, $kode, $tahun) {
    $query = "
        SELECT MONTH(created_at) AS bulan, IFNULL(SUM(qty_so),0) AS total 
        FROM so 
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

// Fungsi untuk mengambil data stok keluar per bulan
function outmonth($conn, $kode, $tahun) {
    $query = "
        SELECT MONTH(B.tgl_tfk) AS bulan, IFNULL(SUM(A.jumlah_tfd),0) AS total 
        FROM transaksi_fakturdetail AS A 
        INNER JOIN transaksi_faktur AS B ON A.id_tfk=B.id_tfk 
        WHERE A.id_pro = :kode 
        AND YEAR(B.tgl_tfk) = :tahun
        GROUP BY MONTH(B.tgl_tfk)
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
// Fungsi 
function outlet($conn, $kode, $tahun, $id_out) {
    $query = "
        SELECT MONTH(B.tgl_tfk) AS bulan, IFNULL(SUM(A.jumlah_tfd), 0) AS total 
        FROM transaksi_fakturdetail AS A 
        INNER JOIN transaksi_faktur AS B ON A.id_tfk = B.id_tfk 
        WHERE A.id_pro = :kode 
        AND YEAR(B.tgl_tfk) = :tahun
        AND B.id_out = :id_out
        GROUP BY MONTH(B.tgl_tfk)
    ";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':kode', $kode, PDO::PARAM_STR);
    $stmt->bindParam(':tahun', $tahun, PDO::PARAM_INT);
    $stmt->bindParam(':id_out', $id_out, PDO::PARAM_INT); // Tambahkan parameter outlet
    $stmt->execute();

    $dataBulan = array_fill(1, 12, 0); // Default 12 bulan dengan nilai 0
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $dataBulan[$row['bulan']] = $row['total'];
    }

    return $dataBulan;
}

// Ambil data stok masuk, sisa, dan keluar berdasarkan kode dan tahun
$outallData = outallmonth($conn, $kode, $tahun);
$stokData = stoksisa($conn, $kode, $tahun);
$outData = outmonth($conn, $kode, $tahun);
$outletData = outlet($conn, $kode, $tahun, $id_out);

// Label bulan dalam bahasa Indonesia
$labels = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", 
           "Juli", "Agustus", "September", "Oktober", "November", "Desember"];

// Konversi hasil query menjadi array yang sesuai dengan urutan bulan
$outallArray = array_fill(0, 12, 0);
if (!empty($outallData)) {
    foreach ($outallData as $bulan => $total) {
        $outallArray[$bulan - 1] = $total; // Sesuaikan indeks array
    }
}
$stokArray = array_values($stokData);
$outArray = array_values($outData);
$outletArray = array_values($outletData);

// Ambil nama produk berdasarkan id_pro
$stmt = $conn->prepare("SELECT nama_pro FROM produk WHERE id_pro = :kode");
$stmt->bindParam(':kode', $kode, PDO::PARAM_STR);
$stmt->execute();
$namaProduk = $stmt->fetchColumn();

// Jika produk tidak ditemukan, kirim error
if (!$namaProduk) {
    echo json_encode(["error" => "Produk tidak ditemukan!", "nama_produk" => null]);
    exit;
}
// Cek apakah ada data yang ditemukan
if (array_sum($stokArray) === 0 && array_sum($outArray) === 0 && array_sum($outallArray) === 0 && array_sum($outletArray) === 0) {
    echo json_encode(["error" => "Data tidak ditemukan untuk produk ini pada tahun $tahun"]);
} else {
    // Output dalam format JSON untuk ditampilkan di Chart.js
    echo json_encode([
        "nama_produk" => $namaProduk,  // Pastikan ini ada!
        "labels" => $labels,
        "outallData" => $outallArray,
        "stokData" => $stokArray,
        "outData" => $outArray,
        "outletData" => $outletArray
    ]);
}

$conn = $base->close();
?>
