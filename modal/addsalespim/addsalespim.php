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
        <h6 class="modal-title" id="exampleModalLabel">Search Product <?php echo $cart; ?></h6>
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
                    	<!--<th>Kode</th>-->
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
					$nama_p	= 'MP0000000003';
					$no		= 1;
					$master = $conn->prepare("SELECT 
					A.id_psd, 
					A.no_bcode, 
					A.tgl_expired,
					A.created_at,
					A.gudang,
					A.tgl_psd,
					A.sisa_psd,
					B.id_pro,
					B.kode_pro,
					B.nama_pro,
					B.berat_pro,
					C.harga_a,
					C.harga_b,
					C.harga_c,
					D.nama_kpr,
					D.satuan_kpr,
					E.nama_spr,
					(SELECT persen_pds FROM produk_diskon WHERE id_pro = B.id_pro AND id_out = :mitra LIMIT 1) AS persen_pds,
					G.hargapim
    				FROM produk_stokdetail AS A
    				LEFT JOIN produk AS B ON A.id_pro = B.id_pro
    				LEFT JOIN produk_hargapim AS C ON B.id_pro = C.id_pro
    				LEFT JOIN kategori_produk AS D ON B.id_kpr = D.id_kpr
    				LEFT JOIN satuan_produk AS E ON B.id_spr = E.id_spr
    				LEFT JOIN jenis_hargapim AS G ON G.id_out = :mitra
    				WHERE $notin AND A.sisa_psd > 0 AND B.nama_p = :nama_p
    				GROUP BY A.id_psd
    				ORDER BY B.nama_pro ASC");

					$master->bindParam(':mitra', $mitra, PDO::PARAM_STR);
					$master->bindParam(':nama_p', $nama_p, PDO::PARAM_STR); // Static value
					$master->execute();
					$no = 1;
					while ($hasil = $master->fetch(PDO::FETCH_ASSOC)) {
						// Hitung harga langsung di dalam loop
						$harga = 0;
						if ($hasil['hargapim'] == 'A') {
							$harga = $hasil['harga_a'];
						} elseif ($hasil['hargapim'] == 'B') {
							$harga = $hasil['harga_b'];
						} elseif ($hasil['hargapim'] == 'C') {
							$harga = $hasil['harga_c'];
						}

						// Format harga tampil (manusia), tapi kirim harga murni ke onclick (angka mentah)
						$harga_tampil = number_format($harga, 0, '.', ',');
						$harga_kirim = floatval($harga); // Pastikan angka

				?>
                	<tr onClick="<?php echo("getproductsalespim('$nomor', '$hasil[id_psd]', '$hasil[id_pro]', '$hasil[nama_pro]', '$hasil[kode_pro]', '$harga', '$hasil[berat_pro]', '$hasil[nama_kpr]', '$hasil[satuan_kpr]', '$hasil[nama_spr]', '$hasil[no_bcode]', '$hasil[tgl_expired]', '$hasil[gudang]', '$hasil[sisa_psd]', '$hasil[persen_pds]')"); ?>">
					<td><?php echo($hasil['nama_pro']); ?></td>
					<td><center><?php echo($hasil['no_bcode']); ?></center></td>
					<td><center><?php echo($hasil['gudang']); ?></center></td>
					<td><center><?php echo($hasil['tgl_expired']); ?></center></td>
					<td><div align="right"><?php echo($hasil['sisa_psd']); ?></div></td>
					<td><div align="right">
						<?php
							// Menyimpan harga dalam variabel $harga sesuai dengan tipe hargapim
							if ($hasil['hargapim'] == 'A') {
								$harga = $hasil['harga_a'];  // Harga normal
							} elseif ($hasil['hargapim'] == 'B') {
								$harga = $hasil['harga_b'];  // Harga lain
							} elseif ($hasil['hargapim'] == 'C') {
								$harga = $hasil['harga_c'];  // Markup harga
							} else {
								$harga = 0;  // Harga tidak ada
							}
						?>
						<!-- Tampilkan harga yang telah diproses -->
						<?php echo number_format($harga, 0, '.', ','); ?>
					</div></td>
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