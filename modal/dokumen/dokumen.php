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
        <form id="formtransaksii" action="#" method="post" autocomplete="off" enctype="multipart/form-data">
        <input type="hidden" name="nmenu" id="nmenu" value="porder" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="update" readonly="readonly" />
        <input type="hidden" name="namamenu" value="<?php echo($modal); ?>" readonly="readonly" />
        <input type="hidden" name="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
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
        <form id="formtransaksii" action="#" method="post" autocomplete="off">
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
		case "updateStatusBalik":
		$kode	= $secu->injection($_GET['keycode']);
		$read	= $conn->prepare("SELECT
                                        A.id_tfk,
                                        A.sj_tfk,
                                        A.tglsj_tfk,
                                        A.po_tfk,
                                        A.tglpo_tfk,
                                        A.status_dokumen,
                                        A.kode_tfk,
                                        A.tgl_tfk,
                                        A.total_tfk,
                                        A.status_tfk,
                                        B.nama_out,
                                        D.nama_rkb
                                    FROM
                                        transaksi_faktur AS A
                                    LEFT JOIN outlet AS B ON
                                        A.id_out = B.id_out
                                    LEFT JOIN outlet_alamat AS C ON
                                        A.id_out = C.id_out 
                                    LEFT JOIN regional_kabupaten AS D ON
                                        C.id_rkb = D.id_rkb 
                                    WHERE
                                    A.id_tfk=:kode
                                    
                                    ");
		$read->bindParam(':kode', $kode, PDO::PARAM_STR);
		$read->execute();
		$view	= $read->fetch(PDO::FETCH_ASSOC);
?>		

        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Data Dokumen Balik</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formtransaksi" action="#" method="post" autocomplete="off">
            <input type="hidden" name="namamodal" id="namamodal" value="dokumen" readonly="readonly" />
            <input type="hidden" name="nmenu" id="nmenu" value="dokumen" readonly="readonly" />
            <input type="hidden" name="namamenu" value="<?php echo($modal); ?>" readonly="readonly" />
            <input type="hidden" name="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
            <div class="modal-body">
                <div class="row">
                    <div class="form-group col-md-12">
                        <label>No. Faktur</label>
                        <input type="text" class="form-control" value="<?php echo($view['kode_tfk']); ?>" disabled/>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-12">
                        <label>Outlet</label>
                        <input type="text" class="form-control" value="<?php echo($view['nama_out']); ?>" disabled />
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
        case "delete":
            $kode	= $secu->injection($_GET['keycode']);
    ?>
            <div class="modal-header">
                <h6 class="modal-title" id="exampleModalLabel">Konfirmasi</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
                </button>
            </div>
            <form id="formtransaksii" action="#" method="post" autocomplete="off">
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
            case "updateStatusFailing":
            $kode	= $secu->injection($_GET['keycode']);
            $read	= $conn->prepare("SELECT
                                            A.id_tfk,
                                            A.sj_tfk,
                                            A.tglsj_tfk,
                                            A.po_tfk,
                                            A.tglpo_tfk,
                                            A.status_dokumen,
                                            A.kode_tfk,
                                            A.tgl_tfk,
                                            A.total_tfk,
                                            A.status_tfk,
                                            B.nama_out,
                                            D.nama_rkb
                                        FROM
                                            transaksi_faktur AS A
                                        LEFT JOIN outlet AS B ON
                                            A.id_out = B.id_out
                                        LEFT JOIN outlet_alamat AS C ON
                                            A.id_out = C.id_out 
                                        LEFT JOIN regional_kabupaten AS D ON
                                            C.id_rkb = D.id_rkb 
                                        WHERE
                                        A.id_tfk=:kode
                                        
                                        ");
            $read->bindParam(':kode', $kode, PDO::PARAM_STR);
            $read->execute();
            $view	= $read->fetch(PDO::FETCH_ASSOC);
    ?>		
    
            <div class="modal-header">
                <h6 class="modal-title" id="exampleModalLabel">Data Dokumen Balik</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
                </button>
            </div>
            <form id="formtransaksi" action="#" method="post" autocomplete="off">
                <input type="hidden" name="namamodal" id="namamodal" value="dokumen" readonly="readonly" />
                <input type="hidden" name="nmenu" id="nmenu" value="dokumen" readonly="readonly" />
                <input type="hidden" name="namamenu" value="<?php echo($modal); ?>" readonly="readonly" />
                <input type="hidden" name="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
                <div class="modal-body">
                    <div class="row">
                        <div class="form-group col-md-12">
                            <label>No. Faktur</label>
                            <input type="text" class="form-control" value="<?php echo($view['kode_tfk']); ?>" disabled/>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-12">
                            <label>Outlet</label>
                            <input type="text" class="form-control" value="<?php echo($view['nama_out']); ?>" disabled />
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