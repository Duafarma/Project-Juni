<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
    require_once('../../../config/connection/connection.php');
    require_once('../../../config/connection/security.php');
    require_once('../../../config/function/data.php');
    require_once('../../../config/function/date.php');
    $secu    = new Security;
    $base    = new DB;
    $data    = new Data;
    $date    = new Date;
    $admin    = $secu->injection(@$_COOKIE['adminkuy']);
    $kunci    = $secu->injection(@$_COOKIE['kuncikuy']);
    $secu->validadmin($admin, $kunci);
    if($secu->validadmin($admin, $kunci)==false){ header('location:'.$data->sistem('url_sis').'/signout'); } else {
    $conn    = $base->open();
    
    // Perbaikan pada bagian awal file untuk mendukung format id_apl alfanumerik

    // Di bagian awal file, setelah mengambil parameter GET
    $kode = $secu->injection(@$_GET['key']);
    $branch_id = $secu->injection(@$_GET['branch_id']); 
    $id_apl = $secu->injection(@$_GET['id_apl']); // Parameter id_apl yang bisa alfanumerik seperti APL02

    // Gunakan id_apl jika branch_id tidak tersedia
    if (empty($branch_id) && !empty($id_apl)) {
        $branch_id = $id_apl;
    }

    // Info cabang saat ini untuk API
    $self_branch = $data->self_apl();
    $self_branch_id = $self_branch['id_apl'];
    $sistem = $data->sistem('url_sis');
    
    // Get source table from parameter or auto-detect
    $source = $secu->injection(@$_GET['source']) ?: '';
    
    // Function to determine the source table based on the invoice code
    function determineSourceTable($id_tfk, $conn) {
        // First check in transaksi_faktur
        $check = $conn->prepare("SELECT COUNT(*) FROM transaksi_faktur WHERE id_tfk = :id_tfk");
        $check->bindParam(':id_tfk', $id_tfk, PDO::PARAM_STR);
        $check->execute();
        $exists_in_tf = $check->fetchColumn() > 0;

        // If not found in transaksi_faktur, check in transaksi_faktur_pim
        if (!$exists_in_tf) {
            $check = $conn->prepare("SELECT COUNT(*) FROM transaksi_faktur_pim WHERE id_tfk = :id_tfk");
            $check->bindParam(':id_tfk', $id_tfk, PDO::PARAM_STR);
            $check->execute();
            $exists_in_tfp = $check->fetchColumn() > 0;
            
            if ($exists_in_tfp) {
                return 'pim';
            }
        } else {
            return 'standard';
        }
        
        // Default to standard if not found in either
        return 'standard';
    }
    
// Function untuk mencari faktur di cabang lain
function cariDiCabangLain($kode, $conn, $sistem, $branches, $data) {
    foreach ($branches as $branch) {
        error_log("Mencari faktur di cabang: " . $branch['nama_apl'] . " (ID: " . $branch['id_apl'] . ")");
        
        // Generate encrypt untuk autentikasi API
        $remoteTgl = date('Y-m-d');
        $remoteEncrypt = md5($remoteTgl . "#" . $branch['key_apl']);
        
        // Persiapkan parameter API - Gunakan id_apl mentah, bukan konversi ke integer
        $params = [
            'id_tfk' => $kode,
            'encrypt' => $remoteEncrypt,
            'action' => 'view',
            'detail' => 1,  // Tambahkan parameter ini untuk memastikan detail lengkap dikembalikan
            'source_branch_id' => $data->self_apl()['id_apl'], // Identifikasi cabang pemanggil
            'id_apl' => $branch['id_apl'], // Gunakan id_apl asli, bukan konversi
        ];
        
        // Bangun URL API dengan parameter yang lengkap
        $apiUrl = rtrim($branch['base_url_apl'], '/') . '/api/getFakturPajakDetail.php?' . http_build_query($params);
        error_log("Memanggil API cabang lain: $apiUrl");
        
        // Panggil API dengan CURL dan timeout yang lebih lama dan retry
        $response = false;
        $retry = 0;
        $max_retry = 2;
        
        while (!$response && $retry <= $max_retry) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20); // Timeout lebih lama
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Requested-By: MonitoringFP', 'Accept: application/json']);
            $response = curl_exec($ch);
            $curlError = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if (!$response) {
                $retry++;
                error_log("Retry $retry - Error CURL saat mengakses cabang " . $branch['nama_apl'] . ": " . $curlError);
                sleep(1); // Tunggu sebelum mencoba lagi
            }
        }
        
        // Jika masih ada error CURL setelah retry, lanjut ke cabang berikutnya
        if (!$response) {
            error_log("Setelah $max_retry kali coba, tetap gagal mengakses cabang " . $branch['nama_apl']);
            continue;
        }
        
        // Parse response JSON dengan penanganan error yang lebih baik
        $apiData = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Error parsing JSON response dari cabang " . $branch['nama_apl'] . ": " . json_last_error_msg());
            error_log("Response raw: " . substr($response, 0, 500) . "..."); // Log sebagian response untuk debugging
            continue;
        }
        
        // Cek apakah faktur ditemukan dengan pengecekan lebih detail
        if (isset($apiData['success']) && $apiData['success'] && isset($apiData['data'])) {
            error_log("Faktur ditemukan di cabang: " . $branch['nama_apl'] . " - ID: " . $branch['id_apl']);
            
            // Simpan info branch_id dengan benar
            $apiData['data']['branch_id'] = $branch['id_apl'];
            
            // Pastikan URL untuk cetak faktur sudah benar
            $fakturUrl = $sistem . '/laporan/xps/monitoringfp/monitoringfp.php?key=' . $kode . '&branch_id=' . $branch['id_apl'];
            $apiData['data']['faktur_url'] = $fakturUrl;
            
            // Kembalikan data lengkap dengan informasi yang jelas
            return [
                'found' => true,
                'branch_id' => $branch['id_apl'],
                'branch' => $branch,
                'data' => $apiData['data'],
                'details' => isset($apiData['data']['details']) ? $apiData['data']['details'] : [],
                'response_code' => $httpCode
            ];
        } else {
            $errorMsg = isset($apiData['message']) ? $apiData['message'] : 'Unknown error';
            error_log("Faktur tidak ditemukan di cabang " . $branch['nama_apl'] . " - Error: " . $errorMsg);
        }
    }
    
    // Jika tidak ditemukan di semua cabang
    return ['found' => false];
}

    // Variabel untuk menyimpan data invoice
    $view = false;
    $details = [];
    $dari_cabang_lain = false;
    $nama_cabang = '';
    
    // Always use API approach, regardless of branch
    // If branch_id is empty, use current branch
    $target_branch_id = !empty($branch_id) ? $branch_id : $self_branch_id;
    
    // Get branch information
    $stmt = $conn->prepare("SELECT base_url_apl, key_apl, nama_apl FROM aplikasi WHERE id_apl = :branch_id AND active_apl = 1 LIMIT 1");
    $stmt->bindParam(':branch_id', $target_branch_id, PDO::PARAM_STR); // Ubah ke PARAM_STR untuk mendukung alfanumerik
    $stmt->execute();
    $branch = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($branch) {
        $nama_cabang = $branch['nama_apl'];
        $dari_cabang_lain = ($target_branch_id != $self_branch_id);
        
        // Generate encrypt untuk autentikasi API
        $remoteTgl = date('Y-m-d');
        $remoteEncrypt = md5($remoteTgl . "#" . $branch['key_apl']);
        
        // Persiapkan parameter API dengan tambahan parameter untuk meningkatkan kompatibilitas
        $params = [
            'id_tfk' => $kode,
            'encrypt' => $remoteEncrypt,
            'action' => 'view',
            'format' => @$_GET['format'], // Tambahkan parameter format jika ada
            'detail' => 1, // Minta semua detail dimasukkan dalam respons
            'source_branch_id' => $self_branch_id, // Identifikasi cabang pemanggil
            'branch_id' => $branch_id, // Pastikan branch_id diteruskan
            'id_apl' => $branch_id, // Tambahkan id_apl sebagai alternatif
        ];
        
        // Bangun URL API dengan parameter lengkap
        $apiUrl = rtrim($branch['base_url_apl'], '/') . '/api/getFakturPajakDetail.php?' . http_build_query($params);
        error_log("Memanggil API: $apiUrl");
        
        // Tambahkan header khusus untuk memberikan informasi tambahan ke API tujuan
        $headers = [
            'X-Source-Branch-ID: ' . $self_branch_id,
            'X-Request-Purpose: MonitoringFP',
            'X-Format: ' . (@$_GET['format'] ?: 'json')
        ];
        
        // Panggil API dengan CURL dan timeout yang cukup
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        
        // Log informasi respons yang lebih detail untuk debugging
        error_log("Response dari API - HTTP Code: $httpCode, Content-Type: $contentType, Size: " . strlen($response));
        
        // Tambahkan logging untuk response
        error_log("Response dari API - HTTP Code: $httpCode");
        
        if ($curlError) {
            // Display connection error message
            echo '<!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <title>Connection Failed</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f8f9fa; }
                    .error-container { max-width: 600px; margin: 50px auto; padding: 30px; background: white; box-shadow: 0 2px 5px rgba(0,0,0,0.1); border-radius: 4px; }
                    h2 { color: #dc3545; margin-top: 0; }
                    .btn { display: inline-block; padding: 8px 16px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin-top: 20px; }
                </style>
            </head>
            <body>
                <div class="error-container">
                    <h2>Connection to Branch Failed</h2>
                    <p>Sorry, unable to connect to branch <strong>' . $nama_cabang . '</strong>.</p>
                    <p>Error details: ' . $curlError . '</p>
                    <p>Please try again later or contact system administrator.</p>
                    <a href="javascript:window.close();" class="btn">Close</a>
                </div>
            </body>
            </html>';
            exit;
        }
        
        // Parse JSON response with better error handling
        $apiData = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Error parsing JSON response: " . json_last_error_msg());
            error_log("Response: " . substr($response, 0, 1000));
            
            // Display format error message
            echo '<!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <title>Invalid Response Format</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f8f9fa; }
                    .error-container { max-width: 600px; margin: 50px auto; padding: 30px; background: white; box-shadow: 0 2px 5px rgba(0,0,0,0.1); border-radius: 4px; }
                    h2 { color: #dc3545; margin-top: 0; }
                    .btn { display: inline-block; padding: 8px 16px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin-top: 20px; }
                </style>
            </head>
            <body>
                <div class="error-container">
                    <h2>Invalid Response Format</h2>
                    <p>Sorry, response from branch <strong>' . $nama_cabang . '</strong> is not in the correct format.</p>
                    <p>Please try again later or contact system administrator.</p>
                    <a href="javascript:window.close();" class="btn">Close</a>
                </div>
            </body>
            </html>';
            exit;
        }
        
        // Check if invoice data was found
        if (isset($apiData['success']) && $apiData['success'] && isset($apiData['data'])) {
            $view = $apiData['data'];
            $source = isset($apiData['data']['jenis']) && strtoupper($apiData['data']['jenis']) === 'PIM' ? 'pim' : 'standard';
            $jenis = ($source == 'pim') ? 'PIM' : 'Cendo & DPE';
            
            // Set invoice details if available
            if (isset($apiData['data']['details']) && is_array($apiData['data']['details'])) {
                $details = $apiData['data']['details'];
            }
            
            // Log success
            error_log("Successfully retrieved invoice data from branch: $nama_cabang");
        } else {
            $errorMessage = isset($apiData['message']) ? $apiData['message'] : 'Invoice not found in branch ' . $nama_cabang;
            error_log("API Error: $errorMessage");
            
            // If invoice not found and this is current branch, try searching other branches
            if ($target_branch_id == $self_branch_id) {
                error_log("Invoice not found in current branch, searching other branches...");
                
                // Get all active branches
                $stmt = $conn->prepare("SELECT id_apl, base_url_apl, key_apl, nama_apl FROM aplikasi WHERE active_apl = 1 AND id_apl != :self_id");
                $stmt->bindParam(':self_id', $self_branch_id, PDO::PARAM_INT);
                $stmt->execute();
                $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Use function to search in other branches
                $result = cariDiCabangLain($kode, $conn, $sistem, $branches, $data);
                
                if ($result['found']) {
                    $dari_cabang_lain = true;
                    $nama_cabang = $result['branch']['nama_apl'];
                    $branch_id = $result['branch']['id_apl']; 
                    $view = $result['data'];
                    $source = isset($result['data']['jenis']) && strtoupper($result['data']['jenis']) === 'PIM' ? 'pim' : 'standard';
                    $jenis = ($source == 'pim') ? 'PIM' : 'Cendo & DPE';
                    $details = $result['details'];
                } else {
                    // Display invoice not found error message
                    echo '<!DOCTYPE html>
                    <html>
                    <head>
                        <meta charset="utf-8">
                        <meta name="viewport" content="width=device-width, initial-scale=1">
                        <title>Invoice Not Found</title>
                        <style>
                            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f8f9fa; }
                            .error-container { max-width: 600px; margin: 50px auto; padding: 30px; background: white; box-shadow: 0 2px 5px rgba(0,0,0,0.1); border-radius: 4px; }
                            h2 { color: #dc3545; margin-top: 0; }
                            .btn { display: inline-block; padding: 8px 16px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin-top: 20px; }
                        </style>
                    </head>
                    <body>
                        <div class="error-container">
                            <h2>Invoice Not Found</h2>
                            <p>Sorry, invoice with ID <strong>' . $kode . '</strong> could not be found in any branch.</p>
                            <p>This might be because:</p>
                            <ul>
                                <li>Invoice ID is invalid</li>
                                <li>Invoice has been deleted from the system</li>
                                <li>Some branches are currently unavailable</li>
                            </ul>
                            <a href="javascript:window.close();" class="btn">Close</a>
                        </div>
                    </body>
                    </html>';
                    exit;
                }
            } else {
                // Display invoice not found in specific branch error message
                echo '<!DOCTYPE html>
                <html>
                <head>
                    <meta charset="utf-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1">
                    <title>Invoice Not Found</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f8f9fa; }
                        .error-container { max-width: 600px; margin: 50px auto; padding: 30px; background: white; box-shadow: 0 2px 5px rgba(0,0,0,0.1); border-radius: 4px; }
                        h2 { color: #dc3545; margin-top: 0; }
                        .btn { display: inline-block; padding: 8px 16px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin-top: 20px; }
                    </style>
                </head>
                <body>
                    <div class="error-container">
                        <h2>Invoice Not Found</h2>
                        <p>Sorry, invoice with ID <strong>' . $kode . '</strong> was not found in branch <strong>' . $nama_cabang . '</strong>.</p>
                        <p>Message: ' . $errorMessage . '</p>
                        <a href="javascript:window.close();" class="btn">Close</a>
                    </div>
                </body>
                </html>';
                exit;
            }
        }
    } else {
        // Display branch not found error
        echo '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Branch Not Found</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f8f9fa; }
                .error-container { max-width: 600px; margin: 50px auto; padding: 30px; background: white; box-shadow: 0 2px 5px rgba(0,0,0,0.1); border-radius: 4px; }
                h2 { color: #dc3545; margin-top: 0; }
                .btn { display: inline-block; padding: 8px 16px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class="error-container">
                <h2>Branch Not Found</h2>
                <p>Sorry, branch with ID <strong>' . $target_branch_id . '</strong> was not found or is not active.</p>
                <a href="javascript:window.close();" class="btn">Close</a>
            </div>
        </body>
        </html>';
        exit;
    }
    
    // Continue with rest of code that uses $view, $details, etc.
    
    // ... existing code ...
?>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>FAKTUR_<?php echo($view['kode_tfk']); ?><?php echo $dari_cabang_lain ? "(Cabang $nama_cabang)" : ""; ?></title>
    <link href="<?php echo($data->sistem('url_sis')."/config/css/laporan.css"); ?>" rel="stylesheet">
    <style type="text/css" media="print">
        @page { size: portrait; }
    </style>
</head>

<body>
    <div>
        <div style="float:left; font-size:60px;"><img src="<?php echo("../../../berkas/sistem/".$data->sistem('logo_sis')); ?>" height="70" width="100" /></div>
        <div align="right" style="font-size:10px;"><?php echo($data->sistem('pt_sis')); ?></div>
        <div align="right" style="font-size:10px;"><?php echo substr($data->sistem('alamat_sis'),0,69); ?></div>
        <div align="right" style="font-size:10px;"><?php echo substr($data->sistem('alamat_sis'),70,120); ?></div>
        <div align="right" style="font-size:10px;">PHONE <?php echo($data->sistem('telp_sis')); ?></div>
        <div align="right" style="font-size:10px;">NPWP : <?php echo($data->sistem('npwp_sis')); ?></div>
        <div align="right" style="font-size:10px;">IZIN PBF : <?php echo($data->sistem('pbf_sis')); ?></div>
        <div align="right" style="font-size:10px;">SIPA APJ : <?php echo($data->sistem('sipa_sis')); ?></div>
        <div align="right" style="font-size:10px;">CDOB : <?php echo($data->sistem('cdob_sis')); ?></div>
        <div align="right" style="font-size:10px;">CDOB CCP : CDOB2777/S/1-1844/01/2024</div>
        <!-- <div align="right" style="font-size:10px;">JENIS FAKTUR : <?php echo $jenis; ?></div>
        <?php if ($dari_cabang_lain): ?>
                <div align="right" style="font-size:10px;">CABANG : <?php echo $nama_cabang; ?></div>
        <?php endif; ?> -->
    </div>
    <br />
    <table width="100%" style="font-family:Calibri Light, Helvetica, sans-serif; font-size:12px;">
        <tr>
            <td width="50%"></td>
            <td width="15%">Tanggal</td>
            <td width="3%"><center>:</center></td>
            <td width="32%"><?php echo($date->tgl_indo($view['tgl_tfk'])); ?></td>
        </tr>
        <tr>
            <td><div align="left">Kepada Yth,</div></td>
            <td>Faktur No.</td>
            <td><center>:</center></td>
            <td><?php echo($view['kode_tfk']); ?></td>
        </tr>
    </table>
    <table class="tabelinfo " style="font-family:Calibri Light, Helvetica, sans-serif; font-size:12px;">
        <tr>
            <td width="35%">NAMA PELANGGAN</td>
            <td width="3%"><center>:</center></td>
            <td width="62%"><?php echo($view['nama_out']); ?></td>
        </tr>
        <tr>
            <td width="35%">NAMA OUTLET</td>
            <td width="3%"><center>:</center></td>
            <td width="62%"><?php echo($view['resmi_out']); ?></td>
        </tr>
        <tr>
            <td>ALAMAT KIRIM</td>
            <td><center>:</center></td>
            <td><?php echo($view['pengiriman_ola']); ?></td>
        </tr>
        <tr>
            <td>NPWP</td>
            <td><center>:</center></td>
            <td><?php echo($view['npwp_out']); ?></td>
        </tr>
        <tr>
            <td>NO. PO</td>
            <td><center>:</center></td>
            <td><?php echo($view['po_tfk']); ?></td>
        </tr>
    </table>
    <p></p>
    <table class="tabel">
        <thead>
            <tr>
                <th rowspan="2">NO</th>
                <th rowspan="2">NAMA BARANG</th>
                <th colspan="2">SEDIAAN</th>
                <th rowspan="2">NO. BATCH</th>
                <th rowspan="2">EXP. DATE</th>
                <th rowspan="2">KUANTITAS</th>
                <th rowspan="2">HARGA</th>
                <th rowspan="2">DISKON</th>
                <th rowspan="2">TOTAL</th>
            </tr>
            <tr>
                <th>SEDIAAN</th>
                <th>UKURAN</th>
            </tr>
        </thead>
        <tbody>
            <?php
                $subtot = 0;
                $diskon = 0;
                $total = 0;
                $stotal = 0;
                $nomor = 1;
                
                if ($dari_cabang_lain && !empty($details)) {
                    // Gunakan detail dari response API
                    foreach($details as $hasil) {
                        $subtot = isset($hasil['subtotal']) ? $hasil['subtotal'] : ($hasil['jumlah_tfd'] * $hasil['harga_tfd']);
                        $diskon = isset($hasil['diskon_nilai']) ? $hasil['diskon_nilai'] : (($subtot * $hasil['diskon_tfd']) / 100);
                        $total = isset($hasil['total']) ? $hasil['total'] : ($subtot - $diskon);
                        $stotal += $total;
            ?>
                <tr>
                    <td><center><?php echo($nomor); ?></center></td>
                    <td><?php echo($hasil['nama_pro']); ?></td>
                    <td><?php echo($hasil['nama_kpr']); ?></td>
                    <td><?php echo("$hasil[berat_pro] $hasil[nama_spr]"); ?></td>
                    <td><center><?php echo($hasil['no_bcode']); ?></center></td>
                    <td><center><?php echo(substr($hasil['tgl_expired'], 0, 7)); ?></center></td>
                    <td><div align="right"><?php echo($data->angka($hasil['jumlah_tfd'])); ?></div></td>
                    <td><div align="right"><?php echo($data->angka($hasil['harga_tfd'])); ?></div></td>
                    <td><center><?php echo($hasil['diskon_tfd']); ?>%</center></td>
                    <td><div align="right"><?php echo($data->angka($total)); ?></div></td>
                </tr>
            <?php
                        $nomor++;
                    }
                } else {
                    // Use appropriate table for invoice details
                    $detail_table = ($source == 'pim') ? 'transaksi_fakturdetail_pim' : 'transaksi_fakturdetail';
                    
                    $master = $conn->prepare("SELECT A.jumlah_tfd, A.harga_tfd, A.diskon_tfd, 
                                           B.no_bcode, B.tgl_expired, C.kode_pro, C.nama_pro, C.berat_pro, 
                                           D.nama_kpr, E.nama_spr 
                                         FROM $detail_table AS A 
                                         LEFT JOIN produk_stokdetail AS B ON A.id_psd=B.id_psd 
                                         LEFT JOIN produk AS C ON B.id_pro=C.id_pro 
                                         LEFT JOIN kategori_produk AS D ON C.id_kpr=D.id_kpr 
                                         LEFT JOIN satuan_produk AS E ON C.id_spr=E.id_spr 
                                         WHERE A.id_tfk=:kode");
                    $master->bindParam(':kode', $kode, PDO::PARAM_STR);
                    $master->execute();
                    
                    while($hasil = $master->fetch(PDO::FETCH_ASSOC)){
                        $subtot = $hasil['jumlah_tfd'] * $hasil['harga_tfd'];
                        $diskon = ($subtot * $hasil['diskon_tfd']) / 100;
                        $total = $subtot - $diskon;
                        $stotal += $total;
            ?>
                <tr>
                    <td><center><?php echo($nomor); ?></center></td>
                    <td><?php echo($hasil['nama_pro']); ?></td>
                    <td><?php echo($hasil['nama_kpr']); ?></td>
                    <td><?php echo("$hasil[berat_pro] $hasil[nama_spr]"); ?></td>
                    <td><center><?php echo($hasil['no_bcode']); ?></center></td>
                    <td><center><?php echo(substr($hasil['tgl_expired'], 0, 7)); ?></center></td>
                    <td><div align="right"><?php echo($data->angka($hasil['jumlah_tfd'])); ?></div></td>
                    <td><div align="right"><?php echo($data->angka($hasil['harga_tfd'])); ?></div></td>
                    <td><center><?php echo($hasil['diskon_tfd']); ?>%</center></td>
                    <td><div align="right"><?php echo($data->angka($total)); ?></div></td>
                </tr>
            <?php
                        $nomor++;
                    }
                }
                
                // Gunakan PPN dan grand total dari view jika tersedia, atau hitung sendiri
                if (isset($view['calculated_subtotal']) && isset($view['calculated_ppn']) && isset($view['calculated_grandtotal'])) {
                    $stotal = $view['calculated_subtotal'];
                    $ppn = $view['calculated_ppn'];
                    $gtotal = $view['calculated_grandtotal'];
                } else {
                    $ppn = ($stotal * 11) / 100;
                    $gtotal = round(($stotal + $ppn), 0);
                }
            ?>
        </tbody>
    </table>
    <br />
    <div style="width:100%;">
        <div style="width:45%; display:inline-block;" align="left">
            <div style="margin-bottom:50px;"></div>
            <table class="tabel" style="font-size:10px;">
                <thead>
                    <tr>
                        <th width="60%"><div align="left"><?php echo($data->sistem('pt_sis')); ?></div></th>
                        <th width="40%"><div align="left">TTD dan CAP</div></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td height="60" style="vertical-align:bottom;">
                            <div align="left">Nama : <?php echo($data->sistem('apoteker_sis')); ?></div>
                            <div align="left">Jabatan : Apoteker</div>
                        </td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
            <br />
            <table class="tabel" style="font-size:10px;">
                <thead>
                    <tr>
                        <th width="60%"><div align="left">Penerima</div></th>
                        <th width="40%"><div align="left">TTD dan CAP</div></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td height="60">
                            <div align="left" style="margin-bottom:30px; font-weight:bold;"><?php echo($view['nama_out']); ?></div>
                            <div align="left">Nama :</div>
                            <div align="left">Jabatan :</div>
                        </td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div style="width:45%; float:right;">
            <div align="right">
                <table width="80%" style="font-family:Calibri Light, Helvetica, sans-serif; font-size:12px;">
                    <tr>
                        <td><div align="left">Total 1</div></td>
                        <td></td>
                        <td><div align="right"><span style="float:left;">Rp.</span><?php echo($data->angka($stotal)); ?></div></td>
                    </tr>
                    <tr>
                        <td><div align="left">Potongan</div></td>
                        <td></td>
                        <td><div align="right"><span style="float:left;">Rp.</span><?php echo($data->angka(0)); ?></div></td>
                    </tr>
                    <tr>
                        <td><div align="left">PPN <span style="float:right;">11%</span></div></td>
                        <td></td>
                        <td><div align="right"><span style="float:left;">Rp.</span><?php echo($data->angka($ppn)); ?></div></td>
                    </tr>
                    <tr>
                        <td colspan="3"><div style="background:#666666; width:100%; height:1px;"></div></td>
                    </tr>
                    <tr>
                        <td><div align="left"><b>Total Faktur</b></div></td>
                        <td></td>
                        <td><div align="right"><span style="float:left; font-weight:bold;">Rp.</span><b><?php echo($data->angka($view['total_tfk'])); ?></b></div></td>
                    </tr>
                </table>
            </div>
            <div style="min-height:30px; height:auto; border:solid 1px #666666; text-align:center; font-weight:bold; margin-top:10px; padding:5px; font-size:12px;">Terbilang : # <?php echo($data->terbilang($view['total_tfk'])); ?> Rupiah #</div>
            <div style="height:auto; border:solid 1px #666666; text-align:left; margin-top:10px; padding:5px; font-size:10px;">
                <div style="font-weight:bold;">JATUH TEMPO PEMBAYARAN : <?php echo($date->tgl_indo($view['tgl_limit'])." ($view[jarak] Hari Dari Obat Diterima)"); ?></div>
                <div style="margin-top:5px;">Pembayaran dapat dilakukan dengan cara melakukan transfer ke :</div>
                <div style="margin-left:15px; margin-top:5px;">BANK <?php echo($data->sistem('bank_sis')); ?></div>
                <div style="margin-left:15px; margin-top:5px;"><?php echo($data->sistem('norek_sis')); ?></div>
                <div style="margin-left:15px; margin-top:5px;">An. <?php echo($data->sistem('anam_sis')); ?></div>
            </div>
        </div>
    </div>

    <script type="text/javascript">window.print();</script>
</body>
<?php
// Tambahkan sebelum penutup </body>
?>


<script>
function exportToXLS() {
    // Ambil parameter faktur
    const kode = '<?php echo $kode; ?>';
    const id_apl = '<?php echo $branch_id; ?>'; // Gunakan id_apl/branch_id sebagai string
    
    // Generate URL untuk export XLS
    let exportUrl = '<?php echo $sistem; ?>/api/getFakturPajakDetail.php?';
    exportUrl += 'id_tfk=' + kode;
    exportUrl += '&id_apl=' + id_apl; // Gunakan id_apl sebagai parameter utama
    
    <?php if ($dari_cabang_lain && isset($branch['key_apl'])): ?>
    // Gunakan key_apl dari cabang yang dipilih untuk autentikasi
    exportUrl += '&encrypt=<?php echo md5(date('Y-m-d')."#".$branch['key_apl']); ?>';
    <?php else: ?>
    // Gunakan key_apl dari cabang saat ini untuk autentikasi
    exportUrl += '&encrypt=<?php echo md5(date('Y-m-d')."#".$self_branch['key_apl']); ?>';
    <?php endif; ?>
    
    exportUrl += '&action=excel';
    exportUrl += '&format=xls';
    exportUrl += '&t=' + new Date().getTime(); // Tambah timestamp untuk mencegah caching
    
    // Log for debugging
    console.log("Exporting Excel from URL: " + exportUrl);
    
    // Buka URL di tab baru
    window.open(exportUrl, '_blank');
}
</script>
<?php
    }
?>
</html>