<?php
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

$idApl = isset($_GET['id_apl']) ? $secu->injection($_GET['id_apl']) : null;

if (!$idApl) {
    http_response_code(400);
    echo json_encode(["result" => "Parameter id_apl tidak ditemukan."]);
    exit;
}

try {
    // Ambil informasi koneksi dari tabel aplikasi
    $queryAplikasi = "
        SELECT base_url_apl, key_apl, self_apl, nama_apl
        FROM aplikasi
        WHERE id_apl = :id_apl
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

    // Jika aplikasi adalah aplikasi saat ini (self_apl), gunakan koneksi lokal
    if ($selfApl == 1) {
        $query = "
            SELECT 
                P.id_pro,
                P.nama_pro AS nama_produk,
                YEAR(T.tgl_tfk) AS tahun,
                MONTH(T.tgl_tfk) AS bulan,
                COALESCE(SUM(TF.jumlah_tfd), 0) AS total_penjualan
            FROM transaksi_fakturdetail TF
            INNER JOIN transaksi_faktur T ON TF.id_tfk = T.id_tfk
            INNER JOIN produk P ON TF.id_pro = P.id_pro
            WHERE T.tgl_tfk >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) -- Tambahkan filter 12 bulan terakhir
            GROUP BY P.id_pro, P.nama_pro, tahun, bulan
            ORDER BY P.nama_pro ASC, tahun ASC, bulan ASC
        ";

        $stmt = $conn->prepare($query);
        $stmt->execute();
        $dataProduk = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Tambahkan query untuk menghitung total stok
        $queryStok = "
            SELECT id_pro, COALESCE(SUM(sisa_psd), 0) AS total
            FROM produk_stokdetail
            GROUP BY id_pro
        ";  
        $stmtStok = $conn->prepare($queryStok);
        $stmtStok->execute();
        $totalStok = $stmtStok->fetchAll(PDO::FETCH_ASSOC); // Ambil semua data stok per produk

        if (empty($dataProduk)) {
            throw new Exception("Data tidak ditemukan.");
        }

        // Gabungkan data produk dengan total stok
        $stokPerProduk = [];
        foreach ($totalStok as $stok) {
            $stokPerProduk[$stok['id_pro']] = $stok['total'];
        }

        $processedProducts = []; // Array untuk melacak produk yang sudah diproses

        foreach ($dataProduk as &$produk) {
            $idPro = $produk['id_pro'];

            // Tambahkan total stok hanya untuk entri pertama dari setiap produk
            if (!isset($processedProducts[$idPro])) {
                $produk['total_stok'] = $stokPerProduk[$idPro] ?? 0;
                $processedProducts[$idPro] = true; // Tandai produk sebagai sudah diproses
            } else {
                $produk['total_stok'] = null; // Kosongkan stok untuk entri berikutnya
            }
        }

        // Hitung total stok keseluruhan (opsional, jika diperlukan)
        $totalStokKeseluruhan = array_sum(array_column($totalStok, 'total'));

        // Tambahkan total stok keseluruhan ke hasil (opsional)
        $hasil = [
            "result" => $dataProduk,
            "total_stok_keseluruhan" => $totalStokKeseluruhan, // Tambahkan total stok keseluruhan
            "source" => [
                "type" => "database",
                "name" => $aplikasi['nama_apl'],
                "id" => $idApl
            ]
        ];
    } else {
        // Jika aplikasi adalah aplikasi lain, gunakan API aplikasi tersebut
        $tgl = date('Y-m-d');
        $encrypt = md5($tgl . "#" . $keyApl);

        $apiUrl = rtrim($baseUrl, '/') . "/api/getdatapenjualan.php?encrypt=" . $encrypt . "&id_apl=" . urlencode($idApl);

        // Debug URL API
        echo "URL API: " . $apiUrl . "<br>";

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
            echo "JSON Error: " . json_last_error_msg() . "<br>";
            throw new Exception("Gagal memproses data dari API: " . json_last_error_msg());
        }

        // Debug hasil parsing JSON
        echo "<pre>";
        print_r($apiData);
        echo "</pre>";

        if (!$apiData || !isset($apiData['result'])) {
            echo "Data tidak valid atau result tidak ditemukan.<br>";
            print_r($apiData);
            exit;
        }

        if (empty($apiData['result'])) {
            echo "Result kosong.<br>";
            print_r($apiData['result']);
            exit;
        }

        if (!$apiData || !isset($apiData['result']) || empty($apiData['result'])) {
            throw new Exception("Data dari API aplikasi tidak valid atau kosong.");
        }

        echo "<pre>";
        print_r($apiData['result']);
        echo "</pre>";

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
        "error" => $e->getMessage()
    ];
    http_response_code(500);
}

$conn = $base->close();
header('Access-Control-Allow-Origin: *');
header("Content-type: application/json; charset=utf-8");
echo json_encode($hasil, JSON_PRETTY_PRINT);
exit;
