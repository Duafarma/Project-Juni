<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$conn	= $base->open();
	$modal	= $secu->injection(@$_GET['modal']);
	
	switch($modal){
		case "input":
?>
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Input Data - Master MR</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formtransaksi" action="#" method="post" autocomplete="off">
        <input type="hidden" name="nmenu" id="nmenu" value="mastermr" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="input" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-12">
                    <label>Nama MR <span class="tx-danger">*</span></label>
                    <input type="text" name="nama_mr" class="form-control" placeholder="Nama MR..." required="required" />
                </div>
                <div class="form-group col-md-12">
                    <label>Area <span class="tx-danger">*</span></label>
                    <input type="text" name="area" class="form-control" placeholder="Area..." required="required" />
                </div>
                <div class="form-group col-md-12">
                    <label>Keterangan</label>
                    <textarea name="ket" class="form-control" placeholder="Keterangan..." rows="3"></textarea>
                </div>
			</div>
            <div id="imgloading"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Batal</button>
            <button type="submit" id="bsave" class="btn btn-dark btn-xs">Simpan</button>
        </div>
		</form>
<?php
		break;
		case "update":
		$kode	= $secu->injection($_GET['keycode']);
		$read	= $conn->prepare("SELECT nama_mr, area, ket FROM master_mr WHERE id_mr=:kode");
		$read->bindParam(':kode', $kode, PDO::PARAM_STR);
		$read->execute();
		$view	= $read->fetch(PDO::FETCH_ASSOC);
?>
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Update Data - Master MR</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formtransaksi" action="#" method="post" autocomplete="off">
        <input type="hidden" name="nmenu" id="nmenu" value="mastermr" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="update" readonly="readonly" />
        <input type="hidden" name="keycode" id="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-12">
                    <label>Nama MR <span class="tx-danger">*</span></label>
                    <input type="text" name="nama_mr" class="form-control" value="<?php echo($view['nama_mr']); ?>" placeholder="Nama MR..." required="required" />
                </div>
                <div class="form-group col-md-12">
                    <label>Area <span class="tx-danger">*</span></label>
                    <input type="text" name="area" class="form-control" value="<?php echo($view['area']); ?>" placeholder="Area..." required="required" />
                </div>
                <div class="form-group col-md-12">
                    <label>Keterangan</label>
                    <textarea name="ket" class="form-control" placeholder="Keterangan..." rows="3"><?php echo($view['ket']); ?></textarea>
                </div>
			</div>
            <div id="imgloading"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Batal</button>
            <button type="submit" id="bsave" class="btn btn-dark btn-xs">Update</button>
        </div>
		</form>
<?php
		break;
		case "delete":
		$kode	= $secu->injection($_GET['keycode']);
?>
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Konfirmasi</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formtransaksi" action="#" method="post" autocomplete="off">
        <input type="hidden" name="nmenu" id="nmenu" value="mastermr" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="delete" readonly="readonly" />
        <input type="hidden" name="keycode" id="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-12">
                    <div class="alert alert-danger alert-dismissible mg-b-0 fade show" role="alert">
                        <strong>Informasi!</strong> Hapus data Master MR?
                    </div>
                </div>
			</div>
            <div id="imgloading"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Batal</button>
            <button type="submit" id="bsave" class="btn btn-dark btn-xs">Hapus</button>
        </div>
		</form>
<?php
		break;
	}
	$conn	= $base->close();
?>
	<script type="text/javascript" src="<?php echo($data->sistem('url_sis').'/config/js/fazlurr.js'); ?>"></script>
