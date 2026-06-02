<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$sistem	= $data->sistem('url_sis');
	$conn	= $base->open();
	$modal	= $secu->injection(@$_GET['modal']);
	switch($modal){
		case "faktur":
		$uniq	= $secu->injection($_GET['keycode']);
		$kode	= base64_decode($uniq);
		$read	= $conn->prepare("SELECT fak_tre, tglfak_tre, tgl_limit, tgl_tre FROM transaksi_receive WHERE id_tre=:kode");
		$read->bindParam(':kode', $kode, PDO::PARAM_STR);
		$read->execute();
		$view	= $read->fetch(PDO::FETCH_ASSOC);
?>
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Update Data - Faktur Penerimaan</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formmenu" action="#" method="post" autocomplete="off">
        <input type="hidden" name="namamodal" id="namamodal" value="rorder" readonly="readonly" />
        <input type="hidden" name="namamenu" value="faktur" readonly="readonly" />
        <input type="hidden" name="keycode" value="<?php echo($uniq); ?>" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-6">
                    <label>Nomor Faktur <span class="tx-danger">*</span></label>
                    <input type="text" name="nomor" class="form-control" value="<?php echo($view['fak_tre']); ?>" placeholder="Type here..." required="required" />
                </div>
                <div class="form-group col-md-6">
                    <label>Tgl. Faktur <span class="tx-danger">*</span></label>
                    <input type="text" name="tglfaktur" class="form-control fortgl" value="<?php echo($view['tglfak_tre']); ?>" placeholder="9999-99-99" required="required" />
                </div>
			</div>
            <div class="row">
                <div class="form-group col-md-6">
                    <label>Tgl. Terima <span class="tx-danger">*</span></label>
                    <input type="text" name="tglterima" class="form-control fortgl" value="<?php echo($view['tgl_tre']); ?>" placeholder="9999-99-99" required="required" />
                </div>
                <div class="form-group col-md-6">
                    <label>Jatuh Tempo <span class="tx-danger">*</span></label>
                    <input type="text" name="tgltempo" class="form-control fortgl" value="<?php echo($view['tgl_limit']); ?>" placeholder="9999-99-99" required="required" />
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
		case "editdetail":
		$uniq	= $secu->injection($_GET['keycode']);
		$kode	= base64_decode($uniq);
		$read	= $conn->prepare("SELECT A.bcode_trd, A.tbcode_trd, A.jumlah_trd, B.keluar_psd, B.sisa_psd FROM transaksi_receivedetail AS A LEFT JOIN produk_stokdetail AS B ON A.id_trd=B.id_trd WHERE A.id_trd=:kode");
		$read->bindParam(':kode', $kode, PDO::PARAM_STR);
		$read->execute();
		$view	= $read->fetch(PDO::FETCH_ASSOC);
?>
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Update Data - Receive Order</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formmenu" action="#" method="post" autocomplete="off">
        <input type="hidden" name="namamodal" id="namamodal" value="rorder" readonly="readonly" />
        <input type="hidden" name="namamenu" value="editdetail" readonly="readonly" />
        <input type="hidden" name="keycode" value="<?php echo($uniq); ?>" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-6">
                    <label>Batchcode <span class="tx-danger">*</span></label>
                    <input type="text" name="nobcode" class="form-control" value="<?php echo($view['bcode_trd']); ?>" placeholder="Type here..." required="required" />
                </div>
                <div class="form-group col-md-6">
                    <label>Tgl. Expired <span class="tx-danger">*</span></label>
                    <input type="text" name="tglexpired" class="form-control fortgl" value="<?php echo($view['tbcode_trd']); ?>" placeholder="9999-99-99" required="required" />
                </div>
			</div>
            <div class="row">
                <div class="form-group col-md-6">
                    <label>Jumlah Masuk <span class="tx-danger">*</span></label>
                    <input type="text" name="masuk" class="form-control" onkeyup="angka(this)" value="<?php echo($data->angka($view['jumlah_trd'])); ?>" placeholder="0" required="required" />
                </div>
                <div class="form-group col-md-6">
                    <label>Jumlah Keluar <span class="tx-danger">*</span></label>
                    <input type="text" name="keluar" class="form-control" onkeyup="angka(this)" value="<?php echo($data->angka($view['keluar_psd'])); ?>" placeholder="0" required="required" />
                </div>
			</div>
            <div class="row">
                <div class="form-group col-md-6">
                    <label>Jumlah Sisa <span class="tx-danger">*</span></label>
                    <input type="text" name="sisa" class="form-control" onkeyup="angka(this)" value="<?php echo($data->angka($view['sisa_psd'])); ?>" placeholder="0" required="required" />
                    <div id="imgloading"></div>
                </div>
                <div class="form-group col-md-6">
                    <label>Gudang <span class="tx-danger">*</span></label>
                    <select  name="gudang"  class="form-control sumoselect" value="<?php echo($view['gudang']); ?>"  required="required">

                        <option value="Cibinong">Cibinong</option>
                        <option value="Puri">Puri</option>
                            
                    </select>
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
		case "inputitem":
		$kode	= $secu->injection($_GET['keycode']);
?>
        <link rel="stylesheet" href="<?php echo("$sistem/sumoselect/sumoselect.min.css"); ?>" type="text/css" />
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Input Item - Receive Order</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formmenu" action="#" method="post" autocomplete="off">
        <input type="hidden" name="namamodal" id="namamodal" value="rorder" readonly="readonly" />
        <input type="hidden" name="namamenu" value="inputitem" readonly="readonly" />
        <input type="hidden" name="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-12">
                    <label>Produk <span class="tx-danger">*</span></label>
                    <select name="produk" class="form-control sumoselect" required="required">
                    	<option value=""></option>
					<?php
						$status	= 'Active';
						$master	= $conn->prepare("SELECT A.id_pro, A.kode_pro, A.nama_pro, A.berat_pro, B.harga_phg, B.hargap_phg, C.nama_kpr, D.nama_spr FROM produk AS A LEFT JOIN produk_harga AS B ON A.id_pro=B.id_pro LEFT JOIN kategori_produk AS C ON A.id_kpr=C.id_kpr LEFT JOIN satuan_produk AS D ON A.id_spr=D.id_spr WHERE B.status_phg=:status ORDER BY A.nama_pro ASC");
						$master->bindParam(':status', $status, PDO::PARAM_STR);
						$master->execute();
						while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
					?>
                    	<option value="<?php echo($hasil['id_pro']); ?>"><?php echo($hasil['nama_pro']); ?></option>
                	<?php } ?>
                    </select>
                </div>
			</div>
            <div class="row">
                <div class="form-group col-md-6">
                    <label>Batchcode <span class="tx-danger">*</span></label>
                    <input type="text" name="nobcode" class="form-control" placeholder="Type here..." required="required" />
                </div>
                <div class="form-group col-md-6">
                    <label>Tgl. Expired <span class="tx-danger">*</span></label>
                    <input type="text" name="texpired" class="form-control fortgl" placeholder="9999-99-99" required="required" />
                </div>
			</div>
            <div class="row">
                <div class="form-group col-md-6">
                    <label>Jumlah Masuk <span class="tx-danger">*</span></label>
                    <input type="text" name="jumlah" id="jumlah" class="form-control" onkeyup="angka(this)" onchange="sumitemrorder()" placeholder="0" required="required" />
                </div>
                <div class="form-group col-md-6">
                    <label>Harga <span class="tx-danger">*</span></label>
                    <input type="text" name="harga" id="harga" class="form-control" onkeyup="angka(this)" onchange="sumitemrorder()" placeholder="0" required="required" />
                </div>
			</div>
            <div class="row">
                <div class="form-group col-md-6">
                    <label>Diskon<span class="tx-danger">*</span></label>
                    <input type="text" name="diskon" id="diskon" class="form-control" onchange="sumitemrorder()" placeholder="0" required="required" />
                    <div id="imgloading"></div>
                </div>
                <div class="form-group col-md-6">
                    <label>Total<span class="tx-danger">*</span></label>
                    <input type="text" name="total" id="total" class="form-control" onkeyup="angka(this)" placeholder="0" required="required" />
                </div>
                <div class="form-group col-md-6">
                    <label>Gudang <span class="tx-danger">*</span></label>
                    <select  name="gudang"  class="form-control sumoselect" value="<?php echo($view['gudang']); ?>"  required="required">

                        <option value="Cibinong">Cibinong</option>
                        <option value="Puri">Puri</option>
                            
                    </select>
                </div>
			</div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Batal</button>
            <button type="submit" id="bsave" class="btn btn-dark btn-xs">Simpan</button>
        </div>
		</form>
		<script type="text/javascript" src="<?php echo("$sistem/sumoselect/jquery.sumoselect.min.js"); ?>"></script>
        <script type="text/javascript">
        $('.sumoselect').SumoSelect({
            csvDispCount: 3,
            search: true,
            searchText:'Enter here.'
        });
        </script>
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
        <form id="formmenu" action="#" method="post" autocomplete="off">
        <input type="hidden" name="namamodal" id="namamodal" value="rorder" readonly="readonly" />
        <input type="hidden" name="namamenu" value="delete" readonly="readonly" />
        <input type="hidden" name="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-12">
                    <div class="alert alert-danger alert-dismissible mg-b-0 fade show" role="alert">
                        <strong>Informasi!</strong> Hapus data penerimaan barang?
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
	}
	$conn	= $base->close();
?>
	<script type="text/javascript" src="<?php echo("$sistem/config/js/fazlurr.js"); ?>"></script>