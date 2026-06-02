<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
// ...lanjutkan kode Anda
// File: content/kuitansi/generate_kuitansi.php
require_once('../../config/connection/connection.php');
require_once('../../config/function/data.php');
require_once('../../plugins/dompdf/autoload.inc.php');

// Inisialisasi objek koneksi database dan fungsi
$base = new DB;
$data = new Data;
$conn = $base->open();

// Ambil ID transaksi dari parameter URL
$id = isset($_GET['id']) ? $_GET['id'] : '';

// Validasi ID transaksi
if (empty($id)) {
    die('ID transaksi tidak valid');
}

// Ambil data transaksi
$query = $conn->prepare("SELECT * FROM transaksi WHERE id_trans = :id");
$query->bindParam(':id', $id, PDO::PARAM_STR);
$query->execute();
$transaksi = $query->fetch(PDO::FETCH_ASSOC);

// Ambil detail transaksi + produk + merek + divisi + principle
$query_detail = $conn->prepare("
    SELECT td.*, p.nama_pro, p.id_merek, p.id_divisi, p.nama_p, 
           m.nama_merek, d.nama_divisi, mp.nama_principle
    FROM transaksi_detail td
    LEFT JOIN produk p ON td.id_pro = p.id_pro
    LEFT JOIN merek m ON p.id_merek = m.id_merek
    LEFT JOIN divisi d ON p.id_divisi = d.id_divisi
    LEFT JOIN master_principle mp ON p.nama_p = mp.id_mp
    WHERE td.id_trans = :id
");
$query_detail->bindParam(':id', $id, PDO::PARAM_STR);
$query_detail->execute();
$detail_items = $query_detail->fetchAll(PDO::FETCH_ASSOC);

if (empty($detail_items)) {
    die('Detail transaksi tidak ditemukan atau kosong.');
}

// Inisialisasi subtotal kategori
$subtotal_organa = 0;
$subtotal_suplemen = 0;
$subtotal_dua_farma = 0;
$subtotal_dimensi = 0;

foreach ($detail_items as $item) {
    $subtotal = ($item['harga_transd'] * $item['qty_transd']) - (($item['diskon_transd'] / 100) * ($item['harga_transd'] * $item['qty_transd']));
    // Organa: berdasarkan merek
    if (isset($item['nama_merek']) && stripos($item['nama_merek'], 'organa') !== false) {
        $subtotal_organa += $subtotal;
    }
    // Suplemen Kesehatan: berdasarkan divisi
    if (isset($item['nama_divisi']) && stripos($item['nama_divisi'], 'suplemen') !== false) {
        $subtotal_suplemen += $subtotal;
    }
    // Dua Farma: berdasarkan principle
    if (isset($item['nama_principle']) && stripos($item['nama_principle'], 'dua farma') !== false) {
        $subtotal_dua_farma += $subtotal;
    }
    // Dimensi: berdasarkan principle
    if (isset($item['nama_principle']) && stripos($item['nama_principle'], 'dimensi') !== false) {
        $subtotal_dimensi += $subtotal;
    }
}

// Hitung PPN 11% dan total per kategori
function hitungTotalPPN($subtotal) {
    $ppn = $subtotal * 0.11;
    return [$ppn, $subtotal + $ppn];
}
list($ppn_organa, $total_organa) = hitungTotalPPN($subtotal_organa);
list($ppn_suplemen, $total_suplemen) = hitungTotalPPN($subtotal_suplemen);
list($ppn_dua_farma, $total_dua_farma) = hitungTotalPPN($subtotal_dua_farma);
list($ppn_dimensi, $total_dimensi) = hitungTotalPPN($subtotal_dimensi);

$jumlah_akhir = $total_organa + $total_suplemen + $total_dua_farma + $total_dimensi;

function formatRupiah($angka) {
    return number_format($angka, 0, ',', '.');
}

// Output sederhana untuk debug
echo "Total Organa: Rp" . formatRupiah($total_organa) . "<br>";
echo "Total Suplemen Kesehatan: Rp" . formatRupiah($total_suplemen) . "<br>";
echo "Total Dua Farma: Rp" . formatRupiah($total_dua_farma) . "<br>";
echo "Total Dimensi: Rp" . formatRupiah($total_dimensi) . "<br>";
echo "<b>Jumlah Akhir: Rp" . formatRupiah($jumlah_akhir) . "</b><br>";

// Jika sudah muncul, lanjutkan ke tampilan HTML seperti sebelumnya.
header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Kuitansi <?php echo $transaksi['no_invoice']; ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        @media print { body { padding: 0; margin: 0; } .no-print, .print-button { display: none; } }
        h2 { text-align: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        table.header td { padding: 5px 0; }
        table.items { margin-top: 20px; }
        table.items th, table.items td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        table.items th { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer { margin-top: 30px; font-size: 12px; }
        .bold { font-weight: bold; }
        .print-button { background-color: #4CAF50; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; margin-bottom: 20px; }
        .print-button:hover { background-color: #45a049; }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="print-button" onclick="window.print()">Cetak Kuitansi</button>
        <button class="print-button" onclick="window.close()">Tutup</button>
    </div>
    <h2>KUITANSI</h2>
    <table class="header">
        <tr>
            <td width="25%">Nomor Kuitansi</td>
            <td width="25%">: <?php echo $transaksi['no_invoice']; ?></td>
        </tr>
        <tr>
            <td>Tanggal Transaksi</td>
            <td>: <?php echo date('d/m/Y', strtotime($transaksi['tgl_trans'])); ?></td>
        </tr>
        <tr>
            <td>No HP</td>
            <td>: 628<?php echo $transaksi['no_whatsapp']; ?></td>
        </tr>
        <tr>
            <td>Nama</td>
            <td>: <?php echo $transaksi['nama_customer']; ?></td>
            <td class="bold">Total Organa</td>
            <td class="text-right bold">Rp<?php echo formatRupiah($total_organa); ?></td>
        </tr>
        <tr>
            <td>Klasifikasi Harga</td>
            <td>: <?php echo $transaksi['klasifikasi_harga']; ?></td>
            <td class="bold">Total Suplemen Kesehatan</td>
            <td class="text-right bold">Rp<?php echo formatRupiah($total_suplemen); ?></td>
        </tr>
        <tr>
            <td>Status Order</td>
            <td>: <?php echo ($transaksi['order_type'] == 'preorder') ? 'Pre Order' : 'Ready Stock'; ?></td>
            <td class="bold">Total Dua Farma</td>
            <td class="text-right bold">Rp<?php echo formatRupiah($total_dua_farma); ?></td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td class="bold">Total Dimensi Cipta Mandiri</td>
            <td class="text-right bold">Rp<?php echo formatRupiah($total_dimensi); ?></td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td class="bold">Jumlah Akhir</td>
            <td class="text-right bold">Rp<?php echo formatRupiah($jumlah_akhir); ?></td>
        </tr>
    </table>
    <table class="items">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Produk</th>
                <th>Qty</th>
                <th>Harga</th>
                <th>Diskon %</th>
                <th>Subtotal</th>
                <th>PPN</th>
                <th>Total</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; foreach ($detail_items as $item): 
                $subtotalBeforeDiskon = $item['harga_pro'] * $item['qty_transd'];
                $diskonPersen = $item['diskon_transd'];
                $diskonNilai = ($diskonPersen / 100) * $subtotalBeforeDiskon;
                $subtotalAfterDiskon = $subtotalBeforeDiskon - $diskonNilai;
                $ppnNilai = 0.11 * $subtotalAfterDiskon;
                $totalWithPpn = $subtotalAfterDiskon + $ppnNilai;
            ?>
            <tr>
                <td><?php echo $no; ?></td>
                <td><?php echo $item['nama_pro']; ?></td>
                <td><?php echo $item['qty_transd']; ?></td>
                <td class="text-right">Rp<?php echo number_format($item['harga_pro'], 0, ',', '.'); ?></td>
                <td class="text-center"><?php echo $diskonPersen; ?>%</td>
                <td class="text-right">Rp<?php echo number_format($subtotalAfterDiskon, 0, ',', '.'); ?></td>
                <td class="text-right">Rp<?php echo number_format($ppnNilai, 0, ',', '.'); ?></td>
                <td class="text-right">Rp<?php echo number_format($totalWithPpn, 0, ',', '.'); ?></td>
                <td><?php echo $item['catatan_transd'] ?? ''; ?></td>
            </tr>
            <?php $no++; endforeach; ?>
        </tbody>
    </table>
    <div class="footer">
        <p>* Harga termasuk PPN 11% kecuali dinyatakan lain.</p>
        <p>* Untuk pembayaran mohon transfer ke rekening Bank Mandiri 123-000-1234567 a.n PT. Dua Farma</p>
    </div>
    <script>
        window.onload = function() {
            // window.print();
        }
    </script>
</body>
</html>
<?php
// Tutup koneksi database
$conn = $base->close();
?>