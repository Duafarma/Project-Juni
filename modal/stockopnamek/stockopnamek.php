<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$conn	= $base->open();
	$sistem	= $data->sistem('url_sis');
	$modal	= $secu->injection(@$_GET['modal']);
	switch($modal){
		case "faktur":
		$kode	= $secu->injection($_GET['keycode']);

?>
        <link rel="stylesheet" href="<?php echo("$sistem/sumoselect/sumoselect.min.css"); ?>" type="text/css" />
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Pilih Faktur </h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
       <form id="formtransaksiif" action="#" method="post" autocomplete="off">
            <input type="hidden" name="namamodal" id="namamodal" value="pooutleta" readonly="readonly" />
            <input type="hidden" name="nmenu" id="nmenu" value="pooutleta" readonly="readonly" />
            <input type="hidden" name="namamenu" value="<?php echo($modal); ?>" readonly="readonly" />
            <input type="hidden" name="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
	            <div class="form-group col-md-12">
                    <div class="table-responsive">
                    <table class="table table-bordered">
                         <ul>
                            <li><a href="https://imsduafarma.link/192.268.908.10/fsales/i"><button type="submit" class="btn btn-primary btn-pill btn-xs"> <i class="fa-solid fa-file-code"></i> Faktur B</button></a></li>            
                        </ul>
            
                        <!--<ul>-->
                        <!--    <li><a href="https://imsduafarma.link/192.268.908.10/fsales/i"><button class="btn btn-secondary btn-pill btn-xs"> <i class="fa-solid fa-file-code"></i> Faktur B</button></a></li>            -->
                        <!--</ul>-->
            
                      
            
                        <!--<ul>-->
                        <!--    <li><a href="<?php echo($data->sistem('url_sis').'/fsalesd'); ?>"><button class="btn btn-danger btn-pill btn-xs"><i class="fa-solid fa-file-code"></i> Faktur CBN</button></a></li>-->
                        <!--</ul>-->

                       
					</table>
                    </div>
                </div>
			</div>
        </div>
        </form>
        <form id="formtransaksiifi" action="#" method="post" autocomplete="off">
            <input type="hidden" name="namamodal" id="namamodal" value="pooutleta" readonly="readonly" />
            <input type="hidden" name="nmenu" id="nmenu" value="pooutleta" readonly="readonly" />
            <input type="hidden" name="namamenu" value="<?php echo($modal); ?>" readonly="readonly" />
            <input type="hidden" name="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
	            <div class="form-group col-md-12">
                    <div class="table-responsive">
                    <table class="table table-bordered">
                         
            
                        <!--<ul>-->
                        <!--    <li><a href="https://imsduafarma.link/192.268.908.10/fsales/i"><button class="btn btn-secondary btn-pill btn-xs"> <i class="fa-solid fa-file-code"></i> Faktur B</button></a></li>            -->
                        <!--</ul>-->
            
                        <ul>
                            <li><a href="https://imsduafarma.link/192.268.908.11/fsales/i"><button class="btn btn-success btn-pill btn-xs"><i class="fa-solid fa-file-code"></i> Faktur C dan D</button></a></li>
                        </ul>
            
                        <!--<ul>-->
                        <!--    <li><a href="<?php echo($data->sistem('url_sis').'/fsalesd'); ?>"><button class="btn btn-danger btn-pill btn-xs"><i class="fa-solid fa-file-code"></i> Faktur CBN</button></a></li>-->
                        <!--</ul>-->

                       
					</table>
                    </div>
                </div>
			</div>
        </div>
        </form>
        
<?php
		break;
		case "updateStatusPersetujuan":
          $kode	= $secu->injection($_GET['keycode']);
	
?>
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Update Data </h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formtransaksii" action="#" method="post" autocomplete="off" enctype="multipart/form-data">
        <input type="hidden" name="nmenu" id="nmenu" value="stockopnamek" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="stockopnamek" readonly="readonly" />
        <input type="hidden" name="namamenu" value="<?php echo($modal); ?>" readonly="readonly" />
        <input type="hidden" name="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
               <div class="form-group col-md-12">
                        <h6>Apakah Anda Yakin Minta Persetujuan Bapak</h6>
                        <!-- <h6>Nama  : <?php echo($view['gudang']); ?></h6> -->
                        <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th><center>Nama Barang</center></th>
                                    <th><center>Nomor Batch</center></th>
                                    <th><center>Qty Sistem</center></th>
                                    <th><center>Qty Hasil SO</center></th>
                                    
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                               $no		= 0;
    							$kode	= $secu->injection($_GET['keycode']);
    							$master	= $conn->prepare("SELECT A.*, A.no_bcode,A.id_psd, A.tgl_expired,A.gudang, A.qty_so,A.id_pro, A.tgl_psd, A.sisa_psd,B.kategori_obat,B.kode_produk_jadi, B.nama_pro, B.berat_pro,B.minstok_pro, C.harga_phg, C.hargap_phg, D.nama_kpr, E.nama_spr FROM produk_stokdetail AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro LEFT JOIN produk_harga AS C ON B.id_pro=C.id_pro LEFT JOIN kategori_produk AS D ON B.id_kpr=D.id_kpr LEFT JOIN satuan_produk AS E ON B.id_spr=E.id_spr WHERE A.id_psd=:kode ");			
    							$master->bindParam(':kode', $kode, PDO::PARAM_STR);
    							$master->execute();
    							$views	= $master->fetch(PDO::FETCH_ASSOC);
    
                            ?>
                                <tr>
                                    <td><center><?php echo(	$views['nama_pro']); ?></center></td>
                                    <td><center><?php echo(	$views['no_bcode']); ?></center></td>
                                    <td><center><?php echo(	$views['sisa_psd']); ?></center></td>
                                    <td><center><?php echo(	$views['qty_so']); ?></center></td>
    
    
                                </tr>
                            <?php $no++;  ?>
                            </tbody>
                                </table>
                        </div>
                        
    
                    </div>
            <div class="modal-footer">
                <!--<button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Print</button>-->
                <!-- <button type="submit" id="bsave" class="btn btn-danger btn-xs">Tidak</button> -->
                <button type="button" class="btn btn-danger btn-xs" data-dismiss="modal">Tidak</button>
    
                <button type="submit" id="bsave" class="btn btn-success btn-xs">Ya</button>
            </div>
        </div>
		</form>
<?php
		break;
		case "updateStatusRevisi":
            $kode	= $secu->injection($_GET['keycode']);
            
    ?>		
    
    <div class="modal-header">
                <h6 class="modal-title" id="exampleModalLabel">Data Stockopname</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
                </button>
            </div>
            <form id="formtransaksi" action="#" method="post" autocomplete="off">
                <input type="hidden" name="namamodal" id="namamodal" value="stockopnamek" readonly="readonly" />
                <input type="hidden" name="nmenu" id="nmenu" value="stockopnamek" readonly="readonly" />
                <input type="hidden" name="namamenu" value="<?php echo($modal); ?>" readonly="readonly" />
                <input type="hidden" name="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
            <div class="modal-body">
                <div class="row">
                    <div class="form-group col-md-12">
                        <h6>Apakah Anda Yakin Hasil SO Sudah Benar?</h6>
                        <!-- <h6>Nama  : <?php echo($view['gudang']); ?></h6> -->
                        <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th><center>Nama Barang</center></th>
                                    <th><center>Nomor Batch</center></th>
                                    <th><center>Qty Sistem</center></th>
                                    <th><center>Qty Hasil SO</center></th>
                                    
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                               $no		= 0;
    							$kode	= $secu->injection($_GET['keycode']);
    							$master	= $conn->prepare("SELECT A.*, A.no_bcode,A.id_psd, A.tgl_expired,A.gudang, A.qty_so,A.id_pro, A.tgl_psd, A.sisa_psd,B.kategori_obat,B.kode_produk_jadi, B.nama_pro, B.berat_pro,B.minstok_pro, C.harga_phg, C.hargap_phg, D.nama_kpr, E.nama_spr FROM produk_stokdetail AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro LEFT JOIN produk_harga AS C ON B.id_pro=C.id_pro LEFT JOIN kategori_produk AS D ON B.id_kpr=D.id_kpr LEFT JOIN satuan_produk AS E ON B.id_spr=E.id_spr WHERE A.id_psd=:kode ");			
    							$master->bindParam(':kode', $kode, PDO::PARAM_STR);
    							$master->execute();
    							$views	= $master->fetch(PDO::FETCH_ASSOC);
    
                            ?>
                                <tr>
                                    <td><center><?php echo(	$views['nama_pro']); ?></center></td>
                                    <td><center><?php echo(	$views['no_bcode']); ?></center></td>
                                    <td><center><?php echo(	$views['sisa_psd']); ?></center></td>
                                    <td><center><?php echo(	$views['qty_so']); ?></center></td>
    
    
                                </tr>
                            <?php $no++;  ?>
                            </tbody>
                                </table>
                        </div>
                        <!-- <i>Keterangan : <?php echo($view['keterangan']); ?></i> -->
    
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <!--<button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Print</button>-->
                <!-- <button type="submit" id="bsave" class="btn btn-danger btn-xs">Tidak</button> -->
                <button type="button" class="btn btn-danger btn-xs" data-dismiss="modal">Tidak</button>
    
                <button type="submit" id="bsave" class="btn btn-success btn-xs">Ya</button>
            </div>
            </form>
            
    <?php
            break;
		case "updateStatusCancel":
		$kode	= $secu->injection($_GET['keycode']);
		
?>		

<div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Data Stockopname</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formtransaksi" action="#" method="post" autocomplete="off">
            <input type="hidden" name="namamodal" id="namamodal" value="stockopnamek" readonly="readonly" />
            <input type="hidden" name="nmenu" id="nmenu" value="stockopnamek" readonly="readonly" />
            <input type="hidden" name="namamenu" value="<?php echo($modal); ?>" readonly="readonly" />
            <input type="hidden" name="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
	            <div class="form-group col-md-12">
                	<h6>Apakah Anda Yakin Akan Melakukan Revisi?</h6>
                	<!-- <h6>Nama  : <?php echo($view['gudang']); ?></h6> -->
                    <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th><center>Nama Barang</center></th>
                                <th><center>Nomor Batch</center></th>
                                <th><center>Qty Sistem</center></th>
                                <th><center>Qty Hasil SO</center></th>
                                
                            </tr>
						</thead>
                        <tbody>
                        <?php
							$no		= 0;
							$kode	= $secu->injection($_GET['keycode']);
							$master	= $conn->prepare("SELECT A.*, A.no_bcode,A.id_psd, A.tgl_expired,A.gudang, A.qty_so,A.id_pro, A.tgl_psd, A.sisa_psd,B.kategori_obat,B.kode_produk_jadi, B.nama_pro, B.berat_pro,B.minstok_pro, C.harga_phg, C.hargap_phg, D.nama_kpr, E.nama_spr FROM produk_stokdetail AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro LEFT JOIN produk_harga AS C ON B.id_pro=C.id_pro LEFT JOIN kategori_produk AS D ON B.id_kpr=D.id_kpr LEFT JOIN satuan_produk AS E ON B.id_spr=E.id_spr WHERE A.id_psd=:kode ");			
							$master->bindParam(':kode', $kode, PDO::PARAM_STR);
							$master->execute();
							$views	= $master->fetch(PDO::FETCH_ASSOC);

						?>
                        	<tr>
                            	<td><center><?php echo($views['nama_pro']); ?></center></td>
                                <td><center><?php echo($views['no_bcode']); ?></center></td>
                                <td><center><?php echo($views['sisa_psd']); ?></center></td>
                                <td><center><?php echo($views['qty_so']); ?></center></td>


                            </tr>
						<?php $no++;  ?>
                        </tbody>
                            </table>
                    </div>
                    <!-- <i>Keterangan : <?php echo($view['keterangan']); ?></i> -->

                </div>
			</div>
        </div>
        <div class="modal-footer">
            <!--<button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Print</button>-->
            <!-- <button type="submit" id="bsave" class="btn btn-danger btn-xs">Tidak</button> -->
            <button type="button" class="btn btn-danger btn-xs" data-dismiss="modal">Tidak</button>

            <button type="submit" id="bsave" class="btn btn-success btn-xs">Ya</button>
        </div>
        </form>
        
<?php
		break;
	}
	$conn	= $base->close();
?>
	<script type="text/javascript" src="<?php echo("$sistem/sumoselect/jquery.sumoselect.min.js"); ?>"></script>
	<script type="text/javascript" src="<?php echo($data->sistem('url_sis').'/config/js/fazlurr.js'); ?>"></script>
	<script type="text/javascript" src="<?php echo("$sistem/sumoselect/jquery.sumoselect.min.js"); ?>"></script>
	<script type="text/javascript">
	$('.sumoselect').SumoSelect({
		csvDispCount: 3,
		search: true,
		searchText:'Enter here.'
	});
    </script>