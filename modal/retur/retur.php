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
            $read	= $conn->prepare("SELECT A.id_out, B.nama_out, C.sj_tfk, A.id_tfk, A.tanggal, A.no_retur, A.keterangan FROM retur AS A LEFT JOIN outlet AS B ON A.id_out=B.id_out LEFT JOIN transaksi_faktur AS C ON A.id_tfk=C.id_tfk WHERE id_r=:kode");
            $read->bindParam(':kode', $kode, PDO::PARAM_STR);
            $read->execute();
            $view	= $read->fetch(PDO::FETCH_ASSOC);
    ?>
            <div class="modal-header">
                <h6 class="modal-title" id="exampleModalLabel">Update Data - Barang Retur</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
                </button>
            </div>
            <form id="formtransaksi" action="#" method="post" autocomplete="off">
            <input type="hidden" name="nmenu" id="nmenu" value="retur" readonly="readonly" />
            <input type="hidden" name="nact" id="nact" value="update" readonly="readonly" />
            <input type="hidden" name="keycode" id="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
            <div class="modal-body">
                <div class="row">
                    <div class="form-group col-md-12">
                        <label>Tanggal & Waktu <span class="tx-danger"></span></label>
                        <input type="text" name="tanggal" class="form-control datepicker" value="<?php echo($view['tanggal']); ?>" placeholder="9999-99-99" />
                    </div>
                        <div id="imgloading"></div>
                </div>
                <div class="row">
                    <div class="form-group col-md-12">
                    <label>Nama Outlet <span class="tx-danger">*</span></label>
                        <select name="id_out" id="outlet" class="form-control select2" onchange="ceksalespenggantianbarang()" required="required">
                            <option value="<?php echo($view['id_out']); ?>"><?php echo($view['nama_out']); ?></option>
                        <?php
                            $status	= 'Active';
                            $master	= $conn->prepare("SELECT id_out, kode_out, nama_out FROM outlet WHERE status_out=:status ORDER BY nama_out ASC");
                            $master->bindParam(':status', $status, PDO::PARAM_STR);
                            $master->execute();
                            while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
                        ?>
                            <option value="<?php echo($hasil['id_out']); ?>"><?php echo($hasil['nama_out']); ?></option>
                        <?php } ?>
                        </select>
                    </div>
                        <div id="imgloading"></div>
                </div>
                <div class="row">
                    <div class="form-group col-md-12">
                    <label>Nomor Faktur<span class="tx-danger"></span></label>
                        <select name="id_tfk" id="no_tfk_penjualan" class="form-control select2">
                            <option value="<?php echo($view['id_tfk']); ?>"><?php echo($view['sj_tfk']); ?>  </option>
                

                        </select>
                    </div>
                        <div id="imgloading"></div>
                </div>
                <div class="row">
                    <div class="form-group col-md-12">
                        <label>Nomor Retur <span class="tx-danger">*</span></label>
                        <input type="text" name="no_retur" class="form-control" value="<?php echo($view['no_retur']); ?>" placeholder="-" required="required" />
                    </div>
                        <div id="imgloading"></div>
                </div>
                <div class="row">
                    <div class="form-group col-md-12">
                    <label>Keterangan  <span class="tx-danger"></span></label>
                    <input type="text" name="ket" value="<?php echo($view['keterangan']); ?>" class="form-control" placeholder=""/>
                    </div>
                        <div id="imgloading"></div>
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
        <input type="hidden" name="nmenu" id="nmenu" value="retur" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="delete" readonly="readonly" />
        <input type="hidden" name="keycode" id="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-12">
                    <div class="alert alert-danger alert-dismissible mg-b-0 fade show" role="alert">
                        <strong>Informasi!</strong> Hapus data retur barang?
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