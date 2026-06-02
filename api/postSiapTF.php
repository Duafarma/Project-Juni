<?php
    /**
     * API: postSiapTF.php
     * Deskripsi: Menerima id_out dari cabang lain.
     */
    error_reporting(0);
    ini_set('display_errors', 0);
    require_once('../config/connection/connection.php');
    require_once('../config/function/data.php');

    $base = new DB; $data = new Data;
    $conn = $base->open(); $catat = date('Y-m-d H:i:s');
    header('Content-Type: application/json');

    $encrypt = isset($_GET['encrypt']) ? $_GET['encrypt'] : '';
    $act = isset($_GET['act']) ? $_GET['act'] : '';
    if ($act !== "input") { exit; }

    $raw_data = json_decode($_POST['data'], true);
    if (!$raw_data) { exit; }

    $kode = $raw_data['kode']; $tgl = $raw_data['tanggal']; $tujuan = $raw_data['tujuan'];
    $self = $data->self_apl();
    if ($encrypt !== md5(md5($kode . "#" . $tgl) . "#" . $self['key_apl'])) { exit; }

    try {
        $conn->beginTransaction();
        $save = $conn->prepare("INSERT INTO jadwal_tf (id_tf, tanggal, tujuan, created_at, created_by, updated_at, updated_by) VALUES (:id, :tgl, :tujuan, :catat, 'API_SYSTEM', :catat, 'API_SYSTEM')");
        $save->execute([":id" => $kode, ":tgl" => $tgl, ":tujuan" => $tujuan, ":catat" => $catat]);

        foreach ($raw_data['details'] as $det) {
            $saveDet = $conn->prepare("INSERT INTO jadwal_tf_detail (id_tf, no_faktur, id_out, id_apl, ket, nama_out_ext, total_tfk_ext, tgl_tfk_ext, created_at, created_by, updated_at, updated_by) 
                                       VALUES (:id_tf, :no_faktur, :id_out, :id_apl, :ket, :out_ext, :total_ext, :tgl_ext, :catat, 'API_SYSTEM', :catat, 'API_SYSTEM')");
            $saveDet->execute([
                ":id_tf" => $kode, ":no_faktur" => $det['kode_tfk'], ":id_out" => $det['id_out'], ":id_apl" => $det['id_apl'], 
                ":ket" => $det['ket'], ":out_ext" => $det['nama_out'], ":total_ext" => $det['total'], ":tgl_ext" => $det['tgl'], ":catat" => $catat
            ]);
        }
        $conn->commit();
        echo json_encode(["status" => "success"]);
    } catch (Exception $e) { if ($conn->inTransaction()) { $conn->rollBack(); } }
    $base->close();
?>