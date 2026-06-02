<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Mitra</a></li>
                <li class="breadcrumb-item active" aria-current="page">Supplier</li>
            </ol>
        </nav>
        <h4 class="content-title">Input Data - Supplier</h4>
    </div>
</div>
<input type="hidden" name="caridata" id="caridata" value="-" readonly="readonly" />
<input type="hidden" name="halaman" id="halaman" value="1" readonly="readonly" />
<input type="hidden" name="maximal" id="maximal" value="15" readonly="readonly" />
<div class="content-body">
    <div class="component-section no-code">
        <h5 id="section1" class="tx-semibold">Informasi Supplier</h5>
        <p class="mg-b-25">Informasi data-data dasar supplier.</p>
        <form id="formtransaksi" action="#" method="post" enctype="multipart/form-data" autocomplete="off">
        <input type="hidden" name="nmenu" id="nmenu" value="supplier" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="input" readonly="readonly" />
        <div class="row row-sm">
            <div class="col-sm-3">
                <label>Nama Supplier <span class="tx-danger">*</span></label>
                <input type="text" name="nama" class="form-control" placeholder="ex. PT. Farmasi Sejahtera" required="required" />
            </div>
            <div class="col-sm-3">
                <label>Contact Person <span class="tx-danger">*</span></label>
                <input type="text" name="coper" class="form-control" placeholder="ex. Anton Adrian" required="required" />
            </div>
            <div class="col-sm-3">
                <label>Kategori <span class="tx-danger">*</span></label>
				<select name="kategori" class="form-control select2" required="required">
                	<option value="">-- Pilih --</option>
				<?php
					$master	= $conn->prepare("SELECT id_ksp, nama_ksp FROM kategori_supplier ORDER BY nama_ksp ASC");
					$master->execute();
					while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
				?>
                	<option value="<?php echo($hasil['id_ksp']); ?>"><?php echo($hasil['nama_ksp']); ?></option>
                <?php } ?>
                </select>
            </div>
            <div class="col-sm-3">
                <label>NPWP <span class="tx-danger">*</span></label>
                <input type="text" name="npwp" class="form-control" placeholder="0" required="required" />
            </div>
        </div><!-- row -->
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <h5 id="section1" class="tx-semibold">Kontak Supplier</h5>
        <p class="mg-b-25">Lengkapi kontak dan alamat lengkap supplier.</p>
        <div class="row row-sm">
            <div class="col-sm-3">
                <label>Telp. <span class="tx-danger">*</span></label>
                <input type="text" name="telp" class="form-control" placeholder="ex. (021) 9392929" required="required" />
            </div>
            <div class="col-sm-3">
                <label>Hp. / WA <span class="tx-danger">*</span></label>
                <input type="text" name="hape" class="form-control" placeholder="ex. 0838-7999-2274" />
            </div>
            <div class="col-sm-3">
                <label>Fax <span class="tx-danger">*</span></label>
				<input type="text" name="fax" class="form-control" placeholder="ex. (021) 9392929" />
            </div>
            <div class="col-sm-3">
                <label>Email <span class="tx-danger">*</span></label>
				<input type="email" name="email" class="form-control" placeholder="ex. supplier@gmail.com" required="required" />
            </div>
            <div class="col-sm-3 mg-t-10">
                <label>Website <span class="tx-danger">*</span></label>
				<input type="text" name="website" class="form-control" placeholder="ex. www.supplier.com" />
            </div>
            <div class="col-sm-3 mg-t-10">
                <label>Provinsi <span class="tx-danger">*</span></label>
				<select name="provinsi" id="provinsi" class="form-control select2" onchange="selectdata('provinsi', 'carikabupaten', 'kabupaten')" required="required">
                	<option value="">-- Pilih --</option>
				<?php
					$master	= $conn->prepare("SELECT id_rpo, nama_rpo FROM regional_provinsi ORDER BY nama_rpo ASC");
					$master->execute();
					while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
				?>
                	<option value="<?php echo($hasil['id_rpo']); ?>"><?php echo($hasil['nama_rpo']); ?></option>
                <?php } ?>
                </select>
            </div>
            <div class="col-sm-3 mg-t-10">
                <label>Kab. / Kota <span class="tx-danger">*</span></label>
				<select name="kabupaten" id="kabupaten" class="form-control select2" required="required">
                	<option value="">-- Pilih --</option>
                </select>
            </div>
            <div class="col-sm-3 mg-t-10">
                <label>Kode Pos <span class="tx-danger">*</span></label>
				<input type="text" name="kopos" class="form-control" placeholder="ex. 45265" />
            </div>
            <div class="col-sm-3 mg-t-10">
                <label>Alamat Kantor <span class="tx-danger">*</span></label>
				<textarea name="alamat" class="form-control" placeholder="Ketik di sini..." required="required"></textarea>
            </div>
        </div>
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <h5 id="section1" class="tx-semibold">Diskon & Kondisi</h5>
        <p class="mg-b-25">Aturan diskon & kondisi transaksi outlet.</p>
        <div class="row row-sm">
            <div class="col-sm-3">
                <label>Pembelian Minimal <span class="tx-danger">*</span></label>
                <input type="text" name="mini" class="form-control" placeholder="0" required="required" />
            </div>            
            <div class="col-sm-3">
                <label>Diskon <= Minimal <span class="tx-danger">*</span></label>
                <input type="text" name="dismi" class="form-control" placeholder="0" required="required" />
            </div>            
            <div class="col-sm-3">
                <label>Diskon > Minimal <span class="tx-danger">*</span></label>
                <input type="text" name="disma" class="form-control" placeholder="0" required="required" />
            </div>            
            <div class="col-sm-3">
                <label>TOP <span class="tx-danger">*</span></label>
                <input type="text" name="limit" class="form-control" placeholder="0" required="required" />
            </div>
        </div>
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <div class="row row-sm">
            <div class="col-sm-12">
                <a href="<?php echo($data->sistem('url_sis').'/supplier'); ?>" title="Batal">
                <button type="button" class="btn btn-secondary btn-xs">Batal</button>
				</a>
                <button type="submit" id="bsave" class="btn btn-dark btn-xs">Simpan</button>
            </div>
		</div>
		</form>
    </div>
</div>