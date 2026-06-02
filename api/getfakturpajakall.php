<?php
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Access-Control-Allow-Origin: *');
    header("Content-type: application/json; charset=utf-8");
    echo json_encode(["success" => false, "message" => "Method Not Allowed"]);
    exit;
}

require_once('../config/connection/connection.php');
require_once('../config/connection/security.php');
require_once('../config/function/data.php');
require_once('../config/function/paging.php');
$secu = new Security;
$base = new DB;
$data = new Data;
$paging = new Paging;
$conn = $base->open();

// Add this to get the sistem URL - THIS WAS MISSING
$sistem = $data->sistem('url_sis');

// Ambil parameter pencarian/filter
$cari = $secu->injection($_GET['caridata'] ?? '');
$cari_cabang = $secu->injection($_GET['cari_cabang'] ?? '');
$id_out = $secu->injection($_GET['id_out'] ?? '');
$page = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$maxi = isset($_GET['maximal']) && $_GET['maximal'] !== '' ? (int)$_GET['maximal'] : 1000;
$menu = $secu->injection($_GET['menudata'] ?? '');
$mulai = ($page > 1) ? (($page * $maxi) - $maxi) : 0;
$periode_dari = $secu->injection($_GET['periode_dari'] ?? '');
$periode_sampai = $secu->injection($_GET['periode_sampai'] ?? '');
$status_dokumen = 'sudah balik'; // Hanya tampilkan faktur yang sudah balik

