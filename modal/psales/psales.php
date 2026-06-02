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
		case "input":
?>
        <link rel="stylesheet" href="<?php echo("$sistem/sumoselect/sumoselect.min.css"); ?>" type="text/css" />
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Input Data - Pembayaran</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formtransaksi" action="#" method="post" autocomplete="off" enctype="multipart/form-data">
        <input type="hidden" name="nmenu" id="nmenu" value="psales" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="input" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-12">
                	<table width="100%">
                    	<tr>
                        	<td width="35%"><label>Nomor Faktur <span class="tx-danger">*</span></label></td>
                        	<td width="5%"></td>
                        	<td width="60%">
                            <select name="kodesales" id="kodesales" class="form-control sumoselect" onchange="carisales()" required="required">
                                <option value="">-- Select Nomor Faktur --</option>
                            <?php
                                $lunas	    = 'Lunas';
                                $Inactive	= 'Inactive';
                                $qmaster = "SELECT A.id_tfk, A.kode_tfk, B.nama_out FROM transaksi_faktur AS A LEFT JOIN outlet AS B ON A.id_out=B.id_out WHERE A.status_tfk!=:lunas AND A.status_tfk!=:Inactive ORDER BY A.tgl_tfk ASC";
                                $master	= $conn->prepare($qmaster);
                                $master->bindParam(':lunas', $lunas, PDO::PARAM_STR);
                                $master->bindParam(':Inactive', $Inactive, PDO::PARAM_STR);
                                $master->execute();
                                while($hasil	= $master->fetch(PDO::FETCH_ASSOC)){
                            ?>
                                <option value="<?php echo($hasil['id_tfk']); ?>"><?php echo("$hasil[kode_tfk] ($hasil[nama_out])"); ?></option>
                            <?php } ?>
                            </select>
                            </td>
                        </tr>
                        <tr>
                        	<td><label>Outlet <span class="tx-danger">*</span></label></td>
                        	<td></td>
                        	<td><input type="text" name="namaoutlet" id="namaoutlet" class="form-control" style="background:#FFFFFF;" placeholder="-" readonly="readonly" /></td>
                        </tr>
                        <tr>
                        	<td><label>Tgl. Faktur <span class="tx-danger">*</span></label></td>
                        	<td></td>
                        	<td><input type="text" name="tglfaktur" id="tglfaktur" class="form-control" style="background:#FFFFFF;" placeholder="-" readonly="readonly" /></td>
                        </tr>
                        <tr>
                        	<td><label>Jatuh Tempo Faktur <span class="tx-danger">*</span></label></td>
                        	<td></td>
                        	<td><input type="text" name="tgltempo" id="tgltempo" class="form-control" style="background:#FFFFFF;" placeholder="-" readonly="readonly" /></td>
                        </tr>
                    </table>
                </div>
			</div>
            <div class="row">
                <div class="form-group col-md-4">
                    <label>Total Tagihan <span class="tx-danger">*</span></label>
                    <input type="text" name="tagihan" id="tagihan" class="form-control" style="background:#FFFFFF;" placeholder="0" readonly="readonly" required="required" />
                </div>
                <div class="form-group col-md-4">
                    <label>Dibayar <span class="tx-danger">*</span></label>
                    <input type="text" name="dibayar" id="dibayar" class="form-control" style="background:#FFFFFF;" placeholder="0" readonly="readonly" required="required" />
                </div>
                <div class="form-group col-md-4">
                    <label>Sisa <span class="tx-danger">*</span></label>
                    <input type="text" name="sisa" id="sisa" class="form-control" style="background:#FFFFFF;" placeholder="0" readonly="readonly" required="required" />
                </div>
			</div>
            
            <div class="row">
                <div class="form-group col-md-6">
                <label>Pembayaran Via <span class="tx-danger"></span></label>
                <select  name="bank" id="bank" class="form-control sumoselect">

                      <option value="transfer">Transfer</option>
                      <option value="giro">Giro / Cek</option>
                      <option value="cash">Cash</option>
                      <option value="dll">Danlainnya</option>
                        
                 </select>
                </div>

                <div class="form-group col-md-6">
                    <label>Jumlah <span class="tx-danger">*</span></label>
                    <input type="text" name="bayar" id="bayar" class="form-control" onkeyup="angka(this)" onchange="jumlahbayar()" placeholder="0" required="required" />
                </div>
                <!-- <div class="form-group col-md-6">
                    <label>Nomor Rekening <span class="tx-danger"></span></label>
                    <input type="text" name="norek" class="form-control" placeholder="ex. 022939393" />
                </div> -->
			</div>
            <div class="row">
                <!-- <div class="form-group col-md-6">
                    <label>Atas Nama<span class="tx-danger"></span></label>
                    <input type="text" name="nama" class="form-control" placeholder="Type here..." />
                </div> -->
                
			</div>
            <div class="row">
                <div class="form-group col-md-6">
                    <label>Tanggal<span class="tx-danger">*</span></label>
                    <input type="text" name="tanggal" class="form-control fortgl" value="<?php echo(date('Y-m-d')); ?>" placeholder="9999-99-99" required="required" />
                    <div id="imgloading"></div>
                </div>
                <div class="form-group col-md-6">
                    <label>Bukti<span class="tx-danger"></span></label>
					<div>
                        <button type="button" class="btn btn-primary" id="tombol1" onclick="namafile('tombol1', 'foto1')"><i class="fa fa-cloud-upload"></i> Upload</button>
                        <input type="file" id="foto1" name="foto1" hidden="hidden" />
					</div>
                </div>
			</div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Batal</button>
            <button type="submit" id="bsave" class="btn btn-dark btn-xs">Simpan</button>
        </div>
		</form>
