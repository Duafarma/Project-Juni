<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Penjualan</a></li>
                <li class="breadcrumb-item active" aria-current="page">Faktur Penjualan</li>
            </ol>
        </nav>
        <h4 class="content-title">Faktur Penjualan</h4>
    </div>
</div>
<?php $cari	= $secu->injection(@$_GET['cari']); ?>
<input type="hidden" name="caridata" id="caridata" value="<?php echo($cari); ?>" readonly="readonly" />
<input type="hidden" name="halaman" id="halaman" value="1" readonly="readonly" />
<input type="hidden" name="maximal" id="maximal" value="15" readonly="readonly" />
<div class="content-body">
	<div class="row mg-b-10">
        <div class="col-sm-6">
        	<?php echo(($data->akses($admin, $menu, 'A.create_status')==='Active') ? '<a href="'.$sistem.'/fsales/i"><button class="btn btn-primary btn-pill btn-xs"><i class="fa fa-plus-circle"></i> Tambah Data</button></a>' : ''); ?>
			<a href="#modal1" onclick="<?php echo("caridata('carifsales', 'fsales', '$cari')"); ?>" data-toggle="modal"><button class="btn btn-warning btn-pill btn-xs"><i class="fa fa-search"></i> Cari Data</button></a>
			<a href="<?php echo($data->sistem('url_sis').'/fsales'); ?>"><button class="btn btn-info btn-pill btn-xs"><i class="fa fa-spinner"></i> Refresh</button></a>
        </div>
        <div class="col-sm-6">
			<span class="badge badge-pill badge-danger"><i class="fa fa-search"></i> Search : <?php echo($cari); ?></span>
        </div>
    </div>
    <?php require_once('config/frame/alert.php'); ?>
    <div id="flashArea"></div>
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
                    <th><center>Pajak</center></th>

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

<!-- Modal Keterangan Revisi -->
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
                        <div class="invalid-feedback" id="feedbackModal"></div>
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
// Flash message utility
function showFlash(type, msg){
    var cls = (type==='success') ? 'alert-success' : (type==='warning' ? 'alert-warning' : 'alert-danger');
    $('#flashArea').html('<div class="alert '+cls+' alert-dismissible fade show py-2 mb-2">'
        +'<button type="button" class="close" data-dismiss="alert">&times;</button>'
        + (msg||'') + '</div>');
    setTimeout(function(){ $('#flashArea .alert').alert('close'); }, 6000);
}
// Global variables untuk modal
var currentUniqCode = '';
var currentFakturNomor = '';

// Function untuk membuka modal keterangan revisi
function openKeteranganRevisi(uniqCode, nomorFaktur) {
    currentUniqCode = uniqCode;
    currentFakturNomor = nomorFaktur;
    
    // Set data di modal
    $('#nomorFakturModal').text(nomorFaktur);
    $('#uniqCodeModal').val(uniqCode);
    $('#keteranganRevisiModal').val('').removeClass('is-invalid');
    $('#feedbackModal').hide();
    $('#charCountModal').text('0');
    
    // Focus pada textarea
    setTimeout(function() {
        $('#keteranganRevisiModal').focus();
    }, 500);
}

// Character counter untuk modal
$('#keteranganRevisiModal').on('input', function() {
    var value = $(this).val();
    var charCount = value.length;
    
    // Update character counter
    $('#charCountModal').text(charCount);
    
    // Color coding untuk character counter
    if (charCount > 450) {
        $('#charCountModal').addClass('text-danger').removeClass('text-warning text-success');
    } else if (charCount > 350) {
        $('#charCountModal').addClass('text-warning').removeClass('text-danger text-success');
    } else {
        $('#charCountModal').addClass('text-success').removeClass('text-danger text-warning');
    }
    
    // Validation
    if (value.trim() === '') {
        $(this).addClass('is-invalid');
        $('#feedbackModal').text('Keterangan revisi wajib diisi').show();
    } else {
        $(this).removeClass('is-invalid');
        $('#feedbackModal').hide();
    }
});

