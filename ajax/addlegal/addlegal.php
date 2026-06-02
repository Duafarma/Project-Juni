<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$sistem	= $data->sistem('url_sis');
	$catat	= date('Y-m-d H:i:s');
	$admin	= $secu->injection(@$_COOKIE['adminkuy']);
	$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
	$valid	= $secu->validadmin($admin, $kunci);
	if($valid==false){ header("location:$sistem/signout"); } else {
	$conn	= $base->open();
	$nomor	= $secu->injection(@$_POST['n']);
?>
<tr id="<?php echo("item$nomor"); ?>">
    <td>
    <a href="#modal1" onclick="<?php echo("mproduct($nomor, 'showproduct')"); ?>" data-toggle="modal">
    <div id="<?php echo("noproduct$nomor"); ?>">Pilih</div>
    </a>
    <input type="hidden" name="product[]" id="<?php echo("product$nomor"); ?>" class="itemproduct" readonly="readonly" />
    </td>
    <td><div id="<?php echo("detailproduct$nomor"); ?>"></div></td>
    <td><div id="<?php echo("satuanqty$nomor"); ?>"></div></td>
    <td><input type="text" name="dispro[]" class="inputangka" placeholder="0" required="required" /></td>
    <td>
    <center>
    	<a onclick="<?php echo("delprotlet($nomor)"); ?>"><span class="badge badge-danger"><i class="fa fa-times-circle"></i></span></a>
	</center>
	</td>
</tr>
<script type="text/javascript">
	$(".fortgl").mask("9999-99-99");
</script><?php
	$conn	= $base->close();
	}
?>