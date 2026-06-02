<?php
    // Enable error reporting untuk debugging
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    
    // Tambahkan logging untuk debugging
    error_log("getLocalStockopname.php called with key: " . ($_GET['key'] ?? 'none'));
    
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
                'total_cabang' => 1,
                'local_mode' => true
            ],
            'message' => 'Local stock opname data retrieved successfully'
        ];

        // Hanya ambil cabang self (local)
        $qCabang = "SELECT id_apl, nama_apl, base_url_apl, self_apl 
                   FROM aplikasi 
                   WHERE active_apl = 1 AND self_apl = 1
                   ORDER BY nama_apl";
        $stmtCabang = $conn->prepare($qCabang);
        $stmtCabang->execute();
        $cabangList = $stmtCabang->fetchAll(PDO::FETCH_ASSOC);

        if (empty($cabangList)) {
            throw new Exception("No local branch found");
        }

        $cabang = $cabangList[0]; // Ambil cabang pertama (seharusnya hanya ada satu)
        $nama_cabang = $cabang['nama_apl'];
        
        error_log("Processing local branch: " . $nama_cabang);

        // Query 1: Stok Awal dari cabang ini
        $master = $conn->prepare("
            SELECT A.id, A.nama_pro, A.no_bcode, A.qty, A.bcode_so, A.qty_so, A.created_at,
                   PSD.tgl_expired AS tgl_expired, P.kode_produk_jadi, P.kategori_obat
            FROM so AS A
            LEFT JOIN produk_stokdetail AS PSD ON A.id_psd = PSD.id_psd
            LEFT JOIN produk AS P ON A.id_pro = P.id_pro
            WHERE DATE(A.created_at) BETWEEN :start_date AND :end_date
            ORDER BY A.created_at DESC, A.nama_pro DESC
            LIMIT 1000
        ");
        $master->bindValue(':start_date', $start_date_table1);
        $master->bindValue(':end_date', $end_date_table1);
        $master->execute();

        while($hasil = $master->fetch(PDO::FETCH_ASSOC)){
            $response['data'][] = [
                'jenis_transaksi' => 'Stok Awal',
                'tgl_pemasukan' => $tgl_masuk_display,
                'kode_obat_jadi' => $hasil['kode_produk_jadi'] ?? '-',
                'kategori_obat' => $hasil['kategori_obat'] ?? '-',
                'nama_produk' => $hasil['nama_pro'] ?? '-',
                'jumlah' => $hasil['qty_so'] ?? 0,
                'batch' => $hasil['no_bcode'] ?? '-',
                'tgl_expired' => $hasil['tgl_expired'] ?? '-',
                'no_faktur' => '-',
                'sumber' => '-',
                'nama_cabang' => $nama_cabang
            ];
        }

        // Query 2: Penerimaan dari cabang ini (dibatasi untuk performa)
        $master2 = $conn->prepare("
            SELECT A.id, A.id_pro, A.nama_pro, A.no_bcode, A.qty, A.bcode_so, A.qty_so, A.created_at,
                   PSD.tgl_expired AS tgl_expired, P.kode_produk_jadi, P.kategori_obat
            FROM so AS A
            LEFT JOIN produk_stokdetail AS PSD ON A.id_psd = PSD.id_psd
            LEFT JOIN produk AS P ON A.id_pro = P.id_pro
            WHERE DATE(A.created_at) BETWEEN :start_date AND :end_date
            ORDER BY A.created_at DESC, A.nama_pro DESC
            LIMIT 1000
        ");
        $master2->bindValue(':start_date', $start_date);
        $master2->bindValue(':end_date', $end_date);
        $master2->execute();

        // Query untuk mengambil setiap baris penerimaan (pisah per batch)
        $stmtRecvRows = $conn->prepare("
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
                    $supplier_name = !empty($r['nama_sup']) ? $r['nama_sup'] : '-';

                    $response['data'][] = [
                        'jenis_transaksi' => 'Penerimaan',
                        'tgl_pemasukan' => $tgl_masuk,
                        'kode_obat_jadi' => $hasil2['kode_produk_jadi'],
                        'nama_produk' => $hasil2['nama_pro'],
                                                        'kategori_obat' => $hasil2['kategori_obat'] ?? '-',

                        'jumlah' => $jumlah_penerimaan,
                        'batch' => $batch_penerimaan,
                        'tgl_expired' => $expired_penerimaan,
                        'no_faktur' => '-',
                        'sumber' => $supplier_name,
                        'nama_cabang' => $nama_cabang
                    ];
                }
            }
        }

        // Query 3: Stok Akhir dari cabang ini (dibatasi untuk performa)
        $master3 = $conn->prepare("
            SELECT A.id, A.id_psd, A.created_at, A.id_pro, A.no_bcode,
                   A.qty AS jumlah,
                   P.kode_produk_jadi, A.nama_pro,
                   PSD.tgl_expired AS tgl_expired
            FROM so AS A
            LEFT JOIN produk_stokdetail AS PSD ON A.id_psd = PSD.id_psd
            LEFT JOIN produk AS P ON A.id_pro = P.id_pro P.kategori_obat,
            WHERE DATE(A.created_at) BETWEEN :start_date AND :end_date
            ORDER BY A.created_at DESC
            LIMIT 1000
        ");
        $master3->bindValue(':start_date', $start_date);
        $master3->bindValue(':end_date', $end_date);
        $master3->execute();

        while($hasil3 = $master3->fetch(PDO::FETCH_ASSOC)){
            $expired_akhir = !empty($hasil3['tgl_expired']) ? $hasil3['tgl_expired'] : '-';

            $response['data'][] = [
                'jenis_transaksi' => 'Stok Akhir',
                'tgl_pemasukan' => date('Y-m-d', strtotime($hasil3['created_at'])),
                'kode_obat_jadi' => $hasil3['kode_produk_jadi'],
                                        'kategori_obat' => $hasil3['kategori_obat'] ?? '-',

                'nama_produk' => $hasil3['nama_pro'],
                'jumlah' => $hasil3['jumlah'],
                'batch' => $hasil3['no_bcode'],
                'tgl_expired' => $expired_akhir,
                'no_faktur' => '-',
                'sumber' => '-',
                'nama_cabang' => $nama_cabang
            ];
        }

        $response['metadata']['total_records'] = count($response['data']);

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
            
            // Prioritas ketiga: nama produk
            return $a['nama_produk'] <=> $b['nama_produk'];
        });

        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode($response);

    } catch (Exception $e) {
        error_log("getLocalStockopname.php error: " . $e->getMessage());
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    } finally {
        if (isset($conn)) {
            $conn = $base->close();
        }
    }
?>