// Handle form submit
$('#formKeteranganRevisi').on('submit', function(e) {
    e.preventDefault();
    
    var keterangan = $('#keteranganRevisiModal').val().trim();
    
    // Validasi
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
    
    // Simpan keterangan ke session storage untuk dibawa ke halaman edit
    sessionStorage.setItem('keterangan_revisi_' + currentUniqCode, keterangan);
    
    // Tutup modal dan redirect ke halaman edit item
    $('#modalKeteranganRevisi').modal('hide');
    
    // Redirect ke halaman edit item
    window.location.href = usuper + '/fsales/items/' + currentUniqCode;
});

// Auto-resize textarea
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

<!-- Modal Delete Faktur -->
<div class="modal fade" id="modalDeleteFaktur" tabindex="-1" role="dialog" aria-labelledby="modalDeleteFakturLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalDeleteFakturLabel"><i class="fa fa-trash"></i> Hapus Faktur</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formDeleteFaktur">
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fa fa-exclamation-triangle"></i> Anda akan menghapus faktur <strong id="deleteNomorFaktur">-</strong>. Data akan di-backup secara otomatis dengan status Delete.
                    </div>
                    <div class="form-group">
                        <label for="deleteKeterangan" class="form-label"><strong>Alasan / Keterangan Penghapusan <span class="text-danger">*</span></strong></label>
                        <textarea id="deleteKeterangan" name="keterangan_revisi" class="form-control" rows="3" placeholder="Wajib diisi. Contoh: Cancel Faktur Penjualan." maxlength="300" required></textarea>
                        <small class="form-text text-muted"><span id="deleteCharCount">0</span>/300 karakter</small>
                        <div class="invalid-feedback" id="deleteFeedback"></div>
                    </div>
                    <input type="hidden" id="deleteIdFaktur" name="keycode" value="" />
                    <input type="hidden" name="namamenu" value="delete" />
                    <input type="hidden" id="deleteNomorHidden" name="nomorfaktur" value="" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-times"></i> Batal</button>
                    <button type="submit" class="btn btn-danger"><i class="fa fa-trash"></i> Hapus Faktur Penjualan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    
function openDeleteFaktur(id, kode){
    $('#deleteIdFaktur').val(id);
    $('#deleteNomorHidden').val(kode);
    $('#deleteNomorFaktur').text(kode);
    $('#deleteKeterangan').val('').removeClass('is-invalid');
    $('#deleteFeedback').hide();
    $('#deleteCharCount').text('0');
}

$('#deleteKeterangan').on('input', function(){
    const len = this.value.length;
    $('#deleteCharCount').text(len);
    if(this.value.trim()==='' || len < 5){
        $(this).addClass('is-invalid');
        $('#deleteFeedback').text('Minimal 5 karakter dan tidak boleh kosong').show();
    } else {
        $(this).removeClass('is-invalid');
        $('#deleteFeedback').hide();
    }
});

$('#formDeleteFaktur').on('submit', function(e){
    e.preventDefault();
    const ket = $('#deleteKeterangan').val().trim();
    if(ket === '' || ket.length < 5){
        $('#deleteKeterangan').addClass('is-invalid');
        $('#deleteFeedback').text('Alasan penghapusan wajib diisi (>=5 karakter)').show();
        return;
    }
    const formData = new FormData(this);
    $.ajax({
        url: usuper + '/modal/fsales/action.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(resp){
            try { var r = JSON.parse(resp); } catch(e){ r = {status:'Error', message:'Response tidak valid'}; }
            if(r.status === 'Success'){
                showFlash('success', r.message || 'Faktur berhasil dihapus & dibackup');
                $('#modalDeleteFaktur').modal('hide');
                // Hapus baris langsung jika ada
                if(r.deleted_id){
                    var rowSel = '#row_faktur_'+r.deleted_id;
                    var $row = $(rowSel);
                    if($row.length){
                        $row.addClass('table-danger');
                        setTimeout(function(){ $row.fadeOut(400, function(){ $(this).remove(); }); }, 150);
                    }
                }
                // Refresh data untuk sync nomor & pagination
                setTimeout(function(){ loadData(); }, 700);
            } else {
                $('#deleteFeedback').text(r.message).show();
                $('#deleteKeterangan').addClass('is-invalid');
            }
        },
        error: function(){
            $('#deleteFeedback').text('Terjadi kesalahan koneksi').show();
            showFlash('danger', 'Terjadi kesalahan koneksi saat menghapus');
            $('#deleteKeterangan').addClass('is-invalid');
        }
    });
});
</script>