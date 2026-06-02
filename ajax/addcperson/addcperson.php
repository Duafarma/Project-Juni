<?php
	$jumlah	= $_GET['jumlah'];
	$nomor	= $jumlah + 1;
?>
<tr id="<?php echo("traddcperson$nomor"); ?>">
    <td><input type="text" name="nameperson[]" class="form-control" placeholder="Input box" /></td>
    <td><input type="text" name="phoneperson[]" class="form-control" placeholder="Input box" /></td>
    <td><input type="text" name="emailperson[]" class="form-control" placeholder="Input box" /></td>
    <td><center><button type="button" class="badge badge-danger" onClick="<?php echo("remmaximal('addcperson','$nomor')"); ?>"><i class="fa fa-trash"></i></button></center></td>
</tr>
