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
	$read	= $conn->prepare("SELECT id_tfk FROM transaksi_p_luar_kota WHERE id_p_l_k=:kode");
	$read->bindParam(':kode', $kode, PDO::PARAM_STR);
	$read->execute();
	$view	= $read->fetch(PDO::FETCH_ASSOC);
	switch($modal){
		case "delete":
    ?>
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Konfirmasi</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formtransaksi" action="#" method="post" autocomplete="off">
        <input type="hidden" name="nmenu" id="nmenu" value="pengirimanlk" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="delete" readonly="readonly" />
        <input type="hidden" name="keycode" id="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-12">
                    <div class="alert alert-danger alert-dismissible mg-b-0 fade show" role="alert">
                        <strong>Informasi!</strong> Hapus data pengiriman luar kota "<strong><?php echo($view['id_tfk']); ?></strong>" ?
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
		case "update":
		$kode	= $secu->injection($_GET['keycode']);
		$read	= $conn->prepare("SELECT id_tfk, id_out, id_pengiriman, id_vendor, nomor_resi, cabang FROM transaksi_p_luar_kota WHERE id_p_l_k=:kode");
		$read->bindParam(':kode', $kode, PDO::PARAM_STR);
		$read->execute();
		$view	= $read->fetch(PDO::FETCH_ASSOC);
?>
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Update Data - Pengiriman luar kota</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formtransaksi" action="#" method="post" autocomplete="off">
        <input type="hidden" name="nmenu" id="nmenu" value="pengirimanlk" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="update" readonly="readonly" />
        <input type="hidden" name="keycode" id="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
        <div class="modal-body">
                <div class="row">
                    <div class="form-group col-md-6">
                        <label>No Faktur <span class="tx-danger">*</span></label>
                        <input type="text" name="id_tfk" class="form-control" value="<?php echo($view['id_tfk']); ?>" placeholder="ex. ALBUCETINE ED" required="required" />
                    </div>
                    <div class="form-group col-md-6">
                        <label>Nama Outlet <span class="tx-danger">*</span></label>
                        <select name="id_out" class="form-control" required="required">
                            <option value="">-- Pilih Data --</option>
                        <?php
                            $master		= $conn->prepare("SELECT id_out, nama_out FROM outlet ORDER BY nama_out ASC");
                            $master->execute();
                            while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
                                $pilih	= ($view['id_out']==$hasil['id_out']) ? 'selected="selected"' : '';
                        ?>
                            <option value="<?php echo($hasil['id_out']); ?>" <?php echo($pilih); ?>><?php echo($hasil['nama_out']); ?></option>
                        <?php } ?>
                        </select>
                                </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-6">
                        <label>Cabang <span class="tx-danger">*</span></label>
                        <select name="cabang" id="cabang" class="form-control select2" onchange="" required="required">
                            <option value="<?php echo($view['cabang']); ?>"><?php echo($view['cabang']); ?></option>
                            <option value="Jakarta">Jakarta</option>
                            <option value="Bekasi">Bekasi</option>
                            <option value="Cibinong">Cibinong</option>
                            <option value="Surabaya">Surabaya</option>
                            <option value="Medan">Medan</option>
                            <option value="Bali">Bali</option>

                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Ekpedisi <span class="tx-danger">*</span></label>
                        <select name="id_vendor" class="form-control" required="required">
                            <option value="">-- Pilih Data --</option>
                        <?php
                            $master		= $conn->prepare("SELECT id_vendor, nama_vendor FROM vendor_pengiriman ORDER BY nama_vendor ASC");
                            $master->execute();
                            while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
                                $pilih	= ($view['id_vendor']==$hasil['id_vendor']) ? 'selected="selected"' : '';
                        ?>
                            <option value="<?php echo($hasil['id_vendor']); ?>" <?php echo($pilih); ?>><?php echo($hasil['nama_vendor']); ?></option>
                        <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-6">
                        <label>Status Pengiriman <span class="tx-danger">*</span></label>
                        <select name="id_pengiriman" class="form-control" required="required">
                            <option value="">-- Pilih Data --</option>
                        <?php
                            $master		= $conn->prepare("SELECT id_pengiriman, tahap_pengiriman FROM master_pengiriman ORDER BY tahap_pengiriman ASC");
                            $master->execute();
                            while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
                                $pilih	= ($view['id_pengiriman']==$hasil['id_pengiriman']) ? 'selected="selected"' : '';
                        ?>
                            <option value="<?php echo($hasil['id_pengiriman']); ?>" <?php echo($pilih); ?>><?php echo($hasil['tahap_pengiriman']); ?></option>
                        <?php } ?>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label>No Resi <span class="tx-danger">*</span></label>
                        <input type="text" name="nomor_resi" class="form-control" value="<?php echo($view['nomor_resi']); ?>" placeholder="ex. ALBUCETINE ED" required="required" />
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