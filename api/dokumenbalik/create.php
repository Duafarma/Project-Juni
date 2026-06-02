<?php
/**
 * API Dokumen Balik
 * Lokasi: api/dokumenbalik/create.php
 */

header('Content-Type: application/json; charset=utf-8');

require_once('../../config/connection/connection.php');
require_once('../../config/connection/security.php');
require_once('../../config/function/data.php');

$secu = new Security;
$base = new DB;
$data = new Data;

$admin = $secu->injection(@$_COOKIE['adminkuy']);
$kunci = $secu->injection(@$_COOKIE['kuncikuy']);
$catat = date('Y-m-d H:i:s');
$log_file = "../../logs/api_dokumenbalik_" . date('Y-m-d') . ".log";

function write_api_log($file, $data) {
    $entry = "[" . date('Y-m-d H:i:s') . "] " . json_encode($data) . "\n";
    file_put_contents($file, $entry, FILE_APPEND);
}

try {
    $conn = $base->open();

    // 1. Validasi Autentikasi
    $is_remote = isset($_POST['is_remote']) && $_POST['is_remote'] == 'true';
    $ext_id_db = $_POST['ext_id_db'] ?? null; // Ambil ext_id_db jika dari remote

    // Gunakan admin dari payload jika remote, atau dari session jika lokal
    if ($is_remote && !empty($_POST['admin'])) {
        $admin = $secu->injection($_POST['admin']);
    }

    if (!$is_remote && !$secu->validadmin($admin, $kunci)) {
        throw new Exception("Unauthorized: Session expired", 401);
    }

    // Ambil nama cabang lokal
    $stmt_apl = $conn->query("SELECT nama_apl FROM aplikasi WHERE active_apl = 1 AND self_apl = 1 LIMIT 1");
    $apl = $stmt_apl->fetch(PDO::FETCH_ASSOC);
    $nama_cabang = $apl ? $apl['nama_apl'] : 'Pusat';

    // 2. Parsing Input
    $input_raw = file_get_contents('php://input');
    $input_json = json_decode($input_raw, true);
    $tanggal = isset($input_json['tanggal']) ? $input_json['tanggal'] : ($_POST['tanggal'] ?? '');
    $items = isset($input_json['items']) ? $input_json['items'] : ($_POST['items'] ?? []);

    if (empty($items) && isset($_POST['no_faktur'])) {
        foreach ($_POST['no_faktur'] as $idx => $val) {
            $items[] = ['no_faktur' => $val, 'ket' => $_POST['ket'][$idx] ?? ''];
        }
    }

    if (empty($tanggal) || empty($items)) throw new Exception("Data tidak lengkap", 400);

    $conn->beginTransaction();

    // Generate ID Lokal untuk menghindari duplikat antar cabang
    $kode = $data->basecode('TANDA', 5, 'id_db', 'dokumen_balik');
    
    // 3. Simpan Master menggunakan format INSERT yang eksplisit kolomnya
    $stmt = $conn->prepare("INSERT INTO dokumen_balik (id_db, ext_id_db, tanggal, created_at, created_by, updated_at, updated_by) VALUES (:id, :ext_id, :tgl, :catat, :admin, :catat, :admin)");
    $stmt->execute([
        ':id' => $kode, 
        ':ext_id' => $ext_id_db, // Masukkan ext_id_db (berisi ID pusat jika ini di cabang)
        ':tgl' => $tanggal, 
        ':catat' => $catat, 
        ':admin' => $admin
    ]);

    foreach ($items as $item) {
        // Format ID: sumber|id_tfk|kode_tfk|nama_out|tgl_tfk
        $parts = explode('|', $item['no_faktur']);
        if (count($parts) < 5) continue;
        
        $sumber    = $parts[0];
        $id_tfk    = $parts[1]; // Numeric ID untuk relasi lokal jika ada
        $kode_tfk  = $parts[2]; // Kode Faktur (String)
        $nama_out  = $parts[3]; // Nama Outlet
        $tgl_tfk   = $parts[4]; // Tanggal Faktur

        // Detail (Simpan data denormalisasi ke kolom baru)
        $stmt_det = $conn->prepare("INSERT INTO dokumen_balik_detail (id_db, no_faktur, kode_tfk_ext, nama_out_ext, tgl_faktur_ext, ket, status_dokumen, created_at, created_by, updated_at, updated_by) VALUES (:id, :no_faktur, :kode_ext, :nama_ext, :tgl_ext, :ket, 'sudah balik', :catat, :admin, :catat, :admin)");
        $stmt_det->execute([
            ':id'        => $kode, 
            ':no_faktur'  => $id_tfk, 
            ':kode_ext'   => $kode_tfk,
            ':nama_ext'   => $nama_out,
            ':tgl_ext'    => $tgl_tfk,
            ':ket'        => $item['ket'], 
            ':catat'      => $catat, 
            ':admin'      => $admin
        ]);

        // Update Status (Gunakan kode_tfk agar sinkron antar server)
        $tbl = ($sumber === 'PIM') ? 'transaksi_faktur_pim' : 'transaksi_faktur';
        $conn->prepare("UPDATE $tbl SET status_dokumen = 'sudah balik' WHERE kode_tfk = ?")->execute([$kode_tfk]);
    }

    // 4. Sinkronisasi (Hanya jika input lokal dari browser)
    $synced_branches = [];
    if (!$is_remote) {
        $stmt_rem = $conn->query("SELECT nama_apl, base_url_apl FROM aplikasi WHERE active_apl = 1 AND self_apl = 0");
        while ($rem = $stmt_rem->fetch()) {
            if (empty($rem['base_url_apl'])) continue;
            
            $ch = curl_init(rtrim($rem['base_url_apl'], '/') . '/api/dokumenbalik/create.php');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'is_remote' => 'true', 
                'ext_id_db' => $kode, // Kirim ID lokal ini sebagai ext_id_db untuk cabang
                'admin'     => $admin,
                'tanggal'   => $tanggal, 
                'items'     => $items
            ]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_exec($ch);
            
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($http_code >= 200 && $http_code < 300) {
                $synced_branches[] = $rem['nama_apl'];
            }
            curl_close($ch);
        }
    }

    $conn->commit();
    
    // Pesan dinamis
    $pesan_sukses = "Data Berhasil Disimpan di Cabang $nama_cabang";
    if (!empty($synced_branches)) {
        $pesan_sukses .= " dan tersinkronisasi ke: " . implode(", ", $synced_branches);
    }
    
    echo json_encode(["ok" => true, "message" => $pesan_sukses]);

} catch (Exception $e) {
    if (isset($conn)) $conn->rollBack();
    
    // Custom error code processing
    $code = $e->getCode() ?: 500;
    if ($code < 100 || $code > 599) $code = 500;
    http_response_code($code);
    
    $err_msg = $e->getMessage();
    echo json_encode(["ok" => false, "message" => $err_msg]);
    write_api_log($log_file, ["error" => $err_msg, "input" => $_POST, "user" => $admin]);
} finally {
    if (isset($base)) $base->close();
}
