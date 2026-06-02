<?php
/**
 * Form Tambah Transfer Stock Cancel
 * Pilih faktur dan transfer stok cancel ke inventory tujuan
 */
require_once('config/connection/connection.php');
require_once('config/connection/security.php');
require_once('config/function/data.php');

$secu   = new Security;
$base   = new DB;
$data   = new Data;
$conn   = $base->open();

// Generate Nomor Transfer: TRF/STK/001/XX/XXXX (XX = bulan romawi)
$bulanAngka = date('n');
$tahun = date('Y');
$romawiMap = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
$bulanRomawi = $romawiMap[$bulanAngka];
$prefix = "TRF/STK/";
$suffix = "/$bulanRomawi/$tahun";

// Get last nomor transfer for current month/year
$qLastNo = "SELECT nomor_transfer FROM transfer_stockcancel 
            WHERE nomor_transfer LIKE :pattern 
            ORDER BY id_tsc DESC LIMIT 1";
$stmtLastNo = $conn->prepare($qLastNo);
$pattern = $prefix . '%' . $suffix;
$stmtLastNo->bindParam(':pattern', $pattern, PDO::PARAM_STR);
$stmtLastNo->execute();
$lastNo = $stmtLastNo->fetch(PDO::FETCH_ASSOC);

if($lastNo){
    // Extract number from last nomor_transfer
    // Format: TRF/STK/001/II/2026
    preg_match('/TRF\/STK\/(\d+)\//', $lastNo['nomor_transfer'], $matches);
    $nextNum = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
} else {
    $nextNum = 1;
}

$nomor_transfer = $prefix . str_pad($nextNum, 3, '0', STR_PAD_LEFT) . $suffix;

// Query untuk mengambil daftar faktur yang punya item pending
$qFaktur = "SELECT 
                kode_faktur,
                tgl_faktur,
                MAX(cancel_at) as cancel_at,
                COUNT(*) as total_item,
                SUM(jumlah_cancel) as total_qty
            FROM produk_stockdetail_cancel
            WHERE status = 'cancel'
            GROUP BY kode_faktur, tgl_faktur
            ORDER BY cancel_at DESC";
$faktur = $conn->prepare($qFaktur);
$faktur->execute();
$fakturList = $faktur->fetchAll(PDO::FETCH_ASSOC);

// Load daftar produk untuk dropdown
$qProduk = "SELECT id_pro, nama_pro, kode_produk_jadi FROM produk WHERE status_pro = 'active' ORDER BY nama_pro ASC";
$produk = $conn->prepare($qProduk);
$produk->execute();
$produkList = $produk->fetchAll(PDO::FETCH_ASSOC);
$produkListJson = json_encode($produkList);

// Tidak perlu load inventory - transfer otomatis ke gudang asal
?>
<style>
    .editable-field {
        border: 1px solid #ced4da;
        transition: all 0.3s ease;
    }
    .editable-field:hover {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.1rem rgba(0,123,255,.25);
        cursor: text;
    }
    .editable-field.is-invalid {
        border-color: #dc3545;
        background-color: #f8d7da;
    }
    .field-modified {
        background-color: #d1ecf1 !important;
        border-color: #0c5460 !important;
    }
    .produk-transfer-select {
        font-size: 0.875rem;
        border: 1px solid #ced4da;
        transition: all 0.3s ease;
    }
    .produk-transfer-select:hover {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.1rem rgba(0,123,255,.25);
    }
    .produk-transfer-select.field-modified {
        background-color: #fff3cd !important;
        border-color: #ffc107 !important;
        font-weight: bold;
    }
