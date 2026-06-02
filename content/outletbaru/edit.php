<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item">Mitra</li>
                <li class="breadcrumb-item active" aria-current="page">Outlet Baru</li>
            </ol>
        </nav>
        <h4 class="content-title">Edit Data - Outlet Baru</h4>
    </div>
</div>
<?php
	$kode	= $secu->injection($_GET['keycode']);
	$read	= $conn->prepare("SELECT A.id_kot, A.nama_out, A.resmi_out, A.npwp_out, A.ofcode_out, A.ket_out, A.status_out, B.id_rpo, B.id_rkb, B.telp_ola, B.hp_ola, B.fax_ola, B.email_ola, B.web_ola, B.kopos_ola, B.picp_ola, B.picpk_ola, B.picf_ola, B.picfk_ola, B.jatuk_ola, B.syatuk_ola, B.kantor_ola, B.pengiriman_ola, B.atuk_ola, C.top_odi, C.parameter_odi, C.diskon_odi, COUNT(D.id_ole) AS legal FROM outlet AS A INNER JOIN outlet_alamat AS B ON A.id_out=B.id_out INNER JOIN outlet_diskon AS C ON A.id_out=C.id_out LEFT JOIN outlet_legal AS D ON A.id_out=D.id_out WHERE A.id_out=:kode AND A.status_out = 'Need Revision' GROUP BY A.id_out");
	$read->bindParam(':kode', $kode, PDO::PARAM_STR);
	$read->execute();
	$view	= $read->fetch(PDO::FETCH_ASSOC);
