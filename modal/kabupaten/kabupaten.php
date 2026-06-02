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
            <h6 class="modal-title" id="exampleModalLabel">Input Data - Kabupaten</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formtransaksi" action="#" method="post" autocomplete="off">
        <input type="hidden" name="nmenu" id="nmenu" value="kabupaten" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="input" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-12">
                    <label>Provinsi <span class="tx-danger">*</span></label>
                    <select name="provinsi" class="form-control" required="required">
                    	<option value="">-- Pilih Data --</option>
                    <?php
						$master		= $conn->prepare("SELECT id_rpo, nama_rpo FROM regional_provinsi ORDER BY nama_rpo ASC");
						$master->execute();
						while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
					?>
                    	<option value="<?php echo($hasil['id_rpo']); ?>"><?php echo($hasil['nama_rpo']); ?></option>
                    <?php } ?>
                    </select>
                </div>
			</div>
            <div class="row">
                <div class="form-group col-md-12">
                    <label>Kabupaten <span class="tx-danger">*</span></label>
                    <input type="text" name="nama" class="form-control" placeholder="ex. Indramayu" required="required" />
                    <div id="imgloading"></div>
                </div>
			</div>
            <div class="row">
                <div class="form-group col-md-12">
                    <label>Kode Kabupaten <span class="tx-danger">*</span></label>
                    <input type="number" name="kode_k" class="form-control" placeholder="ex. Indramayu" required="required" />
                    <div id="imgloading"></div>
                </div>
			</div>
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
		$read	= $conn->prepare("SELECT id_rpo, nama_rkb,kode_k FROM regional_kabupaten WHERE id_rkb=:kode");
		$read->bindParam(':kode', $kode, PDO::PARAM_STR);
		$read->execute();
		$view	= $read->fetch(PDO::FETCH_ASSOC);
?>
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Update Data - Kabupaten</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formtransaksi" action="#" method="post" autocomplete="off">
        <input type="hidden" name="nmenu" id="nmenu" value="kabupaten" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="update" readonly="readonly" />
        <input type="hidden" name="keycode" id="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-12">
                    <label>Provinsi <span class="tx-danger">*</span></label>
                    <select name="provinsi" class="form-control" required="required">
                    	<option value="">-- Pilih Data --</option>
                    <?php
						$master		= $conn->prepare("SELECT id_rpo, nama_rpo FROM regional_provinsi ORDER BY nama_rpo ASC");
						$master->execute();
						while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
							$pilih	= ($view['id_rpo']==$hasil['id_rpo']) ? 'selected="selected"' : '';
					?>
                    	<option value="<?php echo($hasil['id_rpo']); ?>" <?php echo($pilih); ?>><?php echo($hasil['nama_rpo']); ?></option>
                    <?php } ?>
                    </select>
                </div>
			</div>
            <div class="row">
                <div class="form-group col-md-12">
                    <label>Kabupaten <span class="tx-danger">*</span></label>
                    <input type="text" name="nama" class="form-control" value="<?php echo($view['nama_rkb']); ?>" placeholder="ex. Indramayu" required="required" />
                    <div id="imgloading"></div>
                </div>
			</div>
            <div class="row">
                <div class="form-group col-md-12">
                    <label>Kode Kabupaten <span class="tx-danger">*</span></label>
                    <input type="number" name="kode_k" class="form-control" value="<?php echo($view['kode_k']); ?>" placeholder="ex. Indramayu" required="required" />
                    <div id="imgloading"></div>
                </div>
			</div>
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
        <input type="hidden" name="nmenu" id="nmenu" value="kabupaten" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="delete" readonly="readonly" />
        <input type="hidden" name="keycode" id="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-12">
                    <div class="alert alert-danger alert-dismissible mg-b-0 fade show" role="alert">
                        <strong>Informasi!</strong> Hapus data kabupaten?
                    </div>
                    <div id="imgloading"></div>
                </div>
			</div>
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