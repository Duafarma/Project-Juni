<?php
require_once('../config/connection/connection.php');
require_once('../config/connection/security.php');
require_once('../config/function/data.php');

header('Access-Control-Allow-Origin: *');
header("Content-type: application/json; charset=utf-8");
ini_set('display_errors', 0);

$secu = new Security;
$base = new DB;
$data = new Data;
$conn = $base->open();

$catat     = date('Y-m-d H:i:s');
$tgl       = date('Y-m-d');
$request   = ($_SERVER['REQUEST_METHOD'] === 'POST') ? $_POST : $_GET;
$encrypt   = $secu->injection(@$request['encrypt']);
$idAplReq  = $secu->injection(@$request['id_apl']);
$act       = $secu->injection(@$request['act']);
if ($act === '') {
    $act = $secu->injection(@$_GET['act'] ?? ''); // act may be passed in URL query string
}
$adminPost = $secu->injection(@$request['admin']);

$source    = $data->self_apl();
$sourceKey = $source['key_apl'] ?? '';
$id_apl    = $source['id_apl'] ?? '';

function respondPostSOJson($payload, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode($payload);
}

function canApproveStockOpname($currentDate) {
    return (int)date('j', strtotime($currentDate)) >= 25;
}

function normalizeStockOpnameNumber($value) {
    $value = trim((string)$value);
    if ($value === '') {
        return '0';
    }

    if (preg_match('/^-?\d{1,3}(\.\d{3})+(,\d+)?$/', $value)) {
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
        return $value;
    }

    if (strpos($value, ',') !== false && strpos($value, '.') === false) {
        return str_replace(',', '.', $value);
    }

    return str_replace(',', '', $value);
}

function findLatestStockOpnameRow(PDO $conn, $idPsd, $idPro = '', $noBcode = '') {
    $idPsd = trim((string)$idPsd);
    if ($idPsd === '') {
        return array();
    }

    $candidates = array(
        array('use_id_pro' => ($idPro !== ''), 'use_no_bcode' => ($noBcode !== '')),
        array('use_id_pro' => ($idPro !== ''), 'use_no_bcode' => false),
        array('use_id_pro' => false, 'use_no_bcode' => false)
    );

    foreach ($candidates as $candidate) {
        $sql = "SELECT id, id_psd, id_pro, no_bcode, qty, bcode_so, qty_so, selisih
                FROM stock
                WHERE id_psd = :id_psd";

        if ($candidate['use_id_pro']) {
            $sql .= ' AND id_pro = :id_pro';
        }
        if ($candidate['use_no_bcode']) {
            $sql .= ' AND no_bcode = :no_bcode';
        }

        $sql .= ' ORDER BY (CASE WHEN selisih = 0 THEN 0 ELSE 1 END) DESC, id DESC LIMIT 1';

        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':id_psd', $idPsd, PDO::PARAM_STR);
        if ($candidate['use_id_pro']) {
            $stmt->bindValue(':id_pro', $idPro, PDO::PARAM_STR);
        }
        if ($candidate['use_no_bcode']) {
            $stmt->bindValue(':no_bcode', $noBcode, PDO::PARAM_STR);
        }
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row;
        }
    }

    return array();
}

function updateProdukStokdetailFromApprovedStock(PDO $conn, $idPsd, $qtySo) {
    // Hanya update sisa_psd dan awal agar mengikuti qty_so (hasil hitung fisik).
    // qty_so TIDAK diubah — itu catatan hasil fisik yang harus tetap.
    $stmt = $conn->prepare("UPDATE produk_stokdetail SET sisa_psd = :sisa_psd, awal = :awal_qty, status = 'belum so', updated_at = :updated_at, updated_by = :updated_by WHERE id_psd = :id_psd");
    $stmt->bindValue(':id_psd', $idPsd, PDO::PARAM_STR);
    $stmt->bindValue(':sisa_psd', $qtySo, PDO::PARAM_STR);
    $stmt->bindValue(':awal_qty', $qtySo, PDO::PARAM_STR);
    $stmt->bindValue(':updated_at', date('Y-m-d H:i:s'), PDO::PARAM_STR);
    $stmt->bindValue(':updated_by', (string)($GLOBALS['admin'] ?? 'system'), PDO::PARAM_STR);
    $stmt->execute();

    $verify = $conn->prepare("SELECT sisa_psd, qty_so, awal, status, updated_at, updated_by FROM produk_stokdetail WHERE id_psd = :id_psd LIMIT 1");
    $verify->bindValue(':id_psd', $idPsd, PDO::PARAM_STR);
    $verify->execute();

    return $verify->fetch(PDO::FETCH_ASSOC) ?: array();
}

