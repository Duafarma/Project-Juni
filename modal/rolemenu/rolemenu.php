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
?>
    <link rel="stylesheet" href="<?php echo("$sistem/sumoselect/sumoselect.css"); ?>">
<?php
	switch($modal){
		case "input":
?>
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Input Data - Role Menu</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formtransaksi" action="#" method="post" autocomplete="off">
        <input type="hidden" name="nmenu" id="nmenu" value="rolemenu" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="input" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-12">
                    <label>Administartor <span class="tx-danger">*</span></label>
                    <select name="member" class="form-control" required="required">
                    	<option value="">-- Select Administartor--</option>
                    <?php
						$master		= $conn->prepare("SELECT id_adm, nama_adm FROM adminz ORDER BY nama_adm ASC");
						$master->execute();
						while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
					?>
                    	<option value="<?php echo($hasil['id_adm']); ?>"><?php echo($hasil['nama_adm']); ?></option>
                    <?php } ?>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="form-group col-md-12">
                    <label>Sub Menu <span class="tx-danger">*</span></label>
                    <select name="submenu[]"  multiple="multiple" class="form-control sumoselect" required="required">
                    <?php
						$master		= $conn->prepare("SELECT id_smu, nama_smu FROM sub_menu ORDER BY nama_smu ASC");
						$master->execute();
						while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
					?>
                    	<option value="<?php echo($hasil['id_smu']); ?>"><?php echo($hasil['nama_smu']); ?></option>
                    <?php } ?>
					</select>
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
		$read	= $conn->prepare("SELECT id_adm, akses_rmu FROM role_menu WHERE id_rmu=:kode");
		$read->bindParam(':kode', $kode, PDO::PARAM_STR);
		$read->execute();
		$view	= $read->fetch(PDO::FETCH_ASSOC);
?>
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Update Data - Role Menu</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formtransaksi" action="#" method="post" autocomplete="off">
        <input type="hidden" name="nmenu" id="nmenu" value="rolemenu" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="update" readonly="readonly" />
        <input type="hidden" name="keycode" id="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-12">
                    <label>Administartor <span class="tx-danger">*</span></label>
                    <select name="member" class="form-control" required="required">
                    	<option value="">-- Select Administartor--</option>
                    <?php
						$master		= $conn->prepare("SELECT id_adm, nama_adm FROM adminz ORDER BY nama_adm ASC");
						$master->execute();
						while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
							$pilih	= ($hasil['id_adm']===$view['id_adm']) ? 'selected="selected"' : '';
					?>
                    	<option value="<?php echo($hasil['id_adm']); ?>" <?php echo($pilih); ?>><?php echo($hasil['nama_adm']); ?></option>
                    <?php } ?>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="form-group col-md-12">
                    <label>Sub Menu <span class="tx-danger">*</span></label>
                    <select name="submenu[]"  multiple="multiple" class="form-control sumoselect" required="required">
                    <?php
						$master		= $conn->prepare("SELECT id_smu, nama_smu FROM sub_menu ORDER BY nama_smu ASC");
						$master->execute();
						while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
							$pilih	= ($data->cariarray($view['akses_rmu'], ',', $hasil['id_smu'])==true) ? 'selected="selected"' : '';
					?>
                    	<option value="<?php echo($hasil['id_smu']); ?>" <?php echo($pilih); ?>><?php echo($hasil['nama_smu']); ?></option>
                    <?php } ?>
					</select>
                    <div id="imgloading"></div>
                </div>
			</div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Batal</button>
            <button type="submit" id="bsave" class="btn btn-dark btn-xs">Update</button>
            <div id="imgloading"></div>
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
        <input type="hidden" name="nmenu" id="nmenu" value="rolemenu" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="delete" readonly="readonly" />
        <input type="hidden" name="keycode" id="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-12">
                    <div class="alert alert-danger alert-dismissible mg-b-0 fade show" role="alert">
                        <strong>Informasi!</strong> Hapus data role menu?
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
	<script type="text/javascript" src="<?php echo("$sistem/sumoselect/jquery.sumoselect.js"); ?>"></script>
	<script type="text/javascript" src="<?php echo("$sistem/config/js/fazlurr.js"); ?>"></script>
    <script type="text/javascript">
	$(".sumoselect").SumoSelect({
		placeholder	: 'Select Items',
		csvDispCount: 3,
		search		: true,
		selectAll	: true
	});
    </script>