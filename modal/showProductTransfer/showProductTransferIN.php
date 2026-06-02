<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$tgl	= date('Y-m-d');
	$conn	= $base->open();
	$nomor	= $secu->injection($_POST['x']);
	$kode	= $secu->injection($_POST['y']);
	$from	= $secu->injection($_POST['from']);
	$fromnm	= $secu->injection($_POST['fromnm']);
	$type	= $secu->injection($_POST['type']);
	$cart	= $secu->injection($_POST['cart']);
	// get stok
	$target 	= $data->get_apl($from);
	$targetUrl 	= $target[0]['base_url_apl'];
	$targetKey  = $target[0]['key_apl'];
	$encrypt    = md5($tgl . "#" . $targetKey);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $targetUrl . "/api/showProductTransfer.php?encrypt=" . $encrypt);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query(array('cart' => $cart)));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$res = curl_exec($ch);
	// curl handling error
	if (curl_errno($ch)) {
		$error_msg = curl_error($ch);
	}
	curl_close ($ch);
	if (isset($error_msg)) {
		$hasil 	= $error_msg;
	} else {
		$json 	= json_decode($res);
		$hasil = $json->result;
	}
?>
	<link href="<?php echo($data->sistem('url_sis').'/DataTables/datatables.min.css'); ?>" rel="stylesheet" />
    <div class="modal-header">
        <h6 class="modal-title" id="exampleModalLabel">Search Product <?php echo $fromnm; ?></h6>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
        </button>
    </div>
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-12">
				<div class="table-responsive">
					<table id="example1" class="tabelgetdata">
						<thead>
							<tr>
								<th>Kode</th>
								<th>Nama</th>
								<th><center>Batchcode</center></th>
								<th><center>Tgl. ED</center></th>
								<th><center>Gudang</center></th>
								<th><div align="right">Stok</div></th>
								<th><div align="right">Harga</div></th>
							</tr>
						</thead>
						<tbody>
						<?php
							if (count($hasil)>1) {
								$no		= 1;
								foreach ($hasil as $hsl) {
						?>
							<tr onClick="<?php echo("showProductTransfer('$nomor', '$hsl->id_psd', '$hsl->id_pro', '$hsl->nama_pro', '$hsl->kode_pro', '$hsl->harga_phg', '$hsl->berat_pro', '$hsl->nama_kpr', '$hsl->satuan_kpr', '$hsl->nama_spr', '$hsl->no_bcode', '$hsl->tgl_expired', '$hsl->sisa_psd', '$hsl->id_trd', '$hsl->tgl_psd', '$hsl->gudang')"); ?>">
								<td><?php echo($hsl->kode_pro); ?></td>
								<td><?php echo($hsl->nama_pro); ?></td>
								<td><center><?php echo($hsl->no_bcode); ?></center></td>
								<td><center><?php echo($hsl->tgl_expired); ?></center></td>
								<td><center><?php echo($hsl->gudang); ?></center></td>
								<td><div align="right"><?php echo($hsl->sisa_psd); ?></div></td>
								<td><div align="right"><?php echo($data->angka($hsl->harga_phg)); ?></div></td>
							</tr>
						<?php
									$no++;
								}
							} else {
						?>
							<tr><td colspan="6"><?php echo($hasil); ?></td></tr>
						<?php } ?>
						</tbody>
					</table>
				</div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Close</button>
    </div>
<?php $conn	= $base->close(); ?>
	<script type="text/javascript" src="<?php echo($data->sistem('url_sis').'/DataTables/datatables.min.js'); ?>"></script>
	<script type="text/javascript">
	$('#example1').DataTable({
	  language: {
		searchPlaceholder: 'Search...',
		sSearch: '',
		lengthMenu: 'Show _MENU_ data',
		"info": "_START_ to _END_ of _TOTAL_ data",
		"paginate": {
			"next": "Last",
			"previous": "First"
		}
	  }
	});
    </script>
