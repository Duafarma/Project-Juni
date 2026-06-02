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
		case "update":
		$kode	= $secu->injection($_GET['keycode']);
		$read	= $conn->prepare("SELECT * FROM transaksi_faktur WHERE id_tfk=:kode");
		$read->bindParam(':kode', $kode, PDO::PARAM_STR);
		$read->execute();
		$view	= $read->fetch(PDO::FETCH_ASSOC);
?>
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Update Data - Faktur Penjualan</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formsalespnp" action="#" method="post" autocomplete="off">
        <input type="hidden" name="namamodal" id="namamodal" value="fsales" readonly="readonly" />
        <input type="hidden" name="namamenu" value="update" readonly="readonly" />
        <input type="hidden" name="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-6">
                    <label>Nomor Faktur <span class="tx-danger">*</span></label>
                    <input type="text" name="nomorfaktur" class="form-control" value="<?php echo($view['kode_tfk']); ?>" placeholder="Type here..." required="required" />
                </div>
                <div class="form-group col-md-6">
                    <label>Tgl. Faktur <span class="tx-danger">*</span></label>
                    <input type="text" name="tglfak" class="form-control fortgl" value="<?php echo($view['tgl_tfk']); ?>" placeholder="9999-99-99" required="required" />
                </div>
			</div>
            <div class="row">
                <div class="form-group col-md-6">
                    <label>Nomor SJ <span class="tx-danger">*</span></label>
                    <input type="text" name="nomorsj" class="form-control" value="<?php echo($view['sj_tfk']); ?>" placeholder="Type here..." required="required" />
                </div>
                <div class="form-group col-md-6">
                    <label>Tgl. SJ <span class="tx-danger">*</span></label>
                    <input type="text" name="tglsj" class="form-control fortgl" value="<?php echo($view['tglsj_tfk']); ?>" placeholder="9999-99-99" required="required" />
                </div>
			</div>
            <div class="row">
                <div class="form-group col-md-6">
                    <label>Nomor PO <span class="tx-danger">*</span></label>
                    <input type="text" name="nomorpo" class="form-control" value="<?php echo($view['po_tfk']); ?>" placeholder="Type here..." required="required" />
                </div>
                <div class="form-group col-md-6">
                    <label>Tgl. PO <span class="tx-danger">*</span></label>
                    <input type="text" name="tglpo" class="form-control fortgl" value="<?php echo($view['tglpo_tfk']); ?>" placeholder="9999-99-99" required="required" />
                </div>
			</div>
            <div class="row">
                <div class="form-group col-md-6">
                    <label>Jatuh Tempo<span class="tx-danger">*</span></label>
                    <input type="text" name="jatuhtempo" class="form-control fortgl" value="<?php echo($view['tgl_limit']); ?>" placeholder="9999-99-99" required="required" />
                </div>
                 <div class="form-group col-md-6">
                    <label>Ket <span class="tx-danger"></span></label>
                    <input type="text" name="ket" class="form-control" value="<?php echo($view['ket']); ?>" placeholder="Type here..." />
                </div>
			</div>
			
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Batal</button>
            <button type="submit" id="bsave" class="btn btn-dark btn-xs">Update</button>
        </div>
		</form>

        <!-- Input Tuker Faktut -->
        <!-- <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Input Data Tuker Faktur </h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formsalespnp" action="#" method="post" autocomplete="off">
        <input type="hidden" name="namamodal" id="namamodal" value="fsales" readonly="readonly" />
        <input type="hidden" name="namamenu" value="tf" readonly="readonly" />
        <input type="hidden" name="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
        <div class="modal-body">
            
         <div class="row">
                <div class="form-group col-md-6">
                    <label>Tanggal Tuker Faktur <span class="tx-danger">*</span></label>
                    <input type="text" name="tglpo" class="form-control fortgl" value="<?php echo($view['tanggal_tuker_faktur']); ?>" placeholder="9999-99-99" required="required" />
                </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Batal</button>
            <button type="submit" id="bsave" class="btn btn-dark btn-xs">Input</button>
        </div>
		</form> -->
    <?php
		break;
		case 'manual_revision':
		$kode	= $secu->injection($_GET['keycode']);
		$read	= $conn->prepare("SELECT subtot_tfk, ppn_tfk, total_tfk, kode_tfk FROM transaksi_faktur WHERE id_tfk=:kode");
		$read->bindParam(':kode', $kode, PDO::PARAM_STR);
		$read->execute();
		$view	= $read->fetch(PDO::FETCH_ASSOC);
