<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Inventory</a></li>
                <li class="breadcrumb-item"><a href="#">Transfer Stok</a></li>
                <li class="breadcrumb-item active" aria-current="page">Transfer Stok Baru</li>
            </ol>
        </nav>
        <h4 class="content-title">Transfer Stok Baru</h4>
    </div>
</div>
<?php
        $unik	= "/".$data->romawi(date('m')).'/'.date('Y');
        $kode	= $data->transcodetf($unik, "kode_ttr", "transaksi_transferstock");
        $apls   = $data->get_apl();

?>
<div class="content-body">
    <div class="component-section no-code">
        <h5 id="section1" class="tx-semibold"><?php echo($data->sistem('pt_sis')); ?></h5>
        <div style="margin-top:10px; margin-bottom:25px;">
            <div>Izin PBF No : <?php echo($data->sistem('pbf_sis')); ?></div>
            <div>NPWP No : <?php echo($data->sistem('npwp_sis')); ?></div>
            <div>Alamat : <?php echo($data->sistem('alamat_sis')); ?></div>
        </div>
        <form id="formProductTransfer" action="#" method="post" autocomplete="off">
        <input type="hidden" name="nmenu" id="nmenu" value="transferstok" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="input" readonly="readonly" />
        <div class="row row-sm">
            <div class="col-sm-3">
                <label>Kode <span class="tx-danger">*</span></label>
                <input type="text" name="kode" class="form-control" value="<?php echo($kode); ?>" placeholder="-" required="required" />
            </div>
            <div class="col-sm-3">
                <label>Tipe Transfer <span class="tx-danger">*</span></label>
				<select name="transfer_apl_type" id="transfer_apl_type" locked class="form-control select2" required="required">
                	<option value="">-- Pilih --</option>
                	<option value="IN">Masuk</option>
                    <option value="OUT">Keluar</option>
                </select>
            </div>
            <div class="col-sm-3">
                <label>Tanggal <span class="tx-danger">*</span></label>
                <input type="text" name="tanggal" class="form-control" value="<?php echo(date('Y-m-d')); ?>" placeholder="9999-99-99" required="required" readonly/>
            </div>
        </div>
        <div class="row row-sm">
            <div class="col-sm-3">
                <label>Asal <span class="tx-danger">*</span></label>
				<select name="transfer_apl_from" id="transfer_apl_from" class="form-control select2" required="required">
                	<option value="">-- Pilih --</option>
				<?php
					foreach ($apls as $hasil) {
				?>
                	<option value="<?php echo($hasil['id_apl']); ?>"><?php echo($hasil['nama_apl']); ?></option>
                <?php } ?>
                </select>
            </div>
            <div class="col-sm-3">
                <label>Tujuan <span class="tx-danger">*</span></label>
				<select name="transfer_apl_to" id="transfer_apl_to" class="form-control select2" required="required">
                	<option value="">-- Pilih --</option>
                <?php
					foreach ($apls as $hasil) {
				?>
                	<option value="<?php echo($hasil['id_apl']); ?>"><?php echo($hasil['nama_apl']); ?></option>
                <?php } ?>
                </select>
            </div>
            <div class="col-sm-6">
                <label>Keterangan</label>
                <input type="text" name="keterangan" class="form-control" placeholder="Ketik keterangan di sini..." />
            </div>
        </div><!-- row -->
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <h5 id="section1" class="tx-semibold">Transfer Produk</h5>
        <p class="mg-b-25">Pilih produk yang akan ditransfer.</p>
        <div class="row row-sm">
            <div class="col-sm-12">
            	<div class="table-responsive">
				<table class="tabeltransaksi">
                	<thead>
                        <tr>
                            <th>Product</th>
                            <th>Detail</th>
                            <th>Batchcode</th>
                            <th>Tgl. Expired</th>
                            <th>Stok</th>
                            <th>Harga</th>
                            <th id="labelTransferType">Jumlah</th>
                            <th>Satuan Qty.</th>
                            <th><center>Act</center></th>
                        </tr>
                    </thead>
                    <tbody id="dataaddProductTransfer">
                    </tbody>
                </table>
                </div>
                <input type="hidden" name="cartaddProductTransfer" id="cartaddProductTransfer" value="" readonly="readonly" />
                <input type="hidden" name="countaddProductTransfer" id="countaddProductTransfer" value="0" readonly="readonly" />
                <a onclick="addProductTransfer('addProductTransfer', 'transfer_apl_type', 'transfer_apl_from', 'transfer_apl_to', 'tanggal', '<?php echo $self_apl['id_apl']; ?>', 50)"><span class="badge badge-success">
                    <i class="fa fa-plus-circle"></i> Add Data</span>
                </a>
            </div>
        </div>
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <div class="row row-sm">
            <div class="col-sm-12">
                <a href="<?php echo("$sistem/transferstok"); ?>" title="Batal">
                    <button type="button" class="btn btn-secondary">Batal</button>
				</a>
                <button type="submit" id="bsave" class="btn btn-dark">Simpan</button>
                <div id="imgloading"></div>
            </div>
		</div>
		</form>
    </div>
</div>