<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Stock Opname</a></li>
                <li class="breadcrumb-item active" aria-current="page">Meker</li>
            </ol>
        </nav>
        <h4 class="content-title">Selesai Stock Opname</h4>
    </div>
</div>
<div class="content-body">
    <div class="component-section no-code">
        <h5 id="section1" class="tx-semibold">Data Stockopname Yang Suda Selesai </h5>
        <p class="mg-b-25">Apakah Yakin Mau Selesai Stock Opname?</p>
        <p class="mg-b-25">Ketika Sudah di klik tombol selesai stock opname, akan menyelesaikan proses stock opname</p>

        <?php echo(($data->akses($admin, $menu, 'A.create_status')==='Active') ? '<a href="'.$sistem.'/stockopnameap/v"><button class="btn btn-primary btn-pill btn-xs"><i class="fa fa-plus-circle"></i> Rekap Hasil Inventory Sebelum </button></a>' : ''); ?>

        <form id="formtransaksi" action="#" method="post"  enctype="multipart/form-data" autocomplete="off">
        <input type="hidden" name="nmenu" id="nmenu" value="stockopnamein" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="input" readonly="readonly" />
       
        <div class="row row-sm">
            <div class="col-sm-12">
            	<div class="table-responsive" >
				<table class="tabel">
                	<thead>
                        <tr>
                              <th colspan="3"><center>STOK INVENTORY</center></th>
                              <th colspan="1"><center>STOCK OPNAME</center></th>
                        </tr>
                        <tr>
                            <th><center>Id Produk</center></th>

                            <th><center>Nama Produk</center></th>
                            <th><center>QTY</center></th>
                            <th><center>QTY</center></th>
                            <th><center>Selisih</center></th>
                            
                    </tr>
                    </thead>
                    <tbody>
                    <?php
						$active	= 'Active';
                        $master	= $conn->prepare("SELECT SUM(A.sisa_psd) AS total, SUM(A.qty_so) AS totall, A.id_psd, A.no_bcode, A.id_pro, A.status_barang,A.status, A.tgl_expired, A.gudang,A.status, A.tgl_psd, A.qty_so, A.sisa_psd,A.status, B.nama_pro, B.berat_pro,B.minstok_pro, C.harga_phg, C.hargap_phg, E.nama_spr FROM produk_stokdetail AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro LEFT JOIN produk_harga AS C ON B.id_pro=C.id_pro LEFT JOIN kategori_produk AS D ON B.id_kpr=D.id_kpr LEFT JOIN satuan_produk AS E ON B.id_spr=E.id_spr WHERE A.status_barang=:active AND C.status_phg=:active  GROUP BY B.nama_pro ASC");
                        $master->bindParam(':active', $active, PDO::PARAM_STR);
                        $master->execute();
                        while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
                            $a= $hasil['total'];
                            $b= $hasil['totall'];
                            $selisih = ($b - $a);
                        	$nomor	= $secu->injection(@$_POST['n']);
                            
					?>
                    	<tr id="<?php echo("id_pro$nomor"); ?>">
                            
                            <td>
                                <input type="text" name="id_pro[]" id="id_pro[]" class="form-control" value="<?php echo($hasil['id_pro']); ?> " readonly="readonly"/>
                            </td>
                            <td>
                                <input type="text" name="nama_pro[]" id="nama_pro[]" class="form-control" value="<?php echo($hasil['nama_pro']); ?> " readonly="readonly"/>
                            </td>
                          
                            <td>
                                <input type="text" name="qty[]" id="qty[]" class="form-control" value="<?php echo($hasil['total']); ?>" readonly="readonly"/>
                            </td>
                            
                            <td>
                                <input type="text" name="qty_so[]" id="qty_so[]" class="form-control" value="<?php echo($hasil['totall']); ?>" readonly="readonly"/>
                            </td>

                            <td>
                                <input type="text" name="selisih[]" id="selisih[]" class="form-control" value="<?php echo($selisih); ?>" readonly="readonly"/>
                            </td>   
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
                <div id="imgloading"></div>
                </div>
            </div>
        </div>
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <div class="row row-sm">
            <div class="col-sm-12">
                <a href="<?php echo("$sistem/stockopnameap"); ?>" title="Batal"><button type="button" class="btn btn-danger  btn-xs">Revisi</button></a>
                <button type="submit" id="bsave" class="btn btn-success btn-xs">Selesai Stock Opname</button>
               
         
            </div>
		</div>
		</form>
    </div>
</div>