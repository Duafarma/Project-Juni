<?php
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Access-Control-Allow-Origin: *');
    header("Content-type: application/json; charset=utf-8");
    echo json_encode(["success" => false, "message" => "Metode tidak diizinkan"]);
    exit;
}

require_once('../config/connection/connection.php');
require_once('../config/connection/security.php');
require_once('../config/function/data.php');

$secu = new Security;
$base = new DB;
$data = new Data;
$conn = $base->open();

// Perbaikan parameter dan query untuk mendukung id_apl alfanumerik

// Validasi parameter dan enkripsi
$id_tfk = $secu->injection(@$_GET['id_tfk']);
$encrypt = $secu->injection(@$_GET['encrypt']);
$action = $secu->injection(@$_GET['action']);
$branch_id = $secu->injection(@$_GET['branch_id']); 
$id_apl = $secu->injection(@$_GET['id_apl']); // Parameter id_apl yang bisa alfanumerik

// Gunakan id_apl jika branch_id tidak tersedia
if (empty($branch_id) && !empty($id_apl)) {
    $branch_id = $id_apl;
}

$format = $secu->injection(@$_GET['format']); // 'xls' untuk export Excel

// Log parameters untuk debug
error_log("Permintaan API - id_tfk: $id_tfk, branch_id: $branch_id, action: $action");

// Validasi enkripsi
$tgl = date('Y-m-d');
$source = $data->self_apl();
$sourceKey = $source['key_apl'];
$self_branch_id = $source['id_apl'];
$sistem = $data->sistem('url_sis'); // Dapatkan URL sistem untuk link faktur

if (md5($tgl . "#" . $sourceKey) != $encrypt) {
    http_response_code(401);
    header('Access-Control-Allow-Origin: *');
    header("Content-type: application/json; charset=utf-8");
    echo json_encode([
        "success" => false,
        "message" => "Akses tidak diizinkan - kunci enkripsi tidak valid",
        "data" => null
    ]);
    exit;
}

// Cek apakah ini permintaan dari cabang lain atau untuk cabang lain
$isRemoteBranch = !empty($branch_id) && $branch_id != $self_branch_id;

