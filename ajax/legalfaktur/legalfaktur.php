<?php
    require_once('../../config/connection/connection.php');
    require_once('../../config/connection/security.php');
    require_once('../../config/function/data.php');

    $secu  = new Security;
    $base  = new DB;
    $data  = new Data;
    $sistem = $data->sistem('url_sis');
    $catat  = date('Y-m-d H:i:s');
    $admin  = $secu->injection(@$_COOKIE['adminkuy']);
    $kunci  = $secu->injection(@$_COOKIE['kuncikuy']);
    $valid  = $secu->validadmin($admin, $kunci);

    if($valid == false) { 
        header("location:$sistem/signout");
    } else {
        $conn = $base->open();
        $nomor = $secu->injection(@$_POST['n']);
?>
<tr id="<?php echo("no_faktur$nomor"); ?>">
    <td>
        <!-- changed: give each select a unique id to avoid duplicates and allow re-init -->
        <select name="no_faktur[]" id="no_faktur_<?php echo $nomor; ?>" class="form-control select2" style="width:100%" required="required">
            <option value="">-- Pilih Nomor Faktur --</option>
            <?php
                $status = 'belum balik';
                $master = $conn->prepare("
                    SELECT A.id_tfk, A.tgl_tfk, A.kode_tfk, A.total_tfk, B.nama_out, A.status_balik 
                    FROM transaksi_faktur AS A 
                    INNER JOIN outlet AS B ON A.id_out = B.id_out 
                    WHERE A.status_balik = :status 
                    AND YEAR(A.tgl_tfk) IN (2025, 2026) 
                    
                    UNION ALL
                    
                    SELECT A.id_tfk, A.tgl_tfk, A.kode_tfk, A.total_tfk, B.nama_out, A.status_balik 
                    FROM transaksi_faktur_pim AS A 
                    INNER JOIN outlet AS B ON A.id_out = B.id_out 
                    WHERE A.status_balik = :status2 
                    AND YEAR(A.tgl_tfk) IN (2025, 2026) 
                    
                    ORDER BY tgl_tfk DESC
                ");
                $master->bindParam(':status', $status, PDO::PARAM_STR);
                $master->bindParam(':status2', $status, PDO::PARAM_STR);
                $master->execute();
                while($hasil = $master->fetch(PDO::FETCH_ASSOC)) {
            ?>
                <option value="<?php echo($hasil['id_tfk']); ?>">
                    <?php echo($hasil['kode_tfk']); ?> 
                    ( <?php echo($hasil['nama_out']); ?>)
                </option>
            <?php } ?>
        </select>
    </td>
    <td>
        <center>
            <select name="ket[]" class="form-control">
                <option value="">-- Pilih Data --</option>
                <option value="Ada tanda terima">Ada Tanda Terima</option>
                <option value="tidak ada tanda terima">Tidak Ada Tanda Terima</option>
            </select>        
        </center>
    </td>
    <td>
        <center>
            <a onclick="<?php echo("removeitem('jumlegal', 'no_faktur', $nomor)"); ?>">
                <span class="badge badge-danger"><i class="fa fa-times-circle"></i></span>
            </a>
        </center>
    </td>
</tr>
<?php
    $conn = $base->close();
    }
?>
<!-- changed: initialize only the newly added select2 element with same options as the page -->
<script type="text/javascript">
    setTimeout(function(){
        if (typeof $ === 'undefined' || !$.fn || !$.fn.select2) return;
        var sel = $('#no_faktur_<?php echo $nomor; ?>');
        if (sel.length) {
            if (sel.hasClass('select2-hidden-accessible')) {
                try { sel.select2('destroy'); } catch(e) {}
            }
            sel.select2({
                placeholder: '-- Pilih Nomor Faktur --',
                width: '100%',
                allowClear: true
            });
        }
    }, 50);
</script>
