<?php
// ==============================================================================
// API ENDPOINT: GET HOME KURIR DATA - FILTER BY ADMIN SESSION
// ==============================================================================

// 1. VALIDASI REQUEST METHOD
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Access-Control-Allow-Origin: *');
    header("Content-type: application/json; charset=utf-8");
    echo json_encode(["error" => "Method Not Allowed"]);
    exit;
}

// 2. INCLUDE DEPENDENCIES
require_once('../config/connection/connection.php');
require_once('../config/connection/security.php');
require_once('../config/function/data.php');

// 3. INITIALIZE OBJECTS
$secu = new Security;
$base = new DB;
$data = new Data;

// 4. SETUP VARIABLES
$tgl = date('Y-m-d');
$conn = $base->open();
$hasil = "Error";

// 5. SANITIZE INPUT PARAMETERS
$nama_adm = $secu->injection(@$_GET['nama_adm']);
$encrypt = $secu->injection(@$_GET['encrypt']);
$local_only = $secu->injection(@$_GET['local_only']);

// Validasi nama_adm wajib ada
if(empty($nama_adm)) {
    http_response_code(400);
    header('Access-Control-Allow-Origin: *');
    header("Content-type: application/json; charset=utf-8");
    echo json_encode([
        "status" => "error",
        "message" => "Nama Admin is required",
        "data" => []
    ]);
    exit;
}

// Debug log untuk memastikan parameter diterima
error_log("getHomeKurir.php called with nama_adm: " . $nama_adm . ", local_only: " . $local_only);

// 6. AUTHENTICATION
$source = $data->self_apl();
$sourceKey = $source['key_apl'];

