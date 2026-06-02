<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Receive</a></li>
                <li class="breadcrumb-item active" aria-current="page">Penerimaan Barang Transfer IN</li>
            </ol>
        </nav>
        <h4 class="content-title">Input Data - Barang Transfer IN</h4>
    </div>
</div>
<?php
	$unik	= "/TRFIN/".$data->romawi(date('m')).'/'.date('y');
	$kode	= $data->transcode($unik, "transfer_tre", "transfer_in");
?>
<div class="content-body">
    <div class="component-section no-code">
        <h5 id="section1" class="tx-semibold">Informasi Barang Transfer IN</h5>
        <p class="mg-b-25">Informasi data-data Transfer Produk IN.</p>
        <form id="formmenu" action="#" method="post" autocomplete="off">
        <input type="hidden" name="namamodal" id="namamodal" value="rorder" readonly="readonly" />
        <input type="hidden" name="namamenu" value="input" readonly="readonly" />
        <div class="row row-sm">
            <div class="col-sm-3 mg-t-10">
                <label>Nomor Tranfer <span class="tx-danger">*</span></label>
                <input type="text" name="faktur" class="form-control" value="<?php echo($kode); ?>" placeholder="-" required="required" />
            </div>
            <div class="col-sm-3 mg-t-10">
                <label>Tanggal Penerimaan Barang<span class="tx-danger">*</span></label>
                <input type="text" name="tglfaktur" class="form-control datepicker" placeholder="9999-99-99" required="required" />
            </div>
            <div class="col-sm-3 mg-t-10">
                <label>Status <span class="tx-danger">*</span></label>
                <select  name="status"  class="form-control sumoselect" value="<?php echo($view['status']); ?>"  required="required">

                      <option value="P">P</option>
                      <option value="NP">NP</option>
                        
                 </select>
            </div>
        </div><!-- row -->
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <h5 id="section1" class="tx-semibold">Produk IN</h5>
        <p class="mg-b-25">Input produk yang akan masuk.</p>
        <div class="row row-sm">
            <div class="col-sm-12">
            	<div class="table-responsive">
				<table class="tabeltransaksi">
                	<thead>
                    	<tr>
                            <th>Produk</th>
                            <th>Batchcode</th>
                            <th>Tgl. ED</th>
                            <th>Gudang</th>
                            <th>Jumlah Barang</th>
                            <th><center>Act</center></th>
                        </tr>
                    </thead>
                    <tbody id="dataaddtransfer"></tbody>
                </table>
                </div>
                <input type="hidden" name="jumaaddtransfer" id="jumaaddtransfer" value="0" readonly="readonly" />
                <a onclick="addmaximal('addtransfer')"><span class="badge badge-success"><i class="fa fa-plus-circle"></i> Add Data</span></a>
            </div>
        </div>
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <div class="row row-sm">
            <div class="col-sm-12">
                <a href="<?php echo("$sistem/tfstok"); ?>" title="Batal">
                <button type="button" class="btn btn-secondary btn-xs">Batal</button>
				</a>
                <button type="submit" id="bsave" class="btn btn-dark btn-xs">Simpan</button>
                <div id="imgloading"></div>
            </div>
		</div>
		</form>
    </div>
</div>