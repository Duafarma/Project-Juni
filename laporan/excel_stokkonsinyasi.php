<?php
require_once('../config/connection/connection.php');
require_once('../config/connection/security.php');
require_once('../config/function/data.php');

$secu = new Security;
$base = new DB;
$data = new Data;
$conn = $base->open();

// Set header untuk download Excel
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Stok_Detail_Konsinyasi_" . date('Y-m-d_His') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Stok Detail Konsinyasi</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #5a67d8;
            color: white;
            font-weight: bold;
            text-align: center;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .footer {
            background-color: #f0f0f0;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>LAPORAN STOK DETAIL KONSINYASI</h2>
        <p>Tanggal Export: <?php echo date('d/m/Y H:i:s'); ?></p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Nomor Faktur</th>
                <th>Outlet</th>
                <th>Rentang Waktu</th>
                <th>Nama Produk</th>
                <th>Barcode</th>
                <th>Expired</th>
                <th>Keluar</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php
            $no = 1;
            $total_keluar = 0;
            
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
            
            while($record = $records->fetch(PDO::FETCH_ASSOC)){
                $tgl_psd = date('d/m/Y', strtotime($record['tgl_psd']));
                $tgl_expired = date('d/m/Y', strtotime($record['tgl_expired']));
                
                // Hitung rentang waktu konsinyasi
                $rentang_waktu = '-';
                if($record['tgl_tfk']){
                    $tgl_mulai = new DateTime($record['tgl_tfk']);
                    $tgl_sekarang = new DateTime();
                    $selisih = $tgl_mulai->diff($tgl_sekarang);
                    $rentang_waktu = $selisih->days . ' hari';
                }
                
                $total_keluar += $record['sisa_psd'];
                
                $status = ($record['status_barang'] == 'active') ? 'Active' : 'Inactive';
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
                <td class="text-right"><?php echo number_format($record['sisa_psd'], 0, ',', '.'); ?></td>
                <td class="text-center"><?php echo $status; ?></td>
            </tr>
        <?php
                $no++;
            }
        ?>
        </tbody>
        <tfoot>
            <tr class="footer">
                <td colspan="9" class="text-right"><strong>TOTAL KELUAR:</strong></td>
                <td class="text-right"><strong><?php echo number_format($total_keluar, 0, ',', '.'); ?></strong></td>
            </tr>
        </tfoot>
    </table>
    
    <br>
    <p style="font-size: 11px; color: #666;">
        <strong>Keterangan:</strong><br>
        - Laporan ini menampilkan riwayat pengeluaran barang konsinyasi<br>
        - Data diurutkan berdasarkan tanggal terbaru<br>
        - Total Keluar: <?php echo number_format($total_keluar, 0, ',', '.'); ?> unit
    </p>
</body>
</html>
<?php
$conn = $base->close();
?>
