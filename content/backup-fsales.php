<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Penjualan</a></li>
                <li class="breadcrumb-item active" aria-current="page">Backup Faktur Penjualan</li>
            </ol>
        </nav>
        <h4 class="content-title">Data Backup Faktur Penjualan</h4>
    </div>
</div>
<?php 
$cari = $secu->injection(@$_GET['cari']); 

// Pagination setup
$halaman = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$limit = 20;
$start = ($halaman - 1) * $limit;

// Search functionality
$whereClause = 'WHERE tfj.status_tfk IN ("Tagihan","Delete")';
$params = array();

if (!empty($cari)) {
    $whereClause .= " AND (tfj.kode_tfk LIKE :cari OR tfj.sj_tfk LIKE :cari OR o.nama_out LIKE :cari OR a.nama_adm LIKE :cari OR tfj.keterangan_revisi LIKE :cari)";
    $params[':cari'] = '%' . $cari . '%';
}

try {
    $countQuery = "SELECT COUNT(*) as total 
                   FROM transaksi_faktur_junk tfj 
                   LEFT JOIN outlet o ON tfj.id_out = o.id_out 
                   LEFT JOIN adminz a ON tfj.backup_by = a.id_adm
                   $whereClause";
    $countStmt = $conn->prepare($countQuery);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalData = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalHalaman = ceil($totalData / $limit);

    $query = "SELECT tfj.*, o.nama_out, a.nama_adm,
                     (SELECT COUNT(*) FROM transaksi_fakturdetail_junk tfdj WHERE tfdj.id_tfk_junk = tfj.id_tfk_junk) as total_items
              FROM transaksi_faktur_junk tfj 
              LEFT JOIN outlet o ON tfj.id_out = o.id_out 
              LEFT JOIN adminz a ON tfj.backup_by = a.id_adm
              $whereClause 
              ORDER BY tfj.backup_at DESC 
              LIMIT $start, $limit";
    
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $hasil = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
    $hasil = array();
    $totalData = 0;
    $totalHalaman = 0;
}
?>
<input type="hidden" name="caridata" id="caridata" value="<?php echo($cari); ?>" readonly="readonly" />
<input type="hidden" name="halaman" id="halaman" value="<?php echo($halaman); ?>" readonly="readonly" />
<input type="hidden" name="maximal" id="maximal" value="20" readonly="readonly" />
<div class="content-body">
    <div class="row mg-b-10">
        <div class="col-sm-6">
            <a href="#modal1" onclick="<?php echo("caridata('caribackupfsales', 'backup-fsales', '$cari')"); ?>" data-toggle="modal">
                <button class="btn btn-warning btn-pill btn-xs"><i class="fa fa-search"></i> Cari Data</button>
            </a>
            <a href="<?php echo($data->sistem('url_sis').'/backup-fsales'); ?>">
                <button class="btn btn-info btn-pill btn-xs"><i class="fa fa-spinner"></i> Refresh</button>
            </a>
            <a target="_blank" href="<?php echo($data->sistem('url_sis').'/laporan/xls/backup-fsales/backup-fsales.php?key='.$cari); ?>">
                <button class="btn btn-success btn-pill btn-xs"><i class="fa fa-file-excel-o"></i> Download Excel</button>
            </a>
        </div>
        <div class="col-sm-6">
            <span class="badge badge-pill badge-danger"><i class="fa fa-search"></i> Search : <?php echo($cari); ?></span>
        </div>
    </div>
    <?php require_once('config/frame/alert.php'); ?>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?= $error_message ?></div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-hover mg-b-0">
            <thead>
                <tr>
                    <th><center>#</center></th>
                    <th>No. Faktur</th>
                    <th>Status</th>
                    <th>Backup Reason</th>
                    <th>Outlet</th>
                    <th><center>Tgl. Faktur</center></th>
                    <th><div align="right"> Total</div></th>
                    <th>Backup By</th>
                    <th><center>Backup At</center></th>
                    <th>Ket. Revisi</th>
                    <th><center>Action</center></th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($hasil) > 0): ?>
                    <?php foreach ($hasil as $index => $row): ?>
                        <tr>
                            <td><center><?= $start + $index + 1 ?></center></td>
                            <td>
                                <strong class="text-primary"><?= htmlspecialchars($row['kode_tfk']) ?></strong>
                            </td>
                            <td>
                                <?php
                                    $status = $row['status_tfk'];
                                    $badgeClass = 'secondary';
                                    if ($status === 'Tagihan') $badgeClass = 'primary';
                                    elseif ($status === 'Revisi') $badgeClass = 'warning';
                                ?>
                                <span class="badge badge-<?= $badgeClass ?>"><?= htmlspecialchars($status) ?></span>
                            </td>
                            <td>
                                <?php
                                    $reason = $row['backup_reason'];
                                    $reasonBadge = 'secondary';
                                    if ($reason === 'Before Edit Item') $reasonBadge = 'info';
                                    elseif ($reason === 'Delete Faktur Penjualan') $reasonBadge = 'danger';
                                ?>
                                <span class="badge badge-<?= $reasonBadge ?>" title="<?= htmlspecialchars($reason) ?>">
                                    <?= htmlspecialchars($reason) ?>
                                </span>
                            </td>
                            <td>
                                <div class="text-truncate" style="max-width: 200px;" 
                                     title="<?= htmlspecialchars($row['nama_out']) ?>">
                                    <?= htmlspecialchars($row['nama_out']) ?>
                                </div>
                            </td>
                            <td><center><?= date('d/m/Y', strtotime($row['tgl_tfk'])) ?></center></td>
                            <td><div align="right">
                                <strong>Rp <?= number_format($row['total_tfk'], 0, ',', '.') ?></strong>
                            </div></td>
                            <td>
                                <div class="text-truncate" style="max-width: 120px;" 
                                     title="<?= htmlspecialchars($row['nama_adm']) ?>">
                                    <?= htmlspecialchars($row['nama_adm']) ?>
                                </div>
                            </td>
                            <td><center>
                                <small><?= date('d/m/Y H:i', strtotime($row['backup_at'])) ?></small>
                            </center></td>
                            <td>
                                <div class="text-truncate" style="max-width: 150px;" 
                                     title="<?= htmlspecialchars($row['keterangan_revisi']) ?>">
                                    <?= htmlspecialchars($row['keterangan_revisi']) ?>
                                </div>
                            </td>
                            <td><center>
                                <button type="button" class="btn btn-sm btn-info" 
                                        onclick="viewBackupDetail('<?= $row['id_tfk_junk'] ?>', '<?= htmlspecialchars($row['kode_tfk']) ?>')">
                                    <i class="fa fa-eye"></i>
                                </button>
                                <a target="_blank" href="<?= $data->sistem('url_sis') ?>/laporan/xps/backup-fsales/backup-fsales.php?key=<?= $row['id_tfk_junk'] ?>" 
                                   class="btn btn-sm btn-success">
                                    <i class="fa fa-print"></i>
                                </a>
                            </center></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="14" class="text-center py-4">
                            <div class="text-muted">
                                <i class="fa fa-inbox fa-2x mb-3"></i>
                                <br>Tidak ada data backup (Tagihan / Delete / Revisi) ditemukan
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalHalaman > 1): ?>
        <div class="mg-t-20">
            <nav aria-label="Pagination">
                <ul class="pagination justify-content-center">
                    <!-- Previous -->
                    <?php if ($halaman > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?menu=backup-fsales&halaman=<?= $halaman - 1 ?><?= !empty($cari) ? '&cari=' . urlencode($cari) : '' ?>">
                                <i class="fa fa-chevron-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- Page Numbers -->
                    <?php
                    $start_page = max(1, $halaman - 2);
                    $end_page = min($totalHalaman, $halaman + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <li class="page-item <?= $i == $halaman ? 'active' : '' ?>">
                            <a class="page-link" href="?menu=backup-fsales&halaman=<?= $i ?><?= !empty($cari) ? '&cari=' . urlencode($cari) : '' ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <!-- Next -->
                    <?php if ($halaman < $totalHalaman): ?>
                        <li class="page-item">
                            <a class="page-link" href="?menu=backup-fsales&halaman=<?= $halaman + 1 ?><?= !empty($cari) ? '&cari=' . urlencode($cari) : '' ?>">
                                <i class="fa fa-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
            
            <div class="text-center text-muted">
                Halaman <?= $halaman ?> dari <?= $totalHalaman ?> 
                (Total: <?= number_format($totalData) ?> data)
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Detail Backup -->
<div class="modal fade" id="modalBackupDetail" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Backup Faktur</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="modalBackupContent">
                <div class="text-center py-4">
                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                    <br>Loading...
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function viewBackupDetail(junkId, fakturCode) {
    $('#modalBackupDetail').modal('show');
    
    // Load detail via AJAX
    $.ajax({
        url: '<?php echo($sistem); ?>/ajax/backup-detail/',
        type: 'POST',
        data: {
            junk_id: junkId,
            faktur_code: fakturCode
        },
        success: function(response) {
            $('#modalBackupContent').html(response);
        },
        error: function() {
            $('#modalBackupContent').html(
                '<div class="alert alert-danger">' +
                '<i class="fa fa-exclamation-triangle"></i> ' +
                'Error loading backup detail' +
                '</div>'
            );
        }
    });
}
</script>
