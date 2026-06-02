<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Perusahaan</a></li>
                <li class="breadcrumb-item active" aria-current="page">Perusahaan</li>
            </ol>
        </nav>
        <h4 class="content-title">Edit Data - Perusahaan</h4>
    </div>
</div>
<div class="content-body">
    <div class="component-section no-code">
        <h5 id="section1" class="tx-semibold">Data Perusahaan</h5>
        <p class="mg-b-25">Informasi data-data perusahaan.</p>
		<?php
			$read	= $conn->prepare("SELECT * FROM sistem WHERE id_sis=1");
			$read->execute();
			$view	= $read->fetch(PDO::FETCH_ASSOC);
		?>        
        <form id="formtransaksi" action="#" method="post" autocomplete="off" enctype="multipart/form-data">
        <input type="hidden" name="nmenu" id="nmenu" value="sistem" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="update" readonly="readonly" />
        <div class="row row-sm">
            <div class="form-group col-sm-3">
                <label>Nama Perusahaan <span class="tx-danger">*</span></label>
                <input type="text" name="pete" class="form-control" value="<?php echo($view['pt_sis']); ?>" placeholder="ex. -" required="required" />
            </div>
            <div class="form-group col-sm-3">
                <label>NPWP <span class="tx-danger">*</span></label>
                <input type="text" name="npwp" class="form-control" value="<?php echo($view['npwp_sis']); ?>" placeholder="ex. -" required="required" />
            </div>
            <div class="form-group col-sm-3">
                <label>Telepon <span class="tx-danger">*</span></label>
                <input type="text" name="telp" class="form-control" value="<?php echo($view['telp_sis']); ?>" placeholder="ex. -" required="required" />
            </div>
            <div class="form-group col-sm-3">
                <label>Email <span class="tx-danger">*</span></label>
                <input type="email" name="email" class="form-control" value="<?php echo($view['email_sis']); ?>" placeholder="ex. -" required="required" />
            </div>
		</div>
		<div class="row row-sm">
            <div class="form-group col-sm-3">
                <label>Nomor Izin PBF <span class="tx-danger">*</span></label>
                <input type="text" name="pbf" class="form-control" value="<?php echo($view['pbf_sis']); ?>" placeholder="ex. -" required="required" />
            </div>
            <div class="form-group col-sm-3">
                <label>Jatuh Tempo Izin PBF <span class="tx-danger">*</span></label>
                <input type="text" name="tpbf" class="form-control datepicker" value="<?php echo($view['tpbf_sis']); ?>" placeholder="9999-99-99" required="required" />
            </div>
            <div class="form-group col-sm-3">
                <label>Nomor Izin CDOB <span class="tx-danger">*</span></label>
                <input type="text" name="cdob" class="form-control" value="<?php echo($view['cdob_sis']); ?>" placeholder="ex. -" required="required" />
            </div>
            <div class="form-group col-sm-3">
                <label>Jatuh Tempo Izin CDOB <span class="tx-danger">*</span></label>
                <input type="text" name="tcdob" class="form-control datepicker" value="<?php echo($view['tcdob_sis']); ?>" placeholder="9999-99-99" required="required" />
            </div>
		</div>
		<div class="row row-sm">
            <div class="form-group col-sm-3">
                <label>Nomor Izin SIPA <span class="tx-danger">*</span></label>
                <input type="text" name="sipa" class="form-control" value="<?php echo($view['sipa_sis']); ?>" placeholder="ex. -" required="required" />
            </div>
            <div class="form-group col-sm-3">
                <label>Jatuh Tempo Izin SIPA <span class="tx-danger">*</span></label>
                <input type="text" name="tsipa" class="form-control datepicker" value="<?php echo($view['tsipa_sis']); ?>" placeholder="9999-99-99" required="required" />
            </div>
            <div class="form-group col-sm-3">
                <label>Apoteker Penanggung Jawab <span class="tx-danger">*</span></label>
                <input type="text" name="apoteker" class="form-control" value="<?php echo($view['apoteker_sis']); ?>" placeholder="ex. -" required="required" />
            </div>
            <div class="form-group col-sm-3">
                <label>Alamat <span class="tx-danger">*</span></label>
                <textarea name="alamat" class="form-control" placeholder="Ketik alamat di sini..." required="required"><?php echo($view['alamat_sis']); ?></textarea>
            </div>
        </div>
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <h5 id="section1" class="tx-semibold">Account Bank</h5>
        <p class="mg-b-25">Informasi data-data bank perusahaan.</p>
        <div class="row row-sm">
            <div class="form-group col-sm-3">
                <label>Bank <span class="tx-danger">*</span></label>
                <input type="text" name="bank" class="form-control" value="<?php echo($view['bank_sis']); ?>" placeholder="ex. -" required="required" />
            </div>
            <div class="form-group col-sm-3">
                <label>No. Rekening <span class="tx-danger">*</span></label>
                <input type="text" name="norek" class="form-control" value="<?php echo($view['norek_sis']); ?>" placeholder="ex. -" required="required" />
            </div>
            <div class="form-group col-sm-3">
                <label>Account <span class="tx-danger">*</span></label>
                <input type="text" name="anam" class="form-control" value="<?php echo($view['anam_sis']); ?>" placeholder="ex. -" required="required" />
            </div>
        </div>
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <h5 id="section1" class="tx-semibold">Limit Expired, Stok & Tagihan</h5>
        <p class="mg-b-25">Parameter limit expired, minimal stok, tagihan supplier dan outlet.</p>
        <div class="row row-sm">
            <div class="form-group col-sm-3">
                <label>Expired Date <span class="tx-danger">*</span></label>
                <input type="text" name="limited" class="form-control" value="<?php echo($view['limit_expired']); ?>" placeholder="0" required="required" />
            </div>
            <?php /*
            <div class="form-group col-sm-3">
                <label>Minimal Stok <span class="tx-danger">*</span></label>
                <input type="text" name="limitstok" class="form-control" value="<?php echo($view['limit_stok']); ?>" placeholder="0" required="required" />
            </div>
			*/ ?>
            <div class="form-group col-sm-3">
                <label>Tagihan Supplier <span class="tx-danger">*</span></label>
                <input type="text" name="limitsup" class="form-control" value="<?php echo($view['limit_supplier']); ?>" placeholder="0" required="required" />
            </div>
            <div class="form-group col-sm-3">
                <label>Tagihan Outlet <span class="tx-danger">*</span></label>
                <input type="text" name="limitout" class="form-control" value="<?php echo($view['limit_outlet']); ?>" placeholder="0" required="required" />
            </div>
        </div>
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <h5 id="section1" class="tx-semibold">Sistem Aplikasi</h5>
        <p class="mg-b-25">Data sistem aplikasi website.</p>
        <div class="row row-sm">
            <div class="form-group col-sm-3">
                <label>Nama Sistem <span class="tx-danger">*</span></label>
                <input type="text" name="namasis" class="form-control" value="<?php echo($view['nama_sis']); ?>" placeholder="ex. -" required="required" />
            </div>
            <div class="form-group col-sm-3">
                <label>Nama Aplikasi <span class="tx-danger">*</span></label>
                <input type="text" name="namaap" class="form-control" value="<?php echo($view['app_sis']); ?>" placeholder="ex. -" required="required" />
            </div>
            <div class="form-group col-sm-3">
                <label>Tagline <span class="tx-danger">*</span></label>
                <input type="text" name="tagline" class="form-control" value="<?php echo($view['tagline_sis']); ?>" placeholder="ex. -" required="required" />
            </div>
            <div class="form-group col-sm-3">
                <label>URL Sistem <span class="tx-danger">*</span></label>
                <input type="text" name="urlsis" class="form-control" value="<?php echo($view['url_sis']); ?>" placeholder="ex. -" required="required" />
            </div>
		</div>
        <div class="row row-sm">
            <div class="form-group col-sm-3">
                <label>Logo <span class="tx-danger">*</span></label>
                <div class="clearfix"></div>
                <button type="button" class="btn btn-primary" id="tombol1" onclick="namafile('tombol1', 'foto1')"><i class="fa fa-cloud-upload"></i> Upload di sini...</button>
                <input type="file" id="foto1" name="foto1" hidden="hidden" />
                <div id="imgloading"></div>
            </div>
            <div class="form-group col-sm-3">
                <label>Favicon <span class="tx-danger">*</span></label>
                <div class="clearfix"></div>
                <button type="button" class="btn btn-info" id="tombol2" onclick="namafile('tombol2', 'foto2')"><i class="fa fa-cloud-upload"></i> Upload di sini...</button>
                <input type="file" id="foto2" name="foto2" hidden="hidden" />
                <div id="imgloading"></div>
            </div>
            <div class="form-group col-sm-3">
                <label>Cap Apoteker <span class="tx-danger">*</span></label>
                <div class="clearfix"></div>
                <button type="button" class="btn btn-warning" id="tombol3" onclick="namafile('tombol3', 'foto3')"><i class="fa fa-cloud-upload"></i> Upload di sini...</button>
                <input type="file" id="foto3" name="foto3" hidden="hidden" />
            </div>
        </div>
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <div class="row row-sm">
            <div class="col-sm-12">
                <a href="<?php echo("$sistem/sistem"); ?>" title="Batal">
                <button type="button" class="btn btn-secondary">Batal</button>
				</a>
                <button type="submit" id="bsave" class="btn btn-dark">Update</button>
            </div>
		</div>
		</form>
    </div>
</div>