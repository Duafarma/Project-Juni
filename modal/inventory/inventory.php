<?php
    require_once('../../config/connection/connection.php');
    require_once('../../config/connection/security.php');
    require_once('../../config/function/data.php');
    $secu   = new Security;
    $base   = new DB;
    $data   = new Data;
    $conn   = $base->open();
    $modal  = $secu->injection(@$_GET['modal']);
    switch($modal){
        case "update":
        $kode   = $secu->injection($_GET['keycode']);
        $read   = $conn->prepare("SELECT no_bcode, tgl_expired,masuk_psd, keluar_psd, sisa_psd, gudang  FROM produk_stokdetail WHERE id_psd=:kode");
        $read->bindParam(':kode', $kode, PDO::PARAM_STR);
        $read->execute();
        $view   = $read->fetch(PDO::FETCH_ASSOC);
?>
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Update Data - Inventory</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formtransaksi" action="#" method="post" autocomplete="off">
        <input type="hidden" name="nmenu" id="nmenu" value="inventory" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="update" readonly="readonly" />
        <input type="hidden" name="keycode" id="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-6">
                    <label>Batchcode <span class="tx-danger">*</span></label>
                    <input type="text" name="nobcode" class="form-control" value="<?php echo($view['no_bcode']); ?>" placeholder="Type here..." required="required" />
                </div>
                <div class="form-group col-md-6">
                    <label>Tgl. Expired <span class="tx-danger">*</span></label>
                    <input type="text" name="tglexpired" class="form-control fortgl" value="<?php echo($view['tgl_expired']); ?>" placeholder="9999-99-99" required="required" />
                </div>
            </div>
            <div class="row">
                <div class="form-group col-md-6">
                    <label>Jumlah Masuk <span class="tx-danger">*</span></label>
                    <input type="text" name="masuk" class="form-control" onkeyup="angka(this)" value="<?php echo($data->angka($view['masuk_psd'])); ?>" placeholder="0" required="required" />
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
                     <option value="Bekasi">Bekasi</option>
                      <option value="Surabaya">Surabaya</option>
                    <option value="Minus-SO">Minus SO</option>
                        
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
    }
    $conn   = $base->close();
?>
    <script type="text/javascript" src="<?php echo($data->sistem('url_sis').'/config/js/fazlurr.js'); ?>"></script>