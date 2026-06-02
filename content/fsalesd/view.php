<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Penjualan</a></li>
                <li class="breadcrumb-item active" aria-current="page">Surat Jalan</li>
            </ol>
        </nav>
        <h4 class="content-title">Detail Data - Surat Jalan</h4>
    </div>
</div>
<?php
	$kode	= $secu->injection($_GET['keycode']);
	$read	= $conn->prepare("SELECT A.kode_tsl, A.subtot_tsl, A.ppn_tsl, A.total_tsl, A.tgl_tsl, B.nama_out FROM transaksi_sales AS A LEFT JOIN outlet AS B ON A.id_out=B.id_out WHERE A.id_tsl=:kode");
	$read->bindParam(':kode', $kode, PDO::PARAM_STR);
	$read->execute();
	$view	= $read->fetch(PDO::FETCH_ASSOC);
?>
<div class="content-body">
    <div class="component-section no-code">
        <div class="row row-sm">
            <div class="col-sm-12">
            	<table width="50%">
                	<tr>
                    	<td>Nomor SJ</td>
                    	<td><center>:</center></td>
                    	<td><?php echo($view['kode_tsl']); ?></td>
                    </tr>
                	<tr>
                    	<td>Supplier</td>
                    	<td><center>:</center></td>
                    	<td><?php echo("$view[nama_out]"); ?></td>
                    </tr>
                	<tr>
                    	<td>Tgl. SJ</td>
                    	<td><center>:</center></td>
                    	<td><?php echo($date->tgl_indo($view['tgl_tsl'])); ?></td>
                    </tr>
                </table>
                <div class="clearfix mg-t-15 mg-b-15"></div>
            	<div class="table-responsive">
				<table class="table table-bordered">
                	<thead>
                    	<tr>
                            <th>Product</th>
                            <th>Detail</th>
                            <th><div align="right">Jumlah</div></th>
                            <th>Satuan Qty.</th>
                            <th><div align="right">Harga</div></th>
                            <th><div align="right">Diskon</div></th>
                            <th><div align="right">Total</div></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
						$master	= $conn->prepare("SELECT A.jumlah_tsd, A.harga_tsd, A.diskon_tsd, A.total_tsd, B.kode_pro, B.nama_pro, B.berat_pro, C.nama_kpr, C.satuan_kpr, D.nama_spr FROM transaksi_salesdetail AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro LEFT JOIN kategori_produk AS C ON B.id_kpr=C.id_kpr LEFT JOIN satuan_produk AS D ON B.id_spr=D.id_spr WHERE A.id_tsl=:kode");
						$master->bindParam(':kode', $kode, PDO::PARAM_STR);
						$master->execute();
						while($hasil	= $master->fetch(PDO::FETCH_ASSOC)){
					?>
                    	<tr>
                        	<td><?php echo("$hasil[nama_pro]"); ?></td>
                        	<td><?php echo("$hasil[nama_kpr] ($hasil[berat_pro] $hasil[nama_spr])"); ?></td>
                        	<td><div align="right"><?php echo($data->angka($hasil['jumlah_tsd'])); ?></div></td>
                        	<td><?php echo($hasil['satuan_kpr']); ?></td>
                        	<td><div align="right"><?php echo($data->angka($hasil['harga_tsd'])); ?></div></td>
                        	<td><div align="right"><?php echo($data->angka($hasil['diskon_tsd'])); ?></div></td>
                        	<td><div align="right"><?php echo($data->angka($hasil['total_tsd'])); ?></div></td>
                        </tr>
                  	<?php
                    	}
					?>
                    	<tr>
                        	<td colspan="6"><div align="right"><b>Subtotal</b></div></td>
                        	<td><div align="right"><?php echo($data->angka($view['subtot_tsl'])); ?></div></td>
                        </tr>
                    	<tr>
                        	<td colspan="6"><div align="right"><b>PPN (10%)</b></div></td>
                        	<td><div align="right"><?php echo($data->angka($view['ppn_tsl'])); ?></div></td>
                        </tr>
                    	<tr>
                        	<td colspan="6"><div align="right"><b>Total</b></div></td>
                        	<td><div align="right"><?php echo($data->angka($view['total_tsl'])); ?></div></td>
                        </tr>
                    </tbody>
                </table>
                <div style="font-style:italic;">Ket. -</div>
                </div>
            </div>
        </div>
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <div class="row row-sm">
            <div class="col-sm-12">
                <a href="<?php echo("$sistem/fsales"); ?>" title="Kembai"><button type="button" class="btn btn-secondary btn-xs"><i class="fa fa-chevron-circle-left"></i> Kembali</button></a>
                <a target="_blank" href="<?php echo("$sistem/laporan/xps/sjsales/sjsales.php?key=$kode"); ?>" title="Cetak"><button type="button" class="btn btn-danger btn-xs"><i class="fa fa-print"></i> Cetak</button></a>
            </div>
		</div>
    </div>
</div>