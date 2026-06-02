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
	$diskon	= $secu->injection(@$_GET['diskon']);
	$nomor	= intval($jumlah) + 1; // Pastikan $jumlah adalah angka
?>
<tr id="<?php echo("traddsales$nomor"); ?>">
	<td>
        <!-- Menggunakan data-id_tfk untuk menyimpan nilai id_tfk -->
        <a href="#modal1" onclick="<?php echo("addsalespim2($nomor, 'outlet')"); ?>" data-toggle="modal">
            <div id="<?php echo("noproduct$nomor"); ?>">Pilih</div>
        </a>
    <input type="hidden" name="kodestok[]" id="<?php echo("kodestok$nomor"); ?>" readonly="readonly" />
    <input type="hidden" name="product[]" id="<?php echo("product$nomor"); ?>" class="itemproduct" readonly="readonly" />
    <input type="hidden" name="prostok[]" id="<?php echo("prostok$nomor"); ?>" readonly="readonly" />
    </td>
    <td><div id="<?php echo("prodetail$nomor"); ?>">-</div></td>
	<td><div id="<?php echo("nobcode$nomor"); ?>">-</div></td>
	<td><div id="<?php echo("gudang$nomor"); ?>">-</div></td>
    <td><div id="<?php echo("tgled$nomor"); ?>">-</div></td>
	<!--<td><input type="text" name="catatan[]" class="form-control" placeholder="Ketik di sini..." /></td>-->
	<td><input type="text" name="harga[]" id="<?php echo("pharga$nomor"); ?>" class="inputangka" onkeyup="angka(this); hitungsalespim2(<?php echo $nomor; ?>);" placeholder="0" /></td>
    <td><input type="text" name="jumlah[]" id="<?php echo("pjumlah$nomor"); ?>" class="inputangka" value="1" onchange="<?php echo("jumlahsalespim2($nomor)"); ?>" onkeyup="angka(this)" placeholder="0" /></td>
    <!-- <td><div id="<?php echo("satuanqty$nomor"); ?>">-</div></td> -->
    <!-- Hapus kolom diskon -->
    <td style="display:none"><input type="hidden" name="diskon[]" id="<?php echo("pdiskon$nomor"); ?>" value="0" /></td>
    <td><input type="text" name="total[]" id="<?php echo("ptotal$nomor"); ?>" class="inputtotal" onkeyup="angka(this)" placeholder="0" readonly="readonly" /></td>
    <td>
    <center>
        <a onclick="<?php echo("delsalespim2($nomor)"); ?>"><span class="badge badge-danger"><i class="fa fa-times-circle"></i></span></a>
    </center>
    </td>
</tr>
<?php
	$conn	= $base->close();
?>
<!--
<script type="text/javascript">
$('.select2').select2({
	placeholder: '-- Pilih Data --',
	searchInputPlaceholder: 'Search options'
});
</script>
-->