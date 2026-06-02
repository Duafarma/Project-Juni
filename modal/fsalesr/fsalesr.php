<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$conn	= $base->open();
	$sistem	= $data->sistem('url_sis');
	$modal	= $secu->injection(@$_GET['modal']);
	switch($modal){
		case "update":
		$kode	= $secu->injection($_GET['keycode']);
		$read	= $conn->prepare("SELECT * FROM transaksi_faktur_r WHERE id_tfk=:kode");
		$read->bindParam(':kode', $kode, PDO::PARAM_STR);
		$read->execute();
		$view	= $read->fetch(PDO::FETCH_ASSOC);
?>
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Update Data - Faktur Retur</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formsalespnpr" action="#" method="post" autocomplete="off">
        <input type="hidden" name="namamodal" id="namamodal" value="fsalesr" readonly="readonly" />
        <input type="hidden" name="namamenu" value="update" readonly="readonly" />
        <input type="hidden" name="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-6">
                    <label>Nomor Faktur Retur <span class="tx-danger">*</span></label>
                    <input type="text" name="nomorfaktur" class="form-control" value="<?php echo($view['kode_tfk']); ?>" placeholder="Type here..." required="required" />
                </div>
                <div class="form-group col-md-6">
                    <label>Tgl. Retur <span class="tx-danger">*</span></label>
                    <input type="text" name="tglfak" class="form-control fortgl" value="<?php echo($view['tgl_tfk']); ?>" placeholder="9999-99-99" required="required" />
                </div>
			</div>
            <div class="row">
                <div class="form-group col-md-6">
                    <label>Nomor Retur <span class="tx-danger">*</span></label>
                    <input type="text" name="nomorsj" class="form-control" value="<?php echo($view['sj_tfk']); ?>" placeholder="Type here..." required="required" />
                </div>
                <div class="form-group col-md-6">
                    <label>Tgl. Retur <span class="tx-danger">*</span></label>
                    <input type="text" name="tglsj" class="form-control fortgl" value="<?php echo($view['tglsj_tfk']); ?>" placeholder="9999-99-99" required="required" />
                </div>
			</div>
            <div class="row">
                <div class="form-group col-md-6">
                    <label>Keterangan Retur <span class="tx-danger">*</span></label>
                    <input type="text" name="nomorpo" class="form-control" value="<?php echo($view['po_tfk']); ?>" placeholder="Type here..." required="required" />
                </div>
                <!-- <div class="form-group col-md-6">
                    <label>Tgl. PO <span class="tx-danger">*</span></label>
                    <input type="text" name="tglpo" class="form-control fortgl" value="<?php echo($view['tglpo_tfk']); ?>" placeholder="9999-99-99" required="required" />
                </div> -->
			</div>
            <!-- <div class="row">
                <div class="form-group col-md-6">
                    <label>Jatuh Tempo<span class="tx-danger">*</span></label>
                    <input type="text" name="jatuhtempo" class="form-control fortgl" value="<?php echo($view['tgl_limit']); ?>" placeholder="9999-99-99" required="required" />
                </div>
			</div> -->
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Batal</button>
            <button type="submit" id="bsave" class="btn btn-dark btn-xs">Update</button>
        </div>
		</form>

        <!-- Input Tuker Faktut -->
        <!-- <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Input Data Tuker Faktur </h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formsalespnp" action="#" method="post" autocomplete="off">
        <input type="hidden" name="namamodal" id="namamodal" value="fsales" readonly="readonly" />
        <input type="hidden" name="namamenu" value="tf" readonly="readonly" />
        <input type="hidden" name="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
        <div class="modal-body">
            
         <div class="row">
                <div class="form-group col-md-6">
                    <label>Tanggal Tuker Faktur <span class="tx-danger">*</span></label>
                    <input type="text" name="tglpo" class="form-control fortgl" value="<?php echo($view['tanggal_tuker_faktur']); ?>" placeholder="9999-99-99" required="required" />
                </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Batal</button>
            <button type="submit" id="bsave" class="btn btn-dark btn-xs">Input</button>
        </div>
		</form> -->


<?php
		break;
		case 'delete':
		$kode	= $secu->injection($_GET['keycode']);
		$read	= $conn->prepare("SELECT status_tfk, kode_tfk FROM transaksi_faktur_r WHERE id_tfk=:kode");
		$read->bindParam(':kode', $kode, PDO::PARAM_STR);
		$read->execute();
		$view	= $read->fetch(PDO::FETCH_ASSOC);
?>
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Konfirmasi</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formsalespnpr" action="#" method="post" autocomplete="off">
        <input type="hidden" name="namamodal" id="namamodal" value="fsalesr" readonly="readonly" />
        <input type="hidden" name="namamenu" value="delete" readonly="readonly" />
        <input type="hidden" name="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
        <input type="hidden" name="nomorfaktur" value="<?php echo($view['kode_tfk']); ?>" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-12">
                    <label><?php echo(($view['status_tfk']==='Faktur' || $view['status_tfk']==='Tagihan') ? 'Hapus data faktur RETUR?' : 'Data sudah diproses, tidak dapat dihapus!' ); ?> <span class="tx-danger">*</span></label>
                </div>
			</div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Batal</button>
            <?php echo(($view['status_tfk']==='Faktur' || $view['status_tfk']=='Tagihan') ? '<button type="submit" id="bsave" class="btn btn-dark btn-xs">Hapus</button>' : ''); ?>
        </div>
		</form>
<?php
		break;
	}
	$conn	= $base->close();
?>
	<script type="text/javascript" src="<?php echo("$sistem/config/js/fazlurr.js"); ?>"></script>
