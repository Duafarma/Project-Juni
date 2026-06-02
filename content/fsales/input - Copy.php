<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Penjualan</a></li>
                <li class="breadcrumb-item active" aria-current="page">Faktur Penjualan</li>
            </ol>
        </nav>
        <h4 class="content-title">Input Data - Faktur Penjualan</h4>
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
        <form id="formtransaksi" action="#" method="post" autocomplete="off">
        <input type="hidden" name="nmenu" id="nmenu" value="fsales" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="input" readonly="readonly" />
        <input type="hidden" name="keycode" value="<?php echo('FAK'.time()); ?>" readonly="readonly" />
        <div class="row row-sm">
            <div class="col-sm-3">
                <label>Nomor SJ <span class="tx-danger">*</span></label>
                <input type="text" name="invoice" id="koout" class="form-control" placeholder="-" />
            </div>
            <div class="col-sm-3">
                <label>Outlet <span class="tx-danger">*</span></label>
				<select name="outlet" id="outlet" class="form-control select2" onchange="ceksales()" required="required">
                	<option value="">-- Pilih --</option>
				<?php
					$status	= 'Active';
					$master	= $conn->prepare("SELECT id_out, kode_out, nama_out FROM outlet WHERE status_out=:status ORDER BY resmi_out ASC");
					$master->bindParam(':status', $status, PDO::PARAM_STR);
					$master->execute();
					while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
				?>
                	<option value="<?php echo($hasil['id_out']); ?>"><?php echo($hasil['nama_out']); ?></option>
                <?php } ?>
                </select>
            </div>
            <div class="col-sm-3">
                <label>Nomor Faktur <span class="tx-danger">*</span></label>
                <input type="text" name="nomorfaktur" id="fkout" class="form-control" placeholder="Ketik nomor faktur di sini..." />
            </div>
            <div class="col-sm-3">
                <label>Tanggal Faktur <span class="tx-danger">*</span></label>
                <input type="text" name="tglfaktur" class="form-control datepicker" value="<?php echo(date('Y-m-d')); ?>" placeholder="9999-99-99" />
            </div>
            <div class="col-sm-3 mg-t-10">
                <label>Tanggal SJ <span class="tx-danger">*</span></label>
                <input type="text" name="tglsales" class="form-control datepicker" value="<?php echo(date('Y-m-d')); ?>" placeholder="9999-99-99" />
            </div>
            <div class="col-sm-3 mg-t-10">
                <label>Nomor PO <span class="tx-danger">*</span></label>
                <input type="text" name="nomorpo" class="form-control" placeholder="Ketik nomor po di sini..." />
            </div>
            <div class="col-sm-3 mg-t-10">
                <label>Tanggal PO <span class="tx-danger">*</span></label>
                <input type="text" name="tglpo" class="form-control datepicker" value="<?php echo(date('Y-m-d')); ?>" placeholder="9999-99-99" />
            </div>
            <div class="col-sm-3 mg-t-10">
                <label>Jatuh Tempo<span class="tx-danger">*</span></label>
                <input type="text" name="jatuhtempo" id="jatuhtempo" class="form-control datepicker" placeholder="9999-99-99" required="required" />
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
                            <th>Batchcode</th>
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
                    <?php /*
                        <tr id="traddsales1">
                            <td>
                            <a href="#modal1" onclick="addsales(1, 'mproductsales')" data-toggle="modal">
                            <div id="noproduct1">Pilih</div>
                            </a>
                            <input type="hidden" name="kodestok[]" id="kodestok1" readonly="readonly" />
                            <input type="hidden" name="product[]" id="product1" readonly="readonly" />
                            <input type="hidden" name="prostok[]" id="prostok1" readonly="readonly" />
                            </td>
							<td><div id="prodetail1">-</div></td>
							<td><div id="nobcode1">-</div></td>
							<td><div id="tgled1">-</div></td>
                            <!--<td><input type="text" name="catatan[]" class="form-control" placeholder="Ketik di sini..." /></td>-->
                            <td><input type="text" name="harga[]" id="pharga1" class="inputangka" onkeyup="angka(this)" placeholder="0" readonly="readonly" /></td>
                            <td><input type="text" name="jumlah[]" id="pjumlah1" class="inputangka" value="1" onchange="jumlahsales(1)" onkeyup="angka(this)" placeholder="0" /></td>
                            <!--<td><input type="text" name="subtotal[]" id="psubtotal1" class="inputtotal" onkeyup="angka(this)" placeholder="0" readonly="readonly" /></td>-->
                            <td><input type="text" name="diskon[]" id="pdiskon1" class="inputangka" value="0" onchange="hitungsales(1)" onkeyup="angka(this)" placeholder="0" /></td>
                            <td><input type="text" name="total[]" id="ptotal1" class="inputtotal" onkeyup="angka(this)" placeholder="0" readonly="readonly" /></td>
                            <td>
                            <center>
                                <a onclick="delsales(1)"><span class="badge badge-danger"><i class="fa fa-times-circle"></i></span></a>
                            </center>
                            </td>
                        </tr>
						*/ ?>
					</tbody>
                    <tfoot>
                    	<tr>
                        	<td colspan="8"><div align="right"><b>SUBTOTAL</b></div></td>
                            <td><input type="text" name="pstotal" id="pstotal" class="inputtotal" onkeyup="angka(this)" placeholder="0" readonly="readonly" /></td>
                        	<td></td>
                        </tr>
                    	<tr>
                        	<td colspan="8"><div align="right"><b>PPN (10%)</b></div></td>
                            <td><input type="text" name="pppn" id="pppn" class="inputtotal" onkeyup="angka(this)" placeholder="0" readonly="readonly" /></td>
                        	<td></td>
                        </tr>
                    	<tr>
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
                <a href="<?php echo("$sistem/fsales"); ?>" title="Batal">
                <button type="button" class="btn btn-secondary">Batal</button>
				</a>
                <button type="submit" id="bsave" class="btn btn-dark">Simpan</button>
                <div id="imgloading"></div>
            </div>
		</div>
		</form>
    </div>
</div>