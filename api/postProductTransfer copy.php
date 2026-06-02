<?php
	require_once('../config/connection/connection.php');
	require_once('../config/connection/security.php');
	require_once('../config/function/data.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$catat	= date('Y-m-d H:i:s');
	$conn	= $base->open();
    $hasil 	= "Error";
	$id		= $secu->injection($_POST['id']);
	$kode	= $secu->injection($_POST['kode']);
	$type	= ($_POST['transfer_apl_type'] === 'IN' ) ? 'OUT' : 'IN';
	$tgl	= $secu->injection($_POST['tanggal']);
	$from	= $secu->injection($_POST['transfer_apl_from']);
	$to		= $secu->injection($_POST['transfer_apl_to']);
	$ket	= $secu->injection($_POST['keterangan']);
    $encrypt= $secu->injection($_GET['encrypt']);
	$status	= 'Waiting';
	$jum	= count($_POST['product']);
	$no		= 0;
    // checking encrypt
    $source = $data->self_apl();
    $sourceKey  = $source['key_apl'];
    if (md5(md5($id . "#" . $kode) . "#" . $sourceKey) == $encrypt) {
        // save transfer product detail
        $msgBugs = array();
        while($no<$jum){
            if (count($msgBugs) == 0) {
                $produk	= $secu->injection($_POST['product'][$no]);
                $jumlah	= str_replace('.', '', $_POST['jumlah'][$no]);
                $idpsd = $secu->injection($_POST['idpsd'][$no]);
                $qSave = "INSERT
                            INTO
                            transaksi_transferstockdetail (
                                id_ttr, 
                                id_psd, 
                                id_pro, 
                                jumlah_ttd, 
                                created_at, 
                                created_by)
                            VALUES(
                                :id, 
                                :id_psd,
                                :produk, 
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
                    $save->execute();
                } catch (PDOException $e) {
                    array_push($msgBugs, $e->getMessage());
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
                                'Create', 
                                '$status', 
                                '$catat', 
                                'System')";
            try {
                $riwayat= $conn->query($qRiwayat);
                $riwayat->execute();
            } catch (PDOException $th) {
                array_push($msgBugs, $e->getMessage());
            }
        }
        if (count($msgBugs) == 0) {
            $hasil = "Success";
            http_response_code(200);
            // update produk stock detail
			$no = 0;
			$now = date('Y-m-d h:i:s');
			while($no<$jum){
                if (count($msgBugs) == 0) {
                    $idpsd = $secu->injection($_POST['idpsd'][$no]);
                    $jumlah	= (int)str_replace('.', '', $_POST['jumlah'][$no]);
                    if ($type == 'IN') {
                        $set = "masuk_psd = masuk_psd + $jumlah, sisa_psd = sisa_psd + $jumlah";
                    } else {
                        $set = "keluar_psd = keluar_psd + $jumlah, sisa_psd = sisa_psd - $jumlah";
                    }
                    $qUpdate = "UPDATE
                                    produk_stokdetail
                                SET
                                    $set,
                                    updated_at = '$now',
                                    updated_by = 'System'
                                WHERE
                                    id_psd = '$idpsd'";
                    try {
                        $update	= $conn->prepare($qUpdate);
                        $update->execute();	
                    } catch (PDOException $th) {
                        array_push($msgBugs, $e->getMessage());
                    }
                }		
				$no++;
			}
        } else {
            $hasil = $msgBugs;
            http_response_code(500);
        }
    } else {
        $hasil = "Unauthorized";
        http_response_code(401);
    }
	$conn	= $base->close();
	header('Access-Control-Allow-Origin: *');
	header("Content-type: application/json; charset=utf-8");
	echo json_encode(array("result" => $hasil));
?>