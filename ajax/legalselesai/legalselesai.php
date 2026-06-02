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
<tr id="<?php echo("no_faktur$nomor"); ?>">
    <td>
    <!-- changed: unique, valid id and proper class so select2 can be initialized with search -->
    <select name="no_faktur[]" id="no_faktur_<?php echo $nomor; ?>" class="form-control select2" required="required">
        <option value="">-- Select Data Faktur --</option>
        <?php
                //  $status	= 'sudah failing';
                 $master = $conn->prepare("SELECT A.id_tfk,A.tgl_tfk, A.status_tfk, A.kode_tfk,A.total_tfk,B.nama_out,A.status_failing FROM transaksi_faktur AS A INNER JOIN 
                                           outlet AS B ON A.id_out=B.id_out 
                                           WHERE 
                                                A.tgl_tfk >= DATE_SUB(NOW(), INTERVAL 60 DAY)  ORDER BY A.tgl_tfk DESC");
                // $master->bindParam(':status', $status, PDO::PARAM_STR);
                $master->execute();
                  while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
                ?>
                 <option value="<?php echo($hasil['id_tfk']); ?>">
                     <!--<?php echo("$hasil[kode_tfk] ($hasil[total_tfk])"); ?>-->
                     <?php echo($hasil['kode_tfk']); ?>
                     
                     ( <?php echo($hasil['nama_out']); ?>)
                 </option>
         <?php } ?>
    </select>
    </td>
    <td><input type="text" name="ket[]" class="form-control" placeholder="Type here..." /></td>
    <!-- <td><input type="text" name="tgllegal[]" class="form-control fortgl" placeholder="9999-99-99" /></td> -->
    <td>
    <center>
        <a onclick="<?php echo("removeitem('jumlegal', 'no_faktur', $nomor)"); ?>"><span class="badge badge-danger"><i class="fa fa-times-circle"></i></span></a>
    </center>
    </td>
</tr>
<?php
    $conn	= $base->close();
    }
?>
<!-- changed: initialize only the newly added select2 element so it shows search box like other inputs -->
<script type="text/javascript">
    setTimeout(function(){
        if (typeof $ === 'undefined' || !$.fn || !$.fn.select2) return;
        var sel = $('#no_faktur_<?php echo $nomor; ?>');
        if (sel.length) {
            // ensure clean state
            if (sel.hasClass('select2-hidden-accessible')) {
                try { sel.select2('destroy'); } catch(e) {}
            }
            sel.select2({
                placeholder: '-- Pilih Nomor Faktur --',
                width: '100%',
                allowClear: true,
                minimumResultsForSearch: 0 // force search box to appear
            });
        }
    }, 50);
</script>
