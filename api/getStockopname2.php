<?php
    // Enable error reporting untuk debugging
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    
    // Tambahkan logging untuk debugging
    error_log("getStockopname2.php called with key: " . ($_GET['key'] ?? 'none'));
    
    try {
        require_once('../config/connection/connection.php');
        require_once('../config/connection/security.php');
        require_once('../config/function/data.php');
    } catch (Exception $e) {
        error_log("Error loading required files: " . $e->getMessage());
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Failed to load required files: ' . $e->getMessage()]);
        exit;
    }

    $base = new DB;
    $secu = new Security;
    $data = new Data;
    
    try {
        $conn = $base->open();
    } catch (Exception $e) {
        error_log("Database connection error: " . $e->getMessage());
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
        exit;
    }

    // Validate API key
    $api_key = $secu->injection($_GET['key'] ?? '');
    $valid_key = false;
    
    try {
        $qkey = "SELECT key_apl, nama_apl FROM aplikasi WHERE key_apl = :key AND active_apl = 1";
        $key_stmt = $conn->prepare($qkey);
        $key_stmt->bindValue(':key', $api_key, PDO::PARAM_STR);
        $key_stmt->execute();
        $key_result = $key_stmt->fetch(PDO::FETCH_ASSOC);
        $valid_key = $key_result !== false;
    } catch (Exception $e) {
        error_log("API key validation error: " . $e->getMessage());
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'API key validation failed: ' . $e->getMessage()]);
        exit;
    }

    if (!$valid_key) {
        error_log("Invalid API key: " . $api_key);
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid API key', 'provided_key' => $api_key]);
        exit;
    }

    error_log("Valid API key from: " . $key_result['nama_apl']);

    // Cek apakah ini adalah mode local (dipanggil dari cabang lain)
    $local_mode = isset($_GET['mode']) && $_GET['mode'] === 'local';
    
    if ($local_mode) {
        error_log("Running in local mode - processing only self branch");
    }

    try {
        // Tanggal pemasukan tetap: hari pertama bulan lalu
        $tgl_masuk_display = date('Y-m-01', strtotime('-1 month'));

        // Rentang tanggal untuk query
        $start_date = date('Y-m-01', strtotime('-1 month')); // hari pertama bulan lalu
        $end_date = date('Y-m-d'); // tanggal hari ini

        // Rentang untuk Stok Awal (mulai dari akhir 2 bulan lalu)
        $start_date_table1 = date('Y-m-t', strtotime('-2 months'));
        $end_date_table1 = date('Y-m-d');

        $response = [
            'status' => 'success',
            'data' => [],
            'metadata' => [
                'tgl_masuk_display' => $tgl_masuk_display,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'start_date_stok_awal' => $start_date_table1,
                'total_cabang' => 0,
                'local_mode' => $local_mode
            ],
            'message' => 'Stock opname data retrieved successfully'
        ];

        // Tambahkan container terpisah untuk tabel 1/2/3
        $table_stok_awal = [];
        $table_penerimaan = [];
        $table_stok_akhir = [];

        // Jika mode local, hanya proses cabang self
        if ($local_mode) {
            $qCabang = "SELECT id_apl, nama_apl, base_url_apl, self_apl 
                       FROM aplikasi 
                       WHERE active_apl = 1 AND self_apl = 1
                       ORDER BY nama_apl";
        } else {
            // Mode normal - ambil semua cabang yang aktif
            $qCabang = "SELECT id_apl, nama_apl, base_url_apl, self_apl 
                       FROM aplikasi 
                       WHERE active_apl = 1 
                       ORDER BY nama_apl";
        }
        
        $stmtCabang = $conn->prepare($qCabang);
        $stmtCabang->execute();
        $cabangList = $stmtCabang->fetchAll(PDO::FETCH_ASSOC);

        $response['metadata']['total_cabang'] = count($cabangList);

        // Log jumlah cabang yang ditemukan
        error_log("Found " . count($cabangList) . " active branches" . ($local_mode ? " (local mode)" : ""));
        
        foreach($cabangList as $cabang) {
            $nama_cabang = $cabang['nama_apl'];
            error_log("Processing branch: " . $nama_cabang);
            
            // Skip jika ini adalah cabang yang sama dengan yang menjalankan API (self)
            if ($cabang['self_apl'] == 1) {
                error_log("Processing local branch (self): " . $nama_cabang);
                $connCabang = $conn; // Gunakan koneksi yang sudah ada
            } else {
                error_log("Processing remote branch: " . $nama_cabang);
                
                // Untuk cabang lain, kita perlu memanggil API mereka
                try {
                    // Array endpoint untuk dicoba berurutan
                    $endpoints_to_try = [
                        "/api/getLocalStockopname.php?key=" . urlencode($api_key),
                        "/api/getstockopname2.php?key=" . urlencode($api_key) . "&mode=local",
                        "/api/getstockopname.php?key=" . urlencode($api_key), // fallback ke API lama jika ada
                    ];
                    
                    $success = false;
                    $last_error = '';
                    
                    foreach ($endpoints_to_try as $endpoint) {
                        $remote_api_url = rtrim($cabang['base_url_apl'], '/') . $endpoint;
                        error_log("Trying endpoint: " . $remote_api_url);
                        
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $remote_api_url);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Kurangi timeout jadi 30 detik
                        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // Timeout koneksi 10 detik
                        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
                        curl_setopt($ch, CURLOPT_USERAGENT, 'StockOpname API Client/1.0');
                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                            'Accept: application/json',
                            'Cache-Control: no-cache'
                        ]);
                        
                        $remote_response = curl_exec($ch);
                        $remote_error = curl_error($ch);
                        $remote_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);
                        
                        if ($remote_error) {
                            $last_error = "cURL Error: " . $remote_error;
                            error_log("Endpoint failed with cURL error: " . $remote_error);
                            continue; // Coba endpoint berikutnya
                        }
                        
                        if ($remote_http_code == 302 || $remote_http_code == 404) {
                            $last_error = "HTTP Error: " . $remote_http_code;
                            error_log("Endpoint failed with HTTP " . $remote_http_code);
                            continue; // Coba endpoint berikutnya
                        }
                        
                        if ($remote_http_code != 200) {
                            $last_error = "HTTP Error: " . $remote_http_code;
                            error_log("Endpoint failed with HTTP " . $remote_http_code);
                            continue; // Coba endpoint berikutnya
                        }
                        
                        $remote_data = json_decode($remote_response, true);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            $last_error = "JSON Decode Error: " . json_last_error_msg();
                            error_log("Endpoint failed with JSON error: " . json_last_error_msg());
                            continue; // Coba endpoint berikutnya
                        }
                        
                        if (!isset($remote_data['status']) || $remote_data['status'] !== 'success') {
                            $last_error = "API Error: " . ($remote_data['error'] ?? 'Unknown error');
                            error_log("Endpoint failed with API error: " . $last_error);
                            continue; // Coba endpoint berikutnya
                        }
                        
                        // Sukses! Tambahkan data dari cabang remote ke response
                        if (isset($remote_data['data']) && is_array($remote_data['data'])) {
                            foreach ($remote_data['data'] as $item) {
                                $item['nama_cabang'] = $nama_cabang; // Override nama cabang
                                $response['data'][] = $item;
                                
                                // Tambahkan juga ke tabel yang sesuai berdasarkan jenis transaksi
                                switch ($item['jenis_transaksi']) {
                                    case 'Stok Awal':
                                        $table_stok_awal[] = $item;
                                        break;
                                    case 'Penerimaan':
                                        $table_penerimaan[] = $item;
                                        break;
                                    case 'Stok Akhir':
                                        $table_stok_akhir[] = $item;
                                        break;
                                }
                            }
                        }
                        
                        error_log("Successfully retrieved " . count($remote_data['data'] ?? []) . " records from remote branch: " . $nama_cabang);
                        $success = true;
                        break; // Keluar dari loop endpoint karena sudah berhasil
                    }
                    
                    if (!$success) {
                        throw new Exception($last_error ?: "All endpoints failed");
                    }
                    
                    continue; // Lanjut ke cabang berikutnya
                    
                } catch (Exception $e) {
                    error_log("Error calling remote API for {$nama_cabang}: " . $e->getMessage());
                    
                    // Jangan tambahkan data error ke response, hanya log saja
                    // $response['data'][] = [
                    //     'jenis_transaksi' => 'Error',
                    //     'tgl_pemasukan' => date('Y-m-d'),
                    //     'kode_obat_jadi' => '-',
                    //     'nama_produk' => 'Remote API Connection Failed',
                    //     'jumlah' => 0,
                    //     'batch' => '-',
                    //     'tgl_expired' => '-',
                    //     'no_faktur' => '-',
                    //     'sumber' => substr($e->getMessage(), 0, 100), // Batasi panjang error message
                    //     'nama_cabang' => $nama_cabang
                    // ];
                    continue; // Lanjut ke cabang berikutnya
                }
            }

            // Proses data lokal (untuk cabang self atau jika koneksi tersedia)
            try {
                // Query 1: Stok Awal dari cabang ini
                $master = $connCabang->prepare("
                    SELECT A.id, A.nama_pro, A.no_bcode, A.qty, A.bcode_so, A.qty_so, A.created_at,
                           PSD.tgl_expired AS tgl_expired, P.kode_produk_jadi, P.kategori_obat
                    FROM so AS A
                    LEFT JOIN produk_stokdetail AS PSD ON A.id_psd = PSD.id_psd
                    LEFT JOIN produk AS P ON A.id_pro = P.id_pro
                    WHERE DATE(A.created_at) BETWEEN :start_date AND :end_date
                    ORDER BY A.created_at DESC, A.nama_pro DESC
                ");
                $master->bindValue(':start_date', $start_date_table1);
                $master->bindValue(':end_date', $end_date_table1);
                $master->execute();

                while($hasil = $master->fetch(PDO::FETCH_ASSOC)){
                    $rec = [
                        'jenis_transaksi' => 'Stok Awal',
                        'tgl_pemasukan' => $tgl_masuk_display,
                        'kode_obat_jadi' => $hasil['kode_produk_jadi'] ?? '-',
                        'nama_produk' => $hasil['nama_pro'] ?? '-',
                        'kategori_obat' => $hasil['kategori_obat'] ?? '-',
                        'jumlah' => $hasil['qty_so'] ?? 0,
                        'batch' => $hasil['no_bcode'] ?? '-',
                        'tgl_expired' => $hasil['tgl_expired'] ?? '-',
                        'no_faktur' => '-',
                        'sumber' => '-',
                        'nama_cabang' => $nama_cabang
                    ];
                    $response['data'][] = $rec;
                    $table_stok_awal[] = $rec; // tambah ke tabel 1
                }

                // Query 2: Penerimaan dari cabang ini
                $master2 = $connCabang->prepare("
                    SELECT A.id, A.id_pro, A.nama_pro, A.no_bcode, A.qty, A.bcode_so, A.qty_so, A.created_at,
                           PSD.tgl_expired AS tgl_expired, P.kode_produk_jadi, P.kategori_obat
                    FROM so AS A
                    LEFT JOIN produk_stokdetail AS PSD ON A.id_psd = PSD.id_psd
                    LEFT JOIN produk AS P ON A.id_pro = P.id_pro
                    WHERE DATE(A.created_at) BETWEEN :start_date AND :end_date
                    ORDER BY A.created_at DESC, A.nama_pro DESC
                ");
                $master2->bindValue(':start_date', $start_date);
                $master2->bindValue(':end_date', $end_date);
                $master2->execute();

                // Query untuk mengambil setiap baris penerimaan (pisah per batch)
                $stmtRecvRows = $connCabang->prepare("
                    SELECT trd.jumlah_trd, trd.bcode_trd, trd.tbcode_trd,
                           tr.created_at AS recv_date, s.nama_sup
                    FROM transaksi_receivedetail trd
                    JOIN transaksi_receive tr ON trd.id_tre = tr.id_tre
                    LEFT JOIN supplier s ON tr.id_sup = s.id_sup
                    WHERE trd.id_pro = :id_pro
                      AND DATE(tr.created_at) BETWEEN :start_date AND :end_date
                    ORDER BY tr.created_at ASC
                ");
                
                while($hasil2 = $master2->fetch(PDO::FETCH_ASSOC)){
                    // ambil semua baris penerimaan untuk produk ini dalam rentang (dipisah per row)
                    $stmtRecvRows->bindValue(':id_pro', $hasil2['id_pro']);
                    $stmtRecvRows->bindValue(':start_date', $start_date);
                    $stmtRecvRows->bindValue(':end_date', $end_date);
                    $stmtRecvRows->execute();
                    $recvRows = $stmtRecvRows->fetchAll(PDO::FETCH_ASSOC);

                    if(count($recvRows) > 0){
                        // tampilkan satu baris per transaksi_receivedetail (batch terpisah)
                        foreach($recvRows as $r){
                            $tgl_masuk = !empty($r['recv_date']) ? date('Y-m-d', strtotime($r['recv_date'])) : date('Y-m-d', strtotime($hasil2['created_at']));
                            $jumlah_penerimaan = $r['jumlah_trd'];
                            $batch_penerimaan = !empty($r['bcode_trd']) ? $r['bcode_trd'] : '-';
                            $expired_penerimaan = !empty($r['tbcode_trd']) ? $r['tbcode_trd'] : '-';
                            $no_faktur_recv = '-';
                            $supplier_name = !empty($r['nama_sup']) ? $r['nama_sup'] : '-';

                            $rec = [
                                'jenis_transaksi' => 'Penerimaan',
                                'tgl_pemasukan' => $tgl_masuk,
                                'kode_obat_jadi' => $hasil2['kode_produk_jadi'],
                                'nama_produk' => $hasil2['nama_pro'],
                                'kategori_obat' => $hasil2['kategori_obat'] ?? '-',
                                'jumlah' => $jumlah_penerimaan,
                                'batch' => $batch_penerimaan,
                                'tgl_expired' => $expired_penerimaan,
                                'no_faktur' => $no_faktur_recv,
                                'sumber' => $supplier_name,
                                'nama_cabang' => $nama_cabang
                            ];
                            $response['data'][] = $rec;
                            $table_penerimaan[] = $rec; // tambah ke tabel 2
                        }
                    } else {
                        // tidak ada baris dalam transaksi_receivedetail -> tampilkan fallback (data dari so)
                        $jumlah_penerimaan = 0;
                        $tgl_masuk = date('Y-m-d', strtotime($hasil2['created_at']));
                        $batch_penerimaan = !empty($hasil2['no_bcode']) ? $hasil2['no_bcode'] : '-';
                        $expired_penerimaan = !empty($hasil2['tgl_expired']) ? $hasil2['tgl_expired'] : '-';
                        $no_faktur_recv = '-';
                        $supplier_name = '-';

                        $rec = [
                            'jenis_transaksi' => 'Penerimaan',
                            'tgl_pemasukan' => $tgl_masuk,
                            'kode_obat_jadi' => $hasil2['kode_produk_jadi'],
                            'nama_produk' => $hasil2['nama_pro'],
                            'kategori_obat' => $hasil2['kategori_obat'] ?? '-',
                            'jumlah' => $jumlah_penerimaan,
                            'batch' => $batch_penerimaan,
                            'tgl_expired' => $expired_penerimaan,
                            'no_faktur' => $no_faktur_recv,
                            'sumber' => $supplier_name,
                            'nama_cabang' => $nama_cabang
                        ];
                        $response['data'][] = $rec;
                        $table_penerimaan[] = $rec; // fallback juga ke tabel 2
                    }
                }

                // Query 3: Stok Akhir dari cabang ini
                $master3 = $connCabang->prepare("
                    SELECT A.id, A.id_psd, A.created_at, A.id_pro, A.no_bcode,
                           A.qty AS jumlah,
                           P.kode_produk_jadi, A.nama_pro, P.kategori_obat,
                           PSD.tgl_expired AS tgl_expired
                    FROM so AS A
                    LEFT JOIN produk_stokdetail AS PSD ON A.id_psd = PSD.id_psd
                    LEFT JOIN produk AS P ON A.id_pro = P.id_pro
                    WHERE DATE(A.created_at) BETWEEN :start_date AND :end_date
                    ORDER BY A.created_at DESC
                ");
                $master3->bindValue(':start_date', $start_date);
                $master3->bindValue(':end_date', $end_date);
                $master3->execute();

                // siapkan statement untuk tanggal terakhir (last_date) per produk
                $stmtLast = $connCabang->prepare("
                    SELECT MAX(created_at) AS last_date
                    FROM so
                    WHERE id_pro = :id_pro
                      AND DATE(created_at) BETWEEN :start_date AND :end_date
                ");

                while($hasil3 = $master3->fetch(PDO::FETCH_ASSOC)){
                    // ambil tanggal pemasukan terakhir untuk produk ini dalam rentang; fallback ke row created_at
                    $stmtLast->bindValue(':id_pro', $hasil3['id_pro']);
                    $stmtLast->bindValue(':start_date', $start_date);
                    $stmtLast->bindValue(':end_date', $end_date);
                    $stmtLast->execute();
                    $last = $stmtLast->fetch(PDO::FETCH_ASSOC);
                    $tgl_masuk = !empty($last['last_date']) ? date('Y-m-d', strtotime($last['last_date'])) : date('Y-m-d', strtotime($hasil3['created_at']));

                    // ambil tgl_expired dari produk_stokdetail via id_psd; tampilkan '-' jika kosong
                    $expired_akhir = !empty($hasil3['tgl_expired']) ? $hasil3['tgl_expired'] : '-';

                    $response['data'][] = [
                        'jenis_transaksi' => 'Stok Akhir',
                        'tgl_pemasukan' => $tgl_masuk,
                        'kode_obat_jadi' => $hasil3['kode_produk_jadi'],
                        'nama_produk' => $hasil3['nama_pro'],
                        'kategori_obat' => $hasil3['kategori_obat'] ?? '-',
                        'jumlah' => $hasil3['jumlah'],
                        'batch' => $hasil3['no_bcode'],
                        'tgl_expired' => $expired_akhir,
                        'no_faktur' => '-',
                        'sumber' => '-',
                        'nama_cabang' => $nama_cabang
                    ];
                    // ambil record terakhir yang baru saja dimasukkan dan juga tambahkan ke tabel stok akhir
                    $table_stok_akhir[] = end($response['data']); // atau buat $rec seperti di atas
                }

            } catch (Exception $e) {
                // Log error untuk cabang ini, tapi lanjutkan ke cabang lain
                error_log("Error processing local branch {$nama_cabang}: " . $e->getMessage());
                
                // Jangan tambahkan data error ke response, hanya log saja
                // $response['data'][] = [
                //     'jenis_transaksi' => 'Error',
                //     'tgl_pemasukan' => date('Y-m-d'),
                //     'kode_obat_jadi' => '-',
                //     'nama_produk' => 'Local Processing Error',
                //     'jumlah' => 0,
                //     'batch' => '-',
                //     'tgl_expired' => '-',
                //     'no_faktur' => '-',
                //     'sumber' => $e->getMessage(),
                //     'nama_cabang' => $nama_cabang
                // ];
            }
        }

        error_log("Total records generated: " . count($response['data']));
        
        // Sortir data berdasarkan jenis transaksi terlebih dahulu, kemudian tanggal pemasukan
        usort($response['data'], function($a, $b) {
            // Prioritas pertama: jenis transaksi
            $order = ['Stok Awal' => 1, 'Penerimaan' => 2, 'Stok Akhir' => 3];
            $order_a = $order[$a['jenis_transaksi']] ?? 4;
            $order_b = $order[$b['jenis_transaksi']] ?? 4;
            
            if ($order_a != $order_b) {
                return $order_a <=> $order_b; // Urutkan berdasarkan jenis transaksi
            }
            
            // Prioritas kedua: tanggal pemasukan
            $date_a = strtotime($a['tgl_pemasukan']);
            $date_b = strtotime($b['tgl_pemasukan']);
            
            if ($date_a != $date_b) {
                return $date_a <=> $date_b; // Urutkan berdasarkan tanggal (ascending)
            }
            
            // Prioritas ketiga: nama cabang
            if ($a['nama_cabang'] != $b['nama_cabang']) {
                return $a['nama_cabang'] <=> $b['nama_cabang'];
            }
            
            // Prioritas keempat: nama produk
            return $a['nama_produk'] <=> $b['nama_produk'];
        });

        // Sortir tabel terpisah tetap berdasarkan tanggal pemasukan dan cabang (untuk keperluan laporan terpisah)
        usort($table_stok_awal, function($a, $b) {
            $date_a = strtotime($a['tgl_pemasukan']);
            $date_b = strtotime($b['tgl_pemasukan']);
            if ($date_a != $date_b) {
                return $date_a <=> $date_b;
            }
            if ($a['nama_cabang'] != $b['nama_cabang']) {
                return $a['nama_cabang'] <=> $b['nama_cabang'];
            }
            return $a['nama_produk'] <=> $b['nama_produk'];
        });

        usort($table_penerimaan, function($a, $b) {
            $date_a = strtotime($a['tgl_pemasukan']);
            $date_b = strtotime($b['tgl_pemasukan']);
            if ($date_a != $date_b) {
                return $date_a <=> $date_b;
            }
            if ($a['nama_cabang'] != $b['nama_cabang']) {
                return $a['nama_cabang'] <=> $b['nama_cabang'];
            }
            return $a['nama_produk'] <=> $b['nama_produk'];
        });

        usort($table_stok_akhir, function($a, $b) {
            $date_a = strtotime($a['tgl_pemasukan']);
            $date_b = strtotime($b['tgl_pemasukan']);
            if ($date_a != $date_b) {
                return $date_a <=> $date_b;
            }
            if ($a['nama_cabang'] != $b['nama_cabang']) {
                return $a['nama_cabang'] <=> $b['nama_cabang'];
            }
            return $a['nama_produk'] <=> $b['nama_produk'];
        });

        // Tambahkan objek tabel terpisah ke response
        $response['tables'] = [
            'stok_awal' => $table_stok_awal,
            'penerimaan' => $table_penerimaan,
            'stok_akhir' => $table_stok_akhir
        ];

        $response['metadata']['total_records'] = count($response['data']);
        $response['metadata']['total_stok_awal'] = count($table_stok_awal);
        $response['metadata']['total_penerimaan'] = count($table_penerimaan);
        $response['metadata']['total_stok_akhir'] = count($table_stok_akhir);

        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode($response);

    } catch (Exception $e) {
        error_log("getStockopname2.php main error: " . $e->getMessage());
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    } finally {
        if (isset($conn)) {
            $conn = $base->close();
        }
    }
?>