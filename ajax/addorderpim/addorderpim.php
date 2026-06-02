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
	$nomor	= ($jumlah + 1);
?>
<tr id="<?php echo("traddorder$nomor"); ?>">
    <td>
    <a href="#modal1" onclick="<?php echo("mproduct($nomor, 'showproduct')"); ?>" data-toggle="modal">
    <div id="<?php echo("noproduct$nomor"); ?>">Pilih</div>
    </a>
    <input type="hidden" name="product[]" id="<?php echo("product$nomor"); ?>" class="itemproduct" readonly="readonly" />
    </td>
    <td><div id="<?php echo("detailproduct$nomor"); ?>"></div></td>
<!--
    <td><input type="text" name="batchcode[]" class="inputrans" placeholder="-" /></td>
    <td><input type="text" name="tbatchcode[]" class="inputrans fortgl" placeholder="9999-99-99" /></td>
    <td><input type="text" name="harga[]" id="<?php //echo("pharga$nomor"); ?>" class="inputangka" onkeyup="angka(this)" placeholder="0" readonly="readonly" /></td>
-->
    <td><input type="text" name="jumlah[]" id="<?php echo("pjumlah$nomor"); ?>" class="inputangka" onchange="<?php echo("cekproduk($nomor)"); ?>" onkeyup="angka(this)" placeholder="0" required="required" /></td>
    <td><div id="<?php echo("satuanqty$nomor"); ?>"></div></td>
<!--    
    <td><input type="text" name="subtotal[]" id="<?php //echo("psubtotal$nomor"); ?>" class="inputtotal" onkeyup="angka(this)" placeholder="0" readonly="readonly" /></td>
    <td><input type="text" name="diskon[]" id="<?php //echo("pdiskon$nomor"); ?>" class="inputangka" value="0" onchange="<?php //echo("hitungsales($nomor)"); ?>" onkeyup="angka(this)" placeholder="0" /></td>
    <td><input type="text" name="total[]" id="<?php //echo("ptotal$nomor"); ?>" class="inputtotal" onkeyup="angka(this)" placeholder="0" readonly="readonly" /></td>
-->
    <td>
    <center>
    	<a onclick="<?php echo("deleteorder($nomor)"); ?>"><span class="badge badge-danger"><i class="fa fa-times-circle"></i></span></a>
	</center>
	</td>
</tr>
<?php
	$conn	= $base->close();
?>
<script type="text/javascript">
	$(".fortgl").mask("9999-99-99");
</script>