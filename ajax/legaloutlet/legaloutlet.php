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
<tr id="<?php echo("ileg$nomor"); ?>">
    <td>
	<select name="legal[]" class="form-control" required="required">
    	<option value="">-- Select Legal --</option>
    <?php
		$master	= $conn->prepare("SELECT id_klg, nama_klg FROM kategori_legal ORDER BY nama_klg ASC");
		$master->execute();
		while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
	?>
    	<option value="<?php echo($hasil['id_klg']); ?>"><?php echo($hasil['nama_klg']); ?></option>
    <?php } ?>
    </select>
    </td>
    <td><input type="text" name="ketlegal[]" class="form-control" placeholder="Type here..." /></td>
    <td><input type="text" name="tgllegal[]" class="form-control fortgl" placeholder="9999-99-99" /></td>
    <td>
    <center>
    	<a onclick="<?php echo("removeitem('jumlegal', 'ileg', $nomor)"); ?>"><span class="badge badge-danger"><i class="fa fa-times-circle"></i></span></a>
	</center>
	</td>
</tr>
<script type="text/javascript">
	$(".fortgl").mask("9999-99-99");
</script>
<?php
	$conn	= $base->close();
	}
?>