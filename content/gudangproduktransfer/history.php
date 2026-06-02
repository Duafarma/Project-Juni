<?php
// filepath: c:\Development\laragon\www\192.268.908.09\content\gudangproduktransfer\history.php
$user = $secu->injection(@$_COOKIE['idUser']);
$view = $data->ceknav($user, 'gudangproduktransfer');
$id_ttg = $secu->injection(@$_GET['id']);

// Ambil data transfer
$transfer_query = $conn->prepare("
    SELECT 
        t.id_ttg, 
        t.kode_ttg, 
        t.tgl_ttg, 
        t.id_inventory, 
        t.id_inventory_tujuan,
        t.status_ttg,
        t.ket_ttg,
        t.created_at,
        t.created_by,
        t.updated_at,
        t.updated_by,
        mi_asal.nama_inventory as gudang_asal_nama,
        mi_tujuan.nama_inventory as gudang_tujuan_nama,
        a_created.nama_adm as created_by_name,
        a_updated.nama_adm as updated_by_name
    FROM transfer_gudang t 
    LEFT JOIN master_inventory mi_asal ON t.id_inventory = mi_asal.id_inventory
    LEFT JOIN master_inventory mi_tujuan ON t.id_inventory_tujuan = mi_tujuan.id_inventory
    LEFT JOIN adminz a_created ON t.created_by = a_created.id_adm
    LEFT JOIN adminz a_updated ON t.updated_by = a_updated.id_adm
    WHERE t.id_ttg = :id
");
$transfer_query->bindParam(':id', $id_ttg);
$transfer_query->execute();
$transfer = $transfer_query->fetch(PDO::FETCH_ASSOC);

if (!$transfer) {
    echo "<div class='alert alert-danger'>Transfer tidak ditemukan</div>";
    exit;
}

// Ambil history log transfer dari tabel transfer_gudang_log
$log_query = $conn->prepare("
    SELECT 
        l.*,
        a.nama_adm as created_by_name
    FROM transfer_gudang_log l
    LEFT JOIN adminz a ON l.created_by = a.id_adm
    WHERE l.id_ttg = :id
    ORDER BY l.created_at DESC
");
$log_query->bindParam(':id', $id_ttg);
$log_query->execute();
$logs = $log_query->fetchAll(PDO::FETCH_ASSOC);

// Ambil detail produk transfer
$detail_query = $conn->prepare("
    SELECT 
        td.*,
        p.nama_pro,
        p.kode_pro
    FROM transfer_gudangdetail td
    LEFT JOIN produk p ON td.id_pro = p.id_pro
    WHERE td.id_ttg = :id
");
$detail_query->bindParam(':id', $id_ttg);
$detail_query->execute();
$details = $detail_query->fetchAll(PDO::FETCH_ASSOC);

// Helper function untuk warna status
function getStatusColor($status)
{
    switch ($status) {
        case 'Draft':
            return 'secondary';
        case 'Process':
            return 'primary';
        case 'Delivered':
            return 'info';
        case 'Completed':
            return 'success';
        case 'Canceled':
            return 'danger';
        default:
            return 'secondary';
    }
}

// Helper function untuk ikon status
function getStatusIcon($status)
{
    switch ($status) {
        case 'Draft':
            return 'file-alt';
        case 'Process':
            return 'sync';
        case 'Delivered':
            return 'truck';
        case 'Completed':
            return 'check';
        case 'Canceled':
            return 'times';
        default:
            return 'circle';
    }
}
?>

<!-- Content Header -->
<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= $sistem; ?>">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Inventory</a></li>
                <li class="breadcrumb-item"><a href="<?php echo $sistem; ?>/gudangproduktransfer">Transfer Gudang</a></li>
                <li class="breadcrumb-item active" aria-current="page">History Transfer</li>
            </ol>
        </nav>
        <h4 class="content-title">History Transfer Gudang - <?php echo $transfer['kode_ttg']; ?></h4>
    </div>
</div>

<div class="content-body">
    <!-- Detail Transfer -->
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h6 class="card-title mb-0">Detail Transfer</h6>
                    <span class="badge badge-<?php echo getStatusColor($transfer['status_ttg']); ?>"><?php echo $transfer['status_ttg']; ?></span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td width="40%">Kode Transfer</td>
                                    <td width="2%">:</td>
                                    <td><strong><?php echo $transfer['kode_ttg']; ?></strong></td>
                                </tr>
                                <tr>
                                    <td>Tanggal</td>
                                    <td>:</td>
                                    <td><?php echo date('d/m/Y', strtotime($transfer['tgl_ttg'])); ?></td>
                                </tr>
                                <tr>
                                    <td>Gudang Asal</td>
                                    <td>:</td>
                                    <td><?php echo $transfer['gudang_asal_nama']; ?></td>
                                </tr>
                                <tr>
                                    <td>Gudang Tujuan</td>
                                    <td>:</td>
                                    <td><?php echo $transfer['gudang_tujuan_nama']; ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td width="40%">Dibuat Oleh</td>
                                    <td width="2%">:</td>
                                    <td><?php echo $transfer['created_by_name']; ?></td>
                                </tr>
                                <tr>
                                    <td>Tanggal Dibuat</td>
                                    <td>:</td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($transfer['created_at'])); ?></td>
                                </tr>
                                <tr>
                                    <td>Terakhir Update</td>
                                    <td>:</td>
                                    <td>
                                        <?php
                                        echo !empty($transfer['updated_at'])
                                            ? date('d/m/Y H:i', strtotime($transfer['updated_at'])) . ' oleh ' . $transfer['updated_by_name']
                                            : '-';
                                        ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <?php if (!empty($transfer['ket_ttg'])): ?>
                        <div class="mt-3">
                            <strong>Catatan:</strong>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($transfer['ket_ttg'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Produk Detail -->
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">Daftar Produk</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered mg-b-0">
                            <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th>Kode</th>
                                    <th>Nama Produk</th>
                                    <th>Batch</th>
                                    <th class="text-right">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($details) > 0): ?>
                                    <?php
                                    $total_qty = 0;
                                    foreach ($details as $i => $detail):
                                        $total_qty += $detail['jumlah_ttd'];
                                    ?>
                                        <tr>
                                            <td class="text-center"><?php echo ($i + 1); ?></td>
                                            <td><?php echo $detail['kode_pro']; ?></td>
                                            <td><?php echo $detail['nama_pro']; ?></td>
                                            <td><?php echo $detail['no_batch']; ?></td>
                                            <td class="text-right"><?php echo number_format($detail['jumlah_ttd']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>

                                    <!-- Total -->
                                    <tr>
                                        <td colspan="4" class="text-right"><strong>Total</strong></td>
                                        <td class="text-right"><strong><?php echo number_format($total_qty); ?></strong></td>
                                    </tr>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Tidak ada data produk</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- History Status with Timeline View -->
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">History Status Transfer</h6>
                </div>
                <div class="card-body">
                    <?php if (count($logs) > 0): ?>
                        <div class="timeline">
                            <?php foreach ($logs as $log): ?>
                                <div class="timeline-item">
                                    <div class="timeline-badge bg-<?= getStatusColor($log['status_baru']) ?>">
                                        <i class="fas fa-<?= getStatusIcon($log['status_baru']) ?>"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <div class="timeline-date mb-2">
                                            <i class="far fa-clock mr-1"></i>
                                            <?= date('d M Y H:i:s', strtotime($log['created_at'])) ?>
                                            <span class="ml-2 text-muted">oleh <?= $log['created_by_name'] ?></span>
                                        </div>
                                        <p class="mb-2">
                                            <span class="badge badge-<?= getStatusColor($log['status_lama']) ?>"><?= $log['status_lama'] ?: 'New' ?></span>
                                            <span class="status-arrow"><i class="fas fa-long-arrow-alt-right"></i></span>
                                            <span class="badge badge-<?= getStatusColor($log['status_baru']) ?>"><?= $log['status_baru'] ?></span>
                                        </p>
                                        <?php if (!empty($log['catatan'])): ?>
                                            <div class="mt-2">
                                                <small class="text-muted mb-1 d-block">Catatan:</small>
                                                <p class="mb-0"><?= nl2br(htmlspecialchars($log['catatan'])) ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            Belum ada history status untuk transfer gudang ini.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Table View -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">Tabel History Status</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered mg-b-0">
                            <thead>
                                <tr>
                                    <th class="text-center" width="5%">No</th>
                                    <th width="20%">Tanggal</th>
                                    <th width="15%">Status Lama</th>
                                    <th width="15%">Status Baru</th>
                                    <th>Catatan</th>
                                    <th width="15%">Oleh</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($logs) > 0): ?>
                                    <?php foreach ($logs as $i => $log): ?>
                                        <tr>
                                            <td class="text-center"><?= $i + 1 ?></td>
                                            <td><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
                                            <td>
                                                <?php if (!empty($log['status_lama'])): ?>
                                                    <span class="badge badge-<?= getStatusColor($log['status_lama']) ?>">
                                                        <?= $log['status_lama'] ?>
                                                    </span>
                                                <?php else: ?>
                                                    <em>-</em>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= getStatusColor($log['status_baru']) ?>">
                                                    <?= $log['status_baru'] ?>
                                                </span>
                                            </td>
                                            <td><?= nl2br(htmlspecialchars($log['catatan'])) ?: '-' ?></td>
                                            <td><?= $log['created_by_name'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">Tidak ada data history status</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4 text-center">
        <a href="<?php echo $sistem; ?>/gudangproduktransfer" class="btn btn-secondary">
            <i class="fa fa-arrow-left mr-1"></i> Kembali
        </a>

        <?php if ($transfer['status_ttg'] !== 'Completed' && $transfer['status_ttg'] !== 'Canceled'): ?>
            <button type="button" class="btn btn-primary" onclick="showStatusModal('<?php echo $transfer['id_ttg']; ?>', '<?php echo $transfer['status_ttg']; ?>')">
                <i class="fa fa-refresh mr-1"></i> Update Status
            </button>
        <?php endif; ?>


    </div>
</div>

<!-- Modal untuk update status -->
<div id="modal1" class="modal fade">
    <div class="modal-dialog modal-dialog-vertical-center" role="document">
        <div class="modal-content bd-0 tx-14">
            <div class="modal-body pd-0">
                <div class="row flex-row-reverse">
                    <div class="col-lg-12 pd-lg-25 pd-0">
                        <div id="Content"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Status -->
<div class="modal fade" id="statusModal" tabindex="-1" role="dialog" aria-labelledby="statusModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="statusModalLabel">Update Status Transfer</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <div id="statusUpdateResult"></div>

                <div id="statusFormContainer">
                    <form id="statusForm">
                        <input type="hidden" id="statusTransferId" value="">

                        <div class="form-group">
                            <label for="currentStatusDisplay">Status Saat Ini</label>
                            <div>
                                <span id="currentStatusBadge" class="badge badge-pill"></span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="newStatus">Status Baru <span class="text-danger">*</span></label>
                            <select class="form-control" id="newStatus" required>
                                <option value="">-- Pilih Status Baru --</option>
                                <!-- Options will be populated by JavaScript -->
                            </select>
                            <div id="statusError" class="invalid-feedback" style="display:none;"></div>
                        </div>

                        <div class="form-group">
                            <label for="statusCatatan">Catatan</label>
                            <textarea class="form-control" id="statusCatatan" rows="3" placeholder="Alasan perubahan status atau informasi tambahan..."></textarea>
                        </div>

                        <div id="statusInfoContainer"></div>
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnSubmitStatus">Update Status</button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Timeline Style */
    .timeline {
        position: relative;
        padding: 20px 0;
    }

    .timeline:before {
        content: '';
        position: absolute;
        height: 100%;
        width: 2px;
        background-color: #e9ecef;
        left: 24px;
        top: 0;
    }

    .timeline-item {
        position: relative;
        padding-left: 70px;
        padding-bottom: 25px;
    }

    .timeline-item:last-child {
        padding-bottom: 0;
    }

    .timeline-badge {
        position: absolute;
        width: 36px;
        height: 36px;
        left: 7px;
        top: 0;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        z-index: 1;
    }

    .timeline-content {
        padding: 15px;
        border-radius: 4px;
        background-color: #f8f9fa;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .timeline-date {
        font-size: 0.85rem;
        color: #6c757d;
    }

    .status-arrow {
        display: inline-block;
        margin: 0 8px;
        color: #6c757d;
    }

    /* Print styles */
    @media print {

        .breadcrumb,
        .content-header,
        .btn,
        #modal1 {
            display: none !important;
        }

        .card {
            border: 1px solid #ddd;
            margin-bottom: 20px;
            break-inside: avoid;
        }

        .card-header {
            background-color: #f8f9fa !important;
            border-bottom: 1px solid #ddd;
            padding: 10px 15px;
        }
    }
</style>

<script type="text/javascript">
    // Function untuk menampilkan modal update status
    function showStatusModal(id, currentStatus) {
        // Reset form
        $("#statusError").hide();
        $("#statusUpdateResult").empty();
        $("#statusForm")[0].reset();
        $("#statusTransferId").val(id);
        $("#statusInfoContainer").empty();

        // Set current status display
        $("#currentStatusBadge")
            .removeClass()
            .addClass("badge badge-pill badge-" + getStatusColorClass(currentStatus))
            .text(currentStatus);

        // Populate next status options based on current status
        const nextStatuses = getNextStatuses(currentStatus);
        let options = '<option value="">-- Pilih Status Baru --</option>';

        nextStatuses.forEach(status => {
            options += `<option value="${status}">${status}</option>`;
        });

        $("#newStatus").html(options);

        // Show appropriate info message
        if (nextStatuses.includes('Completed')) {
            $("#statusInfoContainer").html(`
                <div class="alert alert-info">
                    <i class="fa fa-info-circle mr-2"></i>
                    Mengubah status menjadi <strong>Completed</strong> akan menambah stok di gudang tujuan.
                </div>
            `);
        } else if (nextStatuses.includes('Canceled')) {
            $("#statusInfoContainer").html(`
                <div class="alert alert-warning">
                    <i class="fa fa-exclamation-triangle mr-2"></i>
                    Mengubah status menjadi <strong>Canceled</strong> akan membatalkan transfer. Tindakan ini tidak dapat dibatalkan.
                </div>
            `);
        }

        // Show modal
        $("#statusModal").modal('show');
    }

    // Helper function to get status color class
    function getStatusColorClass(status) {
        switch (status) {
            case 'Draft':
                return 'secondary';
            case 'Process':
                return 'primary';
            case 'Delivered':
                return 'info';
            case 'Completed':
                return 'success';
            case 'Canceled':
                return 'danger';
            default:
                return 'secondary';
        }
    }

    // Helper function to get next possible statuses
    function getNextStatuses(currentStatus) {
        switch (currentStatus) {
            case 'Draft':
                return ['Completed'];
            default:
                return [];
        }
    }

    // Submit status update
    $(document).ready(function() {
        $("#btnSubmitStatus").on('click', function() {
            const id = $("#statusTransferId").val();
            const status = $("#newStatus").val();
            const catatan = $("#statusCatatan").val();

            // Validasi
            if (!status) {
                $("#statusError").text("Pilih status baru").show();
                return false;
            } else {
                $("#statusError").hide();
            }

            // Tampilkan loading state
            $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memproses...');

            // Kirim data via AJAX
            $.ajax({
                url: '<?php echo $sistem; ?>/ajax/gudangproduktransfer/update_status.php',
                type: 'POST',
                data: {
                    id: id,
                    status: status,
                    catatan: catatan
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        // Tampilkan notifikasi sukses
                        $("#statusUpdateResult").html(`
                            <div class="alert alert-success">
                                <i class="fa fa-check-circle mr-2"></i> ${response.message}
                            </div>
                        `);

                        // Tunggu 1.5 detik, lalu refresh halaman
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        // Tampilkan error
                        $("#statusUpdateResult").html(`
                            <div class="alert alert-danger">
                                <i class="fa fa-exclamation-circle mr-2"></i> ${response.message}
                            </div>
                        `);
                        $("#btnSubmitStatus").prop('disabled', false).html('Update Status');
                    }
                },
                error: function(xhr, status, error) {
                    // Tampilkan error
                    $("#statusUpdateResult").html(`
                        <div class="alert alert-danger">
                            <i class="fa fa-exclamation-circle mr-2"></i> Terjadi kesalahan saat memproses permintaan. Silakan coba lagi.
                        </div>
                    `);
                    $("#btnSubmitStatus").prop('disabled', false).html('Update Status');
                }
            });
        });

        // Untuk keperluan demo di lingkungan pengembangan
        // Comment kode ini di production
        $("#btnSubmitStatus").off('click.dummyMode').on('click.dummyMode', function(e) {
            // Mencegah event handler asli berjalan di lingkungan dev
            if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                e.stopImmediatePropagation();

                const status = $("#newStatus").val();

                // Validasi
                if (!status) {
                    $("#statusError").text("Pilih status baru").show();
                    return false;
                }

                // Tampilkan loading state
                $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memproses...');

                // Simulasikan respon sukses setelah 1 detik
                setTimeout(function() {
                    $("#statusUpdateResult").html(`
                        <div class="alert alert-success">
                            <i class="fa fa-check-circle mr-2"></i> Status berhasil diubah menjadi ${status}
                        </div>
                    `);

                    // Tunggu 1.5 detik, lalu tutup modal (tanpa refresh untuk demo)
                    setTimeout(function() {
                        $("#statusModal").modal('hide');
                        $("#btnSubmitStatus").prop('disabled', false).html('Update Status');
                    }, 1500);
                }, 1000);
            }
        });
    });

    // Fungsi modal() yang ada tetap dipertahankan
    function modal(url) {
        $("#Content").html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 mb-0">Memuat data...</p></div>');
        $('#modal1').modal('show');
        $.ajax({
            url: url,
            type: 'GET',
            cache: false,
            success: function(response) {
                $("#Content").html(response);
            },
            error: function(xhr, status, error) {
                $("#Content").html(`
                    <div class="modal-header">
                        <h5 class="modal-title text-danger">Error</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger mb-0">
                            <p><i class="fa fa-exclamation-triangle mr-2"></i> Gagal memuat modal.</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
                    </div>
                `);
            }
        });
    }
</script>