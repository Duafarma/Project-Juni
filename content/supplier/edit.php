<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">List Menu</a></li>
                <li class="breadcrumb-item active" aria-current="page">Cash & Bank</li>
            </ol>
        </nav>
        <h4 class="content-title">Edit Data - Outlet</h4>
    </div>
</div>
<?php
	$kode	= $secu->injection($_GET['keycode']);
	$read	= $conn->prepare("SELECT A.id_ksp, A.nama_sup, A.cp_sup, A.npwp_sup, B.id_rpo, B.id_rkb, B.telp_sal, B.fax_sal, B.hp_sal, B.email_sal, B.web_sal, B.kopos_sal, B.alamat_sal, C.top_sdi, C.parameter_sdi, C.diskon1_sdi, C.diskon2_sdi FROM supplier AS A INNER JOIN supplier_alamat AS B ON A.id_sup=B.id_sup LEFT JOIN supplier_diskon AS C ON A.id_sup=C.id_sup WHERE A.id_sup=:kode");
	$read->bindParam(':kode', $kode, PDO::PARAM_STR);
	$read->execute();
	$view	= $read->fetch(PDO::FETCH_ASSOC);
?>
<div class="content-body">
    <div class="component-section no-code">
        <h5 id="section1" class="tx-semibold">Informasi Outlet</h5>
        <p class="mg-b-25">Informasi data-data dasar supplier.</p>
        <input type="hidden" name="jumitem" id="jumitem" value="0" readonly="readonly" />
        <form id="formtransaksi" action="#" method="post" enctype="multipart/form-data" autocomplete="off">
        <input type="hidden" name="nmenu" id="nmenu" value="supplier" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="update" readonly="readonly" />
        <input type="hidden" name="keycode" id="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
        <div class="row row-sm">
            <div class="col-sm-3">
                <label>Nama Supplier <span class="tx-danger">*</span></label>
                <input type="text" name="nama" class="form-control" value="<?php echo($view['nama_sup']); ?>" placeholder="ex. PT. Farmasi Sejahtera" required="required" />
            </div>
            <div class="col-sm-3">
                <label>Contact Person <span class="tx-danger">*</span></label>
                <input type="text" name="coper" class="form-control" value="<?php echo($view['cp_sup']); ?>" placeholder="ex. Anton Adrian" required="required" />
            </div>
            <div class="col-sm-3">
                <label>Kategori <span class="tx-danger">*</span></label>
				<select name="kategori" class="form-control select2" required="required">
                	<option value="">-- Pilih --</option>
				<?php
					$master	= $conn->prepare("SELECT id_ksp, nama_ksp FROM kategori_supplier ORDER BY nama_ksp ASC");
					$master->execute();
					while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
						$pilih	= ($view['id_ksp']==$hasil['id_ksp']) ? 'selected="selected"' : ''; 
				?>
                	<option value="<?php echo($hasil['id_ksp']); ?>" <?php echo($pilih); ?>><?php echo($hasil['nama_ksp']); ?></option>
                <?php } ?>
                </select>
            </div>
            <div class="col-sm-3">
                <label>NPWP <span class="tx-danger">*</span></label>
                <input type="text" name="npwp" class="form-control" value="<?php echo($view['npwp_sup']); ?>" placeholder="0" required="required" />
            </div>
        </div><!-- row -->
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <h5 id="section1" class="tx-semibold">Kontak Supplier</h5>
        <p class="mg-b-25">Lengkapi kontak dan alamat lengkap supplier.</p>
        <div class="row row-sm">
            <div class="col-sm-3">
                <label>Telp. <span class="tx-danger">*</span></label>
                <input type="text" name="telp" class="form-control" value="<?php echo($view['telp_sal']); ?>" placeholder="ex. (021) 9392929" required="required" />
            </div>
            <div class="col-sm-3">
                <label>Hp. / WA <span class="tx-danger">*</span></label>
                <input type="text" name="hape" class="form-control" value="<?php echo($view['hp_sal']); ?>" placeholder="ex. 0838-7999-2274" />
            </div>
            <div class="col-sm-3">
                <label>Fax <span class="tx-danger">*</span></label>
				<input type="text" name="fax" class="form-control" value="<?php echo($view['fax_sal']); ?>" placeholder="ex. (021) 9392929" />
            </div>
            <div class="col-sm-3">
                <label>Email <span class="tx-danger">*</span></label>
				<input type="email" name="email" class="form-control" value="<?php echo($view['email_sal']); ?>" placeholder="ex. supplier@gmail.com" required="required" />
            </div>
            <div class="col-sm-3 mg-t-10">
                <label>Website <span class="tx-danger">*</span></label>
				<input type="text" name="website" class="form-control" value="<?php echo($view['web_sal']); ?>" placeholder="ex. www.supplier.com" />
            </div>
            <div class="col-sm-3 mg-t-10">
                <label>Provinsi <span class="tx-danger">*</span></label>
				<select name="provinsi" id="provinsi" class="form-control select2" onchange="selectdata('provinsi', 'carikabupaten', 'kabupaten')" required="required">
                	<option value="">-- Pilih --</option>
				<?php
					$master	= $conn->prepare("SELECT id_rpo, nama_rpo FROM regional_provinsi ORDER BY nama_rpo ASC");
					$master->execute();
					while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
						$pilih	= ($view['id_rpo']==$hasil['id_rpo']) ? 'selected="selected"' : ''; 
				?>
                	<option value="<?php echo($hasil['id_rpo']); ?>" <?php echo($pilih); ?>><?php echo($hasil['nama_rpo']); ?></option>
                <?php } ?>
                </select>
            </div>
            <div class="col-sm-3 mg-t-10">
                <label>Kab. / Kota <span class="tx-danger">*</span></label>
				<select name="kabupaten" id="kabupaten" class="form-control select2" required="required">
                	<option value="">-- Pilih --</option>
				<?php
					$master	= $conn->prepare("SELECT id_rkb, nama_rkb FROM regional_kabupaten WHERE id_rpo=:prov ORDER BY nama_rkb ASC");
					$master->bindParam(':prov', $view['id_rpo'], PDO::PARAM_STR);
					$master->execute();
					while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
						$pilih	= ($view['id_rkb']==$hasil['id_rkb']) ? 'selected="selected"' : ''; 
				?>
                	<option value="<?php echo($hasil['id_rkb']); ?>" <?php echo($pilih); ?>><?php echo($hasil['nama_rkb']); ?></option>
                <?php } ?>
                </select>
            </div>
            <div class="col-sm-3 mg-t-10">
                <label>Kode Pos <span class="tx-danger">*</span></label>
				<input type="text" name="kopos" class="form-control" value="<?php echo($view['kopos_sal']); ?>" placeholder="ex. 45265" />
            </div>
            <div class="col-sm-3 mg-t-10">
                <label>Alamat Kantor <span class="tx-danger">*</span></label>
				<textarea name="alamat" class="form-control" placeholder="Ketik di sini..." required="required"><?php echo($view['alamat_sal']); ?></textarea>
            </div>
        </div>
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <h5 id="section1" class="tx-semibold">Diskon & Kondisi</h5>
        <p class="mg-b-25">Aturan diskon & kondisi transaksi outlet.</p>
        <div class="row row-sm">
            <div class="col-sm-3">
                <label>Pembelian Minimal <span class="tx-danger">*</span></label>
                <input type="text" name="mini" class="form-control" value="<?php echo($view['parameter_sdi']); ?>" placeholder="0" required="required" />
            </div>            
            <div class="col-sm-3">
                <label>Diskon <= Minimal <span class="tx-danger">*</span></label>
                <input type="text" name="dismi" class="form-control" value="<?php echo($view['diskon1_sdi']); ?>" placeholder="0" required="required" />
            </div>            
            <div class="col-sm-3">
                <label>Diskon > Minimal <span class="tx-danger">*</span></label>
                <input type="text" name="disma" class="form-control" value="<?php echo($view['diskon2_sdi']); ?>" placeholder="0" required="required" />
            </div>            
            <div class="col-sm-3">
                <label>TOP <span class="tx-danger">*</span></label>
                <input type="text" name="limit" class="form-control" value="<?php echo($view['top_sdi']); ?>" placeholder="0" required="required" />
            </div>
        </div>
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
					<tbody id="tbldiskon">
                        <?php
                        $nomor = 0;
                        // Query to get all product discounts for this supplier
                        $discount = $conn->prepare("SELECT 
                            a.id_pro, a.persen_pds, 
                            b.kode_pro, b.nama_pro, b.berat_pro,
                            c.nama_kpr, c.satuan_kpr,
                            d.nama_spr
                            FROM produk_diskonsup AS a
                            LEFT JOIN produk AS b ON a.id_pro=b.id_pro
                            LEFT JOIN kategori_produk AS c ON b.id_kpr=c.id_kpr
                            LEFT JOIN satuan_produk AS d ON b.id_spr=d.id_spr
                            WHERE a.id_sup=:kode
                            ORDER BY b.nama_pro ASC");
                        $discount->bindParam(':kode', $kode, PDO::PARAM_STR);
                        $discount->execute();
                        while($hasil = $discount->fetch(PDO::FETCH_ASSOC)){
                            $nomor++;
                        ?>
                        <tr id="item<?php echo($nomor); ?>">
                            <td>
                            <a href="#modal1" onclick="mproduct(<?php echo($nomor); ?>, 'showproduct')" data-toggle="modal">
                            <div id="noproduct<?php echo($nomor); ?>">(<?php echo($hasil['kode_pro']); ?>) <?php echo($hasil['nama_pro']); ?></div>
                            </a>
                            <input type="hidden" name="product[]" id="product<?php echo($nomor); ?>" value="<?php echo($hasil['id_pro']); ?>" class="itemproduct" readonly="readonly" />
                            </td>
                            <td><div id="detailproduct<?php echo($nomor); ?>"><?php echo($hasil['nama_kpr']); ?> (<?php echo($hasil['berat_pro']); ?> <?php echo($hasil['nama_spr']); ?>)</div></td>
                            <td><div id="satuanqty<?php echo($nomor); ?>"><?php echo($hasil['satuan_kpr']); ?></div></td>
                            <td><input type="text" name="dispro[]" class="inputangka" value="<?php echo($hasil['persen_pds']); ?>" placeholder="0" required="required" /></td>
                            <td>
                            <center>
                                <a onclick="delprosup(<?php echo($nomor); ?>)"><span class="badge badge-danger"><i class="fa fa-times-circle"></i></span></a>
                            </center>
                            </td>
                        </tr>
                        <?php
                        }
                        // Update the counter for new items
                        ?>
                        <script>document.getElementById("jumitem").value = "<?php echo($nomor); ?>";</script>
                        </tbody>
				</table>
                <a onclick="disprosupplier()"><span class="badge badge-success"><i class="fa fa-plus-circle"></i> Add Data</span></a>
            </div>
		</div>
		
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <div class="row row-sm">
            <div class="col-sm-12">
                <a href="<?php echo($data->sistem('url_sis').'/supplier'); ?>" title="Batal">
                <button type="button" class="btn btn-secondary btn-xs">Batal</button>
				</a>
                <button type="submit" id="bsave" class="btn btn-dark btn-xs">Update</button>
            </div>
		</div>
		</form>
    </div>
</div>