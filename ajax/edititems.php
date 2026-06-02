<?php
	$uniq	= $secu->injection(@$_GET['keycode']);
	$code	= base64_decode($uniq);
	$read	= $conn->prepare("SELECT sj_tfk, id_out, kode_tfk, status_tfk FROM transaksi_faktur WHERE id_tfk=:code");
	$read->bindParam(':code', $code, PDO::PARAM_STR);
	$read->execute();
	$view	= $read->fetch(PDO::FETCH_ASSOC);
?>
<form id="formsalespnp" action="#" method="post" autocomplete="off">
<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Penjualan</a></li>
                <li class="breadcrumb-item active" aria-current="page">Faktur Penjualan</li>
            </ol>
        </nav>
        <h4 class="content-title">Edit Item - Faktur Penjualan</h4>
        <h5>SJ : <?php echo($view['sj_tfk']); ?> | Faktur : <?php echo($view['kode_tfk']); ?></h5>
    </div>
</div>
<div class="content-body">
    <div class="component-section no-code">
        <h5 id="section1" class="tx-semibold"><?php echo($data->sistem('pt_sis')); ?></h5>
        <div style="margin-top:10px; margin-bottom:25px;">
            <div>Izin PBF No : <?php echo($data->sistem('pbf_sis')); ?></div>
            <div>NPWP No : <?php echo($data->sistem('npwp_sis')); ?></div>
            <div>Alamat : <?php echo($data->sistem('alamat_sis')); ?></div>
        </div>
        <input type="hidden" name="outlet" id="outlet" value="<?php echo($view['id_out']); ?>" readonly="readonly" />
        <input type="hidden" name="namamodal" id="namamodal" value="fsales" readonly="readonly" />
        <input type="hidden" name="namamenu" value="edit_item" readonly="readonly" />
        <input type="hidden" name="keycode" value="<?php echo($uniq); ?>" readonly="readonly" />
        <input type="hidden" name="nomorfaktur" value="<?php echo($view['kode_tfk']); ?>" readonly="readonly" />
        
        <div class="row row-sm mg-b-25">
            <div class="col-sm-12">
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> 
                    <strong>Info:</strong> Anda sedang mengedit item faktur yang sudah ada. Perubahan akan mempengaruhi stok produk.
                    Status faktur saat ini: <strong><?php echo($view['status_tfk']); ?></strong>
                </div>
            </div>
        </div>
        
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <h5 id="section1" class="tx-semibold">Item Produk Saat Ini</h5>
        <p class="mg-b-25">Edit produk yang sudah ada atau tambah produk baru.</p>
        
        <div class="row row-sm mg-b-15">
            <div class="col-sm-6">
                <button type="button" id="loadExistingItems" class="btn btn-info btn-xs">
                    <i class="fa fa-refresh"></i> Muat Item Yang Ada
                </button>
                <button type="button" id="addNewItem" class="btn btn-success btn-xs">
                    <i class="fa fa-plus-circle"></i> Tambah Item Baru
                </button>
            </div>
            <div class="col-sm-6 text-right">
                <span class="badge badge-warning">
                    <i class="fa fa-exclamation-triangle"></i> Hati-hati saat mengedit, stok akan berubah!
                </span>
            </div>
        </div>
        
        <div class="row row-sm">
            <div class="col-sm-12">
                <div class="table-responsive">
                <table class="tabeltransaksi">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Detail</th>
                            <th>Batchcode</th>
                            <th>Gudang</th>
                            <th>Tgl. ED</th>
                            <th>Harga</th>
                            <th>Jumlah</th>
                            <th>Stok Tersedia</th>
                            <th>Diskon</th>
                            <th>Total</th>
                            <th><center>Act</center></th>
                        </tr>
                    </thead>
                    <tbody id="dataaddsales">
                    	<tr id="loadingitems"><td colspan="11"><center>Klik "Muat Item Yang Ada" untuk menampilkan data...</center></td></tr>
					</tbody>
                    <tfoot>
                    	<tr>
                            <td></td>
                        	<td colspan="8"><div align="right"><b>SUBTOTAL</b></div></td>
                            <td><input type="text" name="pstotal" id="pstotal" class="inputtotal" onkeyup="angka(this)" placeholder="0" readonly="readonly" /></td>
                        	<td></td>
                        </tr>
                    	<tr>
                            <td></td>
                        	<td colspan="8"><div align="right"><b><span id="taxLabel">PPN (11%)</span></b></div></td>
                            <td><input type="text" name="pppn" id="pppn" class="inputtotal" onkeyup="angka(this)" placeholder="0"  /></td>
                        	<td></td>
                        </tr>
                    	<tr>
                            <td></td>
                        	<td colspan="8"><div align="right"><b>TOTAL</b></div></td>
                            <td><input type="text" name="pgtotal" id="pgtotal" class="inputtotal" onkeyup="angka(this)" placeholder="0" readonly="readonly" /></td>
                        	<td></td>
                        </tr>
                    </tfoot>
                </table>
                </div>
                <input type="hidden" name="minorder" id="minorder" value="" readonly="readonly" />
                <input type="hidden" name="diskon1" id="diskon1" value="" readonly="readonly" />
                <input type="hidden" name="diskon2" id="diskon2" value="" readonly="readonly" />
                <input type="hidden" name="cartaddsales" id="cartaddsales" value="" readonly="readonly" />
                <input type="hidden" name="jumaddsales" id="jumaddsales" value="0" readonly="readonly" />
                <input type="hidden" name="dataaddsales" id="dataaddsales" value="" readonly="readonly" />
            </div>
        </div>
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <div class="row row-sm">
            <div class="col-sm-12">
                <a href="<?php echo("$sistem/fsales"); ?>" title="Batal">
                    <button type="button" class="btn btn-secondary">Batal</button>
                </a>
                <button type="submit" id="bsave" class="btn btn-dark">Simpan Perubahan</button>
                <div id="imgloading"></div>
            </div>
		</div>
    </div>
