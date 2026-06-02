<?php
require_once('../../config/connection/connection.php');
require_once('../../config/connection/security.php');
require_once('../../config/function/data.php');
$secu	= new Security;
$base	= new DB;
$data	= new Data;
$conn	= $base->open();
$modal	= $secu->injection(@$_GET['modal']);
$id		= $secu->injection(@$_POST['id_ttr']);
$approval = $secu->injection(@$_POST['approval']);
$read	= $conn->prepare("SELECT tt.*, a1.nama_apl as from_apl, a2.nama_apl as to_apl FROM transaksi_transferretur tt 
							LEFT JOIN aplikasi a1 ON tt.id_app_from=a1.id_apl 
							LEFT JOIN aplikasi a2 ON tt.id_app_to=a2.id_apl 
							WHERE tt.id_ttr=:id LIMIT 1");
$read->bindParam(':id', $id, PDO::PARAM_STR);
$read->execute();
$view	= $read->fetch(PDO::FETCH_ASSOC);
$conn	= $base->close();
$status	= ($approval == '1') ? 'Approve' : 'Reject';
?>
<div class="modal-header">
	<h6 class="modal-title" id="exampleModalLabel"><?php echo $status; ?> Transfer Retur</h6>
	<button type="button" class="close" data-dismiss="modal" aria-label="Close">
		<span aria-hidden="true">&times;</span>
	</button>
</div>
<div class="modal-body">
	<div class="row">
		<div class="form-group col-md-6">
			<label>Kode <span class="tx-danger">*</span></label>
			<input type="text" name="kode" id="kode" class="form-control" value="<?php echo $view['kode_ttr']; ?>" readonly>
			<input type="hidden" name="kode_ext" id="kode_ext" class="form-control" value="<?php echo $view['kode_ext_ttr']; ?>">
		</div>
		<div class="form-group col-md-6">
			<label>ID Transfer <span class="tx-danger">*</span></label>
			<input type="text" name="id" id="id" class="form-control" value="<?php echo $view['id_ttr']; ?>" readonly>
		</div>
	</div>
	<div class="row">
		<div class="form-group col-md-6">
			<label>From <span class="tx-danger">*</span></label>
			<input type="text" class="form-control" value="<?php echo $view['from_apl']; ?>" readonly>
			<input type="hidden" name="id_app_from" id="id_app_from" value="<?php echo $view['id_app_from']; ?>">
		</div>
		<div class="form-group col-md-6">
			<label>To <span class="tx-danger">*</span></label>
			<input type="text" class="form-control" value="<?php echo $view['to_apl']; ?>" readonly>
			<input type="hidden" name="id_app_to" id="id_app_to" value="<?php echo $view['id_app_to']; ?>">
		</div>
	</div>
	<div class="row">
		<div class="form-group col-md-12">
			<label>Type <span class="tx-danger">*</span></label>
			<input type="text" class="form-control" value="<?php echo $view['tipe_ttr']; ?>" readonly>
			<input type="hidden" name="type" id="type" value="<?php echo $view['tipe_ttr']; ?>">
		</div>
	</div>
	<div class="row">
		<div class="form-group col-md-12">
			<input type="hidden" name="approval" id="approval" value="<?php echo $approval; ?>">
			<input type="hidden" name="nact" id="nact" value="approval">
			<input type="hidden" name="nmenu" id="nmenu" value="transferretur">
			<p>Apakah Anda yakin ingin <?php echo strtolower($status); ?> transfer retur ini?</p>
		</div>
	</div>
</div>
<div class="modal-footer">
	<button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Batal</button>
	<button type="button" id="btnApprove" class="btn btn-<?php echo $approval == '1' ? 'success' : 'danger'; ?> btn-xs" onclick="formApprovalTransferReturModal();"><?php echo $status; ?></button>
</div>