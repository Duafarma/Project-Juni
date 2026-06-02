<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	$base	= new DB;
	$secu	= new Security;
	$data	= new Data;
	$sistem	= $data->sistem('url_sis');
	$conn	= $base->open();
	$jumlah	= $secu->injection($_GET['jumlah']);
	$nomor	= $jumlah + 1;
?>
<tr id="<?php echo("traddbankaccount$nomor"); ?>">
    <td>
    <select name="namebank[]" class="form-control search-box">
        <option value="">-- Select Bank --</option>
    <?php
        $conn	= $base->open();
        $master	= $conn->prepare("SELECT id_bank, nama_bank FROM bank ORDER BY nama_bank ASC");
        $master->execute();
        while($hasil= $master->fetchObject()){
            echo('<option value="'.$hasil->id_bank.'">'.$hasil->nama_bank.'</option>');
        }
    ?>
    </select>
    </td>
    <td><input type="text" name="phonebank[]" class="form-control" placeholder="Input box" /></td>
    <td><input type="text" name="emailbank[]" class="form-control" placeholder="Input box" /></td>
    <td><center><button type="button" class="badge badge-danger" onClick="<?php echo("remmaximal('addbankaccount','$nomor')"); ?>"><i class="fa fa-trash"></i></button></center></td>
</tr>
<script type="text/javascript" src="<?php echo("$sistem/config/js/fazlurr.js"); ?>"></script>
