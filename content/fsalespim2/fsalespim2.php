<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Penjualan</a></li>
                <li class="breadcrumb-item active" aria-current="page">Faktur Penjualan PIM</li>
            </ol>
        </nav>
        <h4 class="content-title">Faktur Penjualan PIM Nego E-Katalog</h4>
    </div>
</div>
<?php $cari	= $secu->injection(@$_GET['cari']); ?>
<input type="hidden" name="caridata" id="caridata" value="<?php echo($cari); ?>" readonly="readonly" />
<input type="hidden" name="halaman" id="halaman" value="1" readonly="readonly" />
<input type="hidden" name="maximal" id="maximal" value="15" readonly="readonly" />
<div class="content-body">
	<div class="row mg-b-10">
        <div class="col-sm-6">
        	<?php echo(($data->akses($admin, $menu, 'A.create_status')==='Active') ? '<a href="'.$sistem.'/fsalespim2/i"><button class="btn btn-primary btn-pill btn-xs"><i class="fa fa-plus-circle"></i> Tambah Data</button></a>' : ''); ?>
			<a href="#modal1" onclick="<?php echo("caridata('carifsales', 'fsalespim2', '$cari')"); ?>" data-toggle="modal"><button class="btn btn-warning btn-pill btn-xs"><i class="fa fa-search"></i> Cari Data</button></a>
			<a href="<?php echo($data->sistem('url_sis').'/fsalespim2'); ?>"><button class="btn btn-info btn-pill btn-xs"><i class="fa fa-spinner"></i> Refresh</button></a>
			<?php echo(($data->akses($admin, $menu, 'A.read_status')==='Active') ? '<a target="_blank" href="'.$sistem.'/laporan/xls/rpenjualan/rpenjualanpim2.php?key='.$cari.'" title="XLS"><button class="btn btn-success btn-pill btn-xs"><i class="fa fa-print"></i> PEENJUALAN</button></a>' : ''); ?>
        </div>
        <div class="col-sm-6">
			<span class="badge badge-pill badge-danger"><i class="fa fa-search"></i> Search : <?php echo($cari); ?></span>
        </div>
    </div>
    <?php require_once('config/frame/alert.php'); ?>
    <div class="table-responsive">
        <table class="table table-hover mg-b-0">
            <thead>
                <tr>
                    <th><center>#</center></th>
                    <th>Nomor Faktur</th>
                    <th>Outlet</th>
                    <th>CCP</th>
                    <th>Kota</th>
                    <th><center>Tgl. Faktur</center></th>
                    <th>Nomor PO</th>
                    <th><center>Tgl. PO</center></th>
                    <th><div align="right">Total</div></th>
                    <th><center>Detail</center></th>
                    <th><center>Action</center></th>
                    <th><center>excel</center></th>
                    <th><center>Suhu</center></th>
                    <th><center>SPH</center></th>
                    <th><center>Resi</center></th>

                </tr>
            </thead>
            <tbody id="isitabel"></tbody>
        </table>
        <div class="mg-t-10">
            <nav aria-label="Page navigation example">
                <ul class="pagination pagination-circle mg-b-0" id="paginasi"></ul>
            </nav>
		</div>
    </div>
</div>

