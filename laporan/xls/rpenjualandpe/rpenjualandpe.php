<?php
    require_once('../../../config/connection/connection.php');
    require_once('../../../config/connection/security.php');
    require_once('../../../config/function/data.php');

    $base = new DB;
    $secu = new Security;
    $data = new Data;
    $conn = $base->open();

    // ACCESS DATA
    $admin = $secu->injection(@$_COOKIE['adminkuy']);
    $kunci = $secu->injection(@$_COOKIE['kuncikuy']);
    $level = $secu->injection(@$_COOKIE['jeniskuy']);
    $valid = $secu->validadmin($admin, $kunci);

    if ($valid == false) {
        header("location: " . $data->sistem('url_sis') . "/signout");
        exit;
    }

    // POST DATA
    $cari = $secu->injection(@$_GET['key']);
    $pecah = explode('_', $cari);
    $outlet = empty($pecah[0]) ? "" : "AND C.id_out='$pecah[0]'";
    $produk = empty($pecah[1]) ? "" : "AND A.id_pro='$pecah[1]'";
    $tgl1 = empty($pecah[2]) ? "" : "AND B.tgl_tfk>='$pecah[2]'";
    $tgl2 = empty($pecah[3]) ? "" : "AND B.tgl_tfk<='$pecah[3]'";

    // Set header for Excel download
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=report_penjualan_dpe_" . date('Y-m-d_H-i-s') . ".xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo "<table border='1'>";
    echo "<tr style='background-color: #f0f0f0;'>
            <th>#</th>
            <th>Tgl. Faktur</th>
            <th>Nomor Faktur</th>
            <th>Nama Outlet</th>
            <th>Kategori Produk</th>
            <th>Nama Produk</th>
            <th>Qty</th>
            <th>Harga</th>
            <th>Diskon</th>
            <th>Total</th>
          </tr>";

    // Get data from current application (DPEA)
    $master = $conn->prepare("SELECT A.jumlah_tfd, A.harga_tfd, A.diskon_tfd, A.total_tfd, 
                                     B.kode_tfk, B.tgl_tfk,
                                     C.nama_out, 
                                     D.nama_pro, E.nama_kpr
                             FROM transaksi_fakturdetail AS A 
                             LEFT JOIN transaksi_faktur AS B ON A.id_tfk = B.id_tfk
                             LEFT JOIN produk AS D ON A.id_pro = D.id_pro
                             LEFT JOIN outlet AS C ON B.id_out = C.id_out
                             LEFT JOIN kategori_produk AS E ON D.id_kpr = E.id_kpr
                             WHERE A.id_tfd != '' AND (D.nama_p = 'MP0000000002' OR D.nama_pro LIKE '%VISION BLU%') $outlet $produk $tgl1 $tgl2 
                             ORDER BY B.tgl_tfk DESC, B.kode_tfk DESC");
    $master->execute();

    $current_data = [];
    while ($hasil = $master->fetch(PDO::FETCH_ASSOC)) {
        // Format regional untuk konsistensi dengan API
        $current_data[] = $hasil;
    }

    // Get data from other applications via API
    $other_data = [];
    
    // Get list of other applications
    $qapp = "SELECT id_apl, nama_apl, base_url_apl, key_apl FROM aplikasi WHERE self_apl = 0 AND active_apl = 1";
    $app_stmt = $conn->prepare($qapp);
    $app_stmt->execute();
    
    while ($app = $app_stmt->fetch(PDO::FETCH_ASSOC)) {
        $api_url = rtrim($app['base_url_apl'], '/') . '/api/getPenjualanDPE.php';
        
        // Properly build API parameters - only send non-empty values
        $api_params = ['key' => $app['key_apl']];
        
        if (!empty($pecah[0])) {
            $api_params['outlet'] = $pecah[0];
        }
        if (!empty($pecah[1])) {
            $api_params['produk'] = $pecah[1];
        }
        if (!empty($pecah[2])) {
            $api_params['tgl1'] = $pecah[2];
        }
        if (!empty($pecah[3])) {
            $api_params['tgl2'] = $pecah[3];
        }
        
        $api_params['halaman'] = 1;
        $api_params['maximal'] = 999999; // Get all data for export
        
        // Debug: Log what parameters we're sending
        error_log("Excel Export API Call - URL: $api_url");
        error_log("Excel Export API Params: " . json_encode($api_params));
        
        // Make API call
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url . '?' . http_build_query($api_params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Debug: Log API call details
        error_log("API Call: " . $api_url . '?' . http_build_query($api_params));
        error_log("HTTP Code: " . $http_code);
        error_log("Response: " . substr($response, 0, 200));
        
        if ($http_code === 200 && $response) {
            $api_data = json_decode($response, true);
            if ($api_data && isset($api_data['data']) && is_array($api_data['data'])) {
                $other_data = array_merge($other_data, $api_data['data']);
            }
        }
    }

    // Combine and sort all data
    $all_data = array_merge($current_data, $other_data);
    
    // Sort by date descending
    if (!empty($all_data)) {
        usort($all_data, function($a, $b) {
            return strtotime($b['tgl_tfk']) - strtotime($a['tgl_tfk']);
        });
    }

    // Output data
    $no = 0;
    foreach ($all_data as $hasil) {
        $no++;
        // Format tanggal dari YYYY-MM-DD ke YY-MM-DD
        $formatted_date = date('y-m-d', strtotime($hasil['tgl_tfk']));
        
        echo "<tr>
              <td>" . $no . "</td>
              <td>" . htmlspecialchars($formatted_date) . "</td>
              <td>" . substr(htmlspecialchars($hasil['kode_tfk']), 0, 4) . "....</td>
              <td>" . htmlspecialchars($hasil['nama_out']) . "</td>
              <td>" . htmlspecialchars($hasil['nama_kpr'] ?? '') . "</td>
              <td>" . htmlspecialchars($hasil['nama_pro']) . "</td>
              <td>" . htmlspecialchars($hasil['jumlah_tfd']) . "</td>
              <td>" . htmlspecialchars($hasil['harga_tfd']) . "</td>
              <td>" . $hasil['diskon_tfd'] . "%</td>
              <td>" . htmlspecialchars($hasil['total_tfd']) . "</td>
              </tr>";
    }

    echo "</table>";

    $conn = $base->close();
?>
