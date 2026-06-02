<?php
    /**
     * API: getFakturSiap.php
     * Deskripsi: Mengambil data faktur yang berstatus 'sudah siap' untuk sinkronisasi antar cabang.
     */
    error_reporting(0);
    ini_set('display_errors', 0);
    require_once('../config/connection/connection.php');
    require_once('../config/function/data.php');

    $base = new DB;
    $data = new Data;
    $conn = $base->open();

    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');

    $encrypt = isset($_GET['encrypt']) ? $_GET['encrypt'] : '';
    $id_apl  = isset($_GET['id_apl']) ? $_GET['id_apl'] : '';

    if (empty($encrypt) || empty($id_apl)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Parameter tidak lengkap"]);
        exit;
    }

    try {
        // Validasi Pengirim
        $stmtApl = $conn->prepare("SELECT key_apl FROM aplikasi WHERE id_apl = :id_apl AND active_apl = 1");
        $stmtApl->execute([':id_apl' => $id_apl]);
        $apl = $stmtApl->fetch(PDO::FETCH_ASSOC);
        
        if (!$apl) { 
            throw new Exception("Aplikasi peminta tidak terdaftar atau tidak aktif"); 
        }

        if (md5(date('Y-m-d') . "#" . $apl['key_apl']) !== $encrypt) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Unauthorized: Hash mismatch"]);
            exit;
        }
        
        // Query Gabungan Faktur Siap (Limit 120 hari terakhir untuk performa)
        $query = "SELECT kode_tfk, id_out, nama_out, tgl_tfk, total_tfk 
                  FROM (
                    SELECT A.kode_tfk, A.id_out, B.nama_out, A.tgl_tfk, A.total_tfk 
                    FROM transaksi_faktur AS A LEFT JOIN outlet AS B ON A.id_out = B.id_out WHERE A.status_dokumentasi = 'sudah siap'
                    UNION ALL
                    SELECT A.kode_tfk, A.id_out, B.nama_out, A.tgl_tfk, A.total_tfk 
                    FROM transaksi_faktur_pim AS A LEFT JOIN outlet AS B ON A.id_out = B.id_out WHERE A.status_ceklis = 'sudah'
                    UNION ALL
                    SELECT A.kode_tfk, A.id_out, B.nama_out, A.tgl_tfk, A.total_tfk 
                    FROM transaksi_faktur_c AS A LEFT JOIN outlet AS B ON A.id_out = B.id_out WHERE A.status_ceklis = 'sudah'
                    UNION ALL
                    SELECT A.kode_tfk, A.id_out, B.nama_out, A.tgl_tfk, A.total_tfk 
                    FROM transaksi_faktur_np_medan AS A LEFT JOIN outlet AS B ON A.id_out = B.id_out WHERE A.status_ceklis = 'sudah'
                  ) AS combined
                  WHERE tgl_tfk >= DATE_SUB(NOW(), INTERVAL 120 DAY)
                  ORDER BY tgl_tfk DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $hasil = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            "status" => "success",
            "count" => count($hasil),
            "result" => $hasil
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    } finally {
        $base->close();
    }
?>