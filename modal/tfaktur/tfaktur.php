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
	switch($modal){
		case "input":
?>
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Input Data - Tuker Faktur</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formmenu" action="#" method="post" autocomplete="off">
        <input type="hidden" name="namamodal" id="namamodal" value="tfaktur" readonly="readonly" />
        <input type="hidden" name="namamenu" value="input" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
            <div class="form-group col-md-6">
                    <label>Nomor Faktur <span class="tx-danger">*</span></label>
                    <select name="id_tfk" class="form-control sumoselect" required="required">
                    	<option value="">-- Pilih Nomor Faktur --</option>
                    <?php
						$master		= $conn->prepare("SELECT id_tfk, kode_tfk FROM transaksi_faktur ORDER BY kode_tfk ASC");
						$master->execute();
						while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
					?>
                    	<option value="<?php echo($hasil['id_tfk']); ?>"><?php echo($hasil['kode_tfk']); ?></option>
                    <?php } ?>
                    </select>
                </div>
                <div class="form-group col-md-6">
                        <tr>
                        	<td width="35%"><label>Nama Outlet <span class="tx-danger">*</span></label></td>
                        	<td width="5%"></td>
                        	<td width="60%">
                            <select name="id_kot" class="form-control sumoselect" required="required">
                                <option value="">-- Pilih Outlet --</option>
                            <?php
                                $master		= $conn->prepare("SELECT id_out, nama_out FROM outlet ORDER BY nama_out ASC");
                                $master->execute();
                                while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
                            ?>
                                <option value="<?php echo($hasil['id_out']); ?>"><?php echo($hasil['nama_out']); ?></option>
                            <?php } ?>
                            </select>
                            </td>
                        </tr>
                </div>
			</div>
            <div class="row">
                 <div class="form-group col-sm-6">
                    <label>Status Tuker Faktur<span class="tx-danger"></span></label>
                    <input type="text" name="status" id="status" class="form-control" value="<?php echo('Sudah Tuker Faktur'); ?>" readonly="readonly" />
                </div>
                <div class="form-group col-md-6">
                    <label>Tanggal Tuker Faktur<span class="tx-danger">*</span></label>
                    <input type="text" name="tanggal_tkf" id="tanggal_tkf" class="form-control fortgl" value="<?php echo(date('Y-m-d')); ?>" placeholder="9999-99-99" required="required" />
                    <div id="imgloading"></div>
                </div>
			</div>
            <div class="row">
                 <div class="form-group col-sm-6">
                    <label>Keterangan<span class="tx-danger"></span></label>
                    <input type="text" name="note" id="note" class="form-control" value="" />
                </div>
			</div>
            <!-- <div class="row">
                <div class="form-group col-md-6">
                    <label>Tanggal Faktur<span class="tx-danger">*</span></label>
                    <input type="text" name="tanggal" class="form-control fortgl" value="<?php echo(date('Y-m-d')); ?>" placeholder="9999-99-99" required="required" />
                    <div id="imgloading"></div> -->
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
		$read	= $conn->prepare("SELECT id_kot, id_tfk, tanggal_tkf, status, note FROM tuker_faktur WHERE id_tkf_t=:kode");
		$read->bindParam(':kode', $kode, PDO::PARAM_STR);
		$read->execute();
		$view	= $read->fetch(PDO::FETCH_ASSOC);
?>
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Update Data - Tuker Faktur</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formmenu" action="#" method="post" autocomplete="off">
        <input type="hidden" name="namamodal" id="namamodal" value="tfaktur" readonly="readonly" />
        <input type="hidden" name="namamenu" value="update" readonly="readonly" />
        <input type="hidden" name="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
            <div class="form-group col-md-6">
                    <label>Nama Outlet <span class="tx-danger">*</span></label>
                    <select name="id_kot" class="form-control sumoselect" required="required">
                    	<option value="">-- Pilih Nama Outlet--</option>
                    <?php
						$master		= $conn->prepare("SELECT id_out, nama_out FROM outlet ORDER BY nama_out ASC");
						$master->execute();
						while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
                            $pilih	= ($view['id_kot']==$hasil['id_out']) ? 'selected="selected"' : '';
					?>
                    	<option value="<?php echo($hasil['id_out']); ?>" <?php echo($pilih); ?>><?php echo($hasil['nama_out']); ?></option>
                    <?php } ?>
                    </select>
                </div>
            <div class="form-group col-md-6">
                    <label>Nomor Faktur <span class="tx-danger">*</span></label>
                    <select name="id_tfk" class="form-control sumoselect" required="required">
                    	<option value="">-- Pilih Nomor Faktur --</option>
                    <?php
						$master		= $conn->prepare("SELECT id_tfk, kode_tfk FROM transaksi_faktur ORDER BY kode_tfk ASC");
						$master->execute();
						while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
                            $pilih	= ($view['id_tfk']==$hasil['id_tfk']) ? 'selected="selected"' : '';
					?>
                    	<option value="<?php echo($hasil['id_tfk']); ?>" <?php echo($pilih); ?>><?php echo($hasil['kode_tfk']); ?></option>
                    <?php } ?>
                    </select>
                </div>
              
			</div>
            <div class="row">
            <div class="form-group col-md-6">
                    <label>Tanggal Tuker Faktur<span class="tx-danger">*</span></label>
                    <input type="text" name="tanggal_tkf" class="form-control fortgl" value="<?php echo($view['tanggal_tkf']); ?>" placeholder="9999-99-99" required="required" />
                    <div id="imgloading"></div>
                </div>
                <div class="form-group col-sm-6">
                    <label>Status Tuker Faktur<span class="tx-danger"></span></label>
                    <input type="text" name="status" class="form-control" value="<?php echo($view['status']); ?>" readonly="readonly" />
                </div>
			</div>
            <div class="row">
                 <div class="form-group col-sm-6">
                    <label>Keterangan<span class="tx-danger"></span></label>
                    <input type="text" name="note" id="note" class="form-control" value="<?php echo($view['note']); ?>" />
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
        <form id="formmenu" action="#" method="post" autocomplete="off">
        <input type="hidden" name="namamodal" id="namamodal" value="tfaktur" readonly="readonly" />
        <input type="hidden" name="namamenu" value="delete" readonly="readonly" />
        <input type="hidden" name="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-12">
                    <div class="alert alert-danger alert-dismissible mg-b-0 fade show" role="alert">
                        <strong>Informasi!</strong> Hapus data Tuker Faktur?
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
	<script type="text/javascript" src="<?php echo("$sistem/config/js/fazlurr.js"); ?>"></script>
	<!-- <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" rel="stylesheet" />-->
 <!--       <script src="https://code.jquery.com/jquery-3.4.1.js" integrity="sha256-WpOohJOqMqqyKL9FccASB9O0KwACQJpFTUBLTYOVvVU=" crossorigin="anonymous"></script>-->
 <!--       <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/js/select2.min.js"></script>-->
	<!--<script type="text/javascript">-->
 <!--    $(document).ready(function() {-->
 <!--        $('#tuker').select2();-->
 <!--    });-->
 <!--   </script>-->