</style>
<div class="card card-default">
    <div class="card-header card-header-border-bottom">
        <div class="row">
            <div class="col">
                <h2>Tambah Transfer Stock Cancel</h2>
            </div>
            <div class="col-auto">
                <a href="../transferstockcancel" class="btn btn-secondary">
                    <i class="fa fa-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if(!empty($fakturList)): ?>
        
        <!-- Step 1: Pilih Faktur -->
        <div class="card mb-3" id="stepPilihFaktur">
            <div class="card-header bg-primary text-white">
                <strong><i class="fa fa-file-invoice"></i> Step 1: Pilih Faktur</strong>
            </div>
            <div class="card-body">
                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-sm table-hover table-bordered" id="tableFaktur">
                        <thead class="bg-light" style="position: sticky; top: 0;">
                            <tr>
                                <th width="40"><center><input type="radio" disabled></center></th>
                                <th>Kode Faktur</th>
                                <th><center>Tgl. Faktur</center></th>
                                <th><center>Tgl. Cancel</center></th>
                                <th><center>Total Item</center></th>
                                <th><center>Total Qty</center></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            foreach($fakturList as $fkt): 
                                $tglFaktur = !empty($fkt['tgl_faktur']) ? date('d-m-Y', strtotime($fkt['tgl_faktur'])) : '-';
                                $cancelAt = !empty($fkt['cancel_at']) ? date('d-m-Y H:i', strtotime($fkt['cancel_at'])) : '-';
                            ?>
                            <tr class="faktur-row" data-faktur="<?php echo htmlspecialchars($fkt['kode_faktur']); ?>" data-tglfaktur="<?php echo $fkt['tgl_faktur']; ?>" style="cursor: pointer;">
                                <td>
                                    <center>
                                        <input type="radio" name="pilih_faktur" value="<?php echo htmlspecialchars($fkt['kode_faktur']); ?>" class="radio-faktur">
                                    </center>
                                </td>
                                <td><strong><?php echo $fkt['kode_faktur']; ?></strong></td>
                                <td><center><?php echo $tglFaktur; ?></center></td>
                                <td><center><?php echo $cancelAt; ?></center></td>
                                <td><center><span class="badge badge-primary"><?php echo $data->angka($fkt['total_item']); ?></span></center></td>
                                <td><center><span class="badge badge-danger"><?php echo $data->angka($fkt['total_qty']); ?></span></center></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Step 2: Detail Item & Transfer -->
        <div class="card mb-3" id="stepTransfer" style="display: none;">
            <div class="card-header bg-success text-white">
                <strong><i class="fa fa-exchange-alt"></i> Step 2: Transfer Stok</strong>
            </div>
            <div class="card-body">
                <form id="formTransfer" method="post" autocomplete="off">
                    <input type="hidden" name="nmenu" value="transferstockcancel">
                    <input type="hidden" name="nact" value="transfer_batch">
                    <input type="hidden" name="kode_faktur" id="input_kode_faktur">
                    <input type="hidden" name="tgl_faktur" id="input_tgl_faktur">
                    
                    <div class="alert alert-info py-2 mb-3">
                        <i class="fa fa-info-circle"></i> <strong>Info:</strong> Stok akan otomatis ditransfer kembali ke <strong>gudang asal</strong> sesuai data pada tabel cancel.
                    </div>
                    
                    <div class="alert alert-warning py-2 mb-3">
                        <i class="fa fa-edit"></i> <strong>Catatan:</strong> Anda dapat <strong>mengedit Batch/Barcode, Expired Date, Qty Transfer, dan menambah baris (split batch)</strong>. Untuk split batch, klik tombol <strong>[+]</strong> lalu isi batch baru dengan qty yang diinginkan.
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label><strong>Faktur Terpilih</strong></label>
                            <input type="text" id="display_faktur" class="form-control" readonly>
                        </div>
                        <div class="col-md-4">
                            <label><strong>Nomor Transfer</strong> <span class="text-danger">*</span></label>
                            <input type="text" name="nomor_transfer" id="nomor_transfer" class="form-control bg-light" value="<?php echo $nomor_transfer; ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label><strong>Keterangan</strong></label>
                            <input type="text" name="keterangan_transfer" class="form-control" placeholder="Keterangan (opsional)...">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong><i class="fa fa-boxes"></i> Daftar Item</strong>
                            <div>
                                <button type="button" class="btn btn-xs btn-outline-primary" onclick="selectAllItems()"><i class="fa fa-check-square"></i> Pilih Semua</button>
                                <button type="button" class="btn btn-xs btn-outline-secondary" onclick="deselectAllItems()"><i class="fa fa-square"></i> Batal Pilih</button>
                            </div>
                        </div>
                        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-sm table-hover table-bordered" id="tableItems">
                                <thead class="bg-light" style="position: sticky; top: 0;">
                                    <tr>
                                        <th width="40"><center><input type="checkbox" id="checkAll" onchange="toggleAllItems(this)" checked></center></th>
                                        <th>Produk</th>
                                        <th>Batch/Barcode <small class="text-info">(editable)</small></th>
                                        <th><center>Expired <small class="text-info">(editable)</small></center></th>
                                        <th><center>Qty Cancel</center></th>
                                        <th><center>Qty Transfer <small class="text-info">(editable)</small></center></th>
                                        <th>Gudang Asal</th>
                                        <th width="60"><center>Aksi</center></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody">
                                    <!-- Loaded via AJAX -->
                                </tbody>
                                <tfoot class="bg-light">
                                    <tr>
                                        <th colspan="4" class="text-right">Total Transfer:</th>
                                        <th><center><span id="totalItemSelected">0</span> item</center></th>
                                        <th><center><span id="totalQtyTransfer" class="badge badge-primary">0</span> pcs</center></th>
                                        <th colspan="2"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    
                    <div class="text-right">
                        <button type="button" class="btn btn-secondary" onclick="resetForm()"><i class="fa fa-times"></i> Batal</button>
                        <button type="submit" id="btnSave" class="btn btn-primary"><i class="fa fa-check"></i> Transfer Stok Terpilih</button>
                        <div id="imgloading" class="mt-2"></div>
                    </div>
                </form>
            </div>
        </div>
        
        <?php else: ?>
        <div class="alert alert-warning">
            <i class="fa fa-exclamation-triangle"></i> Tidak ada stok cancel yang pending untuk ditransfer.
            <br><small>Stok cancel akan muncul ketika faktur dihapus dengan tanggal beda bulan.</small>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Global variable untuk daftar produk
