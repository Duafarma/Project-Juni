<?php
require_once('../../config/connection/connection.php');
require_once('../../config/connection/security.php');
require_once('../../config/function/data.php');
$secu    = new Security;
$base    = new DB;
$data    = new Data;
$conn    = $base->open();
$modal    = $secu->injection(@$_GET['modal']);
switch ($modal) {
    case "update":
        $kode    = $secu->injection($_GET['keycode']);
        $read    = $conn->prepare("SELECT kode_ttr FROM transaksi_transferretur WHERE id_ttr=:kode");
        $read->bindParam(':kode', $kode, PDO::PARAM_STR);
        $read->execute();
        $view    = $read->fetch(PDO::FETCH_ASSOC);
?>
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Update Data - Transfer Retur</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formtransaksi" action="#" method="post" autocomplete="off">
            <input type="hidden" name="nmenu" id="nmenu" value="transferstok" readonly="readonly" />
            <input type="hidden" name="nact" id="nact" value="update" readonly="readonly" />
            <input type="hidden" name="keycode" id="keycode" value="<?php echo ($kode); ?>" readonly="readonly" />
            <div class="modal-body">
                <div class="row">
                    <div class="form-group col-md-12">
                        <label>Nomor PO <span class="tx-danger">*</span></label>
                        <input type="text" name="nomorpo" class="form-control" value="<?php echo ($view['kode_tor']); ?>" placeholder="Type here..." required="required" />
                        <div id="imgloading"></div>
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
    case 'delete':
        $kode    = $secu->injection($_GET['keycode']);
        $read    = $conn->prepare("SELECT * FROM transaksi_transferretur WHERE id_ttr=:kode");
        $read->bindParam(':kode', $kode, PDO::PARAM_STR);
        $read->execute();
        $view    = $read->fetch(PDO::FETCH_ASSOC);
    ?>
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Konfirmasi</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formtransaksi" action="#" method="post" autocomplete="off">
            <input type="hidden" name="nmenu" id="nmenu" value="transferstok" readonly="readonly" />
            <input type="hidden" name="nact" id="nact" value="delete" readonly="readonly" />
            <input type="hidden" name="keycode" id="keycode" value="<?php echo ($kode); ?>" readonly="readonly" />
            <input type="hidden" name="id" id="id" value="<?= $view['id_ttr'] ?>" readonly="readonly" />
            <input type="hidden" name="kode" id="kode" value="<?= $view['kode_ttr'] ?>" readonly="readonly" />
            <div class="modal-body">
                <div class="row">
                    <div class="form-group col-md-12">
                        <label><?php echo (($view['status_ttr'] == 'Process') ? 'Hapus data Transfer Retur?' : 'Data sudah diproses, tidak dapat dihapus!'); ?> <span class="tx-danger">*</span></label>

                        <!-- Development notification message -->
                        <div class="alert alert-info mt-2" style="border-left: 4px solid #3f51b5; background-color: #f5f7ff;">
                            <div style="display: flex; align-items: center;">
                                <i class="fa fa-tools mr-3" style="font-size: 24px; color: #3f51b5;"></i>
                                <div>
                                    <strong style="color: #3f51b5;">Fitur Dalam Pengembangan</strong>
                                    <p class="mb-0">Mohon maaf, fitur penghapusan Transfer Retur saat ini sedang dalam proses pengembangan.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Batal</button>
                <?php if ($view['status_ttr'] == 'Process'): ?>
                    <!-- Hide the button temporarily but keep the code -->
                    <button type="button" id="bsave_dev" class="btn btn-info btn-xs" onclick="showDevelopmentAlert()">Hapus</button>
                    <!-- Original button is preserved in the code but hidden with display:none -->
                    <button type="submit" id="bsave" class="btn btn-dark btn-xs" style="display:none;">Hapus</button>
                <?php endif; ?>
            </div>

        </form>
    <?php
        break;
    case 'tutup':
        $kode    = $secu->injection($_GET['keycode']);
        $read    = $conn->prepare("SELECT * FROM transaksi_transferretur WHERE id_ttr=:kode");
        $read->bindParam(':kode', $kode, PDO::PARAM_STR);
        $read->execute();
        $view    = $read->fetch(PDO::FETCH_ASSOC);
    ?>
        <div class="modal-header">
            <h6 class="modal-title" id="exampleModalLabel">Konfirmasi</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formtransaksi" action="#" method="post" autocomplete="off">
            <input type="hidden" name="nmenu" id="nmenu" value="order" readonly="readonly" />
            <input type="hidden" name="nact" id="nact" value="tutup" readonly="readonly" />
            <input type="hidden" name="keycode" id="keycode" value="<?php echo ($kode); ?>" readonly="readonly" />
            <div class="modal-body">
                <div class="row">
                    <div class="form-group col-md-12">
                        <label><?php echo (($view['status_ttr'] == 'Success') ? "Transfer Retur dengan no : <b>$view[kode_ttr]</b> sudah berhasil!" : "Transfer Retur dengan no : <b>$view[kode_ttr]</b> masih dalam proses!"); ?></label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Batal</button>
            </div>
        </form>
<?php
        break;
}
$conn    = $base->close();
?>
<script type="text/javascript" src="<?php echo ($data->sistem('url_sis') . '/config/js/fazlurr.js'); ?>"></script>