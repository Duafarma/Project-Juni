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
<tr id="<?php echo("nokwi$nomor"); ?>">
    <td>
    <select name="nokwi[]" id="<?php echo 'nokwi_'.$nomor; ?>" class="form-control select2 select2-faktur" required="required">
        <option value="">-- Select Data Faktur --</option>
        <?php
                $qmaster = "
                        SELECT id_tfk, kode_tfk, total_tfk, 'cendo' as sumber FROM transaksi_faktur 
                        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)
                        
                        UNION
                        
                        SELECT id_tfk, kode_tfk, total_tfk, 'pim' as sumber FROM transaksi_faktur_pim 
                        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)
                        
                        ORDER BY kode_tfk ASC
                    ";

                 $master	= $conn->prepare($qmaster);
                 $master->execute();
                  while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
                ?>
               <option value="<?php echo($hasil['id_tfk'].'|'.$hasil['sumber']); ?>">
                    <?php echo("[$hasil[sumber]] $hasil[kode_tfk] (Rp. ".$data->angka($hasil['total_tfk']).")"); ?>
               </option>

         <?php } ?>
    </select>
    </td>
    <td><input type="text" name="ket[]" class="form-control" placeholder="Type here..." /></td>
    <td>
    <center>
        <a onclick="<?php echo("removeitem('jumlegal', 'nokwi', $nomor)"); ?>"><span class="badge badge-danger"><i class="fa fa-times-circle"></i></span></a>
    </center>
    </td>
</tr>
<?php
    $conn	= $base->close();
    }
?>
<script type="text/javascript">
// Inisialisasi Select2 khusus untuk row yang baru ditambahkan
(function () {
    if (typeof $ === 'undefined' || !$.fn || !$.fn.select2) return;
    var $sel = $('#<?php echo 'nokwi_'.$nomor; ?>');
    if (!$sel.length) return;

    if ($sel.hasClass('select2-hidden-accessible')) {
        try { $sel.select2('destroy'); } catch (e) {}
    }

    $sel.select2({
        placeholder: '-- Pilih Nomor Faktur --',
        width: '100%',
        allowClear: true,
        minimumResultsForSearch: 0, // selalu tampilkan kotak pencarian
        dropdownParent: $sel.closest('tr')
    });

    // Placeholder untuk field pencarian
    $sel.on('select2:open', function () {
        setTimeout(function () {
            $('.select2-container--open .select2-search__field')
                .attr('placeholder', 'Cari nomor faktur...');
        }, 0);
    });
})();
</script>
