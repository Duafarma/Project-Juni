<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item">Mitra</li>
                <li class="breadcrumb-item active" aria-current="page">Outlet Baru</li>
            </ol>
        </nav>
        <h4 class="content-title">Input Data - Outlet Baru</h4>
    </div>
</div>
<!--
<input type="hidden" name="caridata" id="caridata" value="-" readonly="readonly" />
<input type="hidden" name="halaman" id="halaman" value="1" readonly="readonly" />
<input type="hidden" name="maximal" id="maximal" value="15" readonly="readonly" />
-->
<input type="hidden" name="jumlegal" id="jumlegal" value="0" readonly="readonly" />
<input type="hidden" name="jumitem" id="jumitem" value="0" readonly="readonly" />
<div class="content-body">
    <div class="component-section no-code">
        <h5 id="section1" class="tx-semibold">Informasi Outlet</h5>
        <p class="mg-b-25">Informasi data-data dasar outlet.</p>
        <form id="formoutlet" action="#" method="post" enctype="multipart/form-data" autocomplete="off">
        <input type="hidden" name="nmenu" id="nmenu" value="outletbaru" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="input" readonly="readonly" />
        <div class="row">
            <div class="form-group col-sm-3">
                <label>Nama Outlet <span class="tx-danger">*</span></label>
                <input type="text" name="namaoutlet" class="form-control" placeholder="Type here..." required="required" />
            </div>
            <div class="form-group col-sm-3">
                <label>Nama Resmi <span class="tx-danger">*</span></label>
                <input type="text" name="namaresmi" class="form-control" placeholder="Type here..." required="required" />
            </div>
            <div class="form-group col-sm-3">
                <label>Kategori <span class="tx-danger">*</span></label>
				<select name="kategori" class="form-control select2" required="required">
                	<option value="">-- Pilih --</option>
				<?php
					$master	= $conn->prepare("SELECT id_kot, kode_kot, nama_kot FROM kategori_outlet ORDER BY nama_kot ASC");
					$master->execute();
					while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
				?>
                	<option value="<?php echo($hasil['id_kot']); ?>"><?php echo("$hasil[kode_kot] - $hasil[nama_kot]"); ?></option>
                <?php } ?>
                </select>
            </div>
            <div class="form-group col-sm-3">
                <label>NPWP <span class="tx-danger">*</span></label>
                <input type="text" name="npwp" class="form-control" placeholder="Type here..." required="required" />
            </div>
            <!--<div class="form-group col-sm-3">-->
            <!--    <label>Officer Code <span class="tx-danger">*</span></label>-->
            <!--    <input type="text" name="ofcode" class="form-control" placeholder="Type here..." required="required" />-->
            <!--</div>-->
            <div class="form-group col-sm-3">
                <label> PEMBAYARAN VIA</label> <span class="tx-danger">*</span></label> <br>
                <input type="checkbox" name="kete" value="Giro"> Giro<br>
                <input type="checkbox" name="kete" value="Cash "> Cash<br>
                <input type="checkbox" name="kete" value="Transfer"> Transfer<br> 
            </div>
              <div class="form-group col-sm-3">
                <label> Kode Rumah Sakit</label> <span class="tx-danger">*</span></label> <br>
                <input type="checkbox" name="kode_rs" value="APK"> Apotek & Klinik<br>
                <input type="checkbox" name="kode_rs" value="RSP "> Rumah Sakit Pemerintah<br>
                <input type="checkbox" name="kode_rs" value="RSS"> Rumah Sakit Swasta<br> 
                <input type="checkbox" name="kode_rs" value="PBF"> PBF<br> 
            </div>
             <div class="form-group col-sm-3">
                <label>Grup Outlet<span class="tx-danger"> *</span></label>
                <select name="id_mg" class="form-control select2" required="required">
                    <option value="">-- Pilih --</option>
                    <?php
                        $master = $conn->prepare("SELECT id_mg, kode_mg, nama_mg FROM master_grup WHERE nama_mg IS NOT NULL ORDER BY nama_mg ASC");
                        $master->execute();
                        while ($hasil = $master->fetch(PDO::FETCH_ASSOC)) {
                    ?>
                        <option value="<?php echo($hasil['id_mg']); ?>">
                            <?php echo($hasil['nama_mg']); ?>
                        </option>
                    <?php } ?>
                </select>
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
							<th><center>#</center></th>
						</tr>
					</thead>
					<tbody id="tbllegal"></tbody>
				</table>
                <a onclick="additem('tbllegal', 'jumlegal', 'legaloutlet')"><span class="badge badge-success"><i class="fa fa-plus-circle"></i> Add Data</span></a>
            </div>
		</div>
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <h5 id="section1" class="tx-semibold">Kontak Outlet</h5>
        <p class="mg-b-25">Lengkapi kontak dan alamat lengkap outlet.</p>
        <div class="row">
            <div class="form-group col-sm-3">
                <label>Telp. <span class="tx-danger"></span></label>
                <input type="text" name="telp" class="form-control" placeholder="Type here..."  />
            </div>
            <div class="form-group col-sm-3">
                <label>Hp. / WA <span class="tx-danger"></span></label>
                <input type="text" name="hape" class="form-control" placeholder="Type here..." />
            </div>
            <div class="form-group col-sm-3">
                <label>Fax <span class="tx-black"></span></label>
				<input type="text" name="fax" class="form-control" placeholder="Type here..." />
            </div>
            <div class="form-group col-sm-3">
                <label>Email <span class="tx-black"></span></label>
				<input type="email" name="email" class="form-control" placeholder="Type here..."  />
            </div>
            <div class="form-group col-sm-3">
                <label>Website <span class="tx-black"></span></label>
				<input type="text" name="website" class="form-control" placeholder="Type here..." />
            </div>
            <div class="form-group col-sm-3">
                <label>Provinsi <span class="tx-danger">*</span></label>
                <select name="provinsi" id="provinsi" class="form-control select2" onchange="selectdata('provinsi', 'carikabupaten', 'kabupaten')" required="required">
                	<option value="">-- Pilih --</option>
				<?php
					$master	= $conn->prepare("SELECT id_rpo, nama_rpo FROM regional_provinsi ORDER BY nama_rpo ASC");
					$master->execute();
					while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
				?>
                	<option value="<?php echo($hasil['id_rpo']); ?>"><?php echo($hasil['nama_rpo']); ?></option>
                <?php } ?>
                </select>
            </div>
            <div class="form-group col-sm-3">
                <label>Kab. / Kota <span class="tx-danger">*</span></label>
				<select name="kabupaten" id="kabupaten" class="form-control select2" required="required">
                	<option value="">-- Pilih --</option>
                </select>
            </div>
            <div class="form-group col-sm-3">
                <label>Kode Pos <span class="tx-black">*</span></label>
				<input type="text" name="kopos" class="form-control" placeholder="Type here..." />
            </div>
            <div class="form-group col-sm-3">
                <label>Jadwal Tukar Faktur <span class="tx-danger">*</span></label>
				<textarea name="jadwal" class="form-control" placeholder="Type here..."></textarea>
            </div>
            <div class="form-group col-sm-3">
                <label>Persyaratan Tukar Faktur <span class="tx-danger">*</span></label>
				<textarea name="syarat" class="form-control" placeholder="Type here..."></textarea>
            </div>
            <div class="form-group col-sm-3">
                <label>Alamat Kantor <span class="tx-danger">*</span></label>
				<textarea name="alamatkantor" class="form-control" placeholder="Type here..." required="required"></textarea>
            </div>
            <div class="form-group col-sm-3">
                <label>Alamat Pengiriman <span class="tx-danger">*</span></label>
				<textarea name="alamatkirim" class="form-control" placeholder="Type here..."></textarea>
            </div>
            <div class="form-group col-sm-3">
                <label>Alamat Tukar Faktur <span class="tx-danger">*</span></label>
				<textarea name="alamattukar" class="form-control" placeholder="Type here..."></textarea>
            </div>
              <div class="form-group col-sm-3">
                <label>Alamat NPWP <span class="tx-danger">*</span></label>
				<textarea name="npwpa" class="form-control" placeholder="Type here..."></textarea>
            </div>
        </div><!-- row -->
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <h5 id="section1" class="tx-semibold">PIC Outlet</h5>
        <p class="mg-b-25">Informasi PIC Procurement & Finance outlet.</p>
        <div class="row">
            <div class="form-group col-sm-3">
                <label>PIC Procurement <span class="tx-danger">*</span></label>
				<input type="text" name="picp" class="form-control" placeholder="Type here..." />
            </div>
            <div class="form-group col-sm-3">
                <label>Hp. / WA <span class="tx-dangerk">*</span></label>
				<input type="text" name="picpk" class="form-control" placeholder="Type here..." />
            </div>
            <div class="form-group col-sm-3">
                <label>PIC Finance <span class="tx-danger">*</span></label>
				<input type="text" name="picf" class="form-control" placeholder="Type here..." />
            </div>
            <div class="form-group col-sm-3">
                <label>Hp. / WA <span class="tx-black">*</span></label>
				<input type="text" name="picfk" class="form-control" placeholder="Type here..." />
            </div>
        </div><!-- row -->
        <div class="clearfix mg-t-25 mg-b-25"></div>
       
		<div class="row">
            <div class="form-group col-sm-3">
                <label>TOP <span class="tx-danger">*</span></label>
                <input type="text" name="limit" class="form-control" placeholder="0" required="required" />
            </div>
        </div><!-- row -->
        
        <div class="clearfix mg-t-15 mg-b-15"></div>
        <h6 class="tx-semibold">Diskon Berdasarkan Principle</h6>
        <p class="mg-b-15">Atur diskon khusus untuk setiap principle produk.</p>
        <!-- Hidden field untuk diskon Cendo yang akan menjadi default -->
        <input type="hidden" id="diskon_default" name="diskon" value="0" />
        <div class="row">
            <?php
                $principle = $conn->prepare("SELECT id_mp, nama_principle FROM master_principle ORDER BY nama_principle ASC");
                $principle->execute();
                while($princ = $principle->fetch(PDO::FETCH_ASSOC)){
            ?>
            <div class="form-group col-sm-4">
                <label>Diskon <?php echo($princ['nama_principle']); ?> (%)</label>
                <input type="text" name="diskon_principle[<?php echo($princ['id_mp']); ?>]" class="form-control" placeholder="0" value="0" />
                <small class="text-muted">Diskon untuk produk principle <?php echo($princ['nama_principle']); ?></small>
            </div>
            <?php } ?>
        </div><!-- row -->

                <!-- Program Produk -->
                <div class="row" id="programSection" style="display:none;">
                    <div class="form-group col-sm-12">
                                <label>Program Produk</label>
                                <p class="mg-b-15">Pilih program yang diikuti outlet ini (centang jika ikut).</p>
                                <?php
                                    $prog = $conn->prepare("SELECT id_pp, nama_program, jenis_program, min_qty, diskon_persen FROM master_program_produk WHERE status_program='Active' ORDER BY nama_program ASC");
                                    $prog->execute();
                                    while($p = $prog->fetch(PDO::FETCH_ASSOC)){
                                ?>
                <div class="card border-secondary mb-2" style="background:#fff;">
                    <div class="card-body" style="padding:12px;">
                        <label style="display:flex;align-items:center;gap:12px;margin:0;width:100%;">
                            <div style="flex:0 0 36px;display:flex;align-items:center;justify-content:center;">
                                <input class="form-check-input" type="checkbox" name="program_ids[]" id="prog_<?php echo($p['id_pp']); ?>" value="<?php echo($p['id_pp']); ?>" style="width:20px;height:20px;margin:0;">
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
        <!-- row -->
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <div class="row">
            <div class="col-sm-12">
                <a href="<?php echo("$sistem/outletbaru"); ?>" title="Batal"><button type="button" class="btn btn-secondary btn-xs">Batal</button></a>
                <button type="submit" id="bsave" class="btn btn-dark btn-xs">Ajukan</button>
                <div id="imgloading"></div>
            </div>
		</div>
		</form>
    </div>
</div>

<script>
$(document).ready(function(){
    // Fungsi untuk update diskon default berdasarkan Cendo
    function updateDiskonDefault() {
        var diskonCendo = 0;
        $('input[name^="diskon_principle"]').each(function(){
            var principleId = $(this).attr('name').match(/\[(.*?)\]/)[1];
            var principleValue = $(this).val();
            
            // Cek apakah ini principle Cendo (biasanya MP0000000001)
            // Kita ambil yang pertama sebagai Cendo atau bisa disesuaikan
            if($(this).closest('.form-group').find('label').text().includes('Cendo')){
                diskonCendo = principleValue || 0;
                return false; // break loop
            }
        });
        
        $('#diskon_default').val(diskonCendo);
    }
    
    // Update saat halaman load
    updateDiskonDefault();
    
    // Update saat nilai principle berubah
    $('input[name^="diskon_principle"]').on('input change', function(){
        updateDiskonDefault();
    });

    // Show/hide program section only for kategori 'Apotek'
    function toggleProgramSection(){
        var sel = $('select[name="kategori"]');
        var val = sel.val();
        var text = sel.find('option:selected').text() || '';
        if(text.toLowerCase().includes('apotek')){
            $('#programSection').show();
        } else {
            $('#programSection').hide();
            // uncheck any program checkboxes when hidden
            $('#programSection').find('input[name="program_ids[]"]').prop('checked', false);
        }
    }
    // initial
    toggleProgramSection();
    // on change
    $('select[name="kategori"]').on('change', function(){ toggleProgramSection(); });
});
</script>