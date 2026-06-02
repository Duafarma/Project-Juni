<?php
	$uniq	= $secu->injection(@$_GET['keycode']);
	$code	= base64_decode($uniq);
	$read	= $conn->prepare("SELECT sj_tfk, id_out, kode_tfkk FROM transaksi_faktur_penggantian_barang WHERE id_tfk=:code");
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
                <li class="breadcrumb-item"><a href="#">Retur</a></li>
                <li class="breadcrumb-item active" aria-current="page">Faktur Penggantian Barang Retur</li>
            </ol>
        </nav>
        <h4 class="content-title">Input Item - Faktur Penggantian Barang Retur</h4>
        <h5>SJ : <?php echo($view['sj_tfk']); ?></h5>
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
        <input type="hidden" name="namamodal" id="namamodal" value="fpenggantianbarang" readonly="readonly" />
        <input type="hidden" name="namamenu" value="items" readonly="readonly" />
        <input type="hidden" name="keycode" value="<?php echo($uniq); ?>" readonly="readonly" />
        <input type="hidden" name="nomorfaktur" value="<?php echo($view['kode_tfkk']); ?>" readonly="readonly" />
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <h5 id="section1" class="tx-semibold">Order Produk</h5>
        <p class="mg-b-25">Pilih produk yang akan di input.</p>
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
                            <th>St. Qty.</th>
                            <th>Diskon</th>
                            <th>Total</th>
                            <th><center>Act</center></th>
                        </tr>
                    </thead>
                    <tbody id="dataaddsales">
                    	<tr id="pilihoutlet"><td colspan="10">Pilih outlet dulu...</td></tr>
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
                <a onclick="addmaximal('addsales', 'outlet', 200)"><span class="badge badge-success"><i class="fa fa-plus-circle"></i> Add Data</span></a>
            </div>
        </div>
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <div class="row row-sm">
            <div class="col-sm-12">
                <a href="<?php echo("$sistem/fpenggantianbarang"); ?>" title="Batal"><button type="button" class="btn btn-secondary">Batal</button></a>
                <button type="submit" id="bsave" class="btn btn-dark">Simpan</button>
                <div id="imgloading"></div>
            </div>
		</div>
    </div>
</div>
</form>


<script type="text/javascript">
// Override viewdata IMMEDIATELY after form
$(document).ready(function(){
    // Stop propagation of ready event from fazlurr.js
    console.log('Item page ready - overriding viewdata');
    
    // Override viewdata to prevent JSON parse error
    window.viewdata = function(menu, maximal, halaman) {
        console.log('viewdata BLOCKED for:', menu);
        // Return empty result, don't call AJAX
        $("#isitabel").html('<tr><td colspan="10">-</td></tr>');
        return false;
    };
});

// Custom getproductsales for penggantian barang
// This overrides the one in fazlurr.js to ensure it works with our modal
function getproductsales(nomor, kode, produk, nama, code, harga, berat, kategori, satuanqty, satuan, bcode, tgled, gudang, stok, diskon){
    console.log('=== getproductsales CALLED (penggantian barang override) ===');
    console.log('Nomor:', nomor);
    console.log('Element check #product' + nomor + ':', $("#product" + nomor).length > 0);
    
    // Ensure we have the element
    if($("#product" + nomor).length === 0) {
        console.error('ERROR: Required element #product' + nomor + ' not found!');
        console.error('Available elements with id starting with product:', $('[id^="product"]').length);
        alert('Error: Baris produk tidak ditemukan. Refresh halaman dan coba lagi.');
        return false;
    }
    
    // Convert values to appropriate types
    nomor = parseInt(nomor);
    harga = parseInt(harga);
    stok = parseInt(stok);
    diskon = parseInt(diskon) || 0;
    
    console.log('Converted values - nomor:', nomor, 'harga:', harga, 'stok:', stok, 'diskon:', diskon);
    
    // Default diskon jika tidak ada
    if(!diskon) diskon = 0;
    
    // Set jumlah default ke 1
    var jumlah = 1;
    
    // Calculate totals
    var subtot = (jumlah * harga);
    var total = Math.round((parseInt(subtot) - ((subtot * diskon) / 100)), 0);
    
    // Get current totals
    var pstotal = $("#pstotal").val() || "0";
    pstotal = bersih(pstotal);
    var ptotal = $("#ptotal"+nomor).val() || "0";
    ptotal = bersih(ptotal);
    
    var stotal = parseInt(total) + parseInt(pstotal) - parseInt(ptotal);
    var ppn = Math.round(((stotal * 11) / 100), 0);
    var gtotal = parseInt(stotal) + parseInt(ppn);
    
    // Update form elements
    console.log('Updating elements...');
    $("#noproduct" + nomor).html('(' + code + ') ' + nama);
    $("#satuanqty" + nomor).html(satuanqty);
    $("#nobcode" + nomor).html(bcode);
    $("#tgled" + nomor).html(tgled);
    $("#gudang" + nomor).html(gudang);
    $("#pdiskon" + nomor).val(diskon);
    $("#prostok" + nomor).val(stok);
    $("#stoktampil" + nomor).html(stok);
    $("#prodetail" + nomor).html(kategori + ' (' + berat + ' ' + satuan + ')');
    $("#product" + nomor).val(produk);
    $("#pnamaproduk" + nomor).val(nama);
    $("#pharga" + nomor).val(titik(harga));
    $("#ptotal" + nomor).val(titik(total));
    $("#pstotal").val(titik(stotal));
    $("#pppn").val(titik(ppn));
    $("#pgtotal").val(titik(gtotal));
    $("#kodestok" + nomor).val(kode);
    
    console.log('Calling cart function...');
    // Update cart
    cart(nomor, kode, 'addsales');
    
    console.log('Cart value:', $("#cartaddsales").val());
    console.log('Hidden input #product' + nomor + ' value:', $("#product" + nomor).val());
    
    // Close modal
    console.log('Closing modal...');
    $('#modal1').modal('hide');
    
    console.log('=== FINISHED ===');
}

// Fungsi khusus untuk penggantian barang
function openProductModal(nomor, outlet) {
    var cart = $("#cartaddsales").val();
    var mitra = $("#" + outlet).val();
    
    console.log('openProductModal - nomor:', nomor, 'outlet:', mitra);
    
    if(!mitra || mitra === '') {
        alert('Error: Outlet ID tidak ditemukan!');
        return false;
    }
    
    $.ajax({
        url: usuper + "/modal/fpenggantianbarang/products.php",
        type: "POST",
        dataType: "text",
        data: {
            "m": mitra,
            "x": nomor,
            "y": cart
        },
        success: function(data) {
            console.log('Modal loaded, length:', data.length);
            $(".modal-content").html(data);
            
            // Show the modal
            $('#modal1').modal('show');
        },
        error: function(xhr, status, error) {
            console.error('Error loading modal:', error);
            alert('Error: ' + error);
        }
    });
}
</script>