<?php
/**
 * Form Transfer Faktur ke Stok
 * Step 1: Cari & pilih faktur
 * Step 2: Pilih item, isi batch/expired/gudang & qty transfer
 */
require_once('config/connection/connection.php');
require_once('config/connection/security.php');
require_once('config/function/data.php');

$secu = new Security;
$base = new DB;
$data = new Data;
$conn = $base->open();

// Generate nomor transfer otomatis: TRF/FKS/001/II/2026
$bulanAngka  = (int)date('n');
$tahun       = date('Y');
$romawiMap   = ['','I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
$bulanRomawi = $romawiMap[$bulanAngka];
$prefix      = "TRF/FKS/";
$suffix      = "/$bulanRomawi/$tahun";

// Auto-create tabel jika belum ada
try {
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
} catch(Exception $e){ /* sudah ada atau tidak ada privilege CREATE */ }

$nomor_transfer = $prefix . '001' . $suffix; // fallback default
try {
    $qLastNo = "SELECT nomor_transfer FROM transfer_faktur_stok
                WHERE nomor_transfer LIKE :pattern
                ORDER BY id_tfs DESC LIMIT 1";
    $stmtLast = $conn->prepare($qLastNo);
    $pattern  = $prefix . '%' . $suffix;
    $stmtLast->bindParam(':pattern', $pattern, PDO::PARAM_STR);
    $stmtLast->execute();
    $lastNo = $stmtLast->fetch(PDO::FETCH_ASSOC);

    if($lastNo){
        preg_match('/TRF\/FKS\/(\d+)\//', $lastNo['nomor_transfer'], $matches);
        $nextNum = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
    } else {
        $nextNum = 1;
    }
    $nomor_transfer = $prefix . str_pad($nextNum, 3, '0', STR_PAD_LEFT) . $suffix;
} catch(Exception $e){
    // Tabel belum ada — pakai fallback, akan dibuat saat action.php dijalankan
    $nomor_transfer = $prefix . '001' . $suffix;
}

// Load daftar gudang (ambil distinct dari produk_stokdetail)
$gudangList = [];
try {
    $qGudang = "SELECT DISTINCT gudang FROM produk_stokdetail WHERE gudang != '' AND gudang IS NOT NULL ORDER BY gudang ASC";
    $sgudang  = $conn->query($qGudang)->fetchAll(PDO::FETCH_ASSOC);
    foreach($sgudang as $g){ $gudangList[] = $g['gudang']; }
} catch(Exception $e){ $gudangList = ['Gudang Utama']; }
?>
<style>
.faktur-row-result { cursor:pointer; transition: background 0.2s; }
.faktur-row-result:hover, .faktur-row-result.active { background:#d4edda !important; }
.editable-cell { border:1px solid #ced4da; border-radius:4px; padding:3px 6px; }
.editable-cell:hover { border-color:#80bdff; }
.field-modified { background:#fff3cd !important; border-color:#ffc107 !important; }
#stepItems { display:none; }
</style>

<div class="card card-default">
    <div class="card-header card-header-border-bottom">
        <div class="row align-items-center">
            <div class="col">
                <h2><i class="fa fa-exchange-alt text-primary"></i> Transfer Faktur ke Stok</h2>
            </div>
            <div class="col-auto">
                <a href="../transferfakturstok" class="btn btn-secondary btn-sm">
                    <i class="fa fa-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
    </div>
    <div class="card-body">

        <!-- ======== STEP 1: CARI FAKTUR ======== -->
        <div class="card mb-3" id="stepCari">
            <div class="card-header bg-primary text-white py-2">
                <strong><i class="fa fa-search"></i> Step 1: Cari & Pilih Faktur</strong>
            </div>
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" id="inputCariFaktur" class="form-control"
                                   placeholder="Ketik nomor faktur atau nama outlet...">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="button" onclick="cariFaktur()">
                                    <i class="fa fa-search"></i> Cari
                                </button>
                            </div>
                        </div>
                        <small class="text-muted">Biarkan kosong untuk memuat daftar terbaru (30 data)</small>
                    </div>
                </div>
                <div class="table-responsive" style="max-height:320px; overflow-y:auto;">
                    <table class="table table-sm table-hover table-bordered" id="tableFakturResult">
                        <thead class="bg-light" style="position:sticky; top:0;">
                            <tr>
                                <th width="35"><center>#</center></th>
                                <th>Kode Faktur</th>
                                <th>Nama Outlet</th>
                                <th><center>Tgl. Faktur</center></th>
                                <th><center>Total Faktur</center></th>
                                <th><center>Status</center></th>
                                <th><center>Pilih</center></th>
                            </tr>
                        </thead>
                        <tbody id="bodyFakturResult">
                            <tr><td colspan="7" class="text-center text-muted">
                                <i class="fa fa-info-circle"></i> Klik tombol <strong>Cari</strong> untuk menampilkan daftar faktur.
                            </td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ======== STEP 2: ITEM & FORM TRANSFER ======== -->
        <div id="stepItems">
            <div class="card mb-3">
                <div class="card-header bg-success text-white py-2">
                    <strong><i class="fa fa-boxes"></i> Step 2: Pilih Item & Isi Detail Transfer</strong>
                </div>
                <div class="card-body">
                    <form id="formTransfer" autocomplete="off">
                        <input type="hidden" name="nmenu"          value="transferfakturstok">
                        <input type="hidden" name="nact"           value="do_transfer">
                        <input type="hidden" name="id_tfk"         id="input_id_tfk">
                        <input type="hidden" name="kode_faktur"    id="input_kode_faktur">
                        <input type="hidden" name="tgl_faktur"     id="input_tgl_faktur">
                        <input type="hidden" name="nama_outlet"    id="input_nama_outlet">

                        <div class="alert alert-warning py-2">
                            <i class="fa fa-exclamation-triangle"></i>
                            <strong>Perhatian:</strong> Item yang ditransfer akan <strong>mengurangi jumlah &amp; nominal</strong>
                            pada faktur asal. Pastikan qty transfer tidak melebihi jumlah di faktur.
                        </div>

                        <!-- Info Faktur -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label><strong>Faktur Terpilih</strong></label>
                                <input type="text" id="disp_kode_faktur" class="form-control bg-light" readonly>
                            </div>
                            <div class="col-md-3">
                                <label><strong>Outlet</strong></label>
                                <input type="text" id="disp_nama_outlet" class="form-control bg-light" readonly>
                            </div>
                            <div class="col-md-3">
                                <label><strong>Nomor Transfer</strong></label>
                                <input type="text" name="nomor_transfer" id="nomor_transfer"
                                       class="form-control bg-light"
                                       value="<?php echo htmlspecialchars($nomor_transfer); ?>" readonly>
                            </div>
                            <div class="col-md-3">
                                <label><strong>Keterangan</strong></label>
                                <input type="text" name="keterangan" class="form-control"
                                       placeholder="Keterangan (opsional)...">
                            </div>
                        </div>

                        <!-- Tabel Items -->
                        <div class="mb-2 d-flex justify-content-between align-items-center">
                            <strong><i class="fa fa-list"></i> Item Faktur</strong>
                            <span>
                                <button type="button" class="btn btn-xs btn-outline-primary"
                                        onclick="pilihSemua()">
                                    <i class="fa fa-check-square"></i> Pilih Semua
                                </button>
                                <button type="button" class="btn btn-xs btn-outline-secondary"
                                        onclick="batalPilih()">
                                    <i class="fa fa-square"></i> Batal Semua
                                </button>
                            </span>
                        </div>

                        <div class="table-responsive" style="max-height:400px; overflow-y:auto;">
                            <table class="table table-sm table-bordered table-hover" id="tableItems">
                                <thead class="bg-light" style="position:sticky; top:0;">
                                    <tr>
                                        <th width="35">
                                            <center><input type="checkbox" id="checkAll"
                                                           onchange="toggleAll(this)" checked></center>
                                        </th>
                                        <th>Produk</th>
                                        <th><center>Batch / Barcode<br><small class="text-info">(editable)</small></center></th>
                                        <th><center>Tgl. Expired<br><small class="text-info">(editable)</small></center></th>
                                        <th><center>Gudang<br><small class="text-info">(editable)</small></center></th>
                                        <th><center>Qty di Faktur</center></th>
                                        <th><center>Harga Satuan</center></th>
                                        <th><center>Diskon (%)</center></th>
                                        <th><center>Total Faktur</center></th>
                                        <th><center>Qty Transfer<br><small class="text-info">(editable)</small></center></th>
                                        <th><center>Perkiraan<br>Pengurangan</center></th>
                                    </tr>
                                </thead>
                                <tbody id="bodyItems">
                                    <tr id="rowLoading">
                                        <td colspan="11" class="text-center text-muted">
                                            Pilih faktur terlebih dahulu.
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="bg-light">
                                    <tr>
                                        <th colspan="9" class="text-right">Ringkasan Transfer:</th>
                                        <th colspan="2">
                                            <span id="totalItemSel">0</span> item,
                                            <span id="totalQtySel" class="badge badge-primary">0</span> pcs
                                            <br>
                                            <small>Pengurangan total: <strong id="totalPengurangan">Rp 0</strong></small>
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="text-right mt-3">
                            <button type="button" class="btn btn-secondary" onclick="resetForm()">
                                <i class="fa fa-times"></i> Batal
                            </button>
                            <button type="submit" id="btnSave" class="btn btn-success">
                                <i class="fa fa-check"></i> Proses Transfer
                            </button>
                        </div>
                        <div id="imgloading" class="text-center mt-2"></div>
                    </form>
                </div>
            </div>
        </div><!-- /stepItems -->

    </div>
</div>

<script>
var gudangOptions = <?php echo json_encode($gudangList); ?>;

// ======== Cari Faktur ========
function cariFaktur(){
    var q = $('#inputCariFaktur').val();
    $('#bodyFakturResult').html('<tr><td colspan="7" class="text-center"><i class="fa fa-spinner fa-spin"></i> Mencari...</td></tr>');
    $.ajax({
        url: usuper + '/ajax/transferfakturstok/getFaktur.php',
        type: 'GET',
        data: { q: q },
        dataType: 'json',
        success: function(res){
            if(res.status !== 'success' || !res.data.length){
                $('#bodyFakturResult').html('<tr><td colspan="7" class="text-center text-danger">Faktur tidak ditemukan.</td></tr>');
                return;
            }
            var html = '';
            $.each(res.data, function(i, f){
                var statusBadge = '<span class="badge badge-secondary">' + (f.status_tfk || '-') + '</span>';
                html += '<tr class="faktur-row-result" data-id_tfk="' + f.id_tfk  + '"'
                      + ' data-kode="' + escHtml(f.kode_tfk) + '"'
                      + ' data-tgl="' + (f.tgl_tfk || '') + '"'
                      + ' data-outlet="' + escHtml(f.nama_out || '') + '"'
                      + ' onclick="pilihFaktur(this)">'
                      + '<td><center>' + (i+1) + '</center></td>'
                      + '<td><strong>' + escHtml(f.kode_tfk) + '</strong></td>'
                      + '<td>' + escHtml(f.nama_out || '-') + '</td>'
                      + '<td><center>' + (f.tgl_tfk_fmt || '-') + '</center></td>'
                      + '<td class="text-right">' + (f.total_tfk_fmt || '-') + '</td>'
                      + '<td><center>' + statusBadge + '</center></td>'
                      + '<td><center><button class="btn btn-xs btn-success"><i class="fa fa-check"></i> Pilih</button></center></td>'
                      + '</tr>';
            });
            $('#bodyFakturResult').html(html);
        },
        error: function(){
            $('#bodyFakturResult').html('<tr><td colspan="7" class="text-center text-danger">Gagal menghubungi server.</td></tr>');
        }
    });
}

// ======== Pilih Faktur → Load Items ========
function pilihFaktur(row){
    var $row    = $(row);
    var id_tfk  = $row.data('id_tfk');
    var kode    = $row.data('kode');
    var tgl     = $row.data('tgl');
    var outlet  = $row.data('outlet');

    $('.faktur-row-result').removeClass('active');
    $row.addClass('active');

    $('#input_id_tfk').val(id_tfk);
    $('#input_kode_faktur').val(kode);
    $('#input_tgl_faktur').val(tgl);
    $('#input_nama_outlet').val(outlet);
    $('#disp_kode_faktur').val(kode);
    $('#disp_nama_outlet').val(outlet);

    // Load items
    $('#bodyItems').html('<tr><td colspan="11" class="text-center"><i class="fa fa-spinner fa-spin"></i> Memuat item...</td></tr>');
    $('#stepItems').slideDown();

    $.ajax({
        url: usuper + '/ajax/transferfakturstok/getItems.php',
        type: 'GET',
        data: { id_tfk: id_tfk },
        dataType: 'json',
        success: function(res){
            if(res.status !== 'success' || !res.data.length){
                $('#bodyItems').html('<tr><td colspan="11" class="text-center text-danger">' + (res.message || 'Tidak ada item') + '</td></tr>');
                return;
            }
            renderItemRows(res.data);
        },
        error: function(){
            $('#bodyItems').html('<tr><td colspan="11" class="text-center text-danger">Gagal memuat item.</td></tr>');
        }
    });
}

function renderItemRows(items){
    var html = '';
    // Build gudang <option> list
    var gudangOpts = '<option value="">-- Pilih Gudang --</option>';
    $.each(gudangOptions, function(i, g){
        gudangOpts += '<option value="' + escHtml(g) + '">' + escHtml(g) + '</option>';
    });

    $.each(items, function(idx, item){
        var n = idx + 1;
        var tglExpVal = item.tgl_expired || '';
        html += '<tr data-n="' + n + '">'
              + '<td><center>'
              + '<input type="checkbox" name="items[' + n + '][selected]" value="1" class="item-chk" checked '
              + 'onchange="updateTotal()">'
              + '<input type="hidden" name="items[' + n + '][id_tfd]"  value="' + item.id_tfd  + '">'
              + '<input type="hidden" name="items[' + n + '][id_pro]"  value="' + escHtml(item.id_pro)  + '">'
              + '<input type="hidden" name="items[' + n + '][harga_tfd]" value="' + item.harga_tfd + '" class="h-harga">'
              + '<input type="hidden" name="items[' + n + '][diskon_tfd]" value="' + item.diskon_tfd + '" class="h-diskon">'
              + '<input type="hidden" name="items[' + n + '][jumlah_asal]" value="' + item.jumlah_tfd + '" class="h-jumlah-asal">'
              + '</center></td>'

              // Produk
              + '<td><small class="text-muted">' + escHtml(item.kode_produk_jadi||'') + '</small><br>'
              + '<strong>' + escHtml(item.nama_pro||'-') + '</strong></td>'

              // Batch
              + '<td><input type="text" name="items[' + n + '][no_bcode]" '
              + 'class="form-control form-control-sm editable-cell batch-input" '
              + 'value="' + escHtml(item.no_bcode||'') + '" placeholder="Batch/Barcode...">'
              + '</td>'

              // Expired
              + '<td><center><input type="date" name="items[' + n + '][tgl_expired]" '
              + 'class="form-control form-control-sm editable-cell exp-input" '
              + 'value="' + tglExpVal + '" style="width:140px;"></center></td>'

              // Gudang
              + '<td>'
              + '<select name="items[' + n + '][gudang]" class="form-control form-control-sm editable-cell gudang-sel">'
              + gudangOpts
              + '</select>'
              + '</td>'

              // Qty di faktur
              + '<td><center><span class="badge badge-secondary">' + item.jumlah_tfd + '</span></center></td>'

              // Harga satuan
              + '<td class="text-right"><small>Rp ' + item.harga_fmt + '</small></td>'

              // Diskon
              + '<td><center>' + item.diskon_tfd + '%</center></td>'

              // Total faktur
              + '<td class="text-right"><small>Rp ' + item.total_fmt + '</small></td>'

              // Qty transfer
              + '<td><center><input type="number" name="items[' + n + '][jumlah_transfer]" '
              + 'class="form-control form-control-sm text-center qty-transfer" '
              + 'value="' + item.jumlah_tfd + '" '
              + 'min="1" max="' + item.jumlah_tfd + '" style="width:80px;" '
              + 'oninput="hitungPerkiraan(this)" onchange="hitungPerkiraan(this)">'
              + '</center></td>'

              // Perkiraan pengurangan
              + '<td class="text-right text-danger perkiraan-cell">'
              + '<small>Rp ' + item.total_fmt + '</small>'
              + '</td>'

              + '</tr>';
    });
    $('#bodyItems').html(html);

    // Set gudang dari data jika ada
    $.each(items, function(idx, item){
        var n = idx+1;
        if(item.gudang){
            $('select[name="items['+n+'][gudang]"]').val(item.gudang);
        }
    });

    updateTotal();

    // Highlight saat edit
    $('.editable-cell').on('focus', function(){ $(this).addClass('field-modified'); })
                       .on('blur',  function(){ /* keep highlight */ });
}

function hitungPerkiraan(el){
    var $row       = $(el).closest('tr');
    var qty        = parseInt($(el).val()) || 0;
    var harga      = parseFloat($row.find('.h-harga').val()) || 0;
    var diskon     = parseFloat($row.find('.h-diskon').val()) || 0;
    var pengurangan = harga * qty * (1 - diskon/100);
    $row.find('.perkiraan-cell').html('<small>Rp ' + numberFmt(Math.round(pengurangan)) + '</small>');
    updateTotal();
}

function updateTotal(){
    var totalItem = 0;
    var totalQty  = 0;
    var totalPengurangan = 0;

    $('.item-chk:checked').each(function(){
        totalItem++;
        var $row = $(this).closest('tr');
        var qty  = parseInt($row.find('.qty-transfer').val()) || 0;
        var harga   = parseFloat($row.find('.h-harga').val()) || 0;
        var diskon  = parseFloat($row.find('.h-diskon').val()) || 0;
        totalQty += qty;
        totalPengurangan += harga * qty * (1 - diskon/100);
    });

    $('#totalItemSel').text(totalItem);
    $('#totalQtySel').text(totalQty.toLocaleString('id-ID'));
    $('#totalPengurangan').text('Rp ' + numberFmt(Math.round(totalPengurangan)));

    var allChecked = $('.item-chk:checked').length === $('.item-chk').length;
    $('#checkAll').prop('checked', allChecked);
}

function toggleAll(cb){
    $('.item-chk').prop('checked', cb.checked);
    updateTotal();
}
function pilihSemua(){ $('#checkAll').prop('checked',true); $('.item-chk').prop('checked',true); updateTotal(); }
function batalPilih(){ $('#checkAll').prop('checked',false); $('.item-chk').prop('checked',false); updateTotal(); }

function resetForm(){
    $('.faktur-row-result').removeClass('active');
    $('#stepItems').slideUp();
    $('#bodyItems').html('<tr><td colspan="11" class="text-muted text-center">Pilih faktur terlebih dahulu.</td></tr>');
    $('#input_id_tfk,#input_kode_faktur,#input_tgl_faktur,#input_nama_outlet,#disp_kode_faktur,#disp_nama_outlet').val('');
    updateTotal();
}

// ======== Submit Transfer ========
$('#formTransfer').on('submit', function(e){
    e.preventDefault();

    var selectedCount = $('.item-chk:checked').length;
    if(selectedCount === 0){
        swal("Perhatian", "Pilih minimal 1 item untuk ditransfer!", "warning");
        return;
    }

    // Validasi batch, expired, gudang untuk item terpilih
    var valid = true;
    var errMsg = '';
    $('.item-chk:checked').each(function(){
        var $row  = $(this).closest('tr');
        var batch = $row.find('.batch-input').val().trim();
        var exp   = $row.find('.exp-input').val().trim();
        var gudang= $row.find('.gudang-sel').val();
        var qty   = parseInt($row.find('.qty-transfer').val()) || 0;
        var maks  = parseInt($row.find('.h-jumlah-asal').val()) || 0;
        if(!batch){ valid = false; errMsg = 'Batch/Barcode wajib diisi untuk item yang dipilih!'; return false; }
        if(!exp)  { valid = false; errMsg = 'Tanggal Expired wajib diisi untuk item yang dipilih!'; return false; }
        if(!gudang){ valid = false; errMsg = 'Gudang wajib dipilih untuk item yang dipilih!'; return false; }
        if(qty <= 0){ valid = false; errMsg = 'Qty Transfer harus > 0!'; return false; }
        if(qty > maks){ valid = false; errMsg = 'Qty Transfer melebihi jumlah di faktur (' + maks + ')!'; return false; }
    });

    if(!valid){
        swal("Validasi Gagal", errMsg, "error");
        return;
    }

    swal({
        title: "Konfirmasi Transfer",
        text: "Apakah Anda yakin akan memproses transfer? Jumlah pada faktur akan berkurang.",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#28a745",
        confirmButtonText: "Ya, Proses!",
        cancelButtonText: "Batal"
    }, function(confirmed){
        if(!confirmed) return;

        var $btn = $('#btnSave');
        $btn.prop('disabled', true);
        $('#imgloading').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i><br><small>Memproses...</small></div>');

        $.ajax({
            url: usuper + '/modal/transferfakturstok/action.php',
            type: 'POST',
            data: new FormData($('#formTransfer')[0]),
            contentType: false,
            cache: false,
            processData: false,
            success: function(res){
                if(res === 'success'){
                    swal({
                        title: "Berhasil!",
                        text: "Transfer berhasil diproses. Nominal faktur telah diperbarui.",
                        type: "success",
                        timer: 2500,
                        showConfirmButton: false
                    }, function(){
                        window.location.href = usuper + '/transferfakturstok';
                    });
                } else {
                    swal("Gagal!", res, "error");
                    $btn.prop('disabled', false);
                    $('#imgloading').html('');
                }
            },
            error: function(){
                swal("Error", "Koneksi ke server gagal, silakan coba lagi.", "error");
                $btn.prop('disabled', false);
                $('#imgloading').html('');
            }
        });
    });
});

// ======== Helper ========
function escHtml(str){ return $('<div>').text(str).html(); }
function numberFmt(n){ return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'); }

// Enter di kotak cari faktur
$('#inputCariFaktur').on('keydown', function(e){
    if(e.key === 'Enter'){ e.preventDefault(); cariFaktur(); }
});

// Auto load saat halaman terbuka
$(document).ready(function(){ cariFaktur(); });
</script>
