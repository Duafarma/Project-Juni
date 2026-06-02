<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Pembelian</a></li>
                <li class="breadcrumb-item active" aria-current="page">Barang Retur</li>
            </ol>
        </nav>
        <h4 class="content-title">Detail Data - Barang Retur</h4>
    </div>
</div>
<?php
	$kode	= $secu->injection($_GET['keycode']);
	$read	= $conn->prepare("SELECT A.no_retur, A.keterangan, A.tanggal, B.nama_out FROM retur AS A LEFT JOIN outlet AS B ON A.id_out=B.id_out WHERE A.id_r=:kode");
	$read->bindParam(':kode', $kode, PDO::PARAM_STR);
	$read->execute();
	$view	= $read->fetch(PDO::FETCH_ASSOC);
?>
<div class="content-body">
    <div class="component-section no-code">
		<div class="row row-sm">
			<div class="col-sm-4">
				<a href=""><button class="btn btn-success btn-pill btn-xs"><i class="fa fa-spinner"></i> Refresh</button></a>
			</div>
			<div class="col-sm-8">
            	<table width="50%">
                	<tr>
                    	<td>Nomor Retur</td>
                    	<td><center>:</center></td>
                    	<td><?php echo($view['no_retur']); ?></td>
                    </tr>
                	<tr>
                    	<td>Nama Outlet</td>
                    	<td><center>:</center></td>
                    	<td><?php echo("$view[nama_out]"); ?></td>
                    </tr>
                	<tr>
                    	<td>Tgl. Retur</td>
                    	<td><center>:</center></td>
                    	<td><?php echo($date->tgl_indo($view['tanggal'])); ?></td>
                    </tr>
                </table>
			</div>
		</div>
        <div class="row row-sm">
            <div class="col-sm-12">
                <div class="clearfix mg-t-15 mg-b-15"></div>
            	<div class="table-responsive">
				<table class="table table-bordered">
                	<thead>
                    	<tr>
                            <th>Product</th>
                            <th>Detail</th>
                             <th>Batch</th>
                            <th>ed</th>
                            <th><div align="right">Jumlah</div></th>
                            <th>Satuan Qty.</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
						$master	= $conn->prepare("SELECT A.id_r_d,A.ed,A.no_bcode, A.jumlah, B.kode_pro, B.nama_pro, B.berat_pro, C.nama_kpr, C.satuan_kpr, D.nama_spr FROM retur_detail AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro LEFT JOIN kategori_produk AS C ON B.id_kpr=C.id_kpr LEFT JOIN satuan_produk AS D ON B.id_spr=D.id_spr WHERE A.id_r=:kode");
						$master->bindParam(':kode', $kode, PDO::PARAM_STR);
						$master->execute();
						while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
							$uniq	= base64_encode($hasil['id_r_d']);
					?>
                    	<tr>
                        	<td><?php echo("$hasil[nama_pro]"); ?></td>
                        	<td><?php echo("$hasil[nama_kpr] ($hasil[berat_pro] $hasil[nama_spr])"); ?></td>
                        	<td><?php echo("$hasil[no_bcode]"); ?></td>
                        	<td><?php echo("$hasil[ed]"); ?></td>
                        	<td><div align="right"><?php echo($data->angka($hasil['jumlah'])); ?></div></td>
                        	<td><?php echo($hasil['satuan_kpr']); ?></td>
                        </tr>
                  	<?php
                    	}
					?>
                    </tbody>
                </table>
                <div style="font-style:italic;">Ket. <?php echo($view['keterangan']); ?></div>
                </div>
            </div>
        </div>
		<?php require_once('config/frame/alert.php'); ?>
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <div class="row row-sm">
            <div class="col-sm-12">
                <a href="<?php echo("$sistem/retur"); ?>" title="Kembai"><button type="button" class="btn btn-secondary btn-xs"><i class="fa fa-chevron-circle-left"></i> Kembali</button></a>
                <a target="_blank" href="<?php echo("$sistem/laporan/xps/retur/retur.php?key=$kode"); ?>" title="Cetak"><button type="button" class="btn btn-danger btn-xs"><i class="fa fa-print"></i> Cetak</button></a>
            </div>
		</div>
    </div>
</div>