<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$sistem	= $data->sistem('url_sis');
	$admin	= $secu->injection(@$_COOKIE['adminkuy']);
	$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
	$valid	= $secu->validadmin($admin, $kunci);
	if($valid==false){ header("location:$sistem/signout"); } else {
	$conn	= $base->open();
	$nomor	= $secu->injection(@$_POST['n']);
?>
<tr id="<?php echo("ileg$nomor"); ?>">
    <td>
	<select name="legal[<?php echo($nomor); ?>]" class="form-control legal-select" data-row="<?php echo($nomor); ?>" required="required">
    	<option value="">-- Select Legal --</option>
    <?php
		$master	= $conn->prepare("SELECT id_klg, nama_klg FROM kategori_legal ORDER BY nama_klg ASC");
		$master->execute();
		while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			$namaLower = strtolower(trim($hasil['nama_klg']));
			$isSpesimen = (
				(strpos($namaLower, 'spesimen') !== false || strpos($namaLower, 'specimen') !== false)
				&& (strpos($namaLower, 'ttd') !== false || strpos($namaLower, 'tanda tangan') !== false)
			) ? '1' : '0';
	?>
    	<option value="<?php echo($hasil['id_klg']); ?>" data-spesimen="<?php echo($isSpesimen); ?>"><?php echo($hasil['nama_klg']); ?></option>
    <?php } ?>
    </select>
    </td>
    <td>
		<input type="text" name="ketlegal[<?php echo($nomor); ?>]" id="<?php echo('ketlegal'.$nomor); ?>" class="form-control legal-ket" placeholder="Type here..." />
		<label class="mg-b-0 legal-doc" id="<?php echo('docwrap'.$nomor); ?>" style="display:none;">
			<input type="checkbox" class="legal-dok" /> Ada <small class="tx-gray-500">(uncheck = Tidak)</small>
		</label>
	</td>
    <td>
		<input type="text" name="tgllegal[<?php echo($nomor); ?>]" id="<?php echo('tgllegal'.$nomor); ?>" class="form-control fortgl legal-exp" placeholder="9999-99-99" />
	</td>
    <td>
		<input type="file" name="doklegal[<?php echo($nomor); ?>]" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx" />
		<input type="hidden" name="olddoklegal[<?php echo($nomor); ?>]" value="" />
	</td>
    <td>
    <center>
    	<a onclick="<?php echo("removeitem('jumlegal', 'ileg', $nomor)"); ?>"><span class="badge badge-danger"><i class="fa fa-times-circle"></i></span></a>
	</center>
	</td>
</tr>

<script type="text/javascript">
	(function(){
		function isSpesimenSelected($opt){
			var optText = ($opt.text() || '').toLowerCase();
			var hasSpesimenWord = (optText.indexOf('spesimen') !== -1) || (optText.indexOf('specimen') !== -1);
			var isSpesimenText = hasSpesimenWord && (optText.indexOf('ttd') !== -1 || optText.indexOf('tanda tangan') !== -1);
			return ($opt.data('spesimen') == 1) || isSpesimenText;
		}

		function applyRowState($row){
			var $sel = $row.find('select.legal-select');
			if(!$sel.length) return;

			var $opt = $sel.find('option:selected');
			var isSpesimen = isSpesimenSelected($opt);
			var $ket = $row.find('input.legal-ket');
			var $exp = $row.find('input.legal-exp');
			var $wrap = $row.find('.legal-doc');
			var $chk = $row.find('input.legal-dok');

			if(isSpesimen){
				$wrap.show();
				$ket.hide();
				$exp.hide();
				$exp.val('9999-99-99');
				if($ket.val() !== '1' && $ket.val() !== '0'){
					var legacy = ($ket.val() || '').toString().trim();
					$ket.val(legacy !== '' ? '1' : '0');
				}
				$chk.prop('checked', $ket.val() === '1');
			} else {
				$wrap.hide();
				$ket.show();
				$exp.show();
				if($ket.val() === '0' || $ket.val() === '1'){
					$ket.val('');
				}
			}
		}

		var $row = $('#ileg<?php echo((int)$nomor); ?>');
		if ($.fn.mask) { $row.find('.fortgl').mask('9999-99-99'); }

		$(document).on('change', '#ileg<?php echo((int)$nomor); ?> select.legal-select', function(){
			applyRowState($(this).closest('tr'));
		});

		$(document).on('change', '#ileg<?php echo((int)$nomor); ?> input.legal-dok', function(){
			var $r = $(this).closest('tr');
			$r.find('input.legal-ket').val(this.checked ? '1' : '0');
		});

		applyRowState($row);
	})();
</script>

<?php
	$conn	= $base->close();
	}
?>