var produkList = <?php echo $produkListJson; ?>;

$(document).ready(function(){
    // Click row to select faktur
    $('.faktur-row').on('click', function(){
        var radio = $(this).find('.radio-faktur');
        radio.prop('checked', true);
        
        var kodeFaktur = $(this).data('faktur');
        var tglFaktur = $(this).data('tglfaktur');
        
        loadItems(kodeFaktur, tglFaktur);
    });
    
    // Form submit
    $('#formTransfer').on('submit', function(e){
        e.preventDefault();
        
        var selectedItems = $('.item-checkbox:checked').length;
        if(selectedItems == 0){
            swal("Error", "Pilih minimal 1 item untuk ditransfer!", "error");
            return;
        }
        
        // Validasi batch/barcode tidak boleh kosong
        var invalidBatch = false;
        $('.item-checkbox:checked').each(function() {
            var row = $(this).closest('tr');
            var batch = row.find('input[name*="[no_bcode]"]').val().trim();
            if(batch == ''){
                invalidBatch = true;
                row.find('input[name*="[no_bcode]"]').addClass('is-invalid');
            } else {
                row.find('input[name*="[no_bcode]"]').removeClass('is-invalid');
            }
        });
        
        if(invalidBatch){
            swal("Error", "Batch/Barcode tidak boleh kosong pada item yang dipilih!", "error");
            return;
        }
        
        var btn = $('#btnSave');
        btn.prop('disabled', true);
        $('#imgloading').html('<img src="'+usuper+'/berkas/gif/tunggu.gif" style="width:15%;" />');
        
        $.ajax({
            url: usuper+'/modal/transferstockcancel/action.php',
            type: 'POST',
            async: true,
            dataType: 'text',
            data: new FormData(this),
            contentType: false,
            cache: false,
            processData: false,
            beforeSend: function(){},
            success: function(data){
                if(data == 'success'){
                    swal({
                        title: "Selamat!",
                        text: "Data berhasil ditransfer...",
                        type: "success",
                        timer: 2000,
                        showCancelButton: false,
                        showConfirmButton: false
                    }, function(){
                        window.location.href = usuper+'/transferstockcancel';
                    });
                } else {
                    swal("Gagal!", data, "error");
                }
            },
            complete: function(data){
                btn.prop('disabled', false);
                $('#imgloading').html('');
            }
        });
    });
});

