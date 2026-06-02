<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item">Tracking Sistem</li>
                <li class="breadcrumb-item active" aria-current="page">Faktur Pajak</li>
            </ol>
        </nav>
        <h4 class="content-title">Edit Data - Faktur Pajak</h4>
    </div>
</div>
<?php
	$kode	= $secu->injection($_GET['keycode']);
	$read	= $conn->prepare("SELECT	A.id_tfk,
						A.id_f_p,
                        A.status_f_pajak,
                        A.nomor_seri,
                        A.id_tfk,
						A.tanggal,
						A.keterangan,
						B.id_tfk,
						B.kode_tfk,
						B.tgl_tfk
					
					FROM
						faktur_pajak AS A
					LEFT JOIN transaksi_faktur AS B ON
						A.id_tfk = B.id_tfk WHERE A.id_f_p=:kode GROUP BY A.id_tfk");
	$read->bindParam(':kode', $kode, PDO::PARAM_STR);
	$read->execute();
	$view	= $read->fetch(PDO::FETCH_ASSOC);
?>
<div class="content-body">
    <div class="component-section no-code">
        <div>
            <div class="row row-sm">
                <div class="col-sm-6">
                    
                </div>
            </div>
        </div>
        
        <form id="formtransaksi" action="#" method="post" enctype="multipart/form-data" autocomplete="off">
        <input type="hidden" name="nmenu" id="nmenu" value="fakturpajak" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="update" readonly="readonly" />
        <input type="hidden" name="keycode" id="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
        <div class="row">
            
             <div class="form-group col-sm-6">
                <label>Nomor Seri Faktur Pajak <span class="tx-danger">*</span></label>
                <input type="text" name="nomor_seri" class="form-control " value="<?php echo($view['nomor_seri']); ?>" placeholder="Type here..." required="required" />
            </div>
           
            <div class="form-group col-sm-6">
                <label>Keterangan <span class="tx-danger">*</span></label>
				<textarea name="keterangan" class="form-control" value="<?php echo($view['keterangan']); ?>" placeholder="Type here..."></textarea>
            </div>
            <div class="form-group col-sm-6">
                <label>tanggal <span class="tx-danger">*</span></label>
                <input type="text" name="tanggal" class="form-control datepicker" value="<?php echo($view['tanggal']); ?>" placeholder="Type here..." required="required" />
            </div>
           
		</div>
        <!-- row -->
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <div class="row">
            <div class="col-sm-12">
                <a href="<?php echo($data->sistem('url_sis').'/outlet'); ?>" title="Batal">
                <button type="button" class="btn btn-secondary btn-xs">Batal</button>
				</a>
                <button type="submit" id="bsave" class="btn btn-dark btn-xs">Update</button>
            </div>
		</div>
		</form>
    </div>
</div>