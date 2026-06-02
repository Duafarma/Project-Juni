<?php
// ==============================================================================
// API ENDPOINT: GET PENGIRIMAN DATA
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
$cari = $secu->injection(@$_GET['caridata']);
$page = $secu->injection(@$_GET['halaman']);
$maxi = $secu->injection(@$_GET['maximal']);
$menu = $secu->injection(@$_GET['menudata']);
$mulai = ($page > 1) ? (($page * $maxi) - $maxi) : 0;

// 6. AUTHENTICATION
$encrypt = $secu->injection($_GET['encrypt']);
$source = $data->self_apl();
$sourceKey = $source['key_apl'];

if (md5($tgl . "#" . $sourceKey) == $encrypt) {
    
    // 7. PREPARE SEARCH PARAMETERS
    $searchTerm = '%' . $cari . '%';
    
    // 8. BUILD SQL QUERY WITH UNION ALL
    $qMaster = "
        SELECT 
            'local' as source,
            A.created_at,
            A.updated_at,
            A.id_tfkkb,
            A.status_tfkkb,
            A.ket_tfkkb,
            A.tgl_tfkkb,
            B.id_tfk,
            B.kode_tfk,
            B.sj_tfk,
            B.tgl_tfk,
            B.sediaan,
            B.jml_sediaan,
            B.ccp,
            C.nama_out,
            D.nama_adm,
            'transaksi_faktur' as source_table
        FROM
            transaksi_faktur_kirim_b AS A
        LEFT JOIN transaksi_faktur AS B ON
            B.id_tfk = A.id_tfk
        LEFT JOIN outlet AS C ON
            C.id_out = B.id_out
        LEFT JOIN adminz AS D ON
            A.id_adm = D.id_adm
        WHERE
            (A.id_tfkkb LIKE :cari1
            OR A.id_adm LIKE :cari2
            OR A.id_tfk LIKE :cari3
            OR B.kode_tfk LIKE :cari4
            OR C.nama_out LIKE :cari5
            OR D.nama_adm LIKE :cari6)
            AND B.id_tfk IS NOT NULL
            
        UNION ALL
        
        SELECT 
            'local' as source,
            A.created_at,
            A.updated_at,
            A.id_tfkkb,
            A.status_tfkkb,
            A.ket_tfkkb,
            A.tgl_tfkkb,
            P.id_tfk,
            P.kode_tfk,
            P.sj_tfk,
            P.tgl_tfk,
            P.sediaan,
            P.jml_sediaan,
            P.ccp,
            C.nama_out,
            D.nama_adm,
            'transaksi_faktur_pim' as source_table
        FROM
            transaksi_faktur_kirim_b AS A
        LEFT JOIN transaksi_faktur_pim AS P ON
            P.id_tfk = A.id_tfk
        LEFT JOIN outlet AS C ON
            C.id_out = P.id_out
        LEFT JOIN adminz AS D ON
            A.id_adm = D.id_adm
        WHERE
            (A.id_tfkkb LIKE :cari7
            OR A.id_adm LIKE :cari8
            OR A.id_tfk LIKE :cari9
            OR P.kode_tfk LIKE :cari10
            OR C.nama_out LIKE :cari11
            OR D.nama_adm LIKE :cari12)
            AND P.id_tfk IS NOT NULL
            
        UNION ALL
        
        SELECT 
            'local' as source,
            A.created_at,
            A.updated_at,
            A.id_tfkkb,
            A.status_tfkkb,
            A.ket_tfkkb,
            A.tgl_tfkkb,
            CTF.id_tfk,
            CTF.kode_tfk,
            CTF.sj_tfk,
            CTF.tgl_tfk,
            CTF.sediaan,
            CTF.jml_sediaan,
            CTF.ccp,
            OC.nama_out,
            AD.nama_adm,
            'transaksi_faktur_c' as source_table
        FROM
            transaksi_faktur_kirim_b AS A
        LEFT JOIN transaksi_faktur_c AS CTF ON
            CTF.id_tfk = A.id_tfk
        LEFT JOIN outlet AS OC ON
            OC.id_out = CTF.id_out
        LEFT JOIN adminz AS AD ON
            A.id_adm = AD.id_adm
        WHERE
            (A.id_tfkkb LIKE :cari13
            OR A.id_adm LIKE :cari14
            OR A.id_tfk LIKE :cari15
            OR CTF.kode_tfk LIKE :cari16
            OR OC.nama_out LIKE :cari17
            OR AD.nama_adm LIKE :cari18)
            AND CTF.id_tfk IS NOT NULL
            
        ORDER BY
            created_at DESC,
            tgl_tfk DESC
        LIMIT :mulai, :maxi
    ";
    
    // 9. PREPARE AND EXECUTE QUERY
    try {
        $master = $conn->prepare($qMaster);
        
        // Bind search parameters
        $master->bindParam(':cari1', $searchTerm, PDO::PARAM_STR);
        $master->bindParam(':cari2', $searchTerm, PDO::PARAM_STR);
        $master->bindParam(':cari3', $searchTerm, PDO::PARAM_STR);
        $master->bindParam(':cari4', $searchTerm, PDO::PARAM_STR);
        $master->bindParam(':cari5', $searchTerm, PDO::PARAM_STR);
        $master->bindParam(':cari6', $searchTerm, PDO::PARAM_STR);
        $master->bindParam(':cari7', $searchTerm, PDO::PARAM_STR);
        $master->bindParam(':cari8', $searchTerm, PDO::PARAM_STR);
        $master->bindParam(':cari9', $searchTerm, PDO::PARAM_STR);
        $master->bindParam(':cari10', $searchTerm, PDO::PARAM_STR);
        $master->bindParam(':cari11', $searchTerm, PDO::PARAM_STR);
        $master->bindParam(':cari12', $searchTerm, PDO::PARAM_STR);
        $master->bindParam(':cari13', $searchTerm, PDO::PARAM_STR);
        $master->bindParam(':cari14', $searchTerm, PDO::PARAM_STR);
        $master->bindParam(':cari15', $searchTerm, PDO::PARAM_STR);
        $master->bindParam(':cari16', $searchTerm, PDO::PARAM_STR);
        $master->bindParam(':cari17', $searchTerm, PDO::PARAM_STR);
        $master->bindParam(':cari18', $searchTerm, PDO::PARAM_STR);
        
        // Bind pagination parameters
        $master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
        $master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
        
        $master->execute();
        
        // 10. PROCESS RESULTS
        if ($master) {
            $rawResults = $master->fetchAll(PDO::FETCH_ASSOC);
            
            // Format results for better display
            $formattedResults = [];
            foreach ($rawResults as $row) {
                // PERBAIKAN: Pastikan created_at dan updated_at ada dan valid
                $created_at = isset($row['created_at']) ? $row['created_at'] : '';
                $updated_at = isset($row['updated_at']) ? $row['updated_at'] : '';
                
                // Jika created_at kosong atau invalid, gunakan tgl_tfkkb sebagai fallback
                if (empty($created_at) || $created_at == '0000-00-00 00:00:00' || $created_at == '1970-01-01 00:00:00') {
                    $created_at = !empty($row['tgl_tfkkb']) ? $row['tgl_tfkkb'] : '';
                }
                
                $formattedResults[] = [
                    'source' => $row['source'],
                    'id_tfkkb' => $row['id_tfkkb'],
                    'id_tfk' => $row['id_tfk'],
                    'kode_tfk' => $row['kode_tfk'],
                    'sj_tfk' => $row['sj_tfk'],
                    'tgl_tfk' => $row['tgl_tfk'],
                    'tgl_tfkkb' => $row['tgl_tfkkb'],
                    'status_tfkkb' => $row['status_tfkkb'],
                    'ket_tfkkb' => $row['ket_tfkkb'],
                    'nama_out' => $row['nama_out'],
                    'nama_adm' => $row['nama_adm'],
                    'sediaan' => $row['sediaan'],
                    'jml_sediaan' => $row['jml_sediaan'],
                    'ccp' => isset($row['ccp']) ? $row['ccp'] : '',
                    'source_table' => $row['source_table'],
                    'created_at' => $created_at, // PERBAIKAN: Gunakan created_at yang sudah diperiksa
                    'updated_at' => $updated_at, // PERBAIKAN: Gunakan updated_at yang sudah diperiksa
                    'formatted_sediaan' => !empty($row['sediaan']) ? $row['sediaan'] : '-',
                    'formatted_jml_sediaan' => !empty($row['jml_sediaan']) ? number_format($row['jml_sediaan'], 0, ',', '.') : '-'
                ];
            }
            
            $hasil = $formattedResults;
            http_response_code(200);
        } else {
            $hasil = "Database query failed";
            http_response_code(500);
        }
        
    } catch (PDOException $e) {
        $hasil = "Database error: " . $e->getMessage();
        http_response_code(500);
    }
    
} else {
    // 11. UNAUTHORIZED ACCESS
    $hasil = "Unauthorized - Invalid encryption";
    http_response_code(401);
}

// 12. CLOSE CONNECTION
$conn = $base->close();

// 13. SEND RESPONSE
header('Access-Control-Allow-Origin: *');
header("Content-type: application/json; charset=utf-8");
echo json_encode([
    "status" => http_response_code() == 200 ? "success" : "error",
    "result" => $hasil,
    "total_records" => is_array($hasil) ? count($hasil) : 0,
    "search_term" => $cari,
    "page" => $page,
    "max_per_page" => $maxi,
    "start_from" => $mulai
]);
?>
