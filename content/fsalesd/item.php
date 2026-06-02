<?php
	$uniq	= $secu->injection(@$_GET['keycode']);
	$code	= base64_decode($uniq);
	$read	= $conn->prepare("SELECT sj_tfk, id_out, kode_tfk FROM transaksi_faktur_d WHERE id_tfk=:code");
	$read->bindParam(':code', $code, PDO::PARAM_STR);
	$read->execute();
	$view	= $read->fetch(PDO::FETCH_ASSOC);
?>
<form id="formsalespnpd" action="#" method="post" autocomplete="off">
<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Donasi</a></li>
                <li class="breadcrumb-item active" aria-current="page">Faktur Donasi</li>
            </ol>
        </nav>
        <h4 class="content-title">Input Item - Faktur Donasi</h4>
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
        <input type="hidden" name="namamodal" id="namamodal" value="fsalesd" readonly="readonly" />
        <input type="hidden" name="namamenu" value="items" readonly="readonly" />
        <input type="hidden" name="keycode" value="<?php echo($uniq); ?>" readonly="readonly" />
        <input type="hidden" name="nomorfaktur" value="<?php echo($view['kode_tfk']); ?>" readonly="readonly" />
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <h5 id="section1" class="tx-semibold"> Produk</h5>
        <p class="mg-b-25">Pilih produk yang akan di Donasikan</p>
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
                        	<td colspan="8"><div align="right"><b><span id="taxLabel">PPN (10%)</span></b></div></td>
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
                <a onclick="addmaximal('addsales', 'outlet', 50)"><span class="badge badge-success"><i class="fa fa-plus-circle"></i> Add Data</span></a>
            </div>
        </div>
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <div class="row row-sm">
            <div class="col-sm-12">
                <a href="<?php echo("$sistem/fsalesd"); ?>" title="Batal"><button type="button" class="btn btn-secondary">Batal</button></a>
                <button type="submit" id="bsave" class="btn btn-dark">Simpan</button>
                <div id="imgloading"></div>
            </div>
		</div>
    </div>
</div>
</form>
