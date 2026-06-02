<?php
	$info	= $secu->injection(@$_COOKIE['info']);
	$pesan	= $secu->injection(@$_COOKIE['pesan']);
	if(!empty($info)){
?>
<div class="<?php echo("alert alert-$info alert-dismissible mg-b-0 fade show"); ?>" role="alert">
	<strong>Informasi!</strong> <?php echo($pesan); ?>
	<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true"><i class="fa fa-times-circle"></i></span></button>
</div>
<?php } ?>