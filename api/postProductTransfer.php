<?php
	require_once('../config/connection/connection.php');
	require_once('../config/connection/security.php');
	require_once('../config/function/data.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$catat	= date('Y-m-d H:i:s');
    $idExt	= $secu->injection(@$_POST['id']);
    $kodeExt= $secu->injection(@$_POST['kode']);
    $encrypt= $secu->injection(@$_GET['encrypt']);
    $act	= $secu->injection(@$_GET['act']);
	$conn	= $base->open();
    $hasil 	= "Error";
    // checking encrypt
    $source = $data->self_apl();
    $sourceKey  = $source['key_apl'];
    if (md5(md5($idExt . "#" . $kodeExt) . "#" . $sourceKey) == $encrypt) {
        // save transfer product detail
        $msgBugs = array();
        switch($act){
            case "input":
                $status	= 'Waiting';
                $type	= (@$_POST['transfer_apl_type'] === 'IN' ) ? 'OUT' : 'IN';
                $tgl	= $secu->injection(@$_POST['tanggal']);
                $from	= $secu->injection(@$_POST['transfer_apl_from']);
                $to		= $secu->injection(@$_POST['transfer_apl_to']);
                $ket	= $secu->injection(@$_POST['keterangan']);
                $unik	= "/TTS/".$data->romawi(date('m')).'/'.date('y');
                $id		= "TTR".time();
                $kode	= $data->transcodeorder($unik, "kode_ttr", "transaksi_transferstock");
                $jum	= count($_POST['product']);
                $no		= 0;
                while($no<$jum){
                    if (count($msgBugs) == 0) {
                        $produkExt	= $secu->injection($_POST['product'][$no]);
                        $product = $idpsd = null;
                        $jumlah	= str_replace('.', '', $_POST['jumlah'][$no]);
                        $idpsdExt = $secu->injection($_POST['idpsd'][$no]);
                        // search in product existing
                        if ($type == 'IN') {
                            // check product
                            $namaproduct = $secu->injection($_POST['namaproduct'][$no]);
                            $qSearch = "SELECT * 
                                        FROM produk
                                        WHERE
                                            nama_pro = '$namaproduct' AND
                                            status_pro = 'Active'
                                        LIMIT 1";
                            try {
                                $search	= $conn->prepare($qSearch);
                                $search->execute();
                                $dataTf  = $search->fetch(PDO::FETCH_ASSOC);
                                if (!is_array($dataTf)) {
                                    array_push($msgBugs, "Produk ".$namaproduct." belum tersedia pada system penerima!");
                                } else {
                                    $produk = $dataTf['id_pro'];
                                }
                            } catch (PDOException $e) {
                                array_push($msgBugs, $e->getMessage());
                            }
                            // check product stok detail
                            if (empty($msgBugs)) {
                                $bcode = $secu->injection($_POST['bcode'][$no]);
                                $id_trd = $secu->injection($_POST['id_trd'][$no]);
                                $tgl_expired = $secu->injection($_POST['tgl_expired'][$no]);
                                $tgl_psd = $secu->injection($_POST['tgl_psd'][$no]);
                                $gudang = $secu->injection($_POST['gudang'][$no]);
                                $qSearch = "SELECT * 
                                            FROM produk_stokdetail
                                            WHERE
                                                id_pro = '$produk' AND
                                                no_bcode = '$bcode'
                                            ORDER BY sisa_psd ASC
                                            LIMIT 1";
                                try {
                                    $search	= $conn->prepare($qSearch);
                                    $search->execute();
                                    $dataTf  = $search->fetch(PDO::FETCH_ASSOC);
                                    if (!is_array($dataTf)) {
                                        $qSave = "INSERT 
                                                    INTO produk_stokdetail (
                                                        id_trd,
                                                        id_pro,
                                                        no_bcode,
                                                        tgl_expired,
                                                        tgl_psd,
                                                        masuk_psd,
                                                        keluar_psd,
                                                        sisa_psd,
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
                                        try {
                                            $save	= $conn->prepare($qSave);
                                            $save->bindParam(':id_trd', $id_trd, PDO::PARAM_STR);
                                            $save->bindParam(':id_pro', $produk, PDO::PARAM_STR);
                                            $save->bindParam(':no_bcode', $bcode, PDO::PARAM_STR);
                                            $save->bindParam(':tgl_expired', $tgl_expired, PDO::PARAM_STR);
                                            $save->bindParam(':tgl_psd', $tgl_psd, PDO::PARAM_STR);
                                            $save->bindParam(':gudang', $gudang, PDO::PARAM_STR);
                                            $save->bindParam(':catat', $catat, PDO::PARAM_STR);
                                            $save->execute();
                                            $idpsd = $conn->lastInsertId();
                                        } catch (PDOException $e) {
                                            array_push($msgBugs, $e->getMessage());
                                        }
                                    } else {
                                        $idpsd = $dataTf['id_psd'];
                                    }
                                } catch (PDOException $e) {
                                    array_push($msgBugs, $e->getMessage());
                                }
                            }
                        }
                        if ($type == 'OUT') {
                            $produk = $produkExt;
                            $idpsd = $idpsdExt;
                            $produkExt = null;
                            $idpsdExt = null;
                        }
                        // save
                        if (count($msgBugs) == 0) {
                            $qSave = "INSERT
                                        INTO
                                        transaksi_transferstockdetail (
                                            id_ttr, 
                                            id_psd,
                                            id_ext_psd, 
                                            id_pro,
                                            id_ext_pro,
                                            jumlah_ttd, 
                                            created_at, 
                                            created_by)
                                        VALUES(
                                            :id, 
                                            :id_psd,
                                            :id_ext_psd, 
                                            :produk, 
                                            :id_ext_pro,
                                            :jumlah, 
                                            :catat, 
                                            'System')";
                            try {
                                $save	= $conn->prepare($qSave);
                                $save->bindParam(':id', $id, PDO::PARAM_STR);
                                $save->bindParam(':produk', $produk, PDO::PARAM_STR);
                                $save->bindParam(':jumlah', $jumlah, PDO::PARAM_INT);
                                $save->bindParam(':catat', $catat, PDO::PARAM_STR);
                                $save->bindParam(':id_psd', $idpsd, PDO::PARAM_STR);
                                $save->bindParam(':id_ext_psd', $idpsdExt, PDO::PARAM_STR);
                                $save->bindParam(':id_ext_pro', $produkExt, PDO::PARAM_STR);
                                $save->execute();
                            } catch (PDOException $e) {
                                array_push($msgBugs, $e->getMessage());
                            }
                        }
                        $no++;
                    }
                }
                // save transfer product
                if (count($msgBugs) == 0) {
                        $qSave = "INSERT
                                INTO
                                transaksi_transferstock (
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
                        $save	= $conn->prepare($qSave);
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
                    }
                }
                // save riwayat & notification
                if (count($msgBugs) == 0) {
                    $qRiwayat = "INSERT
                                    INTO
                                    riwayat (
                                        kode_riwayat, 
                                        menu_riwayat, 
                                        status_riwayat, 
                                        ket_riwayat,
                                        created_at, 
                                        created_by)
                                    VALUES (
                                        '$id', 
                                        'Transfer Stok', 
                                        'Create', 
                                        '$status', 
                                        '$catat', 
                                        'System')";
                    try {
                        $riwayat= $conn->prepare($qRiwayat);
                        $riwayat->execute();
                    } catch (PDOException $e) {
                        array_push($msgBugs, $e->getMessage());
                    }
                }
                if (count($msgBugs) == 0) {
                    // create new notif transfer
                    $titleNotif = "Menunggu Approval Proses Transfer Stok ".$kode;
                    $pathNotif = "transferstok";
                    $statusNotif = "U";
                    $qNotif = "INSERT
                                    INTO
                                    notifications (
                                        id_datanotif, 
                                        kode_datanotif, 
                                        title_notif, 
                                        path_notif, 
                                        status_datanotif, 
                                        status_notif, 
                                        created_at, 
                                        created_by)
                                    VALUES (
                                        '$id', 
                                        '$kode', 
                                        '$titleNotif', 
                                        '$pathNotif',
                                        '$status',
                                        '$statusNotif', 
                                        '$catat', 
                                        'System')";
                    try {
                        $notif= $conn->prepare($qNotif);
                        $notif->execute();
                    } catch (PDOException $e) {
                        array_push($msgBugs, $e->getMessage());
                    }
                }
                break;
            case "approval":
                $status = ($secu->injection(@$_POST['approval']) == "1") ? 'Approved' : 'Rejected';
                $kode = $secu->injection(@$_POST['kode_ext']);
                // read status transfer stok
                $qRead  = "SELECT * FROM transaksi_transferstock WHERE kode_ttr = '$kode'";
                try {
                    $read	= $conn->prepare($qRead);
                    $read->execute();
                    $dataTf  = $read->fetch(PDO::FETCH_ASSOC);
                    if (empty($dataTf) || $dataTf['status_ttr'] != 'Process') {
                        array_push($msgBugs, "Approval transfer stok tidak dapat diproses!");
                    } else {
                        $id = $dataTf['id_ttr'];
                    }
                } catch (PDOException $e) {
                    array_push($msgBugs, $e->getMessage());
                }
                // update produk stok detail
                if (empty($msgBugs)) {
                    // update product detail stok
                    if ($status == 'Approved') {
                        // read status transfer stok
                        $qRead  = "SELECT * FROM transaksi_transferstockdetail WHERE id_ttr = '$id'";
                        try {
                            $read	= $conn->prepare($qRead);
                            $read->execute();
                        } catch (PDOException $e) {
                            array_push($msgBugs, $e->getMessage());
                        }
                        if (empty($msgBugs)) {
                            $datasTf  = $read->fetchAll(PDO::FETCH_ASSOC);
                            $type   = $dataTf['tipe_ttr'];
                            foreach ($datasTf as $dataTf) {
                                $idpsd = $dataTf['id_psd'];
                                $jumlah	= (int)$dataTf['jumlah_ttd'];
                                if ($type == 'IN') {
                                    $set = "masuk_psd = masuk_psd + $jumlah, sisa_psd = sisa_psd + $jumlah";
                                } else {
                                    $set = "keluar_psd = keluar_psd + $jumlah, sisa_psd = sisa_psd - $jumlah";
                                }
                                $qUpdate = "UPDATE
                                                produk_stokdetail
                                            SET
                                                $set,
                                                updated_at = '$catat',
                                                updated_by = 'System'
                                            WHERE
                                                id_psd = '$idpsd'";
                                try {
                                    $update	= $conn->prepare($qUpdate);
                                    $update->execute();	
                                } catch (PDOException $e) {
                                    array_push($msgBugs, $e->getMessage());
                                }
                            }
                        }
                    }
                }
                // update transfer
				if (empty($msgBugs)) {
					$qUpdate = "UPDATE
									transaksi_transferstock
								SET
									status_ttr = '$status',
									updated_at = '$catat',
									updated_by = 'System'
								WHERE
									id_ttr = '$id'";
					try {
						$update	= $conn->prepare($qUpdate);
						$update->execute();	
					} catch (PDOException $e) {
						array_push($msgBugs, $e->getMessage());
					}
				}
				// save riwayat
				if (empty($msgBugs)) {
					$qRiwayat = "INSERT
									INTO
									riwayat (
										kode_riwayat, 
										menu_riwayat, 
										status_riwayat,
										ket_riwayat,
										created_at, 
										created_by)
									VALUES (
										'$id', 
										'Transfer Stok', 
										'Update', 
										'$status.',
										'$catat',
										'System')";
					try {
						$riwayat = $conn->prepare($qRiwayat);
						$riwayat->execute();
					} catch (PDOException $e) {
						array_push($msgBugs, $e->getMessage());
					}
				}
                if (empty($msgBugs)) {
                    // create new notif transfer
                    $titleNotif = "Proses Transfer Stok ".$kode." sudah ".$status;
                    $pathNotif = "transferstok";
                    $statusNotif = "U";
                    $qNotif = "INSERT
                                    INTO
                                    notifications (
                                        id_datanotif, 
                                        kode_datanotif, 
                                        title_notif, 
                                        path_notif, 
                                        status_datanotif, 
                                        status_notif, 
                                        created_at, 
                                        created_by)
                                    VALUES (
                                        '$id', 
                                        '$kode', 
                                        '$titleNotif', 
                                        '$pathNotif',
                                        '$status',
                                        '$statusNotif', 
                                        '$catat', 
                                        'System')";
                    try {
                        $notif= $conn->prepare($qNotif);
                        $notif->execute();
                    } catch (PDOException $e) {
                        array_push($msgBugs, $e->getMessage());
                    }
                }
                break;
            case "delete":
                $status = 'Canceled';
                // check current
                $qRead  = "SELECT * FROM transaksi_transferstock WHERE kode_ext_ttr = '$kodeExt'";
                try {
                    $read	= $conn->prepare($qRead);
                    $read->execute();
                } catch (PDOException $e) {
                    array_push($msgBugs, $e->getMessage());
                }
                // update transfer
				if (count($msgBugs) == 0) {
                    $dataTf = $read->fetch(PDO::FETCH_ASSOC);
                    $id     = $dataTf['id_ttr'];
                    if (in_array($dataTf['status_ttr'],array('Waiting'))) {
                        $qUpdate = "UPDATE
                                        transaksi_transferstock
                                    SET
                                        status_ttr = '$status',
                                        updated_at = '$catat',
                                        updated_by = 'System'
                                    WHERE
                                        id_ttr = '$id'";
                        try {
                            $update	= $conn->prepare($qUpdate);
                            $update->execute();	
                        } catch (PDOException $e) {
                            array_push($msgBugs, $e->getMessage());
                        }
                        // save riwayat
                        if (count($msgBugs) == 0) {
                            $qRiwayat = "INSERT
                                            INTO
                                            riwayat (
                                                kode_riwayat, 
                                                menu_riwayat, 
                                                status_riwayat,
                                                ket_riwayat,
                                                created_at, 
                                                created_by)
                                            VALUES (
                                                '$id', 
                                                'Transfer Stok', 
                                                'Update', 
                                                '$status',
                                                '$catat',
                                                'System')";
                            try {
                                $riwayat = $conn->prepare($qRiwayat);
                                $riwayat->execute();
                            } catch (PDOException $e) {
                                array_push($msgBugs, $e->getMessage());
                            }
                        }
                    } else {
                        array_push($msgBugs, "Action delete transfer stok sudah tidak dapat diproses");
                    }
				}
                break;
            default:
                $hasil = "Error";
                http_response_code(500);
                break;
        }
    } else {
        $hasil = "Unauthorized";
        http_response_code(401);
    }
    // check error
    if (empty($msgBugs)) {
        $hasil = "Success";
        http_response_code(200);
    } else {
        $hasil = implode(", ",$msgBugs);
        http_response_code(500);
    }
	$conn	= $base->close();
	header('Access-Control-Allow-Origin: *');
	header("Content-type: application/json; charset=utf-8");
	echo json_encode(array("result" => $hasil));
?>
