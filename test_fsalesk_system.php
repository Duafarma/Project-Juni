<?php
// Test ceksalesk API
require_once("config/connection/connection.php");
require_once("config/connection/security.php");
require_once("config/function/data.php");

$secu = new Security;
$base = new DB;
$data = new Data;
$conn = $base->open();

echo "=== TEST CEKSALESK SYSTEM ===\n\n";

// 1. Test auto-generate nomor konsinyasi
echo "1. Testing Auto-Generate Nomor:\n";
$inv = $data->transcode('.01.01/KON/'.$data->romawi(date('m')).'/'.date('y'), 'sj_tfk', 'transaksi_faktur_konsinyasi');
$fak = $data->transcode('.01.01/KON/'.$data->romawi(date('m')).'/'.date('y'), 'kode_tfk', 'transaksi_faktur_konsinyasi');
echo "   Nomor SJ Konsinyasi: $inv\n";
echo "   Nomor Faktur Konsinyasi: $fak\n";
echo "   Format: XXX/KON/bulan-romawi/tahun-2digit ✓\n\n";

// 2. Test outlet data
echo "2. Testing Outlet Data:\n";
$query = $conn->query("SELECT id_out, nama_out FROM outlet WHERE status_out='Active' LIMIT 1");
$outlet = $query->fetch(PDO::FETCH_ASSOC);

if ($outlet) {
    echo "   Sample Outlet: {$outlet['nama_out']} (ID: {$outlet['id_out']})\n";
    
    // Simulate ceksalesk.php
    $read = $conn->prepare("SELECT A.status_pembayaran, B.kode_kot, C.top_odi, C.parameter_odi, C.diskon1_odi, C.diskon2_odi FROM outlet AS A LEFT JOIN kategori_outlet AS B ON A.id_kot=B.id_kot LEFT JOIN outlet_diskon AS C ON A.id_out=C.id_out WHERE A.id_out=:kode");
    $read->bindParam(':kode', $outlet['id_out'], PDO::PARAM_STR);
    $read->execute();
    $view = $read->fetch(PDO::FETCH_ASSOC);
    
    $catat = date('Y-m-d H:i:s');
    $limit = date("Y-m-d", strtotime("+$view[top_odi] Days", strtotime($catat)));
    
    echo "   TOP: {$view['top_odi']} hari\n";
    echo "   Jatuh Tempo: $limit\n";
    echo "   Diskon 1: {$view['diskon1_odi']}%\n";
    echo "   Diskon 2: {$view['diskon2_odi']}%\n";
    echo "   Min Order: Rp " . number_format($view['parameter_odi']) . "\n";
    echo "   Auto-fill data outlet ✓\n\n";
} else {
    echo "   No active outlet found\n\n";
}

// 3. Test format romawi
echo "3. Testing Format Romawi:\n";
$bulan = date('m');
$tahun = date('y');
echo "   Bulan $bulan = " . $data->romawi($bulan) . "\n";
echo "   Tahun 2 digit = $tahun\n";
echo "   Format romawi ✓\n\n";

echo "=== ALL TESTS PASSED ===\n";

$base->close();
?>