function loadItems(kodeFaktur, tglFaktur){
    $('#input_kode_faktur').val(kodeFaktur);
    $('#input_tgl_faktur').val(tglFaktur);
    $('#display_faktur').val(kodeFaktur);
    
    $('#itemsBody').html('<tr><td colspan="8" class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</td></tr>');
    $('#stepTransfer').slideDown();
    
    $.ajax({
        url: '../ajax/transferstockcancel/getItems.php?kode_faktur=' + encodeURIComponent(kodeFaktur),
        type: 'GET',
        dataType: 'json',
        success: function(response){
            if(response.status == 'success'){
                var html = '';
                var no = 0;
                $.each(response.data, function(i, item){
                    no++;
                    var tglExp = item.tgl_expired_formatted || '-';
                    var tglExpValue = item.tgl_expired || '';
                    
                    html += '<tr class="parent-row" data-parent-no="'+no+'">';
                    html += '<td><center>';
                    html += '<input type="checkbox" name="items['+no+'][selected]" value="1" class="item-checkbox" checked>';
                    html += '<input type="hidden" name="items['+no+'][id_psc]" value="'+item.id_psc+'">';
                    html += '<input type="hidden" name="items['+no+'][id_pro]" value="'+item.id_pro+'">';
                    html += '<input type="hidden" name="items['+no+'][id_trd]" value="'+(item.id_trd || '')+'">';
                    html += '<input type="hidden" name="items['+no+'][jumlah_cancel]" value="'+item.jumlah_cancel+'" class="original-qty">';
                    html += '<input type="hidden" name="items['+no+'][gudang]" value="'+(item.gudang || '')+'">';
                    html += '<input type="hidden" name="items['+no+'][parent_no]" value="'+no+'">';
                    html += '</center></td>';
                    html += '<td><small class="text-muted">'+(item.kode_produk_jadi || '')+'</small><br>'+(item.nama_pro || '-')+'</td>';
                    html += '<td><input type="text" name="items['+no+'][no_bcode]" class="form-control form-control-sm editable-field" value="'+(item.no_bcode || '')+'" placeholder="Batch/Barcode..." title="Klik untuk edit batch/barcode" data-original="'+(item.no_bcode || '')+'"></td>';
                    html += '<td><center><input type="date" name="items['+no+'][tgl_expired]" class="form-control form-control-sm text-center editable-field" value="'+tglExpValue+'" style="width:140px;" title="Klik untuk edit tanggal expired" data-original="'+tglExpValue+'"></center></td>';
                    html += '<td><center><span class="badge badge-danger qty-cancel-badge">'+item.jumlah_cancel_formatted+'</span></center></td>';
                    html += '<td><center><input type="number" name="items['+no+'][jumlah_transfer]" class="form-control form-control-sm text-center qty-transfer editable-field" value="'+item.jumlah_cancel+'" min="1" style="width:80px;" onchange="updateTotal()" title="Klik untuk edit jumlah transfer" data-original="'+item.jumlah_cancel+'" data-parent-no="'+no+'"></center></td>';
                    html += '<td><small>'+(item.nama_gudang || item.gudang || '-')+'</small></td>';
                    html += '<td><center><button type="button" class="btn btn-xs btn-success btn-split" onclick="addSplitRow('+no+')" title="Split batch menjadi 2 baris"><i class="fa fa-plus"></i></button></center></td>';
                    html += '</tr>';
                });
                
                $('#itemsBody').html(html);
                updateTotal();
                
                // Bind checkbox change dengan parent-child relationship logic
                $('.item-checkbox').on('change', function() {
                    var $row = $(this).closest('tr');
                    var parentNo = $row.data('parent-no');
                    
                    // Jika ini parent row dan unchecked, uncheck semua splits
                    if($row.hasClass('parent-row') && !this.checked) {
                        $('tr.split-row[data-parent-no="'+parentNo+'"]').each(function(){
                            $(this).find('.item-checkbox').prop('checked', false);
                        });
                    }
                    
                    // Jika ini split row dan checked, check juga parent
                    if($row.hasClass('split-row') && this.checked) {
                        $('tr.parent-row[data-parent-no="'+parentNo+'"]').find('.item-checkbox').prop('checked', true);
                    }
                    
                    updateTotal();
                });
                
                // Tambahkan highlight saat field diedit
                $('.editable-field').on('focus', function(){
                    $(this).css('background-color', '#fff3cd');
                }).on('blur', function(){
                    $(this).css('background-color', '');
                }).on('change input', function(){
                    var originalValue = $(this).data('original');
                    var currentValue = $(this).val();
                    if(originalValue != currentValue){
                        $(this).addClass('field-modified');
                    } else {
                        $(this).removeClass('field-modified');
                    }
                    // Remove invalid class when user types
                    $(this).removeClass('is-invalid');
                });
            } else {
                $('#itemsBody').html('<tr><td colspan="8" class="text-center text-danger">'+response.message+'</td></tr>');
            }
        },
        error: function(xhr, status, error){
            console.log('Error:', xhr.responseText);
            $('#itemsBody').html('<tr><td colspan="8" class="text-center text-danger">Gagal memuat data: '+error+'</td></tr>');
        }
    });
}

