<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$tgl	= date('Y-m-d');
	$conn	= $base->open();
	$id_ttr	= $secu->injection($_POST['id_ttr']);
	$approval= $secu->injection($_POST['approval']);
	$dataTf	= null;
	$msgBugs= array();
	// checking systems
	$qRead = "SELECT * FROM transaksi_transferstock WHERE id_ttr=:id_ttr";
	try {
		$read	= $conn->prepare($qRead);
		$read->bindParam(':id_ttr', $id_ttr, PDO::PARAM_STR);
		$read->execute();
	} catch (PDOException $e) {
		array_push($msgBugs, $e->getMessage());
	}
	if (count($msgBugs)==0) {
		$read   = $read->fetch(PDO::FETCH_ASSOC);
		if ($read['status_ttr'] == 'Waiting') {
			$dataTf = $read;
		}
	}
?>
    <?php
		if ($dataTf) {
	?>
	<form id="formApprovalTransferStokModal" action="#" method="post" autocomplete="off">
		<input type="hidden" name="nmenu" id="nmenu" value="transferstok" readonly="readonly" />
		<input type="hidden" name="nact" id="nact" value="approval" readonly="readonly" />
		<input type="hidden" name="approval" id="approval" value="<?=$approval?>" readonly="readonly" />
		<input type="hidden" name="kode" id="kode" value="<?=$dataTf['kode_ttr']?>" readonly="readonly" />
		<input type="hidden" name="kode_ext" id="kode_ext" value="<?=$dataTf['kode_ext_ttr']?>" readonly="readonly" />
		<input type="hidden" name="id" id="id" value="<?=$dataTf['id_ttr']?>" readonly="readonly" />
		<input type="hidden" name="type" id="type" value="<?=$dataTf['tipe_ttr']?>" readonly="readonly" />
		<input type="hidden" name="id_app_from" id="id_app_from" value="<?=$dataTf['id_app_from']?>" readonly="readonly" />
		<input type="hidden" name="id_app_to" id="id_app_to" value="<?=$dataTf['id_app_to']?>" readonly="readonly" />
	<?php
		}
	?>
	<div class="modal-header">
        <h6 class="modal-title" id="exampleModalLabel"><?=($approval=='1') ? 'Approve' : 'Reject'?> Transfer Stok</h6>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
        </button>
    </div>
    <div class="modal-body">
        <div class="row">
            <div class="col-md-12 text-center">
				<?php
					if ($dataTf) {
				?>
				<b><?=($approval=='1') ? 'Approve' : 'Reject'?></b> Permintaan Transfer Stok
				<?php
					} else {
				?>
				Approval permintaan Transfer Stok tidak dapat diproses
				<?php
					}
				?>
            </div>
        </div>
    </div>
    <div class="modal-footer">
		<?php
			if ($dataTf) {
		?>
		<button type="button" onclick="formApprovalTransferStokModal()" class="btn btn-<?=($approval=='1') ? 'success' : 'danger'?> btn-xs" data-dismiss="modal"><?=($approval=='1') ? 'Approve' : 'Reject'?></button>
		<?php
			}
		?>
        <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Close</button>
    </div>
	<?php
		if ($dataTf) {
	?>
	</form>
	<?php
		}
	?>
<?php $conn	= $base->close(); ?>