function getPostedStockOpnameRows($secu) {
    $rows = array();

    if (!empty($_POST['rows_payload'])) {
        $decodedRows = json_decode((string)$_POST['rows_payload'], true);
        if (is_array($decodedRows)) {
            foreach ($decodedRows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $rows[] = array(
                    'id_apl' => $secu->injection($row['id_apl'] ?? ''),
                    'id_psd' => $secu->injection($row['id_psd'] ?? ''),
                    'id_pro' => $secu->injection($row['id_pro'] ?? ''),
                    'nama_pro' => $secu->injection($row['nama_pro'] ?? ''),
                    'nama_inventory' => $secu->injection($row['nama_inventory'] ?? ''),
                    'no_bcode' => $secu->injection($row['no_bcode'] ?? ''),
                    'qty' => $secu->injection($row['qty'] ?? ''),
                    'bcode_so' => $secu->injection($row['bcode_so'] ?? ''),
                    'qty_so' => $secu->injection($row['qty_so'] ?? ''),
                    'selisih' => $secu->injection($row['selisih'] ?? '')
                );
            }
        }
    }

    if (!empty($rows)) {
        return $rows;
    }

    $jumlah = 0;
    if (isset($_POST['id_psd']) && is_array($_POST['id_psd'])) {
        $jumlah = count($_POST['id_psd']);
    } elseif (isset($_POST['id_pro']) && is_array($_POST['id_pro'])) {
        $jumlah = count($_POST['id_pro']);
    }

    for ($index = 0; $index < $jumlah; $index++) {
        $rows[] = array(
            'id_apl' => $secu->injection(@$_POST['id_apl'][$index]),
            'id_psd' => $secu->injection(@$_POST['id_psd'][$index]),
            'id_pro' => $secu->injection(@$_POST['id_pro'][$index]),
            'nama_pro' => $secu->injection(@$_POST['nama_pro'][$index]),
            'nama_inventory' => $secu->injection(@$_POST['nama_inventory'][$index]),
            'no_bcode' => $secu->injection(@$_POST['no_bcode'][$index]),
            'qty' => $secu->injection(@$_POST['qty'][$index]),
            'bcode_so' => $secu->injection(@$_POST['bcode_so'][$index]),
            'qty_so' => $secu->injection(@$_POST['qty_so'][$index]),
            'selisih' => $secu->injection(@$_POST['selisih'][$index])
        );
    }

    return $rows;
}

// ── Validate encrypt ────────────────────────────────────────────────────────
$validEncrypt = ($sourceKey !== '' && $encrypt === md5($tgl . '#' . $sourceKey));

if (!$validEncrypt && $idAplReq !== '' && $idAplReq !== (string)$id_apl) {
    $stmtKey = $conn->prepare('SELECT key_apl FROM aplikasi WHERE id_apl=:id AND active_apl=1 LIMIT 1');
    $stmtKey->bindValue(':id', $idAplReq, PDO::PARAM_STR);
    $stmtKey->execute();
    $branchKey = $stmtKey->fetchColumn();
    if ($branchKey !== false) {
        $validEncrypt = ($encrypt === md5($tgl . '#' . $branchKey));
    }
}

if (!$validEncrypt) {
    respondPostSOJson(array('status' => 'error', 'message' => 'Unauthorized: encrypt tidak valid'), 401);
    $conn = $base->close();
    exit;
}

$admin = ($adminPost !== '') ? $adminPost : ($secu->injection(@$_COOKIE['adminkuy'] ?? '') ?: 'system');

