<?php
    require_once('../../config/connection/connection.php');
    require_once('../../config/connection/security.php');
    require_once('../../config/function/data.php');
    require_once('../../config/function/paging.php');
    
    /**
     * Normalize faktur type consistently across all systems
     * @param array $row The data row
     * @return string The normalized faktur type
     */
    function normalizeJenisFaktur($row) {
        // If jenis already exists and is valid, use it
        if (isset($row['jenis']) && !empty($row['jenis'])) {
            return $row['jenis'];
        }
        
        // Method 1: Detect based on invoice code pattern
        if (isset($row['kode_tfk'])) {
            if (strpos(strtoupper($row['kode_tfk']), 'PIM') !== false) {
                return 'PIM';
            }
        }
        
        // Method 2: Detect based on table source if available
        if (isset($row['source_table'])) {
            if ($row['source_table'] === 'transaksi_faktur_pim') {
                return 'PIM';
            }
        }
        
        // Default to "Cendo & DPE" if no specific detection
        return 'Cendo & DPE';
    }
    
    $secu = new Security;
    $base = new DB;
    $data = new Data;
    $paging = new Paging;
    $conn = $base->open();
    $sistem = $data->sistem('url_sis');

    //ACCESS DATA
    $admin = $secu->injection(@$_COOKIE['adminkuy']);
    $kunci = $secu->injection(@$_COOKIE['kuncikuy']);
    $level = $secu->injection(@$_COOKIE['jeniskuy']);
    $valid = $secu->validadmin($admin, $kunci);

    //POST DATA
    $cari = $secu->injection(@$_GET['caridata']);
    $cari_cabang = $secu->injection(@$_GET['cari_cabang']);
    $id_out = $secu->injection(@$_GET['id_out']);
    $page = $secu->injection(@$_GET['halaman']);
    $maxi = $secu->injection(@$_GET['maximal']);
    $menu = $secu->injection(@$_GET['menudata']);
    $id_apl = $secu->injection(@$_GET['id_apl']);

    // Get parameters for urgent and normal numbering - initialize global counters
    $urgent_start = $secu->injection(@$_GET['urgent_start']) ?: 1;
    $normal_start = $secu->injection(@$_GET['normal_start']) ?: 1;
    $globalUrgentNumber = (int)$urgent_start;
    $globalNormalNumber = (int)$normal_start;
    $sequential_numbering = true; // Always ensure normal numbers follow urgent numbers

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
            B.nama_out LIKE '%$cari%' OR
            B.status_urgent LIKE '%$cari%'
        ) AND A.upload_f_pajak = 'belum' AND A.status_tfk != 'Draft'";

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
    
        // Use the parameters from GET request for initial numbering
        $globalUrgentNumber = (int)$urgent_start;
        $globalNormalNumber = (int)$normal_start;
        
        // Track numbers per cabang
        $cabangCurrentUrgentNumber = [];
        $cabangCurrentNormalNumber = [];
            
            // Loop through each cabang and fetch data from their APIs
            foreach ($all_cabang as $cabang) {
                // Generate encrypt for API authentication
                $tgl = date('Y-m-d');
                $encrypt = md5($tgl . "#" . $cabang['key_apl']);
                
                // Setup API parameters
                $api_params = [
                'caridata' => $cari,
                'cari_cabang' => $cari_cabang,
                'id_out' => ($id_out !== 'All') ? $id_out : '',
                'halaman' => 1,
                'maximal' => 1000,
                'menudata' => $menu,
                'encrypt' => $encrypt,
                'periode_dari' => $periode_dari,
                'periode_sampai' => $periode_sampai,
                'upload_f_pajak' => 'belum',
                'urgent_start' => $urgent_start,
                'normal_start' => $normal_start
            ];
                
                // Skip if no base_url_apl is available
                if (empty($cabang['base_url_apl'])) {
                    continue;
                }
                
                // Build API URL
                $api_url = rtrim($cabang['base_url_apl'], '/') . '/api/getFakturPajak.php?' . http_build_query($api_params);
                
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
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                // Log informasi respons API yang lebih detail
                error_log("API HTTP Code: " . $http_code);
                error_log("API Response: " . substr($api_response, 0, 1000)); // Log 1000 karakter pertama

                if ($curl_error) {
                    error_log("cURL Error: " . $curl_error);
                    continue;
                }

                // Cek apakah respons API adalah JSON valid
                $json_validation_error = null;
                $api_data = json_decode($api_response, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $json_validation_error = json_last_error_msg();
                    error_log("JSON Error: " . $json_validation_error);
                    continue;
                }

                // Verifikasi struktur data
                if (!isset($api_data['data'])) {
                    error_log("API Response missing 'data' key: " . substr(json_encode($api_data), 0, 500));
                    continue;
                }
                
                if (isset($api_data['data']) && is_array($api_data['data'])) {
                    // Track highest numbers used in urgent/normal data
                    $cabang_id = $cabang['id_apl'];
                    if (isset($api_data['urgent_data']) && isset($api_data['urgent_data']['last_number'])) {
                        $cabangCurrentUrgentNumber[$cabang_id] = $api_data['urgent_data']['last_number'];
                    }
                    if (isset($api_data['normal_data']) && isset($api_data['normal_data']['last_number'])) {
                        $cabangCurrentNormalNumber[$cabang_id] = $api_data['normal_data']['last_number'];
                    }
                    
                    // Re-number items with global numbering
                    foreach ($api_data['data'] as &$row) {
                    // PERBAIKAN: Treat 'nanti' as non-priority for numbering (number like normal),
                    // but keep urgent_type='nanti' for UI styling if needed.
                    $isUrgent = false;
                    $urgentType = 'normal';

                    if (isset($row['urgent_flag']) && $row['urgent_flag'] === true) {
                        $urgentType = isset($row['urgent_type']) ? $row['urgent_type'] : 'urgent';
                        // only treat explicit 'urgent' as numbering-priority
                        $isUrgent = ($urgentType === 'urgent');
                    } elseif (isset($row['status_urgent'])) {
                        $urgentValue = strtolower(trim($row['status_urgent']));
                        if ($urgentValue === 'urgent') {
                            $isUrgent = true;
                            $urgentType = 'urgent';
                        } elseif ($urgentValue === 'nanti') {
                            // 'nanti' kept as type for styling but NOT counted as urgent for numbering
                            $isUrgent = false;
                            $urgentType = 'nanti';
                        } else {
                            $isUrgent = false;
                            $urgentType = 'normal';
                        }
                    }

                    // Ensure we preserve and expose the 'urgent_type' and 'status_urgent' coming from API
                    $row['urgent_type'] = $urgentType;
                    if (!isset($row['status_urgent']) || $row['status_urgent'] === '') {
                        $row['status_urgent'] = $urgentType; // ensure frontend can read 'nanti'
                    }
                     
                     // Standarisasi status urgent
                     $row['urgent_flag'] = $isUrgent;
                     // Keep urgent_type (for UI) but DO NOT treat 'nanti' as numbering-priority.
                     $row['urgent_type'] = $urgentType;
                     // Preserve the original semantic ('nanti' stays visible), but urgent_flag controls numbering.
                     $row['status_urgent'] = $urgentType; // 'urgent' / 'nanti' / 'normal'
                     // Flag for styling: orange for 'nanti', red for true urgent
                     // Ensure flag_type always present for frontend styling
                     if ($urgentType === 'nanti') {
                        $row['flag_type'] = "orange_flag";
                     } elseif ($isUrgent) {
                        $row['flag_type'] = "red_flag";
                     } else {
                        $row['flag_type'] = null;
                     }
                     
                     // PERBAIKAN: Memastikan jenis faktur SELALU diatur dengan benar
                     $row['jenis'] = normalizeJenisFaktur($row);
                     

                     // TAMBAHKAN: Pastikan faktur_url memiliki parameter id_apl dari cabang sumber
                     if (isset($row['action']) && isset($row['action']['faktur_url'])) {
                         // Perbarui URL dengan id_apl cabang yang benar
                         $row['action']['faktur_url'] = $sistem . '/laporan/xps/monitoringfp/monitoringfp.php?key=' . $row['id_tfk'] . '&id_apl=' . $cabang['id_apl'];
                     }
                     
                    // Numbering: only true 'urgent' get urgent-numbering/priority.
                    // 'nanti' keeps urgent_type for UI but will be numbered like normal items.
                    if ($isUrgent) {
                        $row['no'] = 'U' . $globalUrgentNumber++;
                        $row['original_no'] = $row['no'];
                    } else {
                        if ($sequential_numbering && $globalNormalNumber < $globalUrgentNumber) {
                            $globalNormalNumber = $globalUrgentNumber;
                        }
                        $row['no'] = $globalNormalNumber++;
                        $row['original_no'] = $row['no'];
                    }
                     
                     // Tambahkan ke data gabungan
                     $combinedData[] = $row;
                 }
                 }
                
            }
            
            // Add local data if needed
            // Your existing local data query can be added here
            
            // Sort combined data by invoice date (descending)
            usort($combinedData, function($a, $b) {
                return strtotime($b['tgl_tfk']) - strtotime($a['tgl_tfk']);
            });
            
            // First sort by urgency (urgent first), then by date
            usort($combinedData, function($a, $b) {
                // Check if row has an urgent number
                $aIsUrgent = isset($a['no']) && strpos($a['no'], 'U') === 0;
                $bIsUrgent = isset($b['no']) && strpos($b['no'], 'U') === 0;
                
                // Urgent items come first
                if ($aIsUrgent && !$bIsUrgent) return -1;
                if (!$aIsUrgent && $bIsUrgent) return 1;
                
                // If both are urgent or both are normal, sort by date
                return strtotime($b['tgl_tfk']) - strtotime($a['tgl_tfk']);
            });
            
            // Apply pagination manually
            $total = count($combinedData);
            $startIndex = ($page - 1) * $maxi;
            $endIndex = min($startIndex + $maxi, $total);
            
            // Prepare the paginated result
            $paginatedData = [];
            $no = $mulai;
    
            for ($i = $startIndex; $i < $endIndex; $i++) {
                if (isset($combinedData[$i])) {
                    // Jangan timpa nomor dengan prefix U untuk item urgent
                    if (!isset($combinedData[$i]['urgent_flag']) || $combinedData[$i]['urgent_flag'] !== true) {
                        $no++;
                        $combinedData[$i]['no'] = $no;
                    }
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
                    'periode_sampai' => $periode_sampai, // Tambahkan parameter periode sampai
                    'urgent_start' => $secu->injection(@$_GET['urgent_start']) ?: 1, // Tambah parameter urgent_start
                    'normal_start' => $secu->injection(@$_GET['normal_start']) ?: 1  // Tambah parameter normal_start
                ];

                // Bangun URL API tujuan
                $api_url = rtrim($row['base_url_apl'], '/') . '/api/getFakturPajak.php?' . http_build_query($api_params);

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

                // Log informasi respons API yang lebih detail
                error_log("API HTTP Code: " . $http_code);
                error_log("API Response: " . substr($api_response, 0, 1000)); // Log 1000 karakter pertama

                if ($curl_error) {
                    error_log("cURL Error: " . $curl_error);
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

                // Cek apakah respons API adalah JSON valid
                $json_validation_error = null;
                $api_data = json_decode($api_response, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $json_validation_error = json_last_error_msg();
                    error_log("JSON Error: " . $json_validation_error);
                    goto output_json;
                }

                // Verifikasi struktur data
                if (!isset($api_data['data'])) {
                    error_log("API Response missing 'data' key: " . substr(json_encode($api_data), 0, 500));
                    goto output_json;
                }
                
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
                    
                    // If action URLs are not provided by the API, create them
                    if (!isset($row['action']) || empty($row['action'])) {
                        $row['action'] = [
                            "sj_url" => $sistem . '/laporan/xps/sjsales/sjsales.php?key=' . $row['id_tfk'],
                            "faktur_url" => $sistem . '/laporan/xps/monitoringfp/monitoringfp.php?key=' . $row['id_tfk'] . '&id_apl=' . $id_apl
                        ];
                    }
                    
                    // If URLs have different domain, update them to point to the current API's domain
                    if (isset($row['action']['sj_url']) && strpos($row['action']['sj_url'], $sistem) === false) {
                        $row['action']['sj_url'] = $sistem . '/laporan/xps/sjsales/sjsales.php?key=' . $row['id_tfk'];
                        $row['action']['faktur_url'] = $sistem . '/laporan/xps/monitoringfp/monitoringfp.php?key=' . $row['id_tfk'] . '&id_apl=' . $id_apl;
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
            $qJumlah = "SELECT COUNT(*) AS total FROM (
    SELECT A.id_tfk
    FROM transaksi_faktur AS A
    LEFT JOIN outlet AS B ON A.id_out = B.id_out
    LEFT JOIN outlet_alamat AS C ON A.id_out = C.id_out
    LEFT JOIN regional_kabupaten AS D ON C.id_rkb = D.id_rkb
    WHERE $where
    
    UNION ALL
    
    SELECT A.id_tfk
    FROM transaksi_faktur_pim AS A
    LEFT JOIN outlet AS B ON A.id_out = B.id_out
    LEFT JOIN outlet_alamat AS C ON A.id_out = C.id_out
    LEFT JOIN regional_kabupaten AS D ON C.id_rkb = D.id_rkb
    WHERE $where
) AS combined_data";

            $stmtJumlah = $conn->prepare($qJumlah);

            if (!empty($id_out) && $id_out !== 'All') {
                $stmtJumlah->bindParam(':id_out', $id_out, PDO::PARAM_STR); // Bind parameter id_out
            }

            $stmtJumlah->execute();
            $jumlah = $stmtJumlah->fetch(PDO::FETCH_ASSOC);
            $total = $jumlah['total'] ?? 0;

            $qMaster = "SELECT
                    A.id_tfk,
                    A.id_out,
                    A.sj_tfk,
                    A.tglsj_tfk,
                    A.po_tfk,
                    A.tglpo_tfk,
                    A.kode_tfk,
                    A.tgl_tfk,
                    A.ppn_tfk,
                    A.total_tfk,
                    A.status_tfk,
                    A.subtot_tfk,
                    A.status_f_pajak,      
                    A.upload_f_pajak,     
                    B.nama_out,
                    B.status_urgent,
                    D.nama_rkb,
                    'Cendo & DPE' AS jenis
                FROM transaksi_faktur AS A
                LEFT JOIN outlet AS B ON A.id_out = B.id_out
                LEFT JOIN outlet_alamat AS C ON A.id_out = C.id_out
                LEFT JOIN regional_kabupaten AS D ON C.id_rkb = D.id_rkb
                WHERE $where
                
    UNION ALL

                SELECT
                    A.id_tfk,
                    A.id_out,
                    A.sj_tfk,
                    A.tglsj_tfk,
                    A.po_tfk,
                    A.tglpo_tfk,
                    A.kode_tfk,
                    A.tgl_tfk,
                    A.ppn_tfk,
                    A.total_tfk,
                    A.status_tfk,
                    A.subtot_tfk,
                    A.status_f_pajak,      
                    A.upload_f_pajak,     
                    B.nama_out,
                    B.status_urgent,
                    D.nama_rkb,
                    'PIM' AS jenis
                FROM transaksi_faktur_pim AS A
                LEFT JOIN outlet AS B ON A.id_out = B.id_out
                LEFT JOIN outlet_alamat AS C ON A.id_out = C.id_out
                LEFT JOIN regional_kabupaten AS D ON C.id_rkb = D.id_rkb
                WHERE $where
                
                ORDER BY 
                    CASE WHEN LOWER(status_urgent) = 'urgent' THEN 0 ELSE 1 END,
                    EXTRACT(YEAR_MONTH FROM tgl_tfk) DESC,
                    tgl_tfk ASC,
                    CAST(sj_tfk AS UNSIGNED) ASC
                LIMIT :mulai, :maxi";

            $master = $conn->prepare($qMaster);

            if (!empty($id_out) && $id_out !== 'All') {
                $master->bindParam(':id_out', $id_out, PDO::PARAM_STR);
            }
            $master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
            $master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
            $master->execute();

            // Pisahkan data menjadi urgent dan normal
            $dataUrgent = [];
            $dataNormal = [];

            // Get parameters for urgent and normal numbering
            $urgent_start = $secu->injection(@$_GET['urgent_start']) ?: 1;
            $normal_start = $secu->injection(@$_GET['normal_start']) ?: 1;

            // Use these parameters for numbering
            $no_urgent = (int)$urgent_start; 
			$no_normal = (int)$normal_start; // Add this line to initialize $no_normal

            // Process all urgent items first to determine the last urgent number used
            $last_urgent_number = $no_urgent - 1; // Initialize as one less than the starting number

            while ($hasil = $master->fetch(PDO::FETCH_ASSOC)) {
                // Create the row data array
                $rowData = [
                    "kode_tfk" => $hasil['kode_tfk'],
                    "tgl_tfk" => $hasil['tgl_tfk'],
                    "nama_cabang" => $nama_cabang,
                    "nama_out" => $hasil['nama_out'],
                    "subtot_tfk" => $data->angka($hasil['subtot_tfk']),
                    "ppn_tfk" => $data->angka($hasil['ppn_tfk']),
                    "total_tfk" => $data->angka($hasil['total_tfk']),
                    "status_f_pajak" => $hasil['status_f_pajak'],
                    "upload_f_pajak" => $hasil['upload_f_pajak'],
                    "status_tfk" => $hasil['status_tfk'], // <-- added
                    "jenis" => $hasil['jenis'] ?? 'Cendo & DPE', // Ensure jenis is set
                    "id_tfk" => $hasil['id_tfk'],
                    "id_out" => $hasil['id_out'],
                    "action" => ($data->akses($admin, $menu, 'A.read_status') === 'Active') ? [
                        "sj_url" => $sistem . '/laporan/xps/sjsales/sjsales.php?key=' . $hasil['id_tfk'],
                        "faktur_url" => $sistem . '/laporan/xps/monitoringfp/monitoringfp.php?key=' . $hasil['id_tfk']
                    ] : []
                ];
                
                // Deteksi urgent: treat only explicit 'urgent' as priority.
                // 'nanti' will not get urgent numbering but will keep urgent_type='nanti' for styling.
                $isUrgent = false;
                $urgentType = 'normal';
                if (isset($hasil['status_urgent'])) {
                    $urgentValue = strtolower(trim($hasil['status_urgent']));
                    if ($urgentValue === 'urgent') {
                        $isUrgent = true;
                        $urgentType = 'urgent';
                    } elseif ($urgentValue === 'nanti') {
                        $isUrgent = false; // not priority for numbering
                        $urgentType = 'nanti';
                    }
                }
                
                if ($isUrgent) {
                    // true urgent - keep in urgent list and number via $no_urgent
                    error_log("LOCAL DATA - URGENT DETECTED for invoice: " . $hasil['kode_tfk'] . ", type: " . $urgentType . ", numbering as: " . $no_urgent);
                    $rowData["no"] = $no_urgent;
                    $rowData["display_no"] = (string)$no_urgent;
                    $rowData["urgent_flag"] = true;
                    $rowData["urgent_type"] = $urgentType;
                    $rowData["flag_type"] = "red_flag";
                    $rowData["urgent_label"] = "URGENT";
                    $rowData["priority"] = "high";
                    $dataUrgent[] = $rowData;
                    $no_urgent++;
                } else {
                    // normal or 'nanti' -> treat numbering as normal sequence,
                    // but keep urgent_type='nanti' for styling if needed
                    error_log("LOCAL DATA - NOT URGENT for invoice: " . $hasil['kode_tfk'] . " (type: " . $urgentType . ")");
                    $rowData["no"] = $no_normal;
                    $rowData["display_no"] = (string)$no_normal;
                    $rowData["urgent_flag"] = false; // not priority for numbering
                    $rowData["urgent_type"] = $urgentType === 'nanti' ? 'nanti' : 'normal';
                    $rowData["flag_type"] = $urgentType === 'nanti' ? 'orange_flag' : null;
                    $rowData["urgent_label"] = $urgentType === 'nanti' ? 'NANTI' : null;
                    $rowData["priority"] = $urgentType === 'nanti' ? 'medium' : 'normal';
                    $dataNormal[] = $rowData;
                    $no_normal++;
                }
            }

            // Now process normal data with sequential numbers after the urgent ones
            $no_normal = $no_urgent; // Nomor normal LANGSUNG melanjutkan nomor urgent
            foreach ($dataNormal as &$item) {
                $item["no"] = $no_normal;
                $item["display_no"] = (string)$no_normal; // Also update display_no to match
                $no_normal++;
            }

            // Gabungkan data urgent (di atas) dengan data normal
            $dataRows = array_merge($dataUrgent, $dataNormal);

            $navi = $paging->myPaging($menu, $total, $maxi, $page);
        }

// Tambahkan di bagian awal setelah mengambil parameter
$urgent_start = $secu->injection(@$_GET['urgent_start']) ?: 1;
$normal_start = $secu->injection(@$_GET['normal_start']) ?: 1;

// Inisialisasi variabel untuk next values
$next_urgent_start = (int)$urgent_start;
$next_normal_start = (int)$normal_start;

    // In the section where you process combined data from all_cabang
    if ($id_apl === 'all_cabang') {
        // --- PERBAIKAN PENOMORAN UNTUK ALL_CABANG ---
        
        // 1. Pisahkan item urgent dan normal
        $urgentItems = [];
        $normalItems = [];
        
        // 2. HANYA gunakan parameter yang dikirim untuk penomoran awal
        // Ini adalah kunci utama untuk penomoran yang konsisten
        $current_urgent = (int)$urgent_start; 
        $current_normal = (int)$normal_start;
        
        // Log nilai awal penomoran
        error_log("ALL_CABANG - PERBAIKAN PENOMORAN - Nilai awal: urgent_start=$urgent_start, normal_start=$normal_start");
        
        // 3. Proses data dari semua cabang
        foreach ($combinedData as $row) {
            // Deteksi status urgent
            // Prefer explicit urgent_type if present, fallback to status_urgent
            $urgentType = 'normal';
            if (isset($row['urgent_type']) && $row['urgent_type'] !== '') {
                $urgentType = strtolower(trim($row['urgent_type']));
            } elseif (isset($row['status_urgent']) && $row['status_urgent'] !== '') {
                $urgentType = strtolower(trim($row['status_urgent']));
            }
            // only 'urgent' means numbering-priority; 'nanti' remains styling-only
            $isUrgent = ($urgentType === 'urgent');
            // Ensure fields exist for frontend
            $row['urgent_type'] = $urgentType;
            if (!isset($row['status_urgent']) || $row['status_urgent'] === '') {
                $row['status_urgent'] = $urgentType;
            }
            
            // Tambahkan ke array yang sesuai
            if ($isUrgent) {
                $urgentItems[] = $row;
            } else {
                $normalItems[] = $row;
            }
        }
        
        // 4. Urutkan data berdasarkan tanggal - newest month/year first, oldest day first
        usort($urgentItems, function($a, $b) {
            $dateA = new DateTime($a['tgl_tfk']);
            $dateB = new DateTime($b['tgl_tfk']);
            
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
            $dateA = new DateTime($a['tgl_tfk']);
            $dateB = new DateTime($b['tgl_tfk']);
            
            // Compare years (descending)
            $yearDiff = (int)$dateB->format('Y') - (int)$dateA->format('Y');
            if ($yearDiff !== 0) return $yearDiff;
            
            // Compare months (descending)
            $monthDiff = (int)$dateB->format('m') - (int)$dateA->format('m');
            if ($monthDiff !== 0) return $monthDiff;
            
            // Compare days (ascending)
            return (int)$dateA->format('d') - (int)$dateB->format('d');
        });
        
        // 5. Terapkan paginasi SEBELUM penomoran
        // Ini sangat penting agar penomoran hanya diberikan pada data yang ditampilkan
        $total = count($urgentItems) + count($normalItems);
        $startIndex = ($page - 1) * $maxi;
        $endIndex = min($startIndex + $maxi, $total);
        
        // Siapkan array untuk paginasi
        $paginatedUrgentItems = [];
        $paginatedNormalItems = [];
        $currentIndex = 0;
        
        // Ambil urgent items sesuai pagination
        foreach ($urgentItems as $item) {
            if ($currentIndex >= $startIndex && $currentIndex < $endIndex) {
                $paginatedUrgentItems[] = $item;
            }
            $currentIndex++;
            
            // Jika sudah cukup untuk halaman ini, keluar dari loop
            if ($currentIndex >= $endIndex) break;
        }
        
        // Jika masih ada ruang, ambil normal items
        if ($currentIndex < $endIndex) {
            foreach ($normalItems as $item) {
                if ($currentIndex >= $startIndex && $currentIndex < $endIndex) {
                    $paginatedNormalItems[] = $item;
                }
                $currentIndex++;
                
                // Jika sudah cukup, keluar dari loop
                if ($currentIndex >= $endIndex) break;
            }
        }
        
        // 6. Terapkan penomoran HANYA untuk data yang akan ditampilkan
        foreach ($paginatedUrgentItems as &$item) {
            $item['no'] = $current_urgent;
            $item['display_no'] = (string)$current_urgent;
            $item['urgent_flag'] = true;
            $current_urgent++;
        }
        unset($item);
        
        // Pastikan penomoran normal melanjutkan dari urgent jika diperlukan
        if ($current_urgent > $current_normal) {
            $current_normal = $current_urgent;
        }
        
        foreach ($paginatedNormalItems as &$item) {
            $item['no'] = $current_normal;
            $item['display_no'] = (string)$current_normal;
            $item['urgent_flag'] = false;
            $current_normal++;
        }
        unset($item);
        
        // 7. Gabungkan data yang sudah diberi nomor
        $paginatedData = array_merge($paginatedUrgentItems, $paginatedNormalItems);
        
        // 8. Setel nilai untuk halaman berikutnya berdasarkan penomoran yang sudah dilakukan
        $next_urgent_start = $current_urgent;
        $next_normal_start = $current_normal;
        
        error_log("ALL_CABANG - PERBAIKAN PENOMORAN - Nilai akhir: next_urgent=$next_urgent_start, next_normal=$next_normal_start");
        
        // 9. Set response values
        $dataRows = $paginatedData;
        $navi = $paging->myPaging($menu, $total, $maxi, $page);
    }

    // Untuk dihasilkan di response
    // Pastikan variabel json hanya diset sekali dengan nilai terakhir
if (!isset($json) || !is_array($json)) {
    $json = [
        "success" => true,
        "message" => "",
        "data" => $dataRows,
        "total" => $total,
        "halaman" => $page,
        "paginasi" => $navi,
        "next_urgent_start" => $next_urgent_start,
        "next_normal_start" => $next_normal_start
    ];
}
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
<?php
// Setelah data dari semua cabang digabungkan:

// Debug: periksa urgent items dari API
$urgentFromAPI = array_filter($api_data['data'], function($item) {
    return isset($item['urgent_flag']) && $item['urgent_flag'] === true;
});

error_log("API {$cabang['nama_apl']} - Total urgent from API: " . count($urgentFromAPI));
if (!empty($urgentFromAPI)) {
    error_log("Sample urgent from API: " . json_encode(reset($urgentFromAPI)));
}

// Debug jumlah urgent final
$urgentItems = array_filter($combinedData, function($item) {
    return isset($item['urgent_flag']) && $item['urgent_flag'] === true;
});

error_log("COMBINED DATA - Total records: " . count($combinedData) . ", Urgent items: " . count($urgentItems));
if (count($urgentItems) > 0) {
    error_log("Sample urgent: " . json_encode(array_slice($urgentItems, 0, 1)));
}

// Urutkan data gabungan: bulan termuda di atas, tapi tanggal tertua di atas untuk bulan yang sama
usort($combinedData, function($a, $b) {
    $dateA = strtotime($a['tgl_tfk'] ?? '0');
    $dateB = strtotime($b['tgl_tfk'] ?? '0');
    
    // Ambil tahun dan bulan untuk kedua tanggal
    $yearMonthA = date('Y-m', $dateA);
    $yearMonthB = date('Y-m', $dateB);
    
    // Pertama bandingkan tahun-bulan (bulan terbaru di atas)
    if ($yearMonthA != $yearMonthB) {
        return strtotime($yearMonthB) - strtotime($yearMonthA); // Bulan termuda dulu
    }
    
    // Jika bulan sama, bandingkan hari (hari tertua dulu)
    return $dateA - $dateB; // Tanggal lama dulu dalam bulan yang sama
});

// Prioritaskan item urgent tanpa mengganggu urutan tanggal
usort($combinedData, function($a, $b) {
    // Ambil tahun, bulan, tanggal untuk perbandingan
    $dateA = strtotime($a['tgl_tfk']);
    $dateB = strtotime($b['tgl_tfk']);
    
    $yearMonthA = date('Y-m', $dateA);
    $yearMonthB = date('Y-m', $dateB);
    $fullDateA = date('Y-m-d', $dateA);
    $fullDateB = date('Y-m-d', $dateB);
    
    // Jika bulan berbeda, pertahankan pengurutan sebelumnya (bulan terbaru di atas)
    if ($yearMonthA != $yearMonthB) {
        return strtotime($yearMonthB) - strtotime($yearMonthA);
    }
    
    // Jika tanggal sama persis, prioritaskan urgent
    if ($fullDateA == $fullDateB) {
        $isUrgentA = isset($a['urgent_flag']) && $a['urgent_flag'] === true ? 0 : 1;
        $isUrgentB = isset($b['urgent_flag']) && $b['urgent_flag'] === true ? 0 : 1;
        return $isUrgentA - $isUrgentB;
    }
    
    // Jika tanggal berbeda, pertahankan urutan tanggal tertua di atas
    return $dateA - $dateB;
});
