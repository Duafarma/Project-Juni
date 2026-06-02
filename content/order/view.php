<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Pembelian</a></li>
                <li class="breadcrumb-item active" aria-current="page">Order</li>
            </ol>
        </nav>
        <h4 class="content-title">Detail Data - Order</h4>
    </div>
</div>
<?php
	$kode	= $secu->injection($_GET['keycode']);
	$read	= $conn->prepare("SELECT A.kode_tor, A.ket_tor, A.tgl_tor, B.nama_sup FROM transaksi_order AS A LEFT JOIN supplier AS B ON A.id_sup=B.id_sup WHERE A.id_tor=:kode");
	$read->bindParam(':kode', $kode, PDO::PARAM_STR);
	$read->execute();
	$view	= $read->fetch(PDO::FETCH_ASSOC);
?>
<div class="content-body">
    <div class="component-section no-code">
		<div class="row row-sm">
			<div class="col-sm-4">
				<?php echo(($data->akses($admin, 'order', 'A.create_status')==='Active') ? '<a href="#modal1" onclick="crud(\'orderdetail\', \'input\', \''.base64_encode($kode).'\')" data-toggle="modal"><button class="btn btn-primary btn-pill btn-xs"><i class="fa fa-plus-circle"></i> Tambah Data</button></a>' : ''); ?>
				<a href=""><button class="btn btn-success btn-pill btn-xs"><i class="fa fa-spinner"></i> Refresh</button></a>
			</div>
			<div class="col-sm-8">
            	<table width="50%">
                	<tr>
                    	<td>Nomor PO</td>
                    	<td><center>:</center></td>
                    	<td><?php echo($view['kode_tor']); ?></td>
                    </tr>
                	<tr>
                    	<td>Supplier</td>
                    	<td><center>:</center></td>
                    	<td><?php echo("$view[nama_sup]"); ?></td>
                    </tr>
                	<tr>
                    	<td>Tgl. PO</td>
                    	<td><center>:</center></td>
                    	<td><?php echo($date->tgl_indo($view['tgl_tor'])); ?></td>
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
                            <th><div align="right">Jumlah</div></th>
                            <th>Satuan Qty.</th>
                            <th><center>#</center></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
						$master	= $conn->prepare("SELECT A.id_tod, A.jumlah_tod, B.kode_pro, B.nama_pro, B.berat_pro, C.nama_kpr, C.satuan_kpr, D.nama_spr FROM transaksi_orderdetail AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro LEFT JOIN kategori_produk AS C ON B.id_kpr=C.id_kpr LEFT JOIN satuan_produk AS D ON B.id_spr=D.id_spr WHERE A.id_tor=:kode");
						$master->bindParam(':kode', $kode, PDO::PARAM_STR);
						$master->execute();
						while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
							$uniq	= base64_encode($hasil['id_tod']);
							$edit	= ($data->akses($admin, 'order', 'A.update_status')==='Active') ? '<a href="#modal1" onclick="crud(\'orderdetail\', \'update\', \''.$uniq.'\')" data-toggle="modal"><span class="badge badge-info"><i class="fa fa-edit"></i></span></a>' : '';
							$delete	= ($data->akses($admin, 'order', 'A.delete_status')==='Active') ? ' <a href="#modal1" onclick="crud(\'orderdetail\', \'delete\', \''.$uniq.'\')" data-toggle="modal"><span class="badge badge-danger"><i class="fa fa-trash"></i></span></a>' : '';
					?>
                    	<tr>
                        	<td><?php echo("$hasil[nama_pro]"); ?></td>
                        	<td><?php echo("$hasil[nama_kpr] ($hasil[berat_pro] $hasil[nama_spr])"); ?></td>
                        	<td><div align="right"><?php echo($data->angka($hasil['jumlah_tod'])); ?></div></td>
                        	<td><?php echo($hasil['satuan_kpr']); ?></td>
							<td><center><?php echo($edit.$delete); ?></center></td>
                        </tr>
                  	<?php
                    	}
					?>
                    </tbody>
                </table>
                <div style="font-style:italic;">Ket. <?php echo($view['ket_tor']); ?></div>
                </div>
            </div>
        </div>
		<?php require_once('config/frame/alert.php'); ?>
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <div class="row row-sm">
            <div class="col-sm-12">
                <a href="<?php echo("$sistem/order"); ?>" title="Kembai"><button type="button" class="btn btn-secondary btn-xs"><i class="fa fa-chevron-circle-left"></i> Kembali</button></a>
                <a target="_blank" href="<?php echo("$sistem/laporan/xps/order/order.php?key=$kode"); ?>" title="Cetak"><button type="button" class="btn btn-danger btn-xs"><i class="fa fa-print"></i> Cetak</button></a>
            </div>
		</div>
    </div>
</div>