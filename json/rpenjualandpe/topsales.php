<?php
require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	$base	= new DB;
	$secu	= new Security;
	$data	= new Data;
	$conn	= $base->open();

//ACCESS DATA
$admin = $secu->injection(@$_COOKIE['adminkuy']);
$kunci = $secu->injection(@$_COOKIE['kuncikuy']);
$level = $secu->injection(@$_COOKIE['jeniskuy']);
$valid = $secu->validadmin($admin, $kunci);

//POST DATA
$cari = $secu->injection(@$_GET['caridata']);
$tgl1_direct = $secu->injection(@$_GET['tgl1']); // Direct date parameter
$tgl2_direct = $secu->injection(@$_GET['tgl2']); // Direct date parameter
$category = $secu->injection(@$_GET['category']); // Category filter

//READ DATA
if($valid==false){
    $response = array("status" => "error", "message" => "Session login anda habis...");
} else {
    $pecah = explode('_', $cari);
    $outlet = empty($pecah[0]) ? "" : "AND D.id_out='$pecah[0]'"; 
    $produk = empty($pecah[1]) ? "" : "AND A.id_pro='$pecah[1]'"; 
    
    // Use direct date parameters if provided, otherwise use from caridata
    if (!empty($tgl1_direct)) {
        $tgl1 = "AND B.tgl_tfk>='$tgl1_direct'";
    } else {
        $tgl1 = empty($pecah[2]) ? "" : "AND B.tgl_tfk>='$pecah[2]'";
    }
    
    if (!empty($tgl2_direct)) {
        $tgl2 = "AND B.tgl_tfk<='$tgl2_direct'";
    } else {
        $tgl2 = empty($pecah[3]) ? "" : "AND B.tgl_tfk<='$pecah[3]'";
    }
    
    // Get top sales from current application (DPEA)
    $current_data = [];
    
    // Determine WHERE clause based on category
    $category_filter = "";
    $group_by = "D.id_out, C.id_pro";
    $select_fields = "D.nama_out, C.nama_pro, E.nama_kpr";
    
    switch($category) {
        case 'vision_blu':
            $category_filter = "AND C.nama_pro LIKE '%VISION BLU 7 ML%' AND C.nama_pro NOT LIKE '%EXTRA%'";
            $group_by = "D.id_out";
            $select_fields = "D.nama_out, 'VISION BLU 7 ML' as nama_pro, 'VISION BLU' as nama_kpr";
            break;
        case 'vision_blu_extra':
            $category_filter = "AND C.nama_pro LIKE '%VISION BLU EXTRA 7 ML%'";
            $group_by = "D.id_out";
            $select_fields = "D.nama_out, 'VISION BLU EXTRA 7 ML' as nama_pro, 'VISION BLU' as nama_kpr";
            break;
        case 'penjualan_data':
            $category_filter = ""; // No product filter, show all outlets
            $group_by = "D.id_out";
            $select_fields = "D.nama_out, 'All Products' as nama_pro, 'PENJUALAN DATA' as nama_kpr";
            break;
        case 'dpe_general':
            $category_filter = "AND C.nama_p = 'MP0000000002'";
            $group_by = "C.id_pro";
            $select_fields = "'' as nama_out, C.nama_pro, E.nama_kpr";
            break;
        default:
            $category_filter = "AND (C.nama_p = 'MP0000000002' OR C.nama_pro LIKE '%VISION BLU%')";
            break;
    }
    
    // Build SQL query for top sales - Group by category type
    if ($category === 'penjualan_data') {
        $sql = "SELECT $select_fields,
                SUM(A.total_tfd) as total_sales,
                SUM(A.jumlah_tfd) as total_qty,
                SUM(A.total_tfd) as total_amount,
                COUNT(A.id_tfd) as total_transaksi
                FROM transaksi_fakturdetail AS A 
                LEFT JOIN transaksi_faktur AS B ON A.id_tfk = B.id_tfk
                LEFT JOIN produk AS C ON A.id_pro = C.id_pro
                LEFT JOIN outlet AS D ON B.id_out = D.id_out
                LEFT JOIN kategori_produk AS E ON C.id_kpr = E.id_kpr
                WHERE A.id_tfd != '' $category_filter $outlet $produk $tgl1 $tgl2 
                GROUP BY $group_by
                ORDER BY total_amount DESC 
                LIMIT 10";
    } else {
        $sql = "SELECT $select_fields,
                SUM(A.total_tfd) as total_sales,
                SUM(A.jumlah_tfd) as total_qty,
                COUNT(A.id_tfd) as total_transaksi
                FROM transaksi_fakturdetail AS A 
                LEFT JOIN transaksi_faktur AS B ON A.id_tfk = B.id_tfk
                LEFT JOIN produk AS C ON A.id_pro = C.id_pro
                LEFT JOIN outlet AS D ON B.id_out = D.id_out
                LEFT JOIN kategori_produk AS E ON C.id_kpr = E.id_kpr
                WHERE A.id_tfd != '' $category_filter $outlet $produk $tgl1 $tgl2 
                GROUP BY $group_by
                ORDER BY total_qty DESC 
                LIMIT 10";
    }
    
    $master = $conn->prepare($sql);
    $master->execute();
    
    while($hasil = $master->fetch(PDO::FETCH_ASSOC)){
        $current_data[] = $hasil;
    }

    // Get data from other applications via API
    $other_data = [];
    
    // Get list of other applications
    $qapp = "SELECT id_apl, nama_apl, base_url_apl, key_apl FROM aplikasi WHERE self_apl = 0 AND active_apl = 1";
    $app_stmt = $conn->prepare($qapp);
    $app_stmt->execute();
    
    while ($app = $app_stmt->fetch(PDO::FETCH_ASSOC)) {
        $api_url = rtrim($app['base_url_apl'], '/') . '/api/getTopSalesDPE.php';
        
        // Build API parameters
        $api_params = ['key' => $app['key_apl']];
        
        // Add category parameter
        if (!empty($category)) {
            $api_params['category'] = $category;
        }
        
        if (!empty($pecah[0])) {
            $api_params['outlet'] = $pecah[0];
        }
        if (!empty($pecah[1])) {
            $api_params['produk'] = $pecah[1];
        }
        
        // Use direct date parameters if provided
        if (!empty($tgl1_direct)) {
            $api_params['tgl1'] = $tgl1_direct;
        } elseif (!empty($pecah[2])) {
            $api_params['tgl1'] = $pecah[2];
        }
        
        if (!empty($tgl2_direct)) {
            $api_params['tgl2'] = $tgl2_direct;
        } elseif (!empty($pecah[3])) {
            $api_params['tgl2'] = $pecah[3];
        }
        
        $api_params['limit'] = 10;
        
        // Make API call
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url . '?' . http_build_query($api_params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response_api = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200 && $response_api) {
            $api_data = json_decode($response_api, true);
            if ($api_data && isset($api_data['data']) && is_array($api_data['data'])) {
                $other_data = array_merge($other_data, $api_data['data']);
            }
        }
    }

    // Combine all data and group by outlet+product
    $combined_data = [];
    $all_data = array_merge($current_data, $other_data);
    
    foreach($all_data as $item) {
        $key = $item['nama_out'] . '|' . $item['nama_pro'];
        if (isset($combined_data[$key])) {
            $combined_data[$key]['total_sales'] += $item['total_sales'];
            $combined_data[$key]['total_qty'] += $item['total_qty'];
            $combined_data[$key]['total_transaksi'] += $item['total_transaksi'];
        } else {
            $combined_data[$key] = $item;
        }
    }
    
    // Sort by total quantity descending and get top 5
    usort($combined_data, function($a, $b) {
        return $b['total_qty'] - $a['total_qty'];
    });
    
    $top_sales = array_slice($combined_data, 0, 5);
    
    $response = array(
        "status" => "success", 
        "data" => $top_sales,
        "message" => "Top sales data loaded successfully"
    );
}

$conn = $base->close();
http_response_code(200);
header('Access-Control-Allow-Origin: *');
header("Content-type: application/json; charset=utf-8");
echo(json_encode($response));
?>