// Jika permintaan untuk cabang lain, teruskan ke API cabang tersebut
if ($isRemoteBranch) {
    // Ambil informasi cabang tujuan - gunakan PDO::PARAM_STR
    $stmt = $conn->prepare("SELECT base_url_apl, key_apl, nama_apl FROM aplikasi WHERE id_apl = :branch_id AND active_apl = 1 LIMIT 1");
    $stmt->bindParam(':branch_id', $branch_id, PDO::PARAM_STR); // Gunakan PARAM_STR untuk id_apl alfanumerik
    $stmt->execute();
    $branch = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$branch) {
        http_response_code(404);
        header('Access-Control-Allow-Origin: *');
        header("Content-type: application/json; charset=utf-8");
        echo json_encode([
            "success" => false,
            "message" => "Cabang tidak ditemukan atau tidak aktif (ID: $branch_id)",
            "data" => null,
            "error_code" => "BRANCH_NOT_FOUND"
        ]);
        exit;
    }
    
    // Log untuk tracking permintaan antar cabang
    error_log("Permintaan faktur dari cabang: {$self_branch_id} ke cabang: {$branch_id} ({$branch['nama_apl']}) - ID Faktur: {$id_tfk}");
    
    // Generate encrypt untuk cabang tujuan
    $remoteTgl = date('Y-m-d');
    $remoteEncrypt = md5($remoteTgl . "#" . $branch['key_apl']);
    
    // Build API URL untuk cabang tujuan dengan parameter yang ditingkatkan
    $params = [
        'id_tfk' => $id_tfk,
        'encrypt' => $remoteEncrypt,
        'action' => $action,
        'source_branch_id' => $self_branch_id, // Tambahkan informasi cabang pengirim
        'format' => $format,
        'detail' => 1 // Minta semua detail
    ];
    
    $apiUrl = rtrim($branch['base_url_apl'], '/') . '/api/getFakturPajakDetail.php?' . http_build_query($params);
    
    // Log URL API yang dipanggil
    error_log("Memanggil API cabang lain: " . $apiUrl);
    
    // Set custom headers untuk informasi tambahan
    $headers = [
        'X-Source-Branch-ID: ' . $self_branch_id,
        'X-Source-Branch-Name: ' . ($source['nama_apl'] ?? 'Unknown'),
        'X-Request-Purpose: getFakturPajakDetail',
        'Accept: application/json'
    ];
    
    // Panggil API cabang lain dengan curl dan header khusus
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Handle error curl
    if ($curlError) {
        error_log("Error curl saat mengakses cabang $branch_id: $curlError");
        http_response_code(500);
        header('Access-Control-Allow-Origin: *');
        header("Content-type: application/json; charset=utf-8");
        echo json_encode([
            "success" => false,
            "message" => "Gagal terhubung ke cabang " . $branch['nama_apl'] . " - " . $curlError,
            "data" => null,
            "error_type" => "connection_failed"
        ]);
        exit;
    }
    
    // Validasi response dari cabang
    $responseData = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Response cabang tidak valid: " . substr($response, 0, 1000));
        http_response_code(500);
        header('Access-Control-Allow-Origin: *');
        header("Content-type: application/json; charset=utf-8");
        echo json_encode([
            "success" => false,
            "message" => "Format response dari cabang " . $branch['nama_apl'] . " tidak valid",
            "data" => null,
            "error_type" => "invalid_response"
        ]);
        exit;
    }
    
    // Tambahkan informasi cabang ke response jika berhasil
    if ($responseData['success']) {
        // Pastikan 'data' selalu ada dalam respons
        if (!isset($responseData['data'])) {
            $responseData['data'] = [];
        }
        
        // Tambahkan informasi cabang
        $responseData['data']['nama_cabang'] = $branch['nama_apl'];
        $responseData['data']['id_cabang'] = $branch_id;
        
        // Tambahkan informasi debug yang akan membantu troubleshooting
        $responseData['debug_info'] = [
            "source_branch" => [
                "id" => $self_branch_id,
                "name" => $source['nama_apl'] ?? 'Unknown'
            ],
            "target_branch" => [
                "id" => $branch_id,
                "name" => $branch['nama_apl']
            ],
            "request_parameters" => [
                "id_tfk" => $id_tfk,
                "action" => $action,
                "format" => $format
            ],
            "api_endpoint" => $apiUrl,
            "response_code" => $httpCode,
            "timestamp" => date('Y-m-d H:i:s')
        ];
        
        // Update URL ke domain yang benar untuk akses dari cabang saat ini
        if (isset($responseData['data']['details']) && is_array($responseData['data']['details'])) {
            // Data ada dan sudah berformat array
            $fakturUrl = $sistem . '/laporan/xps/monitoringfp/monitoringfp.php?key=' . $id_tfk . '&id_apl=' . $branch_id;
            $responseData['data']['faktur_url'] = $fakturUrl;
            
            // Tambahkan informasi jumlah item detail
            $responseData['data']['jumlah_item'] = count($responseData['data']['details']);
        } else {
            // Jika tidak ada details, berikan peringatan
            error_log("Warning: Faktur ditemukan di cabang {$branch['nama_apl']} tetapi tidak ada detail produk");
            $responseData['data']['warning'] = "Faktur ditemukan tetapi tidak memiliki detail produk";
            $responseData['data']['jumlah_item'] = 0;
        }
    }
    
    // Log tambahan untuk troubleshooting
    error_log("Hasil permintaan faktur dari cabang {$branch_id}: " . ($responseData['success'] ? "SUCCESS" : "FAILED"));
    
    // Jika respons berhasil dan memiliki data, tetapi tidak memiliki id_tfk, tambahkan id_tfk dari permintaan
    if ($responseData['success'] && isset($responseData['data']) && !isset($responseData['data']['id_tfk'])) {
        $responseData['data']['id_tfk'] = $id_tfk;
        error_log("ID TFK ditambahkan dari parameter permintaan: $id_tfk");
    }
    
    // Return response dari cabang
    header('Access-Control-Allow-Origin: *');
    header("Content-type: application/json; charset=utf-8");
    echo json_encode($responseData);
    exit;
}

