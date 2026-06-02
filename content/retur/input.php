<div class="content-header">
    <div>
        <!-- <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Pengiriman</a></li>
                <li class="breadcrumb-item active" aria-current="page">Pengiriman Kurir</li>
            </ol>
        </nav> -->
        <h4 class="content-title">Input Data - Barang Retur</h4>
    </div>
</div>
<?php
        $unik	= "/RETUR/DFM/PST/".$data->romawi(date('m')).'/'.date('Y');
        $kode	= $data->transcoderetur($unik, "nomor", "finance");
        // $apls   = $data->get_apl();

?>
<input type="hidden" name="jumlegal" id="jumlegal" value="0" readonly="readonly" />
<input type="hidden" name="jumitem" id="jumitem" value="0" readonly="readonly" />
<div class="content-body">
    <div class="component-section no-code">
        <form id="formtransaksi" action="#" method="post"  enctype="multipart/form-data" autocomplete="off">
        <input type="hidden" name="nmenu" id="nmenu" value="retur" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="input" readonly="readonly" />
        <div class="form-row">
            <div class="form-group col-sm-6">
                <label>Tanggal  <span class="tx-danger"></span></label>
                <input type="text" name="tanggal" class="form-control datepicker"  value="<?php echo(date('Y-m-d')); ?>" placeholder="9999-99-99"  />
            </div>
              <div class="form-group col-sm-6">
                <label>Nama Outlet <span class="tx-danger">*</span></label>
				<select name="id_out" id="outlet" class="form-control select2" onchange="ceksalespenggantianbarang()" required="required">
                	<option value="">-- Pilih --</option>
				<?php
					$status	= 'Active';
					$master	= $conn->prepare("SELECT id_out, kode_out, nama_out FROM outlet WHERE status_out=:status ORDER BY nama_out ASC");
					$master->bindParam(':status', $status, PDO::PARAM_STR);
					$master->execute();
					while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
				?>
                	<option value="<?php echo($hasil['id_out']); ?>"><?php echo($hasil['nama_out']); ?></option>
                <?php } ?>
                </select>
            </div>
            <div class="form-group col-sm-6">
                <label>Nomor Faktur<span class="tx-danger"></span></label>
                <select name="id_tfk" id="no_tfk_penjualan" class="form-control select2">
                    <option value="">-- Pilih --</option>
                
                    </option>
                </select>
            </div>
            <div class="form-group col-sm-6">
                    <label>Nomor Retur <span class="tx-danger">*</span></label>
                    <input type="text" name="no_retur" class="form-control" value="<?php echo($kode); ?>" placeholder="-" required="required" />
            </div>
            <div class="form-group col-sm-6">
                <label>Keterangan  <span class="tx-danger"></span></label>
                <input type="text" name="keterangan" class="form-control" placeholder=""/>
            </div>
        </div>
         <div class="row">
            <div class="form-group col-sm-12">
                <label>Nomor Faktur <span class="tx-danger">*</span></label>
				<table class="table table-hover mg-b-0">
					<thead>
						<tr>
							<th><center>Produk</center></th>
							<th><center>No Batch</center></th>
							 <th><center>Expired Date</center></th> 
							 	<th><center>Qty</center></th> 
							 <th><center>Gudang</center></th> 
							<th><center>Hapus</center></th>
						</tr>
					</thead>
					<tbody id="tbllegal"></tbody>
				</table>
                <a onclick="additem('tbllegal', 'jumlegal', 'returbarang')"><span class="badge badge-success"><i class="fa fa-plus-circle"></i> Add Data</span></a>
            </div>
		</div>
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <div class="row">
            <div class="col-sm-12">
                <a href="<?php echo("$sistem/retur"); ?>" title="Batal"><button type="button" class="btn btn-secondary btn-xs">Batal</button></a>
                <button type="submit" id="bsave" class="btn btn-dark btn-xs">Simpan</button>
                <div id="imgloading"></div>
            </div>
		</div>
        <!-- <div class="row">
            <div class="textarea col-sm-12">
                 <a href="<?php echo("$sistem/finance"); ?>" title="Batal"><button type="button" class="btn btn-secondary btn-xs">Batal</button></a>

                <button type="submit" id="bsave" class="btn btn-dark">Simpan</button>
                <div id="imgloading"></div>
            </div>
		</div> -->
		</form>
    </div>
</div>

<!-- <div>
    json_encode($_REQUEST_api)
</div> -->