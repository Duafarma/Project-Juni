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
	<select name="no_faktur[]"  id="no_faktur<?php echo($nomor); ?>" class="form-control select2" required="required">
    	<option value="">-- Pilih Data Faktur --</option>
		<?php
				 $status	= 'belum balik';
                 $master = $conn->prepare("
                                            SELECT A.id_tfk, A.tgl_tfk, A.kode_tfk, A.total_tfk, B.nama_out, A.status_dokumen, 'Cendo' AS sumber
                                            FROM transaksi_faktur AS A 
                                            INNER JOIN outlet AS B ON A.id_out = B.id_out 
                                            WHERE A.status_dokumen = :status AND YEAR(A.tgl_tfk) IN (2025, 2026) 
                                        
                                            UNION ALL
                                        
                                            SELECT A.id_tfk, A.tgl_tfk, A.kode_tfk, A.total_tfk, B.nama_out, A.status_dokumen, 'PIM' AS sumber
                                            FROM transaksi_faktur_pim AS A 
                                            INNER JOIN outlet AS B ON A.id_out = B.id_out 
                                            WHERE A.status_dokumen = :status AND YEAR(A.tgl_tfk) IN (2025, 2026)
                                        
                                            ORDER BY tgl_tfk DESC
                                        ");
                $master->bindParam(':status', $status, PDO::PARAM_STR);
				$master->execute();
                  while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
                ?>
                 <option value="<?php echo($hasil['sumber'] . '|' . $hasil['id_tfk']); ?>">
                     <!--<?php echo("$hasil[kode_tfk] ($hasil[total_tfk])"); ?>-->
                     <?php echo($hasil['kode_tfk']); ?>
                            
                     ( <?php echo($hasil['nama_out']); ?> - <?php echo($hasil['sumber']); ?>)
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
<!-- <script type="text/javascript">
	$(".fortgl").mask("9999-99-99");
</script> -->
<?php
	$conn	= $base->close();
	}
?>
<script type="text/javascript">
// Let the main page handle select2 initialization
// $('.select2').select2({
//     placeholder: '-- Pilih Nomor Faktur --',
//     searchInputPlaceholder: 'Search options',
//     width: '100%',
//     allowClear: true
// });
</script>
