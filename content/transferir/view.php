<?php
$id_tir = (int)($secu->injection(@$_GET['keycode'] ?? 0));
if ($id_tir < 1) { header("Location: $sistem/transferir"); exit; }

// Ambil header
$qH = $conn->prepare("SELECT * FROM transfer_ir WHERE id_tir=:id");
$qH->bindParam(':id', $id_tir, PDO::PARAM_INT);
$qH->execute();
$H = $qH->fetch(PDO::FETCH_ASSOC);
if (!$H) { header("Location: $sistem/transferir"); exit; }

// Ambil detail
$qD = $conn->prepare("
	SELECT D.*, P.nama_pro, P.berat_pro, S.nama_spr,
	       PSD.sisa_psd
	FROM transfer_ir_detail D
	JOIN produk P ON D.id_pro = P.id_pro
	JOIN satuan_produk S ON P.id_spr = S.id_spr
	LEFT JOIN produk_stokdetail PSD ON D.id_psd = PSD.id_psd
	WHERE D.id_tir = :id
	ORDER BY D.id_tird
");
$qD->bindParam(':id', $id_tir, PDO::PARAM_INT);
$qD->execute();
$details = $qD->fetchAll(PDO::FETCH_ASSOC);

$statusLabel = [
	'draft'    => '<span class="badge badge-secondary badge-lg">Draft</span>',
	'pending'  => '<span class="badge badge-warning text-dark badge-lg">Menunggu Approve</span>',
	'approved' => '<span class="badge badge-success badge-lg">Approved</span>',
	'rejected' => '<span class="badge badge-danger badge-lg">Ditolak</span>',
];
?>
<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Menu</a></li>
                <li class="breadcrumb-item"><a href="<?php echo $sistem; ?>/transferir">Transfer Retur ke Penjualan</a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($H['no_tir']); ?></li>
            </ol>
        </nav>
        <h4 class="content-title">
            <i class="fa fa-exchange-alt text-warning"></i>
            Detail Transfer Retur ke Penjualan: <strong><?php echo htmlspecialchars($H['no_tir']); ?></strong>
            <?php echo $statusLabel[$H['status_tir']] ?? $H['status_tir']; ?>
        </h4>
    </div>
</div>

<div class="content-body">
    <!-- Info Header -->
    <div class="row mg-b-15">
        <div class="col-md-6">
            <div class="card card-bordered">
                <div class="card-body p-3">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><th width="130">No. Transfer</th><td>: <strong><?php echo htmlspecialchars($H['no_tir']); ?></strong></td></tr>
                        <tr><th>Status</th><td>: <?php echo $statusLabel[$H['status_tir']] ?? $H['status_tir']; ?></td></tr>
                        <tr><th>Keterangan</th><td>: <?php echo htmlspecialchars($H['keterangan']); ?></td></tr>
                        <tr><th>Dibuat oleh</th><td>: <?php echo htmlspecialchars($H['created_by']); ?></td></tr>
                        <tr><th>Tanggal Buat</th><td>: <?php echo $H['created_at'] ? date('d/m/Y H:i', strtotime($H['created_at'])) : '-'; ?></td></tr>
                        <?php if($H['status_tir'] === 'pending' || $H['status_tir'] === 'approved' || $H['status_tir'] === 'rejected'): ?>
                        <tr><th>Diajukan oleh</th><td>: <?php echo htmlspecialchars($H['submitted_by']); ?></td></tr>
                        <tr><th>Tgl Pengajuan</th><td>: <?php echo $H['submitted_at'] ? date('d/m/Y H:i', strtotime($H['submitted_at'])) : '-'; ?></td></tr>
                        <?php endif; ?>
                        <?php if($H['status_tir'] === 'approved' || $H['status_tir'] === 'rejected'): ?>
                        <tr><th>Diproses oleh</th><td>: <?php echo htmlspecialchars($H['approved_by']); ?></td></tr>
                        <tr><th>Tgl Proses</th><td>: <?php echo $H['approved_at'] ? date('d/m/Y H:i', strtotime($H['approved_at'])) : '-'; ?></td></tr>
                        <?php endif; ?>
                        <?php if($H['notes_tir']): ?>
                        <tr><th>Catatan Approve</th><td>: <em><?php echo htmlspecialchars($H['notes_tir']); ?></em></td></tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <!-- Action Buttons -->
            <div class="card card-bordered h-100">
                <div class="card-header bg-light"><h6 class="mb-0">Aksi</h6></div>
                <div class="card-body d-flex align-items-center flex-wrap gap-2">
                    <?php if($H['status_tir'] === 'draft'): ?>
                        <?php if($data->akses($admin, $menu, 'A.update_status') === 'Active'): ?>
                        <button class="btn btn-warning btn-sm mr-2" onclick="submitPending()">
                            <i class="fa fa-paper-plane"></i> Ajukan ke Approve
                        </button>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if($H['status_tir'] === 'pending'): ?>
                        <?php if($level === 'Super' || $data->akses($admin, $menu, 'A.delete_status') === 'Active'): ?>
                        <button class="btn btn-success btn-sm mr-2" onclick="approve()">
                            <i class="fa fa-check-circle"></i> Approve & Pindah ke Penjualan
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="tolak()">
                            <i class="fa fa-times-circle"></i> Tolak
                        </button>
                        <?php endif; ?>
                    <?php endif; ?>
                    <a href="<?php echo $sistem; ?>/transferir" class="btn btn-secondary btn-sm ml-auto">
                        <i class="fa fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Detail Item -->
    <div class="table-responsive">
        <table class="table table-bordered table-sm table-hover">
            <thead class="thead-dark">
                <tr>
                    <th width="4%"><center>#</center></th>
                    <th>Produk</th>
                    <th width="14%"><center>No. Batch</center></th>
                    <th width="8%"><center>ED</center></th>
                    <th width="8%"><center>Gudang</center></th>
                    <th width="7%"><center>Jumlah</center></th>
                    <?php if($H['status_tir'] === 'draft'): ?>
                    <th width="8%"><center>Sisa Stok</center></th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php if(empty($details)): ?>
                <tr><td colspan="7" class="text-center text-muted">Tidak ada item.</td></tr>
            <?php else: ?>
            <?php $no=1; foreach($details as $d): ?>
                <tr>
                    <td><center><?php echo $no++; ?></center></td>
                    <td>
                        <strong><?php echo htmlspecialchars($d['nama_pro'].' '.$d['berat_pro'].' '.$d['nama_spr']); ?></strong>
                    </td>
                    <td><center><?php echo htmlspecialchars($d['no_bcode']); ?></center></td>
                    <td><center><?php echo $d['tgl_expired']; ?></center></td>
                    <td><center><?php echo htmlspecialchars($d['gudang']); ?></center></td>
                    <td><center><strong><?php echo number_format($d['jumlah'],0,',','.'); ?></strong></center></td>
                    <?php if($H['status_tir'] === 'draft'): ?>
                    <td><center><?php echo number_format((int)$d['sisa_psd'],0,',','.'); ?></center></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Notes Tolak -->
<div class="modal fade" id="modalTolak" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h6 class="modal-title">Tolak Transfer</h6>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Alasan Penolakan</label>
                    <textarea id="notesTolak" class="form-control" rows="3" placeholder="Isi alasan penolakan..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
                <button class="btn btn-danger btn-sm" onclick="konfirmasiTolak()">Tolak Transfer</button>
            </div>
        </div>
    </div>
</div>

<script>
var idTir = <?php echo $id_tir; ?>;
var urlAction = '<?php echo $data->sistem('url_sis'); ?>/modal/transferir/action.php';

function submitPending() {
    swal({
        title: 'Ajukan Transfer?',
        text: 'Transfer akan dikirim untuk proses approval.',
        type: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Ajukan',
        cancelButtonText: 'Batal'
    }, function(isConfirmed) {
        if (isConfirmed) kirimAksi('submit_pending', '');
    });
}

function approve() {
    swal({
        title: 'Approve Transfer?',
        text: 'Stok akan langsung dipindah ke Inventory Penjualan.',
        type: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Approve',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#28a745'
    }, function(isConfirmed) {
        if (isConfirmed) kirimAksi('approve', '');
    });
}

function tolak() {
    $('#notesTolak').val('');
    $('#modalTolak').modal('show');
}

function konfirmasiTolak() {
    var notes = $('#notesTolak').val().trim();
    $('#modalTolak').modal('hide');
    kirimAksi('reject', notes);
}

function kirimAksi(action, notes) {
    swal({ title: 'Memproses...', text: 'Mohon tunggu...', showConfirmButton: false });
    $.ajax({
        url: urlAction,
        type: 'POST',
        data: { namamenu: action, id_tir: idTir, notes: notes },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'ok') {
                swal('Berhasil', res.message, 'success');
                setTimeout(function() { location.reload(); }, 1500);
            } else {
                swal('Gagal', res.message, 'error');
            }
        },
        error: function() { swal('Error', 'Terjadi kesalahan.', 'error'); }
    });
}
</script>
