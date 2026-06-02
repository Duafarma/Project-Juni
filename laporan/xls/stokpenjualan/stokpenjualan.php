<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
try {
    if (!headers_sent()) {
        header("Content-Type: application/force-download");
        header("Cache-Control: no-cache, must-revalidate");
        header("Expires: Laporan"); 
        header("content-disposition:attachment; filename=AnalisisPenjualan.xls");
    }

    require_once('../../../config/connection/connection.php');
    require_once('../../../config/connection/security.php');
    require_once('../../../config/function/data.php');

    $base = new DB();
    $secu = new Security;
    $data = new Data;

    $idApl = $secu->injection(@$_GET['id_apl']);
    if (!$idApl) {
        throw new Exception("Parameter id_apl tidak ditemukan.");
    }

    // Tentukan nama cabang berdasarkan id_apl
    if ($idApl === 'all') {
        $namaCabang = "Semua Cabang";
    } elseif ($idApl === 'a_b') {
        $namaCabang = "Puri + Puri B";
    } else {
        $namaCabang = ""; // Default, akan diisi berdasarkan data aplikasi
    }

    // Ambil data aplikasi berdasarkan id_apl
    $queryAplikasi = "
        SELECT id_apl, nama_apl, base_url_apl
        FROM aplikasi
    ";

    if ($idApl === 'all') {
        // Ambil semua data aplikasi
        $stmtAplikasi = $base->open()->prepare($queryAplikasi);
    } elseif ($idApl === 'a_b') {
        // Ambil data aplikasi untuk APL01 dan APL02
        $queryAplikasi .= " WHERE id_apl IN ('APL01', 'APL02')";
        $stmtAplikasi = $base->open()->prepare($queryAplikasi);
    } else {
        // Ambil data aplikasi untuk id_apl tertentu
        $queryAplikasi .= " WHERE id_apl = :id_apl";
        $stmtAplikasi = $base->open()->prepare($queryAplikasi);
        $stmtAplikasi->bindParam(':id_apl', $idApl, PDO::PARAM_STR);
    }

    $stmtAplikasi->execute();
    $aplikasiList = $stmtAplikasi->fetchAll(PDO::FETCH_ASSOC);

    if (empty($aplikasiList)) {
        throw new Exception("Data aplikasi tidak ditemukan.");
    }

    // Jika id_apl bukan 'all' atau 'a_b', gunakan nama cabang dari data aplikasi
    if ($idApl !== 'all' && $idApl !== 'a_b') {
        $namaCabang = $aplikasiList[0]['nama_apl'];
    }

    // Hitung 12 bulan terakhir dari bulan sekarang
    $currentMonth = (int)date('m'); // Bulan sekarang (Mei = 5)
    $currentYear = (int)date('Y');  // Tahun sekarang (2025)
    $bulanArray = [];

    for ($i = 11; $i >= 0; $i--) {
        $date = new DateTime("$currentYear-$currentMonth-01");
        $date->modify("-$i months");
        $bulanArray[] = [
            'month' => $date->format('M'), // Nama bulan (Jan, Feb, ...)
            'year' => $date->format('Y')  // Tahun (2024, 2025, ...)
        ];
    }

    // Gabungkan data dari semua cabang
    $produkData = [];
    foreach ($aplikasiList as $aplikasi) {
        $baseUrl = rtrim($aplikasi['base_url_apl'], '/');

        // Panggil API untuk mendapatkan data
        $apiUrl = $baseUrl . "/api/getdatapenjualan.php?id_apl=" . urlencode($aplikasi['id_apl']);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        // Validasi respons API
        $apiData = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Gagal memproses data dari API: " . json_last_error_msg());
        }

        if (!isset($apiData['result']) || !is_array($apiData['result'])) {
            throw new Exception("Data dari API tidak valid atau kosong.");
        }

        // Gabungkan data produk
        foreach ($apiData['result'] as $row) {
            $namaProduk = $row['nama_produk'];
            $tahun = $row['tahun'];
            $bulan = $row['bulan'];
            $totalPenjualan = $row['total_penjualan'];

            // Cari indeks bulan dalam $bulanArray
            foreach ($bulanArray as $index => $bulanInfo) {
                if ((int)$bulan == (int)date('m', strtotime($bulanInfo['month'])) && (int)$tahun == (int)$bulanInfo['year']) {
                    if (!isset($produkData[$namaProduk])) {
                        $produkData[$namaProduk] = array_fill(0, 12, 0); // Inisialisasi 12 bulan dengan nilai 0
                    }
                    $produkData[$namaProduk][$index] += $totalPenjualan; // Tambahkan penjualan
                }
            }
        }
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Data Analisi Penjualan</title>
    </head>

    <body>
        <table>
            <tr>
                <th colspan="4">DATA ANALISIS PENJUALAN</th>
            </tr>
            <tr>
                <th colspan="4"><?php echo($data->sistem('pt_sis')); ?></th>
            </tr>
            <tr>
                <td colspan="4"></td>
            </tr>
        </table>
        <table border="1">
            <thead>
                <tr>
                    <th colspan="2"><center>Cabang : <?php echo $namaCabang; ?></center></th>
                    <?php
                        $currentYear = '';
                        foreach ($bulanArray as $bulan) {
                            // Jika tahun berubah, tambahkan kolspan untuk tahun
                            if ($currentYear !== $bulan['year']) {
                                if ($currentYear !== '') {
                                    echo "<th colspan='{$colspan}'><center>{$currentYear}</center></th>";
                                }
                                $currentYear = $bulan['year'];
                                $colspan = 0;
                            }
                            $colspan++;
                        }
                        // Tambahkan tahun terakhir
                        if ($currentYear !== '') {
                            echo "<th colspan='{$colspan}'><center>{$currentYear}</center></th>";
                        }
                    ?>
                    <th rowspan="2"><center>AVERAGE</center></th>
                    <th rowspan="2"><center>MAX</center></th>
                </tr>
                <tr>
                    <th rowspan="1"><center>No</center></th>
                    <th rowspan="1"><center>Nama Produk</center></th>
                    <?php
                        foreach ($bulanArray as $bulan) {
                            echo "<th><center>{$bulan['month']}</center></th>";
                        }
                    ?>
                </tr>
            </thead>
            <tbody>
            <?php
                $nomor = 1;
                foreach ($produkData as $namaProduk => $penjualanPerBulan) {
                    $total = array_sum($penjualanPerBulan);
                    $average = $total / 12;
                    $max = max($penjualanPerBulan);

                    echo "<tr>";
                    echo "<td><center>{$nomor}</center></td>";
                    echo "<td>{$namaProduk}</td>";

                    // Tampilkan data per bulan
                    foreach ($penjualanPerBulan as $penjualan) {
                        echo "<td><center>{$penjualan}</center></td>";
                    }

                    // Tambahkan kolom AVERAGE dan MAX
                    echo "<td><center>" . round($average, 2) . "</center></td>";
                    echo "<td><center>{$max}</center></td>";
                    echo "</tr>";

                    $nomor++;
                }
            ?>
            </tbody>
        </table>
    </body>
</html>
