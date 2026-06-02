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
            <h6 class="modal-title" id="exampleModalLabel">Input Data - Menu</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formtransaksi" action="#" method="post" autocomplete="off">
        <input type="hidden" name="nmenu" id="nmenu" value="menu" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="input" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-6">
                    <label>Nama <span class="tx-danger">*</span></label>
                    <input type="text" name="nama" class="form-control" placeholder="Type here..." required="required" />
                </div>
                <div class="form-group col-md-6">
                    <label>Kategori <span class="tx-danger">*</span></label>
                    <select name="kategori" class="form-control" required="required">
                    	<option value="">-- Select Kategori --</option>
                    <?php
						$master		= $conn->prepare("SELECT id_kmu, nama_kmu FROM kategori_menu ORDER BY urutan_kmu ASC");
						$master->execute();
						while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
					?>
                    	<option value="<?php echo($hasil['id_kmu']); ?>"><?php echo($hasil['nama_kmu']); ?></option>
                    <?php } ?>
                    </select>
                </div>
			</div>
            <div class="row">
                <div class="form-group col-md-6">
                    <label>Icon <span class="tx-danger">*</span></label>
                    <select name="ikon" class="form-control" required="required">
                    	<option value="">-- Select Icon --</option>
                    <?php
						$master		= $conn->prepare("SELECT id_icon, nama_icon FROM icon ORDER BY nama_icon ASC");
						$master->execute();
						while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
					?>
                    	<option value="<?php echo($hasil['id_icon']); ?>"><?php echo($hasil['nama_icon']); ?></option>
                    <?php } ?>
                    </select>
                    <div id="imgloading"></div>
                </div>
                <div class="form-group col-md-6">
                    <label>Urutan <span class="tx-danger">*</span></label>
                    <input type="text" name="urutan" class="form-control" placeholder="0" onkeyup="angka(this)" required="required" />
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
		$read	= $conn->prepare("SELECT id_kmu, id_icon, nama_menu, urutan_menu FROM menu WHERE id_menu=:kode");
		$read->bindParam(':kode', $kode, PDO::PARAM_STR);
		$read->execute();
		$view	= $read->fetch(PDO::FETCH_ASSOC);
?>
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Update Data - Menu</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formtransaksi" action="#" method="post" autocomplete="off">
        <input type="hidden" name="nmenu" id="nmenu" value="menu" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="update" readonly="readonly" />
        <input type="hidden" name="keycode" id="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-6">
                    <label>Nama <span class="tx-danger">*</span></label>
                    <input type="text" name="nama" class="form-control" value="<?php echo($view['nama_menu']); ?>" placeholder="Type here..." required="required" />
                </div>
                <div class="form-group col-md-6">
                    <label>Kategori <span class="tx-danger">*</span></label>
                    <select name="kategori" class="form-control" required="required">
                    	<option value="">-- Select Kategori --</option>
                    <?php
						$master		= $conn->prepare("SELECT id_kmu, nama_kmu FROM kategori_menu ORDER BY urutan_kmu ASC");
						$master->execute();
						while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
							$pilih	= ($hasil['id_kmu']===$view['id_kmu']) ? 'selected="selected"' : '';
					?>
                    	<option value="<?php echo($hasil['id_kmu']); ?>" <?php echo($pilih); ?>><?php echo($hasil['nama_kmu']); ?></option>
                    <?php } ?>
                    </select>
                </div>
			</div>
            <div class="row">
                <div class="form-group col-md-6">
                    <label>Icon <span class="tx-danger">*</span></label>
                    <select name="ikon" class="form-control" required="required">
                    	<option value="">-- Select Icon --</option>
                    <?php
						$master		= $conn->prepare("SELECT id_icon, nama_icon FROM icon ORDER BY nama_icon ASC");
						$master->execute();
						while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
							$pilih	= ($hasil['id_icon']===$view['id_icon']) ? 'selected="selected"' : '';
					?>
                    	<option value="<?php echo($hasil['id_icon']); ?>" <?php echo($pilih); ?>><?php echo($hasil['nama_icon']); ?></option>
                    <?php } ?>
                    </select>
                    <div id="imgloading"></div>
                </div>
                <div class="form-group col-md-6">
                    <label>Urutan <span class="tx-danger">*</span></label>
                    <input type="text" name="urutan" class="form-control" value="<?php echo($data->angka($view['urutan_menu'])); ?>" placeholder="0" onkeyup="angka(this)" required="required" />
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
        <input type="hidden" name="nmenu" id="nmenu" value="menu" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="delete" readonly="readonly" />
        <input type="hidden" name="keycode" id="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-12">
                    <div class="alert alert-danger alert-dismissible mg-b-0 fade show" role="alert">
                        <strong>Informasi!</strong> Hapus data menu?
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