// Proses untuk data lokal (cabang saat ini)

// Tentukan tabel berdasarkan ID faktur
$source = 'standard';
$check = $conn->prepare("SELECT COUNT(*) FROM transaksi_faktur WHERE id_tfk = :id_tfk");
$check->bindParam(':id_tfk', $id_tfk, PDO::PARAM_STR);
$check->execute();
if ($check->fetchColumn() == 0) {
    $check = $conn->prepare("SELECT COUNT(*) FROM transaksi_faktur_pim WHERE id_tfk = :id_tfk");
    $check->bindParam(':id_tfk', $id_tfk, PDO::PARAM_STR);
    $check->execute();
    if ($check->fetchColumn() > 0) {
        $source = 'pim';
    } else {
        // Faktur tidak ditemukan di cabang ini, periksa di cabang lain
        if (empty($branch_id)) {
            // Ambil semua cabang aktif
            $stmt = $conn->prepare("SELECT id_apl, base_url_apl, key_apl, nama_apl FROM aplikasi WHERE active_apl = 1 AND id_apl != :self_id");
            $stmt->bindParam(':self_id', $self_branch_id, PDO::PARAM_INT);
            $stmt->execute();
            $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Log jumlah cabang yang akan dicek
            error_log("Memeriksa faktur di " . count($branches) . " cabang lain");
            
            // Cek di setiap cabang
            foreach ($branches as $branch) {
                // Skip jika tidak ada URL API
                if (empty($branch['base_url_apl'])) {
                    continue;
                }

                // Generate encrypt untuk cabang
                $remoteTgl = date('Y-m-d');
                $remoteEncrypt = md5($remoteTgl . "#" . $branch['key_apl']);
                
                // Build URL API
                $params = [
                    'id_tfk' => $id_tfk,
                    'encrypt' => $remoteEncrypt,
                    'action' => $action
                ];
                
                $apiUrl = rtrim($branch['base_url_apl'], '/') . '/api/getFakturPajakDetail.php?' . http_build_query($params);
                
                // Log URL yang dipanggil
                error_log("Mencari faktur di cabang " . $branch['nama_apl'] . ": " . $apiUrl);
                
                // Panggil API cabang dengan error handling yang lebih baik
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $apiUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10); 
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                $response = curl_exec($ch);
                $curlError = curl_error($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                // Jika gagal terhubung, lanjutkan ke cabang berikutnya
                if ($curlError) {
                    error_log("Gagal terhubung ke cabang " . $branch['nama_apl'] . ": " . $curlError);
                    continue;
                }
                
                // Coba parse respons JSON
                $responseData = json_decode($response, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log("Respons cabang " . $branch['nama_apl'] . " bukan JSON valid");
                    continue;
                }
                
                if (isset($responseData['success']) && $responseData['success'] && !empty($responseData['data'])) {
                    // Faktur ditemukan di cabang ini
                    error_log("Faktur ditemukan di cabang: " . $branch['nama_apl']);
                    
                    // Tambahkan informasi cabang
                    $responseData['data']['nama_cabang'] = $branch['nama_apl'];
                    $responseData['data']['id_cabang'] = $branch['id_apl'];
                    
                    // Update URL untuk akses dari cabang saat ini
                    $fakturUrl = $sistem . '/laporan/xps/monitoringfp/monitoringfp.php?key=' . $id_tfk . '&id_apl=' . $branch['id_apl'];
                    $responseData['data']['faktur_url'] = $fakturUrl;
                    
                    // Return response dari cabang
                    header('Access-Control-Allow-Origin: *');
                    header("Content-type: application/json; charset=utf-8");
                    echo json_encode($responseData);
                    exit;
                }
            }
            
            // Jika sudah mencari di semua cabang dan tidak ditemukan
            http_response_code(404);
            header('Access-Control-Allow-Origin: *');
            header("Content-type: application/json; charset=utf-8");
            echo json_encode([
                "success" => false,
                "message" => "Faktur dengan ID " . $id_tfk . " tidak ditemukan di semua cabang",
                "data" => null,
                "error_type" => "invoice_not_found"
            ]);
            exit;
        }
    }
}

