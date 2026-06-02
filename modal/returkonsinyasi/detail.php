<?php
require_once "../../config/connection.php";

if (isset($_POST['id_trk'])) {
    $id_trk = $secu->injection($_POST['id_trk']);
    
    // Get header
    $qHeader = "SELECT 
                    trk.*,
                    tfk.kode_tfk,
                    outl.nama_out,
                    outl.kode_out
                FROM transaksi_retur_konsinyasi trk
                LEFT JOIN transaksi_faktur_konsinyasi tfk ON trk.id_tfk = tfk.id_tfk
                LEFT JOIN outlet outl ON trk.id_out = outl.id_out
                WHERE trk.id_trk = :id_trk";
    
    $header = $conn->prepare($qHeader);
    $header->bindParam(':id_trk', $id_trk, PDO::PARAM_STR);
    $header->execute();
    $data = $header->fetch(PDO::FETCH_ASSOC);
    
    if (!$data) {
        echo '<div class="modal-body text-center text-danger">Data tidak ditemukan</div>';
        exit;
    }
    
    // Get detail items
    $qDetail = "SELECT 
                    trkd.*,
                    pro.nama_pro,
                    pro.kode_pro
                FROM transaksi_retur_konsinyasi_detail trkd
                LEFT JOIN produk pro ON trkd.id_pro = pro.id_pro
                WHERE trkd.id_trk = :id_trk
                ORDER BY trkd.id_trkd";
    
    $detail = $conn->prepare($qDetail);
    $detail->bindParam(':id_trk', $id_trk, PDO::PARAM_STR);
    $detail->execute();
?>

<div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
    <h5 class="modal-title"><i class="fa fa-undo-alt"></i> Detail Retur Konsinyasi</h5>
    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>

<div class="modal-body">
    <!-- Header Info -->
    <div class="row mg-b-25">
        <div class="col-md-6">
            <table class="table table-borderless table-sm">
                <tr>
                    <td width="40%"><strong>No. Retur</strong></td>
                    <td width="5%">:</td>
                    <td><?php echo $data['no_retur']; ?></td>
                </tr>
                <tr>
                    <td><strong>Tanggal Retur</strong></td>
                    <td>:</td>
                    <td><?php echo date('d F Y', strtotime($data['tgl_retur'])); ?></td>
                </tr>
                <tr>
                    <td><strong>Status</strong></td>
                    <td>:</td>
                    <td><span class="badge badge-success"><?php echo $data['status_trk']; ?></span></td>
                </tr>
            </table>
        </div>
        <div class="col-md-6">
            <table class="table table-borderless table-sm">
                <tr>
                    <td width="40%"><strong>No. Faktur</strong></td>
                    <td width="5%">:</td>
                    <td><?php echo $data['kode_tfk']; ?></td>
                </tr>
                <tr>
                    <td><strong>Outlet</strong></td>
                    <td>:</td>
                    <td><?php echo $data['kode_out']; ?> - <?php echo $data['nama_out']; ?></td>
                </tr>
                <tr>
                    <td><strong>Keterangan</strong></td>
                    <td>:</td>
                    <td><?php echo !empty($data['keterangan']) ? $data['keterangan'] : '-'; ?></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mg-b-20">
        <div class="col-md-6">
            <div class="card" style="border-left: 4px solid #667eea;">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Total Item</h6>
                    <h3 class="card-title" style="color: #667eea;"><?php echo $data['total_item']; ?> Item</h3>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card" style="border-left: 4px solid #764ba2;">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Total Quantity</h6>
                    <h3 class="card-title" style="color: #764ba2;"><?php echo number_format($data['total_qty'], 0, ',', '.'); ?> Pcs</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Items -->
    <h6 class="mg-b-15"><strong>Detail Barang Diretur:</strong></h6>
    <div class="table-responsive">
        <table class="table table-bordered table-sm">
            <thead style="background-color: #f8f9fa;">
                <tr>
                    <th width="5%" class="text-center">No</th>
                    <th width="15%">Kode Produk</th>
                    <th width="35%">Nama Produk</th>
                    <th width="15%" class="text-center">Qty Retur</th>
                    <th width="15%" class="text-center">Sisa Sebelum</th>
                    <th width="15%" class="text-center">Sisa Sesudah</th>
                </tr>
            </thead>
            <tbody>
            <?php
            if ($detail->rowCount() > 0) {
                $no = 1;
                while ($item = $detail->fetch(PDO::FETCH_ASSOC)) {
            ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <td><?php echo $item['kode_pro']; ?></td>
                    <td><?php echo $item['nama_pro']; ?></td>
                    <td class="text-center">
                        <span class="badge badge-primary"><?php echo number_format($item['qty_retur'], 0, ',', '.'); ?></span>
                    </td>
                    <td class="text-center"><?php echo number_format($item['qty_sisa_sebelum'], 0, ',', '.'); ?></td>
                    <td class="text-center">
                        <strong><?php echo number_format($item['qty_sisa_sesudah'], 0, ',', '.'); ?></strong>
                    </td>
                </tr>
            <?php
                }
            } else {
            ?>
                <tr>
                    <td colspan="6" class="text-center">Tidak ada detail item</td>
                </tr>
            <?php
            }
            ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
    <button type="button" class="btn btn-primary" onclick="printRetur('<?php echo $id_trk; ?>')">
        <i class="fa fa-print"></i> Cetak
    </button>
</div>

<script>
function printRetur(id_trk) {
    window.open('<?php echo $sistem; ?>/laporan/retur_konsinyasi.php?id=' + id_trk, '_blank');
}
</script>

<?php
} else {
    echo '<div class="modal-body text-center text-danger">Parameter tidak valid</div>';
}
?>
