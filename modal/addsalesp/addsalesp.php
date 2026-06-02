<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$conn	= $base->open();
	$sistem	= $data->sistem('url_sis');
	$tgl	= date('Y-m-d');
	$nomor	= $secu->injection($_POST['x']);
	$cart	= $secu->injection($_POST['y']);
	$mitra	= $secu->injection($_POST['m']);
	$notin 	= empty($cart) ? "A.id_psd!=''" : "A.id_psd NOT IN('".str_replace("-", "', '", $cart)."')";
?>
	<link href="<?php echo("$sistem/DataTables/datatables.min.css"); ?>" rel="stylesheet" />
    <div class="modal-header">
        <h6 class="modal-title" id="exampleModalLabel">Search Product Pinjaman <?php echo $cart; ?></h6>
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
						<th><center>Gudang</center></th>
                    	<th><center>Tgl. ED</center></th>
                    	<th><div align="right">Stok</div></th>
                    	<th><div align="right">Harga</div></th>
                    </tr>
                </thead>
                <tbody>
				<?php
					$status	= 'Active';
					$no		= 1;
					$master	= $conn->prepare("SELECT A.id_psd, A.no_bcode, A.tgl_expired, A.gudang, A.sisa_psd, B.id_pro, B.kode_pro, B.nama_pro, B.berat_pro, C.harga_phg, D.nama_kpr, D.satuan_kpr, E.nama_spr, F.persen_pds FROM produk_stokdetail AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro LEFT JOIN produk_harga AS C ON B.id_pro=C.id_pro LEFT JOIN kategori_produk AS D ON B.id_kpr=D.id_kpr LEFT JOIN satuan_produk AS E ON B.id_spr=E.id_spr LEFT JOIN produk_diskon AS F ON B.id_pro=F.id_pro WHERE $notin AND A.sisa_psd>0 AND C.status_phg=:status AND F.id_out=:mitra GROUP BY A.id_psd");
					$master->bindParam(':status', $status, PDO::PARAM_STR);
					$master->bindParam(':mitra', $mitra, PDO::PARAM_STR);
					$master->execute();
					while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
				?>
                	<tr onClick="<?php echo("getproductsalesp('$nomor', '$hasil[id_psd]', '$hasil[id_pro]', '$hasil[nama_pro]', '$hasil[kode_pro]', '$hasil[harga_phg]', '$hasil[berat_pro]', '$hasil[nama_kpr]', '$hasil[satuan_kpr]', '$hasil[nama_spr]', '$hasil[no_bcode]', '$hasil[tgl_expired]', '$hasil[gudang]', '$hasil[sisa_psd]', '$hasil[persen_pds]')"); ?>">
                    	<td><?php echo($hasil['kode_pro']); ?></td>
                    	<td><?php echo($hasil['nama_pro']); ?></td>
                    	<td><center><?php echo($hasil['no_bcode']); ?></center></td>
                    	<td><center><?php echo($hasil['gudang']); ?></center></td>
                    	<td><center><?php echo($hasil['tgl_expired']); ?></center></td>
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
	<script type="text/javascript" src="<?php echo("$sistem/DataTables/datatables.min.js"); ?>"></script>
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
	//$('.dataTables_length select').select2({ minimumResultsForSearch: Infinity });
    </script>