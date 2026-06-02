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
        <input type="hidden" name="nmenu" id="nmenu" value="porder" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="input" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-12">
                	<table width="100%">
                    	<tr>
                        	<td width="35%"><label>Nomor Faktur <span class="tx-danger">*</span></label></td>
                        	<td width="5%"></td>
                        	<td width="60%">
                            <select name="kodeorder" id="kodeorder" class="form-control sumoselect" onchange="caritagihan()" required="required">
                                <option value="">-- Select Nomor Faktur --</option>
                            <?php
                                $lunas	= 'Lunas';
                                $master	= $conn->prepare("SELECT A.id_tre, A.fak_tre, B.nama_sup FROM transaksi_receive AS A LEFT JOIN supplier AS B ON A.id_sup=B.id_sup WHERE A.status_tre!=:lunas ORDER BY A.tgl_tre ASC");
                                $master->bindParam(':lunas', $lunas, PDO::PARAM_STR);
                                $master->execute();
                                while($hasil	= $master->fetch(PDO::FETCH_ASSOC)){
                            ?>
                                <option value="<?php echo($hasil['id_tre']); ?>"><?php echo("$hasil[fak_tre] ($hasil[nama_sup])"); ?></option>
                            <?php } ?>
                            </select>
                            </td>
                        </tr>
                        <tr>
                        	<td><label>Supplier <span class="tx-danger">*</span></label></td>
                        	<td></td>
                        	<td><input type="text" name="namasup" id="namasup" class="form-control" style="background:#FFFFFF;" placeholder="-" readonly="readonly" /></td>
                        </tr>
                        <tr>
                        	<td><label>Tgl. Faktur <span class="tx-danger">*</span></label></td>
                        	<td></td>
                        	<td><input type="text" name="tglterima" id="tglterima" class="form-control" style="background:#FFFFFF;" placeholder="-" readonly="readonly" /></td>
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
                    <label>Tagihan <span class="tx-danger">*</span></label>
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
                    <label>BANK <span class="tx-danger">*</span></label>
                    <input type="text" name="bank" class="form-control" placeholder="ex. Mandiri" required="required" />
                </div>
                <div class="form-group col-md-6">
                    <label>Nomor Rekening <span class="tx-danger">*</span></label>
                    <input type="text" name="norek" class="form-control" placeholder="ex. 022939393" required="required" />
                </div>
			</div>
            <div class="row">
                <div class="form-group col-md-6">
                    <label>Atas Nama<span class="tx-danger">*</span></label>
                    <input type="text" name="nama" class="form-control" placeholder="ex. Fazlurrahman" required="required" />
                </div>
                <div class="form-group col-md-6">
                    <label>Jumlah <span class="tx-danger">*</span></label>
                    <input type="text" name="bayar" id="bayar" class="form-control" onkeyup="angka(this)" onchange="jumlahbayar()" placeholder="0" required="required" />
                </div>
			</div>
            <div class="row">
                <div class="form-group col-md-6">
                    <label>Tanggal<span class="tx-danger">*</span></label>
                    <input type="text" name="tanggal" class="form-control fortgl" value="<?php echo(date('Y-m-d')); ?>" placeholder="9999-99-99" required="required" />
                    <div id="imgloading"></div>
                </div>
                <div class="form-group col-md-6">
                    <label>Bukti<span class="tx-danger">*</span></label>
					<div>
                        <button type="button" class="btn btn-primary" id="tombol1" onclick="namafile('tombol1', 'foto1')"><i class="fa fa-cloud-upload"></i> Upload</button>
                        <input type="file" id="foto1" name="foto1" hidden="hidden" required="required" />
					</div>
                </div>
			</div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Close</button>
            <button type="submit" id="bsave" class="btn btn-dark btn-xs">Save</button>
        </div>
		</form>
<?php
		break;
		case "update":
		$kode	= $secu->injection($_GET['keycode']);
		$read	= $conn->prepare("SELECT A.id_tor, A.bank_por, A.norek_por, A.anam_por, A.jumlah_por, A.tgl_por, B.status_tre, B.total_tre, C.kode_tor, D.nama_sup FROM pembayaran_order AS A LEFT JOIN transaksi_receive AS B ON A.id_tor=B.id_tor LEFT JOIN transaksi_order AS C ON B.id_tor=C.id_tor LEFT JOIN supplier AS D ON C.id_sup=D.id_sup WHERE A.id_por=:kode");
		$read->bindParam(':kode', $kode, PDO::PARAM_STR);
		$read->execute();
		$view	= $read->fetch(PDO::FETCH_ASSOC);

		$mbayar	= $conn->prepare("SELECT SUM(jumlah_por) AS total FROM pembayaran_order WHERE id_tor=:kode");
		$mbayar->bindParam(':kode', $view['id_tor'], PDO::PARAM_STR);
		$mbayar->execute();
		$hbayar	= $mbayar->fetch(PDO::FETCH_ASSOC);
		$sisa	= ($view['total_tre'] - $hbayar['total']);