?>
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Revisi Faktur Manual - <?php echo($view['kode_tfk']); ?></h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formsalespnp" action="#" method="post" autocomplete="off">
        <input type="hidden" name="namamodal" id="namamodal" value="fsales" readonly="readonly" />
        <input type="hidden" name="namamenu" value="manual_revision" readonly="readonly" />
        <input type="hidden" name="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-12">
                    <label>Subtotal (Rp) <span class="tx-danger">*</span></label>
                    <input type="text" name="subtotal" id="subtotal_manual" class="form-control" value="<?php echo($data->angka($view['subtot_tfk'])); ?>" placeholder="0" required="required" onkeyup="hitungTotalManual()" />
                </div>
			</div>
            <div class="row">
                <div class="form-group col-md-12">
                    <label>PPN (Rp) <span class="tx-danger">*</span></label>
                    <input type="text" name="ppn" id="ppn_manual" class="form-control" value="<?php echo($data->angka($view['ppn_tfk'])); ?>" placeholder="0" required="required" onkeyup="hitungTotalManual()" />
                </div>
			</div>
            <div class="row">
                <div class="form-group col-md-12">
                    <label>Total (Rp) <span class="tx-danger">*</span></label>
                    <input type="text" name="total" id="total_manual" class="form-control" value="<?php echo($data->angka($view['total_tfk'])); ?>" placeholder="0" required="required" onkeyup="formatTotalManual()" />
                </div>
			</div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Batal</button>
            <button type="submit" id="bsave" class="btn btn-dark btn-xs">Proses Revisi</button>
        </div>
		</form>
        <script>
        function hitungTotalManual() {
            var sub = $('#subtotal_manual').val().replace(/\./g, '') || 0;
            var ppn = $('#ppn_manual').val().replace(/\./g, '') || 0;
            var total = parseInt(sub) + parseInt(ppn);
            
            // Format ribuan saat mengetik
            $('#subtotal_manual').val(sub.toString().replace(/\B(?=(\d{3})+(?!\d))/g, "."));
            $('#ppn_manual').val(ppn.toString().replace(/\B(?=(\d{3})+(?!\d))/g, "."));
            
            $('#total_manual').val(total.toString().replace(/\B(?=(\d{3})+(?!\d))/g, "."));
        }
        
        function formatTotalManual() {
            var total = $('#total_manual').val().replace(/\./g, '') || 0;
            $('#total_manual').val(total.toString().replace(/\B(?=(\d{3})+(?!\d))/g, "."));
        }
        </script>

<?php
		break;
		case 'delete':
		$kode	= $secu->injection($_GET['keycode']);
		$read	= $conn->prepare("SELECT status_tfk, kode_tfk FROM transaksi_faktur WHERE id_tfk=:kode");
		$read->bindParam(':kode', $kode, PDO::PARAM_STR);
		$read->execute();
		$view	= $read->fetch(PDO::FETCH_ASSOC);
?>
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Konfirmasi</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formsalespnp" action="#" method="post" autocomplete="off">
        <input type="hidden" name="namamodal" id="namamodal" value="fsales" readonly="readonly" />
        <input type="hidden" name="namamenu" value="delete" readonly="readonly" />
        <input type="hidden" name="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
        <input type="hidden" name="nomorfaktur" value="<?php echo($view['kode_tfk']); ?>" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-12">
                    <label><?php echo(($view['status_tfk']==='Faktur' || $view['status_tfk']==='Tagihan' || $view['status_tfk']==='Revisi') ? 'Hapus data faktur sales?' : 'Data sudah diproses, tidak dapat dihapus!' ); ?> <span class="tx-danger">*</span></label>
                </div>
			</div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Batal</button>
            <?php echo(($view['status_tfk']==='Faktur' || $view['status_tfk']=='Tagihan' || $view['status_tfk']=='Revisi') ? '<button type="submit" id="bsave" class="btn btn-dark btn-xs">Hapus</button>' : ''); ?>
        </div>
		</form>
<?php
		break;
	}
	$conn	= $base->close();
?>
	<script type="text/javascript" src="<?php echo("$sistem/config/js/fazlurr.js"); ?>"></script>
