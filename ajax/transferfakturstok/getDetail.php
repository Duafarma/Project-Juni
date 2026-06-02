<?php
/**
 * AJAX: Get Detail transfer_faktur_stok untuk modal view
 * GET: id_tfs
 */
session_start();
require_once('../../config/connection/connection.php');
require_once('../../config/connection/security.php');
require_once('../../config/function/data.php');

$secu = new Security;
$base = new DB;
$data = new Data;
$conn = $base->open();

$admin = $secu->injection(@$_COOKIE['adminkuy']);
$kunci = $secu->injection(@$_COOKIE['kuncikuy']);
if($secu->validadmin($admin, $kunci) == false){
    echo '<div class="modal-body"><div class="alert alert-danger">Session tidak valid</div></div>';
    exit;
}

$id_tfs = intval(@$_GET['id_tfs']);
if($id_tfs <= 0){
    echo '<div class="modal-body"><div class="alert alert-danger">ID tidak valid</div></div>';
    exit;
}

// Header
$qH = "SELECT * FROM transfer_faktur_stok WHERE id_tfs = :id_tfs";
$stmtH = $conn->prepare($qH);
$stmtH->bindParam(':id_tfs', $id_tfs, PDO::PARAM_INT);
$stmtH->execute();
$header = $stmtH->fetch(PDO::FETCH_ASSOC);

if(!$header){
    echo '<div class="modal-body"><div class="alert alert-warning">Data tidak ditemukan</div></div>';
    exit;
}

// Detail
$qD = "SELECT * FROM transfer_faktur_stok_detail WHERE id_tfs = :id_tfs ORDER BY id_tfsd ASC";
$stmtD = $conn->prepare($qD);
$stmtD->bindParam(':id_tfs', $id_tfs, PDO::PARAM_INT);
$stmtD->execute();
$details = $stmtD->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="modal-header bg-success text-white">
    <h5 class="modal-title"><i class="fa fa-exchange-alt"></i> Detail Transfer: <?php echo htmlspecialchars($header['nomor_transfer']); ?></h5>
    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
</div>
<div class="modal-body">
    <!-- Info Header -->
    <div class="row mb-3">
        <div class="col-md-4">
            <small class="text-muted">Nomor Transfer</small>
            <div><strong><?php echo htmlspecialchars($header['nomor_transfer']); ?></strong></div>
        </div>
        <div class="col-md-4">
            <small class="text-muted">Faktur Asal</small>
            <div><?php echo htmlspecialchars($header['kode_faktur'] ?? $header['id_tfk']); ?></div>
        </div>
        <div class="col-md-4">
            <small class="text-muted">Outlet</small>
            <div><?php echo htmlspecialchars($header['nama_outlet'] ?? '-'); ?></div>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-3">
            <small class="text-muted">Tgl. Faktur</small>
            <div><?php echo !empty($header['tgl_faktur']) ? date('d-m-Y', strtotime($header['tgl_faktur'])) : '-'; ?></div>
        </div>
        <div class="col-md-3">
            <small class="text-muted">Tgl. Transfer</small>
            <div><?php echo date('d-m-Y H:i', strtotime($header['created_at'])); ?></div>
        </div>
        <div class="col-md-3">
            <small class="text-muted">By</small>
            <div><?php echo htmlspecialchars($header['created_by']); ?></div>
        </div>
        <div class="col-md-3">
            <small class="text-muted">Keterangan</small>
            <div><?php echo htmlspecialchars($header['keterangan'] ?? '-'); ?></div>
        </div>
    </div>

    <!-- Detail Items -->
    <h6 class="border-bottom pb-1"><i class="fa fa-boxes"></i> Item yang Ditransfer</h6>
    <div class="table-responsive">
        <table class="table table-sm table-bordered table-striped">
            <thead class="bg-light">
                <tr>
                    <th>#</th>
                    <th>Produk</th>
                    <th>Batch / Barcode</th>
                    <th><center>Tgl. Expired</center></th>
                    <th><center>Gudang</center></th>
                    <th><center>Qty Transfer</center></th>
                    <th class="text-right">Harga Satuan</th>
                    <th><center>Diskon</center></th>
                    <th class="text-right">Pengurang Nominal</th>
                    <th>ID Stok Baru</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($details)): ?>
                <tr><td colspan="10" class="text-center text-muted">Tidak ada detail</td></tr>
                <?php else: ?>
                <?php $no = 0; foreach($details as $d): $no++; ?>
                <tr>
                    <td><?php echo $no; ?></td>
                    <td><?php echo htmlspecialchars($d['nama_pro'] ?? $d['id_pro']); ?></td>
                    <td><?php echo htmlspecialchars($d['no_bcode'] ?? '-'); ?></td>
                    <td><center><?php echo !empty($d['tgl_expired']) ? date('d-m-Y', strtotime($d['tgl_expired'])) : '-'; ?></center></td>
                    <td><?php echo htmlspecialchars($d['gudang'] ?? '-'); ?></td>
                    <td><center><span class="badge badge-primary"><?php echo number_format($d['jumlah_transfer']); ?></span></center></td>
                    <td class="text-right">Rp <?php echo number_format($d['harga_tfd'], 0, ',', '.'); ?></td>
                    <td><center><?php echo $d['diskon_tfd']; ?>%</center></td>
                    <td class="text-right text-danger">Rp <?php echo number_format($d['selisih_total'], 0, ',', '.'); ?></td>
                    <td><small class="text-muted"><?php echo htmlspecialchars($d['id_psd_baru'] ?? '-'); ?></small></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot class="bg-light">
                <tr>
                    <th colspan="5" class="text-right">Total:</th>
                    <th><center><?php echo number_format($header['total_qty']); ?> pcs</center></th>
                    <th colspan="2"></th>
                    <th class="text-right text-danger">
                        Rp <?php echo number_format(array_sum(array_column($details, 'selisih_total')), 0, ',', '.'); ?>
                    </th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
</div>