</div>
</form>

<script type="text/javascript">
// Add CSS for stock indication
var stockCSS = `
<style>
.text-danger { color: #dc3545 !important; font-weight: bold; }
.text-success { color: #28a745 !important; font-weight: bold; }
.text-warning { color: #ffc107 !important; font-weight: bold; }
.is-invalid { border-color: #dc3545 !important; box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important; }
</style>
`;
$('head').append(stockCSS);

// Load existing items
$("#loadExistingItems").click(function(){
    var keycode = $("input[name='keycode']").val();
    var code = atob(keycode);
    
    $("#loadingitems").html('<td colspan="11"><center><i class="fa fa-spinner fa-spin"></i> Memuat data...</center></td>');
    
    $.ajax({
        url: usuper + "/ajax/detailsalesfsales/detailsalesfsales.php",
        type: "POST",
        async: true,
        dataType: "json",
        cache: false,
        data: { "x": code },
        success: function(data) {
            $("#dataaddsales").html(data.tabel);
            $("#jumaddsales").val(data.jumlahitem);
            $("#pstotal").val(data.subtotal);
            
            // Calculate PPN and total
            var subtotal = parseInt(data.subtotal.replace(/\./g, '')) || 0;
            var ppn = Math.round(subtotal * 0.11);
            var total = subtotal + ppn;
            
            $("#pppn").val(addCommas(ppn));
            $("#pgtotal").val(addCommas(total));
        },
        error: function() {
            $("#loadingitems").html('<td colspan="11"><center class="text-danger">Gagal memuat data</center></td>');
        }
    });
});

