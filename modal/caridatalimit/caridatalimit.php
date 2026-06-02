<?php
error_reporting(E_ALL);

// FIX: jangan tampilkan error ke output (kalau tampil, JSON jadi rusak)
ini_set('display_errors', 0);

// PENTING: Set header JSON di awal sebelum output apapun
header('Content-Type: application/json; charset=utf-8');

require_once('../../config/connection/connection.php');
require_once('../../config/connection/security.php');
require_once('../../config/function/data.php');

$secu	= new Security;
$base	= new DB;
$data	= new Data;

$admin	= $secu->injection($_COOKIE['adminkuy'] ?? '');
$kunci	= $secu->injection($_COOKIE['kuncikuy'] ?? '');
$catat	= date('Y-m-d H:i:s');
$act	= $secu->injection($_GET['act'] ?? '');

function logError(string $message, array $context = []) {
    $logDir = __DIR__ . '/../../logs';
    if(!is_dir($logDir)){
        @mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . '/limit_errors.log';
    $entry = [
        'time'    => date('Y-m-d H:i:s'),
        'file'    => __FILE__,
        'message' => $message,
        'context' => $context,
        'ip'      => $_SERVER['REMOTE_ADDR'] ?? '',
        'post'    => $_POST,
        'get'     => $_GET
    ];
    @file_put_contents($logFile, json_encode($entry, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).PHP_EOL, FILE_APPEND | LOCK_EX);
}

logError('Request received', ['act' => $act, 'admin' => $admin]);

$secu->validadmin($admin, $kunci);
if($secu->validadmin($admin, $kunci)==false){
    logError('Invalid admin session', ['admin' => $admin]);
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Session login anda habis. Silakan login ulang.']);
    exit;
}

$conn = $base->open();

function updateStatusLimit(PDO $conn, $id_tfk, $status){
    $paramType = ($status === null) ? PDO::PARAM_NULL : PDO::PARAM_STR;

    $up1 = $conn->prepare("UPDATE transaksi_faktur SET status_limit=:st WHERE id_tfk=:id");
    $up1->bindValue(':st', $status, $paramType);
    $up1->bindValue(':id', $id_tfk, PDO::PARAM_STR);
    $up1->execute();
    if($up1->rowCount() > 0) return true;

    $up2 = $conn->prepare("UPDATE transaksi_faktur_pim SET status_limit=:st WHERE id_tfk=:id");
    $up2->bindValue(':st', $status, $paramType);
    $up2->bindValue(':id', $id_tfk, PDO::PARAM_STR);
    $up2->execute();

    return ($up2->rowCount() > 0);
}

function updateOutletLimit(PDO $conn, $id_tfk){
    try {
        // Coba ambil dari transaksi_faktur dulu
        $q = $conn->prepare("SELECT total_tfk, id_out FROM transaksi_faktur WHERE id_tfk = :id LIMIT 1");
        $q->bindValue(':id', $id_tfk, PDO::PARAM_STR);
        $q->execute();
        $row = $q->fetch(PDO::FETCH_ASSOC);

        if(!$row){
            // jika tidak ditemukan, cek di transaksi_faktur_pim
            $q = $conn->prepare("SELECT total_tfk, id_out FROM transaksi_faktur_pim WHERE id_tfk = :id LIMIT 1");
            $q->bindValue(':id', $id_tfk, PDO::PARAM_STR);
            $q->execute();
            $row = $q->fetch(PDO::FETCH_ASSOC);
        }

        if($row && isset($row['id_out'])){
            $total = (float) $row['total_tfk'];
            $id_out = $row['id_out'];

            // update kolom `limit` di tabel outlet (gunakan backticks karena nama kolom reserved)
            $u = $conn->prepare("UPDATE outlet SET `limit` = :limit_val WHERE id_out = :id_out");
            $u->bindValue(':limit_val', $total, PDO::PARAM_STR);
            $u->bindValue(':id_out', $id_out, PDO::PARAM_STR);
            $u->execute();

            return ($u->rowCount() > 0);
        }

        return false;
    } catch(Exception $e){
        // jangan interrupt flow — cukup log
        @file_put_contents(__DIR__ . '/../../logs/limit_errors.log', json_encode(['time'=>date('Y-m-d H:i:s'),'file'=>__FILE__,'message'=>'updateOutletLimit error','exception'=>$e->getMessage(),'id_tfk'=>$id_tfk]).PHP_EOL, FILE_APPEND | LOCK_EX);
        return false;
    }
}

switch($act){

    case "input":
        try {
            $conn->beginTransaction();
            
            // Generate kode limit dengan retry jika duplicate
            $maxRetries = 5;
            $retry = 0;
            $kode = '';
            $isDuplicate = true;
            
            while ($isDuplicate && $retry < $maxRetries) {
                $kode = $data->basecode('LMT', 5, 'id_limit', '`limit`');
                
                // Check apakah kode sudah ada
                $checkKode = $conn->prepare("SELECT COUNT(*) as total FROM `limit` WHERE kode_limit = :kode");
                $checkKode->bindValue(':kode', $kode, PDO::PARAM_STR);
                $checkKode->execute();
                $resultCheck = $checkKode->fetch(PDO::FETCH_ASSOC);
                
                if ($resultCheck['total'] == 0) {
                    $isDuplicate = false;
                } else {
                    $retry++;
                    logError('Duplicate kode detected, retrying', ['kode' => $kode, 'retry' => $retry]);
                    // Tambahkan random suffix untuk ensure uniqueness
                    $kode = $kode . rand(10, 99);
                }
            }
            
            if ($isDuplicate) {
                throw new Exception('Failed to generate unique code after ' . $maxRetries . ' attempts');
            }
            
            $tgl  = $secu->injection($_POST['tanggal'] ?? '');
            $noFakturArr = $_POST['no_faktur'] ?? [];
            $ketArr = $_POST['ket'] ?? [];
            $jumlah = is_array($noFakturArr) ? count($noFakturArr) : 0;
            
            logError('Input validation', [
                'kode' => $kode,
                'tgl' => $tgl,
                'jumlah' => $jumlah
            ]);
            
            if(empty($tgl)){
                $conn->rollBack();
                logError('Tanggal kosong');
                echo "error: tanggal_required";
                exit;
            }
            
            if($jumlah < 1){
                $conn->rollBack();
                logError('Tidak ada faktur dipilih');
                echo "error: no_faktur_required";
                exit;
            }

            $firstIdTfk = '';
            $firstSumber = 'Cendo';
            
            for($i=0; $i<$jumlah; $i++){
                $raw = $secu->injection($noFakturArr[$i] ?? '');
                if(empty($raw)) continue;

                $parts = explode('|', $raw);
                if(count($parts) >= 2){
                    $sumber = trim($parts[0]);
                    $id_tfk = trim($parts[1]);
                } else {
                    $sumber = 'Cendo';
                    $id_tfk = trim($parts[0]);
                }

                if(!empty($id_tfk)){
                    $firstIdTfk = $id_tfk;
                    if(strtoupper($sumber) === 'PIM') $firstSumber = 'PIM';
                    break;
                }
            }
            
            if(empty($firstIdTfk)){
                $conn->rollBack();
                logError('No valid id_tfk found');
                echo "error: invalid_faktur_format";
                exit;
            }

            // Insert header
            $saveHeader = $conn->prepare("
                INSERT INTO `limit`
                    (kode_limit, id_tfk, sumber, status_limit, tgl_limit, created_at, created_by)
                VALUES
                    (:kode, :id_tfk, :sumber, :status_limit, :tgl, :catat, :admin)
            ");
            $saveHeader->bindValue(":kode", $kode, PDO::PARAM_STR);
            $saveHeader->bindValue(":id_tfk", $firstIdTfk, PDO::PARAM_STR);
            $saveHeader->bindValue(":sumber", $firstSumber, PDO::PARAM_STR);
            $saveHeader->bindValue(":status_limit", 'Approved', PDO::PARAM_STR);
            $saveHeader->bindValue(":tgl", $tgl, PDO::PARAM_STR);
            $saveHeader->bindValue(":catat", $catat, PDO::PARAM_STR);
            $saveHeader->bindValue(":admin", $admin, PDO::PARAM_STR);

            if(!$saveHeader->execute()){
                $err = $saveHeader->errorInfo();
                logError('Header insert failed', ['error' => $err]);
                throw new Exception('Header insert failed: ' . implode(' | ', $err));
            }

            $id_limit = (int)$conn->lastInsertId();
            logError('Header inserted', ['id_limit' => $id_limit, 'kode' => $kode]);

            // Insert details
            $saveDetail = $conn->prepare("
                INSERT INTO `limit_detail`
                    (id_limit, no_faktur, ket, status, created_at, created_by)
                VALUES
                    (:id_limit, :no_faktur, :ket, :status, :catat, :admin)
            ");

            $inserted = 0;

            for($no=0; $no<$jumlah; $no++){
                $no_faktur_full = $secu->injection($noFakturArr[$no] ?? '');
                $ket = $secu->injection($ketArr[$no] ?? '');

                if(empty($no_faktur_full)) continue;

                $parts = explode('|', $no_faktur_full);
                if(count($parts) >= 2){
                    $id_tfk = trim($parts[1]);
                } else {
                    $id_tfk = trim($parts[0]);
                }
                
                if(empty($id_tfk)) continue;

                $status_detail = 'limit';
                // set detail status to Approved
                $status_detail = 'Approved';

                $saveDetail->bindValue(":id_limit", $id_limit, PDO::PARAM_INT);
                $saveDetail->bindValue(":no_faktur", $id_tfk, PDO::PARAM_STR);
                $saveDetail->bindValue(":ket", $ket, PDO::PARAM_STR);
                $saveDetail->bindValue(":status", $status_detail, PDO::PARAM_STR);
                $saveDetail->bindValue(":catat", $catat, PDO::PARAM_STR);
                $saveDetail->bindValue(":admin", $admin, PDO::PARAM_STR);

                if(!$saveDetail->execute()){
                    $err = $saveDetail->errorInfo();
                    logError('Detail insert failed', ['error' => $err, 'no' => $no, 'id_tfk' => $id_tfk]);
                    throw new Exception('Detail insert failed: ' . implode(' | ', $err));
                }

                $inserted++;
                updateStatusLimit($conn, $id_tfk, 'limit');
            }

            logError('Details inserted', ['count' => $inserted]);

            if($inserted < 1){
                throw new Exception('No detail rows inserted');
            }

            // Insert riwayat
            $riwayat = $conn->prepare("
                INSERT INTO riwayat (kode_riwayat, menu_riwayat, status_riwayat, ket_riwayat, created_at, created_by)
                VALUES(:kode, 'Finance Limit', 'Create', 'Input Limit', :catat, :admin)
            ");
            $riwayat->bindValue(":kode", $kode, PDO::PARAM_STR);
            $riwayat->bindValue(":catat", $catat, PDO::PARAM_STR);
            $riwayat->bindValue(":admin", $admin, PDO::PARAM_STR);
            $riwayat->execute();

            $conn->commit();
            logError('Transaction committed successfully', ['kode' => $kode]);
            echo json_encode(['status' => 'success', 'message' => 'Data limit berhasil disimpan']);
            
        } catch(Exception $e){
            if($conn->inTransaction()) $conn->rollBack();
            logError('Exception during input', [
                'admin'=>$admin, 
                'exception'=>$e->getMessage(),
                'trace'=>$e->getTraceAsString()
            ]);
            echo "error: " . $e->getMessage();
        }
    break;

    case "delete":
        $id_limit = (int)$secu->injection($_POST['keycode'] ?? '');
        if($id_limit < 1){ echo "error"; break; }

        try{
            $conn->beginTransaction();

            $q = $conn->prepare("SELECT no_faktur FROM `limit_detail` WHERE id_limit=:id");
            $q->bindValue(":id", $id_limit, PDO::PARAM_INT);
            $q->execute();
            $rows = $q->fetchAll(PDO::FETCH_ASSOC);

            foreach($rows as $r){
                updateStatusLimit($conn, $r['no_faktur'], null);
            }

            $delHeader = $conn->prepare("DELETE FROM `limit` WHERE id_limit=:id");
            $delHeader->bindValue(":id", $id_limit, PDO::PARAM_INT);
            $delHeader->execute();

            $conn->commit();

            // FIX: Gunakan struktur kolom yang benar
            $ri = $conn->prepare("
                INSERT INTO riwayat (kode_riwayat, menu_riwayat, status_riwayat, ket_riwayat, created_at, created_by)
                VALUES(:kode, 'Finance Limit', 'Delete', 'Delete Limit', :catat, :admin)
            ");
            $ri->bindValue(":kode", (string)$id_limit, PDO::PARAM_STR);
            $ri->bindValue(":catat", $catat, PDO::PARAM_STR);
            $ri->bindValue(":admin", $admin, PDO::PARAM_STR);
            $ri->execute();

            echo json_encode(['status' => 'success', 'message' => 'Data limit berhasil dihapus']);
        } catch(Exception $e){
            if($conn->inTransaction()) $conn->rollBack();
            echo "error";
        }
    break;

    case "approve":
        $id_limit  = (int)$secu->injection($_POST['id_limit'] ?? '');
        $single_no = $secu->injection($_POST['no_faktur'] ?? '');
        $approve_type = $secu->injection($_POST['approve_type'] ?? '');
        $updated   = false;
        $kode_ref  = '';

        try{
            $conn->beginTransaction();

            if($id_limit > 0){
                // existing batch approve logic (unchanged)
                // ...existing code...
            } elseif(!empty($single_no)){
                // APPROVE SINGLE FAKTUR - alternatif: cek dulu header `limit` yg ada, tambahkan detail jika belum ada
                $raw = $single_no;
                if(strpos($raw, '|') !== false){
                    $parts = explode('|', $raw, 2);
                    $maybeVal = trim($parts[1]);
                } else {
                    $maybeVal = trim($raw);
                }

                // cari faktur di transaksi (prioritas transaksi_faktur)
                $single_id = null;
                $sumber = 'Cendo';
                $qTF = $conn->prepare("SELECT id_tfk, kode_tfk FROM transaksi_faktur WHERE id_tfk=:nf OR kode_tfk=:nf LIMIT 1");
                $qTF->bindValue(":nf", $maybeVal, PDO::PARAM_STR);
                $qTF->execute();
                $rowTF = $qTF->fetch(PDO::FETCH_ASSOC);
                if($rowTF){
                    $single_id = $rowTF['id_tfk'];
                    $kode_ref = $rowTF['kode_tfk'] ?: $maybeVal;
                    $sumber = 'Cendo';
                } else {
                    $qTFP = $conn->prepare("SELECT id_tfk, kode_tfk FROM transaksi_faktur_pim WHERE id_tfk=:nf OR kode_tfk=:nf LIMIT 1");
                    $qTFP->bindValue(":nf", $maybeVal, PDO::PARAM_STR);
                    $qTFP->execute();
                    $rowTFP = $qTFP->fetch(PDO::FETCH_ASSOC);
                    if($rowTFP){
                        $single_id = $rowTFP['id_tfk'];
                        $kode_ref = $rowTFP['kode_tfk'] ?: $maybeVal;
                        $sumber = 'PIM';
                    }
                }

                if(!$single_id){
                    throw new Exception('Faktur tidak ditemukan di transaksi_faktur / transaksi_faktur_pim');
                }

                // Jika approve_type == 'merah' gunakan prosedur approve khusus (tidak sama dengan 'limit')
                if(strtolower($approve_type) === 'merah'){
                    // Simpan ke tabel limit_merah + limit_merah_detail, lalu ubah status transaksi jadi 'approve'
                    // Cek apakah sudah ada entry merah untuk no_faktur ini
                    $qExist = $conn->prepare("
                        SELECT lm.id_merah, lm.kode_merah
                        FROM `limit_merah` lm
                        JOIN `limit_merah_detail` lmd ON lmd.id_merah = lm.id_merah
                        WHERE lmd.no_faktur = :no_faktur
                        LIMIT 1
                    ");
                    $qExist->bindValue(':no_faktur', $single_id, PDO::PARAM_STR);
                    $qExist->execute();
                    $exists = $qExist->fetch(PDO::FETCH_ASSOC);

                    if($exists){
                        $id_merah = (int)$exists['id_merah'];
                        $kode_merah = $exists['kode_merah'];
                    } else {
                        // buat header limit_merah dengan kode unik MRH...
                        $maxRetries = 5; $retry = 0;
                        do {
                            $kode_merah = $data->basecode('MRH', 5, 'id_merah', '`limit_merah`');
                            if($retry) $kode_merah .= rand(10,99);
                            $chk = $conn->prepare("SELECT COUNT(*) FROM `limit_merah` WHERE kode_merah=:kode");
                            $chk->bindValue(':kode', $kode_merah, PDO::PARAM_STR);
                            $chk->execute();
                            $existsCnt = (int)$chk->fetchColumn();
                            $retry++;
                        } while($existsCnt > 0 && $retry < $maxRetries);

                        $insH = $conn->prepare("
                            INSERT INTO `limit_merah` (kode_merah, id_tfk, sumber, status_merah, tgl_merah, created_at, created_by)
                            VALUES(:kode, :id_tfk, :sumber, 'Approved', :tgl_merah, :catat, :admin)
                        ");
                        $insH->bindValue(':kode', $kode_merah, PDO::PARAM_STR);
                        $insH->bindValue(':id_tfk', $single_id, PDO::PARAM_STR);
                        $insH->bindValue(':sumber', $sumber, PDO::PARAM_STR);
                        $insH->bindValue(':tgl_merah', date('Y-m-d'), PDO::PARAM_STR);
                        $insH->bindValue(':catat', $catat, PDO::PARAM_STR);
                        $insH->bindValue(':admin', $admin, PDO::PARAM_STR);
                        $insH->execute();
                        $id_merah = (int)$conn->lastInsertId();
                    }

                    // pastikan tidak duplikasi detail
                    $qDetailCheck = $conn->prepare("SELECT COUNT(*) FROM `limit_merah_detail` WHERE no_faktur=:no_faktur");
                    $qDetailCheck->bindValue(':no_faktur', $single_id, PDO::PARAM_STR);
                    $qDetailCheck->execute();
                    $detailExists = (int)$qDetailCheck->fetchColumn();

                    if($detailExists === 0){
                        $insD = $conn->prepare("
                            INSERT INTO `limit_merah_detail` (id_merah, no_faktur, ket, status, created_at, created_by)
                            VALUES(:id_merah, :no_faktur, :ket, 'Approved', :catat, :admin)
                        ");
                        $insD->bindValue(':id_merah', $id_merah, PDO::PARAM_INT);
                        $insD->bindValue(':no_faktur', $single_id, PDO::PARAM_STR);
                        $insD->bindValue(':ket', 'Approve status merah', PDO::PARAM_STR);
                        $insD->bindValue(':catat', $catat, PDO::PARAM_STR);
                        $insD->bindValue(':admin', $admin, PDO::PARAM_STR);
                        $insD->execute();
                    }

                    // update status transaksi menjadi approved
                    updateStatusLimit($conn, $single_id, 'approve');

                    // catat riwayat khusus MERAH
                    $ri = $conn->prepare("
                        INSERT INTO riwayat (kode_riwayat, menu_riwayat, status_riwayat, ket_riwayat, created_at, created_by)
                        VALUES(:kode, 'Finance Limit (Merah)', 'Approve', 'Approve Merah', :catat, :admin)
                    ");
                    $ri->bindValue(":kode", $kode_merah, PDO::PARAM_STR);
                    $ri->bindValue(":catat", $catat, PDO::PARAM_STR);
                    $ri->bindValue(":admin", $admin, PDO::PARAM_STR);
                    $ri->execute();

                    $kode_ref = $kode_merah;
                    $updated = true;
                    logError('Approve single MERAH processed', ['search'=>$single_no,'id_tfk'=>$single_id,'kode_merah'=>$kode_merah]);
                 } else {
                    // existing single approve flow (unchanged) for normal 'limit' approves
                    // ...existing code continues here...
                    // Update transaksi status dulu
                    updateStatusLimit($conn, $single_id, 'approve');

                    // update outlet.limit dengan total_tfk untuk faktur ini
                    updateOutletLimit($conn, $single_id);
                    // Cek apakah sudah ada header limit untuk id_tfk ini atau kode
                    $qExists = $conn->prepare("SELECT id_limit, kode_limit, status_limit FROM `limit` WHERE id_tfk=:id_tfk OR kode_limit=:kode LIMIT 1");
                    $qExists->bindValue(':id_tfk', $single_id, PDO::PARAM_STR);
                    $qExists->bindValue(':kode', $kode_ref, PDO::PARAM_STR);
                    $qExists->execute();
                    $hdr = $qExists->fetch(PDO::FETCH_ASSOC);

                    if($hdr){
                        $id_limit_use = (int)$hdr['id_limit'];
                        $kode_ref = $hdr['kode_limit'] ?: $kode_ref;
                        // set header jadi approve jika belum
                        if(strtolower(trim((string)$hdr['status_limit'])) !== 'approve'){
                            $uLimit = $conn->prepare("UPDATE `limit` SET status_limit='Approved', updated_at=:catat, updated_by=:admin WHERE id_limit=:id_limit");
                            $uLimit->bindValue(":catat", $catat, PDO::PARAM_STR);
                            $uLimit->bindValue(":admin", $admin, PDO::PARAM_STR);
                            $uLimit->bindValue(":id_limit", $id_limit_use, PDO::PARAM_INT);
                            $uLimit->execute();
                        }
                    } else {
                        // buat header baru
                        $maxRetries = 5; $retry = 0;
                        do {
                            $newKode = $data->basecode('LMT', 5, 'id_limit', '`limit`');
                            if($retry) $newKode .= rand(10,99);
                            $chk = $conn->prepare("SELECT COUNT(*) FROM `limit` WHERE kode_limit=:kode");
                            $chk->bindValue(':kode', $newKode, PDO::PARAM_STR);
                            $chk->execute();
                            $exists = (int)$chk->fetchColumn();
                            $retry++;
                        } while($exists > 0 && $retry < $maxRetries);

                        $insH = $conn->prepare("INSERT INTO `limit` (kode_limit, id_tfk, sumber, status_limit, tgl_limit, created_at, created_by) VALUES(:kode, :id_tfk, :sumber, 'Approved', :tgl_limit, :catat, :admin)");
                        $insH->bindValue(':kode', $newKode, PDO::PARAM_STR);
                        $insH->bindValue(':id_tfk', $single_id, PDO::PARAM_STR);
                        $insH->bindValue(':sumber', $sumber, PDO::PARAM_STR);
                        $insH->bindValue(':tgl_limit', date('Y-m-d'), PDO::PARAM_STR);
                        $insH->bindValue(':catat', $catat, PDO::PARAM_STR);
                        $insH->bindValue(':admin', $admin, PDO::PARAM_STR);
                        $insH->execute();
                        $id_limit_use = (int)$conn->lastInsertId();
                        $kode_ref = $newKode;
                    }

                    // Pastikan tidak duplikasi detail untuk faktur ini
                    $qDetailCheck = $conn->prepare("SELECT COUNT(*) FROM `limit_detail` WHERE id_limit=:id_limit AND no_faktur=:no_faktur");
                    $qDetailCheck->bindValue(':id_limit', $id_limit_use, PDO::PARAM_INT);
                    $qDetailCheck->bindValue(':no_faktur', $single_id, PDO::PARAM_STR);
                    $qDetailCheck->execute();
                    $detailExists = (int)$qDetailCheck->fetchColumn();

                    if($detailExists === 0){
                        $insD = $conn->prepare("INSERT INTO `limit_detail` (id_limit, no_faktur, ket, status, created_at, created_by) VALUES(:id_limit, :no_faktur, :ket, 'Approved', :catat, :admin)");
                        $insD->bindValue(':id_limit', $id_limit_use, PDO::PARAM_INT);
                        $insD->bindValue(':no_faktur', $single_id, PDO::PARAM_STR);
                        $insD->bindValue(':ket', 'Approve status limit', PDO::PARAM_STR);
                        $insD->bindValue(':catat', $catat, PDO::PARAM_STR);
                        $insD->bindValue(':admin', $admin, PDO::PARAM_STR);
                        $insD->execute();
                    }

                    $updated = true;
                    logError('Approve single processed (alt)', ['search'=>$single_no,'id_tfk'=>$single_id,'id_limit'=>$id_limit_use,'kode'=>$kode_ref,'sumber'=>$sumber]);
                }
            }

            if(!$updated){
                $conn->rollBack();
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Tidak ada data yang bisa di-approve.']);
                break;
            }

            // Insert riwayat
            $ri = $conn->prepare("
                INSERT INTO riwayat (kode_riwayat, menu_riwayat, status_riwayat, ket_riwayat, created_at, created_by)
                VALUES(:kode, 'Finance Limit', 'Approve', 'Approve Limit', :catat, :admin)
            ");
            $ri->bindValue(":kode", $kode_ref, PDO::PARAM_STR);
            $ri->bindValue(":catat", $catat, PDO::PARAM_STR);
            $ri->bindValue(":admin", $admin, PDO::PARAM_STR);
            $ri->execute();

            $conn->commit();
            logError('Transaction approve committed', ['kode_ref' => $kode_ref]);

            echo json_encode(['status' => 'success', 'message' => 'Limit berhasil di-approve']);
            
        } catch(Exception $e){
            if($conn->inTransaction()) $conn->rollBack();
            logError('Exception during approve', ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Gagal approve: ' . $e->getMessage()]);
        }
    break;
}

$conn = $base->close();
?>