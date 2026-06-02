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
<tr id="<?php echo("id_pro$nomor"); ?>">
    <td>
	<select name="id_pro[]"  id="id_pro[]" class="form-control select2" required="required">
    	<option value="">-- Select Data Produk --</option>
		<?php
				//  $status	= 'sudah failing';
				$status	= 'Active';
               	$master	= $conn->prepare("SELECT A.id_pro, A.kode_pro, A.nama_pro, A.berat_pro, B.harga_phg, C.nama_kpr, C.satuan_kpr, D.nama_spr FROM produk AS A LEFT JOIN produk_harga AS B ON A.id_pro=B.id_pro LEFT JOIN kategori_produk AS C ON A.id_kpr=C.id_kpr LEFT JOIN satuan_produk AS D ON A.id_spr=D.id_spr WHERE B.status_phg=:status GROUP BY A.id_pro, A.kode_pro, A.nama_pro, A.berat_pro, B.harga_phg, C.nama_kpr, C.satuan_kpr, D.nama_spr ORDER BY A.nama_pro ASC ");
				$master->bindParam(':status', $status, PDO::PARAM_STR);
									$master->execute();
                  while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
                ?>
                 <option value="<?php echo($hasil['id_pro']); ?>">
                     <?php echo($hasil['nama_pro']); ?>
                     
                     ( <?php echo($hasil['satuan_kpr']); ?>)
                 </option>
         <?php } ?>
    </select>

    </td>
    <td>
        <input type="text" name="no_bcode[]" class="form-control" placeholder="Type here..." />
    
    </td>
     <td>
         <input type="text" name="ed[]" class="form-control fortgl" placeholder="9999-99-99" />
     </td> 
      <td>
         <input type="number" name="qty[]" class="form-control fortgl" placeholder="0" />
     </td> 
     <td>
        <input type="text" name="gudang[]" class="form-control" placeholder="Type here..." />
    
    </td>
    <td>
    <center>
    	<a onclick="<?php echo("removeitem('jumlegal', 'id_pro', $nomor)"); ?>"><span class="badge badge-danger"><i class="fa fa-times-circle"></i></span></a>
	</center>
	</td>
</tr>
<!-- <script type="text/javascript">
	$(".fortgl").mask("9999-99-99");
</script> -->
<?php
	$conn	= $base->close();
	}
?>
<script type="text/javascript">
$('.select2').select2({
	placeholder: '-- Pilih Nomor Faktur --',
	searchInputPlaceholder: 'Search options'
});
</script>