?>
<div class="content-body">
    <div class="component-section no-code">
        <div>
            <div class="row row-sm">
                <div class="col-sm-6">
                    <h5 id="section1" class="tx-semibold">Informasi Outlet</h5>
                    <p class="mg-b-25">Informasi data-data dasar outlet.</p>
                </div>
                <div class="col-sm-6 text-right">
                    <?php if (in_array($view['status_out'],['Need Revision']) ) { ?>
                    <a href="#modal1" id="btnRevised" onclick="crud('outletbaru','updateStatusRevised','<?php echo $kode; ?>')" class="btn btn-warning btn-xs" data-toggle="modal"><i class="fa fa-check"></i> FINISH REVISION</a>
                    <?php } ?>
                </div>
            </div>
        </div>
        <input type="hidden" name="jumlegal" id="jumlegal" value="<?php echo($view['legal']); ?>" readonly="readonly" />
        <input type="hidden" name="jumitem" id="jumitem" value="0" readonly="readonly" />
        <form id="formtransaksi" action="#" method="post" enctype="multipart/form-data" autocomplete="off">
        <input type="hidden" name="nmenu" id="nmenu" value="outletbaru" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="update" readonly="readonly" />
        <input type="hidden" name="keycode" id="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
        <div class="row">
            <div class="form-group col-sm-3">
                <label>Nama Outlet <span class="tx-danger">*</span></label>
                <input type="text" name="namaoutlet" class="form-control" value="<?php echo($view['nama_out']); ?>" placeholder="Type here..." required="required" />
            </div>
            <div class="form-group col-sm-3">
                <label>Nama Resmi <span class="tx-danger">*</span></label>
                <input type="text" name="namaresmi" class="form-control" value="<?php echo($view['resmi_out']); ?>" placeholder="Type here..." required="required" />
            </div>
            <div class="form-group col-sm-3">
                <label>Kategori <span class="tx-danger">*</span></label>
                <input type="text" name="kategori" class="form-control" value="<?php echo($data->koutlet($view['id_kot'], 'nama_kot')); ?>" readonly="readonly" />
            </div>
            <div class="form-group col-sm-3">
                <label>NPWP <span class="tx-danger">*</span></label>
                <input type="text" name="npwp" class="form-control" value="<?php echo($view['npwp_out']); ?>" placeholder="Type here..." required="required" />
            </div>
            <div class="form-group col-sm-3">
                <label>Officer Code <span class="tx-danger">*</span></label>
                <input type="text" name="ofcode" class="form-control" value="<?php echo($view['ofcode_out']); ?>" placeholder="Type here..." required="required" />
            </div>
            <div class="form-group col-sm-3">
                <label>Ket. Lainnya <span class="tx-black">*</span></label>
                <input type="text" name="kete" class="form-control" value="<?php echo($view['ket_out']); ?>" placeholder="Type here..." />
            </div>
        </div><!-- row -->
		<div class="row">
            <div class="form-group col-sm-12">
                <label>Legal Outlet <span class="tx-danger">*</span></label>
				<table class="table table-hover mg-b-0">
					<thead>
						<tr>
							<th>Legal</th>
							<th>Ket.</th>
							<th>Expired Date</th>
							<th>Dokumen</th>
							<th><center>#</center></th>
						</tr>
					</thead>
					<tbody id="tbllegal">
					<?php
						$no	= 1;
						$master	= $conn->prepare("SELECT id_klg, ket_ole, expired_ole, dokumen_ole FROM outlet_legal WHERE id_out=:kode");
						$master->bindParam(':kode', $kode, PDO::PARAM_STR);
						$master->execute();
						while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
					?>
                        <tr id="<?php echo("ileg$nomor"); ?>">
                            <td>
                            <select name="legal[]" class="form-control" required="required">
                                <option value="">-- Select Legal --</option>
                            <?php
                                $mlega	= $conn->prepare("SELECT id_klg, nama_klg FROM kategori_legal ORDER BY nama_klg ASC");
                                $mlega->execute();
                                while($hlega= $mlega->fetch(PDO::FETCH_ASSOC)){
									$pilih	= ($hlega['id_klg']===$hasil['id_klg']) ? 'selected="selected"' : '';
                            ?>
                                <option value="<?php echo($hlega['id_klg']); ?>" <?php echo($pilih); ?>><?php echo($hlega['nama_klg']); ?></option>
                            <?php } ?>
                            </select>
                            </td>
                            <td><input type="text" name="ketlegal[]" class="form-control" value="<?php echo($hasil['ket_ole']); ?>" placeholder="Type here..." /></td>
                            <td><input type="text" name="tgllegal[]" class="form-control fortgl" value="<?php echo($hasil['expired_ole']); ?>" placeholder="9999-99-99" /></td>
                            <td>
                                <input type="file" name="doklegal[]" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx" />
                                <?php if(!empty($hasil['dokumen_ole'])){ ?>
                                    <small><a href="<?php echo($data->sistem('url_sis').'/berkas/legal/'.$hasil['dokumen_ole']); ?>" target="_blank" class="tx-primary"><?php echo($hasil['dokumen_ole']); ?></a></small>
                                <?php } ?>
                            </td>
                            <td>
                            <center>
                                <a onclick="<?php echo("removeitem('jumlegal', 'ileg', $nomor)"); ?>"><span class="badge badge-danger"><i class="fa fa-times-circle"></i></span></a>
                            </center>
                            </td>
                        </tr>
                    <?php $no++; } ?>
                    </tbody>
				</table>
                <a onclick="additem('tbllegal', 'jumlegal', 'legaloutlet')"><span class="badge badge-success"><i class="fa fa-plus-circle"></i> Add Data</span></a>
            </div>
		</div>
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <h5 id="section1" class="tx-semibold">Kontak Outlet</h5>
        <p class="mg-b-25">Lengkapi kontak dan alamat lengkap outlet.</p>
        <div class="row">
            <div class="form-group col-sm-3">
                <label>Telp. <span class="tx-danger">*</span></label>
                <input type="text" name="telp" class="form-control" value="<?php echo($view['telp_ola']); ?>" placeholder="Type here..." required="required" />
            </div>
            <div class="form-group col-sm-3">
                <label>Hp. / WA <span class="tx-black">*</span></label>
                <input type="text" name="hape" class="form-control" value="<?php echo($view['hp_ola']); ?>" placeholder="Type here..." />
            </div>
            <div class="form-group col-sm-3">
                <label>Fax <span class="tx-black">*</span></label>
				<input type="text" name="fax" class="form-control" value="<?php echo($view['fax_ola']); ?>" placeholder="Type here..." />
            </div>
            <div class="form-group col-sm-3">
                <label>Email <span class="tx-danger">*</span></label>
				<input type="email" name="email" class="form-control" value="<?php echo($view['email_ola']); ?>" placeholder="Type here..." required="required" />
            </div>
            <div class="form-group col-sm-3">
                <label>Website <span class="tx-black">*</span></label>
				<input type="text" name="website" class="form-control" value="<?php echo($view['web_ola']); ?>" placeholder="Type here..." />
            </div>
            <div class="form-group col-sm-3">
                <label>Provinsi <span class="tx-danger">*</span></label>                
				<select name="provinsi" id="provinsi" class="form-control select2" onchange="selectdata('provinsi', 'carikabupaten', 'kabupaten')" required="required">
                	<option value="">-- Pilih --</option>
				<?php
					$master	= $conn->prepare("SELECT id_rpo, nama_rpo FROM regional_provinsi ORDER BY nama_rpo ASC");
					$master->execute();
					while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
						$pilih	= ($view['id_rpo']==$hasil['id_rpo']) ? 'selected="selected"' : '';
				?>
                	<option value="<?php echo($hasil['id_rpo']); ?>" <?php echo($pilih); ?>><?php echo($hasil['nama_rpo']); ?></option>
                <?php } ?>
                </select>
            </div>
            <div class="form-group col-sm-3">
                <label>Kab. / Kota <span class="tx-danger">*</span></label>
				<select name="kabupaten" id="kabupaten" class="form-control select2" required="required">
                	<option value="">-- Pilih --</option>
				<?php
					$master	= $conn->prepare("SELECT id_rkb, nama_rkb FROM regional_kabupaten WHERE id_rpo=:prov ORDER BY nama_rkb ASC");
					$master->bindParam(':prov', $view['id_rpo'], PDO::PARAM_STR);
					$master->execute();
					while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
						$pilih	= ($view['id_rkb']==$hasil['id_rkb']) ? 'selected="selected"' : '';
				?>
                	<option value="<?php echo($hasil['id_rkb']); ?>" <?php echo($pilih); ?>><?php echo($hasil['nama_rkb']); ?></option>
                <?php } ?>
                </select>
            </div>
            <div class="form-group col-sm-3">
                <label>Kode Pos <span class="tx-black">*</span></label>
				<input type="text" name="kopos" class="form-control" value="<?php echo($view['kopos_ola']); ?>" placeholder="Type here..." />
            </div>
            <div class="form-group col-sm-3">
                <label>Jadwal Tukar Faktur <span class="tx-black">*</span></label>
				<textarea name="jadwal" class="form-control" placeholder="Type here..."><?php echo($view['jatuk_ola']); ?></textarea>
            </div>
            <div class="form-group col-sm-3">
                <label>Persyaratan Tukar Faktur <span class="tx-black">*</span></label>
				<textarea name="syarat" class="form-control" placeholder="Type here..."><?php echo($view['syatuk_ola']); ?></textarea>
            </div>
            <div class="form-group col-sm-3">
                <label>Alamat Kantor <span class="tx-danger">*</span></label>
				<textarea name="alamatkantor" class="form-control" placeholder="Type here..." required="required"><?php echo($view['kantor_ola']); ?></textarea>
            </div>
            <div class="form-group col-sm-3">
                <label>Alamat Pengiriman <span class="tx-black">*</span></label>
				<textarea name="alamatkirim" class="form-control" placeholder="Type here..."><?php echo($view['pengiriman_ola']); ?></textarea>
            </div>
            <div class="form-group col-sm-3">
                <label>Alamat Tukar Faktur <span class="tx-black">*</span></label>
				<textarea name="alamattukar" class="form-control" placeholder="Type here..."><?php echo($view['atuk_ola']); ?></textarea>
            </div>
        </div><!-- row -->
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <h5 id="section1" class="tx-semibold">PIC Outlet</h5>
        <p class="mg-b-25">Informasi PIC Procurement & Finance outlet.</p>
        <div class="row">
            <div class="form-group col-sm-3">
                <label>PIC Procurement <span class="tx-black">*</span></label>
				<input type="text" name="picp" class="form-control" value="<?php echo($view['picp_ola']); ?>" placeholder="Type here..." />
            </div>
            <div class="form-group col-sm-3">
                <label>Hp. / WA <span class="tx-black">*</span></label>
				<input type="text" name="picpk" class="form-control" value="<?php echo($view['picpk_ola']); ?>" placeholder="Type here..." />
            </div>
            <div class="form-group col-sm-3">
                <label>PIC Finance <span class="tx-black">*</span></label>
				<input type="text" name="picf" class="form-control" value="<?php echo($view['picf_ola']); ?>" placeholder="Type here..." />
            </div>
            <div class="form-group col-sm-3">
                <label>Hp. / WA <span class="tx-black">*</span></label>
				<input type="text" name="picfk" class="form-control" value="<?php echo($view['picfk_ola']); ?>" placeholder="Type here..." />
            </div>
        </div><!-- row -->
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <h5 id="section1" class="tx-semibold">Diskon & Kondisi</h5>
        <p class="mg-b-25">Aturan diskon & kondisi transaksi outlet.</p>
        <div class="row">
            <div class="form-group col-sm-3">
				<label>Diskon Produk <span class="tx-danger">*</span></label>
                <input type="text" name="diskon" class="form-control" value="<?php echo($view['diskon_odi']); ?>" placeholder="0" required="required" />
            </div>
            <div class="form-group col-sm-3">
                <label>TOP <span class="tx-danger">*</span></label>
                <input type="text" name="limit" class="form-control" value="<?php echo($view['top_odi']); ?>" placeholder="0" required="required" />
            </div>
        </div><!-- row -->
		<div class="row">
            <div class="form-group col-sm-12">
                <label>Diskon Produk <span class="tx-danger">*</span></label>
				<table class="table table-hover mg-b-0">
					<thead>
						<tr>
							<th>Produk</th>
							<th>Detail</th>
							<th>Satuan</th>
							<th>Diskon</th>
							<th><center>#</center></th>
						</tr>
					</thead>
					<tbody id="tbldiskon"></tbody>
				</table>
                <a onclick="disprotlet()"><span class="badge badge-success"><i class="fa fa-plus-circle"></i> Add Data</span></a>
            </div>
		</div>
        <!-- row -->
        <div class="row" id="programSection" style="display:none;">
            <div class="form-group col-sm-12">
                <label>Program Produk</label>
                <p class="mg-b-15">Pilih program yang diikuti outlet ini (centang jika ikut).</p>
                <?php
                    $assigned = array();
                    $aq = $conn->prepare("SELECT id_pp FROM program_produk_outlet WHERE id_out=:code");
                    $aq->bindParam(':code', $kode, PDO::PARAM_STR);
                    $aq->execute();
                    while($ar = $aq->fetch(PDO::FETCH_ASSOC)){
                        $assigned[] = $ar['id_pp'];
                    }
                    // Tampilkan Growth Pack terlebih dahulu (jika ada)
                    $gq = $conn->prepare("SELECT id_pp, nama_program, jenis_program, min_qty, diskon_persen FROM program_produk WHERE status_program='Active' AND jenis_program='Growth Pack' ORDER BY nama_program LIMIT 1");
                    $gq->execute();
                    $growth = $gq->fetch(PDO::FETCH_ASSOC);
                    if($growth){
                        $checked = in_array($growth['id_pp'], $assigned) ? 'checked' : '';
                    ?>
                <div class="card border-success mb-3" style="background:#f7fff7;">
                    <div class="card-body" style="padding:12px;">
                        <label style="display:flex;align-items:center;gap:12px;margin:0;width:100%;">
                            <div style="flex:0 0 36px;display:flex;align-items:center;justify-content:center;">
                                <input class="form-check-input" type="checkbox" name="program_ids[]" id="prog_<?php echo($growth['id_pp']); ?>" value="<?php echo($growth['id_pp']); ?>" <?php echo($checked); ?> style="width:20px;height:20px;margin:0;">
                            </div>
                            <div style="flex:1 1 auto;min-width:0;">
                                <h5 style="margin:0;line-height:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo($growth['nama_program']); ?></h5>
                                <p class="mb-0 text-muted" style="margin:0;font-size:0.95em;">Min qty: <?php echo($growth['min_qty']); ?> &middot; Diskon: <?php echo($growth['diskon_persen']); ?>%</p>
                            </div>
                            <div style="flex:0 0 auto;margin-left:12px;text-align:right;color:#6c757d;font-size:12px;">Growth Pack</div>
                        </label>
                    </div>
                </div>
                    <?php }
                    $prog = $conn->prepare("SELECT id_pp, nama_program, jenis_program, min_qty, diskon_persen FROM program_produk WHERE status_program='Active' " . ($growth?"AND id_pp!=:gid ":"") . "ORDER BY nama_program ASC");
                    if($growth){ $prog->bindParam(':gid', $growth['id_pp'], PDO::PARAM_INT); }
                    $prog->execute();
                    while($p = $prog->fetch(PDO::FETCH_ASSOC)){
                        $checked = in_array($p['id_pp'], $assigned) ? 'checked' : '';
                    ?>
                <div class="card border-secondary mb-2" style="background:#fff;">
                    <div class="card-body" style="padding:12px;">
                        <label style="display:flex;align-items:center;gap:12px;margin:0;width:100%;">
                            <div style="flex:0 0 36px;display:flex;align-items:center;justify-content:center;">
                                <input class="form-check-input" type="checkbox" name="program_ids[]" id="prog_<?php echo($p['id_pp']); ?>" value="<?php echo($p['id_pp']); ?>" <?php echo($checked); ?> style="width:20px;height:20px;margin:0;">
                            </div>
                            <div style="flex:1 1 auto;min-width:0;">
                                <h5 style="margin:0;line-height:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo($p['nama_program']); ?></h5>
                                <p class="mb-0 text-muted" style="margin:0;font-size:0.95em;">Min qty: <?php echo($p['min_qty']); ?> &middot; Diskon: <?php echo($p['diskon_persen']); ?>%</p>
                            </div>
                            <div style="flex:0 0 auto;margin-left:12px;text-align:right;color:#6c757d;font-size:12px;"><?php echo($p['jenis_program']); ?></div>
                        </label>
                    </div>
                </div>
                    <?php } ?>
            </div>
        </div>
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <div class="row">
            <div class="col-sm-12">
                <a href="<?php echo($data->sistem('url_sis').'/outlet'); ?>" title="Batal">
                <button type="button" class="btn btn-secondary btn-xs">Batal</button>
				</a>
                <button type="submit" id="bsave" class="btn btn-dark btn-xs">Update</button>
            </div>
		</div>
		</form>
    </div>
</div>
<script>
// Show program section for edit only when kategori value contains 'Apotek'
document.addEventListener('DOMContentLoaded', function(){
    function toggleProgramSectionEdit(){
        var kat = document.querySelector('input[name="kategori"]');
        if(!kat) return;
        var text = kat.value || '';
        var sec = document.getElementById('programSection');
        if(text.toLowerCase().includes('apotek')){ sec.style.display = ''; }
        else { sec.style.display = 'none'; }
    }
    toggleProgramSectionEdit();
});
</script>