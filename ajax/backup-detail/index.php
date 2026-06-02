<?php
session_start();
require_once('../../config/connection/connection.php');
require_once('../../config/connection/security.php');
require_once('../../config/function/data.php');

$secu = new Security;
$base = new DB;
$data = new Data;
$conn = $base->open();

// Check admin session using cookie like other files
$admin = $secu->injection(@$_COOKIE['adminkuy']);
$kunci = $secu->injection(@$_COOKIE['kuncikuy']);
$valid = $secu->validadmin($admin, $kunci);

if ($valid == false) {
    echo '<div class="alert alert-danger">Unauthorized access</div>';
    exit();
}

$junkId = isset($_POST['junk_id']) ? (int)$_POST['junk_id'] : 0;
$fakturCode = isset($_POST['faktur_code']) ? $_POST['faktur_code'] : '';

if ($junkId <= 0) {
    echo '<div class="alert alert-danger">Invalid backup ID</div>';
    exit();
}

try {
    // Get backup faktur header
    $fakturQuery = "SELECT tfj.*, o.nama_out,  a.nama_adm
                    FROM transaksi_faktur_junk tfj 
                    LEFT JOIN outlet o ON tfj.id_out = o.id_out 
                    LEFT JOIN adminz a ON tfj.backup_by = a.id_adm
                    WHERE tfj.id_tfk_junk = :junk_id";
    $fakturStmt = $conn->prepare($fakturQuery);
    $fakturStmt->bindParam(':junk_id', $junkId, PDO::PARAM_INT);
    $fakturStmt->execute();
    $faktur = $fakturStmt->fetch(PDO::FETCH_ASSOC);

    if (!$faktur) {
        echo '<div class="alert alert-danger">Backup data not found</div>';
        exit();
    }

    // Get backup detail items
    $detailQuery = "SELECT tfdj.*, p.nama_pro, 
                           psd.tgl_expired, psd.sisa_psd
                    FROM transaksi_fakturdetail_junk tfdj 
                    LEFT JOIN produk p ON tfdj.id_pro = p.id_pro 
                    LEFT JOIN produk_stokdetail psd ON tfdj.id_psd = psd.id_psd 
                    WHERE tfdj.id_tfk_junk = :junk_id 
                    ORDER BY tfdj.id_tfd_junk";
    $detailStmt = $conn->prepare($detailQuery);
    $detailStmt->bindParam(':junk_id', $junkId, PDO::PARAM_INT);
    $detailStmt->execute();
    $details = $detailStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Database error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    exit();
}
?>

<div class="container-fluid">
    <!-- Header Information -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fa fa-file-text"></i> Informasi Faktur</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm">
                        <tr>
                            <td width="40%"><strong>No. Faktur:</strong></td>
                            <td><?= htmlspecialchars($faktur['kode_tfk']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>No. SJ:</strong></td>
                            <td><?= htmlspecialchars($faktur['sj_tfk']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Tanggal Faktur:</strong></td>
                            <td><?= date('d/m/Y', strtotime($faktur['tgl_tfk'])) ?></td>
                        </tr>
                        <tr>
                            <td><strong>PO Number:</strong></td>
                            <td><?= htmlspecialchars($faktur['po_tfk']) ?></td>
                        </tr>
                        <?php if (!empty($faktur['tglpo_tfk'])): ?>
                        <tr>
                            <td><strong>Tgl PO:</strong></td>
                            <td><?= date('d/m/Y', strtotime($faktur['tglpo_tfk'])) ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fa fa-building"></i> Informasi Outlet</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm">
                        <tr>
                            <td width="40%"><strong>Nama Outlet:</strong></td>
                            <td><?= htmlspecialchars($faktur['nama_out']) ?></td>
                        </tr>
                       
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Backup Information -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0"><i class="fa fa-archive"></i> Informasi Backup</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Backup By:</strong><br>
                            <?= htmlspecialchars($faktur['nama_adm']) ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Backup Date:</strong><br>
                            <?= date('d/m/Y H:i:s', strtotime($faktur['backup_at'])) ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Backup Reason:</strong><br>
                            <?= htmlspecialchars($faktur['backup_reason']) ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Status:</strong><br>
                            <span class="badge badge-secondary"><?= htmlspecialchars($faktur['status_tfk']) ?></span>
                        </div>
                    </div>
                    <?php if (!empty($faktur['keterangan_revisi'])): ?>
                    <hr>
                    <div>
                        <strong>Keterangan Revisi:</strong><br>
                        <em><?= htmlspecialchars($faktur['keterangan_revisi']) ?></em>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Items -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="fa fa-list"></i> Detail Items Backup (<?= count($details) ?> items)</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th width="50">#</th>
                                    <th>Produk</th>
                                    <th>Expired</th>
                                    <th width="80">Qty</th>
                                    <th width="120">Harga</th>
                                    <th width="80">Diskon (%)</th>
                                    <th width="120">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($details) > 0): ?>
                                    <?php 
                                    $subtotal = 0;
                                    foreach ($details as $index => $item): 
                                        $subtotal += $item['total_tfd'];
                                    ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td>
                                                <div class="font-weight-bold text-primary">
                                                    <?= htmlspecialchars($item['nama_pro']) ?>
                                                </div>
                                                <small class="text-muted">ID: <?= htmlspecialchars($item['id_pro']) ?></small>
                                            </td>
                                          
                                            <td>
                                                <?php if (!empty($item['tgl_expired'])): ?>
                                                    <small><?= date('d/m/Y', strtotime($item['tgl_expired'])) ?></small>
                                                <?php else: ?>
                                                    <small class="text-muted">-</small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-primary"><?= number_format($item['jumlah_tfd']) ?></span>
                                            </td>
                                            <td class="text-right">
                                                Rp <?= number_format($item['harga_tfd'], 0, ',', '.') ?>
                                            </td>
                                            <td class="text-center">
                                                <?= number_format($item['diskon_tfd'], 2) ?>%
                                            </td>
                                            <td class="text-right font-weight-bold">
                                                Rp <?= number_format($item['total_tfd'], 0, ',', '.') ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">
                                            No items found
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary -->
    <div class="row mt-3">
        <div class="col-md-8"></div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h6 class="mb-0"><i class="fa fa-calculator"></i> Total</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm mb-0">
                        <tr>
                            <td><strong>Subtotal:</strong></td>
                            <td class="text-right">Rp <?= number_format($faktur['subtot_tfk'], 0, ',', '.') ?></td>
                        </tr>
                        <tr>
                            <td><strong>PPN:</strong></td>
                            <td class="text-right">Rp <?= number_format($faktur['ppn_tfk'], 0, ',', '.') ?></td>
                        </tr>
                        <tr class="border-top">
                            <td><strong>Grand Total:</strong></td>
                            <td class="text-right"><strong>Rp <?= number_format($faktur['total_tfk'], 0, ',', '.') ?></strong></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $conn = $base->close(); ?>
