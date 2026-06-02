<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
    header("Content-Type: application/force-download");
    header("Cache-Control: no-cache, must-revalidate");
    header("Expires: Laporan"); 
    header("content-disposition:attachment; filename=report_STOCKOPNAME_A.xls");
    
    require_once('../../../config/connection/connection.php');
    require_once('../../../config/connection/security.php');
    require_once('../../../config/function/data.php');
    
    $secu = new Security;
    $base = new DB;
    $data = new Data;
    
    // Ambil sistem URL dan key untuk API
    $sistem = $data->sistem('url_sis');
    $conn = $base->open();
    
    // Ambil key aplikasi untuk autentikasi API
    $source = $data->self_apl();
    $api_key = $source['key_apl'];
    
    $conn = $base->close();
    
    // Panggil API untuk mendapatkan data dari semua cabang
    $api_url = $sistem . "/api/getStockopname2.php?key=" . urlencode($api_key);
    
    // Debug: tampilkan URL API yang dipanggil
    error_log("Calling API URL: " . $api_url);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120); // Timeout 2 menit karena banyak cabang
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    
    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Debug: log response
    error_log("API Response HTTP Code: " . $http_code);
    error_log("API Response: " . substr($response, 0, 500)); // First 500 chars
    
    $stockData = [];
    $error_message = '';
    
    if ($curl_error) {
        $error_message = "Error koneksi API: " . $curl_error;
    } elseif ($http_code != 200) {
        $error_message = "HTTP Error: " . $http_code;
    } else {
        $api_result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error_message = "Error parsing JSON: " . json_last_error_msg();
        } elseif (!isset($api_result['status']) || $api_result['status'] !== 'success') {
            $error_message = "API Error: " . ($api_result['error'] ?? 'Unknown error');
        } else {
            $stockData = $api_result['data'] ?? [];
        }
    }
?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Report Stockopname</title>
    </head>

    <body>
        <table>
            <tr>
                <th colspan="11">REPORT STOCKOPNAME</th>
            </tr>
            <?php if (!empty($error_message)): ?>
            <tr>
                <td colspan="11" style="color: red;">ERROR: <?php echo $error_message; ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td colspan="11"></td>
            </tr>
        </table>
        <table border="1">
            <thead>
                <tr>
                    <th><center>NO</center></th>
                    <th><center>JENIS TRANSAKSI</center></th>
                    <th><center>TANGGAL PEMASUKAN</center></th>
                    <th><center>KODE OBAT JADI</center></th>
                    <!-- <th><center>NAMA PRODUK</center></th> -->
                    <th><center>JUMLAH</center></th>
                    <th><center>BATCH</center></th>
                    <th><center>TANGGAL EXPIRED</center></th>
                    <!-- <th><center>NO FAKTUR</center></th> -->
                    <th><center>Nama Produk</center></th>
                    <th><center>Kategori Obat</center></th>
                    <!--<th><center>KETERANGAN</center></th>-->
                    <!--<th><center>ID KOTA/KAB SUMBER</center></th>-->
                    <!--<th><center>NAMA KOTA/KAB SUMBER</center></th>-->
                    <!--<th><center>NAMA PROVINSI SUMBER</center></th>-->
                    <!--<th><center>CABANG</center></th>-->
                </tr>
            </thead>
            <tbody>
            <?php
                if (empty($stockData) && empty($error_message)) {
                    echo '<tr><td colspan="11" style="text-align: center;">Tidak ada data ditemukan</td></tr>';
                } elseif (!empty($stockData)) {
                    $nomor = 1;
                    foreach ($stockData as $row) {
            ?>
                <tr>
                    <td><center><?php echo $nomor; ?></center></td>
                    <td><center><?php echo htmlspecialchars($row['jenis_transaksi'] ?? '-'); ?></center></td>
                    <td><center><?php echo htmlspecialchars($row['tgl_pemasukan'] ?? '-'); ?></center></td>
                    <td><center><?php echo htmlspecialchars($row['kode_obat_jadi'] ?? '-'); ?></center></td>
                    <td><center><?php echo htmlspecialchars($row['jumlah'] ?? '0'); ?></center></td>
                    <td><center><?php echo htmlspecialchars($row['batch'] ?? '-'); ?></center></td>
                    <td><center><?php echo htmlspecialchars($row['tgl_expired'] ?? '-'); ?></center></td>
                    <td><center><?php echo htmlspecialchars($row['nama_produk'] ?? '-'); ?></center></td>
                    <td><center><?php echo htmlspecialchars($row['kategori_obat'] ?? '-'); ?></center></td>
                 
                </tr>
            <?php 
                        $nomor++; 
                    }
                } else {
                    echo '<tr><td colspan="11" style="text-align: center; color: red;">Error mengambil data: ' . htmlspecialchars($error_message) . '</td></tr>';
                }
            ?>
            </tbody>
        </table>
        
        <?php if (!empty($stockData)): ?>
        <table>
            <tr>
                <td colspan="11"></td>
            </tr>
        </table>
        <?php endif; ?>
    </body>
</html>
