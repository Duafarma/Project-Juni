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
            <h6 class="modal-title" id="exampleModalLabel">Input Data - Hak Akses</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formtransaksi" action="#" method="post" autocomplete="off">
        <input type="hidden" name="nmenu" id="nmenu" value="hakakses" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="input" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-6">
                    <label>Level <span class="tx-danger">*</span></label>
                    <select name="jenisadmin" class="form-control" required="required">
                    	<option value="">-- Pilih Data --</option>
                    	<option value="Super">Super</option>
                    	<option value="Admin">Admin</option>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label>Group Menu <span class="tx-danger">*</span></label>
                    <input type="text" name="namamenu" class="form-control" placeholder="ex. Master Data" required="required" />
                </div>
			</div>
            <div class="row">
                <div class="form-group col-md-4">
                    <label>Tambah <span class="tx-danger">*</span></label>
                    <select name="tambahdata" class="form-control" required="required">
                    	<option value="">-- Pilih Data --</option>
                    	<option value="Ya">Ya</option>
                    	<option value="Tidak">Tidak</option>
                    </select>
                    <div id="imgloading"></div>
                </div>
                <div class="form-group col-md-4">
                    <label>Edit <span class="tx-danger">*</span></label>
                    <select name="editdata" class="form-control" required="required">
                    	<option value="">-- Pilih Data --</option>
                    	<option value="Ya">Ya</option>
                    	<option value="Tidak">Tidak</option>
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label>Delete <span class="tx-danger">*</span></label>
                    <select name="deletedata" class="form-control" required="required">
                    	<option value="">-- Pilih Data --</option>
                    	<option value="Ya">Ya</option>
                    	<option value="Tidak">Tidak</option>
                    </select>
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
		$read	= $conn->prepare("SELECT jenis_adm, nama_menu, tambah_data, edit_data, delete_data FROM adminz_akses WHERE id_aak=:kode");
		$read->bindParam(':kode', $kode, PDO::PARAM_STR);
		$read->execute();
		$view	= $read->fetch(PDO::FETCH_ASSOC);
?>
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Update Data - Hak Akses</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formtransaksi" action="#" method="post" autocomplete="off">
        <input type="hidden" name="nmenu" id="nmenu" value="hakakses" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="update" readonly="readonly" />
        <input type="hidden" name="keycode" id="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-6">
                    <label>Level <span class="tx-danger">*</span></label>
                    <select name="jenisadmin" class="form-control" required="required">
                    	<option value="">-- Pilih Data --</option>
                    	<option value="Super" <?php echo(($view['jenis_adm']=='Super') ? 'selected="selected"' : ''); ?>>Super</option>
                    	<option value="Admin" <?php echo(($view['jenis_adm']=='Admin') ? 'selected="selected"' : ''); ?>>Admin</option>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label>Group Menu <span class="tx-danger">*</span></label>
                    <input type="text" name="namamenu" class="form-control" value="<?php echo($view['nama_menu']); ?>" placeholder="ex. Master Data" required="required" />
                </div>
			</div>
            <div class="row">
                <div class="form-group col-md-4">
                    <label>Tambah <span class="tx-danger">*</span></label>
                    <select name="tambahdata" class="form-control" required="required">
                    	<option value="">-- Pilih Data --</option>
                    	<option value="Ya" <?php echo(($view['tambah_data']=='Ya') ? 'selected="selected"' : ''); ?>>Ya</option>
                    	<option value="Tidak" <?php echo(($view['tambah_data']=='Tidak') ? 'selected="selected"' : ''); ?>>Tidak</option>
                    </select>
                    <div id="imgloading"></div>
                </div>
                <div class="form-group col-md-4">
                    <label>Edit <span class="tx-danger">*</span></label>
                    <select name="editdata" class="form-control" required="required">
                    	<option value="">-- Pilih Data --</option>
                    	<option value="Ya" <?php echo(($view['edit_data']=='Ya') ? 'selected="selected"' : ''); ?>>Ya</option>
                    	<option value="Tidak" <?php echo(($view['edit_data']=='Tidak') ? 'selected="selected"' : ''); ?>>Tidak</option>
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label>Delete <span class="tx-danger">*</span></label>
                    <select name="deletedata" class="form-control" required="required">
                    	<option value="">-- Pilih Data --</option>
                    	<option value="Ya" <?php echo(($view['delete_data']=='Ya') ? 'selected="selected"' : ''); ?>>Ya</option>
                    	<option value="Tidak" <?php echo(($view['delete_data']=='Tidak') ? 'selected="selected"' : ''); ?>>Tidak</option>
                    </select>
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
        <input type="hidden" name="nmenu" id="nmenu" value="hakakses" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="delete" readonly="readonly" />
        <input type="hidden" name="keycode" id="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-12">
                    <div class="alert alert-danger alert-dismissible mg-b-0 fade show" role="alert">
                        <strong>Informasi!</strong> Hapus data hak akses?
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