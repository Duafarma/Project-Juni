<?php
/**
 * Action Handler: Transfer dari transaksi_faktur ke produk_stokdetail
 * POST nact: do_transfer
 *
 * Alur:
 *  1. Untuk setiap item terpilih → buat entri baru di produk_stokdetail
 *  2. Kurangi jumlah_tfd di transaksi_fakturdetail
 *  3. Recalculate total_tfd dan update transaksi_fakturdetail
 *  4. Recalculate total_tfk dan update transaksi_faktur
 *  5. Simpan history ke transfer_faktur_stok dan transfer_faktur_stok_detail
 */
require_once('../../config/connection/connection.php');
require_once('../../config/connection/security.php');
require_once('../../config/function/data.php');

$secu  = new Security;
$base  = new DB;
$data  = new Data;
$conn  = $base->open();

$admin = $secu->injection(@$_COOKIE['adminkuy']);
$kunci = $secu->injection(@$_COOKIE['kuncikuy']);
$catat = date('Y-m-d H:i:s');

if($secu->validadmin($admin, $kunci) == false){
    echo "Session tidak valid, silakan login ulang";
    exit;
}

$act = $secu->injection($_POST['nact'] ?? '');

switch($act){
    case "do_transfer":
        try {
            $conn->beginTransaction();

            $id_tfk       = $secu->injection($_POST['id_tfk'] ?? '');
            $kode_faktur  = $secu->injection($_POST['kode_faktur'] ?? '');
            $tgl_faktur   = $secu->injection($_POST['tgl_faktur'] ?? '');
            $nama_outlet  = $secu->injection($_POST['nama_outlet'] ?? '');
            $nomor_transfer = $secu->injection($_POST['nomor_transfer'] ?? '');
            $keterangan   = $secu->injection($_POST['keterangan'] ?? '');
            $items        = $_POST['items'] ?? [];

            if(empty($id_tfk)){
                throw new Exception("ID Faktur tidak valid");
            }
            if(empty($nomor_transfer)){
                throw new Exception("Nomor transfer harus diisi");
            }
            if(empty($items)){
                throw new Exception("Tidak ada item yang dipilih");
            }

            // Auto-create tabel jika belum ada
            $conn->exec("CREATE TABLE IF NOT EXISTS `transfer_faktur_stok` (
                `id_tfs` int(11) NOT NULL AUTO_INCREMENT,
                `nomor_transfer` varchar(100) NOT NULL,
                `id_tfk` varchar(50) NOT NULL,
                `kode_faktur` varchar(100) DEFAULT NULL,
                `tgl_faktur` date DEFAULT NULL,
                `nama_outlet` varchar(200) DEFAULT NULL,
                `total_item` int(11) NOT NULL DEFAULT 0,
                `total_qty` int(11) NOT NULL DEFAULT 0,
                `keterangan` text DEFAULT NULL,
                `status` varchar(50) DEFAULT 'selesai',
                `created_at` datetime NOT NULL,
                `created_by` varchar(100) NOT NULL,
                `updated_at` datetime NOT NULL,
                `updated_by` varchar(100) NOT NULL,
                PRIMARY KEY (`id_tfs`)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1");

            $conn->exec("CREATE TABLE IF NOT EXISTS `transfer_faktur_stok_detail` (
                `id_tfsd` int(11) NOT NULL AUTO_INCREMENT,
                `id_tfs` int(11) NOT NULL,
                `id_tfd` int(11) NOT NULL,
                `id_tfk` varchar(50) NOT NULL,
                `id_pro` varchar(50) NOT NULL,
                `nama_pro` varchar(200) DEFAULT NULL,
                `id_psd_baru` varchar(50) DEFAULT NULL,
                `no_bcode` varchar(100) DEFAULT NULL,
                `tgl_expired` date DEFAULT NULL,
                `gudang` varchar(100) DEFAULT NULL,
                `jumlah_transfer` int(11) NOT NULL DEFAULT 0,
                `harga_tfd` decimal(15,2) DEFAULT 0,
                `diskon_tfd` varchar(10) DEFAULT '0',
                `selisih_total` decimal(15,2) DEFAULT 0,
                `created_at` datetime NOT NULL,
                `created_by` varchar(100) NOT NULL,
                PRIMARY KEY (`id_tfsd`)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1");

            // Filter item yang selected
            $selectedItems = [];
            foreach($items as $item){
                if(isset($item['selected']) && $item['selected'] == '1'){
                    $selectedItems[] = $item;
                }
            }

            if(empty($selectedItems)){
                throw new Exception("Pilih minimal 1 item untuk ditransfer");
            }

            $totalItem  = 0;
            $totalQty   = 0;
            $detailRows = [];

            foreach($selectedItems as $item){
                $id_tfd         = intval($item['id_tfd'] ?? 0);
                $id_pro         = $secu->injection($item['id_pro'] ?? '');
                $no_bcode       = $secu->injection($item['no_bcode'] ?? '');
                $tgl_exp        = $secu->injection($item['tgl_expired'] ?? '');
                $gudang         = $secu->injection($item['gudang'] ?? '');
                $jumlah_transfer = intval($item['jumlah_transfer'] ?? 0);

                if($id_tfd <= 0 || empty($id_pro)){
                    throw new Exception("Data item tidak valid (id_tfd=$id_tfd)");
                }
                if($jumlah_transfer <= 0){
                    continue; // Skip qty 0
                }
                if(empty($no_bcode)){
                    throw new Exception("Batch/Barcode wajib diisi untuk setiap item yang dipilih");
                }
                if(empty($tgl_exp)){
                    throw new Exception("Tanggal expired wajib diisi untuk setiap item yang dipilih");
                }
                if(empty($gudang)){
                    throw new Exception("Gudang wajib diisi untuk setiap item yang dipilih");
                }

                // Ambil data fakturdetail: jumlah_tfd, harga_tfd, diskon_tfd, nama_pro
                $qTfd = "SELECT A.id_tfd, A.jumlah_tfd, A.harga_tfd, A.diskon_tfd, A.total_tfd, B.nama_pro
                         FROM transaksi_fakturdetail AS A
                         LEFT JOIN produk AS B ON A.id_pro = B.id_pro
                         WHERE A.id_tfd = :id_tfd AND A.id_tfk = :id_tfk";
                $stmtTfd = $conn->prepare($qTfd);
                $stmtTfd->bindParam(':id_tfd', $id_tfd, PDO::PARAM_INT);
                $stmtTfd->bindParam(':id_tfk', $id_tfk, PDO::PARAM_STR);
                $stmtTfd->execute();
                $tfdData = $stmtTfd->fetch(PDO::FETCH_ASSOC);

                if(!$tfdData){
                    throw new Exception("Item id_tfd=$id_tfd tidak ditemukan di faktur ini");
                }

                $jumlah_tfd_asal = intval($tfdData['jumlah_tfd']);
                $harga_tfd       = floatval($tfdData['harga_tfd']);
                $diskon_tfd      = floatval($tfdData['diskon_tfd']);
                $nama_pro        = $tfdData['nama_pro'] ?? '';

                if($jumlah_transfer > $jumlah_tfd_asal){
                    throw new Exception("Qty transfer ($jumlah_transfer) melebihi jumlah di faktur ($jumlah_tfd_asal) untuk produk: $nama_pro");
                }

                // Hitung jumlah baru setelah transfer dikurangi
                $jumlah_tfd_baru = $jumlah_tfd_asal - $jumlah_transfer;
                $total_tfd_baru  = round($harga_tfd * $jumlah_tfd_baru * (1 - ($diskon_tfd / 100)));
                $selisih_total   = round($tfdData['total_tfd']) - $total_tfd_baru;

                // 1. Simpan ke produk_stockdetail_cancel (status: transferred)
                $ref_id_tfd = (string)$id_tfd;
                $tgl_psd    = date('Y-m-d');

                $qInsertCancel = "INSERT INTO produk_stockdetail_cancel
                    (id_psd, id_trd, id_pro, id_tfk, kode_faktur, tgl_faktur,
                     no_bcode, tgl_expired, tgl_psd, jumlah_cancel, gudang,
                     keterangan_cancel, dari_konsinyasi, id_tfk_konsinyasi, status,
                     cancel_at, cancel_by, created_at, created_by)
                    VALUES
                    (:id_psd, :id_trd, :id_pro, :id_tfk, :kode_faktur, :tgl_faktur,
                     :no_bcode, :tgl_expired, :tgl_psd, :jumlah_cancel, :gudang,
                     :keterangan_cancel, 'tidak', '', 'cancel',
                     :cancel_at, :cancel_by, :created_at, :created_by)";
                $emptyPsd = '';
                $insertCancel = $conn->prepare($qInsertCancel);
                $insertCancel->bindParam(':id_psd',            $emptyPsd,        PDO::PARAM_STR);
                $insertCancel->bindParam(':id_trd',            $ref_id_tfd,      PDO::PARAM_STR);
                $insertCancel->bindParam(':id_pro',            $id_pro,          PDO::PARAM_STR);
                $insertCancel->bindParam(':id_tfk',            $id_tfk,          PDO::PARAM_STR);
                $insertCancel->bindParam(':kode_faktur',       $kode_faktur,     PDO::PARAM_STR);
                $insertCancel->bindParam(':tgl_faktur',        $tgl_faktur,      PDO::PARAM_STR);
                $insertCancel->bindParam(':no_bcode',          $no_bcode,        PDO::PARAM_STR);
                $insertCancel->bindParam(':tgl_expired',       $tgl_exp,         PDO::PARAM_STR);
                $insertCancel->bindParam(':tgl_psd',           $tgl_psd,         PDO::PARAM_STR);
                $insertCancel->bindParam(':jumlah_cancel',     $jumlah_transfer, PDO::PARAM_INT);
                $insertCancel->bindParam(':gudang',            $gudang,          PDO::PARAM_STR);
                $insertCancel->bindParam(':keterangan_cancel', $keterangan,      PDO::PARAM_STR);
                $insertCancel->bindParam(':cancel_at',         $catat,           PDO::PARAM_STR);
                $insertCancel->bindParam(':cancel_by',         $admin,           PDO::PARAM_STR);
                $insertCancel->bindParam(':created_at',        $catat,           PDO::PARAM_STR);
                $insertCancel->bindParam(':created_by',        $admin,           PDO::PARAM_STR);
                $insertCancel->execute();
                $id_psd_baru = $conn->lastInsertId(); // id_psc dari produk_stockdetail_cancel

                // 2. Hapus atau update transaksi_fakturdetail
                if($jumlah_tfd_baru <= 0){
                    // Semua qty ditransfer → hapus baris
                    $qDelTfd = "DELETE FROM transaksi_fakturdetail WHERE id_tfd = :id_tfd";
                    $delTfd  = $conn->prepare($qDelTfd);
                    $delTfd->bindParam(':id_tfd', $id_tfd, PDO::PARAM_INT);
                    $delTfd->execute();
                } else {
                    // Sebagian qty ditransfer → update sisa
                    $qUpdateTfd = "UPDATE transaksi_fakturdetail
                                   SET jumlah_tfd  = :jumlah_baru,
                                       total_tfd   = :total_baru,
                                       updated_at  = :updated_at,
                                       updated_by  = :updated_by
                                   WHERE id_tfd = :id_tfd";
                    $updateTfd = $conn->prepare($qUpdateTfd);
                    $updateTfd->bindParam(':jumlah_baru', $jumlah_tfd_baru, PDO::PARAM_INT);
                    $updateTfd->bindParam(':total_baru',  $total_tfd_baru,  PDO::PARAM_STR);
                    $updateTfd->bindParam(':updated_at',  $catat,           PDO::PARAM_STR);
                    $updateTfd->bindParam(':updated_by',  $admin,           PDO::PARAM_STR);
                    $updateTfd->bindParam(':id_tfd',      $id_tfd,          PDO::PARAM_INT);
                    $updateTfd->execute();
                }

                $totalItem++;
                $totalQty += $jumlah_transfer;

                $detailRows[] = [
                    'id_tfd'          => $id_tfd,
                    'id_pro'          => $id_pro,
                    'nama_pro'        => $nama_pro,
                    'id_psd_baru'     => $id_psd_baru,
                    'no_bcode'        => $no_bcode,
                    'tgl_expired'     => $tgl_exp,
                    'gudang'          => $gudang,
                    'jumlah_transfer' => $jumlah_transfer,
                    'harga_tfd'       => $harga_tfd,
                    'diskon_tfd'      => $diskon_tfd,
                    'selisih_total'   => $selisih_total,
                ];
            }

            if($totalItem == 0){
                throw new Exception("Tidak ada item dengan qty transfer valid");
            }

            $qFakturNow = "SELECT subtot_tfk, ppn_tfk FROM transaksi_faktur WHERE id_tfk = :id_tfk";
            $stmtFakturNow = $conn->prepare($qFakturNow);
            $stmtFakturNow->bindParam(':id_tfk', $id_tfk, PDO::PARAM_STR);
            $stmtFakturNow->execute();
            $fakturNow = $stmtFakturNow->fetch(PDO::FETCH_ASSOC);
            $curSubtot = floatval($fakturNow['subtot_tfk'] ?? 0);
            $curPpn    = floatval($fakturNow['ppn_tfk'] ?? 0);
            $ppnRate   = ($curSubtot > 0) ? ($curPpn / $curSubtot) : 0;

            // Hitung subtot baru = SUM total_tfd
            $qSumTfd = "SELECT COALESCE(SUM(total_tfd), 0) AS new_subtot
                        FROM transaksi_fakturdetail
                        WHERE id_tfk = :id_tfk";
            $stmtSum = $conn->prepare($qSumTfd);
            $stmtSum->bindParam(':id_tfk', $id_tfk, PDO::PARAM_STR);
            $stmtSum->execute();
            $newSubtot = floatval($stmtSum->fetch(PDO::FETCH_ASSOC)['new_subtot']);

            $newPpn   = round($newSubtot * $ppnRate);
            $newTotal = $newSubtot + $newPpn;

            $qUpdateFaktur = "UPDATE transaksi_faktur
                              SET subtot_tfk = :subtot_tfk,
                                  ppn_tfk    = :ppn_tfk,
                                  total_tfk  = :total_tfk,
                                  status_tfk = 'Retur-item',
                                  updated_at = :updated_at,
                                  updated_by = :updated_by
                              WHERE id_tfk = :id_tfk";
            $updateFaktur = $conn->prepare($qUpdateFaktur);
            $updateFaktur->bindParam(':subtot_tfk', $newSubtot,  PDO::PARAM_STR);
            $updateFaktur->bindParam(':ppn_tfk',    $newPpn,     PDO::PARAM_STR);
            $updateFaktur->bindParam(':total_tfk',  $newTotal,   PDO::PARAM_STR);
            $updateFaktur->bindParam(':updated_at', $catat,      PDO::PARAM_STR);
            $updateFaktur->bindParam(':updated_by', $admin,      PDO::PARAM_STR);
            $updateFaktur->bindParam(':id_tfk',     $id_tfk,     PDO::PARAM_STR);
            $updateFaktur->execute();

            // 4. Insert header ke transfer_faktur_stok
            $qInsertHeader = "INSERT INTO transfer_faktur_stok
                (nomor_transfer, id_tfk, kode_faktur, tgl_faktur, nama_outlet,
                 total_item, total_qty, keterangan, status, created_at, created_by, updated_at, updated_by)
                VALUES
                (:nomor_transfer, :id_tfk, :kode_faktur, :tgl_faktur, :nama_outlet,
                 :total_item, :total_qty, :keterangan, 'selesai', :created_at, :created_by, :updated_at, :updated_by)";
            $insertHeader = $conn->prepare($qInsertHeader);
            $insertHeader->bindParam(':nomor_transfer', $nomor_transfer, PDO::PARAM_STR);
            $insertHeader->bindParam(':id_tfk',         $id_tfk,         PDO::PARAM_STR);
            $insertHeader->bindParam(':kode_faktur',    $kode_faktur,    PDO::PARAM_STR);
            $insertHeader->bindParam(':tgl_faktur',     $tgl_faktur,     PDO::PARAM_STR);
            $insertHeader->bindParam(':nama_outlet',    $nama_outlet,    PDO::PARAM_STR);
            $insertHeader->bindParam(':total_item',     $totalItem,      PDO::PARAM_INT);
            $insertHeader->bindParam(':total_qty',      $totalQty,       PDO::PARAM_INT);
            $insertHeader->bindParam(':keterangan',     $keterangan,     PDO::PARAM_STR);
            $insertHeader->bindParam(':created_at',     $catat,          PDO::PARAM_STR);
            $insertHeader->bindParam(':created_by',     $admin,          PDO::PARAM_STR);
            $insertHeader->bindParam(':updated_at',     $catat,          PDO::PARAM_STR);
            $insertHeader->bindParam(':updated_by',     $admin,          PDO::PARAM_STR);
            $insertHeader->execute();
            $id_tfs = $conn->lastInsertId();

            // 5. Insert detail ke transfer_faktur_stok_detail
            foreach($detailRows as $dr){
                $qInsertDetail = "INSERT INTO transfer_faktur_stok_detail
                    (id_tfs, id_tfd, id_tfk, id_pro, nama_pro, id_psd_baru, no_bcode, tgl_expired,
                     gudang, jumlah_transfer, harga_tfd, diskon_tfd, selisih_total, created_at, created_by)
                    VALUES
                    (:id_tfs, :id_tfd, :id_tfk, :id_pro, :nama_pro, :id_psd_baru, :no_bcode, :tgl_expired,
                     :gudang, :jumlah_transfer, :harga_tfd, :diskon_tfd, :selisih_total, :created_at, :created_by)";
                $insertDetail = $conn->prepare($qInsertDetail);
                $insertDetail->bindParam(':id_tfs',          $id_tfs,            PDO::PARAM_INT);
                $insertDetail->bindParam(':id_tfd',          $dr['id_tfd'],      PDO::PARAM_INT);
                $insertDetail->bindParam(':id_tfk',          $id_tfk,            PDO::PARAM_STR);
                $insertDetail->bindParam(':id_pro',          $dr['id_pro'],      PDO::PARAM_STR);
                $insertDetail->bindParam(':nama_pro',        $dr['nama_pro'],    PDO::PARAM_STR);
                $insertDetail->bindParam(':id_psd_baru',     $dr['id_psd_baru'], PDO::PARAM_STR);
                $insertDetail->bindParam(':no_bcode',        $dr['no_bcode'],    PDO::PARAM_STR);
                $insertDetail->bindParam(':tgl_expired',     $dr['tgl_expired'], PDO::PARAM_STR);
                $insertDetail->bindParam(':gudang',          $dr['gudang'],      PDO::PARAM_STR);
                $insertDetail->bindParam(':jumlah_transfer', $dr['jumlah_transfer'], PDO::PARAM_INT);
                $insertDetail->bindParam(':harga_tfd',       $dr['harga_tfd'],   PDO::PARAM_STR);
                $insertDetail->bindParam(':diskon_tfd',      $dr['diskon_tfd'],  PDO::PARAM_STR);
                $insertDetail->bindParam(':selisih_total',   $dr['selisih_total'], PDO::PARAM_STR);
                $insertDetail->bindParam(':created_at',      $catat,             PDO::PARAM_STR);
                $insertDetail->bindParam(':created_by',      $admin,             PDO::PARAM_STR);
                $insertDetail->execute();
            }

            // 6. Log riwayat (jika tabel riwayat tersedia)
            try {
                $qRiwayat  = "INSERT INTO riwayat VALUES('', :ref, 'transfer_faktur_stok', 'Create', :ket, :catat, :admin)";
                $riwayat   = $conn->prepare($qRiwayat);
                $ketRiwayat = "Transfer Faktur ke Stok: $nomor_transfer — $totalItem item ($totalQty pcs) dari faktur $kode_faktur. Total faktur diperbarui: Rp " . number_format($newTotal, 0, ',', '.');
                $riwayat->bindParam(':ref',   $id_tfk,      PDO::PARAM_STR);
                $riwayat->bindParam(':ket',   $ketRiwayat,  PDO::PARAM_STR);
                $riwayat->bindParam(':catat', $catat,       PDO::PARAM_STR);
                $riwayat->bindParam(':admin', $admin,       PDO::PARAM_STR);
                $riwayat->execute();
            } catch(Exception $eRiwayat){
                // Tabel riwayat mungkin tidak ada — abaikan
            }

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