if (md5($tgl . "#" . $sourceKey) == $encrypt) {
    
    try {
        // 7. QUERY FOR REGULAR INVOICES (TRANSAKSI_FAKTUR) - FILTERED BY ADMIN
        $query_regular = "SELECT
                A.*,
                B.sj_tfk,
                B.kode_tfk,
                B.tgl_tfk,
                C.nama_out,
                D.nama_adm,
                D.id_adm,
                'transaksi_faktur' AS source_table
            FROM
                transaksi_faktur_kirim_f AS A
            LEFT JOIN transaksi_faktur AS B ON B.id_tfk = A.id_tfk
            LEFT JOIN outlet AS C ON C.id_out = B.id_out
            LEFT JOIN adminz AS D ON A.id_adm = D.id_adm
            WHERE
                A.status_tfkkf = 'Belum Dikirim'
                AND D.nama_adm = :nama_adm
                AND B.id_tfk IS NOT NULL
            ORDER BY A.created_at DESC";
        
        $master_regular = $conn->prepare($query_regular);
        $master_regular->bindParam(':nama_adm', $nama_adm, PDO::PARAM_STR);
        $master_regular->execute();
        $results_regular = $master_regular->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug log untuk melihat hasil query
        error_log("Query regular results for nama_adm '$nama_adm': " . count($results_regular) . " rows");
        
        // 8. QUERY FOR PIM INVOICES - FILTERED BY ADMIN
        $query_pim = "SELECT
                A.*,
                B.sj_tfk,
                B.kode_tfk,
                B.tgl_tfk,
                C.nama_out,
                D.nama_adm,
                'transaksi_faktur_pim' AS source_table
            FROM
                transaksi_faktur_kirim_f AS A
            LEFT JOIN transaksi_faktur_pim AS B ON B.id_tfk = A.id_tfk
            LEFT JOIN outlet AS C ON C.id_out = B.id_out
            LEFT JOIN adminz AS D ON A.id_adm = D.id_adm
            WHERE
                D.nama_adm = :nama_adm
                AND B.id_tfk IS NOT NULL
                AND A.status_tfkkf = 'Belum Dikirim'
            ORDER BY A.created_at DESC";
        
        $master_pim = $conn->prepare($query_pim);
        $master_pim->bindParam(':nama_adm', $nama_adm, PDO::PARAM_STR);
        $master_pim->execute();
        $results_pim = $master_pim->fetchAll(PDO::FETCH_ASSOC);
        
        // 9. QUERY FOR FAKTUR C - FILTERED BY ADMIN
        $query_c = "SELECT
                A.*,
                B.sj_tfk,
                B.kode_tfk,
                B.tgl_tfk,
                C.nama_out,
                D.nama_adm,
                'transaksi_faktur_c' AS source_table
            FROM
                transaksi_faktur_kirim_f AS A
            LEFT JOIN transaksi_faktur_c AS B ON B.id_tfk = A.id_tfk
            LEFT JOIN outlet AS C ON C.id_out = B.id_out
            LEFT JOIN adminz AS D ON A.id_adm = D.id_adm
            WHERE
                D.nama_adm = :nama_adm
                AND B.id_tfk IS NOT NULL
                AND A.status_tfkkf = 'Belum Dikirim'
            ORDER BY A.created_at DESC";
        
        $master_c = $conn->prepare($query_c);
        $master_c->bindParam(':nama_adm', $nama_adm, PDO::PARAM_STR);
        $master_c->execute();
        $results_c = $master_c->fetchAll(PDO::FETCH_ASSOC);
        
        // 10. COMBINE LOCAL RESULTS
        $all_results = array_merge($results_regular, $results_pim, $results_c);
        
        // 11. SORT BY CREATED_AT
        usort($all_results, function($a, $b) {
            $a_time = !empty($a['created_at']) ? strtotime($a['created_at']) : 0;
            $b_time = !empty($b['created_at']) ? strtotime($b['created_at']) : 0;
            return $b_time - $a_time;
        });
        
        // 12. ADD SOURCE BRANCH INFO TO LOCAL RESULTS
        $branch_info = $data->self_apl();
        foreach ($all_results as &$result) {
            $result['id_apl'] = $branch_info['id_apl'];
            $result['nama_apl'] = $branch_info['nama_apl'];
            $result['branch_source'] = 'local';
            
            // Format dates for consistency
            if (!empty($result['created_at'])) {
                $timestamp = strtotime($result['created_at']);
                if ($timestamp !== false) {
                    $result['created_at_formatted'] = date('d-m-Y H:i', $timestamp);
                }
            }
            
            // Add keycodes for actions
            if (!empty($result['id_tfkkf'])) {
                $id_tfkkf = $result['id_tfkkf'];
                $source = !empty($result['source_table']) ? $result['source_table'] : 'transaksi_faktur';
                $result['keycode_done'] = base64_encode($id_tfkkf . '|' . $source . '|done');
                $result['keycode_cancel'] = base64_encode($id_tfkkf . '|' . $source . '|cancel');
            }
        }
        
        // 13. HANYA RETURN DATA LOCAL JIKA INI DIPANGGIL DARI CABANG LAIN
        if ($local_only === 'true') {
            $hasil = [
                "status" => "success",
                "data" => $all_results,
                "timestamp" => date('Y-m-d H:i:s'),
                "filtered_by_admin" => $nama_adm,
                "total_records" => count($all_results)
            ];
            http_response_code(200);
        } else {
            // 14. GET BRANCH CONNECTIONS - HANYA JIKA BUKAN LOCAL_ONLY
            $branch_query = $conn->prepare("SELECT id_apl, nama_apl, base_url_apl, key_apl FROM aplikasi WHERE active_apl = 1 AND id_apl != :current_id");
            $branch_query->bindParam(':current_id', $branch_info['id_apl'], PDO::PARAM_STR);
            $branch_query->execute();
            $branches = $branch_query->fetchAll(PDO::FETCH_ASSOC);
            
            // 15. FETCH DATA FROM REMOTE BRANCHES - DENGAN FILTER ADMIN
            $remote_results = [];
            foreach ($branches as $branch) {
                $branch_encrypt = md5($tgl . "#" . $branch['key_apl']);
                $api_url = rtrim($branch['base_url_apl'], '/') . '/api/getHomeKurir.php';
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $api_url . '?nama_adm=' . urlencode($nama_adm) . '&encrypt=' . $branch_encrypt . '&local_only=true');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                
                $response = curl_exec($ch);
                $curl_error = curl_error($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($http_code == 200 && !empty($response)) {
                    $branch_data = json_decode($response, true);
                    if (isset($branch_data['status']) && $branch_data['status'] == 'success' && !empty($branch_data['data'])) {
                        // Add branch info to each record - HAPUS FILTER ADMIN KARENA SUDAH DIFILTER DI QUERY
                        foreach ($branch_data['data'] as &$item) {
                            $item['id_apl'] = $branch['id_apl'];
                            $item['nama_apl'] = $branch['nama_apl'];
                            $item['branch_source'] = 'remote';
                            $remote_results[] = $item;
                        }
                    }
                }
            }
            
            // 16. COMBINE LOCAL AND REMOTE RESULTS
            $combined_results = array_merge($all_results, $remote_results);
            
            // 17. SORT FINAL RESULTS BY DATE (NEWEST FIRST)
            usort($combined_results, function($a, $b) {
                $a_time = !empty($a['created_at']) ? strtotime($a['created_at']) : 0;
                $b_time = !empty($b['created_at']) ? strtotime($b['created_at']) : 0;
                return $b_time - $a_time;
            });
            
            $hasil = $combined_results;
            http_response_code(200);
        }
        
    } catch (PDOException $e) {
        $hasil = "Database error: " . $e->getMessage();
        http_response_code(500);
    }
    
} else {
    // UNAUTHORIZED ACCESS
    $hasil = "Unauthorized - Invalid encryption";
    http_response_code(401);
}

// CLOSE CONNECTION
$conn = $base->close();

// SEND RESPONSE
header('Access-Control-Allow-Origin: *');
header("Content-type: application/json; charset=utf-8");

// Filter final untuk memastikan hanya data dengan nama_adm yang sesuai
if (is_array($hasil) && isset($hasil['data'])) {
    $filtered_data = array_filter($hasil['data'], function($item) use ($nama_adm) {
        return isset($item['nama_adm']) && $item['nama_adm'] === $nama_adm;
    });
    
    // Langsung kembalikan data terfilter tanpa wrapping tambahan
    echo json_encode([
        "status" => "success",
        "data" => array_values($filtered_data),
        "timestamp" => date('Y-m-d H:i:s'),
        "filtered_by_admin" => $nama_adm,
        "total_records" => count($filtered_data)
    ]);
    
} elseif (is_array($hasil)) {
    $filtered_data = array_filter($hasil, function($item) use ($nama_adm) {
        return isset($item['nama_adm']) && $item['nama_adm'] === $nama_adm;
    });
    
    echo json_encode([
        "status" => "success",
        "data" => array_values($filtered_data),
        "timestamp" => date('Y-m-d H:i:s'),
        "filtered_by_admin" => $nama_adm,
        "total_records" => count($filtered_data)
    ]);
    
} else {
    // Jika hasil bukan array, kemungkinan error
    echo json_encode([
        "status" => "error",
        "message" => $hasil,
        "timestamp" => date('Y-m-d H:i:s')
    ]);
}
?>