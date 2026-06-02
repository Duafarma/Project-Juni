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
    <td><input type="text" name="harpoout[]" class="inputangka" id="<?php echo("harpoout$nomor"); ?>" placeholder="0" onkeyup="updateHarpoOutP(<?php echo($nomor); ?>)" required="required" /></td>
    <td><input type="text" name="harpooutp[]" class="inputangka" id="<?php echo("harpooutp$nomor"); ?>" placeholder="0" readonly required="required" /></td>

    <td>
        <center>
            <a onclick="<?php echo("delprotlet($nomor)"); ?>"><span class="badge badge-danger"><i class="fa fa-times-circle"></i></span></a>
        </center>
    </td>
</tr>

<script>
    function updateHarpoOutP(nomor) {
        var harpoout = document.getElementById("harpoout" + nomor).value;
        if (harpoout !== '') {
            var harpooutValue = parseFloat(harpoout);
            var harpooutpValue = harpooutValue + (harpooutValue * 0.11); // Menambahkan 11%
            document.getElementById("harpooutp" + nomor).value = harpooutpValue.toFixed(0); // Menampilkan dengan 2 desimal
        } else {
            document.getElementById("harpooutp" + nomor).value = ''; // Jika input kosong
        }
    }
</script>

<?php
	$conn	= $base->close();
	}
?>