try {
    // Ambil semua cabang aktif
    $stmtApl = $conn->prepare("SELECT id_apl, base_url_apl, key_apl, self_apl, nama_apl FROM aplikasi WHERE active_apl = 1");
    $stmtApl->execute();
    $aplikasiList = $stmtApl->fetchAll(PDO::FETCH_ASSOC);

    $allData = [];
    $total = 0;
    
    // Debug information
    error_log("Processing " . count($aplikasiList) . " branches");

    foreach ($aplikasiList as $apl) {
        // Debug which branch we're processing
        error_log("Processing branch: " . $apl['nama_apl']);
        
        $params = [
            'caridata' => $cari,
            'cari_cabang' => $cari_cabang,
            'id_out' => $id_out,
            'halaman' => 1,
            'maximal' => $maxi,
            'menudata' => $menu,
            'periode_dari' => $periode_dari,
            'periode_sampai' => $periode_sampai,
            'status_dokumen' => $status_dokumen,
        ];

        if ($apl['self_apl'] == 1) {
            // Query langsung ke database lokal
            $where = "(
                A.kode_tfk LIKE :cari OR
                A.sj_tfk LIKE :cari OR
                A.po_tfk LIKE :cari OR
                B.nama_out LIKE :cari
            ) AND A.status_tfk != 'Draft'"; // REMOVED restriction to only "belum terbit" status

            if (!empty($id_out) && $id_out !== 'All') {
                $where .= " AND A.id_out = :id_out";
            }
            if (!empty($cari_cabang)) {
                $where .= " AND (B.nama_out LIKE :cari_cabang OR A.kode_tfk LIKE :cari_cabang)";
            }
            if (!empty($periode_dari)) {
                $where .= " AND A.tgl_tfk >= :periode_dari";
            }
            if (!empty($periode_sampai)) {
                $where .= " AND A.tgl_tfk <= :periode_sampai";
            }
            $where .= " AND A.status_dokumen = '" . $status_dokumen . "'";

            $qMaster = "SELECT
                    A.id_tfk,
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
                
                ORDER BY tglsj_tfk DESC, CAST(sj_tfk AS UNSIGNED) DESC";
        
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
            $master->execute();

            while ($hasil = $master->fetch(PDO::FETCH_ASSOC)) {
                $allData[] = [
                    "kode_tfk" => $hasil['kode_tfk'],
                    "tgl_tfk" => $hasil['tgl_tfk'],
                    "nama_cabang" => $apl['nama_apl'],
                    "nama_out" => $hasil['nama_out'],
                    "subtot_tfk" => $data->angka($hasil['subtot_tfk']),
                    "ppn_tfk" => $data->angka($hasil['ppn_tfk']),
                    "total_tfk" => $data->angka($hasil['total_tfk']),
                    "status_urgent" => $hasil['status_urgent'] ?? null,
                    "status_tfk" => $hasil['status_tfk'] ?? null,
                    "status_f_pajak" => $hasil['status_f_pajak'],
                    "upload_f_pajak" => $hasil['upload_f_pajak'],
                    "jenis" => $hasil['jenis'] ?? 'Cendo & DPE', // Ensure jenis is set
                    "id_tfk" => $hasil['id_tfk'],
                    "action" => [
                        "print_url" => $sistem . '/laporan/xps/faktursales/faktursales.php?key=' . $hasil['id_tfk'],
                        "sj_url" => $sistem . '/laporan/xps/sjsales/sjsales.php?key=' . $hasil['id_tfk'],
                        "faktur_url" => $sistem . '/laporan/xps/monitoringfp/monitoringfp.php?key=' . $hasil['id_tfk'] . '&branch_id=' . $apl['id_apl']
                    ]
                ];
            }
        } else {
            // Query ke API cabang lain
            $tgl = date('Y-m-d');
            $encrypt = md5($tgl . "#" . $apl['key_apl']);
            $params['encrypt'] = $encrypt;
            $apiUrl = rtrim($apl['base_url_apl'], '/') . '/api/getFakturPajak.php?' . http_build_query($params);
            
            // Debug the API URL
            error_log("Calling API: " . $apiUrl);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 300); // Increased timeout to 5 minutes
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            $response = curl_exec($ch);
            $curlError = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // More detailed error logging
            if ($curlError) {
                error_log("cURL Error for {$apl['nama_apl']}: $curlError");
                continue;
            }

            if ($httpCode != 200) {
                error_log("HTTP Error $httpCode for {$apl['nama_apl']}. Response: " . substr($response, 0, 500));
                continue;
            }
            
            // Check for valid JSON response
            $jsonError = null;
            $apiData = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $jsonError = json_last_error_msg();
                error_log("JSON decode error for {$apl['nama_apl']}: $jsonError. Response: " . substr($response, 0, 500));
                continue;
            }
            
            if (!isset($apiData['data']) || !is_array($apiData['data'])) {
                error_log("Invalid data format from {$apl['nama_apl']}. Response: " . substr(json_encode($apiData), 0, 500));
                continue;
            }
            
            // When processing API results, normalize jenis field
            foreach ($apiData['data'] as $row) {
                // Normalize jenis field
                $row['jenis'] = normalizeJenisFaktur($row);
                
                // Add branch name
                $row['nama_cabang'] = $apl['nama_apl'];
                $allData[] = $row;
            }
        }
    }

    // Ubah pengurutan dari tanggal terbaru ke tanggal tertua
    // Urutkan data: bulan termuda di atas, tapi tanggal tertua di atas untuk bulan yang sama
    usort($allData, function($a, $b) {
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

    // Pagination manual
    $total = count($allData);
    $start = $mulai;
    $end = min($start + $maxi, $total);
    $dataRows = array_slice($allData, $start, $maxi);

    // Debug final data count
    error_log("Total rows collected: $total");
    
    $navi = $paging->myPaging($menu, $total, $maxi, $page);

    $response = [
        "success" => true,
        "message" => "",
        "data" => $dataRows,
        "total" => $total,
        "halaman" => $page,
        "paginasi" => $navi
    ];

    http_response_code(200);
    header('Access-Control-Allow-Origin: *');
    header("Content-type: application/json; charset=utf-8");
    
    // Ensure we output valid JSON
    $json_response = json_encode($response);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Error encoding JSON response: " . json_last_error_msg());
        echo json_encode([
            "success" => false,
            "message" => "Error generating response: " . json_last_error_msg(),
            "data" => [],
            "total" => 0
        ]);
    } else {
        echo $json_response;
    }
    
} catch (Exception $e) {
    error_log("Exception in getfakturpajakall.php: " . $e->getMessage());
    http_response_code(500);
    header('Access-Control-Allow-Origin: *');
    header("Content-type: application/json; charset=utf-8");
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage(),
        "data" => [],
        "total" => 0
    ]);
} finally {
    $conn = $base->close();
}

/**
 * Normalize faktur type based on available data
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
?>