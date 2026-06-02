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
		case "update":
		$kode	= $secu->injection($_GET['keycode']);
?>
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Update Data - Account</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formmenu" action="#" method="post" enctype="multipart/form-data" autocomplete="off">
        <input type="hidden" name="namamodal" id="namamodal" value="account" readonly="readonly" />
        <input type="hidden" name="namamenu" value="update" readonly="readonly" />
        <input type="hidden" name="keycode" value="<?php echo(base64_encode($kode)); ?>" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-6">
                    <label>Nama <span class="tx-danger">*</span></label>
                    <input type="text" name="nama" class="form-control" value="<?php echo($data->myadmin($kode, 'nama_adm')); ?>" placeholder="Type here..." required="required" />
                    <div id="imgloading"></div>
                </div>
                <div class="form-group col-md-6">
                    <label>Foto <span class="tx-danger">*</span></label>
                    <div class="clearfix"></div>
                    <button type="button" class="btn btn-primary" id="tombol1" onclick="namafile('tombol1', 'foto1')"><i class="fa fa-cloud-upload"></i> Upload di sini...</button>
                    <input type="file" id="foto1" name="foto1" hidden="hidden" />
                </div>
			</div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Batal</button>
            <button type="submit" id="btnsave" class="btn btn-dark btn-xs">Update</button>
        </div>
		</form>
<?php
		break;
		case "email":
		$kode	= $secu->injection($_GET['keycode']);
?>
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Update Email - Account</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formmenu" action="#" method="post" autocomplete="off">
        <input type="hidden" name="namamodal" id="namamodal" value="account" readonly="readonly" />
        <input type="hidden" name="namamenu" value="email" readonly="readonly" />
        <input type="hidden" name="keycode" value="<?php echo(base64_encode($kode)); ?>" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-12">
                    <label>Email <span class="tx-danger">*</span></label>
                    <input type="email" name="emailz" class="form-control" value="<?php echo($data->myadmin($kode, 'email_adm')); ?>" placeholder="Type here..." required="required" />
                    <div id="imgloading"></div>
                </div>
			</div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Batal</button>
            <button type="submit" id="btnsave" class="btn btn-dark btn-xs">Update</button>
        </div>
		</form>
<?php
		break;
		case "password":
		$kode	= $secu->injection($_GET['keycode']);
?>
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Update Password - Account</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formmenu" action="#" method="post" autocomplete="off">
        <input type="hidden" name="namamodal" id="namamodal" value="account" readonly="readonly" />
        <input type="hidden" name="namamenu" value="password" readonly="readonly" />
        <input type="hidden" name="keycode" value="<?php echo(base64_encode($kode)); ?>" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-12">
                    <label>Password <span class="tx-danger">*</span></label>
                    <input type="password" name="passz" class="form-control" placeholder="Type here..." />
                    <div id="imgloading"></div>
                </div>
			</div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Batal</button>
            <button type="submit" id="btnsave" class="btn btn-dark btn-xs">Update</button>
        </div>
		</form>
<?php
		break;
	}
	$conn	= $base->close();
?>
	<script type="text/javascript" src="<?php echo($data->sistem('url_sis').'/config/js/fazlurr.js'); ?>"></script>