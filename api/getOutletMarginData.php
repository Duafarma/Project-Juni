<?php
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Access-Control-Allow-Origin: *');
    header("Content-type: application/json; charset=utf-8");
    echo json_encode(["success" => false, "message" => "Method Not Allowed"]);
    exit;
}

require_once('../config/connection/connection.php');
require_once('../config/connection/security.php');
require_once('../config/function/data.php');
$secu = new Security;
$base = new DB;
$data = new Data;
$conn = $base->open();

$encrypt = $secu->injection($_GET['encrypt'] ?? '');
$tgl = date('Y-m-d');
$source = $data->self_apl();
$id_apl = $source['id_apl'];
$nama_apl = $source['nama_apl'];
$sourceKey = $source['key_apl'];

// Validasi key
if (md5($tgl . "#" . $sourceKey) != $encrypt) {
    http_response_code(401);
    header('Access-Control-Allow-Origin: *');
    header("Content-type: application/json; charset=utf-8");
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized",
        "data" => [],
        "total" => 0
    ]);
    exit;
}

// Parameter
$cari = $secu->injection($_GET['caridata'] ?? '');
$page = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$maxi = isset($_GET['maximal']) ? (int)$_GET['maximal'] : 1000;
$mulai = ($page > 1) ? (($page * $maxi) - $maxi) : 0;

$where = "A.status_out = 'Active'";
if (!empty($cari)) {
    $where .= " AND (A.nama_out LIKE :cari OR A.ofcode_out LIKE :cari)";
}

// Hitung total
$qCount = "SELECT COUNT(A.id_out) AS total 
    FROM outlet AS A 
    INNER JOIN outlet_diskon AS C ON A.id_out=C.id_out 
    LEFT JOIN kategori_outlet AS D ON A.id_kot=D.id_kot 
    WHERE $where";
$stmtCount = $conn->prepare($qCount);
if (!empty($cari)) $stmtCount->bindValue(':cari', "%$cari%");
$stmtCount->execute();
$total = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Ambil data
$qData = "SELECT 
        A.id_out,
        A.ofcode_out,
        A.nama_out,
        A.status_faktur AS tipe_faktur,
        C.diskon_odi AS diskon,
        A.profit AS modal,
        D.kode_kot AS kategori_outlet
    FROM outlet AS A 
    INNER JOIN outlet_diskon AS C ON A.id_out=C.id_out 
    LEFT JOIN kategori_outlet AS D ON A.id_kot=D.id_kot 
    WHERE $where
    ORDER BY A.nama_out ASC
    LIMIT $mulai, $maxi";
$stmt = $conn->prepare($qData);
if (!empty($cari)) $stmt->bindValue(':cari', "%$cari%");
$stmt->execute();

$dataArr = [];
$no = $mulai + 1;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $profit_margin = floatval($row['modal']) - floatval($row['diskon']);
    $dataArr[] = [
        "no" => $no++,
        "id_out" => $row['id_out'],
        "ofcode_out" => $row['ofcode_out'],
        "nama_out" => $row['nama_out'],
        "tipe_faktur" => $row['tipe_faktur'],
        "diskon" => $row['diskon'],
        "modal" => $row['modal'],
        "profit_margin" => $profit_margin,
        "kategori_outlet" => $row['kategori_outlet'],
        "id_apl" => $id_apl,           // <-- tambahkan ini
        "cabang" => $nama_apl          // <-- tetap kirim nama_apl
    ];
}

header('Access-Control-Allow-Origin: *');
header("Content-type: application/json; charset=utf-8");
echo json_encode([
    "success" => true,
    "data" => $dataArr
]);
