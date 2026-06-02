<?php
/**
 * Action Handler untuk Stok Cancel
 * Handles: transfer batch stok cancel ke inventory tujuan (per faktur)
 */
require_once('../../config/connection/connection.php');
require_once('../../config/connection/security.php');
require_once('../../config/function/data.php');

$secu   = new Security;
$base   = new DB;
$data   = new Data;
$conn   = $base->open();

// Admin dari cookie
$admin  = $secu->injection(@$_COOKIE['adminkuy']);
$kunci  = $secu->injection(@$_COOKIE['kuncikuy']);
$catat  = date('Y-m-d H:i:s');

// Validasi admin
if($secu->validadmin($admin, $kunci)==false){
    echo json_encode(['status'=>'error', 'message'=>'Session tidak valid, silakan login ulang']);
    exit;
}

$act    = $secu->injection($_POST['nact']);

switch($act){
    case "transfer_batch":
        /**
         * Transfer batch stok dari produk_stockdetail_cancel ke produk_stokdetail
         * Transfer per faktur, bisa banyak item sekaligus
         */
        try {
            $conn->beginTransaction();
            
            $kode_faktur = $secu->injection($_POST['kode_faktur']);
            $tgl_faktur = $secu->injection($_POST['tgl_faktur']);
            $id_inventory_tujuan = $secu->injection($_POST['id_inventory_tujuan']);
            $keterangan_transfer = $secu->injection($_POST['keterangan_transfer']);
            $items = $_POST['items'] ?? [];
            
            // Validasi
            if(empty($id_inventory_tujuan)){
                throw new Exception("Inventory tujuan harus dipilih");
            }
            
            if(empty($items)){
                throw new Exception("Tidak ada item yang dipilih untuk transfer");
            }
            
            // Filter hanya item yang selected
            $selectedItems = [];
            foreach($items as $item){
                if(isset($item['selected']) && $item['selected'] == '1'){
                    $selectedItems[] = $item;
                }
            }
            
            if(empty($selectedItems)){
                throw new Exception("Pilih minimal 1 item untuk ditransfer");
            }
            
            // Generate ID untuk transfer_stockcancel (header)
            $id_tsc = $data->bcode('TSC', 'id_tsc', 'transfer_stockcancel');
            $kode_transfer = 'TRF-' . date('Ymd') . '-' . substr($id_tsc, -6);
            
            // Hitung total
            $total_item = count($selectedItems);
            $total_qty = 0;
            
            // Process each selected item
            $transferDetails = [];
            foreach($selectedItems as $item){
                $id_psc = intval($item['id_psc']);
                $id_pro = $secu->injection($item['id_pro']);
                $id_trd = $secu->injection($item['id_trd']);
                $no_bcode = $secu->injection($item['no_bcode']);
                $tgl_expired = $secu->injection($item['tgl_expired']);
                $jumlah_cancel = intval($item['jumlah_cancel']);
                $jumlah_transfer = intval($item['jumlah_transfer']);
                
                // Validasi jumlah
                if($jumlah_transfer <= 0 || $jumlah_transfer > $jumlah_cancel){
                    $jumlah_transfer = $jumlah_cancel;
                }
                
                $total_qty += $jumlah_transfer;
                
                // Generate ID untuk produk_stokdetail baru
                $id_psd_baru = $data->bcode('PSD', 'id_psd', 'produk_stokdetail');
                
                // Insert ke produk_stokdetail (using VALUES like other working queries)
                $qInsertStok = "INSERT INTO produk_stokdetail VALUES('', :id_trd, :id_pro, :no_bcode, :tgl_expired, :tgl_psd, :masuk_psd, 0, :sisa_psd, :gudang, '0', 'belum so', '0', 'active', :created_at, :created_by, :updated_at, :updated_by)";
                
                $insertStok = $conn->prepare($qInsertStok);
                $insertStok->bindParam(':id_trd', $id_trd, PDO::PARAM_STR);
                $insertStok->bindParam(':id_pro', $id_pro, PDO::PARAM_STR);
                $insertStok->bindParam(':no_bcode', $no_bcode, PDO::PARAM_STR);
                $insertStok->bindParam(':tgl_expired', $tgl_expired, PDO::PARAM_STR);
                $insertStok->bindParam(':tgl_psd', $catat, PDO::PARAM_STR);
                $insertStok->bindParam(':masuk_psd', $jumlah_transfer, PDO::PARAM_INT);
                $insertStok->bindParam(':sisa_psd', $jumlah_transfer, PDO::PARAM_INT);
                $insertStok->bindParam(':gudang', $id_inventory_tujuan, PDO::PARAM_STR);
                $insertStok->bindParam(':created_at', $catat, PDO::PARAM_STR);
                $insertStok->bindParam(':created_by', $admin, PDO::PARAM_STR);
                $insertStok->bindParam(':updated_at', $catat, PDO::PARAM_STR);
                $insertStok->bindParam(':updated_by', $admin, PDO::PARAM_STR);
                $insertStok->execute();
                
                // Update produk_stockdetail_cancel
                if($jumlah_transfer == $jumlah_cancel){
                    // Transfer semua, update status menjadi 'transferred'
                    $qUpdateCancel = "UPDATE produk_stockdetail_cancel 
                        SET status = 'transferred'
                        WHERE id_psc = :id_psc";
                    $updateCancel = $conn->prepare($qUpdateCancel);
                    $updateCancel->bindParam(':id_psc', $id_psc, PDO::PARAM_INT);
                    $updateCancel->execute();
                } else {
                    // Transfer sebagian, kurangi jumlah_cancel
                    $sisa_cancel = $jumlah_cancel - $jumlah_transfer;
                    $qUpdateCancel = "UPDATE produk_stockdetail_cancel 
                        SET jumlah_cancel = :sisa_cancel
                        WHERE id_psc = :id_psc";
                    $updateCancel = $conn->prepare($qUpdateCancel);
                    $updateCancel->bindParam(':sisa_cancel', $sisa_cancel, PDO::PARAM_INT);
                    $updateCancel->bindParam(':id_psc', $id_psc, PDO::PARAM_INT);
                    $updateCancel->execute();
                }
                
                // Simpan untuk detail
                $transferDetails[] = [
                    'id_psc' => $id_psc,
                    'id_psd_baru' => $id_psd_baru,
                    'id_pro' => $id_pro,
                    'no_bcode' => $no_bcode,
                    'tgl_expired' => $tgl_expired,
                    'jumlah_transfer' => $jumlah_transfer
                ];
            }
            
            // Insert ke transfer_stockcancel (header)
            $qInsertHeader = "INSERT INTO transfer_stockcancel 
                (id_tsc, kode_transfer, kode_faktur, tgl_faktur, id_inventory_tujuan, 
                 total_item, total_qty, keterangan_transfer, status_transfer, 
                 transfer_at, transfer_by, created_at, created_by)
                VALUES
                (:id_tsc, :kode_transfer, :kode_faktur, :tgl_faktur, :id_inventory_tujuan,
                 :total_item, :total_qty, :keterangan_transfer, 'completed',
                 :transfer_at, :transfer_by, :created_at, :created_by)";
            
            $insertHeader = $conn->prepare($qInsertHeader);
            $insertHeader->bindParam(':id_tsc', $id_tsc, PDO::PARAM_STR);
            $insertHeader->bindParam(':kode_transfer', $kode_transfer, PDO::PARAM_STR);
            $insertHeader->bindParam(':kode_faktur', $kode_faktur, PDO::PARAM_STR);
            $insertHeader->bindParam(':tgl_faktur', $tgl_faktur, PDO::PARAM_STR);
            $insertHeader->bindParam(':id_inventory_tujuan', $id_inventory_tujuan, PDO::PARAM_STR);
            $insertHeader->bindParam(':total_item', $total_item, PDO::PARAM_INT);
            $insertHeader->bindParam(':total_qty', $total_qty, PDO::PARAM_INT);
            $insertHeader->bindParam(':keterangan_transfer', $keterangan_transfer, PDO::PARAM_STR);
            $insertHeader->bindParam(':transfer_at', $catat, PDO::PARAM_STR);
            $insertHeader->bindParam(':transfer_by', $admin, PDO::PARAM_STR);
            $insertHeader->bindParam(':created_at', $catat, PDO::PARAM_STR);
            $insertHeader->bindParam(':created_by', $admin, PDO::PARAM_STR);
            $insertHeader->execute();
            
            // Insert ke transfer_stockcancel_detail
            foreach($transferDetails as $detail){
                $qInsertDetail = "INSERT INTO transfer_stockcancel_detail 
                    (id_tsc, id_psc, id_psd_baru, id_pro, no_bcode, tgl_expired, jumlah_transfer, created_at, created_by)
                    VALUES
                    (:id_tsc, :id_psc, :id_psd_baru, :id_pro, :no_bcode, :tgl_expired, :jumlah_transfer, :created_at, :created_by)";
                
                $insertDetail = $conn->prepare($qInsertDetail);
                $insertDetail->bindParam(':id_tsc', $id_tsc, PDO::PARAM_STR);
                $insertDetail->bindParam(':id_psc', $detail['id_psc'], PDO::PARAM_INT);
                $insertDetail->bindParam(':id_psd_baru', $detail['id_psd_baru'], PDO::PARAM_STR);
                $insertDetail->bindParam(':id_pro', $detail['id_pro'], PDO::PARAM_STR);
                $insertDetail->bindParam(':no_bcode', $detail['no_bcode'], PDO::PARAM_STR);
                $insertDetail->bindParam(':tgl_expired', $detail['tgl_expired'], PDO::PARAM_STR);
                $insertDetail->bindParam(':jumlah_transfer', $detail['jumlah_transfer'], PDO::PARAM_INT);
                $insertDetail->bindParam(':created_at', $catat, PDO::PARAM_STR);
                $insertDetail->bindParam(':created_by', $admin, PDO::PARAM_STR);
                $insertDetail->execute();
            }
            
            // Insert log riwayat
            $qRiwayat = "INSERT INTO riwayat VALUES('', :id_tsc, 'transfer_stockcancel', 'Create', :ket, :catat, :admin)";
            $riwayat = $conn->prepare($qRiwayat);
            $riwayat->bindParam(':id_tsc', $id_tsc, PDO::PARAM_STR);
            $ketRiwayat = "Transfer batch stok cancel: $kode_transfer - $total_item item ($total_qty pcs) dari $kode_faktur ke inventory $id_inventory_tujuan";
            $riwayat->bindParam(':ket', $ketRiwayat, PDO::PARAM_STR);
            $riwayat->bindParam(':catat', $catat, PDO::PARAM_STR);
            $riwayat->bindParam(':admin', $admin, PDO::PARAM_STR);
            $riwayat->execute();
            
            $conn->commit();
            
            echo "success";
            
        } catch (Exception $e) {
            $conn->rollBack();
            error_log("ERROR TRANSFER BATCH STOK CANCEL: " . $e->getMessage());
            echo "error:" . $e->getMessage();
        }
        break;
        
    default:
        echo "error:Action tidak valid";
        break;
}
?>
