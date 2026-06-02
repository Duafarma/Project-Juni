<?php
/**
 * Action Handler untuk Transfer Stock Cancel
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
    echo "Session tidak valid, silakan login ulang";
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
            $nomor_transfer = $secu->injection($_POST['nomor_transfer']);
            $keterangan_transfer = $secu->injection($_POST['keterangan_transfer']);
            $items = $_POST['items'] ?? [];
            
            // Validasi
            if(empty($nomor_transfer)){
                throw new Exception("Nomor transfer harus diisi");
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
            $total_item = 0; // Will be calculated from actual transfers
            $total_qty = 0;
            
            // Group items by parent_no untuk handle split batch
            $itemsByParent = [];
            foreach($selectedItems as $item){
                $parent_no = isset($item['parent_no']) ? $item['parent_no'] : null;
                $is_split = isset($item['is_split']) && $item['is_split'] == '1';
                
                if($is_split && $parent_no){
                    // Split row - group dengan parent
                    if(!isset($itemsByParent[$parent_no])){
                        $itemsByParent[$parent_no] = [
                            'parent' => null,
                            'splits' => []
                        ];
                    }
                    $itemsByParent[$parent_no]['splits'][] = $item;
                } else {
                    // Parent row atau row tanpa split
                    if($parent_no){
                        if(!isset($itemsByParent[$parent_no])){
                            $itemsByParent[$parent_no] = [
                                'parent' => $item,
                                'splits' => []
                            ];
                        } else {
                            $itemsByParent[$parent_no]['parent'] = $item;
                        }
                    } else {
                        // Standalone item tanpa parent_no (backward compatibility)
                        $itemsByParent[] = [
                            'parent' => $item,
                            'splits' => []
                        ];
                    }
                }
            }
            
            // Process each parent with its splits
            $transferDetails = [];
            foreach($itemsByParent as $group){
                $parentItem = $group['parent'];
                $splitItems = $group['splits'];
                
                if(!$parentItem) continue; // Skip jika tidak ada parent
                
                $id_psc = intval($parentItem['id_psc']);
                $id_pro = $secu->injection($parentItem['id_pro']);
                $id_trd = $secu->injection($parentItem['id_trd']);
                $jumlah_cancel = intval($parentItem['jumlah_cancel']);
                $gudang_asal = $secu->injection($parentItem['gudang']);
                
                $total_transfer_from_parent = 0;
                
                // Jika ada split items, proses mereka dulu
                if(!empty($splitItems)){
                    foreach($splitItems as $splitItem){
                        $no_bcode = $secu->injection($splitItem['no_bcode']);
                        $tgl_expired = $secu->injection($splitItem['tgl_expired']);
                        $jumlah_transfer = intval($splitItem['jumlah_transfer']);
                        
                        if($jumlah_transfer <= 0) continue;
                        
                        $total_transfer_from_parent += $jumlah_transfer;
                        $total_qty += $jumlah_transfer;
                        
                        // Generate ID untuk produk_stokdetail baru
                        $id_psd_baru = $data->bcode('PSD', 'id_psd', 'produk_stokdetail');
                        
                        // Insert ke produk_stokdetail
                        $qInsertStok = "INSERT INTO produk_stokdetail VALUES(:id_psd, :id_trd, :id_pro, :no_bcode, :tgl_expired, :tgl_psd, :masuk_psd, 0, :sisa_psd, :gudang, '0', 'belum so', '0', 'active', :created_at, :created_by, :updated_at, :updated_by)";
                        
                        $insertStok = $conn->prepare($qInsertStok);
                        $insertStok->bindParam(':id_psd', $id_psd_baru, PDO::PARAM_STR);
                        $insertStok->bindParam(':id_trd', $id_trd, PDO::PARAM_STR);
                        $insertStok->bindParam(':id_pro', $id_pro, PDO::PARAM_STR);
                        $insertStok->bindParam(':no_bcode', $no_bcode, PDO::PARAM_STR);
                        $insertStok->bindParam(':tgl_expired', $tgl_expired, PDO::PARAM_STR);
                        $insertStok->bindParam(':tgl_psd', $catat, PDO::PARAM_STR);
                        $insertStok->bindParam(':masuk_psd', $jumlah_transfer, PDO::PARAM_INT);
                        $insertStok->bindParam(':sisa_psd', $jumlah_transfer, PDO::PARAM_INT);
                        $insertStok->bindParam(':gudang', $gudang_asal, PDO::PARAM_STR);
                        $insertStok->bindParam(':created_at', $catat, PDO::PARAM_STR);
                        $insertStok->bindParam(':created_by', $admin, PDO::PARAM_STR);
                        $insertStok->bindParam(':updated_at', $catat, PDO::PARAM_STR);
                        $insertStok->bindParam(':updated_by', $admin, PDO::PARAM_STR);
                        $insertStok->execute();
                        
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
                } else {
                    // Tidak ada split, proses parent saja
                    $no_bcode = $secu->injection($parentItem['no_bcode']);
                    $tgl_expired = $secu->injection($parentItem['tgl_expired']);
                    $jumlah_transfer = intval($parentItem['jumlah_transfer']);
                    
                    // Skip jika qty 0 atau negatif
                    if($jumlah_transfer <= 0){
                        continue;
                    }
                    
                    $total_transfer_from_parent = $jumlah_transfer;
                    $total_qty += $jumlah_transfer;
                    
                    // Generate ID untuk produk_stokdetail baru
                    $id_psd_baru = $data->bcode('PSD', 'id_psd', 'produk_stokdetail');
                    
                    // Insert ke produk_stokdetail
                    $qInsertStok = "INSERT INTO produk_stokdetail VALUES(:id_psd, :id_trd, :id_pro, :no_bcode, :tgl_expired, :tgl_psd, :masuk_psd, 0, :sisa_psd, :gudang, '0', 'belum so', '0', 'active', :created_at, :created_by, :updated_at, :updated_by)";
                    
                    $insertStok = $conn->prepare($qInsertStok);
                    $insertStok->bindParam(':id_psd', $id_psd_baru, PDO::PARAM_STR);
                    $insertStok->bindParam(':id_trd', $id_trd, PDO::PARAM_STR);
                    $insertStok->bindParam(':id_pro', $id_pro, PDO::PARAM_STR);
                    $insertStok->bindParam(':no_bcode', $no_bcode, PDO::PARAM_STR);
                    $insertStok->bindParam(':tgl_expired', $tgl_expired, PDO::PARAM_STR);
                    $insertStok->bindParam(':tgl_psd', $catat, PDO::PARAM_STR);
                    $insertStok->bindParam(':masuk_psd', $jumlah_transfer, PDO::PARAM_INT);
                    $insertStok->bindParam(':sisa_psd', $jumlah_transfer, PDO::PARAM_INT);
                    $insertStok->bindParam(':gudang', $gudang_asal, PDO::PARAM_STR);
                    $insertStok->bindParam(':created_at', $catat, PDO::PARAM_STR);
                    $insertStok->bindParam(':created_by', $admin, PDO::PARAM_STR);
                    $insertStok->bindParam(':updated_at', $catat, PDO::PARAM_STR);
                    $insertStok->bindParam(':updated_by', $admin, PDO::PARAM_STR);
                    $insertStok->execute();
                    
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
                
                // Update produk_stockdetail_cancel - set status transferred
                // Karena qty transfer bisa lebih besar dari qty cancel (user split batch dengan qty berbeda)
                // Maka langsung set status transferred untuk semua item yang sudah diproses
                $qUpdateCancel = "UPDATE produk_stockdetail_cancel 
                    SET status = 'transferred'
                    WHERE id_psc = :id_psc";
                $updateCancel = $conn->prepare($qUpdateCancel);
                $updateCancel->bindParam(':id_psc', $id_psc, PDO::PARAM_INT);
                $updateCancel->execute();
            }
            
            // Hitung total item dari transferDetails
            $total_item = count($transferDetails);
            
            // Insert ke transfer_stockcancel (header)
            // id_inventory_tujuan diisi "AUTO" karena setiap item kembali ke gudang masing-masing
            $id_inventory_tujuan_auto = "AUTO";
            
            $qInsertHeader = "INSERT INTO transfer_stockcancel 
                (id_tsc, kode_transfer, nomor_transfer, kode_faktur, tgl_faktur, id_inventory_tujuan, 
                 total_item, total_qty, keterangan_transfer, status_transfer, 
                 transfer_at, transfer_by, created_at, created_by)
                VALUES
                (:id_tsc, :kode_transfer, :nomor_transfer, :kode_faktur, :tgl_faktur, :id_inventory_tujuan,
                 :total_item, :total_qty, :keterangan_transfer, 'completed',
                 :transfer_at, :transfer_by, :created_at, :created_by)";
            
            $insertHeader = $conn->prepare($qInsertHeader);
            $insertHeader->bindParam(':id_tsc', $id_tsc, PDO::PARAM_STR);
            $insertHeader->bindParam(':kode_transfer', $kode_transfer, PDO::PARAM_STR);
            $insertHeader->bindParam(':nomor_transfer', $nomor_transfer, PDO::PARAM_STR);
            $insertHeader->bindParam(':kode_faktur', $kode_faktur, PDO::PARAM_STR);
            $insertHeader->bindParam(':tgl_faktur', $tgl_faktur, PDO::PARAM_STR);
            $insertHeader->bindParam(':id_inventory_tujuan', $id_inventory_tujuan_auto, PDO::PARAM_STR);
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
            $ketRiwayat = "Transfer batch stok cancel: $kode_transfer - $total_item item ($total_qty pcs) dari $kode_faktur kembali ke gudang asal masing-masing";
            $riwayat->bindParam(':ket', $ketRiwayat, PDO::PARAM_STR);
            $riwayat->bindParam(':catat', $catat, PDO::PARAM_STR);
            $riwayat->bindParam(':admin', $admin, PDO::PARAM_STR);
            $riwayat->execute();
            
            $conn->commit();
            
            echo "success";
            
        } catch(Exception $e){
            $conn->rollBack();
            echo $e->getMessage();
        }
        break;
        
    default:
        echo "Action tidak valid";
        break;
}
?>
