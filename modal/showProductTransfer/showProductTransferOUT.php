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
	$fromnm	= $secu->injection($_POST['fromnm']);
	$type	= $secu->injection($_POST['type']);
	$cart	= $secu->injection($_POST['cart']);
	$notin 	= empty($cart) ? "A.id_psd!=''" : "A.id_psd NOT IN('".str_replace("-", "', '", $cart)."')";
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
							$status	= 'Active';
							$no		= 1;
							$qMaster = "SELECT
											A.id_psd,
											A.no_bcode,
											A.tgl_expired,
											A.sisa_psd,
											B.id_pro,
											B.kode_pro,
											B.nama_pro,
											B.berat_pro,
											C.harga_phg,
											D.nama_kpr,
											D.satuan_kpr,
											E.nama_spr,
											A.id_trd,
											A.tgl_psd,
											A.gudang
										FROM
											produk_stokdetail AS A
										LEFT JOIN produk AS B ON
											A.id_pro = B.id_pro
										LEFT JOIN produk_harga AS C ON
											B.id_pro = C.id_pro
										LEFT JOIN kategori_produk AS D ON
											B.id_kpr = D.id_kpr
										LEFT JOIN satuan_produk AS E ON
											B.id_spr = E.id_spr
										WHERE
											$notin AND 
											A.sisa_psd > 0 AND 
											C.status_phg =:status
										GROUP BY
											A.id_psd,
											A.no_bcode,
											A.tgl_expired,
											A.sisa_psd,
											B.id_pro,
											B.kode_pro,
											B.nama_pro,
											B.berat_pro,
											C.harga_phg,
											D.nama_kpr,
											D.satuan_kpr,
											E.nama_spr,
											A.id_trd,
											A.tgl_psd,
											A.gudang";
							$master	= $conn->prepare($qMaster);
							$master->bindParam(':status', $status, PDO::PARAM_STR);
							$master->execute();
							while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
						?>
							<tr onClick="<?php echo("showProductTransfer('$nomor', '$hasil[id_psd]', '$hasil[id_pro]', '$hasil[nama_pro]', '$hasil[kode_pro]', '$hasil[harga_phg]', '$hasil[berat_pro]', '$hasil[nama_kpr]', '$hasil[satuan_kpr]', '$hasil[nama_spr]', '$hasil[no_bcode]', '$hasil[tgl_expired]', '$hasil[sisa_psd]', '$hasil[id_trd]', '$hasil[tgl_psd]', '$hasil[gudang]')"); ?>">
								<td><?php echo($hasil['kode_pro']); ?></td>
								<td><?php echo($hasil['nama_pro']); ?></td>
								<td><center><?php echo($hasil['no_bcode']); ?></center></td>
								<td><center><?php echo($hasil['tgl_expired']); ?></center></td>
								<td><center><?php echo($hasil['gudang']); ?></center></td>
								<td><div align="right"><?php echo($hasil['sisa_psd']); ?></div></td>
								<td><div align="right"><?php echo($data->angka($hasil['harga_phg'])); ?></div></td>
							</tr>
						<?php $no++; } ?>
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