// Pilih tabel yang sesuai
$table = ($source == 'pim') ? 'transaksi_faktur_pim' : 'transaksi_faktur';

// Ambil data faktur
$read = $conn->prepare("SELECT 
                        A.kode_tfk, A.tgl_tfk, A.total_tfk, A.tgl_limit, A.po_tfk,
                        TIMESTAMPDIFF(DAY, A.tgl_tfk, A.tgl_limit) AS jarak,
                        B.resmi_out, B.nama_out, B.npwp_out, C.pengiriman_ola,
                        A.ppn_tfk, A.subtot_tfk 
                      FROM $table AS A 
                      LEFT JOIN outlet AS B ON A.id_out=B.id_out 
                      LEFT JOIN outlet_alamat AS C ON B.id_out=C.id_out 
                      WHERE A.id_tfk=:kode");
$read->bindParam(':kode', $id_tfk, PDO::PARAM_STR);
$read->execute();
$view = $read->fetch(PDO::FETCH_ASSOC);

if ($view === false) {
    http_response_code(404);
    header('Access-Control-Allow-Origin: *');
    header("Content-type: application/json; charset=utf-8");
    echo json_encode([
        "success" => false,
        "message" => "Faktur tidak ditemukan di cabang ini",
        "data" => null,
        "error_type" => "invoice_not_found"
    ]);
    exit;
}

// Ambil detail faktur
$detail_table = ($source == 'pim') ? 'transaksi_fakturdetail_pim' : 'transaksi_fakturdetail';
$details = [];

$master = $conn->prepare("SELECT A.jumlah_tfd, A.harga_tfd, A.diskon_tfd, 
                       B.no_bcode, B.tgl_expired, C.kode_pro, C.nama_pro, C.berat_pro, 
                       D.nama_kpr, E.nama_spr 
                     FROM $detail_table AS A 
                     LEFT JOIN produk_stokdetail AS B ON A.id_psd=B.id_psd 
                     LEFT JOIN produk AS C ON B.id_pro=C.id_pro 
                     LEFT JOIN kategori_produk AS D ON C.id_kpr=D.id_kpr 
                     LEFT JOIN satuan_produk AS E ON C.id_spr=E.id_spr 
                     WHERE A.id_tfk=:kode");
$master->bindParam(':kode', $id_tfk, PDO::PARAM_STR);
$master->execute();

while($hasil = $master->fetch(PDO::FETCH_ASSOC)) {
    $subtot = $hasil['jumlah_tfd'] * $hasil['harga_tfd'];
    $diskon = ($subtot * $hasil['diskon_tfd']) / 100;
    $total = $subtot - $diskon;
    
    // Tambahkan perhitungan ke hasil
    $hasil['subtotal'] = $subtot;
    $hasil['diskon_nilai'] = $diskon;
    $hasil['total'] = $total;
    
    $details[] = $hasil;
}

// Get total count of items
$itemCount = count($details);

// Calculate total & subtotal values
$subtotal = 0;
foreach ($details as $item) {
    $subtotal += $item['total'];
}
$ppn = ($subtotal * 11) / 100;
$grandTotal = round(($subtotal + $ppn), 0);

// Tambahkan detail ke data faktur
$view['details'] = $details;
$view['jenis'] = ($source == 'pim') ? 'PIM' : 'Cendo & DPE';
$view['item_count'] = $itemCount;
$view['calculated_subtotal'] = $subtotal;
$view['calculated_ppn'] = $ppn;
$view['calculated_grandtotal'] = $grandTotal;

// Tambahkan informasi cabang saat ini
$cabangData = $data->self_apl();
$view['nama_cabang'] = $cabangData['nama_apl'];
$view['id_cabang'] = $self_branch_id;

// Tambahkan URL untuk cetak faktur
$view['faktur_url'] = $sistem . '/laporan/xps/monitoringfp/monitoringfp.php?key=' . $id_tfk . '&id_apl=' . $branch_id;

// Jika format XLS diminta
if ($format === 'xls' && $action === 'excel') {
    // Set header untuk download Excel
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="Faktur_Pajak_' . $view['kode_tfk'] . '.xls"');
    header('Cache-Control: max-age=0');
    
    // Generate HTML untuk Excel
    echo '<!DOCTYPE html>';
    echo '<html>';
    echo '<head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
    echo '<title>Faktur Pajak ' . $view['kode_tfk'] . '</title>';
    echo '<style>';
    echo 'table { border-collapse: collapse; width: 100%; }';
    echo 'th, td { border: 1px solid #000; padding: 5px; }';
    echo 'th { background-color: #f2f2f2; }';
    echo '.center { text-align: center; }';
    echo '.right { text-align: right; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    // Header info
    echo '<h2>Faktur Pajak: ' . $view['kode_tfk'] . '</h2>';
    echo '<p>Cabang: ' . $view['nama_cabang'] . '</p>';
    echo '<p>Tanggal: ' . $view['tgl_tfk'] . '</p>';
    echo '<p>Customer: ' . $view['nama_out'] . '</p>';
    
    // Detail produk
    echo '<h3>Detail Produk</h3>';
    echo '<table>';
    echo '<tr>';
    echo '<th>No</th>';
    echo '<th>Nama Produk</th>';
    echo '<th>Batch</th>';
    echo '<th>Expired</th>';
    echo '<th>Qty</th>';
    echo '<th>Harga</th>';
    echo '<th>Diskon</th>';
    echo '<th>Total</th>';
    echo '</tr>';
    
    $no = 1;
    foreach ($details as $item) {
        echo '<tr>';
        echo '<td class="center">' . $no++ . '</td>';
        echo '<td>' . $item['nama_pro'] . '</td>';
        echo '<td class="center">' . $item['no_bcode'] . '</td>';
        echo '<td class="center">' . substr($item['tgl_expired'], 0, 7) . '</td>';
        echo '<td class="right">' . number_format($item['jumlah_tfd']) . '</td>';
        echo '<td class="right">' . number_format($item['harga_tfd']) . '</td>';
        echo '<td class="center">' . $item['diskon_tfd'] . '%</td>';
        echo '<td class="right">' . number_format($item['total']) . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    
    // Summary
    echo '<h3>Ringkasan</h3>';
    echo '<table style="width: 300px; float: right;">';
    echo '<tr><td>Subtotal</td><td class="right">Rp. ' . number_format($view['calculated_subtotal']) . '</td></tr>';
    echo '<tr><td>PPN 11%</td><td class="right">Rp. ' . number_format($view['calculated_ppn']) . '</td></tr>';
    echo '<tr style="font-weight: bold;"><td>Total</td><td class="right">Rp. ' . number_format($view['total_tfk']) . '</td></tr>';
    echo '</table>';
    
    echo '</body>';
    echo '</html>';
    exit;
}

// Kembalikan data dalam format JSON
http_response_code(200);
header('Access-Control-Allow-Origin: *');
header("Content-type: application/json; charset=utf-8");
echo json_encode([
    "success" => true,
    "message" => "Data faktur berhasil diambil",
    "data" => $view,
    "source" => $source
]);

$conn = $base->close();
exit;
?>