// Counter untuk split row
var splitCounter = {};

// Function untuk menambah baris split batch
function addSplitRow(parentNo){
    // Initialize counter jika belum ada
    if(!splitCounter[parentNo]){
        splitCounter[parentNo] = 1;
    } else {
        splitCounter[parentNo]++;
    }
    
    var splitNo = splitCounter[parentNo];
    var parentRow = $('tr[data-parent-no="'+parentNo+'"]').first();
    
    // Get data from parent
    var id_psc = parentRow.find('input[name*="[id_psc]"]').val();
    var id_pro = parentRow.find('input[name*="[id_pro]"]').val();
    var id_trd = parentRow.find('input[name*="[id_trd]"]').val();
    var gudang = parentRow.find('input[name*="[gudang]"]').val();
    var jumlah_cancel = parentRow.find('.original-qty').val();
    var produk_text = parentRow.find('td:eq(1)').html();
    var gudang_text = parentRow.find('td:eq(6)').text();
    
    // Create unique ID untuk split row
    var uniqueId = parentNo + '_split_' + splitNo;
    
    var rowHtml = '<tr class="split-row bg-light" data-parent-no="'+parentNo+'" data-split-id="'+uniqueId+'">';
    rowHtml += '<td><center>';
    rowHtml += '<input type="checkbox" name="items['+uniqueId+'][selected]" value="1" class="item-checkbox" checked>';
    rowHtml += '<input type="hidden" name="items['+uniqueId+'][id_psc]" value="'+id_psc+'">';
    rowHtml += '<input type="hidden" name="items['+uniqueId+'][id_pro]" value="'+id_pro+'">';
    rowHtml += '<input type="hidden" name="items['+uniqueId+'][id_trd]" value="'+id_trd+'">';
    rowHtml += '<input type="hidden" name="items['+uniqueId+'][jumlah_cancel]" value="'+jumlah_cancel+'">';
    rowHtml += '<input type="hidden" name="items['+uniqueId+'][gudang]" value="'+gudang+'">';
    rowHtml += '<input type="hidden" name="items['+uniqueId+'][parent_no]" value="'+parentNo+'">';
    rowHtml += '<input type="hidden" name="items['+uniqueId+'][is_split]" value="1">';
    rowHtml += '</center></td>';
    rowHtml += '<td><small class="text-info"><i class="fa fa-level-up-alt fa-rotate-90"></i> Split dari baris '+parentNo+'</small><br>'+produk_text+'</td>';
    rowHtml += '<td><input type="text" name="items['+uniqueId+'][no_bcode]" class="form-control form-control-sm editable-field" placeholder="Batch baru..." required></td>';
    rowHtml += '<td><center><input type="date" name="items['+uniqueId+'][tgl_expired]" class="form-control form-control-sm text-center editable-field" style="width:140px;"></center></td>';
    rowHtml += '<td><center><span class="badge badge-secondary">-</span></center></td>';
    rowHtml += '<td><center><input type="number" name="items['+uniqueId+'][jumlah_transfer]" class="form-control form-control-sm text-center qty-transfer editable-field" value="0" min="1" style="width:80px;" onchange="updateTotal()" data-parent-no="'+parentNo+'"></center></td>';
    rowHtml += '<td><small>'+gudang_text+'</small></td>';
    rowHtml += '<td><center><button type="button" class="btn btn-xs btn-danger" onclick="removeSplitRow(\''+uniqueId+'\')" title="Hapus baris split"><i class="fa fa-times"></i></button></center></td>';
    rowHtml += '</tr>';
    
    // Insert after last row with same parent_no
    var lastRow = $('tr[data-parent-no="'+parentNo+'"]').last();
    lastRow.after(rowHtml);
    
    // Bind events untuk row baru
    $('tr[data-split-id="'+uniqueId+'"]').find('.editable-field').on('focus', function(){
        $(this).css('background-color', '#fff3cd');
    }).on('blur', function(){
        $(this).css('background-color', '');
    }).on('change input', function(){
        $(this).removeClass('is-invalid');
    });
    
    // Bind checkbox dengan parent-child relationship logic
    $('tr[data-split-id="'+uniqueId+'"]').find('.item-checkbox').on('change', function() {
        var $row = $(this).closest('tr');
        var parentNo = $row.data('parent-no');
        
        // Jika ini split row dan checked, check juga parent
        if($row.hasClass('split-row') && this.checked) {
            $('tr.parent-row[data-parent-no="'+parentNo+'"]').find('.item-checkbox').prop('checked', true);
        }
        
        updateTotal();
    });
    
    updateTotal();
    
    // Show notification
    swal({
        title: "Baris Split Ditambahkan",
        text: "Isi batch dan qty untuk baris baru",
        type: "info",
        timer: 1500,
        showConfirmButton: false
    });
}

