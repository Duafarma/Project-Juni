<?php
require_once('../config/connection/connection.php');
require_once('../config/connection/security.php');
require_once('../config/function/data.php');
$secu   = new Security;
$base   = new DB;
$data   = new Data;
$catat  = date('Y-m-d H:i:s');
$idExt  = $secu->injection(@$_POST['id']);
$kodeExt = $secu->injection(@$_POST['kode']);
$encrypt = $secu->injection(@$_GET['encrypt']);
$act    = $secu->injection(@$_GET['act']);
$conn   = $base->open();
$hasil  = "Error";
// checking encrypt
$source = $data->self_apl();
$sourceKey  = $source['key_apl'];

// Create log directory if not exists
if (!file_exists('../logs')) {
    mkdir('../logs', 0777, true);
}

// Log request
$logFile = fopen('../logs/api_returTransfer_' . date('Y-m-d') . '.log', 'a+');
fwrite($logFile, date('Y-m-d H:i:s') . " - REQUEST: " . json_encode($_POST) . " ACT: " . $act . "\n");
fwrite($logFile, date('Y-m-d H:i:s') . " - REQUEST DETAILS:\n");
fwrite($logFile, date('Y-m-d H:i:s') . " - ID: {$idExt}\n");
fwrite($logFile, date('Y-m-d H:i:s') . " - KODE: {$kodeExt}\n");
fwrite($logFile, date('Y-m-d H:i:s') . " - ENCRYPT: {$encrypt}\n");
fwrite($logFile, date('Y-m-d H:i:s') . " - ACT: {$act}\n");
fwrite($logFile, date('Y-m-d H:i:s') . " - POST DATA: " . json_encode($_POST) . "\n");

