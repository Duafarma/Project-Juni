<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

require_once('../../config/connection/connection.php');
require_once('../../config/connection/security.php');
require_once('../../config/function/data.php');

$secu = new Security;
$base = new DB;
$data = new Data;
$conn = $base->open();
$catat = date('Y-m-d H:i:s');
$admin = $secu->injection(@$_COOKIE['adminkuy']);
$kunci = $secu->injection(@$_COOKIE['kuncikuy']);
$secu->validadmin($admin, $kunci);

$response = ['status' => 'error', 'message' => 'Unknown error'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['menu']) && $_POST['menu'] === 'retur') {
        
        $id_tfk = $secu->injection($_POST['id_tfk']);
        $keterangan = $secu->injection($_POST['keterangan']);
        $selected_items = isset($_POST['selected_items']) ? $_POST['selected_items'] : [];
        
        if (empty($selected_items)) {
            throw new Exception('Tidak ada item yang dipilih');
        }
        
        if (empty($keterangan)) {
            throw new Exception('Keterangan retur wajib diisi');
        }
        
        // Begin transaction
        $conn->beginTransaction();
        
        // Generate ID dan Nomor Retur
        $id_trk = uniqid();
        $tahun = date('y');
        $bulan = date('m');
        $bulanRomawi = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        
        // Get last number
        $qLastNo = "SELECT no_retur FROM transaksi_retur_konsinyasi 
                    WHERE YEAR(tgl_retur) = YEAR(CURDATE()) 
                    ORDER BY created_at DESC LIMIT 1";
        $stmtLastNo = $conn->query($qLastNo);
        $lastData = $stmtLastNo->fetch(PDO::FETCH_ASSOC);
        
        if ($lastData) {
            $lastNo = intval(substr($lastData['no_retur'], 0, 4));
            $newNo = str_pad($lastNo + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNo = '0001';
        }
        
        $no_retur = $newNo . '/RTK/' . $bulanRomawi[intval($bulan)] . '/' . $tahun;
        $tgl_retur = date('Y-m-d');
        
        // Get outlet dari faktur
        $qGetOutlet = "SELECT id_out FROM transaksi_faktur_konsinyasi WHERE id_tfk = :id_tfk";
        $stmtOutlet = $conn->prepare($qGetOutlet);
        $stmtOutlet->bindParam(':id_tfk', $id_tfk, PDO::PARAM_STR);
        $stmtOutlet->execute();
        $dataOutlet = $stmtOutlet->fetch(PDO::FETCH_ASSOC);
        $id_out = $dataOutlet['id_out'];
        
        $total_retur = 0;
        $items_processed = 0;
        
        foreach ($selected_items as $id_tfd) {
            $id_tfd = $secu->injection($id_tfd);
            $id_psd = $secu->injection($_POST['id_psd'][$id_tfd]);
            $qty_retur = isset($_POST['qty_retur'][$id_tfd]) ? intval($_POST['qty_retur'][$id_tfd]) : 0;
            $max_qty = intval($_POST['max_qty'][$id_tfd]);
            
            if ($qty_retur <= 0) {
                continue; // Skip item dengan qty 0
            }
            
            if ($qty_retur > $max_qty) {
                throw new Exception("Jumlah retur melebihi sisa barang (Max: $max_qty)");
            }
            
            // Ambil sisa sebelum retur untuk histori
            $qGetSisa = "SELECT sisa_tfd FROM transaksi_fakturdetail_konsinyasi WHERE id_tfd = :id_tfd";
            $stmtGetSisa = $conn->prepare($qGetSisa);
            $stmtGetSisa->bindParam(':id_tfd', $id_tfd, PDO::PARAM_STR);
            $stmtGetSisa->execute();
            $dataSisa = $stmtGetSisa->fetch(PDO::FETCH_ASSOC);
            $qty_sisa_sebelum = $dataSisa['sisa_tfd'];
            $qty_sisa_sesudah = $qty_sisa_sebelum - $qty_retur;
            
            // Get id_pro dan no_bcode dari konsinyasi
            $qGetData = "SELECT tfd.id_pro, psk.no_bcode 
                         FROM transaksi_fakturdetail_konsinyasi tfd
                         LEFT JOIN produk_stokdetail_konsinyasi psk ON tfd.id_psd = psk.id_psd
                         WHERE tfd.id_tfd = :id_tfd";
            $stmtGetData = $conn->prepare($qGetData);
            $stmtGetData->bindParam(':id_tfd', $id_tfd, PDO::PARAM_STR);
            $stmtGetData->execute();
            $dataItem = $stmtGetData->fetch(PDO::FETCH_ASSOC);
            $id_pro = $dataItem['id_pro'];
            $no_bcode = $dataItem['no_bcode'];
            
            // Get id_psd yang benar dari produk_stokdetail (stok gudang normal)
            $qGetPsdGudang = "SELECT id_psd FROM produk_stokdetail 
                              WHERE id_pro = :id_pro AND no_bcode = :no_bcode LIMIT 1";
            $stmtGetPsdGudang = $conn->prepare($qGetPsdGudang);
            $stmtGetPsdGudang->bindParam(':id_pro', $id_pro, PDO::PARAM_STR);
            $stmtGetPsdGudang->bindParam(':no_bcode', $no_bcode, PDO::PARAM_STR);
            $stmtGetPsdGudang->execute();
            $dataPsdGudang = $stmtGetPsdGudang->fetch(PDO::FETCH_ASSOC);
            
            if (!$dataPsdGudang) {
                throw new Exception("Batch $no_bcode tidak ditemukan di stok gudang");
            }
            $id_psd_gudang = $dataPsdGudang['id_psd'];
            
            // 1. Update transaksi_fakturdetail_konsinyasi (kurangi sisa)
            $qUpdateDetail = "UPDATE transaksi_fakturdetail_konsinyasi 
                              SET sisa_tfd = sisa_tfd - :qty,
                                  updated_at = :catat,
                                  updated_by = :admin
                              WHERE id_tfd = :id_tfd";
            $stmtDetail = $conn->prepare($qUpdateDetail);
            $stmtDetail->bindParam(':qty', $qty_retur, PDO::PARAM_INT);
            $stmtDetail->bindParam(':id_tfd', $id_tfd, PDO::PARAM_STR);
            $stmtDetail->bindParam(':catat', $catat, PDO::PARAM_STR);
            $stmtDetail->bindParam(':admin', $admin, PDO::PARAM_STR);
            $stmtDetail->execute();
            
            // 2. Update produk_stokdetail_konsinyasi (kembalikan stok)
            // 2. Update produk_stokdetail_konsinyasi (barang keluar dari sistem konsinyasi)
            // keluar_psd dikurangi karena barang return dari outlet (tidak jadi keluar)
            // sisa_psd dikurangi karena barang pindah ke gudang normal (keluar dari konsinyasi)
            $qUpdateKonsi = "UPDATE produk_stokdetail_konsinyasi 
                            SET keluar_psd = keluar_psd - :qty,
                                sisa_psd = sisa_psd - :qty,
                                updated_at = :catat,
                                updated_by = :admin
                            WHERE id_psd = :id_psd AND id_tfk = :id_tfk";
            $stmtKonsi = $conn->prepare($qUpdateKonsi);
            $stmtKonsi->bindParam(':qty', $qty_retur, PDO::PARAM_INT);
            $stmtKonsi->bindParam(':id_psd', $id_psd, PDO::PARAM_INT);
            $stmtKonsi->bindParam(':id_tfk', $id_tfk, PDO::PARAM_STR);
            $stmtKonsi->bindParam(':catat', $catat, PDO::PARAM_STR);
            $stmtKonsi->bindParam(':admin', $admin, PDO::PARAM_STR);
            $stmtKonsi->execute();
            
            // 3. Kembalikan ke produk_stokdetail (stok gudang normal)
            // sisa_psd bertambah karena barang masuk ke gudang dari retur konsinyasi
            $qUpdateStok = "UPDATE produk_stokdetail 
                           SET sisa_psd = sisa_psd + :qty,
                               updated_at = :catat,
                               updated_by = :admin
                           WHERE id_psd = :id_psd_gudang";
            $stmtStok = $conn->prepare($qUpdateStok);
            $stmtStok->bindParam(':qty', $qty_retur, PDO::PARAM_INT);
            $stmtStok->bindParam(':id_psd_gudang', $id_psd_gudang, PDO::PARAM_INT);
            $stmtStok->bindParam(':catat', $catat, PDO::PARAM_STR);
            $stmtStok->bindParam(':admin', $admin, PDO::PARAM_STR);
            $stmtStok->execute();
            
            // 4. Insert ke transaksi_retur_konsinyasi_detail (HISTORI)
            $qInsertDetail = "INSERT INTO transaksi_retur_konsinyasi_detail 
                             (id_trkd, id_trk, id_tfd, id_psd, id_pro, qty_retur, qty_sisa_sebelum, qty_sisa_sesudah, created_at, created_by)
                             VALUES (NULL, :id_trk, :id_tfd, :id_psd, :id_pro, :qty_retur, :qty_sebelum, :qty_sesudah, :catat, :admin)";
            $stmtInsertDetail = $conn->prepare($qInsertDetail);
            $stmtInsertDetail->bindParam(':id_trk', $id_trk, PDO::PARAM_STR);
            $stmtInsertDetail->bindParam(':id_tfd', $id_tfd, PDO::PARAM_STR);
            $stmtInsertDetail->bindParam(':id_psd', $id_psd, PDO::PARAM_INT);
            $stmtInsertDetail->bindParam(':id_pro', $id_pro, PDO::PARAM_STR);
            $stmtInsertDetail->bindParam(':qty_retur', $qty_retur, PDO::PARAM_INT);
            $stmtInsertDetail->bindParam(':qty_sebelum', $qty_sisa_sebelum, PDO::PARAM_INT);
            $stmtInsertDetail->bindParam(':qty_sesudah', $qty_sisa_sesudah, PDO::PARAM_INT);
            $stmtInsertDetail->bindParam(':catat', $catat, PDO::PARAM_STR);
            $stmtInsertDetail->bindParam(':admin', $admin, PDO::PARAM_STR);
            $stmtInsertDetail->execute();
            
            $total_retur += $qty_retur;
            $items_processed++;
        }
        
        if ($items_processed == 0) {
            throw new Exception('Tidak ada item yang diproses (jumlah retur = 0)');
        }
        
        // 5. Insert ke transaksi_retur_konsinyasi (HEADER HISTORI)
        $qInsertHeader = "INSERT INTO transaksi_retur_konsinyasi 
                         (id_trk, id_tfk, no_retur, tgl_retur, id_out, total_item, total_qty, keterangan, status_trk, created_at, created_by, updated_at, updated_by)
                         VALUES (:id_trk, :id_tfk, :no_retur, :tgl_retur, :id_out, :total_item, :total_qty, :keterangan, 'Selesai', :catat, :admin, :catat, :admin)";
        $stmtInsertHeader = $conn->prepare($qInsertHeader);
        $stmtInsertHeader->bindParam(':id_trk', $id_trk, PDO::PARAM_STR);
        $stmtInsertHeader->bindParam(':id_tfk', $id_tfk, PDO::PARAM_STR);
        $stmtInsertHeader->bindParam(':no_retur', $no_retur, PDO::PARAM_STR);
        $stmtInsertHeader->bindParam(':tgl_retur', $tgl_retur, PDO::PARAM_STR);
        $stmtInsertHeader->bindParam(':id_out', $id_out, PDO::PARAM_STR);
        $stmtInsertHeader->bindParam(':total_item', $items_processed, PDO::PARAM_INT);
        $stmtInsertHeader->bindParam(':total_qty', $total_retur, PDO::PARAM_INT);
        $stmtInsertHeader->bindParam(':keterangan', $keterangan, PDO::PARAM_STR);
        $stmtInsertHeader->bindParam(':catat', $catat, PDO::PARAM_STR);
        $stmtInsertHeader->bindParam(':admin', $admin, PDO::PARAM_STR);
        $stmtInsertHeader->execute();
        
        // 4. Update status transaksi_faktur_konsinyasi
        $qCheckSisa = "SELECT SUM(sisa_tfd) as total_sisa 
                       FROM transaksi_fakturdetail_konsinyasi 
                       WHERE id_tfk = :id_tfk";
        $stmtCheck = $conn->prepare($qCheckSisa);
        $stmtCheck->bindParam(':id_tfk', $id_tfk, PDO::PARAM_STR);
        $stmtCheck->execute();
        $dataSisa = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        $totalSisa = $dataSisa['total_sisa'];
        
        // Tentukan status
        if ($totalSisa <= 0) {
            $statusBaru = 'Selesai';
        } else {
            $statusBaru = 'Sebagian';
        }
        
        $qUpdateStatus = "UPDATE transaksi_faktur_konsinyasi 
                         SET status_tfk = :status,
                             updated_at = :catat,
                             updated_by = :admin
                         WHERE id_tfk = :id_tfk";
        $stmtStatus = $conn->prepare($qUpdateStatus);
        $stmtStatus->bindParam(':status', $statusBaru, PDO::PARAM_STR);
        $stmtStatus->bindParam(':id_tfk', $id_tfk, PDO::PARAM_STR);
        $stmtStatus->bindParam(':catat', $catat, PDO::PARAM_STR);
        $stmtStatus->bindParam(':admin', $admin, PDO::PARAM_STR);
        $stmtStatus->execute();
        
        // 5. Insert ke riwayat
        $qRiwayat = "INSERT INTO riwayat 
                     (kode_riwayat, menu_riwayat, status_riwayat, ket_riwayat, created_at, created_by) 
                     VALUES (:kode, 'Retur Konsinyasi', 'Retur', :ket, :catat, :admin)";
        $stmtRiwayat = $conn->prepare($qRiwayat);
        $ketRiwayat = "No: $no_retur | $keterangan (Total: $total_retur pcs, Items: $items_processed)";
        $stmtRiwayat->bindParam(':kode', $id_tfk, PDO::PARAM_STR);
        $stmtRiwayat->bindParam(':ket', $ketRiwayat, PDO::PARAM_STR);
        $stmtRiwayat->bindParam(':catat', $catat, PDO::PARAM_STR);
        $stmtRiwayat->bindParam(':admin', $admin, PDO::PARAM_STR);
        $stmtRiwayat->execute();
        
        // Commit transaction
        $conn->commit();
        
        $response = [
            'status' => 'success',
            'message' => "Retur berhasil! No: $no_retur | $items_processed item ($total_retur pcs) telah dikembalikan ke gudang. Status: $statusBaru"
        ];
        
    } else {
        throw new Exception('Invalid request method');
    }
    
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    $response = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
}

header('Content-Type: application/json');
echo json_encode($response);
?>
