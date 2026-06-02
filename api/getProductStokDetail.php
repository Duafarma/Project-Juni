<?php
// must add request validation
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Access-Control-Allow-Origin: *');
    header("Content-type: application/json; charset=utf-8");
    echo json_encode(["result" => "Method Not Allowed"]);
    exit;
} else {
    require_once('../config/connection/connection.php');
    require_once('../config/connection/security.php');
    require_once('../config/function/data.php');
    $secu = new Security;
    $base = new DB;
    $data = new Data;
    $tgl = date('Y-m-d');
    $conn = $base->open();
    $hasil = "Error";

    // --- Tambahkan fungsi stokSo ---
    function stokSo($conn, $kode)
    {
        // Ambil bulan dan tahun berjalan
        $bulan_ini = date('n'); // 1-12
        $tahun_ini = date('Y');

        // Bulan akhir: 1 bulan sebelum bulan berjalan
        $bulan_akhir = $bulan_ini - 1;
        $tahun_akhir = $tahun_ini;
        if ($bulan_akhir <= 0) {
            $bulan_akhir += 12;
            $tahun_akhir -= 1;
        }

        // Awal periode: 11 bulan sebelum bulan akhir
        $startDate = date('Y-m-01', strtotime("-11 months", strtotime("{$tahun_akhir}-{$bulan_akhir}-01")));
        $endDate = date('Y-m-01', strtotime("{$tahun_akhir}-{$bulan_akhir}-01"));

        $query = "
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') AS periode,
                COALESCE(SUM(qty_so), 0) AS total
            FROM so
            WHERE id_pro = :kode
              AND created_at >= :startDate AND created_at < DATE_ADD(:endDate, INTERVAL 1 MONTH)
            GROUP BY periode
            ORDER BY periode
        ";

        $stmt = $conn->prepare($query);
        $stmt->bindParam(':kode', $kode, PDO::PARAM_STR);
        $stmt->bindParam(':startDate', $startDate, PDO::PARAM_STR);
        $stmt->bindParam(':endDate', $endDate, PDO::PARAM_STR);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Siapkan array 12 bulan, dengan bulan +1
        $stokSo = [];
        $labels = [];
        for ($i = 0; $i < 12; $i++) {
            $bulan = date('Y-m', strtotime("+$i month", strtotime($startDate)));
            // Tambahkan +1 bulan untuk index array
            $bulanIndex = date('Y-m', strtotime("+1 month", strtotime($bulan . '-01')));
            $stokSo[$bulanIndex] = 0;
            $labels[] = date('M Y', strtotime($bulanIndex . '-01'));
        }

        foreach ($results as $row) {
            // Tambahkan +1 bulan pada periode hasil query
            $bulanIndex = date('Y-m', strtotime("+1 month", strtotime($row['periode'] . '-01')));
            $stokSo[$bulanIndex] = (float)$row['total'];
        }

        // Ambil hanya bulan 1 sampai bulan berjalan (bulan +1)
        $jumlah_bulan = $bulan_ini;
        $stokSo = array_slice(array_values($stokSo), 0, $jumlah_bulan);

        // return ['labels' => array_slice($labels, 0, $jumlah_bulan), 'data' => $stokSo];
        return $stokSo;
    }
    // --- End fungsi stokSo ---

    // --- Tambahkan fungsi transaksiRD ---
    function transaksiRD($conn, $kode)
    {
        $bulan_ini = date('n');
        $tahun_ini = date('Y');
        $bulan_akhir = $bulan_ini - 1;
        $tahun_akhir = $tahun_ini;
        if ($bulan_akhir <= 0) {
            $bulan_akhir += 12;
            $tahun_akhir -= 1;
        }
        $startDate = date('Y-m-01', strtotime("-11 months", strtotime("{$tahun_akhir}-{$bulan_akhir}-01")));
        $endDate = date('Y-m-01', strtotime("{$tahun_akhir}-{$bulan_akhir}-01"));

        $query = "
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') AS periode,
                COALESCE(SUM(jumlah_trd), 0) AS total
            FROM transaksi_receivedetail
            WHERE id_pro = :kode
              AND created_at >= :startDate AND created_at < DATE_ADD(:endDate, INTERVAL 1 MONTH)
            GROUP BY periode
            ORDER BY periode
        ";

        $stmt = $conn->prepare($query);
        $stmt->bindParam(':kode', $kode, PDO::PARAM_STR);
        $stmt->bindParam(':startDate', $startDate, PDO::PARAM_STR);
        $stmt->bindParam(':endDate', $endDate, PDO::PARAM_STR);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $transaksiRD = [];
        for ($i = 0; $i < 12; $i++) {
            $bulan = date('Y-m', strtotime("+$i month", strtotime($startDate)));
            $transaksiRD[$bulan] = 0;
        }
        foreach ($results as $row) {
            $bulan = $row['periode'];
            $transaksiRD[$bulan] = (float)$row['total'];
        }
        $jumlah_bulan = $bulan_ini;
        $transaksiRD = array_slice(array_values($transaksiRD), 0, $jumlah_bulan);
        return $transaksiRD;
    }
    // --- End fungsi transaksiRD ---

    // --- Tambahkan fungsi getOutletsByGroup ---
    function getOutletsByGroup($conn, $id_mg) {
        $query = "
            SELECT id_out
            FROM outlet
            WHERE id_mg = :id_mg
        ";

        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id_mg', $id_mg, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_COLUMN);

        error_log("getOutletsByGroup result: " . json_encode($result)); // Log hasil query
        return $result;
    }
    // --- End fungsi getOutletsByGroup ---

    // checking encrypt
    $encrypt = $secu->injection($_GET['encrypt']);
    $source = $data->self_apl();
    $sourceKey = $source['key_apl'];
    if (md5($tgl . "#" . $sourceKey) == $encrypt) {
        try {
            $tahun_ini = date('Y');
            $bulan_ini = date('n'); // Bulan saat ini (tanpa leading zero)
            $tahun1 = $tahun_ini - 2;
            $tahun2 = $tahun_ini - 1;
            $tahun3 = $tahun_ini;

            // Periksa jika parameter all_produk ada
            if (isset($_GET['all_produk'])) {
                $id_out = isset($_GET['id_out']) && $_GET['id_out'] !== '' ? $secu->injection($_GET['id_out']) : null;
                $id_mg = isset($_GET['id_mg']) && $_GET['id_mg'] !== '' ? $secu->injection($_GET['id_mg']) : null;
                $id_apl = isset($_GET['id_apl']) && $_GET['id_apl'] !== '' ? $secu->injection($_GET['id_apl']) : null;

                // Tentukan tabel berdasarkan id_apl
                $transaksi_faktur_table = ($id_apl === 'APL02') ? 'transaksi_faktur_c' : 'transaksi_faktur';
                $transaksi_fakturdetail_table = ($id_apl === 'APL02') ? 'transaksi_fakturdetail_c' : 'transaksi_fakturdetail';

                // Ambil daftar id_out berdasarkan id_mg
                $id_out_list = [];
                if ($id_mg) {
                    $id_out_list = getOutletsByGroup($conn, $id_mg);
                    if (empty($id_out_list)) {
                        http_response_code(404);
                        echo json_encode(["result" => "Tidak ada outlet pada grup ini"]);
                        exit;
                    }
                }

                $where_outlet = '';
                if ($id_out) {
                    $where_outlet = "AND T.id_out = :id_out ";
                } elseif (!empty($id_out_list)) {
                    $in_params = [];
                    foreach ($id_out_list as $index => $id_out_value) {
                        $in_params[] = ":id_out_$index";
                    }
                    $where_outlet = "AND T.id_out IN (" . implode(',', $in_params) . ") ";
                } else {
                    $where_outlet = ''; // Tidak ada filter outlet
                }

                $query = "
                    SELECT YEAR(T.tgl_tfk) AS tahun, 
                           MONTH(T.tgl_tfk) AS bulan, 
                           COALESCE(SUM(TF.total_tfd), 0) AS total
                    FROM (
                        SELECT id_tfk, tgl_tfk, id_out FROM $transaksi_faktur_table
                        UNION ALL
                        SELECT id_tfk, tgl_tfk, id_out FROM transaksi_faktur_pim
                    ) AS T
                    JOIN (
                        SELECT id_tfk, total_tfd FROM $transaksi_fakturdetail_table
                        UNION ALL
                        SELECT id_tfk, total_tfd FROM transaksi_fakturdetail_pim
                    ) AS TF ON T.id_tfk = TF.id_tfk
                    WHERE YEAR(T.tgl_tfk) BETWEEN :tahun1 AND :tahun3
                    $where_outlet
                    GROUP BY tahun, bulan
                    ORDER BY tahun ASC, bulan ASC
                ";

                error_log("Query: " . $query);
                error_log("Parameters: " . json_encode($id_out_list));

                $stmt = $conn->prepare($query);
                $stmt->bindParam(':tahun1', $tahun1, PDO::PARAM_INT);
                $stmt->bindParam(':tahun3', $tahun3, PDO::PARAM_INT);

                if ($id_out) {
                    $stmt->bindParam(':id_out', $id_out, PDO::PARAM_STR);
                } elseif (!empty($id_out_list)) {
                    foreach ($id_out_list as $index => $id_out_value) {
                        $stmt->bindValue(":id_out_$index", $id_out_value, PDO::PARAM_STR);
                    }
                }

                $stmt->execute();

                $data_result = [];
                $totalPerTahun = [];

                // Pastikan data awal lengkap untuk semua tahun dan bulan
                for ($tahun = $tahun1; $tahun <= $tahun3; $tahun++) {
                    $data_result[$tahun] = array_fill(1, 12, 0);
                    $totalPerTahun[$tahun] = 0;
                }

                // Isi data dari hasil query
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $tahun = $row['tahun'];
                    $bulan = $row['bulan'];
                    $total = floatval($row['total']);

                    $data_result[$tahun][$bulan] = $total;
                    $totalPerTahun[$tahun] += $total;
                }

                // Buat label bulan
                $bulan_labels = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];

                // Format data untuk dikirim ke frontend
                $series = [];
                for ($tahun = $tahun1; $tahun <= $tahun3; $tahun++) {
                    $series[] = [
                        'name' => "Tahun " . $tahun,
                        'data' => array_values($data_result[$tahun])
                    ];
                }

                $hasil = [
                    'bulan_labels' => $bulan_labels,
                    'series' => $series,
                    'tahun1' => $tahun1,
                    'tahun2' => $tahun1 + 1,
                    'tahun3' => $tahun3,
                    'totalPerTahun' => $totalPerTahun
                ];
            } else {
                $kode = $secu->injection($_GET['id_pro']);
                $id_out = isset($_GET['id_out']) && $_GET['id_out'] !== '' ? $secu->injection($_GET['id_out']) : null;
                $id_mg = isset($_GET['id_mg']) && $_GET['id_mg'] !== '' ? $secu->injection($_GET['id_mg']) : null;

                // Ambil daftar id_out berdasarkan id_mg
                $id_out_list = [];
                if ($id_mg) {
                    $id_out_list = getOutletsByGroup($conn, $id_mg);
                    if (empty($id_out_list)) {
                        http_response_code(404);
                        echo json_encode(["result" => "Tidak ada outlet pada grup ini"]);
                        exit;
                    }
                }

                error_log("id_out_list: " . json_encode($id_out_list));

                // Data transaksi faktur dan transfer stock
                $where_outlet = '';
                if ($id_out) {
                    $where_outlet = "AND T.id_out = :id_out ";
                } elseif (!empty($id_out_list)) {
                    $in_params = [];
                    foreach ($id_out_list as $index => $id_out_value) {
                        $in_params[] = ":id_out_$index";
                    }
                    $where_outlet = "AND T.id_out IN (" . implode(',', $in_params) . ") ";
                } else {
                    $where_outlet = ''; // Tidak ada filter outlet
                }

                // Tentukan tabel berdasarkan id_apl
                $id_apl = isset($_GET['id_apl']) && $_GET['id_apl'] !== '' ? $secu->injection($_GET['id_apl']) : null;

                $transaksi_faktur_table = ($id_apl === 'APL02') ? 'transaksi_faktur_c' : 'transaksi_faktur';
                $transaksi_fakturdetail_table = ($id_apl === 'APL02') ? 'transaksi_fakturdetail_c' : 'transaksi_fakturdetail';

                $query = "
                    SELECT YEAR(T.tgl_tfk) AS tahun, 
                           MONTH(T.tgl_tfk) AS bulan, 
                           COALESCE(SUM(TF.jumlah_tfd), 0) AS total 
                    FROM (
                        SELECT id_tfk, tgl_tfk, id_out FROM $transaksi_faktur_table
                        UNION ALL
                        SELECT id_tfk, tgl_tfk, id_out FROM transaksi_faktur_pim
                    ) AS T
                    JOIN (
                        SELECT id_tfk, jumlah_tfd, id_pro FROM $transaksi_fakturdetail_table
                        UNION ALL
                        SELECT id_tfk, jumlah_tfd, id_pro FROM transaksi_fakturdetail_pim
                    ) AS TF ON T.id_tfk = TF.id_tfk
                    WHERE TF.id_pro = :kode
                    $where_outlet
                    AND YEAR(T.tgl_tfk) BETWEEN :tahun1 AND :tahun3
                    GROUP BY tahun, bulan
                    ORDER BY tahun ASC, bulan ASC
                ";

                $stmt = $conn->prepare($query);
                $stmt->bindParam(':kode', $kode, PDO::PARAM_STR);
                $stmt->bindParam(':tahun1', $tahun1, PDO::PARAM_INT);
                $stmt->bindParam(':tahun3', $tahun3, PDO::PARAM_INT);

                if ($id_out) {
                    $stmt->bindParam(':id_out', $id_out, PDO::PARAM_STR);
                } elseif (!empty($id_out_list)) {
                    foreach ($id_out_list as $index => $id_out_value) {
                        $stmt->bindValue(":id_out_$index", $id_out_value, PDO::PARAM_STR);
                    }
                }

                $stmt->execute();

                $dataTransaksi = [];
                $tempData = [];
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $tahun = $row['tahun'];
                    $bulan = $row['bulan'];
                    
                    // Initialize if not exists
                    if (!isset($tempData[$tahun][$bulan])) {
                        $tempData[$tahun][$bulan] = 0;
                    }
                    
                    // Add data
                    $tempData[$tahun][$bulan] += $row['total'];
                }

                // Format final data
                foreach ($tempData as $tahun => $bulanData) {
                    foreach ($bulanData as $bulan => $total) {
                        $dataTransaksi[$tahun][$bulan] = $total;
                    }
                }

                // Stok sisa total
                $queryStok = "
                    SELECT COALESCE(SUM(sisa_psd), 0) AS total
                    FROM produk_stokdetail
                    WHERE id_pro = :kode
                ";

                $stmtStok = $conn->prepare($queryStok);
                $stmtStok->bindParam(':kode', $kode, PDO::PARAM_STR);
                $stmtStok->execute();
                $stokResult = $stmtStok->fetch(PDO::FETCH_ASSOC);
                $stokTotal = $stokResult['total'] ?? 0;

                // Tampilkan stok di bulan sekarang
                $stokSisaFinal = array_fill(1, 12, 0);
                $stokSisaFinal[$bulan_ini] = $stokTotal;

                // --- Ambil data stok SO per bulan ---
                $stokSoPerBulan = stokSo($conn, $kode);

                // --- Ambil data transaksi RD per bulan ---
                $transaksiRDBulan = transaksiRD($conn, $kode);

                // Gabungkan stokSo dan transaksiRD per bulan
                $stokSoGabungan = [];
                $max_bulan = max(count($stokSoPerBulan), count($transaksiRDBulan));
                for ($i = 0; $i < $max_bulan; $i++) {
                    $so = isset($stokSoPerBulan[$i]) ? $stokSoPerBulan[$i] : 0;
                    $rd = isset($transaksiRDBulan[$i]) ? $transaksiRDBulan[$i] : 0;
                    $stokSoGabungan[] = $so + $rd;
                }

                // Format data akhir
                $finalData = [];
                foreach ([$tahun1, $tahun2, $tahun3] as $thn) {
                    $finalData[$thn] = array_fill(1, 12, 0);
                    if (!empty($dataTransaksi[$thn])) {
                        foreach ($dataTransaksi[$thn] as $bulan => $total) {
                            $finalData[$thn][$bulan] = $total;
                        }
                    }
                }

                // Cari penjualan tertinggi
                $penjualan_tertinggi = [];
                foreach ($finalData as $tahun => $bulanData) {
                    $maxBulan = null;
                    $maxJumlah = 0;

                    foreach ($bulanData as $bulan => $jumlah) {
                        if ($jumlah > $maxJumlah) {
                            $maxJumlah = $jumlah;
                            $maxBulan = $bulan;
                        }
                    }

                    if ($maxBulan !== null) {
                        $penjualan_tertinggi[$tahun] = [
                            "bulan" => $maxBulan,
                            "jumlah" => $maxJumlah
                        ];
                    }
                }

                // Urutan tahun per bulan
                $monthlyOrder = [];
                for ($bulan = 1; $bulan <= 12; $bulan++) {
                    $salesThisMonth = [
                        $tahun1 => $finalData[$tahun1][$bulan],
                        $tahun2 => $finalData[$tahun2][$bulan],
                        $tahun3 => $finalData[$tahun3][$bulan]
                    ];
                    asort($salesThisMonth);
                    $monthlyOrder[$bulan] = array_keys($salesThisMonth);
                }

                $hasil = [
                    "labels" => ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"],
                    "tahun1_label" => $tahun1,
                    "tahun2_label" => $tahun2,
                    "tahun3_label" => $tahun3,
                    "series" => [
                        [
                            "name" => "Tahun " . $tahun1,
                            "data" => array_values($finalData[$tahun1])
                        ],
                        [
                            "name" => "Tahun " . $tahun2,
                            "data" => array_values($finalData[$tahun2])
                        ],
                        [
                            "name" => "Tahun " . $tahun3,
                            "data" => array_values($finalData[$tahun3])
                        ]
                    ],
                    "stokSisa" => array_values($stokSisaFinal),
                    "stokSo" => $stokSoGabungan,
                    // "transaksiRD" => array_values($transaksiRDBulan),
                    // "stokSoGabungan" => $stokSoGabungan,
                    "penjualan_tertinggi" => $penjualan_tertinggi,
                    "monthlyOrder" => $monthlyOrder
                ];
            }

            // Tambahkan kode ini di dalam blok try-catch, sebelum membentuk response akhir
            if (isset($_GET['id_pro']) && $_GET['id_pro'] !== '') {
                $kode = $secu->injection($_GET['id_pro']);
                $id_out = isset($_GET['id_out']) && $_GET['id_out'] !== '' ? $secu->injection($_GET['id_out']) : null;
                $id_mg = isset($_GET['id_mg']) && $_GET['id_mg'] !== '' ? $secu->injection($_GET['id_mg']) : null;
                
                // Ambil data transfer stock jika tidak ada filter grup dan outlet
                if (empty($id_mg) && empty($id_out)) {
                    $transferQuery = "
                        SELECT YEAR(TS.tgl_ttr) AS tahun,
                               MONTH(TS.tgl_ttr) AS bulan,
                               COALESCE(SUM(TSD.jumlah_ttd), 0) AS total
                        FROM transaksi_transferstock AS TS
                        JOIN transaksi_transferstockdetail AS TSD ON TS.id_ttr = TSD.id_ttr
                        WHERE TSD.id_pro = :kode
                          AND TS.tipe_ttr = 'OUT'
                          AND YEAR(TS.tgl_ttr) BETWEEN :tahun1 AND :tahun3
                        GROUP BY tahun, bulan
                        ORDER BY tahun ASC, bulan ASC
                    ";

                    $stmtTransfer = $conn->prepare($transferQuery);
                    $stmtTransfer->bindParam(':kode', $kode, PDO::PARAM_STR);
                    $stmtTransfer->bindParam(':tahun1', $tahun1, PDO::PARAM_INT);
                    $stmtTransfer->bindParam(':tahun3', $tahun3, PDO::PARAM_INT);
                    $stmtTransfer->execute();
                    
                    // Gabungkan data transfer stock ke hasil API
                    while ($row = $stmtTransfer->fetch(PDO::FETCH_ASSOC)) {
                        $tahun = intval($row['tahun']);
                        $bulan = intval($row['bulan']) - 1; // Sesuaikan index untuk array 0-based
                        $total = floatval($row['total']);
                        
                        if (isset($hasil['series'])) {
                            foreach ($hasil['series'] as $idx => $series) {
                                if ($series['name'] === "Tahun " . $tahun) {
                                    $hasil['series'][$idx]['data'][$bulan] += $total;
                                    // Juga update totalPerTahun jika ada
                                    if (isset($hasil['totalPerTahun'][$tahun])) {
                                        $hasil['totalPerTahun'][$tahun] += $total;
                                    }
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            http_response_code(200);
        } catch (PDOException $e) {
            $hasil = "Database Error: " . $e->getMessage();
            http_response_code(500);
        }
    } else {
        $hasil = "Unauthorized";
        http_response_code(401);
    }
    $conn = $base->close();
    header('Access-Control-Allow-Origin: *');
    header("Content-type: application/json; charset=utf-8");
    echo json_encode(array("result" => $hasil));
}
?>