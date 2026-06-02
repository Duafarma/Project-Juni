<?php
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Access-Control-Allow-Origin: *');
    header("Content-type: application/json; charset=utf-8");
    echo json_encode(["success" => false, "message" => "Method Not Allowed"]);
    exit;
} else {
    require_once('../config/connection/connection.php');
    require_once('../config/connection/security.php');
    require_once('../config/function/data.php');
    require_once('../config/function/paging.php');
    $secu = new Security;
    $base = new DB;
    $data = new Data;
    $paging = new Paging;
    $conn = $base->open();

    // Validate encryption key
    $encrypt = $secu->injection($_GET['encrypt'] ?? '');
    $tgl = date('Y-m-d');
    $source = $data->self_apl();
    $sourceKey = $source['key_apl'];
    
    if (md5($tgl . "#" . $sourceKey) != $encrypt) {
        http_response_code(401);
        header('Access-Control-Allow-Origin: *');
        header("Content-type: application/json; charset=utf-8");
        echo json_encode([
            "success" => false,
            "message" => "Unauthorized access",
            "data" => [],
            "total" => 0
        ]);
        exit;
    }

    // Validasi parameter
    $cari = $secu->injection($_GET['caridata'] ?? '');
    $cari_cabang = $secu->injection($_GET['cari_cabang'] ?? '');
    $id_out = $secu->injection($_GET['id_out'] ?? ''); 
    $page = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
    $maxi = isset($_GET['maximal']) ? (int)$_GET['maximal'] : 15;
    $menu = $secu->injection($_GET['menudata'] ?? '');
    $mulai = ($page > 1) ? (($page * $maxi) - $maxi) : 0;
    
    // Ambil parameter periode
    $periode_dari = $secu->injection($_GET['periode_dari'] ?? '');
    $periode_sampai = $secu->injection($_GET['periode_sampai'] ?? '');
    
    // Filter berdasarkan status urgent
    $filter_urgent = $secu->injection($_GET['filter_urgent'] ?? 'all');
    
    // Dapatkan parameter untuk penomoran urgent dan normal
    $urgent_start = isset($_GET['urgent_start']) && is_numeric($_GET['urgent_start']) ? (int)$secu->injection($_GET['urgent_start']) : 1;
    $normal_start = isset($_GET['normal_start']) && is_numeric($_GET['normal_start']) ? (int)$secu->injection($_GET['normal_start']) : 1;
    $urgent_prefix = $secu->injection(@$_GET['urgent_prefix']) ?: 'U';
    
    // PENTING: Penomoran berurutan selalu diaktifkan
    $sequential_numbering = true;

    try {
        // Ambil data faktur pajak dari database lokal
        $where = "(
            A.kode_tfk LIKE :cari OR
            A.sj_tfk LIKE :cari OR
            A.po_tfk LIKE :cari OR
            B.nama_out LIKE :cari OR
            B.status_urgent LIKE :cari
        ) AND A.upload_f_pajak = 'belum'";
        
        // Tambahkan filter urgent jika dibutuhkan
        if ($filter_urgent === 'only_urgent') {
            $where .= " AND LOWER(B.status_urgent) = 'urgent'";
        } elseif ($filter_urgent === 'non_urgent') {
            $where .= " AND (B.status_urgent IS NULL OR LOWER(B.status_urgent) != 'urgent')";
        }
        
        // Tambahkan filter berdasarkan id_out jika ada dan bukan "All"
        if (!empty($id_out) && $id_out !== 'All') {
            $where .= " AND A.id_out = :id_out";
        }

        // Add cabang-specific search if parameter is provided
        if (!empty($cari_cabang)) {
            $cabang_where = "(
                B.nama_out LIKE :cari_cabang OR
                A.kode_tfk LIKE :cari_cabang
            )";
            $where = "($where) AND ($cabang_where)";
        }
        
        // Tambahkan filter periode tanggal jika disediakan
        if (!empty($periode_dari)) {
            $where .= " AND A.tgl_tfk >= :periode_dari";
        }
        if (!empty($periode_sampai)) {
            $where .= " AND A.tgl_tfk <= :periode_sampai";
        }
        
        // Hitung jumlah faktur urgent dengan deteksi yang lebih baik
        $qUrgentCount = "SELECT COUNT(*) AS urgent_count FROM (
            SELECT A.id_tfk
            FROM transaksi_faktur AS A
            LEFT JOIN outlet AS B ON A.id_out = B.id_out
            WHERE $where AND LOWER(B.status_urgent) = 'urgent'
            
            UNION ALL
            
            SELECT A.id_tfk
            FROM transaksi_faktur_pim AS A
            LEFT JOIN outlet AS B ON A.id_out = B.id_out
            WHERE $where AND LOWER(B.status_urgent) = 'urgent'
        ) AS combined_urgent";

        
        $stmtUrgentCount = $conn->prepare($qUrgentCount);
        $stmtUrgentCount->bindValue(':cari', '%' . $cari . '%', PDO::PARAM_STR);
        
        if (!empty($id_out) && $id_out !== 'All') {
            $stmtUrgentCount->bindValue(':id_out', $id_out, PDO::PARAM_STR);
        }
        
        if (!empty($cari_cabang)) {
            $stmtUrgentCount->bindValue(':cari_cabang', '%' . $cari_cabang . '%', PDO::PARAM_STR);
        }
        
        // Bind parameter periode
        if (!empty($periode_dari)) {
            $stmtUrgentCount->bindValue(':periode_dari', $periode_dari, PDO::PARAM_STR);
        }
        if (!empty($periode_sampai)) {
            $stmtUrgentCount->bindValue(':periode_sampai', $periode_sampai, PDO::PARAM_STR);
        }
        
        $stmtUrgentCount->execute();
        $urgentResult = $stmtUrgentCount->fetch(PDO::FETCH_ASSOC);
        $urgentCount = $urgentResult['urgent_count'] ?? 0;
        
        // Hitung total faktur
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
        $stmtJumlah->bindValue(':cari', '%' . $cari . '%', PDO::PARAM_STR);
        
        if (!empty($id_out) && $id_out !== 'All') {
            $stmtJumlah->bindValue(':id_out', $id_out, PDO::PARAM_STR);
        }
        
        if (!empty($cari_cabang)) {
            $stmtJumlah->bindValue(':cari_cabang', '%' . $cari_cabang . '%', PDO::PARAM_STR);
        }
        
        // Bind parameter periode
        if (!empty($periode_dari)) {
            $stmtJumlah->bindValue(':periode_dari', $periode_dari, PDO::PARAM_STR);
        }
        if (!empty($periode_sampai)) {
            $stmtJumlah->bindValue(':periode_sampai', $periode_sampai, PDO::PARAM_STR);
        }
        
        $stmtJumlah->execute();
        $jumlah = $stmtJumlah->fetch(PDO::FETCH_ASSOC);
        $total = $jumlah['total'] ?? 0;
        
        // Hitung jumlah faktur normal
        $normalCount = $total - $urgentCount;

        // Peningkatan pengurutan untuk item urgent
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
                EXTRACT(YEAR_MONTH FROM tgl_tfk) DESC, -- Bulan terbaru dulu (DESC)
                tgl_tfk ASC, -- Tanggal lama dulu dalam bulan yang sama (ASC)
                CAST(sj_tfk AS UNSIGNED) ASC
            LIMIT :mulai, :maxi";
        
        $master = $conn->prepare($qMaster);
        $master->bindValue(':cari', '%' . $cari . '%', PDO::PARAM_STR);
        
        if (!empty($id_out) && $id_out !== 'All') {
            $master->bindValue(':id_out', $id_out, PDO::PARAM_STR);
        }
        
        if (!empty($cari_cabang)) {
            $master->bindValue(':cari_cabang', '%' . $cari_cabang . '%', PDO::PARAM_STR);
        }
        
        // Bind parameter periode
        if (!empty($periode_dari)) {
            $master->bindValue(':periode_dari', $periode_dari, PDO::PARAM_STR);
        }
        if (!empty($periode_sampai)) {
            $master->bindValue(':periode_sampai', $periode_sampai, PDO::PARAM_STR);
        }
        
        $master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
        $master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
        $master->execute();

        // Lacak nomor tertinggi yang digunakan
        $highest_urgent_number = $urgent_start - 1;
        $highest_normal_number = $normal_start - 1;

        // Array terpisah untuk item urgent dan normal
        $dataUrgent = [];
        $dataNormal = [];

        // Dapatkan nama cabang dari sistem
        $source = $data->self_apl();
        $nama_cabang = $source['nama_apl'];
        
        // Lacak total nilai untuk item urgent dan normal
        $urgent_total_value = 0;
        $normal_total_value = 0;
        
        // Lacak tanggal faktur untuk analitik
        $urgent_dates = [];
        $normal_dates = [];

        // Lacak format nomor (untuk menangani pagination dengan benar)
        $urgent_numbers = [];
        $normal_numbers = [];

        while ($hasil = $master->fetch(PDO::FETCH_ASSOC)) {
            $sistem = $data->sistem('url_sis');
            
            // Ensure jenis field is properly determined based on source
            $jenis = 'Cendo & DPE'; // Default value
            if (isset($hasil['jenis']) && !empty($hasil['jenis'])) {
                $jenis = $hasil['jenis']; // Use if already provided by query
            } elseif (isset($hasil['kode_tfk'])) {
                // Detect based on invoice code pattern
                if (strpos(strtoupper($hasil['kode_tfk']), 'PIM') !== false) {
                    $jenis = 'PIM';
                }
            }
            
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
                "id_tfk" => $hasil['id_tfk'],
                "jenis" => $jenis, // Set normalized jenis value
                "action" => [
                    "print_url" => $sistem . '/laporan/xps/faktursales/faktursales.php?key=' . $hasil['id_tfk'],
                    "sj_url" => $sistem . '/laporan/xps/sjsales/sjsales.php?key=' . $hasil['id_tfk'],
                    "faktur_url" => $sistem . '/laporan/xps/monitoringfp/monitoringfp.php?key=' . $hasil['id_tfk'] . '&id_apl=' . $source['id_apl']
                ]
            ];

            // Debug nilai status_urgent asli
            $rawUrgentValue = $hasil['status_urgent'] ?? 'NULL';
            error_log("Raw status_urgent value: [$rawUrgentValue]");

            // Deteksi urgent yang lebih baik dengan beberapa nilai valid
            $isUrgent = false;
            if (isset($hasil['status_urgent'])) {
                $isUrgent = strtolower(trim($hasil['status_urgent'])) == 'urgent';
            }
            
            // Dapatkan nilai faktur untuk statistik
            $invoiceValue = floatval(str_replace(',', '', $hasil['subtot_tfk']));
            
            // Simpan tanggal faktur untuk analisis
            $invoiceDate = $hasil['tgl_tfk'];

            if ($isUrgent) {
                // MODIFIED: Now use regular number without U-prefix
                $rowData["no"] = $urgent_start;
                $rowData["numeric_no"] = $urgent_start;
                $rowData["display_no"] = (string)$urgent_start;  // Plain string number
                $rowData["urgent"] = true;
                $rowData["urgent_flag"] = true;
                $rowData["flag_type"] = "red_flag";
                $rowData["urgent_label"] = "URGENT";
                $rowData["priority"] = "high";
                
                // Track numbering
                $urgent_numbers[] = $urgent_start;
                
                // Add to urgent array and increment counter
                $dataUrgent[] = $rowData;
                $urgent_start++;
                $highest_urgent_number = $urgent_start - 1;
                $urgent_total_value += $invoiceValue;
                if ($invoiceDate) $urgent_dates[] = $invoiceDate;
            } else {
                // Penomoran berurutan untuk item normal
                $rowData["no"] = $normal_start;
                $rowData["numeric_no"] = $normal_start;
                $rowData["display_no"] = (string)$normal_start;
                $rowData["urgent"] = false;
                $rowData["urgent_flag"] = false;
                $rowData["flag_type"] = null;
                $rowData["urgent_label"] = null;
                $rowData["urgent_prefix"] = null;
                $rowData["urgent_index"] = null;
                $rowData["priority"] = "normal";
                
                // Lacak nomor normal
                $normal_numbers[] = $normal_start;
                
                // Tambahkan ke array normal dan tambahkan penghitung
                $dataNormal[] = $rowData;
                $normal_start++;
                $highest_normal_number = $normal_start - 1;
                $normal_total_value += $invoiceValue;
                if ($invoiceDate) $normal_dates[] = $invoiceDate;
            }
        }

        // SEBELUM menggabungkan array, pastikan normal_start melanjutkan dari urgent_start
        // Ini penting untuk memastikan penomoran berurutan di semua kasus
        if ($sequential_numbering) {
            // Reset normal_start untuk memastikan urutan yang benar - normal selalu melanjutkan dari urgent
            // Gunakan nilai tertinggi + 1
            $normal_start = $urgent_start; // urgent_start sudah increment di loop sebelumnya

            // Perbarui semua item normal dengan nomor yang benar
            foreach ($dataNormal as &$item) {
                $item["no"] = $normal_start;
                $item["numeric_no"] = $normal_start;
                $item["display_no"] = (string)$normal_start;
                $normal_start++;
                $highest_normal_number = $normal_start - 1;
            }
            unset($item);
        }

        // Gabungkan data urgent (di atas) dengan data normal
        $dataRows = array_merge($dataUrgent, $dataNormal);

        // Hitung statistik rentang tanggal
        $urgentDateRange = [
            'oldest' => !empty($urgent_dates) ? min($urgent_dates) : null,
            'newest' => !empty($urgent_dates) ? max($urgent_dates) : null
        ];
        
        $normalDateRange = [
            'oldest' => !empty($normal_dates) ? min($normal_dates) : null,
            'newest' => !empty($normal_dates) ? max($normal_dates) : null
        ];

        $navi = $paging->myPaging($menu, $total, $maxi, $page);

        $response = [
            "success" => true,
            "message" => "",
            "data" => $dataRows,
            "total" => $total,
            "halaman" => $page,
            "paginasi" => $navi,
            // Tambahkan next_start di level atas untuk kompatibilitas
            "next_urgent_start" => $highest_urgent_number + 1,
            "next_normal_start" => $highest_normal_number + 1,
            // Informasi khusus urgent ditingkatkan
            "urgent_data" => [
                "count" => $urgentCount,
                "percentage" => ($total > 0) ? round(($urgentCount / $total) * 100, 2) : 0,
                "first_number" => $urgent_start,
                "last_number" => $highest_urgent_number,
                "next_start" => $highest_urgent_number + 1,
                "prefix" => $urgent_prefix,
                "numbers_used" => $urgent_numbers,
                "total_value" => $urgent_total_value,
                "date_range" => $urgentDateRange
            ],
            "normal_data" => [
                "count" => $normalCount,
                "percentage" => ($total > 0) ? round(($normalCount / $total) * 100, 2) : 0,
                "first_number" => $normal_start,
                "last_number" => $highest_normal_number,
                "next_start" => $highest_normal_number + 1,
                "numbers_used" => $normal_numbers,
                "total_value" => $normal_total_value,
                "date_range" => $normalDateRange
            ],
            "metadata" => [
                "branch_name" => $nama_cabang,
                "timestamp" => date('Y-m-d H:i:s'),
                "pagination_info" => [
                    "page" => $page,
                    "per_page" => $maxi,
                    "from" => $mulai + 1,
                    "to" => $mulai + count($dataRows)
                ],
                "numbering" => [
                    "urgent_format" => $urgent_prefix . "{n}",
                    "urgent_start" => $urgent_start,
                    "normal_start" => $normal_start
                ],
                "filter_applied" => [
                    "urgent" => $filter_urgent,
                    "period_start" => $periode_dari,
                    "period_end" => $periode_sampai,
                    "outlet" => $id_out,
                    "search_term" => $cari,
                    "branch_search" => $cari_cabang
                ]
            ]
        ];
        
        http_response_code(200);
        header('Access-Control-Allow-Origin: *');
        header("Content-type: application/json; charset=utf-8");
        echo json_encode($response);
        
    } catch (PDOException $e) {
        error_log("Database Error in getFakturPajak.php: " . $e->getMessage());
        http_response_code(500);
        header('Access-Control-Allow-Origin: *');
        header("Content-type: application/json; charset=utf-8");
        echo json_encode([
            "success" => false,
            "message" => "Database Error: " . $e->getMessage(),
            "data" => [],
            "total" => 0
        ]);
    } catch (Exception $e) {
        // Tangani exception lainnya
        error_log("Error in getFakturPajak.php: " . $e->getMessage());
        http_response_code(500);
        header('Access-Control-Allow-Origin: *');
        header("Content-type: application/json; charset=utf-8");
        echo json_encode([
            "success" => false,
            "message" => "Error: " . $e->getMessage(),
            "data" => [],
            "total" => 0
        ]);
    } finally {
        $conn = $base->close();
    }
}
?>