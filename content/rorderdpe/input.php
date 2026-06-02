<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Receive</a></li>
                <li class="breadcrumb-item active" aria-current="page">Penerimaan Barang</li>
            </ol>
        </nav>
        <h4 class="content-title">Input Data - Penerimaan Barang DPE</h4>
    </div>
</div>
<div class="content-body">
    <div class="component-section no-code">
        <h5 id="section1" class="tx-semibold">Informasi Order</h5>
        <p class="mg-b-25">Informasi data-data order.</p>
        <form id="formmenu" action="#" method="post" autocomplete="off">
        <input type="hidden" name="namamodal" id="namamodal" value="rorderdpe" readonly="readonly" />
        <input type="hidden" name="namamenu" value="input" readonly="readonly" />
        <div class="row row-sm">
            <div class="col-sm-3">
                <label>Nomor PO <span class="tx-danger">*</span></label>
				<select name="invoice" id="invoice" class="form-control select2" onchange="cariorderdpe()" required="required">
                	<option value="">-- Select Nomor PO --</option>
			        <?php
                        $proses = 'Open';
                        $supplier = 'SUP1725675322'; // Filter by this supplier
                        $master = $conn->prepare("SELECT A.id_tor, A.kode_tor FROM transaksi_order AS A LEFT JOIN supplier AS B ON A.id_sup = B.id_sup WHERE A.proses_tor=:proses AND A.id_sup=:supplier ORDER BY A.tgl_tor ASC");
                        $master->bindParam(':proses', $proses, PDO::PARAM_STR);
                        $master->bindParam(':supplier', $supplier, PDO::PARAM_STR); // Bind the supplier parameter
                        $master->execute();
                        while ($hasil = $master->fetch(PDO::FETCH_ASSOC)) {
                    ?>
                	<option value="<?php echo($hasil['id_tor']); ?>"><?php echo($hasil['kode_tor']); ?></option>
                <?php } ?>
                </select>
                <div id="imgloading1"></div>
            </div>
            <div class="col-sm-3">
                <label>Supplier <span class="tx-danger">*</span></label>
                <input type="text" name="supplier" id="supplier" class="form-control" style="background:#FFFFFF;" placeholder="-" readonly="readonly" />
            </div>
            <div class="col-sm-3">
                <label>Tgl. PO <span class="tx-danger">*</span></label>
                <input type="text" name="tglorder" id="tglorder" class="form-control" style="background:#FFFFFF;" placeholder="9999-99-99" readonly="readonly" />
            </div>
            <div class="col-sm-3">
                <label>Keterangan Dalam PO<span class="tx-danger">*</span></label>
                <input type="text" name="ketorder" id="ketorder" class="form-control" style="background:#FFFFFF;" placeholder="-" readonly="readonly" />
            </div>
            <div class="col-sm-3 mg-t-10">
                <label>Tgl. Terima <span class="tx-danger">*</span></label>
                <input type="text" name="tglterima" class="form-control datepicker" value="<?php echo(date('Y-m-d')); ?>" placeholder="9999-99-99" required="required" />
            </div>
            <div class="col-sm-3 mg-t-10">
                <label>Jatuh Tempo <span class="tx-danger">*</span></label>
                <input type="text" name="tgltempo" id="jatuhtempo" class="form-control datepicker" placeholder="9999-99-99" required="required" />
            </div>
            <div class="col-sm-3 mg-t-10">
                <label>Nomor Faktur <span class="tx-danger">*</span></label>
                <input type="text" name="faktur" class="form-control" placeholder="-" required="required" />
            </div>
             <div class="col-sm-3 mg-t-10">
                <label>Nomor SJ <span class="tx-danger">*</span></label>
                <input type="text" name="sj_tre" class="form-control" placeholder="-" required="required" />
            </div>
            <div class="col-sm-3 mg-t-10">
                <label>Tgl. Faktur <span class="tx-danger">*</span></label>
                <input type="text" name="tglfaktur" class="form-control datepicker" placeholder="9999-99-99" required="required" />
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
                            <th>Produk</th>
                            <th>Detail</th>
                            <th>Gudang</th>
                            <th>Batchcode</th>
                            <th>Tgl. ED</th>
                            <th>Harga</th>
                            <th>Order</th>
                            <th>Terima</th>
                            <th>Diskon</th>
                            <th>Total</th>
                            <th><center>Act</center></th>
                        </tr>
                    </thead>
                    <tbody id="dataaddorder"></tbody>
                    <tfoot id="footerorder"></tfoot>
                </table>
                </div>
                <input type="hidden" name="jumaddorder" id="jumaddorder" value="0" readonly="readonly" />
                <!--
                <a onclick="addmaximal('addorder', 20)"><span class="badge badge-success"><i class="fa fa-plus-circle"></i> Add Data</span></a>
            	-->
            </div>
        </div>
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <div class="row row-sm">
            <div class="col-sm-12">
                <a href="<?php echo("$sistem/rorderdpe"); ?>" title="Batal">
                <button type="button" class="btn btn-secondary btn-xs">Batal</button>
				</a>
                <button type="submit" id="bsave" class="btn btn-dark btn-xs">Simpan</button>
                <div id="imgloading"></div>
            </div>
		</div>
		</form>
    </div>
</div>