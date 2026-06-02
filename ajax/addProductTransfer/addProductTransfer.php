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
	$from	= $secu->injection(@$_GET['from']);
	$to		= $secu->injection(@$_GET['to']);
	$fromnm	= $secu->injection(@$_GET['fromnm']);
	$tonm	= $secu->injection(@$_GET['tonm']);
	$type	= $secu->injection(@$_GET['type']);
	$self	= $secu->injection(@$_GET['self']);
	$nomor	= ($jumlah + 1);
?>
<tr id="<?php echo("addProductTransfer$nomor"); ?>">
    <td>
    	<a href="#modal2" onclick="addProductTransferModal('<?php echo $nomor; ?>', 'showProductTransfer', '<?php echo $type; ?>', '<?php echo $fromnm; ?>', '<?php echo $from; ?>')" data-toggle="modal">
			<div id="<?php echo("noproduct$nomor"); ?>">Pilih</div>
		</a>
    	<input type="hidden" name="product[]" id="<?php echo("product$nomor"); ?>" class="itemproduct" readonly="readonly" />
		<input type="hidden" name="kodestok[]" id="<?php echo("kodestok$nomor"); ?>" readonly="readonly" />
		<input type="hidden" name="bcode[]" id="<?php echo("bcode$nomor"); ?>" readonly="readonly" />
		<input type="hidden" name="idpsd[]" id="<?php echo("idpsd$nomor"); ?>" readonly="readonly" />
		<input type="hidden" name="namaproduct[]" id="<?php echo("namaproduct$nomor"); ?>" readonly="readonly" />
		<input type="hidden" name="id_trd[]" id="<?php echo("id_trd$nomor"); ?>" readonly="readonly" />
		<input type="hidden" name="tgl_expired[]" id="<?php echo("tgl_expired$nomor"); ?>" readonly="readonly" />
		<input type="hidden" name="tgl_psd[]" id="<?php echo("tgl_psd$nomor"); ?>" readonly="readonly" />
		<input type="hidden" name="gudang[]" id="<?php echo("gudang$nomor"); ?>" readonly="readonly" />
    </td>
    <td>
		<div id="<?php echo("prodetail$nomor"); ?>">-</div>
	</td>
    <td>
		<div id="<?php echo("nobcode$nomor"); ?>">-</div>
	</td>
	<td>
		<div id="<?php echo("tgled$nomor"); ?>">-</div>
	</td>
	<td>
		<div id="<?php echo("prostok$nomor"); ?>">-</div>
	</td>
	<td>
		<input type="text" name="harga[]" id="<?php echo("pharga$nomor"); ?>" class="inputangka" onkeyup="angka(this)" placeholder="0" readonly="readonly" />
	</td>
    <td>
		<input type="text" name="jumlah[]" id="<?php echo("pjumlah$nomor"); ?>" class="inputangka text-right" onchange="<?php echo("cekProdukTransfer($nomor)"); ?>" onkeyup="angka(this)" placeholder="0" required="required" />
	</td>
    <td>
		<div id="<?php echo("satuanqty$nomor"); ?>"></div>
	</td>
    <td>
		<center>
			<a onclick="<?php echo("deleteProductTransfer($nomor)"); ?>"><span class="badge badge-danger"><i class="fa fa-times-circle"></i></span></a>
		</center>
	</td>
</tr>
<?php
	$conn	= $base->close();
?>
<script type="text/javascript">
	$(".fortgl").mask("9999-99-99");
</script>
