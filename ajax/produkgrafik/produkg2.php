<?php
// Aktifkan error reporting untuk debugging di lingkungan dev
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Log error ke file untuk debugging di server
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/php_errors.log');

// Mulai output buffering
ob_start();

// Set header dari awal
header('Content-Type: application/json');

try {
    require_once('../../config/connection/connection.php');
    require_once('../../config/connection/security.php');
    require_once('../../config/function/data.php');

    $secu = new Security;
    $base = new DB;
    $data = new Data;

    // Cek id_apl yang dipilih dari select option
    $selected_apl = isset($_GET['id_apl']) && $_GET['id_apl'] !== '' ? $secu->injection($_GET['id_apl']) : null;
    $id_mg = isset($_GET['id_mg']) && $_GET['id_mg'] !== '' ? $secu->injection($_GET['id_mg']) : null;
    $source_info = null;

    // Log id_mg for debugging
    error_log("id_mg: " . $id_mg);

    // Buka koneksi default
    $conn = $base->open();

    if (!$conn) {
        die(json_encode(["error" => "Koneksi database gagal!"]));
    }

    // Jika "Semua Cabang" dipilih
    if ($selected_apl === 'all') {
        // Ambil semua aplikasi yang aktif
        $query = "SELECT * FROM aplikasi WHERE active_apl = 1";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $all_apps = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$all_apps) {
            die(json_encode(["error" => "Tidak ada cabang aktif ditemukan"]));
        }

        // Dapatkan parameter dari request asli
        $all_produk = isset($_GET['all_produk']) ? 1 : 0;
        $id_pro = isset($_GET['id_pro']) ? $secu->injection($_GET['id_pro']) : null;
        $id_out = isset($_GET['id_out']) && $_GET['id_out'] !== '' ? $secu->injection($_GET['id_out']) : null;

        if (isset($_GET['all_produk']) && $selected_apl === 'all') {
            $bulan_labels = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];
            $tahun_ini = date('Y');
            $tahun1 = $tahun_ini - 2;
            $tahun2 = $tahun_ini - 1;
            $tahun3 = $tahun_ini;

            // Ambil semua aplikasi aktif
            $query = "SELECT * FROM aplikasi WHERE active_apl = 1";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $all_apps = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $series = [
                $tahun1 => array_fill(0, 12, 0),
                $tahun2 => array_fill(0, 12, 0),
                $tahun3 => array_fill(0, 12, 0),
            ];
            $totalPerTahun = [
                $tahun1 => 0,
                $tahun2 => 0,
                $tahun3 => 0,
            ];

            foreach ($all_apps as $app) {
                if (empty($app['base_url_apl']) || empty($app['key_apl'])) continue;

                $tgl = date('Y-m-d');
                $encrypt = md5($tgl . "#" . $app['key_apl']);

                $params = [];
                $params['all_produk'] = 1;
                $params['id_out'] = $id_out; // Tambahkan filter id_out
                if ($id_mg) $params['id_mg'] = $id_mg; // Tambahkan baris ini!
                
                // Tambahkan id_apl untuk identifikasi tabel yang digunakan
                $params['id_apl'] = $app['id_apl']; // Mengirim id_apl ke API
                
                $params['encrypt'] = $encrypt;
                $queryString = http_build_query($params);

                $api_url = rtrim($app['base_url_apl'], '/') . "/api/getProductStokDetail.php?$queryString";

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $api_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

                $response = curl_exec($ch);
                $curl_error = curl_error($ch);
                curl_close($ch);

                if ($curl_error) {
                    error_log("CURL ERROR: $curl_error ($api_url)");
                    continue;
                }

                $api_data = json_decode($response, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        "error" => "API response is not valid JSON",
                        "raw_response" => $response
                    ]);
                    exit;
                }

                // Gabungkan data berdasarkan tahun dan bulan
                if ($api_data && isset($api_data['result']['series'])) {
                    foreach ([$tahun1, $tahun2, $tahun3] as $idx => $tahun) {
                        if (isset($api_data['result']['series'][$idx]['data'])) {
                            foreach ($api_data['result']['series'][$idx]['data'] as $i => $val) {
                                $series[$tahun][$i] += $val;
                                $totalPerTahun[$tahun] += $val;
                            }
                        }
                    }
                }
            }

            // Format output agar sama dengan API satu cabang
            $series_out = [];
            foreach ([$tahun1, $tahun2, $tahun3] as $tahun) {
                $series_out[] = [
                    'name' => "Tahun " . $tahun,
                    'data' => array_values($series[$tahun])
                ];
            }

            $result = [
                'bulan_labels' => $bulan_labels,
                'series' => $series_out,
                'tahun1' => $tahun1,
                'tahun2' => $tahun2,
                'tahun3' => $tahun3,
                'totalPerTahun' => $totalPerTahun,
                'source' => [
                    'type' => 'api',
                    'name' => 'All Cabang',
                    'id' => 'all'
                ]
            ];

            header('Content-Type: application/json');
            error_log("API Response: " . json_encode($result)); // Log respons API
            echo json_encode($result);
            exit;
        }

        // Inisialisasi struktur data gabungan
        $labels = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];
        $tahun_ini = date('Y');
        $tahun1 = $tahun_ini - 2;
        $tahun2 = $tahun_ini - 1;
        $tahun3 = $tahun_ini;

        $series = [
            $tahun1 => array_fill(0, 12, 0),
            $tahun2 => array_fill(0, 12, 0),
            $tahun3 => array_fill(0, 12, 0),
        ];
        $stokSisa = array_fill(0, 12, 0);
        $stokSo = array_fill(0, 12, 0);
        $totalPerTahun = [
            $tahun1 => 0,
            $tahun2 => 0,
            $tahun3 => 0,
        ];

        foreach ($all_apps as $app) {
            if (empty($app['base_url_apl']) || empty($app['key_apl'])) continue;

            $tgl = date('Y-m-d');
            $encrypt = md5($tgl . "#" . $app['key_apl']); // HARUS key_apl cabang tujuan

            $params = [];
            if ($id_pro) $params['id_pro'] = $id_pro;
            if ($id_out) $params['id_out'] = $id_out;
            if ($id_mg) $params['id_mg'] = $id_mg; // Tambahkan baris ini!
            if ($all_produk) $params['all_produk'] = 1;
            
            // Tambahkan id_apl untuk identifikasi tabel yang digunakan
            $params['id_apl'] = $app['id_apl']; // Mengirim id_apl ke API
            
            $params['encrypt'] = $encrypt;
            $queryString = http_build_query($params);

            $api_url = rtrim($app['base_url_apl'], '/') . "/api/getProductStokDetail.php?$queryString";

            // Panggil API menggunakan cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

            $response = curl_exec($ch);
            $curl_error = curl_error($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($curl_error) {
                error_log("CURL ERROR: $curl_error ($api_url)");
                continue;
            }

            $api_data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                header('Content-Type: application/json');
                echo json_encode([
                    "error" => "API response is not valid JSON",
                    "raw_response" => $response
                ]);
                exit;
            }

            // Pastikan format response sesuai
            if (!$api_data || !isset($api_data['result']) || !is_array($api_data['result'])) {
                continue;
            }
            $result = $api_data['result'];

            // Gabungkan data per tahun dan per bulan
            if (isset($result['series'])) {
                foreach ($result['series'] as $idx => $ser) {
                    $year = $tahun1 + $idx;
                    if (isset($series[$year])) {
                        foreach ($ser['data'] as $i => $val) {
                            $series[$year][$i] += $val;
                            $totalPerTahun[$year] += $val;
                        }
                    }
                }
                // Gabungkan stokSo
                if (isset($result['stokSo'])) {
                    foreach ($result['stokSo'] as $i => $val) {
                        $stokSo[$i] += $val;
                    }
                }
                // Gabungkan stokSisa
                if (isset($result['stokSisa'])) {
                    foreach ($result['stokSisa'] as $i => $val) {
                        $stokSisa[$i] += $val;
                    }
                }
            }
        }

        // Format series untuk frontend
        $series_out = [
            [
                "name" => "Tahun " . $tahun1,
                "data" => array_values($series[$tahun1])
            ],
            [
                "name" => "Tahun " . $tahun2,
                "data" => array_values($series[$tahun2])
            ],
            [
                "name" => "Tahun " . $tahun3,
                "data" => array_values($series[$tahun3])
            ]
        ];

        $result = [
            "labels" => $labels,
            "tahun1_label" => $tahun1,
            "tahun2_label" => $tahun2,
            "tahun3_label" => $tahun3,
            "series" => $series_out,
            "stokSisa" => array_values($stokSisa),
            "stokSo" => array_values($stokSo),
            "totalPerTahun" => $totalPerTahun,
            "source" => [
                "type" => "api",
                "name" => "All Cabang",
                "id" => "all"
            ]
        ];

        header('Content-Type: application/json');
        error_log("API Response: " . json_encode($result)); // Log respons API
        echo json_encode($result);
        exit;
    }

    // Jika "APL01 + APL02" dipilih
    if ($selected_apl === 'a_b') {
        // Ambil aplikasi APL01 dan APL02 saja
        $query = "SELECT * FROM aplikasi WHERE active_apl = 1 AND id_apl IN ('APL01', 'APL02')";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $selected_apps = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$selected_apps) {
            die(json_encode(["error" => "Cabang Puri dan Bekasi tidak ditemukan atau tidak aktif"]));
        }

        $all_produk = isset($_GET['all_produk']) ? 1 : 0;
        $id_pro = isset($_GET['id_pro']) ? $secu->injection($_GET['id_pro']) : null;
        $id_out = isset($_GET['id_out']) && $_GET['id_out'] !== '' ? $secu->injection($_GET['id_out']) : null;

        if (isset($_GET['all_produk'])) {
            $bulan_labels = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];
            $tahun_ini = date('Y');
            $tahun1 = $tahun_ini - 2;
            $tahun2 = $tahun_ini - 1;
            $tahun3 = $tahun_ini;

            $series = [
                $tahun1 => array_fill(0, 12, 0),
                $tahun2 => array_fill(0, 12, 0),
                $tahun3 => array_fill(0, 12, 0),
            ];
            $totalPerTahun = [
                $tahun1 => 0,
                $tahun2 => 0,
                $tahun3 => 0,
            ];

            foreach ($selected_apps as $app) {
                if (empty($app['base_url_apl']) || empty($app['key_apl'])) continue;

                $tgl = date('Y-m-d');
                $encrypt = md5($tgl . "#" . $app['key_apl']);

                $params = [];
                $params['all_produk'] = 1;
                $params['id_out'] = $id_out; // Tambahkan filter id_out
                if ($id_mg) $params['id_mg'] = $id_mg; // Tambahkan baris ini!
                
                // Tambahkan id_apl untuk identifikasi tabel yang digunakan
                $params['id_apl'] = $app['id_apl']; // Mengirim id_apl ke API
                
                $params['encrypt'] = $encrypt;
                $queryString = http_build_query($params);

                $api_url = rtrim($app['base_url_apl'], '/') . "/api/getProductStokDetail.php?$queryString";

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $api_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

                $response = curl_exec($ch);
                $curl_error = curl_error($ch);
                curl_close($ch);

                if ($curl_error) {
                    error_log("CURL ERROR: $curl_error ($api_url)");
                    continue;
                }

                $api_data = json_decode($response, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        "error" => "API response is not valid JSON",
                        "raw_response" => $response
                    ]);
                    exit;
                }

                // Gabungkan data berdasarkan tahun dan bulan
                if ($api_data && isset($api_data['result']['series'])) {
                    foreach ([$tahun1, $tahun2, $tahun3] as $idx => $tahun) {
                        if (isset($api_data['result']['series'][$idx]['data'])) {
                            foreach ($api_data['result']['series'][$idx]['data'] as $i => $val) {
                                $series[$tahun][$i] += $val;
                                $totalPerTahun[$tahun] += $val;
                            }
                        }
                    }
                }
            }

            // Format output agar sama dengan API satu cabang
            $series_out = [];
            foreach ([$tahun1, $tahun2, $tahun3] as $tahun) {
                $series_out[] = [
                    'name' => "Tahun " . $tahun,
                    'data' => array_values($series[$tahun])
                ];
            }

            $result = [
                'bulan_labels' => $bulan_labels,
                'series' => $series_out,
                'tahun1' => $tahun1,
                'tahun2' => $tahun2,
                'tahun3' => $tahun3,
                'totalPerTahun' => $totalPerTahun,
                'source' => [
                    'type' => 'api',
                    'name' => 'Puri + Puri B',
                    'id' => 'a_b'
                ]
            ];

            header('Content-Type: application/json');
            error_log("API Response: " . json_encode($result)); // Log respons API
            echo json_encode($result);
            exit;
        }

        $labels = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];
        $tahun_ini = date('Y');
        $tahun1 = $tahun_ini - 2;
        $tahun2 = $tahun_ini - 1;
        $tahun3 = $tahun_ini;

        $series = [
            $tahun1 => array_fill(0, 12, 0),
            $tahun2 => array_fill(0, 12, 0),
            $tahun3 => array_fill(0, 12, 0),
        ];
        $stokSisa = array_fill(0, 12, 0);
        $stokSo = array_fill(0, 12, 0);
        $totalPerTahun = [
            $tahun1 => 0,
            $tahun2 => 0,
            $tahun3 => 0,
        ];

        foreach ($selected_apps as $app) {
            if (empty($app['base_url_apl']) || empty($app['key_apl'])) continue;

            $tgl = date('Y-m-d');
            $encrypt = md5($tgl . "#" . $app['key_apl']);

            $params = [];
            if ($id_pro) $params['id_pro'] = $id_pro;
            if ($id_out) $params['id_out'] = $id_out;
            if ($id_mg) $params['id_mg'] = $id_mg; // Tambahkan baris ini!
            if ($all_produk) $params['all_produk'] = 1;
            
            // Tambahkan id_apl untuk identifikasi tabel yang digunakan
            $params['id_apl'] = $app['id_apl']; // Mengirim id_apl ke API
            
            $params['encrypt'] = $encrypt;
            $queryString = http_build_query($params);

            $api_url = rtrim($app['base_url_apl'], '/') . "/api/getProductStokDetail.php?$queryString";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

            $response = curl_exec($ch);
            $curl_error = curl_error($ch);
            curl_close($ch);

            if ($curl_error) {
                error_log("CURL ERROR: $curl_error ($api_url)");
                continue;
            }

            $api_data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                header('Content-Type: application/json');
                echo json_encode([
                    "error" => "API response is not valid JSON",
                    "raw_response" => $response
                ]);
                exit;
            }

            // Pastikan format response sesuai
            if (!$api_data || !isset($api_data['result']) || !is_array($api_data['result'])) {
                continue;
            }
            $result_api = $api_data['result'];

            // Gabungkan data per tahun dan per bulan
            if (isset($result_api['series'])) {
                foreach ($result_api['series'] as $idx => $ser) {
                    $year = $tahun1 + $idx;
                    if (isset($series[$year])) {
                        foreach ($ser['data'] as $i => $val) {
                            $series[$year][$i] += $val;
                            $totalPerTahun[$year] += $val;
                        }
                    }
                }
                // Gabungkan stokSo
                if (isset($result_api['stokSo'])) {
                    foreach ($result_api['stokSo'] as $i => $val) {
                        $stokSo[$i] += $val;
                    }
                }
                // Gabungkan stokSisa
                if (isset($result_api['stokSisa'])) {
                    foreach ($result_api['stokSisa'] as $i => $val) {
                        $stokSisa[$i] += $val;
                    }
                }
            }
        }

        // Format series untuk frontend
        $series_out = [
            [
                "name" => "Tahun " . $tahun1,
                "data" => array_values($series[$tahun1])
            ],
            [
                "name" => "Tahun " . $tahun2,
                "data" => array_values($series[$tahun2])
            ],
            [
                "name" => "Tahun " . $tahun3,
                "data" => array_values($series[$tahun3])
            ]
        ];

        $result = [
            "labels" => $labels,
            "tahun1_label" => $tahun1,
            "tahun2_label" => $tahun2,
            "tahun3_label" => $tahun3,
            "series" => $series_out,
            "stokSisa" => array_values($stokSisa),
            "stokSo" => array_values($stokSo),
            "totalPerTahun" => $totalPerTahun,
            "source" => [
                "type" => "api",
                "name" => "Puri + Puri B",
                "id" => "a_b"
            ]
        ];

        header('Content-Type: application/json');
        error_log("API Response: " . json_encode($result)); // Log respons API
        echo json_encode($result);
        exit;
    }

    // Jika ada aplikasi yang dipilih, ambil informasinya
    if ($selected_apl) {
        $query = "SELECT * FROM aplikasi WHERE id_apl = :id_apl AND active_apl = 1";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id_apl', $selected_apl, PDO::PARAM_STR);
        $stmt->execute();
        $source_info = $stmt->fetch(PDO::FETCH_ASSOC);

        // Jika aplikasi ditemukan dan bukan self_apl (aplikasi saat ini)
        if ($source_info && $source_info['self_apl'] != 1) {
            // Tutup koneksi saat ini
            $base->close();

            // Menggunakan API untuk mengakses data
            if (!empty($source_info['base_url_apl']) && !empty($source_info['key_apl'])) {
                $tgl = date('Y-m-d');
                $encrypt = md5($tgl . "#" . $source_info['key_apl']);

                // Dapatkan parameter dari request asli
                $all_produk = isset($_GET['all_produk']) ? 1 : 0;
                $id_pro = isset($_GET['id_pro']) ? $_GET['id_pro'] : null;
                $id_out = isset($_GET['id_out']) && $_GET['id_out'] !== '' ? $_GET['id_out'] : null;

                // Buat URL API
                $api_url = rtrim($source_info['base_url_apl'], '/');
                $api_endpoint = '';

                if ($all_produk) {
                    $api_endpoint = "/api/getProductStokDetail.php?all_produk=1&encrypt={$encrypt}";
                    if ($id_out) {
                        $api_endpoint .= "&id_out={$id_out}";
                    }
                    if ($id_mg) {
                        $api_endpoint .= "&id_mg={$id_mg}";
                    }
                    $api_endpoint .= "&id_apl={$selected_apl}";
                } else if ($id_pro) {
                    $api_endpoint = "/api/getProductStokDetail.php?id_pro={$id_pro}&encrypt={$encrypt}";
                    if ($id_out) {
                        $api_endpoint .= "&id_out={$id_out}";
                    }
                    if ($id_mg) {
                        $api_endpoint .= "&id_mg={$id_mg}";
                    }
                    $api_endpoint .= "&id_apl={$selected_apl}";
                } else {
                    die(json_encode(["error" => "Parameter tidak lengkap untuk API request"]));
                }

                $full_url = $api_url . $api_endpoint;

                // Panggil API menggunakan cURL
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $full_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

                $response = curl_exec($ch);
                $curl_error = curl_error($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($curl_error) {
                    error_log("CURL ERROR: $curl_error ($full_url)");
                    die(json_encode([
                        "error" => "API request error: " . $curl_error,
                        "url" => $full_url,
                        "http_code" => $http_code
                    ]));
                }

                // Parse response
                $api_result = json_decode($response, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        "error" => "API response is not valid JSON",
                        "raw_response" => $response
                    ]);
                    exit;
                }

                if (!$api_result || !isset($api_result['result']) || $api_result['result'] === "Error" || $api_result['result'] === "Unauthorized") {
                    die(json_encode([
                        "error" => "API response error",
                        "api_response" => $api_result,
                        "url" => $full_url
                    ]));
                }



                // Kirim response langsung dari API
                header('Content-Type: application/json');
                error_log("API Response: " . json_encode($api_result['result'])); // Log respons API
                echo json_encode($api_result['result']);
                exit;
            } else {
                die(json_encode(["error" => "Tidak ada informasi API untuk cabang yang dipilih"]));
            }
        } else if ($source_info && $source_info['self_apl'] == 1) {
            // Ini adalah aplikasi saat ini, tambahkan informasi sumber ke source_info
            // Koneksi sudah dibuka di atas
        } else {
            die(json_encode(["error" => "Cabang tidak ditemukan atau tidak aktif"]));
        }
    }

    $tahun_ini = date('Y');
    $bulan_ini = date('n');
    $tahun1 = $tahun_ini - 2;
    $tahun2 = $tahun_ini - 1;
    $tahun3 = $tahun_ini;

    // Fungsi untuk semua produk - kode tidak berubah
    function allproduk($conn, $tahun1, $tahun3, $id_out = null, $id_mg = null) {
        // Ambil daftar id_out berdasarkan id_mg
        $id_out_list = [];
        if ($id_mg) {
            $id_out_list = getOutletsByGroup($conn, $id_mg);
            if (empty($id_out_list)) {
                header('Content-Type: application/json');
                echo json_encode(["error" => "Tidak ada outlet pada grup ini"]);
                exit;
            }
        }

        $query = "
            SELECT YEAR(T.tgl_tfk) AS tahun, 
                   MONTH(T.tgl_tfk) AS bulan, 
                   COALESCE(SUM(TF.total_tfd), 0) AS total
            FROM (
                SELECT id_tfk, tgl_tfk, id_out FROM transaksi_faktur
                UNION ALL
                SELECT id_tfk, tgl_tfk, id_out FROM transaksi_faktur_pim
            ) AS T
            JOIN (
                SELECT id_tfk, total_tfd FROM transaksi_fakturdetail
                UNION ALL
                SELECT id_tfk, total_tfd FROM transaksi_fakturdetail_pim
            ) AS TF ON T.id_tfk = TF.id_tfk
            WHERE YEAR(T.tgl_tfk) BETWEEN :tahun1 AND :tahun3
            " . ($id_out ? "AND T.id_out = :id_out " : "") . "
            " . (!empty($id_out_list) ? "AND T.id_out IN (" . implode(',', array_map(fn($i) => ":id_out_$i", array_keys($id_out_list))) . ") " : "") . "
            GROUP BY tahun, bulan
            ORDER BY tahun ASC, bulan ASC
        ";

        $stmt = $conn->prepare($query);
        $stmt->bindParam(':tahun1', $tahun1, PDO::PARAM_INT);
        $stmt->bindParam(':tahun3', $tahun3, PDO::PARAM_INT);
        if ($id_out) {
            $stmt->bindParam(':id_out', $id_out, PDO::PARAM_STR);
        }
        if (!empty($id_out_list)) {
            foreach ($id_out_list as $index => $id_out_value) {
                $stmt->bindValue(":id_out_$index", $id_out_value, PDO::PARAM_STR);
            }
        }
        $stmt->execute();

        $data = [];
        $totalPerTahun = [];

        // Pastikan data awal lengkap untuk semua tahun dan bulan
        for ($tahun = $tahun1; $tahun <= $tahun3; $tahun++) {
            $data[$tahun] = array_fill(1, 12, 0);
            $totalPerTahun[$tahun] = 0;
        }

        // Isi data dari hasil query
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tahun = $row['tahun'];
            $bulan = $row['bulan'];
            $total = floatval($row['total']);

            $data[$tahun][$bulan] = $total;
            $totalPerTahun[$tahun] += $total;
        }

        // Buat label bulan
        $bulan_labels = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];

        // Format data untuk dikirim ke frontend
        $series = [];
        for ($tahun = $tahun1; $tahun <= $tahun3; $tahun++) {
            $series[] = [
                'name' => "Tahun " . $tahun,
                'data' => array_values($data[$tahun])
            ];
        }

        $result = [
            'bulan_labels' => $bulan_labels,
            'series' => $series,
            'tahun1' => $tahun1,
            'tahun2' => $tahun1 + 1,
            'tahun3' => $tahun3,
            'totalPerTahun' => $totalPerTahun
        ];

        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    // Fungsi untuk mendapatkan outlet berdasarkan grup
    function getOutletsByGroup($conn, $id_mg) {
        error_log("getOutletsByGroup called with id_mg: " . $id_mg); // Log id_mg

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

    // Fungsi untuk transaksi faktur
    function transaksiFaktur($conn, $kode, $id_out, $id_mg, $tahun1, $tahun3)
    {
        // Ambil daftar id_out berdasarkan id_mg (hanya untuk transaksi faktur)
        $id_out_list = [];
        if ($id_mg) {
            $id_out_list = getOutletsByGroup($conn, $id_mg);
            if (empty($id_out_list)) {
                header('Content-Type: application/json');
                echo json_encode(["error" => "Tidak ada outlet pada grup ini"]);
                exit;
            }
        }

        // Buat filter outlet
        $where_outlet = '';
        if ($id_out) {
            $where_outlet = "AND T.id_out = :id_out ";
        } elseif (!empty($id_out_list)) {
            $in_params = array_map(function($i) { return ":id_out_$i"; }, array_keys($id_out_list));
            $where_outlet = "AND T.id_out IN (" . implode(',', $in_params) . ") ";
        }

        // Query transaksi_faktur (selalu ada)
        $query = "
            SELECT YEAR(T.tgl_tfk) AS tahun, 
                   MONTH(T.tgl_tfk) AS bulan, 
                   COALESCE(SUM(TF.jumlah_tfd), 0) AS total 
            FROM (
                SELECT id_tfk, tgl_tfk, id_out FROM transaksi_faktur
                UNION ALL
                SELECT id_tfk, tgl_tfk, id_out FROM transaksi_faktur_pim
            ) AS T
            JOIN (
                SELECT id_tfk, jumlah_tfd, id_pro FROM transaksi_fakturdetail
                UNION ALL
                SELECT id_tfk, jumlah_tfd, id_pro FROM transaksi_fakturdetail_pim
            ) AS TF ON T.id_tfk = TF.id_tfk
            WHERE TF.id_pro = :kode
            $where_outlet
            AND YEAR(T.tgl_tfk) BETWEEN :tahun1 AND :tahun3
            GROUP BY tahun, bulan
        ";

        // Tambahkan UNION ALL transferstock HANYA jika TIDAK filter grup DAN TIDAK filter outlet
        if (empty($id_mg) && empty($id_out_list)) {
         $query .= "
                UNION ALL
                SELECT YEAR(TS.tgl_ttr) AS tahun,
                       MONTH(TS.tgl_ttr) AS bulan,
                       COALESCE(SUM(TSD.jumlah_ttd), 0) AS total
                FROM transaksi_transferstock AS TS
                JOIN transaksi_transferstockdetail AS TSD ON TS.id_ttr = TSD.id_ttr
                WHERE TSD.id_pro = :kode
                  AND TS.tipe_ttr = 'OUT'
                  AND YEAR(TS.tgl_ttr) BETWEEN :tahun1 AND :tahun3
                GROUP BY tahun, bulan
            ";
        }

        $query .= " ORDER BY tahun ASC, bulan ASC";

        $stmt = $conn->prepare($query);
        $stmt->bindParam(':kode', $kode, PDO::PARAM_STR);

        // Binding untuk transaksi faktur
        if ($id_out) {
            $stmt->bindParam(':id_out', $id_out, PDO::PARAM_STR);
        } elseif (!empty($id_out_list)) {
            foreach ($id_out_list as $i => $outlet) {
                $stmt->bindValue(":id_out_$i", $outlet, PDO::PARAM_STR);
            }
        }

        $stmt->bindParam(':tahun1', $tahun1, PDO::PARAM_INT);
        $stmt->bindParam(':tahun3', $tahun3, PDO::PARAM_INT);
        $stmt->execute();

        // Proses hasil query seperti biasa...
        $tempData = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tahun = $row['tahun'];
            $bulan = $row['bulan'];
            if (!isset($tempData[$tahun][$bulan])) {
                $tempData[$tahun][$bulan] = 0;
            }
            $tempData[$tahun][$bulan] += $row['total'];
        }

        // Format data akhir
        $data = [];
        foreach ($tempData as $tahun => $bulanData) {
            foreach ($bulanData as $bulan => $total) {
                $data[$tahun][$bulan] = $total;
            }
        }

        return $data;
    }

    // Function untuk mengambil data transaksi bulan sebelumnya
    function transaksiRDBulanSebelumnya($conn, $kode)
    {
        // Ambil bulan dan tahun berjalan
        $bulan_ini = date('n'); // 1-12
        $tahun_ini = date('Y');
        
        // Inisialisasi array hasil dengan 0 untuk semua bulan
        $transaksiRDBulanSebelumnya = array_fill(0, 12, 0);
        
        // Untuk setiap bulan, ambil data dari bulan sebelumnya
        for ($i = 1; $i <= 12; $i++) {
            // Hitung bulan sebelumnya
            $bulan_sebelumnya = $i - 1;
            $tahun_query = $tahun_ini;
            
            // Jika bulan sebelumnya adalah 0 (Desember tahun sebelumnya)
            if ($bulan_sebelumnya <= 0) {
                $bulan_sebelumnya += 12;
                $tahun_query -= 1;
            }
            
            // Format tanggal untuk query
            $startDate = date('Y-m-01', strtotime("$tahun_query-$bulan_sebelumnya-01"));
            $endDate = date('Y-m-t', strtotime("$tahun_query-$bulan_sebelumnya-01"));
            
            // Query untuk mengambil data transaksi RD bulan sebelumnya
            $query = "
                SELECT COALESCE(SUM(jumlah_trd), 0) AS total
                FROM transaksi_receivedetail
                WHERE id_pro = :kode
                  AND created_at >= :startDate AND created_at <= :endDate
            ";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':kode', $kode, PDO::PARAM_STR);
            $stmt->bindParam(':startDate', $startDate, PDO::PARAM_STR);
            $stmt->bindParam(':endDate', $endDate, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Simpan total ke array hasil (index 0-11 untuk Jan-Des)
            $transaksiRDBulanSebelumnya[$i-1] = floatval($result['total']);
        }
        
        return $transaksiRDBulanSebelumnya;
    }

    // Periksa jika parameter all_produk ada
    if (isset($_GET['all_produk'])) {
        $id_out = isset($_GET['id_out']) && $_GET['id_out'] !== '' ? $secu->injection($_GET['id_out']) : null;

        if ($selected_apl === 'all' || $selected_apl === 'a_b') {
            $labels = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];
            $tahun_ini = date('Y');
            $tahun1 = $tahun_ini - 2;
            $tahun2 = $tahun_ini - 1;
            $tahun3 = $tahun_ini;

            $query = ($selected_apl === 'all')
                ? "SELECT * FROM aplikasi WHERE active_apl = 1"
                : "SELECT * FROM aplikasi WHERE active_apl = 1 AND id_apl IN ('APL01', 'APL02')";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $all_apps = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $series = [
                $tahun1 => array_fill(0, 12, 0),
                $tahun2 => array_fill(0, 12, 0),
                $tahun3 => array_fill(0, 12, 0),
            ];
            $totalPerTahun = [
                $tahun1 => 0,
                $tahun2 => 0,
                $tahun3 => 0,
            ];
            $stokSisa = array_fill(0, 12, 0);
            $stokSo = array_fill(0, 12, 0);

            foreach ($all_apps as $app) {
                if (empty($app['base_url_apl']) || empty($app['key_apl'])) continue;

                $tgl = date('Y-m-d');
                $encrypt = md5($tgl . "#" . $app['key_apl']);

                $params = [];
                $params['all_produk'] = 1;
                if ($id_out) $params['id_out'] = $id_out;
                if ($id_mg) $params['id_mg'] = $id_mg; // Tambahkan baris ini!
                
                // Tambahkan id_apl untuk identifikasi tabel yang digunakan
                $params['id_apl'] = $app['id_apl']; // Mengirim id_apl ke API
                
                $params['encrypt'] = $encrypt;
                $queryString = http_build_query($params);

                $api_url = rtrim($app['base_url_apl'], '/') . "/api/getProductStokDetail.php?$queryString";

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $api_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

                $response = curl_exec($ch);
                $curl_error = curl_error($ch);
                curl_close($ch);

                if ($curl_error) {
                    error_log("CURL ERROR: $curl_error ($api_url)");
                    continue;
                }

                $api_data = json_decode($response, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        "error" => "API response is not valid JSON",
                        "raw_response" => $response
                    ]);
                    exit;
                }

                // Pastikan format response sesuai
                if (!$api_data || !isset($api_data['result']['series'])) continue;

                foreach ([$tahun1, $tahun2, $tahun3] as $idx => $tahun) {
                    if (isset($api_data['result']['series'][$idx]['data'])) {
                        foreach ($api_data['result']['series'][$idx]['data'] as $i => $val) {
                            $series[$tahun][$i] += $val;
                            $totalPerTahun[$tahun] += $val;
                        }
                    }
                }
                // Gabungkan stokSo
                if (isset($api_data['result']['stokSo'])) {
                    foreach ($api_data['result']['stokSo'] as $i => $val) {
                        $stokSo[$i] += $val;
                    }
                }
                // Gabungkan stokSisa
                if (isset($api_data['result']['stokSisa'])) {
                    foreach ($api_data['result']['stokSisa'] as $i => $val) {
                        $stokSisa[$i] += $val;
                    }
                }
            }

            // Format output agar sama dengan API satu cabang
            $series_out = [];
            foreach ([$tahun1, $tahun2, $tahun3] as $tahun) {
                $series_out[] = [
                    'name' => "Tahun " . $tahun,
                    'data' => array_values($series[$tahun])
                ];
            }

            $result = [
                'labels' => $labels,
                'tahun1_label' => $tahun1,
                'tahun2_label' => $tahun2,
                'tahun3_label' => $tahun3,
                'series' => $series_out,
                'stokSisa' => array_values($stokSisa),
                'stokSo' => array_values($stokSo),
                'totalPerTahun' => $totalPerTahun,
                'source' => [
                    'type' => 'api',
                    'name' => ($selected_apl === 'all' ? 'All Cabang' : 'Puri + Puri B'),
                    'id' => $selected_apl
                ]
            ];

            header('Content-Type: application/json');
            error_log("API Response: " . json_encode($result)); // Log respons API
            echo json_encode($result);
            exit;
        }

        // Jika bukan all cabang, gunakan query lokal
        allproduk($conn, $tahun1, $tahun3, $id_out, $id_mg);
        // allproduk sudah menggunakan exit
    }

    $kode = isset($_GET['id_pro']) ? $secu->injection($_GET['id_pro']) : null;
    if (!$kode) {
        die(json_encode(["error" => "Parameter id_pro tidak ditemukan"]));
    }

    $id_out = isset($_GET['id_out']) && $_GET['id_out'] !== '' ? $secu->injection($_GET['id_out']) : null;

    function penjualanTertinggi($dataTransaksi)
    {
        $hasil = [];

        foreach ($dataTransaksi as $tahun => $bulanData) {
            $maxBulan = null;
            $maxJumlah = 0;

            foreach ($bulanData as $bulan => $jumlah) {
                if ($jumlah > $maxJumlah) {
                    $maxJumlah = $jumlah;
                    $maxBulan = $bulan;
                }
            }

            if ($maxBulan !== null) {
                $hasil[$tahun] = [
                    "bulan" => $maxBulan,
                    "jumlah" => $maxJumlah
                ];
            }
        }

        return $hasil;
    }

    function stoksisaTotal($conn, $kode)
    {
        $query = "
            SELECT COALESCE(SUM(sisa_psd), 0) AS total
            FROM produk_stokdetail
            WHERE id_pro = :kode
        ";

        $stmt = $conn->prepare($query);
        $stmt->bindParam(':kode', $kode, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['total'] ?? 0;
    }

    function stokSisaTampil($stokTotal, $bulan_ini)
    {
        $stokSisa = array_fill(1, 12, 0); // Set semua bulan ke 0
        $stokSisa[$bulan_ini] = $stokTotal;  // Hanya tampilkan di bulan sekarang
        return $stokSisa;
    }

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

    function transaksiRD($conn, $kode)
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

        // Siapkan array 12 bulan (tanpa geser)
        $transaksiRD = [];
        $labels = [];
        for ($i = 0; $i < 12; $i++) {
            $bulan = date('Y-m', strtotime("+$i month", strtotime($startDate)));
            $transaksiRD[$bulan] = 0;
            $labels[] = date('M Y', strtotime($bulan . '-01'));
        }

        foreach ($results as $row) {
            $bulan = $row['periode'];
            $transaksiRD[$bulan] = (float)$row['total'];
        }

        // Ambil hanya bulan 1 sampai bulan berjalan
        $jumlah_bulan = $bulan_ini;
        $transaksiRD = array_slice(array_values($transaksiRD), 0, $jumlah_bulan);

        return $transaksiRD;
    }

    // Ambil data transaksi faktur
    $dataTransaksi = transaksiFaktur($conn, $kode, $id_out, $id_mg, $tahun1, $tahun3);

    // Tambahkan pengecekan tipe data
    if (!is_array($dataTransaksi)) {
        header('Content-Type: application/json');
        echo json_encode(["error" => "Data transaksi tidak valid", "debug" => $dataTransaksi]);
        exit;
    }

    $finalData = [];
    foreach ([$tahun1, $tahun2, $tahun3] as $thn) {
        $finalData[$thn] = array_fill(1, 12, 0);
        if (isset($dataTransaksi[$thn]) && is_array($dataTransaksi[$thn])) {
            foreach ($dataTransaksi[$thn] as $bulan => $total) {
                $finalData[$thn][$bulan] = $total;
            }
        }
    }

    $stokTotal = stoksisaTotal($conn, $kode);
    $stokSisaFinal = stokSisaTampil($stokTotal, $bulan_ini);
    $stokSoBulan = stokSo($conn, $kode);
    $transaksiRDBulan = transaksiRD($conn, $kode);

    // Gabungkan stokSo dan transaksiRD per bulan
    $stokSoGabungan = [];
    $max_bulan = max(count($stokSoBulan), count($transaksiRDBulan));
    for ($i = 0; $i < $max_bulan; $i++) {
        $so = isset($stokSoBulan[$i]) ? $stokSoBulan[$i] : 0;
        $rd = isset($transaksiRDBulan[$i]) ? $transaksiRDBulan[$i] : 0;
        $stokSoGabungan[] = $so + $rd;
    }

    // Hitung Penjualan Tertinggi
    $penjualan_tertinggi = penjualanTertinggi($finalData);

    // Tambahkan ini di sekitar baris yang memproses data untuk response JSON
    $transaksiRDBulanSebelumnya = transaksiRDBulanSebelumnya($conn, $kode);

    // Buat array untuk menyimpan urutan tahun untuk setiap bulan
    // Bentuk hasil akhir dalam format JSON sesuai yang diminta
    $result = [
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
        "penjualan_tertinggi" => $penjualan_tertinggi,
        "transaksiRDBulanSebelumnya" => $transaksiRDBulanSebelumnya
    ];

    // Juga lakukan pengurutan untuk kebutuhan lain
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
    $result["monthlyOrder"] = $monthlyOrder;

    // Kirim data dalam format JSON
    header('Content-Type: application/json');
    error_log("API Response: " . json_encode($result)); // Log respons API
    echo json_encode($result);
    exit;

    // Tutup koneksi
    $base->close();
} catch (Exception $e) {
    // Bersihkan buffer
    ob_end_clean();
    
    // Catat error
    error_log("Error in produkg2.php: " . $e->getMessage() . " in " . $e->getFile() . " line " . $e->getLine());
    
    // Kirim error sebagai JSON
    echo json_encode([
        "error" => true,
        "message" => $e->getMessage(),
        "file" => basename($e->getFile()),
        "line" => $e->getLine()
    ]); // Perbaiki closing bracket
} catch (Error $e) {
    // Tangani PHP Error
    ob_end_clean();
    error_log("Fatal Error in produkg2.php: " . $e->getMessage());
    echo json_encode([
        "error" => true,
        "message" => "Terjadi kesalahan internal: " . $e->getMessage()
    ]);
}

// Pastikan tidak ada output lain setelah JSON
exit;
?>