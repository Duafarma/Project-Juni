<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$tgl	= date('Y-m-d');
	$conn	= $base->open();
	$sistem	= $data->sistem('url_sis');
	$menu	= $secu->injection(@$_GET['menu']);
	$qRead	= "SELECT a.*
				FROM notifications a
				INNER JOIN transaksi_transferstock b
					ON a.id_datanotif = b.id_ttr
				WHERE a.status_notif = 'U' AND
					(b.status_ttr = 'Waiting' OR
					b.status_ttr = 'Approved')";
	try {
		$read	= $conn->prepare($qRead);
		$read->execute();
		$datas  = $read->fetchAll(PDO::FETCH_ASSOC);
	} catch (PDOException $e) {
		array_push($msgBugs, $e->getMessage());
	}
	if (empty($msgBugs) && count($datas) != 0) {
		$qUpdate = "UPDATE
						notifications a
					INNER JOIN transaksi_transferstock b
						ON a.id_datanotif = b.id_ttr
					SET a.status_notif = 'R'
					WHERE a.status_notif = 'U' AND
						a.status_datanotif = 'Approved'";
		$update = $conn->prepare($qUpdate);
		$update->execute();
?>
    <div class="modal-header">
        <h6 class="modal-title" id="exampleModalLabel">Notification Transfer Stok</h6>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
        </button>
    </div>
    <div class="modal-body">
        <div class="row">
            <div class="col-md-12">
				<?php if (empty($msgBugs) && count($datas) != 0) { ?>
				<div class="list-group">
					<?php foreach ($datas as $data) { ?>
						<a href="<?php echo $sistem.'/transferstok/v/'.$data['id_datanotif'];?>" class="list-group-item list-group-item-action"><?=$data['title_notif']?></a>
					<?php } ?>
				</div>
				<?php } else { ?>
				<p class="text-danger"><?=implode(", ",$msgBugs)?></p>
				<?php } ?>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Close</button>
    </div>
<?php $conn	= $base->close(); ?>
<?php } ?>