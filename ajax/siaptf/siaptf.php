<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');

	// Start session untuk cache sederhana
	if (session_status() === PHP_SESSION_NONE) { session_start(); }

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

	// Cache opsi faktur di session selama 60 detik
	$cacheKey  = 'siaptf_faktur_options_html';
	$cacheTime = 'siaptf_faktur_options_time';
	$ttl       = 60; // detik

	$needReload = true;
	if (isset($_SESSION[$cacheKey], $_SESSION[$cacheTime])) {
	    if (time() - $_SESSION[$cacheTime] < $ttl) {
	        $needReload = false;
	    }
	}

	if ($needReload) {
	    $optionsHtml = '<option value="">-- Select Data Faktur --</option>';
	    // Hapus LEFT JOIN yang tidak dipakai untuk percepat query
		$qmaster = "SELECT A.id_tfk, A.tgl_tfk, A.kode_tfk, B.nama_out, A.status_dokumentasi
					FROM transaksi_faktur AS A
					LEFT JOIN outlet AS B ON A.id_out = B.id_out
					WHERE A.tgl_tfk >= DATE_SUB(NOW(), INTERVAL 93 DAY)
					AND A.status_dokumentasi = 'sudah siap'
					ORDER BY A.tgl_tfk DESC";
	    $master = $conn->prepare($qmaster);
	    $master->execute();
	    while($hasil = $master->fetch(PDO::FETCH_ASSOC)){
	        $id   = htmlspecialchars($hasil['id_tfk'], ENT_QUOTES, 'UTF-8');
	        $kode = htmlspecialchars($hasil['kode_tfk'], ENT_QUOTES, 'UTF-8');
	        $nama = htmlspecialchars($hasil['nama_out'], ENT_QUOTES, 'UTF-8');
	        $optionsHtml .= '<option value="'.$id.'">'.$kode.' ( '.$nama.' )</option>';
	    }
	    $_SESSION[$cacheKey]  = $optionsHtml;
	    $_SESSION[$cacheTime] = time();
	} else {
	    $optionsHtml = $_SESSION[$cacheKey];
	}
?>
<tr id="<?php echo("no_faktur$nomor"); ?>">
    <td>
        <select name="no_faktur[]" id="<?php echo 'no_faktur_'.$nomor; ?>" class="form-control select2" required="required">
            <?php echo $optionsHtml; ?>
        </select>
    </td>
    <td><input type="text" name="ket[]" class="form-control" placeholder="Type here..." /></td>
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
<script type="text/javascript">
(function($){
    var $el = $('#<?php echo 'no_faktur_'.$nomor; ?>');
    if ($el.length && $.fn && $.fn.select2) {
        $el.select2({
            placeholder: '-- Pilih Nomor Faktur --',
            width: '100%',
            allowClear: true
        });
    }
})(jQuery);
</script>
