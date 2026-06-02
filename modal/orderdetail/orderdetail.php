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
		$uniq	= $secu->injection($_GET['keycode']);
?>
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Update Data - Icon</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formmenu" action="#" method="post" autocomplete="off">
        <input type="hidden" name="namamodal" id="namamodal" value="orderdetail" readonly="readonly" />
        <input type="hidden" name="namamenu" value="input" readonly="readonly" />
        <input type="hidden" name="keycode" value="<?php echo($uniq); ?>" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-12">
                    <label>Produk <span class="tx-danger">*</span></label>
                    <select name="produk"  class="form-control sumoselect" required="required">
					<?php
						$status	= 'Active';
						$master	= $conn->prepare("SELECT A.id_pro, A.kode_pro, A.nama_pro, A.berat_pro, B.harga_phg, B.hargap_phg, C.nama_kpr, D.nama_spr FROM produk AS A LEFT JOIN produk_harga AS B ON A.id_pro=B.id_pro LEFT JOIN kategori_produk AS C ON A.id_kpr=C.id_kpr LEFT JOIN satuan_produk AS D ON A.id_spr=D.id_spr WHERE B.status_phg=:status ORDER BY A.nama_pro ASC");
						$master->bindParam(':status', $status, PDO::PARAM_STR);
						$master->execute();
						while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
					?>
						<option value="<?php echo($hasil['id_pro']); ?>"><?php echo("$hasil[nama_pro] ($hasil[kode_pro])"); ?></option>
					<?php } ?>
					</select>
                </div>
			</div>
            <div class="row">
                <div class="form-group col-md-12">
                    <label>Jumlah <span class="tx-danger">*</span></label>
                    <input type="text" name="jumlah" class="form-control" onkeyup="angka(this)" placeholder="0" required="required" />
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
		$uniq	= $secu->injection($_GET['keycode']);
		$kode	= base64_decode($uniq);
		$read	= $conn->prepare("SELECT jumlah_tod, id_pro FROM transaksi_orderdetail WHERE id_tod=:kode");
		$read->bindParam(':kode', $kode, PDO::PARAM_STR);
		$read->execute();
		$view	= $read->fetch(PDO::FETCH_ASSOC);
?>
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Update Data - Icon</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formmenu" action="#" method="post" autocomplete="off">
        <input type="hidden" name="namamodal" id="namamodal" value="orderdetail" readonly="readonly" />
        <input type="hidden" name="namamenu" value="update" readonly="readonly" />
        <input type="hidden" name="keycode" value="<?php echo($uniq); ?>" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-12">
                    <label>Produk <span class="tx-danger">*</span></label>
                    <select name="produk"  class="form-control sumoselect" required="required">
					<?php
						$status	= 'Active';
						$master	= $conn->prepare("SELECT A.id_pro, A.kode_pro, A.nama_pro, A.berat_pro, B.harga_phg, B.hargap_phg, C.nama_kpr, D.nama_spr FROM produk AS A LEFT JOIN produk_harga AS B ON A.id_pro=B.id_pro LEFT JOIN kategori_produk AS C ON A.id_kpr=C.id_kpr LEFT JOIN satuan_produk AS D ON A.id_spr=D.id_spr WHERE B.status_phg=:status ORDER BY A.nama_pro ASC");
						$master->bindParam(':status', $status, PDO::PARAM_STR);
						$master->execute();
						while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
							$pilih	= ($view['id_pro']===$hasil['id_pro']) ? 'selected="selected"' : '';
					?>
						<option value="<?php echo($hasil['id_pro']); ?>" <?php echo($pilih); ?>><?php echo("$hasil[nama_pro] ($hasil[kode_pro])"); ?></option>
					<?php } ?>
					</select>
                </div>
			</div>
            <div class="row">
                <div class="form-group col-md-12">
                    <label>Jumlah <span class="tx-danger">*</span></label>
                    <input type="text" name="jumlah" class="form-control" value="<?php echo($view['jumlah_tod']); ?>" onkeyup="angka(this)" placeholder="0" required="required" />
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
		$uniq	= $secu->injection($_GET['keycode']);
?>
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Konfirmasi</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formmenu" action="#" method="post" autocomplete="off">
        <input type="hidden" name="namamodal" id="namamodal" value="orderdetail" readonly="readonly" />
        <input type="hidden" name="namamenu" value="delete" readonly="readonly" />
        <input type="hidden" name="keycode" value="<?php echo($uniq); ?>" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-12">
                    <div class="alert alert-danger alert-dismissible mg-b-0 fade show" role="alert">
                        <strong>Informasi!</strong> Hapus data item pembelian produk?
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
		selectAll	: false
	});
    </script>