<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$conn	= $base->open();
	$modal	= $secu->injection(@$_GET['modal']);
	switch($modal){
		case "input":
?>
        <div class="modal-header bg-primary text-white">
            <h6 class="modal-title" id="exampleModalLabel"><i class="fas fa-plus-circle"></i> Input Data - Program Promo</h6>
            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formtransaksi" action="#" method="post" autocomplete="off">
        <input type="hidden" name="nmenu" id="nmenu" value="programpromo" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="input" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-6">
                    <label>Kode Program <span class="tx-danger">*</span></label>
                    <input type="text" name="kode_program" class="form-control" placeholder="Contoh: program_vb" required="required" />
                    <small class="text-muted">Gunakan huruf kecil dan underscore, contoh: program_vb, program_diskon</small>
                </div>
                <div class="form-group col-md-6">
                    <label>Nama Program <span class="tx-danger">*</span></label>
                    <input type="text" name="nama_program" class="form-control" placeholder="Contoh: Program VB (Volume Bonus)" required="required" />
                </div>
                <div class="form-group col-md-12">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi" class="form-control" rows="3" placeholder="Deskripsi program promo..."></textarea>
                </div>
                <div class="form-group col-md-6">
                    <label>Icon Class</label>
                    <input type="text" name="icon_class" class="form-control" value="fas fa-tag" placeholder="fas fa-tag" />
                    <small class="text-muted">Font Awesome icon class, contoh: fas fa-percentage, fas fa-gift</small>
                </div>
                <div class="form-group col-md-3">
                    <label>Status <span class="tx-danger">*</span></label>
                    <select name="status_program" class="form-control" required="required">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label>Urutan</label>
                    <input type="number" name="urutan" class="form-control" value="0" min="0" />
                </div>
            </div>
            <div id="imgloading"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Batal</button>
            <button type="submit" id="bsave" class="btn btn-primary btn-xs"><i class="fas fa-save"></i> Simpan</button>
        </div>
        </form>
<?php
		break;
		case "update":
		$kode = $secu->injection($_GET['keycode']);
		$read = $conn->prepare("SELECT * FROM program_promo WHERE id_program=:kode");
		$read->bindParam(':kode', $kode, PDO::PARAM_STR);
		$read->execute();
		$view = $read->fetch(PDO::FETCH_ASSOC);
?>
        <div class="modal-header bg-info text-white">
            <h6 class="modal-title" id="exampleModalLabel"><i class="fas fa-edit"></i> Update Data - Program Promo</h6>
            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formtransaksi" action="#" method="post" autocomplete="off">
        <input type="hidden" name="nmenu" id="nmenu" value="programpromo" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="update" readonly="readonly" />
        <input type="hidden" name="keycode" id="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-6">
                    <label>Kode Program <span class="tx-danger">*</span></label>
                    <input type="text" name="kode_program" class="form-control" value="<?php echo($view['kode_program']); ?>" placeholder="Contoh: program_vb" required="required" />
                    <small class="text-muted">Gunakan huruf kecil dan underscore</small>
                </div>
                <div class="form-group col-md-6">
                    <label>Nama Program <span class="tx-danger">*</span></label>
                    <input type="text" name="nama_program" class="form-control" value="<?php echo($view['nama_program']); ?>" placeholder="Contoh: Program VB" required="required" />
                </div>
                <div class="form-group col-md-12">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi" class="form-control" rows="3" placeholder="Deskripsi program promo..."><?php echo($view['deskripsi']); ?></textarea>
                </div>
                <div class="form-group col-md-6">
                    <label>Icon Class</label>
                    <input type="text" name="icon_class" class="form-control" value="<?php echo($view['icon_class']); ?>" placeholder="fas fa-tag" />
                    <small class="text-muted">Preview: <i class="<?php echo($view['icon_class']); ?>"></i></small>
                </div>
                <div class="form-group col-md-3">
                    <label>Status <span class="tx-danger">*</span></label>
                    <select name="status_program" class="form-control" required="required">
                        <option value="Active" <?php echo($view['status_program']==='Active' ? 'selected' : ''); ?>>Active</option>
                        <option value="Inactive" <?php echo($view['status_program']==='Inactive' ? 'selected' : ''); ?>>Inactive</option>
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label>Urutan</label>
                    <input type="number" name="urutan" class="form-control" value="<?php echo($view['urutan']); ?>" min="0" />
                </div>
            </div>
            <div id="imgloading"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Batal</button>
            <button type="submit" id="bsave" class="btn btn-info btn-xs"><i class="fas fa-save"></i> Update</button>
        </div>
        </form>
<?php
		break;
		case "delete":
		$kode = $secu->injection($_GET['keycode']);
		$read = $conn->prepare("SELECT nama_program FROM program_promo WHERE id_program=:kode");
		$read->bindParam(':kode', $kode, PDO::PARAM_STR);
		$read->execute();
		$view = $read->fetch(PDO::FETCH_ASSOC);
?>
        <div class="modal-header bg-danger text-white">
            <h6 class="modal-title" id="exampleModalLabel"><i class="fas fa-trash"></i> Konfirmasi Hapus</h6>
            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formtransaksi" action="#" method="post" autocomplete="off">
        <input type="hidden" name="nmenu" id="nmenu" value="programpromo" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="delete" readonly="readonly" />
        <input type="hidden" name="keycode" id="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
        <div class="modal-body">
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> Apakah Anda yakin ingin menghapus program promo <strong>"<?php echo($view['nama_program']); ?>"</strong>?
            </div>
            <p class="text-muted">Data yang sudah dihapus tidak dapat dikembalikan.</p>
            <div id="imgloading"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Batal</button>
            <button type="submit" id="bsave" class="btn btn-danger btn-xs"><i class="fas fa-trash"></i> Hapus</button>
        </div>
        </form>
<?php
		break;
		case "cari":
?>
        <div class="modal-header bg-warning">
            <h6 class="modal-title" id="exampleModalLabel"><i class="fas fa-search"></i> Cari Data</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-12">
                    <label>Kata Kunci</label>
                    <input type="text" id="katakunci" class="form-control" placeholder="Ketik nama program atau kode..." />
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Batal</button>
            <a href="#" onclick="location.href='<?php echo($data->sistem('url_sis')); ?>/programpromo/'+$('#katakunci').val();"><button type="button" class="btn btn-warning btn-xs"><i class="fas fa-search"></i> Cari</button></a>
        </div>
<?php
		break;
	}
	$conn = $base->close();
?>
