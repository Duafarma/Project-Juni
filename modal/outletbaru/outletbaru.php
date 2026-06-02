<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$conn	= $base->open();
	$modal	= $secu->injection(@$_GET['modal']);
    $kode	= $secu->injection($_GET['keycode']);
	$read	= $conn->prepare("SELECT nama_out FROM outlet WHERE id_out=:kode");
	$read->bindParam(':kode', $kode, PDO::PARAM_STR);
	$read->execute();
	$view	= $read->fetch(PDO::FETCH_ASSOC);
	switch($modal){
        case "updateStatusRevised":
?>
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Konfirmasi Selesai Revisi Outlet Baru</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formtransaksi" action="#" method="post" autocomplete="off">
        <input type="hidden" name="nmenu" id="nmenu" value="outletbaru" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="updateStatusRevised" readonly="readonly" />
        <input type="hidden" name="keycode" id="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-12">
                    <br>Yakin ingin menyelesaikan revisi data outlet baru?
                    <br><strong><?php echo($view['nama_out']); ?></strong>
                    <br><br>
                    <div class="alert alert-info alert-dismissible mg-b-0 fade show" role="alert">
                        Proses ini akan menandakan data oulet sudah selesai direvisi
                    </div>
                    <div id="imgloading"></div>
                </div>
			</div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Batal</button>
            <button type="submit" id="bsave" class="btn btn-warning btn-xs">Selesai</button>
        </div>
		</form> 
<?php
	}
	$conn	= $base->close();
?>
	<script type="text/javascript" src="<?php echo($data->sistem('url_sis').'/config/js/fazlurr.js'); ?>"></script>