<?php
/**
 * API untuk query data detail ranalis berdasarkan cabang/aplikasi
 * Mengikuti pola API yang sudah ada di sistem
 */

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Access-Control-Allow-Origin: *');
    header("Content-type: application/json; charset=utf-8");
    echo json_encode(["result" => "Method Not Allowed"]);
    exit;
}

require_once('../config/connection/connection.php');
require_once('../config/connection/security.php');
require_once('../config/function/data.php');

$secu = new Security;
$base = new DB;
$data = new Data;

$conn = $base->open();
$hasil = "Error";

// GET parameters
$idApl = isset($_GET['id_apl']) ? $secu->injection($_GET['id_apl']) : null;
$tipe = isset($_GET['tipe']) ? $secu->injection($_GET['tipe']) : null;
$ofcode = isset($_GET['ofcode']) ? $secu->injection($_GET['ofcode']) : null;
$tahun = isset($_GET['tahun']) ? $secu->injection($_GET['tahun']) : date('Y');
$bulan = isset($_GET['bulan']) ? $secu->injection($_GET['bulan']) : date('m');
$cari = isset($_GET['cari']) ? $secu->injection($_GET['cari']) : '';
$page = isset($_GET['page']) ? (int)$secu->injection($_GET['page']) : 1;

if (!$idApl || !$tipe || !$ofcode) {
    http_response_code(400);
    echo json_encode(["result" => "Parameter id_apl, tipe, atau ofcode tidak ditemukan."]);
    exit;
}

