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
		$uniq	= $secu->injection($_GET['keycode']);
		$kode	= base64_decode($uniq);
		$read	= $conn->prepare("SELECT A.persen_pds, B.nama_pro FROM produk_diskon AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro WHERE A.id_pds=:kode");
		$read->bindParam(':kode', $kode, PDO::PARAM_STR);
		$read->execute();
		$view	= $read->fetch(PDO::FETCH_ASSOC);
?>
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Input Data - Diskon Produk</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formmenu" action="#" method="post" autocomplete="off">
        <input type="hidden" name="namamodal" id="namamodal" value="dispro" readonly="readonly" />
        <input type="hidden" name="namamenu" value="update" readonly="readonly" />
        <input type="hidden" name="keycode" value="<?php echo($uniq); ?>" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-12">
                    <label>Nama <span class="tx-danger">*</span></label>
                    <input type="text" name="nama" class="form-control" value="<?php echo($view['nama_pro']); ?>" placeholder="Type here..." readonly="readonly" required="required" />
                </div>
			</div>
            <div class="row">
                <div class="form-group col-md-12">
                    <label>Diskon % <span class="tx-danger">*</span></label>
                    <input type="text" name="jumlah" class="form-control" value="<?php echo($view['persen_pds']); ?>" placeholder="Type here..." required="required" />
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
	}
	$conn	= $base->close();
?>
	<script type="text/javascript" src="<?php echo($data->sistem('url_sis').'/config/js/fazlurr.js'); ?>"></script>