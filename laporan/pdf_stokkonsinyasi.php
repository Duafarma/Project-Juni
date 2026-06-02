<?php
// Enable error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('../config/connection/connection.php');
require_once('../config/connection/security.php');
require_once('../config/function/data.php');

$secu = new Security;
$base = new DB;
$data = new Data;
$conn = $base->open();

// Cek koneksi
if(!$conn) {
    die("Koneksi database gagal");
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok Detail Konsinyasi - PDF</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @page {
            size: A4 landscape;
            margin: 15mm;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            table {
                page-break-inside: auto;
            }
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px solid #5a67d8;
            padding-bottom: 15px;
        }
        
        .header h2 {
            margin: 0 0 10px 0;
            color: #5a67d8;
            font-size: 20px;
            text-transform: uppercase;
        }
        
        .header p {
            margin: 5px 0;
            color: #666;
            font-size: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        th {
            background-color: #5a67d8;
            color: white;
            font-weight: bold;
            text-align: center;
            font-size: 10px;
            text-transform: uppercase;
        }
        
        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        tbody tr:hover {
            background-color: #f0f0f0;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .footer-row {
            background-color: #f0f0f0 !important;
            font-weight: bold;
            border-top: 2px solid #5a67d8;
        }
        
        .badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }
        
        .badge-active {
            background-color: #48bb78;
            color: white;
        }
        
        .badge-inactive {
            background-color: #f56565;
            color: white;
        }
        
        .info-box {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-left: 4px solid #5a67d8;
            font-size: 10px;
        }
        
        .info-box strong {
            color: #5a67d8;
        }
        
        .btn-print {
            background-color: #5a67d8;
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        .btn-print:hover {
            background-color: #4a57c8;
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button class="btn-print" onclick="window.print();">
            <i class="fa fa-print"></i> Print / Save as PDF
        </button>
        <button class="btn-print" onclick="window.close();" style="background-color: #6c757d;">
            <i class="fa fa-times"></i> Tutup
        </button>
    </div>
    
    <div class="header">
        <h2>📦 LAPORAN STOK DETAIL KONSINYASI</h2>
        <p><strong>DUA FARMA INVENTORY SYSTEM</strong></p>
        <p>Tanggal Cetak: <?php echo date('d F Y H:i:s'); ?></p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th style="width: 3%;">No</th>
                <th style="width: 7%;">Tanggal</th>
                <th style="width: 10%;">Nomor Faktur</th>
                <th style="width: 12%;">Outlet</th>
                <th style="width: 8%;">Rentang Waktu</th>
                <th style="width: 20%;">Nama Produk</th>
                <th style="width: 10%;">Barcode</th>
                <th style="width: 7%;">Expired</th>
                <th style="width: 7%;">Keluar</th>
                <th style="width: 8%;">Status</th>
            </tr>
        </thead>
        <tbody>
        <?php
            $no = 1;
            $total_keluar = 0;
            
            try {
                $qData = "SELECT 
                            psk.id_psd,
                            psk.tgl_psd,
                            psk.no_bcode,
                            psk.tgl_expired,
                            psk.sisa_psd,
                            psk.status_barang,
                            pro.kode_pro,
                            pro.nama_pro,
                            tfk.sj_tfk,
                            tfk.tgl_tfk,
                            outl.nama_out
                        FROM produk_stokdetail_konsinyasi psk
                        LEFT JOIN produk pro ON psk.id_pro = pro.id_pro
                        LEFT JOIN transaksi_faktur_konsinyasi tfk ON psk.id_tfk = tfk.id_tfk
                        LEFT JOIN outlet outl ON tfk.id_out = outl.id_out
                        ORDER BY psk.tgl_psd DESC, psk.id_psd DESC";
                
                $records = $conn->query($qData);
                
                if($records && $records->rowCount() > 0) {
                    while($record = $records->fetch(PDO::FETCH_ASSOC)){
                        $tgl_psd = date('d/m/Y', strtotime($record['tgl_psd']));
                        $tgl_expired = date('d/m/Y', strtotime($record['tgl_expired']));
                        
                        // Hitung rentang waktu konsinyasi
                        $rentang_waktu = '-';
                        $badge_class = '';
                        if($record['tgl_tfk']){
                            $tgl_mulai = new DateTime($record['tgl_tfk']);
                            $tgl_sekarang = new DateTime();
                            $selisih = $tgl_mulai->diff($tgl_sekarang);
                            $hari = $selisih->days;
                            $rentang_waktu = $hari . ' hari';
                        }
                        
                        $total_keluar += $record['sisa_psd'];
                        
                        if($record['status_barang'] == 'active'){
                            $status = '<span class="badge badge-active">Active</span>';
                        } else {
                            $status = '<span class="badge badge-inactive">Inactive</span>';
                        }
        ?>
            <tr>
                <td class="text-center"><?php echo $no; ?></td>
                <td class="text-center"><?php echo $tgl_psd; ?></td>
                <td><?php echo $record['sj_tfk'] ? $record['sj_tfk'] : '-'; ?></td>
                <td><?php echo $record['nama_out'] ? $record['nama_out'] : '-'; ?></td>
                <td class="text-center"><?php echo $rentang_waktu; ?></td>
                <td><?php echo $record['nama_pro'] ? $record['nama_pro'] : '-'; ?></td>
                <td><?php echo $record['no_bcode'] ? $record['no_bcode'] : '-'; ?></td>
                <td class="text-center"><?php echo $tgl_expired; ?></td>
                <td class="text-right"><strong><?php echo number_format($record['sisa_psd'], 0, ',', '.'); ?></strong></td>
                <td class="text-center"><?php echo $status; ?></td>
            </tr>
        <?php
                        $no++;
                    }
                } else {
                    echo '<tr><td colspan="10" class="text-center">Tidak ada data</td></tr>';
                }
            } catch (PDOException $e) {
                echo '<tr><td colspan="10" class="text-center">Error: ' . $e->getMessage() . '</td></tr>';
            }
        ?>
        </tbody>
        <tfoot>
            <tr class="footer-row">
                <td colspan="9" class="text-right"><strong>TOTAL KELUAR:</strong></td>
                <td class="text-right"><strong><?php echo number_format($total_keluar, 0, ',', '.'); ?></strong></td>
            </tr>
        </tfoot>
    </table>
    
    <div class="info-box">
        <strong>Keterangan:</strong><br>
        • Laporan ini menampilkan riwayat pengeluaran barang konsinyasi secara detail<br>
        • Data diurutkan berdasarkan tanggal transaksi terbaru<br>
        • Total Keluar: <strong><?php echo number_format($total_keluar, 0, ',', '.'); ?> unit</strong><br>
        • Total Data: <strong><?php echo ($no - 1); ?> baris</strong>
    </div>
    
    <script>
        // Auto print saat halaman dimuat (opsional, bisa dinonaktifkan)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
<?php
                
$conn = $base->close();
?>
