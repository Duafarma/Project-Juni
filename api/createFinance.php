<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false,'error'=>'Method Not Allowed']);
    exit;
}
require_once('../config/connection/connection.php');
require_once('../config/connection/security.php');
require_once('../config/function/data.php');

$secu = new Security;
$base = new DB;
$data = new Data;
header('Content-Type: application/json; charset=utf-8');

$action = isset($_GET['action']) ? trim($_GET['action']) : (isset($_POST['action']) ? trim($_POST['action']) : 'create');

function verifyCaller(PDO $conn, string $id_apl, string $catat, string $encrypt): bool {
    $stmt = $conn->prepare("SELECT key_apl FROM aplikasi WHERE id_apl = :id_apl AND active_apl = 1 LIMIT 1");
    $stmt->bindParam(':id_apl', $id_apl, PDO::PARAM_STR);
    $stmt->execute();
    $caller = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$caller) return false;
    return md5($catat . "#" . $caller['key_apl']) === $encrypt;
}

function financeDetailNoKwitansiColumn(PDO $conn): string {
    static $cached = null;
    if ($cached !== null) return $cached;

    $cols = [];
    $q = $conn->query("SHOW COLUMNS FROM finance_detail");
    while ($r = $q->fetch(PDO::FETCH_ASSOC)) {
        $cols[] = strtolower((string)$r['Field']);
    }

    if (in_array('nokwi', $cols, true)) { $cached = 'nokwi'; return $cached; }
    if (in_array('no_kwitansi', $cols, true)) { $cached = 'no_kwitansi'; return $cached; }

    $cached = 'nokwi';
    return $cached;
}