// Function untuk format angka
function addCommas(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

// Function untuk hitung sales edit
function hitungsalesedit(nomor) {
    // Prevent multiple simultaneous calls
    if (window.calculating) {
        console.log('Calculation already in progress, skipping...');
        return;
    }
    window.calculating = true;
    
    console.log('hitungsalesedit called for nomor:', nomor);
    
    // Format input jumlah untuk angka saja
    var jumlahInput = $("#ejumlah" + nomor);
    var jumlahValue = jumlahInput.val().replace(/[^0-9]/g, '');
    jumlahInput.val(jumlahValue);
    
    var harga = parseInt($("#eharga" + nomor).val().replace(/\./g, '')) || 0;
    var jumlah = parseInt(jumlahValue) || 0;
    var diskon = parseFloat($("#ediskon" + nomor).val()) || 0;
    
    // Hitung total harga
    var subtotal = harga * jumlah;
    var diskonnominal = (subtotal * diskon) / 100;
    var total = subtotal - diskonnominal;
    
    $("#etotal" + nomor).val(addCommas(Math.round(total)));
    
    // Update stok tersedia
    updateStokTersedia(nomor);
    
    // Recalculate grand total
    recalculateTotal();
    
    // Reset calculation flag
    setTimeout(function() {
        window.calculating = false;
    }, 100);
}

// Function untuk update stok tersedia
function updateStokTersedia(nomor) {
    console.log('updateStokTersedia called for nomor:', nomor); // Debug log
    
    var stokAwal = parseInt($("#stok_awal" + nomor).val()) || 0;  // Stok tersedia dari sisa_psd
    var jumlahAwal = parseInt($("#jumlah_awal" + nomor).val()) || 0;  // Jumlah yang sudah digunakan sebelumnya
    var jumlahSekarang = parseInt($("#ejumlah" + nomor).val().replace(/\./g, '')) || 0;  // Jumlah yang akan digunakan sekarang
    
    console.log('Data stok:', {
        stokAwal: stokAwal,
        jumlahAwal: jumlahAwal,
        jumlahSekarang: jumlahSekarang
    });
    
    // Formula sederhana: stok_tersedia = stok_awal + jumlah_yang_dikembalikan - jumlah_yang_diambil_baru
    // stok_tersedia = stok_awal + jumlah_awal - jumlah_sekarang
    var stokTersedia = stokAwal + jumlahAwal - jumlahSekarang;
    
    console.log('Hasil stok tersedia:', stokTersedia);
    
    // Update tampilan stok tersedia
    $("#stok_tersedia" + nomor).text(stokTersedia);
    
    // Validasi stok dan berikan visual feedback
    if (stokTersedia < 0) {
        $("#stok_tersedia" + nomor).addClass('text-danger').removeClass('text-success text-warning');
        $("#ejumlah" + nomor).addClass('is-invalid');
        
        // Show warning
        swal("Peringatan!", "Stok tidak mencukupi! Stok tersedia akan menjadi " + stokTersedia + "\nAnda kekurangan " + Math.abs(stokTersedia) + " unit.", "warning");
    } else if (stokTersedia <= 5) {
        // Warning untuk stok rendah
        $("#stok_tersedia" + nomor).removeClass('text-danger text-success').addClass('text-warning');
        $("#ejumlah" + nomor).removeClass('is-invalid');
    } else {
        $("#stok_tersedia" + nomor).addClass('text-success').removeClass('text-danger text-warning');
        $("#ejumlah" + nomor).removeClass('is-invalid');
    }
}

// Function untuk validasi sebelum submit
function validateStokBeforeSubmit() {
    var hasError = false;
    var errorMessage = "";
    
    $("input[name='jumlah[]']").each(function(index) {
        var nomor = index + 1;
        var stokTersedia = parseInt($("#stok_tersedia" + nomor).text()) || 0;
        
        if (stokTersedia < 0) {
            hasError = true;
            var produkName = $("#traddeditfsales" + nomor + " td:first").text().trim();
            errorMessage += "- " + produkName + " (kekurangan " + Math.abs(stokTersedia) + " unit)\n";
        }
    });
    
    if (hasError) {
        swal("Error!", "Stok tidak mencukupi untuk:\n" + errorMessage, "error");
        return false;
    }
    
    return true;
}

// Function untuk recalculate total
function recalculateTotal() {
    var grandTotal = 0;
    $("input[name='total[]']").each(function() {
        var val = parseInt($(this).val().replace(/\./g, '')) || 0;
        grandTotal += val;
    });
    
    $("#pstotal").val(addCommas(grandTotal));
    
    var ppn = Math.round(grandTotal * 0.11);
    var totalWithPpn = grandTotal + ppn;
    
    $("#pppn").val(addCommas(ppn));
    $("#pgtotal").val(addCommas(totalWithPpn));
}

// Function untuk delete item
function deletefsalesitem(nomor, idTfd) {
    if (confirm('Apakah Anda yakin ingin menghapus item ini?')) {
        $("#traddeditfsales" + nomor).remove();
        recalculateTotal();
        
        // Update jumlah item
        var currentCount = parseInt($("#jumaddsales").val()) || 0;
        $("#jumaddsales").val(currentCount - 1);
    }
}

// Submit form
$("#formsalespnp").submit(function(e){
    e.preventDefault();
    
    // Prevent double submission
    if ($(this).data('submitted') === true) {
        console.log('Form already submitted, preventing double submission');
        return false;
    }
    
    // Validate that there are items
    var itemCount = parseInt($("#jumaddsales").val()) || 0;
    if (itemCount === 0) {
        swal("Error", "Tidak ada item yang akan disimpan!", "error");
        return false;
    }
    
    // Validate stok
    if (!validateStokBeforeSubmit()) {
        return false;
    }
    
    // Mark as submitted
    $(this).data('submitted', true);
    
    var data = $(this).serialize();
    
    console.log('Submitting form with data:', data);
    console.log('Current stok values before submit:');
    $("input[name='jumlah[]']").each(function(index) {
        var nomor = index + 1;
        var stokAwal = $("#stok_awal" + nomor).val();
        var jumlahAwal = $("#jumlah_awal" + nomor).val();
        var jumlahSekarang = $(this).val();
        var stokTersedia = $("#stok_tersedia" + nomor).text();
        
        console.log(`Item ${nomor}:`, {
            stokAwal: stokAwal,
            jumlahAwal: jumlahAwal,
            jumlahSekarang: jumlahSekarang,
            stokTersedia: stokTersedia
        });
    });
    
    // Show loading
    $("#imgloading").html('<img src="'+usuper+'/assets/img/loading.gif" style="width:20px; height:20px;" />');
    $("#bsave").prop('disabled', true);
    
    $.ajax({
        url: usuper + "/modal/fsales/action.php",
        type: "POST",
        async: true,
        dataType: "json",
        cache: false,
        data: data,
        success: function(response) {
            console.log('Server response:', response);
            if(response.status === "Success") {
                swal("Success", response.message, "success").then(function() {
                    window.location.href = usuper + "/" + response.url;
                });
            } else {
                swal("Error", response.message, "error");
                console.error('Server error:', response);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', {xhr: xhr, status: status, error: error});
            swal("Error", "Terjadi kesalahan sistem: " + error, "error");
        },
        complete: function() {
            $("#imgloading").html('');
            $("#bsave").prop('disabled', false);
            // Reset submission flag
            $("#formsalespnp").data('submitted', false);
        }
    });
});

// Function untuk edit item
function editfsalesitem(nomor, idTfd) {
    // Enable editing untuk baris tertentu
    $("#eharga" + nomor).prop('readonly', false).focus();
    $("#ejumlah" + nomor).prop('readonly', false);
    $("#ediskon" + nomor).prop('readonly', false);
    
    // Optional: Tambah indikator bahwa item sedang diedit
    $("#traddeditfsales" + nomor).addClass('table-warning');
}

// Function untuk add new item 
$("#addNewItem").click(function(){
    addmaximal('addsales', 'outlet', 200);
});
</script>
