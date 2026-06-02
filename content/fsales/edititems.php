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
        <div class="row row-sm mg-b-25">
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="keterangan_revisi" class="form-label">
                        <strong>Keterangan Revisi <span class="text-danger">*</span></strong>
                    </label>
                    <textarea name="keterangan_revisi" id="keterangan_revisi" class="form-control" rows="3" 
                              placeholder="Keterangan revisi telah diisi dari modal sebelumnya..."
                              maxlength="500" required readonly></textarea>
                    <small class="form-text text-muted">
                        <i class="fa fa-check-circle text-success"></i> 
                        Keterangan revisi telah diisi dari modal sebelumnya
                        <span class="float-right">
                            <span id="char-count">0</span>/500 karakter
                        </span>
                    </small>
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

<!-- Modal untuk pilih produk -->
<div class="modal fade" id="modal1" tabindex="-1" role="dialog" aria-labelledby="modal1Label" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal1Label">Pilih Produk</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="modal1content">
                    Loading products...
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
// Add CSS for stock indication
var stockCSS = `
<style>
.text-danger { color: #dc3545 !important; font-weight: bold; }
.text-success { color: #28a745 !important; font-weight: bold; }
.text-warning { color: #ffc107 !important; font-weight: bold; }
.text-muted { color: #6c757d !important; font-size: 0.8em; }
.is-invalid { border-color: #dc3545 !important; box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important; }
.stock-warning { 
    background-color: #fff3cd !important; 
    border: 1px solid #ffeaa7 !important; 
    animation: pulse-warning 2s infinite;
}
.stock-danger { 
    background-color: #f8d7da !important; 
    border: 1px solid #f5c6cb !important; 
    animation: pulse-danger 2s infinite;
}
.stock-success { 
    background-color: #d1edff !important; 
    border: 1px solid #bee5eb !important; 
}
@keyframes pulse-warning {
    0% { background-color: #fff3cd; }
    50% { background-color: #ffeaa7; }
    100% { background-color: #fff3cd; }
}
@keyframes pulse-danger {
    0% { background-color: #f8d7da; }
    50% { background-color: #f5c6cb; }
    100% { background-color: #f8d7da; }
}
.swal2-popup .swal2-content {
    font-family: 'Courier New', monospace !important;
    white-space: pre-line !important;
    text-align: left !important;
}
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
            
            // PENTING: Validasi stok untuk semua item setelah data dimuat
            setTimeout(function() {
                console.log('🔄 Validating stock for all items after data load...');
                $("input[name='jumlah[]']").each(function(index) {
                    var nomor = index + 1;
                    console.log('📝 Validating item ' + nomor);
                    updateStokTersedia(nomor);
                });
            }, 500);
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
    
    console.log('🧮 hitungsalesedit called for nomor:', nomor);
    
    // Format input jumlah untuk angka saja
    var jumlahInput = $("#ejumlah" + nomor);
    var jumlahValue = jumlahInput.val().replace(/[^0-9]/g, '');
    jumlahInput.val(jumlahValue);
    
    var harga = parseInt($("#eharga" + nomor).val().replace(/\./g, '')) || 0;
    var jumlah = parseInt(jumlahValue) || 0;
    var diskon = parseFloat($("#ediskon" + nomor).val()) || 0;
    
    console.log('💰 Calculation data:', {harga: harga, jumlah: jumlah, diskon: diskon});
    
    // Hitung total harga
    var subtotal = harga * jumlah;
    var diskonnominal = (subtotal * diskon) / 100;
    var total = subtotal - diskonnominal;
    
    $("#etotal" + nomor).val(addCommas(Math.round(total)));
    
    // Update stok tersedia display
    updateStokTersedia(nomor);
    
    // Recalculate grand total
    recalculateTotal();
    
    // Reset calculation flag
    setTimeout(function() {
        window.calculating = false;
    }, 100);
}

// Function untuk update stok tersedia (display only)
function updateStokTersedia(nomor) {
    console.log('📊 updateStokTersedia display for nomor:', nomor);
    
    // Safety check: only process if the edit input exists
    var jumlahInput = $("#ejumlah" + nomor);
    if (!jumlahInput.length || !jumlahInput.val()) {
        console.log('⚠️ No ejumlah input found for nomor:', nomor);
        return false;
    }
    
    var stokAwal = parseInt($("#stok_awal" + nomor).val()) || 0;
    var jumlahAwal = parseInt($("#jumlah_awal" + nomor).val()) || 0;
    var jumlahValue = jumlahInput.val() || '';
    var jumlahSekarang = parseInt(jumlahValue.toString().replace(/\./g, '')) || 0;
    
    // Hitung stok tersedia untuk display
    var stokTersedia = stokAwal + jumlahAwal - jumlahSekarang;
    
    // Update tampilan stok tersedia
    $("#stok_tersedia" + nomor).text(stokTersedia);
    
    // Visual feedback sederhana berdasarkan stok tersedia
    if (stokTersedia < 0) {
        $("#stok_tersedia" + nomor).addClass('text-danger').removeClass('text-success text-warning');
    } else if (stokTersedia <= 5) {
        $("#stok_tersedia" + nomor).removeClass('text-danger text-success').addClass('text-warning');
    } else {
        $("#stok_tersedia" + nomor).addClass('text-success').removeClass('text-danger text-warning');
    }
    
    return true;
}

// Function untuk validasi sebelum submit
function validateStokBeforeSubmit() {
    var hasError = false;
    var errorMessage = "";
    var overStockItems = "";
    
    $("input[name='jumlah[]']").each(function(index) {
        var nomor = index + 1;
        var stokTersedia = parseInt($("#stok_tersedia" + nomor).text()) || 0;
        var jumlahSekarang = parseInt($(this).val().replace(/\./g, '')) || 0;
        var stokAwal = parseInt($("#stok_awal" + nomor).val()) || 0;
        var jumlahAwal = parseInt($("#jumlah_awal" + nomor).val()) || 0;
        
        // Validasi 1: Stok tidak boleh negatif
        if (stokTersedia < 0) {
            hasError = true;
            var produkName = $("#traddeditfsales" + nomor + " td:first").text().trim();
            errorMessage += "- " + produkName + " (kekurangan " + Math.abs(stokTersedia) + " unit)\n";
        }
        
        // Validasi 2: Total penggunaan tidak boleh melebihi sisa_psd
        var totalPenggunaan = jumlahSekarang + stokTersedia;
        var sisaPsd = stokAwal + jumlahAwal;
        
        if (totalPenggunaan > sisaPsd) {
            hasError = true;
            var produkName = $("#traddeditfsales" + nomor + " td:first").text().trim();
            var kelebihan = totalPenggunaan - sisaPsd;
            overStockItems += "- " + produkName + " (melebihi " + kelebihan + " unit dari sisa_psd)\n";
        }
    });
    
    if (hasError) {
        var fullErrorMessage = "";
        if (errorMessage) {
            fullErrorMessage += "STOK TIDAK MENCUKUPI:\n" + errorMessage + "\n";
        }
        if (overStockItems) {
            fullErrorMessage += "JUMLAH ORDER MELEBIHI STOKKK!!\n" + overStockItems + "\n";
        }
        fullErrorMessage += "Silakan perbaiki item yang bermasalah sebelum menyimpan.";
        
        swal("Error!", fullErrorMessage, "error");
        return false;
    }
    
    // VALIDASI BERHASIL - RETURN TRUE TANPA NOTIFIKASI
    console.log('✅ Validasi stok berhasil, melanjutkan penyimpanan...');
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
    
    // VALIDASI STOK SUDAH DILAKUKAN REAL-TIME, LANGSUNG SUBMIT
    
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
                // TAMPILKAN PESAN SUKSES SEDERHANA HANYA DENGAN NOMOR FAKTUR
                var nomorFaktur = $("input[name='nomorfaktur']").val() || 'Tidak diketahui';
                swal("Berhasil", "Data berhasil disimpan\nNomor Faktur: " + nomorFaktur, "success").then(function() {
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

// Function untuk open product modal (dipanggil dari row yang ditambahkan)
// Define as global function
window.openProductModal = function(nomor, outlet) {
    console.log('🔍 openProductModal called for item nomor:', nomor);
    
    var mitra = $("#" + outlet).val();
    
    if (!mitra || mitra == '') {
        console.error('Outlet belum dipilih!');
        swal("Maaf!", "Pilih outlet dulu...", "error");
        return false;
    }
    
    // Untuk edit items, kita loading produk normal (bukan konsinyasi)
    var modalUrl = usuper + "/modal/addsales/addsales.php";
    
    console.log('Loading product modal:', {
        nomor: nomor,
        outlet: mitra,
        modalUrl: modalUrl
    });
    
    // Load modal content
    $.ajax({
        url: modalUrl,
        type: "POST",
        async: true,
        dataType: "text",
        cache: false,
        data: { 
            "m": mitra, 
            "x": nomor, 
            "y": "0" // cart default 0 untuk edit items
        },
        beforeSend: function() {
            console.log('Loading product selection...');
            $("#modal1content").html('<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Memuat produk...</div>');
        },
        success: function(data) {
            console.log('✅ Product modal loaded successfully');
            $("#modal1 .modal-content").html(data);
            $("#modal1").modal('show');
        },
        error: function(xhr, status, error) {
            console.error('❌ Error loading product modal:', error);
            swal("Error", "Gagal memuat daftar produk: " + error, "error");
        }
    });
};

// Initialize keterangan revisi dari session storage
$(document).ready(function() {
    // Ambil keterangan revisi dari session storage
    var keycode = $("input[name='keycode']").val();
    var sessionKey = 'keterangan_revisi_' + keycode;
    var keteranganRevisi = sessionStorage.getItem(sessionKey);
    
    if (keteranganRevisi) {
        // Isi textarea dengan keterangan dari session
        $("#keterangan_revisi").val(keteranganRevisi);
        $("#char-count").text(keteranganRevisi.length);
        
        // Update color coding
        var charCount = keteranganRevisi.length;
        if (charCount > 450) {
            $("#char-count").addClass('text-danger').removeClass('text-warning text-success');
        } else if (charCount > 350) {
            $("#char-count").addClass('text-warning').removeClass('text-danger text-success');
        } else {
            $("#char-count").addClass('text-success').removeClass('text-danger text-warning');
        }
        
        // Hapus dari session storage setelah digunakan
        sessionStorage.removeItem(sessionKey);
        
        console.log('Keterangan revisi loaded from session:', keteranganRevisi);
    } else {
        // Jika tidak ada di session, redirect kembali ke halaman fsales
        console.log('No revision reason found, redirecting...');
        swal("Error", "Keterangan revisi tidak ditemukan. Silakan klik tombol Edit Item lagi dari halaman Faktur Penjualan.", "error").then(function() {
            window.location.href = usuper + '/fsales';
        });
        return;
    }
    
    // 🚨 PENTING: Event listener KHUSUS UNTUK EDIT ITEM (ejumlah*)
    $(document).on('input keyup paste', 'input[id^="ejumlah"]', function(e) {
        var inputId = $(this).attr('id');
        var nomor = inputId.replace('ejumlah', '');
        var currentValue = $(this).val().replace(/[^0-9]/g, '');
        
        console.log('🔄 Edit item validation for ejumlah' + nomor + ':', currentValue);
        
        // VALIDASI LANGSUNG TANPA DELAY HANYA UNTUK EDIT ITEM
        if (currentValue && parseInt(currentValue) > 0) {
            var stokAwal = parseInt($("#stok_awal" + nomor).val()) || 0;
            var jumlahAwal = parseInt($("#jumlah_awal" + nomor).val()) || 0;
            var batasMaksimal = stokAwal + jumlahAwal;
            var jumlahInput = parseInt(currentValue);
            
            // JIKA MELEBIHI BATAS - LANGSUNG ALERT DAN RESET
            if (jumlahInput > batasMaksimal) {
                var kelebihan = jumlahInput - batasMaksimal;
                
                // RESET KE NILAI SEBELUMNYA SEGERA
                $(this).val(jumlahAwal); // Kembali ke orderan sebelumnya
                
                // TAMPILKAN ALERT
                swal("ORDERAN MELEBIHI STOK!", 
                     "JUMLAH ORDER MELEBIHI STOKKK!!\n\n" +
                     "❌ Jumlah yang diinput: " + addCommas(jumlahInput) + " unit\n" +
                     "✅ Batas maksimal stok: " + addCommas(batasMaksimal) + " unit\n" +
                     "⚠️ Kelebihan: " + addCommas(kelebihan) + " unit\n\n" +
                     "📝 Dikembalikan ke orderan sebelumnya: " + addCommas(jumlahAwal) + " unit", 
                     "error");
                
                // VISUAL FEEDBACK
                $(this).addClass('is-invalid');
                $("#traddeditfsales" + nomor).addClass('stock-danger');
                
                // FOCUS TETAP DI INPUT
                $(this).focus().select();
                
                // HITUNG ULANG DENGAN NILAI YANG BENAR
                setTimeout(function() {
                    hitungsalesedit(nomor);
                }, 100);
                
                return false;
            } else {
                // JIKA VALID - REMOVE ERROR STYLING
                $(this).removeClass('is-invalid');
                $("#traddeditfsales" + nomor).removeClass('stock-danger').addClass('stock-success');
                
                // LANJUTKAN PERHITUNGAN NORMAL
                hitungsalesedit(nomor);
            }
        }
    });
    
    // 📝 Event listener TERPISAH untuk item baru (pjumlah*) - TANPA validasi stok ketat
    $(document).on('input keyup paste', 'input[id^="pjumlah"]', function(e) {
        var inputId = $(this).attr('id');
        var nomor = inputId.replace('pjumlah', '');
        var currentValue = $(this).val().replace(/[^0-9]/g, '');
        
        console.log('📝 New item input for pjumlah' + nomor + ':', currentValue);
        
        // Format input hanya untuk angka
        $(this).val(currentValue);
        
        // Hitung sales untuk item baru (tanpa validasi stok ketat)
        if (typeof hitungsales === 'function') {
            hitungsales(nomor);
        }
        
        // Visual feedback sederhana untuk item baru
        if (parseInt(currentValue) > 0) {
            $(this).removeClass('is-invalid').addClass('stock-success');
        } else {
            $(this).removeClass('is-invalid stock-success stock-danger');
        }
    });
    
    // Event khusus untuk BACKSPACE dan DELETE - HANYA UNTUK EDIT ITEM (ejumlah*)
    $(document).on('keydown', 'input[id^="ejumlah"]', function(e) {
        var inputId = $(this).attr('id');
        var nomor = inputId.replace('ejumlah', '');
        
        // Untuk backspace dan delete, tunggu sebentar lalu validasi
        if (e.keyCode === 8 || e.keyCode === 46) {
            setTimeout(function() {
                var newValue = $("#ejumlah" + nomor).val().replace(/[^0-9]/g, '');
                if (newValue === '' || parseInt(newValue) === 0) {
                    $("#traddeditfsales" + nomor).removeClass('stock-danger stock-warning').addClass('stock-success');
                    $("#ejumlah" + nomor).removeClass('is-invalid');
                }
            }, 50);
        }
    });
    
    // Debug: Log semua input jumlah yang ada
    console.log('🔍 Debug: Available quantity inputs on page load:');
    $('input[name="jumlah[]"]').each(function(index) {
        console.log('Input ' + (index + 1) + ':', $(this).attr('id'), '=', $(this).val());
    });
    
    // Auto-trigger validasi saat halaman dimuat
    setTimeout(function() {
        console.log('🔄 Auto-validating stock on page load...');
        $("input[name='jumlah[]']").each(function(index) {
            var nomor = index + 1;
            if ($("#ejumlah" + nomor).length > 0) {
                console.log('📝 Auto-validating item ' + nomor + ' with value:', $("#ejumlah" + nomor).val());
                updateStokTersedia(nomor);
            }
        });
    }, 1000);
    
    // Debug: Test SweetAlert availability
    console.log('🍬 SweetAlert available:', typeof swal !== 'undefined');
    if (typeof swal !== 'undefined') {
        console.log('✅ SweetAlert ready for use');
    } else {
        console.log('❌ SweetAlert not available, will use regular alert');
    }
});
</script>