<?php
		break;
		case "update":
		$kode	= $secu->injection($_GET['keycode']);
		$read	= $conn->prepare("SELECT A.id_tfk, A.bank_pfk, A.norek_pfk, A.anam_pfk, A.jumlah_pfk, A.tgl_pfk, B.status_tfk, B.total_tfk, B.kode_tfk, C.nama_out FROM pembayaran_faktur AS A LEFT JOIN transaksi_faktur AS B ON A.id_tfk=B.id_tfk LEFT JOIN outlet AS C ON B.id_out=C.id_out WHERE A.id_pfk=:kode");
		$read->bindParam(':kode', $kode, PDO::PARAM_STR);
		$read->execute();
		$view	= $read->fetch(PDO::FETCH_ASSOC);

		$mbayar	= $conn->prepare("SELECT SUM(jumlah_pfk) AS total FROM pembayaran_faktur WHERE id_tfk=:kode");
		$mbayar->bindParam(':kode', $view['id_tfk'], PDO::PARAM_STR);
		$mbayar->execute();
		$hbayar	= $mbayar->fetch(PDO::FETCH_ASSOC);
		$sisa	= ($view['total_tfk'] - $hbayar['total']);
?>
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Update Data - Pembayaran</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formtransaksi" action="#" method="post" autocomplete="off" enctype="multipart/form-data">
        <input type="hidden" name="nmenu" id="nmenu" value="psales" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="update" readonly="readonly" />
        <input type="hidden" name="keycode" id="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-12">
                    <label>Kode Order <span class="tx-danger">*</span></label>
                    <input type="text" name="kodesales" class="form-control" value="<?php echo("$view[kode_tfk] ($view[nama_out])"); ?>" readonly="readonly" required="required">
                </div>
			</div>
            <div class="row">
                <div class="form-group col-md-4">
                    <label>Tagihan <span class="tx-danger">*</span></label>
                    <input type="text" name="tagihan" id="tagihan" class="form-control" value="<?php echo($data->angka($view['total_tfk'])); ?>" placeholder="0" readonly="readonly" required="required" />
                </div>
                <div class="form-group col-md-4">
                    <label>Dibayar <span class="tx-danger">*</span></label>
                    <input type="text" name="dibayar" id="dibayar" class="form-control" value="<?php echo($data->angka($hbayar['total'])); ?>" placeholder="0" readonly="readonly" required="required" />
                </div>
                <div class="form-group col-md-4">
                    <label>Sisa <span class="tx-danger">*</span></label>
                    <input type="text" name="sisa" id="sisa" class="form-control" value="<?php echo($data->angka($sisa)); ?>" placeholder="0" readonly="readonly" required="required" />
                </div>
			</div>
            <div class="row">
                <div class="form-group col-md-6">
                    <label>BANK <span class="tx-danger">*</span></label>
                    <input type="text" name="bank" class="form-control" value="<?php echo($view['bank_pfk']); ?>" placeholder="Type here..." required="required" />
                </div>
                <div class="form-group col-md-6">
                    <label>Nomor Rekening <span class="tx-danger">*</span></label>
                    <input type="text" name="norek" class="form-control" value="<?php echo($view['norek_pfk']); ?>" placeholder="Type here..." required="required" />
                </div>
			</div>
            <div class="row">
                <div class="form-group col-md-6">
                    <label>Atas Nama<span class="tx-danger">*</span></label>
                    <input type="text" name="nama" class="form-control" value="<?php echo($view['anam_pfk']); ?>" placeholder="Type here..." required="required" />
                </div>
                <div class="form-group col-md-6">
                    <label>Jumlah <span class="tx-danger">*</span></label>
                    <input type="text" name="bayar" id="bayar" class="form-control" onkeyup="angka(this)" value="<?php echo($data->angka($view['jumlah_pfk'])); ?>" placeholder="0" required="required" />
                </div>
			</div>
            <div class="row">
                <div class="form-group col-md-6">
                    <label>Tanggal<span class="tx-danger">*</span></label>
                    <input type="text" name="tanggal" class="form-control fortgl" value="<?php echo($view['tgl_pfk']); ?>" placeholder="9999-99-99" required="required" />
                    <div id="imgloading"></div>
                </div>
                <div class="form-group col-md-6">
                    <label>Bukti<span class="tx-danger">*</span></label>
					<div>
                        <button type="button" class="btn btn-primary" id="tombol1" onclick="namafile('tombol1', 'foto1')"><i class="fa fa-cloud-upload"></i> Upload</button>
                        <input type="file" id="foto1" name="foto1" hidden="hidden" />
					</div>
                </div>
			</div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Batal</button>
            <button type="submit" id="bsave" class="btn btn-dark btn-xs">Update</button>
        </div>
		</form>
