<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Pembelian</a></li>
                <li class="breadcrumb-item active" aria-current="page">Penerimaan Barang</li>
            </ol>
        </nav>
        <h4 class="content-title">Penerimaan Barang</h4>
    </div>
</div>
<?php
	$uniq	= $secu->injection($_GET['keycode']);
	$kode	= base64_decode($uniq);
	$read	= $conn->prepare("SELECT A.id_tre, A.fak_tre, A.tglfak_tre, A.total_tre, A.tgl_tre, A.status_tre, B.ket_tor, B.kode_tor, B.tgl_tor, C.nama_sup FROM transaksi_receive AS A LEFT JOIN transaksi_order AS B ON A.id_tor=B.id_tor LEFT JOIN supplier AS C ON B.id_sup=C.id_sup WHERE A.id_tre=:kode");
	$read->bindParam(':kode', $kode, PDO::PARAM_STR);
	$read->execute();
	$view	= $read->fetch(PDO::FETCH_ASSOC);
?>
<div class="content-body">
    <?php require_once('config/frame/alert.php'); ?>
    <div class="component-section no-code">
	<!--
        <h5 id="section1" class="tx-semibold">Order Produk</h5>
        <p class="mg-b-25">Pilih produk yang akan dipesan kepada supplier.</p>
	-->
        <div class="row row-sm">
            <div class="col-sm-12">
            	<table width="50%">
                	<tr>
                    	<td>Nomor Faktur</td>
                    	<td><center>:</center></td>
                    	<td><?php echo($view['fak_tre']); ?></td>
                    </tr>
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
                    	<td>Keterangan</td>
                    	<td><center>:</center></td>
                    	<td><?php echo($view['ket_tor']); ?></td>
                    </tr>
                	<tr>
                    	<td>Tgl. Faktur</td>
                    	<td><center>:</center></td>
                    	<td><?php echo($date->tgl_indo($view['tglfak_tre'])); ?></td>
                    </tr>
                	<tr>
                    	<td>Tgl. Terima</td>
                    	<td><center>:</center></td>
                    	<td><?php echo($date->tgl_indo($view['tgl_tre'])); ?></td>
                    </tr>
                </table>
                <div class="clearfix mg-t-15 mg-b-15"></div>
				<?php echo(($data->akses($admin, 'rorder', 'A.create_status')==='Active') ? '<a href="#modal1" onclick="crud(\'rorder\', \'inputitem\', \''.$uniq.'\')" data-toggle="modal"><button class="btn btn-success btn-pill btn-xs"><i class="fa fa-plus-circle"></i> Tambah Data</button></a>' : ''); ?>
                <div class="clearfix mg-t-15 mg-b-15"></div>
            	<div class="table-responsive">
				<table class="table table-bordered">
                	<thead>
                    	<tr>
                            <th>Product</th>
                            <th>Detail</th>
                            <th>Gudang</th>
                            <th>Batchcode</th>
                            <th>Tgl. Expired</th>
                            <th><div align="right">Harga</div></th>
                            <th><div align="right">Terima</div></th>
                            <th><div align="right">Diskon</div></th>
                            <th><div align="right">Total</div></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
						$total	= 0;
						$master	= $conn->prepare("SELECT A.id_trd, A.bcode_trd, A.gudang, A.tbcode_trd, A.jumlah_trd, A.harga_trd, A.diskon_trd, A.total_trd, B.nama_pro,  B.kode_pro, B.berat_pro, C.nama_kpr, D.nama_spr FROM transaksi_receivedetail AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro LEFT JOIN kategori_produk AS C ON B.id_kpr=C.id_kpr LEFT JOIN satuan_produk AS D ON B.id_spr=D.id_spr WHERE A.id_tre=:kode");
						$master->bindParam(':kode', $kode, PDO::PARAM_STR);
						$master->execute();
						while($hasil	= $master->fetch(PDO::FETCH_ASSOC)){
								$total	+= $hasil['total_trd'];
					?>
                    	<tr>
                        	<td><?php echo(($data->akses($admin, 'rorder', 'update_status')=='Active') ? '<a href="#modal1" onclick="crud(\'rorder\', \'editdetail\', \''.base64_encode($hasil['id_trd']).'\')" data-toggle="modal">'.$hasil['nama_pro'].' - '.$hasil['kode_pro'].'</a>' : ''); ?></td>
                        	<td><?php echo("$hasil[nama_kpr] ($hasil[berat_pro] $hasil[nama_spr])"); ?></td>
                            <td><?php echo($hasil['gudang']); ?></td>
                        	<td><?php echo($hasil['bcode_trd']); ?></td>
                        	<td><?php echo($hasil['tbcode_trd']); ?></td>
                        	<td><div align="right"><?php echo($data->angka($hasil['harga_trd'])); ?></div></td>
                        	<td><div align="right"><?php echo($data->angka($hasil['jumlah_trd'])); ?></div></td>
                        	<td><div align="right"><?php echo($data->angka($hasil['diskon_trd'])); ?>%</div></td>
                        	<td><div align="right"><?php echo($data->angka($hasil['total_trd'])); ?></div></td>
                        </tr>
                  	<?php
                    	}
						$ppn	= ($total * 11) / 100;
						$gtotal	= $total + $ppn;
					?>
                    	<tr>
                            <td></td>
                        	<td colspan="7"><div align="right"><b>SUBTOTAL</b></div></td>
                        	<td><div align="right"><b><?php echo($data->angka($total)); ?></b></div></td>
                        </tr>
						<tr>
                        <td></td>
                        	<td colspan="7"><div align="right"><b>PPN (11%)</b></div></td>
                        	<td><div align="right"><b><?php echo($data->angka($ppn)); ?></b></div></td>
                        </tr>
						<tr>
                        <td></td>
                        	<td colspan="7"><div align="right"><b>TOTAL</b></div></td>
                        	<td><div align="right"><b><?php echo($data->angka($gtotal)); ?></b></div></td>
                        </tr>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <div class="row row-sm">
            <div class="col-sm-12">
                <a href="<?php echo("$sistem/rorder"); ?>" title="Kembai">
                <button type="button" class="btn btn-secondary btn-xs"><i class="fa fa-chevron-circle-left"></i> Kembali</button>
				</a>
            </div>
		</div>
    </div>
</div>