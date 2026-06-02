<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$sistem	= $data->sistem('url_sis');
	$catat	= date('Y-m-d H:i:s');
	$admin	= $secu->injection(@$_COOKIE['adminkuy']);
	$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
	$act	= $secu->injection(@$_GET['act']);
	$conn	= $base->open();

    // Cek parameter GET
if (!isset($_GET['keycode']) || !isset($_GET['tahun'])) {
    die(json_encode(["error" => "Parameter tidak lengkap"]));
}

$kode  = $_GET['keycode'];
$tahun = is_numeric($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');

// Fungsi untuk stok keluar tertinggi di semua bulan
// function outallmonthr($conn, $kode) {
//     $rin = $conn->prepare("
//         SELECT MONTH(A.tgl_tfk) AS bulan, IFNULL(SUM(B.jumlah_tfd),0) AS total 
//         FROM transaksi_faktur AS A 
//         INNER JOIN transaksi_fakturdetail AS B ON A.id_tfk=B.id_tfk 
//         WHERE B.id_pro = :kode  
//         GROUP BY MONTH(A.tgl_tfk)
//     ");
//     $rin->bindParam(':kode', $kode, PDO::PARAM_STR);
//     $rin->execute();
    
//     // Buat array default 12 bulan dengan nilai 0
//     $dataBulan = array_fill(1, 12, 0);

//     while ($row = $rin->fetch(PDO::FETCH_ASSOC)) {
//         $dataBulan[$row['bulan']] = $row['total'];
//     }

//     return $dataBulan;
// }

// Fungsi untuk mengambil bulan dengan stok keluar tertinggi
function outallmonth($conn, $kode, $tahun) {
    $rin = $conn->prepare("
        SELECT MONTH(A.tgl_tfk) AS bulan, IFNULL(SUM(B.jumlah_tfd), 0) AS total 
        FROM transaksi_faktur AS A 
        INNER JOIN transaksi_fakturdetail AS B ON A.id_tfk = B.id_tfk 
        WHERE B.id_pro = :kode AND YEAR(A.tgl_tfk) = :tahun
        GROUP BY bulan
        ORDER BY total DESC 
        LIMIT 1
    ");
    $rin->bindParam(':kode', $kode, PDO::PARAM_STR);
    $rin->bindParam(':tahun', $tahun, PDO::PARAM_INT);
    $rin->execute();

    $result = $rin->fetch(PDO::FETCH_ASSOC);

    // Jika ada hasil, kembalikan bulan dan totalnya, jika tidak, kembalikan nilai default
    return $result ? [$result['bulan'] => $result['total']] : [];
}

// Fungsi untuk mengambil data stok sisa
function stoksisa($conn, $kode, $tahun) {
    $rin = $conn->prepare("
        SELECT MONTH(created_at) AS bulan, IFNULL(SUM(qty_so),0) AS total 
        FROM so 
        WHERE id_pro = :kode 
        AND YEAR(created_at) = :tahun
        GROUP BY MONTH(created_at)
    ");
    $rin->bindParam(':kode', $kode, PDO::PARAM_STR);
    $rin->bindParam(':tahun', $tahun, PDO::PARAM_INT);
    $rin->execute();
    
    // Buat array default 12 bulan dengan nilai 0
    $dataBulan = array_fill(1, 12, 0);

    while ($row = $rin->fetch(PDO::FETCH_ASSOC)) {
        $dataBulan[$row['bulan']] = $row['total'];
    }

    return $dataBulan;
}

// Fungsi untuk mengambil data stok keluar
function outmonth($conn, $kode, $tahun) {
    $rin = $conn->prepare("
        SELECT MONTH(B.tgl_tfk) AS bulan, IFNULL(SUM(A.jumlah_tfd),0) AS total 
        FROM transaksi_fakturdetail AS A 
        INNER JOIN transaksi_faktur AS B ON A.id_tfk=B.id_tfk 
        WHERE A.id_pro = :kode 
        AND YEAR(B.tgl_tfk) = :tahun
        GROUP BY MONTH(B.tgl_tfk)
    ");
    $rin->bindParam(':kode', $kode, PDO::PARAM_STR);
    $rin->bindParam(':tahun', $tahun, PDO::PARAM_INT);
    $rin->execute();
    
    // Buat array default 12 bulan dengan nilai 0
    $dataBulan = array_fill(1, 12, 0);

    while ($row = $rin->fetch(PDO::FETCH_ASSOC)) {
        $dataBulan[$row['bulan']] = $row['total'];
    }

    return $dataBulan;
}

// Ambil data stok masuk, sisa, dan keluar berdasarkan kode dan tahun
$outallData = outallmonth($conn, $kode, $tahun);
$stokData = stoksisa($conn, $kode, $tahun);
$outData = outmonth($conn, $kode, $tahun);

// Buat array bulan lengkap sebagai label
$labels = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", 
           "Juli", "Agustus", "September", "Oktober", "November", "Desember"];

// Ubah array hasil query agar sesuai urutan bulan
$outallArray = array_fill(0, 12, 0);

if (!empty($outallData)) {
    foreach ($outallData as $bulan => $total) {
        $outallArray[$bulan - 1] = $total; // Sesuaikan indeks array (0-based index)
    }
}
$stokArray = array_values($stokData);
$outArray = array_values($outData);

// Output dalam format JSON
echo json_encode([
    "labels" => $labels,
    "outallData" => $outallArray,
    "stokData" => $stokArray,
    "outData" => $outArray
]);
	$conn	= $base->close();
?>