// Function untuk menghapus baris split
function removeSplitRow(splitId){
    $('tr[data-split-id="'+splitId+'"]').remove();
    updateTotal();
}

function toggleAllItems(checkbox) {
    $('.item-checkbox').prop('checked', checkbox.checked);
    updateTotal();
}

function selectAllItems() {
    $('#checkAll').prop('checked', true);
    $('.item-checkbox').prop('checked', true);
    updateTotal();
}

function deselectAllItems() {
    $('#checkAll').prop('checked', false);
    $('.item-checkbox').prop('checked', false);
    updateTotal();
}

function updateTotal() {
    var totalItems = 0;
    var totalQty = 0;
    
    $('.item-checkbox:checked').each(function() {
        totalItems++;
        var row = $(this).closest('tr');
        var qtyTransfer = parseInt(row.find('.qty-transfer').val()) || 0;
        totalQty += qtyTransfer;
    });
    
    $('#totalItemSelected').text(totalItems);
    $('#totalQtyTransfer').text(totalQty.toLocaleString('id-ID'));
    
    var allChecked = $('.item-checkbox:checked').length === $('.item-checkbox').length;
    $('#checkAll').prop('checked', allChecked);
}

function resetForm(){
    $('.radio-faktur').prop('checked', false);
    $('#stepTransfer').slideUp();
    $('#itemsBody').html('');
    $('#input_kode_faktur').val('');
    $('#input_tgl_faktur').val('');
    $('#display_faktur').val('');
}
</script>