// ── Action dispatch ──────────────────────────────────────────────────────────
try {
    switch ($act) {

        case 'input':
            if (!canApproveStockOpname($tgl)) {
                respondPostSOJson(array(
                    'status' => 'error',
                    'message' => 'Approval stock opname baru bisa diproses mulai tanggal 25 setiap bulan'
                ), 403);
                break;
            }

            $postedRows = getPostedStockOpnameRows($secu);
            $jumlah = count($postedRows);
            if ($jumlah === 0) {
                respondPostSOJson(array('status' => 'error', 'message' => 'Tidak ada data yang dikirim'), 400);
                break;
            }
            $nomor  = 0;
            $ok     = true;
            $errors = array();
            $successCount = 0;
            $skippedCount = 0;
            $id     = '';
            while ($nomor < $jumlah) {
                $rowData        = $postedRows[$nomor];
                $id_psd         = $rowData['id_psd'];
                $id_pro         = $rowData['id_pro'];
                $nama_pro       = $rowData['nama_pro'];
                $nama_inventory = $rowData['nama_inventory'];
                $no_bcode       = $rowData['no_bcode'];
                $qty            = normalizeStockOpnameNumber($rowData['qty']);
                $bcode_so       = $rowData['bcode_so'];
                $qty_so         = normalizeStockOpnameNumber($rowData['qty_so']);
                $selisih        = normalizeStockOpnameNumber($rowData['selisih']);

                $stockRow = findLatestStockOpnameRow($conn, $id_psd, $id_pro, $no_bcode);
                $usesExistingStockRow = !empty($stockRow);

                if ((float)$selisih === 0.0) {
                    $skippedCount++;
                    $nomor++;
                    continue;
                }

                try {
                    $conn->beginTransaction();

                    // Always insert a new stock row on approve to keep an audit/log entry
                    $save = $conn->prepare("INSERT INTO stock (id_psd,id_pro,nama_pro,nama_inventory,no_bcode,qty,bcode_so,qty_so,selisih,created_at,created_by,updated_at,updated_by) VALUES(:id_psd,:id_pro,:nama_pro,:nama_inventory,:no_bcode,:qty,:bcode_so,:qty_so,:selisih,:catat,:admin,:catat,:admin)");
                    $save->bindParam(':id_psd',         $id_psd,         PDO::PARAM_STR);
                    $save->bindParam(':id_pro',         $id_pro,         PDO::PARAM_STR);
                    $save->bindParam(':nama_pro',       $nama_pro,       PDO::PARAM_STR);
                    $save->bindParam(':nama_inventory', $nama_inventory, PDO::PARAM_STR);
                    $save->bindParam(':no_bcode',       $no_bcode,       PDO::PARAM_STR);
                    $save->bindParam(':qty',            $qty,            PDO::PARAM_STR);
                    $save->bindParam(':bcode_so',       $bcode_so,       PDO::PARAM_STR);
                    $save->bindParam(':qty_so',         $qty_so,         PDO::PARAM_STR);
                    $save->bindParam(':selisih',        $selisih,        PDO::PARAM_STR);
                    $save->bindParam(':catat',          $catat,          PDO::PARAM_STR);
                    $save->bindParam(':admin',          $admin,          PDO::PARAM_STR);
                    if ($save->execute() === false) {
                        throw new RuntimeException(json_encode($save->errorInfo()));
                    }

                    $verifiedRow = updateProdukStokdetailFromApprovedStock($conn, $id_psd, $qty_so);
                    if (!$verifiedRow) {
                        throw new RuntimeException(json_encode(array('VERIFY_FAILED', 'produk_stokdetail tidak ditemukan', $id_psd)));
                    }

                    if ((float)$verifiedRow['sisa_psd'] !== (float)$qty_so || (float)$verifiedRow['awal'] !== (float)$qty_so) {
                        throw new RuntimeException(json_encode(array(
                            'VERIFY_FAILED',
                            'Nilai sisa_psd/qty_so/awal tidak sesuai qty_so',
                            array(
                                'id_psd' => $id_psd,
                                'source' => $usesExistingStockRow ? 'stock' : 'legacy-post',
                                'expected_qty_so' => $qty_so,
                                'actual_sisa_psd' => $verifiedRow['sisa_psd'],
                                'actual_qty_so' => $verifiedRow['qty_so'],
                                'actual_awal' => $verifiedRow['awal']
                            )
                        )));
                    }

                    $conn->commit();
                    $successCount++;
                } catch (Exception $e) {
                    $ok = false;
                    if ($conn->inTransaction()) {
                        $conn->rollBack();
                    }
                    $errors[] = array(
                        'id_psd' => $id_psd,
                        'source' => $usesExistingStockRow ? 'stock' : 'legacy-post',
                        'message' => $e->getMessage()
                    );
                }

                $nomor++;
            }

            if (!$ok && !empty($errors)) {
                @file_put_contents(__DIR__ . '/../logs/stockopnameap_api.log',
                    date('c') . " input " . json_encode($errors) . PHP_EOL, FILE_APPEND | LOCK_EX);
            }

            if ($successCount === 0) {
                respondPostSOJson(array('status' => 'error', 'message' => 'Sebagian data gagal disimpan'));
            } else {
                $conn->query("INSERT INTO riwayat VALUES('','$id','Administrator','Create','','$catat','$admin')");
                $message = 'Data berhasil disimpan';
                if (!empty($errors)) {
                    $message .= ' dengan ' . count($errors) . ' data dilewati';
                } elseif ($skippedCount > 0) {
                    $message .= ' (' . $skippedCount . ' data tanpa selisih dilewati)';
                }
                respondPostSOJson(array(
                    'status' => 'success',
                    'message' => $message,
                    'updated' => $successCount,
                    'skipped' => $skippedCount,
                    'errors' => $errors
                ));
            }
            break;

        case 'revisiSemua':
            $postedRows = getPostedStockOpnameRows($secu);
            $jumlah = count($postedRows);
            $nomor  = 0;
            $ok     = true;
            $errors = array();
            while ($nomor < $jumlah) {
                $id_psd = $postedRows[$nomor]['id_psd'];
                if ($id_psd !== '') {
                    $upd = $conn->prepare("UPDATE produk_stokdetail SET status='belum so' WHERE id_psd=:id_psd");
                    $upd->bindParam(':id_psd', $id_psd, PDO::PARAM_STR);
                    if ($upd->execute() === false) {
                        $ok = false;
                        $errors[] = $upd->errorInfo();
                    }
                }
                $nomor++;
            }
            $conn->query("INSERT INTO riwayat VALUES('','','Administrator','Revisi','','$catat','$admin')");
            if (!$ok) {
                @file_put_contents(__DIR__ . '/../logs/stockopnameap_api.log',
                    date('c') . " revisiSemua " . json_encode($errors) . PHP_EOL, FILE_APPEND | LOCK_EX);
                respondPostSOJson(array('status' => 'error', 'message' => 'Sebagian data gagal direvisi'));
            } else {
                respondPostSOJson(array('status' => 'success', 'message' => 'Revisi berhasil'));
            }
            break;

        case 'updateStatusRevisi':
            $id_psd = $secu->injection(@$_POST['keycode']);
            $upd    = $conn->prepare("UPDATE produk_stokdetail SET status='belum so' WHERE id_psd=:id_psd");
            $upd->bindParam(':id_psd', $id_psd, PDO::PARAM_STR);
            if ($upd->execute() === false) {
                @file_put_contents(__DIR__ . '/../logs/stockopnameap_api.log',
                    date('c') . " updateStatusRevisi " . json_encode($upd->errorInfo()) . PHP_EOL, FILE_APPEND | LOCK_EX);
                respondPostSOJson(array('status' => 'error', 'message' => 'Gagal update status'));
            } else {
                $conn->query("INSERT INTO riwayat VALUES('','','Administrator','Revisi','','$catat','$admin')");
                respondPostSOJson(array('status' => 'success', 'message' => 'Status berhasil diupdate'));
            }
            break;

        default:
            respondPostSOJson(array('status' => 'error', 'message' => 'Aksi tidak dikenal'), 400);
    }
} catch (PDOException $e) {
    respondPostSOJson(array('status' => 'error', 'message' => $e->getMessage()), 500);
}

$conn = $base->close();
?>