try {
    $conn = $base->open();

    if ($action === 'updateStatus') {
        // update status endpoint (digunakan remote oleh master)
        $id_tfk  = isset($_POST['id_tfk']) ? trim($_POST['id_tfk']) : '';
        $status  = isset($_POST['status']) ? trim($_POST['status']) : '';
        $catat   = isset($_POST['catat']) ? trim($_POST['catat']) : '';
        $id_apl  = isset($_POST['id_apl']) ? trim($_POST['id_apl']) : '';
        $encrypt = isset($_POST['encrypt']) ? trim($_POST['encrypt']) : '';

        if ($id_tfk === '' || $status === '' || $id_apl === '' || $encrypt === '' || $catat === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Missing parameter']);
            exit;
        }

        if (!verifyCaller($conn, $id_apl, $catat, $encrypt)) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
            exit;
        }

        $upd = $conn->prepare("UPDATE transaksi_faktur SET status_dokumentasi = :status WHERE id_tfk = :id_tfk");
        $upd->bindParam(':status', $status, PDO::PARAM_STR);
        $upd->bindParam(':id_tfk', $id_tfk, PDO::PARAM_STR);
        $upd->execute();

        if ($upd->rowCount() === 0) {
            $upd2 = $conn->prepare("UPDATE transaksi_faktur_pim SET status_dokumentasi = :status WHERE id_tfk = :id_tfk");
            $upd2->bindParam(':status', $status, PDO::PARAM_STR);
            $upd2->bindParam(':id_tfk', $id_tfk, PDO::PARAM_STR);
            $upd2->execute();

            if ($upd2->rowCount() === 0) {
                echo json_encode(['ok' => false, 'error' => 'No matching faktur found']);
                exit;
            }
        }

        echo json_encode(['ok' => true, 'message' => 'Status updated']);
        exit;
    }

    if ($action === 'delete') {
        // delete finance + revert status_dokumentasi (dipakai master saat delete)
        $nomor       = isset($_POST['nomor']) ? trim($_POST['nomor']) : '';
        $nama_outlet = isset($_POST['nama_outlet']) ? trim($_POST['nama_outlet']) : ''; // optional
        $catat       = isset($_POST['catat']) ? trim($_POST['catat']) : '';
        $id_apl      = isset($_POST['id_apl']) ? trim($_POST['id_apl']) : '';
        $encrypt     = isset($_POST['encrypt']) ? trim($_POST['encrypt']) : '';

        if ($nomor === '' || $id_apl === '' || $encrypt === '' || $catat === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Missing parameter (nomor, id_apl, encrypt, catat)']);
            exit;
        }

        if (!verifyCaller($conn, $id_apl, $catat, $encrypt)) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
            exit;
        }

        $colKwitansiDetail = financeDetailNoKwitansiColumn($conn);

        $conn->beginTransaction();

        // Cari finance berdasarkan nomor (nama_outlet kalau dikirim -> jadi filter tambahan)
        if ($nama_outlet !== '') {
            $stmtF = $conn->prepare("SELECT id_finance FROM finance WHERE nomor = :nomor AND nama_outlet = :nama_outlet ORDER BY created_at DESC LIMIT 50");
            $stmtF->bindParam(':nomor', $nomor, PDO::PARAM_STR);
            $stmtF->bindParam(':nama_outlet', $nama_outlet, PDO::PARAM_STR);
        } else {
            $stmtF = $conn->prepare("SELECT id_finance FROM finance WHERE nomor = :nomor ORDER BY created_at DESC LIMIT 50");
            $stmtF->bindParam(':nomor', $nomor, PDO::PARAM_STR);
        }
        $stmtF->execute();
        $finRows = $stmtF->fetchAll(PDO::FETCH_ASSOC);

        if (!$finRows) {
            $conn->rollBack();
            echo json_encode(['ok' => false, 'error' => 'Finance not found on remote', 'nomor' => $nomor]);
            exit;
        }

        $deleted = 0;

        foreach ($finRows as $fr) {
            $id_fin = (string)$fr['id_finance'];
            if ($id_fin === '') continue;

            // Ambil id_tfk/no kwitansi dari finance_detail
            $sqlDetail = "SELECT {$colKwitansiDetail} AS kw FROM finance_detail WHERE id_finance = :id_fin";
            $stmtD = $conn->prepare($sqlDetail);
            $stmtD->bindParam(':id_fin', $id_fin, PDO::PARAM_STR);
            $stmtD->execute();
            $detailRows = $stmtD->fetchAll(PDO::FETCH_ASSOC);

            foreach ($detailRows as $dr) {
                $id_tfk = trim((string)($dr['kw'] ?? ''));
                if ($id_tfk === '') continue;

                $u1 = $conn->prepare("UPDATE transaksi_faktur SET status_dokumentasi='belum siap' WHERE id_tfk = :id_tfk");
                $u1->bindParam(':id_tfk', $id_tfk, PDO::PARAM_STR);
                $u1->execute();

                $u2 = $conn->prepare("UPDATE transaksi_faktur_pim SET status_dokumentasi='belum siap' WHERE id_tfk = :id_tfk");
                $u2->bindParam(':id_tfk', $id_tfk, PDO::PARAM_STR);
                $u2->execute();
            }

            // Hapus detail + header
            $delD = $conn->prepare("DELETE FROM finance_detail WHERE id_finance = :id_fin");
            $delD->bindParam(':id_fin', $id_fin, PDO::PARAM_STR);
            $delD->execute();

            $delH = $conn->prepare("DELETE FROM finance WHERE id_finance = :id_fin");
            $delH->bindParam(':id_fin', $id_fin, PDO::PARAM_STR);
            $delH->execute();

            $deleted += (int)$delH->rowCount();
        }

        $conn->commit();
        echo json_encode(['ok' => true, 'message' => 'Remote finance deleted by nomor + status reverted', 'deleted_headers' => $deleted]);
        exit;
    }

    // else => create finance (seperti sebelumnya)
    $nomor = isset($_POST['nomor']) ? trim($_POST['nomor']) : '';
    $nama_outlet = isset($_POST['nama_outlet']) ? trim($_POST['nama_outlet']) : '';
    $tanggal_faktur = isset($_POST['tanggal_faktur']) ? trim($_POST['tanggal_faktur']) : '';
    $details_json = isset($_POST['details']) ? $_POST['details'] : '[]';
    $id_apl = isset($_POST['id_apl']) ? trim($_POST['id_apl']) : '';
    $encrypt = isset($_POST['encrypt']) ? trim($_POST['encrypt']) : '';
    $catat = isset($_POST['catat']) ? trim($_POST['catat']) : date('Y-m-d H:i:s');

    // NEW (opsional): simpan referensi id_finance master ke kolom ke-5 (yang sebelumnya selalu '')
    $master_id_finance = isset($_POST['master_id_finance']) ? trim($_POST['master_id_finance']) : '';

    if ($nomor==='' || $id_apl==='' || $encrypt==='' || $details_json==='') {
        http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Missing parameter']); exit;
    }

    if (!verifyCaller($conn, $id_apl, $catat, $encrypt)) {
        http_response_code(401); echo json_encode(['ok'=>false,'error'=>'Unauthorized']); exit;
    }

    // insert header
    $id = $master_id_finance; // <= sebelumnya ''
    $kode = $data->basecode('FNCKWTTND', 10, 'id_finance', 'finance');
    $save = $conn->prepare("INSERT INTO finance VALUES(:kode, :nomorf, :nama, :tgl, :id, :catat, :admin, :catat, :admin)");
    $admin = 'system_api';
    $save->bindParam(":id", $id, PDO::PARAM_STR);
    $save->bindParam(":kode", $kode, PDO::PARAM_STR);
    $save->bindParam(":nomorf", $nomor, PDO::PARAM_STR);
    $save->bindParam(":nama", $nama_outlet, PDO::PARAM_STR);
    $save->bindParam(":tgl", $tanggal_faktur, PDO::PARAM_STR);
    $save->bindParam(":catat", $catat, PDO::PARAM_STR);
    $save->bindParam(":admin", $admin, PDO::PARAM_STR);
    $save->execute();

    $details = json_decode($details_json, true);
    if (!is_array($details)) $details = [];

    foreach ($details as $d) {
        $id_tfk = isset($d['id_tfk']) ? $d['id_tfk'] : '';
        $ket = isset($d['ket']) ? $d['ket'] : '';
        $ins = $conn->prepare("INSERT INTO finance_detail VALUES(:id, :kode, :nokwi, :ket, :catat, :admin, :catat, :admin)");
        $ins->bindParam(":id", $id, PDO::PARAM_STR);
        $ins->bindParam(":kode", $kode, PDO::PARAM_STR);
        $ins->bindParam(":nokwi", $id_tfk, PDO::PARAM_STR);
        $ins->bindParam(":ket", $ket, PDO::PARAM_STR);
        $ins->bindParam(":catat", $catat, PDO::PARAM_STR);
        $ins->bindParam(":admin", $admin, PDO::PARAM_STR);
        $ins->execute();

        // update local faktur status
        $upd = $conn->prepare("UPDATE transaksi_faktur SET status_dokumentasi='sudah siap' WHERE id_tfk = :id_tfk");
        $upd->bindParam(':id_tfk', $id_tfk, PDO::PARAM_STR);
        $upd->execute();
        if ($upd->rowCount() === 0) {
            $upd2 = $conn->prepare("UPDATE transaksi_faktur_pim SET status_dokumentasi='sudah siap' WHERE id_tfk = :id_tfk");
            $upd2->bindParam(':id_tfk', $id_tfk, PDO::PARAM_STR);
            $upd2->execute();
        }
    }

    echo json_encode(['ok'=>true,'message'=>'Finance created']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
} finally {
    if (isset($conn)) $base->close();
}
?>