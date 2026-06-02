<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	require_once('../../config/function/paging.php');
	$base	= new DB;
	$secu	= new Security;
	$data	= new Data;
	$paging	= new Paging;
	$conn	= $base->open();
	$tanggal= date('Y-m-d');
	//ACCESS DATA
	$admin	= $secu->injection(@$_COOKIE['adminkuy']);
	$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
	$level	= $secu->injection(@$_COOKIE['jeniskuy']);
	$valid	= $secu->validadmin($admin, $kunci);
	//POST DATA
	$cari	= $secu->injection(@$_GET['caridata']);
	$page	= $secu->injection(@$_GET['halaman']);
	$maxi	= $secu->injection(@$_GET['maximal']);
	$menu	= $secu->injection(@$_GET['menudata']);
	$mulai	= ($page>1) ? (($page * $maxi) - $maxi) : 0;
	//READ DATA
	if($valid==false){
		$tabel	= '<tr><td colspan="3">Session login anda habis...</td></tr>';
		$navi	= '';
	} else {
		$pecah	= explode('_', $cari);
		$outlet	= empty($pecah[0]) ? "" : "AND D.id_out='$pecah[0]'"; 
		$produk	= empty($pecah[1]) ? "" : "AND A.id_pro='$pecah[1]'"; 
		$tgl1	= empty($pecah[2]) ? "" : "AND B.tgl_tfk>='$pecah[2]'"; 
		$tgl2	= empty($pecah[3]) ? "" : "AND B.tgl_tfk<='$pecah[3]'"; 
		
		// Get data from current application (DPEA)
		$current_data = [];
		
		// Count total records with DPE filter (nama_p = MP0000000002)
		$count_query = "SELECT COUNT(A.id_tfd) AS total 
						FROM transaksi_fakturdetail AS A 
						LEFT JOIN transaksi_faktur AS B ON A.id_tfk = B.id_tfk
						LEFT JOIN produk AS C ON A.id_pro = C.id_pro
						LEFT JOIN outlet AS D ON B.id_out = D.id_out
						LEFT JOIN kategori_produk AS E ON C.id_kpr = E.id_kpr
						WHERE A.id_tfd != '' AND (C.nama_p = 'MP0000000002' OR C.nama_pro LIKE '%VISION BLU%') $outlet $produk $tgl1 $tgl2";
		
		$jumlah	= $conn->query($count_query)->fetch(PDO::FETCH_ASSOC);
		
		// Fetch current records with proper joins
		$local_query = "SELECT A.jumlah_tfd, A.harga_tfd, A.diskon_tfd, A.total_tfd, 
						B.kode_tfk, B.tgl_tfk,
						C.nama_pro, D.nama_out, 
						E.nama_kpr
						FROM transaksi_fakturdetail AS A 
						LEFT JOIN transaksi_faktur AS B ON A.id_tfk = B.id_tfk
						LEFT JOIN produk AS C ON A.id_pro = C.id_pro
						LEFT JOIN outlet AS D ON B.id_out = D.id_out
						LEFT JOIN kategori_produk AS E ON C.id_kpr = E.id_kpr
						WHERE A.id_tfd != '' AND (C.nama_p = 'MP0000000002' OR C.nama_pro LIKE '%VISION BLU%') $outlet $produk $tgl1 $tgl2 
						ORDER BY B.tgl_tfk DESC, B.kode_tfk DESC";
		
		$master	= $conn->prepare($local_query);
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
			$api_url = rtrim($app['base_url_apl'], '/') . '/api/getPenjualanDPE.php';
			
			// Build API parameters - keep it simple
			$api_params = [
				'key' => $app['key_apl'],
				'halaman' => 1,
				'maximal' => 9999
			];
			
			// Only add filters if they exist
			if (!empty($pecah[0])) $api_params['outlet'] = $pecah[0];
			if (!empty($pecah[1])) $api_params['produk'] = $pecah[1];
			if (!empty($pecah[2])) $api_params['tgl1'] = $pecah[2];
			if (!empty($pecah[3])) $api_params['tgl2'] = $pecah[3];
			
			// Make API call
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $api_url . '?' . http_build_query($api_params));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			$response = curl_exec($ch);
			$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$curl_error = curl_error($ch);
			curl_close($ch);
			
			// Debug: Log what's happening
			error_log("API URL: " . $api_url . '?' . http_build_query($api_params));
			error_log("HTTP Code: " . $http_code);
			error_log("Response: " . substr($response, 0, 500));
			error_log("cURL Error: " . $curl_error);
			
			if ($http_code === 200 && $response && !$curl_error) {
				$api_data = json_decode($response, true);
				if ($api_data && isset($api_data['data']) && is_array($api_data['data'])) {
					$other_data = array_merge($other_data, $api_data['data']);
					error_log("Successfully added " . count($api_data['data']) . " records from " . $app['nama_apl']);
				} else {
					error_log("API response structure invalid from " . $app['nama_apl'] . ": " . json_encode($api_data));
				}
			} else {
				error_log("API call failed - HTTP: $http_code, cURL: $curl_error, Response: " . substr($response, 0, 200));
			}
		}

		// Combine and sort all data
		$all_data = array_merge($current_data, $other_data);
		
		// Debug: Log data counts
		error_log("Local data count: " . count($current_data));
		error_log("API data count: " . count($other_data));
		error_log("Total combined data count: " . count($all_data));
		
		// Sort by date descending
		if (!empty($all_data)) {
			usort($all_data, function($a, $b) {
				return strtotime($b['tgl_tfk']) - strtotime($a['tgl_tfk']);
			});
		}

		// Apply pagination to combined data
		$total_records = count($all_data);
		$paginated_data = array_slice($all_data, $mulai, $maxi);

		// Generate table
		$tabel = '';
		$no = $mulai;
		foreach ($paginated_data as $hasil) {
			$no++;
			// Format tanggal dari YYYY-MM-DD ke YY-MM-DD
			$formatted_date = date('y-m-d', strtotime($hasil['tgl_tfk']));
			
			$tabel .= '<tr>
					  <td><center>' . $no . '</center></td>
					  <td><center>' . htmlspecialchars($formatted_date) . '</center></td>
					  <td>' . substr(htmlspecialchars($hasil['kode_tfk']), 0, 4) . '....</td>
					  <td>' . htmlspecialchars($hasil['nama_out']) . '</td>
					  <td>' . htmlspecialchars($hasil['nama_kpr'] ?? '') . '</td>
					  <td>' . htmlspecialchars($hasil['nama_pro']) . '</td>
					  <td><center>' . $data->angka($hasil['jumlah_tfd']) . '</center></td>
					  <td><center>' . $data->angka($hasil['harga_tfd']) . '</center></td>
					  <td><center>' . $hasil['diskon_tfd'] . '%</center></td>
					  <td><center>' . $data->angka($hasil['total_tfd']) . '</center></td>
					  </tr>';
		}

		$navi = $paging->myPaging($menu, $total_records, $maxi, $page); 
	}
	$conn	= $base->close();
	$json	= array("tabel" => $tabel, "halaman" => $page, "paginasi" => $navi);
	http_response_code(200);
	header('Access-Control-Allow-Origin: *');
	header("Content-type: application/json; charset=utf-8");
	//header('Content-type: text/html; charset=UTF-8');
	echo(json_encode($json));
?>