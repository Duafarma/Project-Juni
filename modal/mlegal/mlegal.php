<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$tgl	= date('Y-m-d');
	$sistem	= $data->sistem('url_sis');
	$conn	= $base->open();
	$nomor	= $secu->injection($_POST['x']);
	$kode	= $secu->injection($_POST['y']);
?>
	<link href="<?php echo("$sistem/DataTables/datatables.min.css"); ?>" rel="stylesheet" />
    <div class="modal-header">
        <h6 class="modal-title" id="exampleModalLabel">Search Product</h6>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
        </button>
    </div>
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-12">
			<table id="example1" class="tabelgetdata">
            	<thead>
                	<tr>
                    	<th>Kode</th>
                    	<th>Nama</th>
                    	<th><div align="right">Harga + PPN</div></th>
                    </tr>
                </thead>
                <tbody>
				<?php
					$status	= 'Active';
					$no		= 1;
					$master	= $conn->prepare("SELECT A.id_pro, A.kode_pro, A.nama_pro, A.berat_pro, B.harga_phg, B.hargap_phg, C.nama_kpr, D.nama_spr FROM produk AS A LEFT JOIN produk_harga AS B ON A.id_pro=B.id_pro LEFT JOIN kategori_produk AS C ON A.id_kpr=C.id_kpr LEFT JOIN satuan_produk AS D ON A.id_spr=D.id_spr WHERE B.status_phg=:status ORDER BY A.nama_pro ASC");
					$master->bindParam(':status', $status, PDO::PARAM_STR);
					$master->execute();
					while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
				?>
                	<tr onclick="<?php echo("getproduct('$nomor', '$hasil[id_pro]', '$hasil[nama_pro]', '$hasil[kode_pro]', '$hasil[harga_phg]', '$hasil[berat_pro]', '$hasil[nama_kpr]', '$hasil[nama_spr]')"); ?>">
                    	<td><?php echo($hasil['kode_pro']); ?></td>
                    	<td><?php echo($hasil['nama_pro']); ?></td>
                    	<td><div align="right"><?php echo($data->angka($hasil['hargap_phg'])); ?></div></td>
                    </tr>
				<?php $no++; } ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Close</button>
    </div>
<?php
	$conn	= $base->close();
?>
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