<!-- Modal Keterangan Revisi (untuk fsalespim2) -->
<div class="modal fade" id="modalKeteranganRevisi" tabindex="-1" role="dialog" aria-labelledby="modalKeteranganRevisiLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalKeteranganRevisiLabel">
                    <i class="fa fa-edit"></i> Keterangan Revisi Item Faktur
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formKeteranganRevisi">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle"></i>
                        <strong>Perhatian:</strong> Anda akan mengedit item faktur <strong id="nomorFakturModal">-</strong>.
                        Perubahan akan mempengaruhi stok produk dan mengubah status faktur menjadi "Revisi".
                    </div>

                    <div class="form-group">
                        <label for="keteranganRevisiModal" class="form-label">
                            <strong>Keterangan Revisi <span class="text-danger">*</span></strong>
                        </label>
                        <textarea name="keterangan_revisi" id="keteranganRevisiModal" class="form-control" rows="4"
                                  placeholder="Wajib diisi: Masukkan alasan/keterangan untuk revisi item faktur ini..."
                                  maxlength="500" required></textarea>
                        <small class="form-text text-muted">
                            <i class="fa fa-info-circle"></i>
                            Keterangan ini akan tersimpan sebagai catatan audit revisi faktur.
                            <span class="float-right">
                                <span id="charCountModal">0</span>/500 karakter
                            </span>
                        </small>
                        <div class="invalid-feedback" id="feedbackModal" style="display:none"></div>
                    </div>

                    <input type="hidden" id="fakturIdModal" name="faktur_id" value="">
                    <input type="hidden" id="uniqCodeModal" name="uniq_code" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fa fa-times"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnLanjutEdit">
                        <i class="fa fa-arrow-right"></i> Lanjut ke Edit Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showFlash(type, msg){
    var cls = (type==='success') ? 'alert-success' : (type==='warning' ? 'alert-warning' : 'alert-danger');
    $('#flashArea').html('<div class="alert '+cls+' alert-dismissible fade show py-2 mb-2">'
        +'<button type="button" class="close" data-dismiss="alert">&times;</button>'
        + (msg||'') + '</div>');
    setTimeout(function(){ $('#flashArea .alert').alert('close'); }, 6000);
}

var currentUniqCode = '';
var currentFakturNomor = '';

function openKeteranganRevisi(uniqCode, nomorFaktur) {
    currentUniqCode = uniqCode;
    currentFakturNomor = nomorFaktur;

    $('#nomorFakturModal').text(nomorFaktur);
    $('#uniqCodeModal').val(uniqCode);
    $('#keteranganRevisiModal').val('').removeClass('is-invalid');
    $('#feedbackModal').hide();
    $('#charCountModal').text('0');

    setTimeout(function() {
        $('#keteranganRevisiModal').focus();
    }, 300);
}

$('#keteranganRevisiModal').on('input', function() {
    var value = $(this).val();
    var charCount = value.length;
    $('#charCountModal').text(charCount);
    if (charCount > 450) {
        $('#charCountModal').addClass('text-danger').removeClass('text-warning text-success');
    } else if (charCount > 350) {
        $('#charCountModal').addClass('text-warning').removeClass('text-danger text-success');
    } else {
        $('#charCountModal').addClass('text-success').removeClass('text-danger text-warning');
    }
    if (value.trim() === '') {
        $(this).addClass('is-invalid');
        $('#feedbackModal').text('Keterangan revisi wajib diisi').show();
    } else {
        $(this).removeClass('is-invalid');
        $('#feedbackModal').hide();
    }
});

$('#formKeteranganRevisi').on('submit', function(e) {
    e.preventDefault();
    var keterangan = $('#keteranganRevisiModal').val().trim();
    if (keterangan === '') {
        $('#keteranganRevisiModal').addClass('is-invalid').focus();
        $('#feedbackModal').text('Keterangan revisi wajib diisi sebelum dapat melanjutkan').show();
        return false;
    }
    if (keterangan.length < 10) {
        $('#keteranganRevisiModal').addClass('is-invalid').focus();
        $('#feedbackModal').text('Keterangan revisi minimal 10 karakter').show();
        return false;
    }
    sessionStorage.setItem('keterangan_revisi_' + currentUniqCode, keterangan);
    $('#modalKeteranganRevisi').modal('hide');

    // Redirect ke edit items untuk fsalespim2
    window.location.href = usuper + '/fsalespim2/items/' + currentUniqCode;
});

// auto-resize
$('#keteranganRevisiModal').on('input', function() {
    this.style.height = 'auto';
    this.style.height = (this.scrollHeight) + 'px';
});
</script>

<style>
.modal-lg { max-width: 600px; }
.form-label { font-weight: 600; margin-bottom: 8px; display: block; }
.float-right { float: right; }
#charCountModal { font-weight: 600; }
.invalid-feedback { display: block; width: 100%; margin-top: 0.25rem; font-size: 0.875em; color: #dc3545; }
textarea.form-control { resize: vertical; min-height: 100px; }
</style>