try {
    // Ambil informasi dari tabel aplikasi
    $queryAplikasi = "
        SELECT base_url_apl, key_apl, self_apl, nama_apl
        FROM aplikasi
        WHERE id_apl = :id_apl AND active_apl = 1
    ";
    $stmtAplikasi = $conn->prepare($queryAplikasi);
    $stmtAplikasi->bindParam(':id_apl', $idApl, PDO::PARAM_STR);
    $stmtAplikasi->execute();
    $aplikasi = $stmtAplikasi->fetch(PDO::FETCH_ASSOC);

    if (!$aplikasi) {
        throw new Exception("Aplikasi dengan id_apl tersebut tidak ditemukan.");
    }

    $baseUrl = $aplikasi['base_url_apl'];
    $keyApl = $aplikasi['key_apl'];
    $selfApl = $aplikasi['self_apl'];

    // Jika aplikasi lokal (self_apl == 1), query database lokal
    if ($selfApl == 1) {
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        // Tentukan tabel berdasarkan tipe
        $tabel = $tipe === 'cendo' ? 'transaksi_faktur' : 'transaksi_faktur_pim';

        // Hitung bulan lalu
        $bulanLalu = (int)$bulan - 1;
        $tahunLalu = $tahun;
        if ($bulanLalu < 1) {
            $bulanLalu = 12;
            $tahunLalu = (int)$tahun - 1;
        }

        // Query untuk mendapatkan SEMUA outlet dulu (no pagination yet, untuk filter)
        $queryAllData = $conn->prepare("SELECT 
            B.id_out,
            B.nama_out,
            COALESCE(SUM(A.subtot_tfk), 0) as total_penjualan
        FROM outlet AS B
        LEFT JOIN $tabel AS A ON B.id_out = A.id_out 
            AND YEAR(CASE WHEN A.tgl_tfk IS NULL OR A.tgl_tfk = '0000-00-00' THEN A.created_at ELSE A.tgl_tfk END) = :tahun
            AND MONTH(CASE WHEN A.tgl_tfk IS NULL OR A.tgl_tfk = '0000-00-00' THEN A.created_at ELSE A.tgl_tfk END) = :bulan
        WHERE B.ofcode_out = :ofcode
        " . (!empty($cari) ? "AND B.nama_out LIKE :cari" : "") . "
        GROUP BY B.id_out, B.nama_out");
        
        $queryAllData->bindParam(':tahun', $tahun, PDO::PARAM_STR);
        $queryAllData->bindParam(':bulan', $bulan, PDO::PARAM_STR);
        $queryAllData->bindParam(':ofcode', $ofcode, PDO::PARAM_STR);
        if (!empty($cari)) {
            $cariParam = "%$cari%";
            $queryAllData->bindParam(':cari', $cariParam, PDO::PARAM_STR);
        }
        $queryAllData->execute();

        $allDataWithFilter = [];
        $total_order = 0;
        $total_belum_order = 0;
        $total_penjualan_keseluruhan = 0;
        
        while ($row = $queryAllData->fetch(PDO::FETCH_ASSOC)) {
            // Query penjualan bulan lalu untuk outlet ini
            $queryBulanLalu = $conn->prepare("SELECT COALESCE(SUM(A.subtot_tfk), 0) as total_bulan_lalu
            FROM $tabel AS A
            WHERE A.id_out = :id_out
            AND YEAR(CASE WHEN A.tgl_tfk IS NULL OR A.tgl_tfk = '0000-00-00' THEN A.created_at ELSE A.tgl_tfk END) = :tahun_lalu
            AND MONTH(CASE WHEN A.tgl_tfk IS NULL OR A.tgl_tfk = '0000-00-00' THEN A.created_at ELSE A.tgl_tfk END) = :bulan_lalu");
            
            $queryBulanLalu->bindParam(':id_out', $row['id_out'], PDO::PARAM_STR);
            $queryBulanLalu->bindParam(':tahun_lalu', $tahunLalu, PDO::PARAM_STR);
            $queryBulanLalu->bindParam(':bulan_lalu', $bulanLalu, PDO::PARAM_INT);
            $queryBulanLalu->execute();
            $dataBulanLalu = $queryBulanLalu->fetch(PDO::FETCH_ASSOC);
            $totalBulanLalu = (float)$dataBulanLalu['total_bulan_lalu'];
            
            // Summary calculations (before filter)
            $total_penjualan_keseluruhan += $row['total_penjualan'];
            if ($row['total_penjualan'] > 0) {
                $total_order++;
            } else {
                $total_belum_order++;
            }
            
            // Filter: hanya simpan data yang ada angka di KEDUA bulan
            if ($totalBulanLalu > 0 && $row['total_penjualan'] > 0) {
                $allDataWithFilter[] = array(
                    'id_out' => $row['id_out'],
                    'nama_out' => $row['nama_out'],
                    'total_penjualan' => $row['total_penjualan'],
                    'total_bulan_lalu' => $totalBulanLalu
                );
            }
        }

        // Sort by total_penjualan DESC
        usort($allDataWithFilter, function($a, $b) {
            return $b['total_penjualan'] - $a['total_penjualan'];
        });

        // Hitung total setelah filter
        $total = count($allDataWithFilter);
        $total_pages = ceil($total / $limit);

        // Apply pagination
        $paginatedData = array_slice($allDataWithFilter, $offset, $limit);

        // Build final result
        $dataResult = [];
        $no = $offset;
        foreach ($paginatedData as $item) {
            $no++;
            
            // Hitung selisih dan persentase
            $selisih = $item['total_penjualan'] - $item['total_bulan_lalu'];
            $persentase = 0;
            if ($item['total_bulan_lalu'] > 0) {
                $persentase = (($item['total_penjualan'] - $item['total_bulan_lalu']) / $item['total_bulan_lalu']) * 100;
            } elseif ($item['total_penjualan'] > 0) {
                $persentase = 100;
            }
            
            $dataResult[] = array(
                'no' => $no,
                'nama_out' => $item['nama_out'],
                'total_penjualan' => $item['total_penjualan'],
                'total_penjualan_formatted' => number_format($item['total_penjualan'], 0, ',', '.'),
                'total_bulan_lalu' => $item['total_bulan_lalu'],
                'total_bulan_lalu_formatted' => number_format($item['total_bulan_lalu'], 0, ',', '.'),
                'selisih' => $selisih,
                'selisih_formatted' => number_format($selisih, 0, ',', '.'),
                'persentase' => round($persentase, 2)
            );
        }

        $hasil = [
            "result" => [
                'data' => $dataResult,
                'total' => $total,
                'page' => $page,
                'total_pages' => $total_pages,
                'total_order' => $total_order,
                'total_belum_order' => $total_belum_order,
                'total_penjualan_keseluruhan' => $total_penjualan_keseluruhan
            ],
            "source" => [
                "type" => "database",
                "name" => $aplikasi['nama_apl'],
                "id" => $idApl
            ]
        ];

    } else {
        // Jika aplikasi remote, call API eksternal
        $tgl = date('Y-m-d');
        $encrypt = md5($tgl . "#" . $keyApl);

        $apiUrl = rtrim($baseUrl, '/') . "/api/getRanalisDetailData.php?encrypt=" . $encrypt 
            . "&id_apl=" . urlencode($idApl) 
            . "&tipe=" . urlencode($tipe)
            . "&ofcode=" . urlencode($ofcode)
            . "&tahun=" . urlencode($tahun)
            . "&bulan=" . urlencode($bulan)
            . "&cari=" . urlencode($cari)
            . "&page=" . urlencode($page);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Exception("CURL Error: " . $curlError);
        }

        $apiData = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Gagal memproses data dari API: " . json_last_error_msg());
        }

        if (!$apiData || !isset($apiData['result'])) {
            throw new Exception("Data dari API tidak valid.");
        }

        $hasil = [
            "result" => $apiData['result'],
            "source" => [
                "type" => "api",
                "name" => $aplikasi['nama_apl'],
                "id" => $idApl
            ]
        ];
    }

    http_response_code(200);

} catch (Exception $e) {
    $hasil = [
        "error" => $e->getMessage(),
        "result" => [
            'data' => [],
            'total' => 0,
            'page' => $page,
            'total_pages' => 0,
            'total_order' => 0,
            'total_belum_order' => 0,
            'total_penjualan_keseluruhan' => 0
        ]
    ];
    http_response_code(500);
}

$conn = $base->close();
header('Access-Control-Allow-Origin: *');
header("Content-type: application/json; charset=utf-8");
echo json_encode($hasil);
exit;
?>
