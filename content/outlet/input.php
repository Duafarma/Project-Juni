<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">List Menu</a></li>
                <li class="breadcrumb-item active" aria-current="page">Cash & Bank</li>
            </ol>
        </nav>
        <h4 class="content-title">Input Data - Contact</h4>
    </div>
</div>
<!--
<input type="hidden" name="caridata" id="caridata" value="-" readonly="readonly" />
<input type="hidden" name="halaman" id="halaman" value="1" readonly="readonly" />
<input type="hidden" name="maximal" id="maximal" value="15" readonly="readonly" />
-->
<input type="hidden" name="jumlegal" id="jumlegal" value="0" readonly="readonly" />
<input type="hidden" name="jumitem" id="jumitem" value="0" readonly="readonly" />
<div class="content-body">
    <div class="component-section no-code">
        <h5 id="section1" class="tx-semibold">Informasi Outlet</h5>
        <p class="mg-b-25">Informasi data-data dasar outlet.</p>
        <form id="formtransaksi" action="#" method="post" enctype="multipart/form-data" autocomplete="off">
        <input type="hidden" name="nmenu" id="nmenu" value="outlet" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="input" readonly="readonly" />
        <div class="row">
            <div class="form-group col-sm-3">
                <label>Nama Outlet <span class="tx-danger">*</span></label>
                <input type="text" name="namaoutlet" class="form-control" placeholder="Type here..." required="required" />
            </div>
            <div class="form-group col-sm-3">
                <label>Nama Resmi <span class="tx-danger">*</span></label>
                <input type="text" name="namaresmi" class="form-control" placeholder="Type here..." required="required" />
            </div>
            <div class="form-group col-sm-3">
                <label>Kategori <span class="tx-danger">*</span></label>
				<select name="kategori" class="form-control select2" required="required">
                	<option value="">-- Pilih --</option>
				<?php
					$master	= $conn->prepare("SELECT id_kot, kode_kot, nama_kot FROM kategori_outlet ORDER BY nama_kot ASC");
					$master->execute();
					while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
				?>
                	<option value="<?php echo($hasil['id_kot']); ?>"><?php echo("$hasil[kode_kot] - $hasil[nama_kot]"); ?></option>
                <?php } ?>
                </select>
            </div>
            <div class="form-group col-sm-3">
                <label>NPWP <span class="tx-danger">*</span></label>
                <input type="text" name="npwp" class="form-control" placeholder="Type here..." required="required" />
            </div>
            <div class="form-group col-sm-3">
                <label>Officer Code <span class="tx-danger">*</span></label>
                <input type="text" name="ofcode" class="form-control" placeholder="Type here..." required="required" />
            </div>
            <div class="form-group col-sm-3">
                <label>Ket. Lainnya <span class="tx-black">*</span></label>
                <input type="text" name="kete" class="form-control" placeholder="Type here..." />
            </div>
        </div><!-- row -->
		<div class="row">
            <div class="form-group col-sm-12">
                <label>Legal Outlet <span class="tx-danger">*</span></label>
				<table class="table table-hover mg-b-0">
					<thead>
						<tr>
							<th>Legal</th>
							<th>Ket.</th>
							<th>Expired Date</th>
							<th><center>#</center></th>
						</tr>
					</thead>
					<tbody id="tbllegal"></tbody>
				</table>
                <a onclick="additem('tbllegal', 'jumlegal', 'legaloutlet')"><span class="badge badge-success"><i class="fa fa-plus-circle"></i> Add Data</span></a>
            </div>
		</div>
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <h5 id="section1" class="tx-semibold">Kontak Outlet</h5>
        <p class="mg-b-25">Lengkapi kontak dan alamat lengkap outlet.</p>
        <div class="row">
            <div class="form-group col-sm-3">
                <label>Telp. <span class="tx-danger">*</span></label>
                <input type="text" name="telp" class="form-control" placeholder="Type here..." required="required" />
            </div>
            <div class="form-group col-sm-3">
                <label>Hp. / WA <span class="tx-black">*</span></label>
                <input type="text" name="hape" class="form-control" placeholder="Type here..." />
            </div>
            <div class="form-group col-sm-3">
                <label>Fax <span class="tx-black">*</span></label>
				<input type="text" name="fax" class="form-control" placeholder="Type here..." />
            </div>
            <div class="form-group col-sm-3">
                <label>Email <span class="tx-danger">*</span></label>
				<input type="email" name="email" class="form-control" placeholder="Type here..." required="required" />
            </div>
            <div class="form-group col-sm-3">
                <label>Website <span class="tx-black">*</span></label>
				<input type="text" name="website" class="form-control" placeholder="Type here..." />
            </div>
            <div class="form-group col-sm-3">
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
            <div class="form-group col-sm-3">
                <label>Kab. / Kota <span class="tx-danger">*</span></label>
				<select name="kabupaten" id="kabupaten" class="form-control select2" required="required">
                	<option value="">-- Pilih --</option>
                </select>
            </div>
            <div class="form-group col-sm-3">
                <label>Kode Pos <span class="tx-black">*</span></label>
				<input type="text" name="kopos" class="form-control" placeholder="Type here..." />
            </div>
            <div class="form-group col-sm-3">
                <label>Jadwal Tukar Faktur <span class="tx-black">*</span></label>
				<textarea name="jadwal" class="form-control" placeholder="Type here..."></textarea>
            </div>
            <div class="form-group col-sm-3">
                <label>Alamat Kantor <span class="tx-danger">*</span></label>
				<textarea name="alamatkantor" class="form-control" placeholder="Type here..." required="required"></textarea>
            </div>
            <div class="form-group col-sm-3">
                <label>Alamat Pengiriman <span class="tx-black">*</span></label>
				<textarea name="alamatkirim" class="form-control" placeholder="Type here..."></textarea>
            </div>
            <div class="form-group col-sm-3">
                <label>Alamat Tukar Faktur <span class="tx-black">*</span></label>
				<textarea name="alamattukar" class="form-control" placeholder="Type here..."></textarea>
            </div>
        </div><!-- row -->
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <h5 id="section1" class="tx-semibold">PIC Outlet</h5>
        <p class="mg-b-25">Informasi PIC Procurement & Finance outlet.</p>
        <div class="row">
            <div class="form-group col-sm-3">
                <label>PIC Procurement <span class="tx-black">*</span></label>
				<input type="text" name="picp" class="form-control" placeholder="Type here..." />
            </div>
            <div class="form-group col-sm-3">
                <label>Hp. / WA <span class="tx-black">*</span></label>
				<input type="text" name="picpk" class="form-control" placeholder="Type here..." />
            </div>
            <div class="form-group col-sm-3">
                <label>PIC Finance <span class="tx-black">*</span></label>
				<input type="text" name="picf" class="form-control" placeholder="Type here..." />
            </div>
            <div class="form-group col-sm-3">
                <label>Hp. / WA <span class="tx-black">*</span></label>
				<input type="text" name="picfk" class="form-control" placeholder="Type here..." />
            </div>
        </div><!-- row -->
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <h5 id="section1" class="tx-semibold">Diskon & Kondisi</h5>
        <p class="mg-b-25">Aturan diskon & kondisi transaksi outlet.</p>
		<div class="row">
            <div class="form-group col-sm-3">
				<label>Diskon Produk <span class="tx-danger">*</span></label>
                <input type="text" name="diskon" class="form-control" placeholder="0" required="required" />
            </div>
            <div class="form-group col-sm-3">
                <label>TOP <span class="tx-danger">*</span></label>
                <input type="text" name="limit" class="form-control" placeholder="0" required="required" />
            </div>
        </div><!-- row -->
		<div class="row">
            <div class="form-group col-sm-12">
                <label>Diskon Produk <span class="tx-danger">*</span></label>
				<table class="table table-hover mg-b-0">
					<thead>
						<tr>
							<th>Produk</th>
							<th>Detail</th>
							<th>Satuan</th>
							<th>Diskon</th>
							<th><center>#</center></th>
						</tr>
					</thead>
					<tbody id="tbldiskon"></tbody>
				</table>
                <a onclick="disprotlet()"><span class="badge badge-success"><i class="fa fa-plus-circle"></i> Add Data</span></a>
            </div>
		</div>
        <!-- row -->
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <div class="row">
            <div class="col-sm-12">
                <a href="<?php echo("$sistem/outlet"); ?>" title="Batal"><button type="button" class="btn btn-secondary btn-xs">Batal</button></a>
                <button type="submit" id="bsave" class="btn btn-dark btn-xs">Simpan</button>
                <div id="imgloading"></div>
            </div>
		</div>
		</form>
    </div>
</div>