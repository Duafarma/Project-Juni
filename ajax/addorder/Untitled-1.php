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
	$act	= $secu->injection(@$_GET['act']);
	$conn	= $base->open();

	$jumlah	= $secu->injection(@$_GET['jumlah']);
	$nomor	= ($jumlah+ 1);
?>
<tr id="<?php echo("tratransfer$nomor"); ?>">
    <td>
    <a href="#modal1" onclick="<?php echo("mproduct($nomor, 'showproduct')"); ?>" data-toggle="modal">
    <div id="<?php echo("noproduct$nomor"); ?>">Pilih</div>
    </a>
    <input type="hidden" name="product[]" id="<?php echo("product$nomor"); ?>" class="itemproduct" readonly="readonly" />
    </td>
    <td><input type="text" name="batchcode_trd[]" class="inputrans" placeholder="-" /></td>
    <td><input type="text" name="tbcode_trd[]" class="inputrans fortgl" placeholder="9999-99-99" /></td>
	<td><input type="text" name="gudang[]" class="inputrans" placeholder=" Cibinong / Meruya " /></td>

    <td><input type="text" name="jumlah[]" id="<?php echo("pjumlah$nomor"); ?>" class="inputangka" onchange="<?php echo("cekproduk($nomor)"); ?>" onkeyup="angka(this)" placeholder="0" required="required" /></td>
    <td>
	<center>
    	<a onclick="<?php echo("deletetransfer($nomor)"); ?>"><span class="badge badge-danger"><i class="fa fa-times-circle"></i></span></a>
	</center>
	</td>
</tr>
<?php
	$conn	= $base->close();
?>
<script type="text/javascript">
	$(".fortgl").mask("9999-99-99");
</script>