?>
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Update Data - Pembayaran</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formtransaksi" action="#" method="post" autocomplete="off" enctype="multipart/form-data">
        <input type="hidden" name="nmenu" id="nmenu" value="porder" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="update" readonly="readonly" />
        <input type="hidden" name="keycode" id="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-12">
                    <label>Kode Order <span class="tx-danger">*</span></label>
                    <input type="text" name="kodeorder" class="form-control" value="<?php echo("$view[kode_tor] ($view[nama_sup])"); ?>" readonly="readonly" required="required">
                </div>
			</div>
            <div class="row">
                <div class="form-group col-md-4">
                    <label>Tagihan <span class="tx-danger">*</span></label>
                    <input type="text" name="tagihan" id="tagihan" class="form-control" value="<?php echo($data->angka($view['total_tre'])); ?>" placeholder="0" readonly="readonly" required="required" />
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
                    <input type="text" name="bank" class="form-control" value="<?php echo($view['bank_por']); ?>" placeholder="Type here..." required="required" />
                </div>
                <div class="form-group col-md-6">
                    <label>Nomor Rekening <span class="tx-danger">*</span></label>
                    <input type="text" name="norek" class="form-control" value="<?php echo($view['norek_por']); ?>" placeholder="Type here..." required="required" />
                </div>
			</div>
            <div class="row">
                <div class="form-group col-md-6">
                    <label>Atas Nama<span class="tx-danger">*</span></label>
                    <input type="text" name="nama" class="form-control" value="<?php echo($view['anam_por']); ?>" placeholder="Type here..." required="required" />
                </div>
                <div class="form-group col-md-6">
                    <label>Jumlah <span class="tx-danger">*</span></label>
                    <input type="text" name="bayar" id="bayar" class="form-control" onkeyup="angka(this)" value="<?php echo($data->angka($view['jumlah_por'])); ?>" placeholder="0" required="required" />
                </div>
			</div>
            <div class="row">
                <div class="form-group col-md-6">
                    <label>Tanggal<span class="tx-danger">*</span></label>
                    <input type="text" name="tanggal" class="form-control fortgl" value="<?php echo($view['tgl_por']); ?>" placeholder="9999-99-99" required="required" />
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
            <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Close</button>
            <button type="submit" id="bsave" class="btn btn-dark btn-xs">Save</button>
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
        <input type="hidden" name="nmenu" id="nmenu" value="porder" readonly="readonly" />
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
		$read	= $conn->prepare("SELECT fak_tre, status_tre FROM transaksi_receive WHERE id_tre=:kode");
		$read->bindParam(':kode', $kode, PDO::PARAM_STR);
		$read->execute();
		$view	= $read->fetch(PDO::FETCH_ASSOC);
?>
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Historis Pembayaran Supplier</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <div class="modal-body">
            <div class="row">
	            <div class="form-group col-md-12">
                	<h6>Nomor Faktur : <?php echo($view['fak_tre']); ?></h6>
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
							$master	= $conn->prepare("SELECT jumlah_pre, file_pre, tgl_pre FROM pembayaran_receive WHERE id_tre=:kode ORDER BY tgl_pre DESC");
							$master->bindParam(':kode', $kode, PDO::PARAM_STR);
							$master->execute();
							while($hasil	= $master->fetch(PDO::FETCH_ASSOC)){
						?>
                        	<tr>
                            	<td><center><?php echo($no); ?></center></td>
                            	<td><center><?php echo($hasil['tgl_pre']); ?></center></td>
                            	<td><div align="right"><?php echo($data->angka($hasil['jumlah_pre'])); ?></div></td>
                            	<td>
                                <center>
                                	<a target="_blank" href="<?php echo($data->sistem('url_sis').'/berkas/porder/'.$hasil['file_pre']); ?>" title="Bukti">
                                    <span class="badge badge-danger"><i class="fa fa-image"></i></span>
                                	</a>
                                </center>
                                </td>
                            </tr>
						<?php $no++; } ?>
                        </tbody>
					</table>
                    </div>
                    <i>Status : <?php echo(($view['status_tre']=='Tagihan') ? 'Belum Bayar' : (($view['status_tre']=='Bayar') ? 'Pembayaran Sebagian' : 'Lunas')); ?></i>
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
	<script type="text/javascript" src="<?php echo($data->sistem('url_sis').'/config/js/fazlurr.js'); ?>"></script>
	<script type="text/javascript" src="<?php echo("$sistem/sumoselect/jquery.sumoselect.min.js"); ?>"></script>
	<script type="text/javascript">
	$('.sumoselect').SumoSelect({
		csvDispCount: 3,
		search: true,
		searchText:'Enter here.'
	});
    </script>