<?php
		break;
		case "delete":
		$kode	= $secu->injection($_GET['keycode']);
?>
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Konfirmasi</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formtransaksi" action="#" method="post" autocomplete="off">
        <input type="hidden" name="nmenu" id="nmenu" value="psales" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="delete" readonly="readonly" />
        <input type="hidden" name="keycode" id="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-12">
                    <div class="alert alert-danger alert-dismissible mg-b-0 fade show" role="alert">
                        <strong>Informasi!</strong> Hapus data data pembayaran?
                    </div>
                    <div id="imgloading"></div>
                </div>
			</div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Batal</button>
            <button type="submit" id="bsave" class="btn btn-dark btn-xs">Hapus</button>
        </div>
		</form>
<?php
		break;
		case "detail":
		$kode	= $secu->injection($_GET['keycode']);
		$read	= $conn->prepare("SELECT kode_tfk, status_tfk FROM transaksi_faktur WHERE id_tfk=:kode");
		$read->bindParam(':kode', $kode, PDO::PARAM_STR);
		$read->execute();
		$view	= $read->fetch(PDO::FETCH_ASSOC);
?>
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Historis Pembayaran Outlet</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <div class="modal-body">
            <div class="row">
	            <div class="form-group col-md-12">
                	<h6>Nomor Faktur : <?php echo($view['kode_tfk']); ?></h6>
                    <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th><center>No.</center></th>
                                <th><center>Tanggal</center></th>
                                <th><div align="right">Jumlah</div></th>
                                <th><center>Bukti</center></th>
                            </tr>
						</thead>
                        <tbody>
                        <?php
							$no		= 1;
							$master	= $conn->prepare("SELECT jumlah_pfk, file_pfk, tgl_pfk FROM pembayaran_faktur WHERE id_tfk=:kode ORDER BY tgl_pfk DESC");
							$master->bindParam(':kode', $kode, PDO::PARAM_STR);
							$master->execute();
							while($hasil	= $master->fetch(PDO::FETCH_ASSOC)){
						?>
                        	<tr>
                            	<td><center><?php echo($no); ?></center></td>
                            	<td><center><?php echo($hasil['tgl_pfk']); ?></center></td>
                            	<td><div align="right"><?php echo($data->angka($hasil['jumlah_pfk'])); ?></div></td>
                            	<td>
                                <center>
                                	<a target="_blank" href="<?php echo($data->sistem('url_sis').'/berkas/pfaktur/'.$hasil['file_pfk']); ?>" title="Bukti">
                                    <span class="badge badge-danger"><i class="fa fa-image"></i></span>
                                	</a>
                                </center>
                                </td>
                            </tr>
						<?php $no++; } ?>
                        </tbody>
					</table>
                    </div>
                    <i>Status : <?php echo(($view['status_tfk']=='Tagihan') ? 'Belum Bayar' : (($view['status_tfk']=='Bayar') ? 'Pembayaran Sebagian' : 'Lunas')); ?></i>
                </div>
			</div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Close</button>
        </div>
<?php
		break;
	}
	$conn	= $base->close();
?>
	<script type="text/javascript" src="<?php echo("$sistem/sumoselect/jquery.sumoselect.min.js"); ?>"></script>
	<script type="text/javascript" src="<?php echo("$sistem/config/js/fazlurr.js"); ?>"></script>
	<script type="text/javascript">
	$('.sumoselect').SumoSelect({
		csvDispCount: 3,
		search: true,
		searchText:'Enter here.'
	});
    </script>