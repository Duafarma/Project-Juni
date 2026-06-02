<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Konsinyasi</a></li>
                <li class="breadcrumb-item active" aria-current="page">Faktur Konsinyasi</li>
            </ol>
        </nav>
        <h4 class="content-title">Detail Data - Faktur Konsinyasi</h4>
    </div>
</div>
<?php
	$kode	= base64_decode($secu->injection($_GET['keycode']));
	$read	= $conn->prepare("SELECT A.kode_tfk, A.sj_tfk, A.tglsj_tfk, A.po_tfk, A.tglpo_tfk, A.subtot_tfk, A.ppn_tfk, A.total_tfk, A.tgl_tfk, A.status_tfk, B.nama_out FROM transaksi_faktur_konsinyasi AS A LEFT JOIN outlet AS B ON A.id_out=B.id_out WHERE A.id_tfk=:kode");
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
                    	<td>Nomor Faktur</td>
                    	<td><center>:</center></td>
                    	<td><?php echo($view['kode_tfk']); ?></td>
                    </tr>
                	<tr>
                    	<td>Outlet</td>
                    	<td><center>:</center></td>
                    	<td><?php echo("$view[nama_out]"); ?></td>
                    </tr>
                	<tr>
                    	<td>Tgl. Faktur</td>
                    	<td><center>:</center></td>
                    	<td><?php echo($date->tgl_indo($view['tgl_tfk'])); ?></td>
                    </tr>
                	<tr>
                    	<td>Nomor SJ</td>
                    	<td><center>:</center></td>
                    	<td><?php echo($view['sj_tfk']); ?></td>
                    </tr>
                	<tr>
                    	<td>Tgl. SJ</td>
                    	<td><center>:</center></td>
                    	<td><?php echo($date->tgl_indo($view['tglsj_tfk'])); ?></td>
                    </tr>
                	<tr>
                    	<td>Nomor PO</td>
                    	<td><center>:</center></td>
                    	<td><?php echo($view['po_tfk']); ?></td>
                    </tr>
                	<tr>
                    	<td>Tgl. PO</td>
                    	<td><center>:</center></td>
                    	<td><?php echo($date->tgl_indo($view['tglpo_tfk'])); ?></td>
                    </tr>
                	<tr>
                    	<td>Status</td>
                    	<td><center>:</center></td>
                    	<td><span class="badge badge-info"><?php echo($view['status_tfk']); ?></span></td>
                    </tr>
                </table>
                <div class="clearfix mg-t-15 mg-b-15"></div>
            	<div class="table-responsive">
				<table class="table table-bordered">
                	<thead>
                    	<tr>
                            <th>Product</th>
                            <th>Detail</th>
                            <th>Batchcode</th>
                            <th><div align="right">Jumlah</div></th>
                            <th><div align="right">Terjual</div></th>
                            <th><div align="right">Sisa</div></th>
                            <th>Satuan Qty.</th>
                            <th><div align="right">Harga</div></th>
                            <th><div align="right">Diskon</div></th>
                            <th><div align="right">Total</div></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
						$master	= $conn->prepare("SELECT A.jumlah_tfd, A.terjual_tfd, A.sisa_tfd, A.harga_tfd, A.diskon_tfd, A.total_tfd, B.kode_pro, B.nama_pro, B.berat_pro, C.nama_kpr, C.satuan_kpr, D.nama_spr, E.no_bcode FROM transaksi_fakturdetail_konsinyasi AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro LEFT JOIN kategori_produk AS C ON B.id_kpr=C.id_kpr LEFT JOIN satuan_produk AS D ON B.id_spr=D.id_spr LEFT JOIN produk_stokdetail AS E ON A.id_psd=E.id_psd WHERE A.id_tfk=:kode");
						$master->bindParam(':kode', $kode, PDO::PARAM_STR);
						$master->execute();
						while($hasil	= $master->fetch(PDO::FETCH_ASSOC)){
					?>
                    	<tr>
                        	<td><?php echo("$hasil[nama_pro]"); ?></td>
                        	<td><?php echo("$hasil[nama_kpr] ($hasil[berat_pro] $hasil[nama_spr])"); ?></td>
                        	<td><?php echo($hasil['no_bcode']); ?></td>
                        	<td><div align="right"><?php echo($data->angka($hasil['jumlah_tfd'])); ?></div></td>
                        	<td><div align="right">
                        		<span class="badge badge-success"><?php echo($data->angka($hasil['terjual_tfd'])); ?></span>
                        	</div></td>
                        	<td><div align="right">
                        		<span class="badge badge-<?php echo($hasil['sisa_tfd'] > 0 ? 'primary' : 'secondary'); ?>">
                        			<?php echo($data->angka($hasil['sisa_tfd'])); ?>
                        		</span>
                        	</div></td>
                        	<td><?php echo($hasil['satuan_kpr']); ?></td>
                        	<td><div align="right"><?php echo($data->angka($hasil['harga_tfd'])); ?></div></td>
                        	<td><div align="right"><?php echo($data->angka($hasil['diskon_tfd'])); ?></div></td>
                        	<td><div align="right"><?php echo($data->angka($hasil['total_tfd'])); ?></div></td>
                        </tr>
                  	<?php
                    	}
					?>
                    	<tr>
                        	<td colspan="9"><div align="right"><b>Subtotal</b></div></td>
                        	<td><div align="right"><?php echo($data->angka($view['subtot_tfk'])); ?></div></td>
                        </tr>
                    	<tr>
                        	<td colspan="9"><div align="right"><b>PPN (11%)</b></div></td>
                        	<td><div align="right"><?php echo($data->angka($view['ppn_tfk'])); ?></div></td>
                        </tr>
                    	<tr>
                        	<td colspan="9"><div align="right"><b>Total</b></div></td>
                        	<td><div align="right"><?php echo($data->angka($view['total_tfk'])); ?></div></td>
                        </tr>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <div class="row row-sm">
            <div class="col-sm-12">
                <a href="<?php echo("$sistem/fsalesk"); ?>" title="Kembali"><button type="button" class="btn btn-secondary btn-xs"><i class="fa fa-chevron-circle-left"></i> Kembali</button></a>
                <a target="_blank" href="<?php echo("$sistem/laporan/pdf_fsalesk.php?keycode=".base64_encode($kode)); ?>" title="Print PDF"><button type="button" class="btn btn-danger btn-xs"><i class="fa fa-file-pdf-o"></i> Print PDF</button></a>
                <a target="_blank" href="<?php echo("$sistem/laporan/xps/faktursales/faktursalesk.php?key=$kode"); ?>" title="Cetak"><button type="button" class="btn btn-danger btn-xs"><i class="fa fa-print"></i> Cetak</button></a>
            </div>
		</div>
    </div>
</div>
