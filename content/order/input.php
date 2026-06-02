<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Order</a></li>
                <li class="breadcrumb-item active" aria-current="page">Surat Pesanan Barang</li>
            </ol>
        </nav>
        <h4 class="content-title">Input Data - Surat Pesanan Barang</h4>
    </div>
</div>
<?php
	$unik	= "/PRC/".$data->romawi(date('m')).'/'.date('y');
	$kode	= $data->transcodeorder($unik, "kode_tor", "transaksi_order");
?>
<div class="content-body">
    <div class="component-section no-code">
            <h5 id="section1" class="tx-semibold"><?php echo($data->sistem('pt_sis')); ?></h5>
            <div style="margin-top:10px; margin-bottom:25px;">
                <div>Izin PBF No : <?php echo($data->sistem('pbf_sis')); ?></div>
                <div>NPWP No : <?php echo($data->sistem('npwp_sis')); ?></div>
                <div>Alamat : <?php echo($data->sistem('alamat_sis')); ?></div>
            </div>
        <form id="formtransaksi" action="#" method="post" autocomplete="off">
        <input type="hidden" name="nmenu" id="nmenu" value="order" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="input" readonly="readonly" />
        <div class="row row-sm">
            <div class="col-sm-3">
                <label>Kode <span class="tx-danger">*</span></label>
                <input type="text" name="kode" class="form-control" value="<?php echo($kode); ?>" placeholder="-" required="required" />
            </div>
            <div class="col-sm-3">
                <label>Supplier <span class="tx-danger">*</span></label>
				<select name="supplier" id="supplier" class="form-control select2" required="required">
                	<option value="">-- Pilih --</option>
				<?php
					$master	= $conn->prepare("SELECT id_sup, nama_sup FROM supplier ORDER BY nama_sup ASC");
					$master->execute();
					while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
				?>
                	<option value="<?php echo($hasil['id_sup']); ?>"><?php echo($hasil['nama_sup']); ?></option>
                <?php } ?>
                </select>
            </div>
            <div class="col-sm-2">
                <label>Tanggal <span class="tx-danger">*</span></label>
                <input type="text" name="tanggal" class="form-control datepicker" value="<?php echo(date('Y-m-d')); ?>" placeholder="9999-99-99" required="required" />
            </div>
            <div class="col-sm-4">
                <label>Keterangan <span class="tx-default">*</span></label>
                <input type="text" name="keterangan" class="form-control" placeholder="Ketik keterangan di sini..." />
            </div>
        </div><!-- row -->
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <h5 id="section1" class="tx-semibold">Order Produk</h5>
        <p class="mg-b-25">Pilih produk yang akan dipesan kepada supplier.</p>
        <div class="row row-sm">
            <div class="col-sm-12">
            	<div class="table-responsive">
				<table class="tabeltransaksi">
                	<thead>
                        <tr>
                            <th>Product</th>
                            <th>Detail</th>
                            <th>Jumlah</th>
                            <th>Satuan Qty.</th>
                            <th><center>Act</center></th>
                        </tr>
                    </thead>
                    <tbody id="dataaddorder">
                    <!--
                        <tr id="traddorder1">
                            <td>
                            <a href="#modal1" onclick="mproduct(1, 'showproduct')" data-toggle="modal">
                            <div id="noproduct1">Pilih</div>
                            </a>
                            <input type="hidden" name="product[]" id="product1" class="itemproduct" readonly="readonly" />
                            </td>
                            <td><div id="detailproduct1">-</div></td>
                            <td><input type="text" name="jumlah[]" id="pjumlah1" class="inputangka" onchange="cekproduk(1)" onkeyup="angka(this)" placeholder="0" required="required" /></td>
                            <td><div id="satuanqty1">-</div></td>
                            <td>
                            <center>
                                <a onclick="deleteorder('$nomor')"><span class="badge badge-danger"><i class="fa fa-times-circle"></i></span></a>
                            </center>
                            </td>
                        </tr>
					-->
                    </tbody>
                </table>
                </div>
                <input type="hidden" name="jumaddorder" id="jumaddorder" value="0" readonly="readonly" />
                <a onclick="addmaximal('addorder', 'supplier', 100)"><span class="badge badge-success"><i class="fa fa-plus-circle"></i> Add Data</span></a>
            </div>
        </div>
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <div class="row row-sm">
            <div class="col-sm-12">
                <a href="<?php echo("$sistem/order"); ?>" title="Batal">
                <button type="button" class="btn btn-secondary">Batal</button>
				</a>
                <button type="submit" id="bsave" class="btn btn-dark">Simpan</button>
                <div id="imgloading"></div>
            </div>
		</div>
		</form>
    </div>
</div>