if (md5(md5($idExt . "#" . $kodeExt) . "#" . $sourceKey) == $encrypt) {
    // save transfer product detail
    $msgBugs = array();
    switch ($act) {
        case "input":
            $status = 'Waiting';
            $type   = (@$_POST['transfer_apl_type'] === 'IN') ? 'OUT' : 'IN';
            $tgl    = $secu->injection(@$_POST['tanggal']);
            $from   = $secu->injection(@$_POST['transfer_apl_from']);
            $to     = $secu->injection(@$_POST['transfer_apl_to']);
            $ket    = $secu->injection(@$_POST['keterangan']);
            // Get the names of source and destination applications based on their IDs
            $from_app = $data->get_apl($from);
            $to_app = $data->get_apl($to);
            $nama_apl_from = isset($from_app[0]['nama_apl']) ? $from_app[0]['nama_apl'] : '';
            $nama_apl_to = isset($to_app[0]['nama_apl']) ? $to_app[0]['nama_apl'] : '';

            // Determine which application name to use for code generation based on transfer type
            if ($type == 'OUT') {
                // For outgoing transfers, use source app name (which should be the current app)
                $nama_apl_for_code = $nama_apl_from;
            } else {
                // For incoming transfers, use destination app name (which should be the current app)
                $nama_apl_for_code = $nama_apl_to;
            }



            // Use the ID that was sent from the initiating system
            $id = $idExt;

            // If kodeExt is provided, use it; otherwise generate a new code
            if (!empty($kodeExt)) {
                $kode = $kodeExt;
                fwrite($logFile, date('Y-m-d H:i:s') . " - Using provided external code: {$kode}\n");
            } else {
                // Generate a unique code with TTR prefix (not TRF)

                $kode = $data->transcoderetur('transaksi_transferretur', $nama_apl_for_code, 'kode_ttr', 'TRF');

                fwrite($logFile, date('Y-m-d H:i:s') . " - Generated local code: {$kode}\n");
            }

            $jum    = count(@$_POST['product']);
            $no     = 0;

            // Process products
            while ($no < $jum) {
                if (count($msgBugs) == 0) {
                    $produkExt   = $secu->injection($_POST['product'][$no]);
                    $product = $idpsd = null;
                    $jumlah = str_replace('.', '', $_POST['jumlah'][$no]);
                    $idpsdExt = $secu->injection($_POST['idpsd'][$no]);

                    // Handle different operations based on transfer type
                    if ($type == 'IN') {
                        // Product details for incoming transfer
                        $namaproduct = $secu->injection($_POST['namaproduct'][$no]);
                        $bcode = $secu->injection($_POST['bcode'][$no]);
                        $id_trd = $secu->injection($_POST['id_trd'][$no]);
                        $tgl_expired = $secu->injection($_POST['tgl_expired'][$no]);
                        $tgl_psd = $secu->injection($_POST['tgl_psd'][$no]);
                        $gudang = $secu->injection($_POST['gudang'][$no]);

                        // Check if product exists in receiving system
                        $qSearch = "SELECT * 
                                    FROM produk
                                    WHERE
                                        nama_pro = :namaproduct AND
                                        status_pro = 'Active'
                                    LIMIT 1";
                        try {
                            $search = $conn->prepare($qSearch);
                            $search->bindParam(':namaproduct', $namaproduct, PDO::PARAM_STR);
                            $search->execute();
                            $dataTf = $search->fetch(PDO::FETCH_ASSOC);

                            if (!is_array($dataTf)) {
                                array_push($msgBugs, "Produk " . $namaproduct . " belum tersedia pada system penerima!");
                            } else {
                                $produk = $dataTf['id_pro'];
                            }
                        } catch (PDOException $e) {
                            array_push($msgBugs, $e->getMessage());
                        }

                        // Check or create inventory item
                        if (empty($msgBugs)) {
                            $qSearch = "SELECT * 
                                        FROM inventory_retur
                                        WHERE
                                            id_pro = :produk AND
                                            no_bcode = :bcode
                                        ORDER BY sisa ASC
                                        LIMIT 1";
                            try {
                                $search = $conn->prepare($qSearch);
                                $search->bindParam(':produk', $produk, PDO::PARAM_STR);
                                $search->bindParam(':bcode', $bcode, PDO::PARAM_STR);
                                $search->execute();
                                $dataTf = $search->fetch(PDO::FETCH_ASSOC);

                                if (!is_array($dataTf)) {
                                    // Create new inventory entry
                                    $qSave = "INSERT 
                                            INTO inventory_retur (
                                                id_r_d,
                                                id_pro,
                                                no_bcode,
                                                ed,
                                                tanggal,
                                                masuk,
                                                keluar,
                                                sisa,
                                                gudang,
                                                created_at, 
                                                created_by,
                                                updated_at, 
                                                updated_by)
                                            VALUES (
                                                :id_trd,
                                                :id_pro,
                                                :no_bcode,
                                                :tgl_expired,
                                                :tgl_psd,
                                                0,
                                                0,
                                                0,
                                                :gudang,
                                                :catat, 
                                                'System',
                                                :catat, 
                                                'System')";
                                    $save = $conn->prepare($qSave);
                                    $save->bindParam(':id_trd', $id_trd, PDO::PARAM_STR);
                                    $save->bindParam(':id_pro', $produk, PDO::PARAM_STR);
                                    $save->bindParam(':no_bcode', $bcode, PDO::PARAM_STR);
                                    $save->bindParam(':tgl_expired', $tgl_expired, PDO::PARAM_STR);
                                    $save->bindParam(':tgl_psd', $tgl_psd, PDO::PARAM_STR);
                                    $save->bindParam(':gudang', $gudang, PDO::PARAM_STR);
                                    $save->bindParam(':catat', $catat, PDO::PARAM_STR);
                                    $save->execute();
                                    $idpsd = $conn->lastInsertId();
                                } else {
                                    $idpsd = $dataTf['id_i_r'];
                                }
                            } catch (PDOException $e) {
                                array_push($msgBugs, $e->getMessage());
                            }
                        }
                    } else {
                        // For outgoing transfers, use the local product data
                        $produk = $produkExt;
                        $idpsd = $idpsdExt;
                        $produkExt = null;
                        $idpsdExt = null;
                    }

                    // Save transfer detail
                    if (count($msgBugs) == 0) {
                        $qSave = "INSERT
                                INTO transaksi_transferreturdetail (
                                    id_ttr, 
                                    id_i_r, 
                                    id_ext_i_r, 
                                    id_pro, 
                                    id_ext_pro,
                                    jumlah_ttd, 
                                    created_at, 
                                    created_by)
                                VALUES(
                                    :id, 
                                    :id_i_r,
                                    :id_ext_i_r, 
                                    :produk, 
                                    :id_ext_pro,
                                    :jumlah, 
                                    :catat, 
                                    'System')";
                        try {
                            $save = $conn->prepare($qSave);
                            $save->bindParam(':id', $id, PDO::PARAM_STR);
                            $save->bindParam(':produk', $produk, PDO::PARAM_STR);
                            $save->bindParam(':jumlah', $jumlah, PDO::PARAM_INT);
                            $save->bindParam(':catat', $catat, PDO::PARAM_STR);
                            $save->bindParam(':id_i_r', $idpsd, PDO::PARAM_STR);
                            $save->bindParam(':id_ext_i_r', $idpsdExt, PDO::PARAM_STR);
                            $save->bindParam(':id_ext_pro', $produkExt, PDO::PARAM_STR);
                            $save->execute();
                        } catch (PDOException $e) {
                            array_push($msgBugs, $e->getMessage());
                        }
                    }
                    $no++;
                } else {
                    break;
                }
            }

            // Save transfer header
            if (count($msgBugs) == 0) {
                // Validate required fields
                if (empty($id)) {
                    array_push($msgBugs, "ID transfer tidak boleh kosong");
                    fwrite($logFile, date('Y-m-d H:i:s') . " - Error: Empty ID\n");
                }

                if (empty($kode)) {
                    array_push($msgBugs, "Kode transfer tidak boleh kosong");
                    fwrite($logFile, date('Y-m-d H:i:s') . " - Error: Empty kode_ttr\n");
                }

                // Save transfer header if all validations pass
                if (count($msgBugs) == 0) {
                    $qSave = "INSERT
                            INTO transaksi_transferretur (
                                id_ttr, 
                                kode_ttr, 
                                kode_ext_ttr, 
                                tipe_ttr, 
                                id_app_from, 
                                id_app_to, 
                                tgl_ttr, 
                                ket_ttr, 
                                status_ttr, 
                                created_at, 
                                created_by)
                            VALUES (
                                :id, 
                                :kode, 
                                :kode_ext_ttr, 
                                :type, 
                                :id_app_from, 
                                :id_app_to, 
                                :tgl_ttr, 
                                :ket, 
                                :status, 
                                :catat, 
                                'System')";
                    try {
                        $save = $conn->prepare($qSave);
                        $save->bindParam(':id', $id, PDO::PARAM_STR);
                        $save->bindParam(':kode', $kode, PDO::PARAM_STR);
                        $save->bindParam(':kode_ext_ttr', $kodeExt, PDO::PARAM_STR);
                        $save->bindParam(':type', $type, PDO::PARAM_STR);
                        $save->bindParam(':id_app_from', $from, PDO::PARAM_STR);
                        $save->bindParam(':id_app_to', $to, PDO::PARAM_STR);
                        $save->bindParam(':tgl_ttr', $tgl, PDO::PARAM_STR);
                        $save->bindParam(':ket', $ket, PDO::PARAM_STR);
                        $save->bindParam(':status', $status, PDO::PARAM_STR);
                        $save->bindParam(':catat', $catat, PDO::PARAM_STR);
                        $save->execute();
                    } catch (PDOException $e) {
                        array_push($msgBugs, $e->getMessage());
                        fwrite($logFile, date('Y-m-d H:i:s') . " - Database Error: " . $e->getMessage() . "\n");
                    }
                }
            }

            // Create history record
            if (count($msgBugs) == 0) {
                $qRiwayat = "INSERT
                            INTO riwayat (
                                kode_riwayat, 
                                menu_riwayat, 
                                status_riwayat, 
                                ket_riwayat,
                                created_at, 
                                created_by)
                            VALUES (
                                :id, 
                                'Transfer Retur', 
                                'Create', 
                                :status, 
                                :catat, 
                                'System')";
                try {
                    $riwayat = $conn->prepare($qRiwayat);
                    $riwayat->bindParam(':id', $id, PDO::PARAM_STR);
                    $riwayat->bindParam(':status', $status, PDO::PARAM_STR);
                    $riwayat->bindParam(':catat', $catat, PDO::PARAM_STR);
                    $riwayat->execute();
                } catch (PDOException $e) {
                    array_push($msgBugs, $e->getMessage());
                }
            }

            // Create notification
            if (count($msgBugs) == 0) {
                $titleNotif = "Menunggu Approval Proses Transfer Retur " . $kode;
                $pathNotif = "transferretur";
                $statusNotif = "U";
                $qNotif = "INSERT
                        INTO notifications (
                            id_datanotif, 
                            kode_datanotif, 
                            title_notif, 
                            path_notif, 
                            status_datanotif, 
                            status_notif, 
                            created_at, 
                            created_by)
                        VALUES (
                            :id, 
                            :kode, 
                            :titleNotif, 
                            :pathNotif,
                            :status,
                            :statusNotif, 
                            :catat, 
                            'System')";
                try {
                    $notif = $conn->prepare($qNotif);
                    $notif->bindParam(':id', $id, PDO::PARAM_STR);
                    $notif->bindParam(':kode', $kode, PDO::PARAM_STR);
                    $notif->bindParam(':titleNotif', $titleNotif, PDO::PARAM_STR);
                    $notif->bindParam(':pathNotif', $pathNotif, PDO::PARAM_STR);
                    $notif->bindParam(':status', $status, PDO::PARAM_STR);
                    $notif->bindParam(':statusNotif', $statusNotif, PDO::PARAM_STR);
                    $notif->bindParam(':catat', $catat, PDO::PARAM_STR);
                    $notif->execute();
                } catch (PDOException $e) {
                    array_push($msgBugs, $e->getMessage());
                }
            }
            break;

        case "approval":
            $status = ($secu->injection(@$_POST['approval']) == "1") ? 'Approved' : 'Rejected';
            $id = $idExt; // Use the ID from the request
            $type = $secu->injection(@$_POST['type']);

            fwrite($logFile, date('Y-m-d H:i:s') . " - API Approval Request: id=$id, status=$status, type=$type\n");

            // Check if transfer exists and has the correct status
            $qRead = "SELECT * FROM transaksi_transferretur WHERE id_ttr = :id";
            try {
                $read = $conn->prepare($qRead);
                $read->bindParam(':id', $id, PDO::PARAM_STR);
                $read->execute();
                $dataTf = $read->fetch(PDO::FETCH_ASSOC);

                if (!$dataTf) {
                    // Try looking up by kode_ext_ttr as fallback
                    $qReadAlt = "SELECT * FROM transaksi_transferretur WHERE kode_ext_ttr = :kode";
                    $readAlt = $conn->prepare($qReadAlt);
                    $readAlt->bindParam(':kode', $kodeExt, PDO::PARAM_STR);
                    $readAlt->execute();
                    $dataTf = $readAlt->fetch(PDO::FETCH_ASSOC);

                    if (!$dataTf) {
                        array_push($msgBugs, "Data transfer retur tidak ditemukan dengan ID: $id atau kode: $kodeExt");
                        fwrite($logFile, date('Y-m-d H:i:s') . " - Record not found with ID: $id or kode: $kodeExt\n");
                    } else {
                        $id = $dataTf['id_ttr']; // Update ID to use the found record
                        fwrite($logFile, date('Y-m-d H:i:s') . " - Found record by kode_ext_ttr, using id: $id\n");
                    }
                }

                if ($dataTf && $dataTf['status_ttr'] !== 'Waiting' && $dataTf['status_ttr'] !== 'Process') {
                    array_push($msgBugs, "Status transfer retur tidak valid untuk approval: " . $dataTf['status_ttr']);
                    fwrite($logFile, date('Y-m-d H:i:s') . " - Invalid status for approval: " . $dataTf['status_ttr'] . "\n");
                } else {
                    // It's valid, so we update the kode variable
                    $kode = $dataTf['kode_ttr'];
                    fwrite($logFile, date('Y-m-d H:i:s') . " - Found valid record with status: " . $dataTf['status_ttr'] . "\n");
                }
            } catch (PDOException $e) {
                array_push($msgBugs, $e->getMessage());
                fwrite($logFile, date('Y-m-d H:i:s') . " - Database error: " . $e->getMessage() . "\n");
            }

            // Update inventory if approved
            if (empty($msgBugs) && $status === 'Approved') {
                $qDetails = "SELECT * FROM transaksi_transferreturdetail WHERE id_ttr = :id";
                $details = $conn->prepare($qDetails);
                $details->bindParam(':id', $id, PDO::PARAM_STR);
                $details->execute();

                while ($item = $details->fetch(PDO::FETCH_ASSOC)) {
                    $idIr = $item['id_i_r'];
                    $jumlah = (int)$item['jumlah_ttd'];

                    // Update inventory based on transfer type
                    if ($type === 'IN') {
                        $set = "masuk = masuk + {$jumlah}, sisa = sisa + {$jumlah}";
                    } else {
                        $set = "keluar = keluar + {$jumlah}, sisa = sisa - {$jumlah}";
                    }

                    $qUpdateInventory = "UPDATE
                                inventory_retur
                            SET
                                {$set},
                                updated_at = :catat,
                                updated_by = 'System'
                            WHERE
                                id_i_r = :idIr";

                    $updateInv = $conn->prepare($qUpdateInventory);
                    $updateInv->bindParam(':catat', $catat, PDO::PARAM_STR);
                    $updateInv->bindParam(':idIr', $idIr, PDO::PARAM_STR);
                    $updateInv->execute();

                    fwrite($logFile, date('Y-m-d H:i:s') . " - Updated inventory for id_i_r: $idIr, $set\n");
                }
            }

            // Update transfer status
            if (empty($msgBugs)) {
                $qUpdate = "UPDATE
                    transaksi_transferretur
                SET
                    status_ttr = :status,
                    updated_at = :catat,
                    updated_by = 'System'
                WHERE
                    id_ttr = :id";
                try {
                    $update = $conn->prepare($qUpdate);
                    $update->bindParam(':status', $status, PDO::PARAM_STR);
                    $update->bindParam(':catat', $catat, PDO::PARAM_STR);
                    $update->bindParam(':id', $id, PDO::PARAM_STR);
                    $update->execute();

                    fwrite($logFile, date('Y-m-d H:i:s') . " - Updated status to $status for id: $id\n");
                } catch (PDOException $e) {
                    array_push($msgBugs, $e->getMessage());
                    fwrite($logFile, date('Y-m-d H:i:s') . " - Database error: " . $e->getMessage() . "\n");
                }
            }

            // Add to history
            if (empty($msgBugs)) {
                $qRiwayat = "INSERT INTO riwayat (
                            kode_riwayat, 
                            menu_riwayat, 
                            status_riwayat,
                            ket_riwayat,
                            created_at, 
                            created_by
                        ) VALUES (
                            :id, 
                            'Transfer Retur', 
                            'Update', 
                            :ket, 
                            :catat,
                            'System'
                        )";

                $riwayat = $conn->prepare($qRiwayat);
                $riwayat->bindParam(':id', $id, PDO::PARAM_STR);
                $riwayat->bindParam(':ket', $status, PDO::PARAM_STR);
                $riwayat->bindParam(':catat', $catat, PDO::PARAM_STR);
                $riwayat->execute();
            }

            // Create notification
            if (empty($msgBugs)) {
                $titleNotif = "Proses Transfer Retur " . $kode . " sudah " . $status;
                $pathNotif = "transferretur";
                $statusNotif = "U";
                $qNotif = "INSERT
                        INTO notifications (
                            id_datanotif, 
                            kode_datanotif, 
                            title_notif, 
                            path_notif, 
                            status_datanotif, 
                            status_notif, 
                            created_at, 
                            created_by)
                        VALUES (
                            :id, 
                            :kode, 
                            :titleNotif, 
                            :pathNotif,
                            :status,
                            :statusNotif, 
                            :catat, 
                            'System')";
                try {
                    $notif = $conn->prepare($qNotif);
                    $notif->bindParam(':id', $id, PDO::PARAM_STR);
                    $notif->bindParam(':kode', $kode, PDO::PARAM_STR);
                    $notif->bindParam(':titleNotif', $titleNotif, PDO::PARAM_STR);
                    $notif->bindParam(':pathNotif', $pathNotif, PDO::PARAM_STR);
                    $notif->bindParam(':status', $status, PDO::PARAM_STR);
                    $notif->bindParam(':statusNotif', $statusNotif, PDO::PARAM_STR);
                    $notif->bindParam(':catat', $catat, PDO::PARAM_STR);
                    $notif->execute();
                } catch (PDOException $e) {
                    array_push($msgBugs, $e->getMessage());
                }
            }
            break;

        case "delete":
            $status = 'Canceled';
            $id = $idExt; // Use the ID from the request

            // Check transfer status
            $qRead = "SELECT * FROM transaksi_transferretur WHERE id_ttr = :id";
            try {
                $read = $conn->prepare($qRead);
                $read->bindParam(':id', $id, PDO::PARAM_STR);
                $read->execute();
                $dataTf = $read->fetch(PDO::FETCH_ASSOC);

                if (!$dataTf) {
                    array_push($msgBugs, "Data transfer retur tidak ditemukan");
                } elseif ($dataTf['status_ttr'] !== 'Waiting') {
                    array_push($msgBugs, "Hanya transfer dengan status 'Waiting' yang dapat dibatalkan");
                } else {
                    $kode = $dataTf['kode_ttr'];
                }
            } catch (PDOException $e) {
                array_push($msgBugs, $e->getMessage());
            }

            // Update transfer status
            if (empty($msgBugs)) {
                $qUpdate = "UPDATE
                            transaksi_transferretur
                        SET
                            status_ttr = :status,
                            updated_at = :catat,
                            updated_by = 'System'
                        WHERE
                            id_ttr = :id";
                try {
                    $update = $conn->prepare($qUpdate);
                    $update->bindParam(':status', $status, PDO::PARAM_STR);
                    $update->bindParam(':catat', $catat, PDO::PARAM_STR);
                    $update->bindParam(':id', $id, PDO::PARAM_STR);
                    $update->execute();
                } catch (PDOException $e) {
                    array_push($msgBugs, $e->getMessage());
                }
            }

            // Add to history
            if (empty($msgBugs)) {
                $qRiwayat = "INSERT INTO riwayat (
                            kode_riwayat, 
                            menu_riwayat, 
                            status_riwayat,
                            ket_riwayat,
                            created_at, 
                            created_by
                        ) VALUES (
                            :id, 
                            'Transfer Retur', 
                            'Update', 
                            :ket, 
                            :catat,
                            'System'
                        )";

                $riwayat = $conn->prepare($qRiwayat);
                $riwayat->bindParam(':id', $id, PDO::PARAM_STR);
                $riwayat->bindParam(':ket', $status, PDO::PARAM_STR);
                $riwayat->bindParam(':catat', $catat, PDO::PARAM_STR);
                $riwayat->execute();
            }

            // Create notification
            if (empty($msgBugs)) {
                $titleNotif = "Proses Transfer Retur " . $kode . " telah dibatalkan";
                $pathNotif = "transferretur";
                $statusNotif = "U";
                $qNotif = "INSERT
                        INTO notifications (
                            id_datanotif, 
                            kode_datanotif, 
                            title_notif, 
                            path_notif, 
                            status_datanotif, 
                            status_notif, 
                            created_at, 
                            created_by)
                        VALUES (
                            :id, 
                            :kode, 
                            :titleNotif, 
                            :pathNotif,
                            :status,
                            :statusNotif, 
                            :catat, 
                            'System')";
                try {
                    $notif = $conn->prepare($qNotif);
                    $notif->bindParam(':id', $id, PDO::PARAM_STR);
                    $notif->bindParam(':kode', $kode, PDO::PARAM_STR);
                    $notif->bindParam(':titleNotif', $titleNotif, PDO::PARAM_STR);
                    $notif->bindParam(':pathNotif', $pathNotif, PDO::PARAM_STR);
                    $notif->bindParam(':status', $status, PDO::PARAM_STR);
                    $notif->bindParam(':statusNotif', $statusNotif, PDO::PARAM_STR);
                    $notif->bindParam(':catat', $catat, PDO::PARAM_STR);
                    $notif->execute();
                } catch (PDOException $e) {
                    array_push($msgBugs, $e->getMessage());
                }
            }
            break;
    }
} else {
    $hasil = "Unauthorized";
    http_response_code(401);
    fwrite($logFile, date('Y-m-d H:i:s') . " - Authentication failed\n");
}

// Check for errors
if (empty($msgBugs)) {
    $hasil = "Success";
    http_response_code(200);
} else {
    $hasil = implode(", ", $msgBugs);
    http_response_code(500);
}

// Log result with more details
fwrite($logFile, date('Y-m-d H:i:s') . " - RESULT: " . $hasil . "\n");
if (!empty($msgBugs)) {
    fwrite($logFile, date('Y-m-d H:i:s') . " - ERRORS: " . implode(", ", $msgBugs) . "\n");
}
fwrite($logFile, date('Y-m-d H:i:s') . " ======== END REQUEST ========\n\n");
fclose($logFile);

// Close database connection
$conn = $base->close();

// Send JSON response
header('Access-Control-Allow-Origin: *');
header("Content-type: application/json; charset=utf-8");
echo json_encode([
    "success" => empty($msgBugs),
    "message" => empty($msgBugs) ? "Operasi berhasil" : $hasil,
    "result" => $hasil,
    "data" => [
        "id" => $id,
        "kode" => isset($kode) ? $kode : null
    ],
    "errors" => $msgBugs
]);
