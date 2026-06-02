<?php
require_once('../../config/connection/connection.php');
require_once('../../config/connection/security.php');
require_once('../../config/function/data.php');
$secu    = new Security;
$base    = new DB;
$data    = new Data;
$conn    = $base->open();
$modal   = $secu->injection(@$_GET['modal']);

// Helper function untuk warna status - deklarasikan SEKALI di awal file
function getStatusColor($status)
{
    switch ($status) {
        case 'Draft':
            return 'secondary';
        case 'Completed':
        case 'Selesai': // backward compatibility
            return 'success';
        default:
            return 'secondary';
    }
}

switch ($modal) {
    case "update":
        $kode    = $secu->injection($_GET['keycode']);
        $read    = $conn->prepare("SELECT kode_ttg FROM transfer_gudang WHERE id_ttg=:kode");
        $read->bindParam(':kode', $kode, PDO::PARAM_STR);
        $read->execute();
        $view    = $read->fetch(PDO::FETCH_ASSOC);
?>
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Update Data - Transfer Gudang</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formtransaksi" action="#" method="post" autocomplete="off">
            <input type="hidden" name="nmenu" id="nmenu" value="gudangproduktransfer" readonly="readonly" />
            <input type="hidden" name="nact" id="nact" value="update" readonly="readonly" />
            <input type="hidden" name="keycode" id="keycode" value="<?php echo ($kode); ?>" readonly="readonly" />
            <div class="modal-body">
                <div class="row">
                    <div class="form-group col-md-12">
                        <label>Nomor Referensi <span class="tx-danger">*</span></label>
                        <input type="text" name="nomorreferensi" class="form-control" value="<?php echo ($view['kode_ttg']); ?>" placeholder="Masukkan nomor referensi..." required="required" />
                        <div id="imgloading"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Batal</button>
                <button type="submit" id="bsave" class="btn btn-dark btn-xs">Update</button>
            </div>
        </form>
    <?php
        break;
    case 'delete':
        $kode    = $secu->injection($_GET['keycode']);
        $read    = $conn->prepare("SELECT * FROM transfer_gudang WHERE id_ttg=:kode");
        $read->bindParam(':kode', $kode, PDO::PARAM_STR);
        $read->execute();
        $view    = $read->fetch(PDO::FETCH_ASSOC);
    ?>
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Konfirmasi</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formtransaksi" action="#" method="post" autocomplete="off">
            <input type="hidden" name="nmenu" id="nmenu" value="gudangproduktransfer" readonly="readonly" />
            <input type="hidden" name="nact" id="nact" value="delete" readonly="readonly" />
            <input type="hidden" name="keycode" id="keycode" value="<?php echo ($kode); ?>" readonly="readonly" />
            <input type="hidden" name="id" id="id" value="<?= $view['id_ttg'] ?>" readonly="readonly" />
            <input type="hidden" name="kode" id="kode" value="<?= $view['kode_ttg'] ?>" readonly="readonly" />
            <div class="modal-body">
                <div class="row">
                    <div class="form-group col-md-12">
                        <label><?php echo (($view['status_ttg'] == 'Draft') ? 'Hapus data Transfer Gudang?' : 'Data sudah diproses, tidak dapat dihapus!'); ?> <span class="tx-danger">*</span></label>

                        <?php if ($view['status_ttg'] != 'Draft'): ?>
                            <!-- Warning message untuk data yang tidak bisa dihapus -->
                            <div class="alert alert-warning mt-2" style="border-left: 4px solid #ffc107; background-color: #fff9e6;">
                                <div style="display: flex; align-items: center;">
                                    <i class="fa fa-exclamation-triangle mr-3" style="font-size: 24px; color: #ffc107;"></i>
                                    <div>
                                        <strong style="color: #856404;">Peringatan</strong>
                                        <p class="mb-0">Transfer Gudang dengan status <strong><?= $view['status_ttg'] ?></strong> tidak dapat dihapus karena sudah diproses.</p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Informasi detail transfer yang akan dihapus -->
                        <div class="mt-3">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td width="35%">Kode Transfer</td>
                                    <td width="5%">:</td>
                                    <td><strong><?= $view['kode_ttg'] ?></strong></td>
                                </tr>
                                <tr>
                                    <td>Tanggal</td>
                                    <td>:</td>
                                    <td><?= date('d M Y', strtotime($view['tgl_ttg'])) ?></td>
                                </tr>
                                <tr>
                                    <td>Status</td>
                                    <td>:</td>
                                    <td>
                                        <?php
                                        $statusColor = '';
                                        switch ($view['status_ttg']) {
                                            case 'Draft':
                                                $statusColor = 'secondary';
                                                break;
                                            case 'Process':
                                                $statusColor = 'primary';
                                                break;
                                            case 'Delivered':
                                                $statusColor = 'info';
                                                break;
                                            case 'Completed':
                                                $statusColor = 'success';
                                                break;
                                            case 'Canceled':
                                                $statusColor = 'danger';
                                                break;
                                            default:
                                                $statusColor = 'secondary';
                                        }
                                        ?>
                                        <span class="badge badge-<?= $statusColor ?>"><?= $view['status_ttg'] ?></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Batal</button>
                <?php if ($view['status_ttg'] == 'Draft'): ?>
                    <button type="submit" id="bsave" class="btn btn-dark btn-xs">Hapus</button>
                <?php endif; ?>
            </div>
        </form>
    <?php
        break;
    case 'status':
        $id = $secu->injection($_GET['keycode']);

        // Dapatkan data transfer
        $get_transfer = $conn->prepare("SELECT * FROM transfer_gudang WHERE id_ttg = :id");
        $get_transfer->bindParam(':id', $id);
        $get_transfer->execute();
        $transfer = $get_transfer->fetch(PDO::FETCH_ASSOC);

        // Tentukan status yang bisa dipilih berdasarkan status saat ini
        $current_status = $transfer['status_ttg'];
        $next_statuses = [];

        // Mapping dari status lama ke baru untuk backward compatibility
        $status_mapping = [
            'Process' => 'Diproses',
            'Delivered' => 'Dikirim',
            'Completed' => 'Selesai',
            'Canceled' => 'Dibatalkan'
        ];

        // Periksa apakah status saat ini menggunakan format lama
        if (in_array($current_status, array_keys($status_mapping))) {
            $current_status_display = $status_mapping[$current_status];
        } else {
            $current_status_display = $current_status;
        }

        switch ($current_status) {
            case 'Draft':
                $next_statuses = ['Completed', 'Dibatalkan'];
                break;
            default:
                // Jika status Selesai, tidak bisa diubah lagi
                $next_statuses = [];
        }
    ?>
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Update Status Transfer</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <div class="modal-body">
            <div id="updateResult"></div>

            <?php if (empty($next_statuses)): ?>
                <div class="alert alert-warning">
                    <i class="fa fa-exclamation-triangle mr-2"></i>
                    Status tidak dapat diubah karena sudah dalam status final (<?= $current_status_display ?>).
                </div>
            <?php else: ?>
                <form id="formUpdateStatus">
                    <input type="hidden" id="idTransfer" value="<?= $id ?>">

                    <div class="form-group">
                        <label for="currentStatus">Status Saat Ini</label>
                        <div>
                            <span class="badge badge-<?= getStatusColor($current_status) ?> badge-pill"><?= $current_status_display ?></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="newStatus">Status Baru <span class="text-danger">*</span></label>
                        <select class="form-control" id="newStatus" required>
                            <option value="">-- Pilih Status Baru --</option>
                            <?php foreach ($next_statuses as $status): ?>
                                <option value="<?= $status ?>"><?= $status ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div id="statusError" class="invalid-feedback" style="display:none;"></div>
                    </div>

                    <div class="form-group">
                        <label for="catatan">Catatan</label>
                        <textarea class="form-control" id="catatan" rows="3" placeholder="Alasan perubahan status atau informasi tambahan..."></textarea>
                    </div>

                    <?php if (in_array('Completed', $next_statuses)): ?>
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle mr-2"></i>
                            Mengubah status menjadi <strong>Selesai</strong> akan menambah stok di gudang tujuan.
                        </div>
                    <?php elseif (in_array('Dibatalkan', $next_statuses)): ?>
                        <div class="alert alert-warning">
                            <i class="fa fa-exclamation-triangle mr-2"></i>
                            Mengubah status menjadi <strong>Dibatalkan</strong> akan membatalkan transfer. Tindakan ini tidak dapat dibatalkan.
                        </div>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>

            <?php if (!empty($next_statuses)): ?>
                <button type="button" class="btn btn-primary btn-sm" id="btnSubmit">Update Status</button>
            <?php endif; ?>
        </div>

        <script>
            $(document).ready(function() {
                // Tambahkan event listener langsung di modal
                $("#btnSubmit").on('click', function() {
                    var id = $("#idTransfer").val();
                    var status = $("#newStatus").val();
                    var catatan = $("#catatan").val();

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
                        url: '<?php echo $data->sistem('url_sis'); ?>/ajax/gudangproduktransfer/update_status.php',
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
                                $("#updateResult").html(`
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
                                $("#updateResult").html(`
                                <div class="alert alert-danger">
                                    <i class="fa fa-exclamation-circle mr-2"></i> ${response.message}
                                </div>
                            `);
                                $("#btnSubmit").prop('disabled', false).html('Update Status');
                            }
                        },
                        error: function(xhr, status, error) {
                            // Tampilkan error
                            $("#updateResult").html(`
                            <div class="alert alert-danger">
                                <i class="fa fa-exclamation-circle mr-2"></i> Terjadi kesalahan saat memproses permintaan. Silakan coba lagi.
                            </div>
                        `);
                            $("#btnSubmit").prop('disabled', false).html('Update Status');
                        }
                    });
                });
            });
        </script>
    <?php
        break;
    case 'tutup':
        $kode    = $secu->injection($_GET['keycode']);
        $read    = $conn->prepare("SELECT * FROM transfer_gudang WHERE id_ttg=:kode");
        $read->bindParam(':kode', $kode, PDO::PARAM_STR);
        $read->execute();
        $view    = $read->fetch(PDO::FETCH_ASSOC);

        // Status mapping untuk backward compatibility
        $status_display = $view['status_ttg'];
        switch ($status_display) {
            case 'Process':
                $status_display = 'Diproses';
                break;
            case 'Delivered':
                $status_display = 'Dikirim';
                break;
            case 'Completed':
                $status_display = 'Selesai';
                break;
            case 'Canceled':
                $status_display = 'Dibatalkan';
                break;
        }
    ?>
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Informasi</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formtransaksi" action="#" method="post" autocomplete="off">
            <input type="hidden" name="nmenu" id="nmenu" value="gudangproduktransfer" readonly="readonly" />
            <input type="hidden" name="nact" id="nact" value="tutup" readonly="readonly" />
            <input type="hidden" name="keycode" id="keycode" value="<?php echo ($kode); ?>" readonly="readonly" />
            <div class="modal-body">
                <div class="row">
                    <div class="form-group col-md-12">
                        <label><?php echo (($view['status_ttg'] == 'Completed' || $view['status_ttg'] == 'Selesai') ? "Transfer Gudang dengan kode : <b>$view[kode_ttg]</b> sudah selesai!" : "Transfer Gudang dengan kode : <b>$view[kode_ttg]</b> masih dalam proses!"); ?></label>

                        <?php if ($view['status_ttg'] == 'Completed' || $view['status_ttg'] == 'Selesai'): ?>
                            <div class="alert alert-success mt-3" style="border-left: 4px solid #28a745; background-color: #f8fff9;">
                                <div style="display: flex; align-items: center;">
                                    <i class="fa fa-check-circle mr-3" style="font-size: 24px; color: #28a745;"></i>
                                    <div>
                                        <strong style="color: #155724;">Transfer Selesai</strong>
                                        <p class="mb-0">Transfer gudang ini telah berhasil diselesaikan pada tanggal <?= date('d M Y H:i', strtotime($view['updated_at'])) ?>.</p>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info mt-3" style="border-left: 4px solid #17a2b8; background-color: #f2fbfd;">
                                <div style="display: flex; align-items: center;">
                                    <i class="fa fa-info-circle mr-3" style="font-size: 24px; color: #17a2b8;"></i>
                                    <div>
                                        <strong style="color: #0c5460;">Transfer Dalam Proses</strong>
                                        <p class="mb-0">Transfer gudang ini masih dalam status <span class="badge badge-<?= getStatusColor($view['status_ttg']) ?>"><?= $status_display ?></span>.</p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Tutup</button>
            </div>
        </form>
    <?php
        break;

    case 'detail':
        $kode    = $secu->injection($_GET['keycode']);
        $read    = $conn->prepare("SELECT 
            t.id_ttg, 
            t.kode_ttg, 
            t.tgl_ttg, 
            t.id_inventory, 
            t.id_inventory_tujuan,
            t.ket_ttg,
            t.status_ttg,
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
        WHERE t.id_ttg=:kode");
        $read->bindParam(':kode', $kode, PDO::PARAM_STR);
        $read->execute();
        $view    = $read->fetch(PDO::FETCH_ASSOC);

        // Status mapping untuk backward compatibility
        $status_display = $view['status_ttg'];
        switch ($status_display) {
            case 'Process':
                $status_display = 'Diproses';
                break;
            case 'Delivered':
                $status_display = 'Dikirim';
                break;
            case 'Completed':
                $status_display = 'Selesai';
                break;
            case 'Canceled':
                $status_display = 'Dibatalkan';
                break;
        }

        // Get details
        $detail = $conn->prepare("SELECT 
            td.*, 
            p.nama_pro,
            p.kode_pro,
            ps.tgl_expired,
            ps.no_bcode
        FROM transfer_gudangdetail td
        LEFT JOIN produk p ON td.id_pro = p.id_pro
        LEFT JOIN produk_stokdetail ps ON td.id_psd = ps.id_psd
        WHERE td.id_ttg=:kode
        ORDER BY p.nama_pro ASC");
        $detail->bindParam(':kode', $kode, PDO::PARAM_STR);
        $detail->execute();
        $details = $detail->fetchAll(PDO::FETCH_ASSOC);
    ?>
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Detail Transfer Gudang - <?= $view['kode_ttg'] ?></h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-12">
                    <!-- Header info with better styling -->
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                        <h6 class="mb-0 font-weight-bold"><?= $view['kode_ttg'] ?></h6>
                        <span class="badge badge-<?= getStatusColor($view['status_ttg']) ?> badge-pill"><?= $status_display ?></span>
                    </div>

                    <!-- Transfer info -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td width="40%" class="font-weight-medium text-muted">Tanggal</td>
                                    <td width="5%">:</td>
                                    <td class="font-weight-medium"><?= date('d M Y', strtotime($view['tgl_ttg'])) ?></td>
                                </tr>
                                <tr>
                                    <td class="font-weight-medium text-muted">Gudang Asal</td>
                                    <td>:</td>
                                    <td class="font-weight-medium"><?= $view['gudang_asal_nama'] ?: '-' ?></td>
                                </tr>
                                <tr>
                                    <td class="font-weight-medium text-muted">Gudang Tujuan</td>
                                    <td>:</td>
                                    <td class="font-weight-medium"><?= $view['gudang_tujuan_nama'] ?: '-' ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td width="40%" class="font-weight-medium text-muted">Dibuat Oleh</td>
                                    <td width="5%">:</td>
                                    <td class="font-weight-medium"><?= $view['created_by_name'] ?: '-' ?></td>
                                </tr>
                                <tr>
                                    <td class="font-weight-medium text-muted">Tanggal Dibuat</td>
                                    <td>:</td>
                                    <td class="font-weight-medium"><?= date('d M Y H:i', strtotime($view['created_at'])) ?></td>
                                </tr>
                                <tr>
                                    <td class="font-weight-medium text-muted">Terakhir Diupdate</td>
                                    <td>:</td>
                                    <td class="font-weight-medium">
                                        <?= !empty($view['updated_at']) ? date('d M Y H:i', strtotime($view['updated_at'])) . ' oleh ' . $view['updated_by_name'] : '-' ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Keterangan -->
                    <?php if (!empty($view['ket_ttg'])): ?>
                        <div class="mb-4 border-top pt-3">
                            <h6 class="mb-2 font-weight-bold">Keterangan:</h6>
                            <p class="bg-light p-3 rounded mb-0"><?= nl2br(htmlspecialchars($view['ket_ttg'])) ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- Detail produk dengan styling yang lebih baik -->
                    <div class="mb-3 border-top pt-3">
                        <h6 class="mb-3 font-weight-bold">Daftar Produk</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="thead-light">
                                    <tr>
                                        <th class="text-center" width="5%">No</th>
                                        <th>Kode</th>
                                        <th>Nama Produk</th>
                                        <th width="15%">Batch</th>
                                        <th width="15%">Kadaluarsa</th>
                                        <th class="text-right" width="12%">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($details) > 0): ?>
                                        <?php foreach ($details as $i => $item): ?>
                                            <tr>
                                                <td class="text-center"><?= $i + 1 ?></td>
                                                <td><?= $item['kode_pro'] ?: '-' ?></td>
                                                <td><?= $item['nama_pro'] ?></td>
                                                <td><?= $item['no_batch'] ?: '-' ?></td>
                                                <td><?= !empty($item['tgl_expired']) ? date('d M Y', strtotime($item['tgl_expired'])) : '-' ?></td>
                                                <td class="text-right"><?= number_format($item['jumlah_ttd']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>

                                        <!-- Total -->
                                        <tr class="bg-light font-weight-bold">
                                            <td colspan="5" class="text-right">Total Item:</td>
                                            <td class="text-right">
                                                <?= number_format(array_sum(array_column($details, 'jumlah_ttd'))) ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">Tidak ada data produk</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Status history - jika diperlukan -->
                    <?php if ($view['status_ttg'] != 'Draft'): ?>
                        <div class="border-top pt-3">
                            <div class="alert alert-light border">
                                <i class="fa fa-info-circle mr-2 text-info"></i>
                                <small class="text-muted">Status transfer gudang ini sudah berubah dari Draft menjadi <?= $status_display ?>.</small>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Tutup</button>
            <!-- Tambahkan tombol print jika diperlukan -->
            <button type="button" class="btn btn-primary btn-sm" onclick="printTransferDetail('<?= $view['id_ttg'] ?>')">
                <i class="fa fa-print mr-1"></i> Cetak
            </button>
        </div>

        <script>
            // Function untuk cetak detail transfer
            function printTransferDetail(id) {
                // Open print window
                window.open('<?= $data->sistem('url_sis') ?>/report/transfer_detail_print.php?id=' + id, '_blank');
            }
        </script>
<?php
        break;
}

$conn = $base->close();
?>
<script type="text/javascript" src="<?php echo ($data->sistem('url_sis') . '/config/js/fazlurr.js'); ?>"></script>