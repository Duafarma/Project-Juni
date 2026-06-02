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
	$sistem	= $data->sistem('url_sis');
	//ACCESS DATA
	$admin	= $secu->injection(@$_COOKIE['adminkuy']);
	$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
	$level	= $secu->injection(@$_COOKIE['jeniskuy']);
	$valid	= $secu->validadmin($admin, $kunci);
	//POST DATA
	$cari	= $secu->injection(@$_GET['caridata']);
	$cari_cabang = $secu->injection(@$_GET['cari_cabang']); // Add this new parameter
	$id_out = $secu->injection(@$_GET['id_out']); // Ambil parameter id_out
	$page	= $secu->injection(@$_GET['halaman']);
	$maxi	= $secu->injection(@$_GET['maximal']);
	$menu	= $secu->injection(@$_GET['menudata']);
	$id_apl = $secu->injection(@$_GET['id_apl']);
	// Tambahkan parameter periode
	$periode_dari = $secu->injection(@$_GET['periode_dari']);
	$periode_sampai = $secu->injection(@$_GET['periode_sampai']);
	$mulai	= ($page>1) ? (($page * $maxi) - $maxi) : 0;
	//$cari	= $data->cekcari($cari, '-', ' ');
	//$cari	= $data->cekcari($cari, '_', '/');
	// Ambil daftar id_outlet untuk id_apl terpilih
	$list_outlet = [];
	if ($id_apl && $id_apl !== 'all') {
	    // Tidak ada kolom id_apl di outlet, jadi blok ini dikosongkan atau gunakan mapping manual jika ada
	    // $list_outlet = getListOutletByCabang($id_apl); // jika punya mapping manual
	}
	// Debug log untuk memeriksa parameter yang diterima
	error_log("ID APL: " . $id_apl);
	error_log("Cari Data: " . $cari);
	error_log("Periode Dari: " . $periode_dari);
	error_log("Periode Sampai: " . $periode_sampai);
	//READ DATA
	if($valid==false){
	    $json = [
	        "success" => false,
	        "message" => "Session login anda habis...",
	        "data" => [],
	        "total" => 0,
	        "halaman" => $page,
	        "paginasi" => ''
	    ];
	} else {
	    $dataRows = [];
	    $total = 0;
	    $active = 'Active';
	    $no = $mulai;
	    $where = "(
	        A.kode_tfk LIKE '%$cari%' OR
	        A.sj_tfk LIKE '%$cari%' OR
	        A.po_tfk LIKE '%$cari%' OR
	        B.nama_out LIKE '%$cari%'
	    )";  // Tambahkan filter ini

	    // Tambahkan filter berdasarkan id_out jika ada dan bukan "All"
	    if (!empty($id_out) && $id_out !== 'All') {
	        $where .= " AND A.id_out = :id_out";
	    }

	    // Add cabang-specific search if parameter is provided
	    if (!empty($cari_cabang)) {
	        // Add additional WHERE clause for cabang search
	        $cabang_where = "(
	            B.nama_out LIKE '%$cari_cabang%' OR
	            A.kode_tfk LIKE '%$cari_cabang%'
	        )";
	        
	        // Combine with existing WHERE clause
	        $where = "($where) AND ($cabang_where)";
	    }
	    
	    // Tambahkan filter periode tanggal jika disediakan
	    if (!empty($periode_dari)) {
	        $where .= " AND A.tgl_tfk >= '$periode_dari'";
	    }
	    if (!empty($periode_sampai)) {
	        $where .= " AND A.tgl_tfk <= '$periode_sampai'";
	    }

	    $bind_id_apl = false;
	    if (!empty($id_apl) && $id_apl === 'all_cabang') {
	        // This will store data from all cabang APIs
	        $combinedData = [];
	        $totalRecords = 0;
	        
	        // Get all active cabang from aplikasi table
	        $get_all_cabang = $conn->prepare("SELECT id_apl, base_url_apl, key_apl, nama_apl FROM aplikasi WHERE active_apl = 1");
	        $get_all_cabang->execute();
	        $all_cabang = $get_all_cabang->fetchAll(PDO::FETCH_ASSOC);
	        
	        // Loop through each cabang and fetch data from their APIs
	        foreach ($all_cabang as $cabang) {
	            // Generate encrypt for API authentication
	            $tgl = date('Y-m-d');
	            $encrypt = md5($tgl . "#" . $cabang['key_apl']);
	            
	            // Setup API parameters
	            $api_params = [
	                'caridata' => $cari,
	                'cari_cabang' => $cari_cabang,
	                'id_out' => ($id_out !== 'All') ? $id_out : '', // Handle "All" value
	                'halaman' => 1, // Always get first page for combined results
	                'maximal' => 1000, // Get more records to combine
	                'menudata' => $menu,
	                'encrypt' => $encrypt,
	                'periode_dari' => $periode_dari, // Tambahkan parameter periode dari
	                'periode_sampai' => $periode_sampai // Tambahkan parameter ini
	            ];
	            
	            // Skip if no base_url_apl is available
	            if (empty($cabang['base_url_apl'])) {
	                continue;
	            }
	            
	            // Build API URL
	            $api_url = rtrim($cabang['base_url_apl'], '/') . '/api/getFakturAr.php?' . http_build_query($api_params);
	            
	            // Log API request for debugging
	            error_log("All Cabang - Requesting from: " . $cabang['nama_apl'] . " URL: " . $api_url);
	            
	            // Fetch data from API
	            $ch = curl_init();
	            curl_setopt($ch, CURLOPT_URL, $api_url);
	            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	            $api_response = curl_exec($ch);
	            $curl_error = curl_error($ch);
	            curl_close($ch);
	            
	            // Skip if error
	            if ($curl_error) {
	                error_log("cURL Error from " . $cabang['nama_apl'] . ": " . $curl_error);
	                continue;
	            }
	            
	            // Process API response
	            $api_data = json_decode($api_response, true);
	            
	            if (isset($api_data['data']) && is_array($api_data['data'])) {
	                // Add cabang information to each record
	                foreach ($api_data['data'] as &$row) {
	                    // Make sure cabang name is set
	                    $row['nama_cabang'] = $cabang['nama_apl'];
	                    
	                    // Format date to DD/MM/YY
	                    if (isset($row['tgl_tfk']) && !empty($row['tgl_tfk'])) {
	                        $date = new DateTime($row['tgl_tfk']);
	                        $row['tgl_tfk'] = $date->format('d/m/y');
	                    }
	                    
	                    // Ensure action URLs point to the right system
	                    if (isset($row['action'])) {
	                        $row['action']['sj_url'] = $sistem . '/laporan/xps/sjsales/sjsales.php?key=' . $row['id_tfk'];
	                        $row['action']['faktur_url'] = $sistem . '/laporan/xps/faktursales/faktursales.php?key=' . $row['id_tfk'];
	                    }
	                    
	                    // Add to combined data
	                    $combinedData[] = $row;
	                }
	                
	                // Add to total count
	                $totalRecords += (int)($api_data['total'] ?? count($api_data['data']));
	            }
	        }
	        
	        // Sort combined data by invoice date (descending)
	        // First separate urgent and normal items
$urgentItems = [];
$normalItems = [];

// Process data from all branches
foreach ($combinedData as $row) {
    // Standardize urgent detection for consistency
    $isUrgent = false;
    if (isset($row['urgent_flag']) && $row['urgent_flag'] === true) {
        $isUrgent = true;
    } elseif (isset($row['status_urgent'])) {
        $urgentValue = strtolower(trim($row['status_urgent']));
        $isUrgent = ($urgentValue === 'urgent' || $urgentValue === '1' || $urgentValue === 'true');
    }
    
    // Update row with standardized urgent flag
    $row['urgent_flag'] = $isUrgent;
    $row['status_urgent'] = $isUrgent ? 'urgent' : 'normal';
    
    // Add to appropriate array - urgent items first
    if ($isUrgent) {
        $urgentItems[] = $row;
    } else {
        $normalItems[] = $row;
    }
}

// Sort each group by year/month descending, day ascending
usort($urgentItems, function($a, $b) {
    // Safely parse dates with error handling
    try {
        $dateA = $a['tgl_tfk'];
        $dateB = $b['tgl_tfk'];
        
        // Convert DD/MM/YY format to Y-m-d format if needed
        if (preg_match('/^\d{2}\/\d{2}\/\d{2}$/', $dateA)) {
            $parts = explode('/', $dateA);
            $dateA = '20' . $parts[2] . '-' . $parts[1] . '-' . $parts[0];
        }
        
        if (preg_match('/^\d{2}\/\d{2}\/\d{2}$/', $dateB)) {
            $parts = explode('/', $dateB);
            $dateB = '20' . $parts[2] . '-' . $parts[1] . '-' . $parts[0];
        }
        
        $dateA = new DateTime($dateA);
        $dateB = new DateTime($dateB);
    } catch (Exception $e) {
        // If date parsing fails, use string comparison
        return strcmp($a['tgl_tfk'], $b['tgl_tfk']);
    }
    
    // Compare years (descending)
    $yearDiff = (int)$dateB->format('Y') - (int)$dateA->format('Y');
    if ($yearDiff !== 0) return $yearDiff;
    
    // Compare months (descending)
    $monthDiff = (int)$dateB->format('m') - (int)$dateA->format('m');
    if ($monthDiff !== 0) return $monthDiff;
    
    // Compare days (ascending)
    return (int)$dateA->format('d') - (int)$dateB->format('d');
});

usort($normalItems, function($a, $b) {
    // Safely parse dates with error handling
    try {
        $dateA = $a['tgl_tfk'];
        $dateB = $b['tgl_tfk'];
        
        // Convert DD/MM/YY format to Y-m-d format if needed
        if (preg_match('/^\d{2}\/\d{2}\/\d{2}$/', $dateA)) {
            $parts = explode('/', $dateA);
            $dateA = '20' . $parts[2] . '-' . $parts[1] . '-' . $parts[0];
        }
        
        if (preg_match('/^\d{2}\/\d{2}\/\d{2}$/', $dateB)) {
            $parts = explode('/', $dateB);
            $dateB = '20' . $parts[2] . '-' . $parts[1] . '-' . $parts[0];
        }
        
        $dateA = new DateTime($dateA);
        $dateB = new DateTime($dateB);
    } catch (Exception $e) {
        // If date parsing fails, use string comparison
        return strcmp($a['tgl_tfk'], $b['tgl_tfk']);
    }
    
    // Compare years (descending)
    $yearDiff = (int)$dateB->format('Y') - (int)$dateA->format('Y');
    if ($yearDiff !== 0) return $yearDiff;
    
    // Compare months (descending)
    $monthDiff = (int)$dateB->format('m') - (int)$dateA->format('m');
    if ($monthDiff !== 0) return $monthDiff;
    
    // Compare days (ascending)
    return (int)$dateA->format('d') - (int)$dateB->format('d');
});

// Combine arrays - urgentItems first, followed by normalItems
$combinedData = array_merge($urgentItems, $normalItems);
	        
	        // Apply pagination manually
	        $total = count($combinedData);
	        $startIndex = ($page - 1) * $maxi;
	        $endIndex = min($startIndex + $maxi, $total);
	        
	        // Renumber items after sorting and merging
	        $no = $mulai;
	        $paginatedData = [];

	        for ($i = $startIndex; $i < $endIndex; $i++) {
	            if (isset($combinedData[$i])) {
	                $no++;
	                $combinedData[$i]['no'] = $no;
	                $paginatedData[] = $combinedData[$i];
	            }
	        }
	        
	        // Generate pagination
	        $navi = $paging->myPaging($menu, $total, $maxi, $page);
	        
	        // Set the result
	        $dataRows = $paginatedData;
	        $total = $total;
	        
	    } elseif (!empty($id_apl) && $id_apl !== 'all') {
	        // Ambil base_url_apl dan key_apl dari tabel aplikasi
	        $stmt = $conn->prepare("SELECT base_url_apl, key_apl, nama_apl FROM aplikasi WHERE id_apl = :id_apl LIMIT 1");
	        $stmt->bindParam(':id_apl', $id_apl, PDO::PARAM_STR);
	        $stmt->execute();
	        $row = $stmt->fetch(PDO::FETCH_ASSOC);

	        if ($row && !empty($row['base_url_apl']) && !empty($row['key_apl'])) {
	            // Generate encrypt sesuai aturan API tujuan
	            $tgl = date('Y-m-d');
	            $encrypt = md5($tgl . "#" . $row['key_apl']);
	            $nama_cabang = $row['nama_apl'];

	            // Susun parameter untuk API tujuan
	            $api_params = [
	                'caridata' => $cari,
	                'cari_cabang' => $cari_cabang,
	                'id_out' => ($id_out !== 'All') ? $id_out : '', // Handle "All" value
	                'halaman' => $page,
	                'maximal' => $maxi,
	                'menudata' => $menu,
	                'encrypt' => $encrypt,
	                'periode_dari' => $periode_dari, // Tambahkan parameter periode dari
	                'periode_sampai' => $periode_sampai // Tambahkan parameter periode sampai
	            ];

	            // Bangun URL API tujuan
	            $api_url = rtrim($row['base_url_apl'], '/') . '/api/getFakturAr.php?' . http_build_query($api_params);

	            // Debug log untuk memeriksa API URL dan respons
	            error_log("API Request to: " . $api_url);

	            // Ambil data dari API cabang lain via cURL
	            $ch = curl_init();
	            curl_setopt($ch, CURLOPT_URL, $api_url);
	            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	            $api_response = curl_exec($ch);
	            $curl_error = curl_error($ch);
	            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	            curl_close($ch);

	            // Log responses for debugging
	            error_log("API HTTP Code: " . $http_code);
	            if ($curl_error) {
	                error_log("cURL Error: " . $curl_error);
	            }
	            error_log("API Response: " . substr($api_response, 0, 500)); // Log first 500 chars

	            if ($curl_error) {
	                $json = [
	                    "success" => false,
	                    "message" => "Koneksi ke server cabang gagal: " . $curl_error,
	                    "data" => [],
	                    "total" => 0,
	                    "halaman" => $page,
	                    "paginasi" => ''
	                ];
	                goto output_json;
	            }

	            $api_data = json_decode($api_response, true);

	            if (isset($api_data['data']) && is_array($api_data['data'])) {
	                $dataRows = $api_data['data'];
	                
	                // Filter results by cabang name if search term is provided
	                if (!empty($cari_cabang)) {
	                    $filteredRows = [];
	                    foreach ($dataRows as $row) {
	                        // Check if branch name, outlet name or invoice code contains search term
	                        if (
	                            stripos($nama_cabang, $cari_cabang) !== false ||
	                            stripos($row['nama_out'] ?? '', $cari_cabang) !== false ||
	                            stripos($row['kode_tfk'] ?? '', $cari_cabang) !== false
	                        ) {
	                            $filteredRows[] = $row;
	                        }
	                    }
	                    $dataRows = $filteredRows;
	                }
	                
	                // Add serial number, branch name, and fix action URLs to each row
	                $no = $mulai;
	                foreach ($dataRows as &$row) {
	                    $no++;
	                    $row['no'] = $no;
	                    if (!isset($row['nama_cabang']) || empty($row['nama_cabang'])) {
	                        $row['nama_cabang'] = $nama_cabang;
	                    }
	                    
	                    // Format date to DD/MM/YY
	                    if (isset($row['tgl_tfk']) && !empty($row['tgl_tfk'])) {
	                        $date = new DateTime($row['tgl_tfk']);
	                        $row['tgl_tfk'] = $date->format('d/m/y');
	                    }
	                    
	                    // If action URLs are not provided by the API, create them
	                    if (!isset($row['action']) || empty($row['action'])) {
	                        $row['action'] = [
	                            "sj_url" => $sistem . '/laporan/xps/sjsales/sjsales.php?key=' . $row['id_tfk'],
	                            "faktur_url" => $sistem . '/laporan/xps/faktursales/faktursales.php?key=' . $row['id_tfk']
	                        ];
	                    }
	                    
	                    // If URLs have different domain, update them to point to the current API's domain
	                    if (isset($row['action']['sj_url']) && strpos($row['action']['sj_url'], $sistem) === false) {
	                        $row['action']['sj_url'] = $sistem . '/laporan/xps/sjsales/sjsales.php?key=' . $row['id_tfk'];
	                        $row['action']['faktur_url'] = $sistem . '/laporan/xps/faktursales/faktursales.php?key=' . $row['id_tfk'];
	                    }
	                }
	                
	                $total = $api_data['total'] ?? count($dataRows);
	                
	                // If we filtered results, update the total
	                if (!empty($cari_cabang)) {
	                    $total = count($dataRows);
	                }
	                
	                // Generate pagination if not provided by API
	                if (isset($api_data['paginasi'])) {
	                    $navi = $api_data['paginasi'];
	                } else {
	                    $navi = $paging->myPaging($menu, $total, $maxi, $page);
	                }
	            } else {
	                $json = [
	                    "success" => false,
	                    "message" => "Format data dari API tidak valid atau data kosong.",
	                    "data" => [],
	                    "total" => 0,
	                    "halaman" => $page,
	                    "paginasi" => ''
	                ];
	                goto output_json;
	            }
	        } else {
	            // Jika base_url_apl tidak tersedia, gunakan data lokal
	            $where .= " AND A.id_apl = :id_apl";
	            $bind_id_apl = true;
	        }
	    }

	    // Ambil nama_apl dari tabel aplikasi jika id_apl dipilih
	    $nama_cabang = 'Puri'; // Default branch name for local data
	    if (!empty($id_apl) && $id_apl !== 'all') {
	        $stmtCab = $conn->prepare("SELECT nama_apl FROM aplikasi WHERE id_apl = :id_apl LIMIT 1");
	        $stmtCab->bindParam(':id_apl', $id_apl, PDO::PARAM_STR);
	        $stmtCab->execute();
	        $rowCab = $stmtCab->fetch(PDO::FETCH_ASSOC);
	        if ($rowCab) {
	            $nama_cabang = $rowCab['nama_apl'];
	        }
	    }

	    // Jika data lokal
	    if (empty($dataRows)) {
	        // Modified approach: Use UNION to combine data from both tables
    $whereCendo = "(
        A.kode_tfk LIKE :cari OR
        A.sj_tfk LIKE :cari OR
        A.po_tfk LIKE :cari OR
        B.nama_out LIKE :cari
    ) AND A.status_tfk NOT IN ('Cancel','Draft')"; // <<< exclude Draft
    
    $wherePIM = "(
        P.kode_tfk LIKE :cari OR
        P.sj_tfk LIKE :cari OR
        P.po_tfk LIKE :cari OR
        B_P.nama_out LIKE :cari
    ) AND P.status_tfk NOT IN ('Cancel','Draft')"; // <<< exclude Draft

    // Add filters for id_out if provided
    if (!empty($id_out) && $id_out !== 'All') {
        $whereCendo .= " AND A.id_out = :id_out";
        $wherePIM .= " AND P.id_out = :id_out";
    }

    // Add cabang-specific search if parameter is provided
    if (!empty($cari_cabang)) {
        $whereCendo .= " AND (B.nama_out LIKE :cari_cabang OR A.kode_tfk LIKE :cari_cabang)";
        $wherePIM .= " AND (B_P.nama_out LIKE :cari_cabang OR P.kode_tfk LIKE :cari_cabang)";
    }
    
    // Add period filters
    if (!empty($periode_dari)) {
        $whereCendo .= " AND A.tgl_tfk >= :periode_dari";
        $wherePIM .= " AND P.tgl_tfk >= :periode_dari";
    }
    if (!empty($periode_sampai)) {
        $whereCendo .= " AND A.tgl_tfk <= :periode_sampai";
        $wherePIM .= " AND P.tgl_tfk <= :periode_sampai";
    }
    
    // Count total records (Cendo & DPE + PIM)
    $qJumlah = "
        SELECT COUNT(*) as total FROM (
            SELECT A.id_tfk FROM transaksi_faktur AS A
            LEFT JOIN outlet AS B ON A.id_out = B.id_out
            LEFT JOIN outlet_alamat AS C ON A.id_out = C.id_out
            LEFT JOIN regional_kabupaten AS D ON C.id_rkb = D.id_rkb
            WHERE $whereCendo
            
            UNION ALL
            
            SELECT P.id_tfk FROM transaksi_faktur_pim AS P
            LEFT JOIN outlet AS B_P ON P.id_out = B_P.id_out
            LEFT JOIN outlet_alamat AS C_P ON P.id_out = C_P.id_out
            LEFT JOIN regional_kabupaten AS D_P ON C_P.id_rkb = D_P.id_rkb
            WHERE $wherePIM
        ) AS combined";
    
    $stmtJumlah = $conn->prepare($qJumlah);
    $stmtJumlah->bindValue(':cari', '%' . $cari . '%', PDO::PARAM_STR);
    
    if (!empty($id_out) && $id_out !== 'All') {
        $stmtJumlah->bindValue(':id_out', $id_out, PDO::PARAM_STR);
    }
    
    if (!empty($cari_cabang)) {
        $stmtJumlah->bindValue(':cari_cabang', '%' . $cari_cabang . '%', PDO::PARAM_STR);
    }
    
    if (!empty($periode_dari)) {
        $stmtJumlah->bindValue(':periode_dari', $periode_dari, PDO::PARAM_STR);
    }
    if (!empty($periode_sampai)) {
        $stmtJumlah->bindValue(':periode_sampai', $periode_sampai, PDO::PARAM_STR);
    }
    
    $stmtJumlah->execute();
    $jumlah = $stmtJumlah->fetch(PDO::FETCH_ASSOC);
    $total = $jumlah['total'] ?? 0;
    
    // Fetch data with UNION query
    $qMaster = "
        (SELECT
            A.id_tfk, 
            A.sj_tfk,
            A.tglsj_tfk,
            A.po_tfk,
            A.tglpo_tfk,
            A.kode_tfk,
            A.status_dokumen,
            A.status_failing,
            A.status_tfk,
            A.status_tfkkf,
            A.tgl_tfk,
            A.ppn_tfk,
            A.total_tfk,
            A.subtot_tfk,
            A.status_f_pajak,
            A.upload_f_pajak,
            B.nama_out,
            B.kode_rs,
            B.status_urgent,
            D.nama_rkb,
            'Cendo & DPE' AS jenis_faktur
        FROM transaksi_faktur AS A
        LEFT JOIN outlet AS B ON A.id_out = B.id_out
        LEFT JOIN outlet_alamat AS C ON A.id_out = C.id_out
        LEFT JOIN regional_kabupaten AS D ON C.id_rkb = D.id_rkb
        WHERE $whereCendo)
        
        UNION ALL
        
        (SELECT
            P.id_tfk, 
            P.sj_tfk,
            P.tglsj_tfk,
            P.po_tfk,
            P.tglpo_tfk,
            P.kode_tfk,
            P.status_dokumen,
            P.status_failing,
            P.status_tfk,
            P.status_tfkkf,
            P.tgl_tfk,
            P.ppn_tfk,
            P.total_tfk,
            P.subtot_tfk,
            P.status_f_pajak,
            P.upload_f_pajak,
            B_P.nama_out,
            B_P.kode_rs,
            B_P.status_urgent,
            D_P.nama_rkb,
            'PIM' AS jenis_faktur
        FROM transaksi_faktur_pim AS P
        LEFT JOIN outlet AS B_P ON P.id_out = B_P.id_out
        LEFT JOIN outlet_alamat AS C_P ON P.id_out = C_P.id_out
        LEFT JOIN regional_kabupaten AS D_P ON C_P.id_rkb = D_P.id_rkb
        WHERE $wherePIM)
        
        ORDER BY 
            CASE WHEN LOWER(status_urgent) = 'urgent' THEN 0 ELSE 1 END,
            YEAR(tgl_tfk) DESC,
            MONTH(tgl_tfk) DESC,
            DAY(tgl_tfk) ASC
        LIMIT :mulai, :maxi";

    $master = $conn->prepare($qMaster);
    $master->bindValue(':cari', '%' . $cari . '%', PDO::PARAM_STR);
    
    if (!empty($id_out) && $id_out !== 'All') {
        $master->bindValue(':id_out', $id_out, PDO::PARAM_STR);
    }
    
    if (!empty($cari_cabang)) {
        $master->bindValue(':cari_cabang', '%' . $cari_cabang . '%', PDO::PARAM_STR);
    }
    
    if (!empty($periode_dari)) {
        $master->bindValue(':periode_dari', $periode_dari, PDO::PARAM_STR);
    }
    if (!empty($periode_sampai)) {
        $master->bindValue(':periode_sampai', $periode_sampai, PDO::PARAM_STR);
    }
    
    $master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
    $master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
    $master->execute();

    while ($hasil = $master->fetch(PDO::FETCH_ASSOC)) {
        $no++;
        
        // Check if item is urgent - use strict comparison for safety
        $isUrgent = false;
        if (isset($hasil['status_urgent']) && strtolower(trim($hasil['status_urgent'])) === 'urgent') {
            $isUrgent = true;
        }
        
        // Format date to DD/MM/YY
        $formattedDate = $hasil['tgl_tfk'];
        if (!empty($hasil['tgl_tfk'])) {
            $date = new DateTime($hasil['tgl_tfk']);
            $formattedDate = $date->format('d/m/y');
        }
        
        $dataRows[] = [
            "no" => $no,
            "kode_tfk" => $hasil['kode_tfk'],
            "nama_out" => $hasil['nama_out'],
            "tgl_tfk" => $formattedDate,
            "total_tfk" => $data->angka($hasil['subtot_tfk']),
            "kode_rs" => $hasil['kode_rs'],
            "po_tfk" => $hasil['po_tfk'],
            "nama_cabang" => $nama_cabang,
            "status_dokumen" => $hasil['status_dokumen'],
            "status_failing" => $hasil['status_failing'],
            "status_tfkkf" => $hasil['status_tfkkf'],
            "status_tfk" => $hasil['status_tfk'],
            "subtot_tfk" => $data->angka($hasil['subtot_tfk']),
            "id_tfk" => $hasil['id_tfk'],
            "urgent_flag" => $isUrgent,
            "status_urgent" => $isUrgent ? 'urgent' : 'normal',
            "upload_f_pajak" => $hasil['upload_f_pajak'] ?? 'belum',
            "status_f_pajak" => $hasil['status_f_pajak'] ?? 'belum terbit',
            "jenis_faktur" => $hasil['jenis_faktur'], // Add the invoice type
            "action" => ($data->akses($admin, $menu, 'A.read_status') === 'Active') ? [
                "sj_url" => $sistem . '/laporan/xps/sjsales/sjsales.php?key=' . $hasil['id_tfk'],
                "faktur_url" => $sistem . '/laporan/xps/faktursales/faktursales.php?key=' . $hasil['id_tfk'],
                "print_enabled" => ($hasil['upload_f_pajak'] ?? '') === 'sudah',
                "print_url" => $sistem . '/laporan/xps/monitoringfi/monitoringfi.php?id_tfk=' . $hasil['id_tfk'] . '&jenis_faktur=' . $hasil['jenis_faktur']
            ] : []
        ];
    }
    $navi = $paging->myPaging($menu, $total, $maxi, $page);
}

	    $json = [
	        "success" => true,
	        "message" => "",
	        "data" => $dataRows,
	        "total" => $total,
	        "halaman" => $page,
	        "paginasi" => $navi
	    ];
	}
	output_json:
	$conn = $base->close();
	http_response_code(200);
	header('Access-Control-Allow-Origin: *');
	header("Content-type: application/json; charset=utf-8");
	echo json_encode($json);
	error_log("JSON Response: " . json_encode($json));
	exit;
?>
