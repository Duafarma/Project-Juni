<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Penjualan</a></li>
                <li class="breadcrumb-item active" aria-current="page">Faktur Penjualan</li>
            </ol>
        </nav>
        <h4 class="content-title">Input Data - Faktur Penjualan</h4>
    </div>
</div>
<div class="content-body">
    <div class="component-section no-code">
        <h5 id="section1" class="tx-semibold"><?php echo($data->sistem('pt_sis')); ?></h5>
        <div style="margin-top:10px; margin-bottom:25px;">
            <div>Izin PBF No : <?php echo($data->sistem('pbf_sis')); ?></div>
            <div>NPWP No : <?php echo($data->sistem('npwp_sis')); ?></div>
            <div>Alamat : <?php echo($data->sistem('alamat_sis')); ?></div>
        </div>
        <form id="formsalespnp" action="#" method="post" autocomplete="off">
        <input type="hidden" name="namamodal" id="namamodal" value="fsales" readonly="readonly" />
        <input type="hidden" name="namamenu" id="namamenu" value="faktur" readonly="readonly" />
        <?php $new_keycode = base64_encode('FAK'.time()); ?>
        <input type="hidden" name="keycode" id="keycode" value="<?php echo $new_keycode; ?>" readonly="readonly" />
        
        <!-- Konsinyasi Section - DIPINDAH KE ATAS -->
        <?php 
        // Tampilkan section konsinyasi hanya untuk user dengan akses Bisnis
        $status_admin = $data->myadmin($admin, 'status_adm');
        if($status_admin === 'Bisnis' || $level === 'Super'): 
        ?>
        <div class="row row-sm">
            <div class="col-sm-12 konsinyasi-section-top">
                <div class="alert alert-info" style="background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); border: 2px solid #2196f3; border-radius: 10px; margin-bottom: 20px;">
                    <h5 style="color: #1565c0; margin-bottom: 15px;">
                        <i class="fas fa-question-circle"></i> Apakah SJ ini dari stok konsinyasi?
                    </h5>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="custom-control custom-radio custom-control-lg">
                                <input type="radio" name="dari_konsinyasi" value="tidak" id="konsinyasi_tidak" class="custom-control-input" checked onchange="toggleKonsinyasiMode()">
                                <label class="custom-control-label" for="konsinyasi_tidak" style="font-size: 16px;">
                                    <strong>TIDAK</strong> - Penjualan Normal (Stok Biasa)
                                </label>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="custom-control custom-radio custom-control-lg">
                                <input type="radio" name="dari_konsinyasi" value="ya" id="konsinyasi_ya" class="custom-control-input" onchange="toggleKonsinyasiMode()">
                                <label class="custom-control-label" for="konsinyasi_ya" style="font-size: 16px;">
                                    <strong>YA</strong> - Dari Konsinyasi
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pilih Faktur Konsinyasi (Hidden by default) -->
                    <div id="konsinyasi_list_container" style="display: none; margin-top: 20px;">
                        <div style="background: white; padding: 20px; border-radius: 8px; border: 2px dashed #2196f3;">
                            <?php
                            // Ambil faktur konsinyasi yang masih aktif
                            $konsi_rows    = [];
                            $konsi_outlets = []; // [id_out => nama_out]
                            try {
                                $konsi_query = $conn->prepare("SELECT 
                                    A.id_tfk, 
                                    A.kode_tfk, 
                                    A.sj_tfk, 
                                    A.tgl_tfk,
                                    A.status_tfk,
                                    A.id_out,
                                    B.nama_out
                                FROM transaksi_faktur_konsinyasi AS A
                                LEFT JOIN outlet AS B ON A.id_out = B.id_out
                                WHERE A.status_tfk IN ('Konsinyasi', 'Sebagian')
                                ORDER BY B.nama_out ASC, A.tgl_tfk DESC, A.kode_tfk DESC");
                                $konsi_query->execute();
                                while ($fk = $konsi_query->fetch(PDO::FETCH_ASSOC)) {
                                    $konsi_rows[] = $fk;
                                    if (!isset($konsi_outlets[$fk['id_out']])) {
                                        $konsi_outlets[$fk['id_out']] = $fk['nama_out'];
                                    }
                                }
                            } catch (PDOException $e) {
                                // handled below
                            }
                            ?>

                            <!-- Step 1: Filter Outlet -->
                            <label style="font-weight: 600; color: #1565c0; margin-bottom: 8px; font-size: 16px;">
                                <i class="fas fa-store"></i> 1. Pilih Outlet:
                            </label>
                            <select id="filter_outlet_konsi" class="form-control select2" style="width: 100%; margin-bottom: 18px;">
                                <option value="">-- Pilih Outlet Dulu --</option>
                                <?php foreach ($konsi_outlets as $id_out => $nama_out): ?>
                                <option value="<?php echo htmlspecialchars($id_out); ?>"><?php echo htmlspecialchars($nama_out); ?></option>
                                <?php endforeach; ?>
                            </select>

                            <!-- Step 2: SJ Konsinyasi (difilter berdasarkan outlet) -->
                            <label style="font-weight: 600; color: #1565c0; margin-bottom: 8px; font-size: 18px;">
                                <i class="fas fa-file-invoice"></i> 2. Pilih SJ Konsinyasi:
                            </label>
                            <select name="id_faktur_konsinyasi" id="id_faktur_konsinyasi" class="form-control select2 select2-konsinyasi" onchange="loadKonsinyasiData()" style="width: 100%; font-size: 20px; height: 60px; padding: 15px 20px;">
                                <option value="">-- Pilih Outlet Dulu --</option>
                                <?php if (!empty($konsi_rows)): ?>
                                    <?php foreach ($konsi_rows as $fk): ?>
                                    <option value="<?php echo $fk['id_tfk']; ?>"
                                            data-outlet="<?php echo $fk['id_out']; ?>"
                                            data-kode="<?php echo $fk['kode_tfk']; ?>"
                                            data-sj="<?php echo $fk['sj_tfk']; ?>">
                                        <?php echo $fk['kode_tfk'].' - '.$fk['nama_out'].' ('.date('d/m/Y', strtotime($fk['tgl_tfk'])).')'; ?>
                                    </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="">Tidak ada konsinyasi aktif</option>
                                <?php endif; ?>
                            </select>
                            <small class="form-text text-muted" style="margin-top: 8px;">
                                <i class="fas fa-info-circle"></i> Setelah pilih SJ, <strong>Outlet dan Nomor Faktur akan terisi otomatis</strong>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Form Fields Utama -->
        <div class="row row-sm" id="main-form-fields">
            <div class="col-sm-3">
                <label>Nomor SJ <span class="tx-danger">*</span></label>
                <input type="text" name="invoice" id="koout" class="form-control" placeholder="-" />
            </div>
             <div class="col-sm-3">
                        <div id="outlet-block" style="padding:0; border-radius:6px; position:relative;">
                            <label id="outlet-label">Outlet <span class="tx-danger">*</span> 
                                <small id="outlet-auto-label" style="display:none; color:#2196f3; font-weight:normal;">
                                    <i class="fas fa-magic"></i> (Otomatis dari konsinyasi)
                                </small>
                            </label>
                            <select name="outlet" id="outlet" class="form-control select2" onchange="ceksales()" required="required">
                            <option value="">-- Pilih --</option>
                            <?php
                                $status = 'Active';
                                $master = $conn->prepare("SELECT id_out, kode_out, nama_out FROM outlet WHERE status_out=:status ORDER BY nama_out ASC");
                                $master->bindParam(':status', $status, PDO::PARAM_STR);
                                $master->execute();
                                while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
                            ?>
                            <option value="<?php echo($hasil['id_out']); ?>" data-nama="<?php echo htmlspecialchars($hasil['nama_out'], ENT_QUOTES); ?>"><?php echo($hasil['nama_out']); ?></option>
                            <?php } ?>
                            </select>
                            <span id="outlet-xicon" style="display:none; position:absolute; right:10px; top:50%; transform:translateY(-50%); color:#c82333; font-size:22px; pointer-events:none;">&#10006;</span>
                            <div id="outlet-warning" style="display:none; color:#c82333; margin-top:5px;"></div>
                        </div>
                </div>
            <div class="col-sm-3">
                <label>Nomor Faktur <span class="tx-danger">*</span>
                    <small id="faktur-auto-label" style="display:none; color:#2196f3; font-weight:normal;">
                        <i class="fas fa-magic"></i> (Otomatis dari konsinyasi)
                    </small>
                </label>
                <input type="text" name="nomorfaktur" id="fkout" class="form-control" placeholder="Ketik nomor faktur di sini..." />
            </div>
            <div class="col-sm-3">
                <label>Tanggal Faktur <span class="tx-danger">*</span></label>
                <input type="text" name="tglfaktur" class="form-control datepicker" value="<?php echo(date('Y-m-d')); ?>" placeholder="9999-99-99" />
            </div>
            <div class="col-sm-3 mg-t-10">
                <label>Tanggal SJ <span class="tx-danger">*</span></label>
                <input type="text" name="tglsales" class="form-control datepicker" value="<?php echo(date('Y-m-d')); ?>" placeholder="9999-99-99" />
            </div>
            <div class="col-sm-3 mg-t-10">
                <label>Nomor PO <span class="tx-danger">*</span></label>
                <input type="text" name="nomorpo" class="form-control" placeholder="Ketik nomor po di sini..." />
            </div>
            <div class="col-sm-3 mg-t-10">
                <label>Tanggal PO <span class="tx-danger">*</span></label>
                <input type="text" name="tglpo" class="form-control datepicker" value="<?php echo(date('Y-m-d')); ?>" placeholder="9999-99-99" />
            </div>
            <div class="col-sm-3 mg-t-10">
                <label>Jatuh Tempo<span class="tx-danger">*</span></label>
                <input type="text" name="jatuhtempo" id="jatuhtempo" class="form-control datepicker" placeholder="9999-99-99" required="required" />
            </div>
            <div class="col-sm-3 mg-t-10">
                <label> CCP</label> <span class="tx-danger">*</span></label> <br>
                <div class="custom-control custom-radio">
                    <input type="radio" name="ccp" value="ada" id="ccp_ada" class="custom-control-input">
                    <label class="custom-control-label" for="ccp_ada">Ada</label>
                </div>
                <div class="custom-control custom-radio">
                    <input type="radio" name="ccp" value="tidak ada" id="ccp_tidak" class="custom-control-input" checked>
                    <label class="custom-control-label" for="ccp_tidak">Tidak Ada</label>
                </div>
            </div>
             <div class="col-sm-3 mg-t-10">
                <label> Cito</label> <span class="tx-danger">*</span></label> <br>
                <div class="custom-control custom-radio">
                    <input type="radio" name="cito" value="cito" id="cito_ya" class="custom-control-input">
                    <label class="custom-control-label" for="cito_ya">Ya</label>
                </div>
                <div class="custom-control custom-radio">
                    <input type="radio" name="cito" value="ga cito" id="cito_tidak" class="custom-control-input" checked>
                    <label class="custom-control-label" for="cito_tidak">Tidak</label>
                </div>
            </div>

            <?php
                // Ambil data master_mr_baru untuk dropdown MR
                $mr_options = [];
                try {
                    $mr_query = $conn->query("SELECT id_mr, nama_mr, area FROM master_mr_baru ORDER BY nama_mr ASC");
                    if($mr_query) $mr_options = $mr_query->fetchAll(PDO::FETCH_ASSOC);
                } catch(Exception $e) { $mr_options = []; }
            ?>

            <!-- Kolom MR (muncul di sebelah Cito saat outlet tertentu dipilih) -->
            <div class="col-sm-3 mg-t-10 section-mr-col" id="section_mr_info" style="display:none;">
                <label style="font-weight:600; color:#856404;"><i class="fas fa-user-tie"></i> MR <span class="tx-danger">*</span></label>
                <select name="id_mr" id="id_mr" class="form-control select2-mr" style="width:100%;">
                    <option value="">-- Pilih MR --</option>
                    <?php foreach($mr_options as $mr): ?>
                    <option value="<?php echo $mr['id_mr']; ?>"><?php echo htmlspecialchars($mr['nama_mr']); ?> (<?php echo htmlspecialchars($mr['area']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <!-- Kolom Keterangan MR -->
            <div class="col-sm-3 mg-t-10 section-mr-col" id="section_ket_mr" style="display:none;">
                <label style="font-weight:600; color:#856404;"><i class="fas fa-sticky-note"></i> Keterangan MR</label>
                <input type="text" name="ket_mr" id="ket_mr" class="form-control" placeholder="Keterangan tambahan..." />
            </div>

            <!-- Garis pemisah -->
            <div class="col-sm-12 mg-t-20 mg-b-15">
                <hr style="border: 2px solid #007bff; margin: 15px 0;">
            </div>
            
            <!-- Program Promo Section -->
            <div class="col-sm-12 program-promo-section">
                <div class="row">
                    <div class="col-sm-12 mg-b-15">
                        <label style="font-weight: bold; color: #007bff; font-size: 16px;">
                            <i class="fas fa-tags"></i> Apakah faktur mengikuti program promo?
                        </label>
                    </div>
                    
                    <div class="col-sm-12 mg-b-15">
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="custom-control custom-radio">
                                    <input type="radio" name="program_promo" value="tidak" id="program_promo_tidak" class="custom-control-input" checked onchange="toggleProgramList()">
                                    <label class="custom-control-label" for="program_promo_tidak">
                                        <strong>Tidak</strong> (Langsung ke Penjualan)
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="custom-control custom-radio">
                                    <input type="radio" name="program_promo" value="ya" id="program_promo_ya" class="custom-control-input" onchange="toggleProgramList()">
                                    <label class="custom-control-label" for="program_promo_ya">
                                        <strong>Ya</strong> (Pilih Program)
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- List Program (Hidden by default) -->
                    <div class="col-sm-12" id="program_list_container" style="display: none;">
                        <label style="font-weight: 600; color: #495057; margin-bottom: 15px;">
                            <i class="fas fa-list-check"></i> Pilih Program Promo:
                        </label>
                        <div class="row">
                            <?php
                            try {
                                $program_query = $conn->prepare("SELECT * FROM program_promo WHERE status_program = 'Active' ORDER BY urutan ASC, nama_program ASC");
                                $program_query->execute();
                                $programs = $program_query->fetchAll(PDO::FETCH_ASSOC);
                                
                                if(count($programs) > 0) {
                                    foreach($programs as $prog) {
                                        $kode = $prog['kode_program'];
                                        $icon = isset($prog['icon_class']) ? $prog['icon_class'] : 'fas fa-tag';
                                        echo '<div class="col-sm-6 mg-b-10 program-checkbox">';
                                        echo '<div class="custom-control custom-checkbox">';
                                        echo '<input type="checkbox" name="selected_programs[]" value="'.$kode.'" id="prog_'.str_replace('program_', '', $kode).'" class="custom-control-input">';
                                        echo '<label class="custom-control-label" for="prog_'.str_replace('program_', '', $kode).'"><i class="'.$icon.'"></i> '.$prog['nama_program'].'</label>';
                                        echo '</div>';
                                        echo '</div>';
                                    }
                                } else {
                                    echo '<div class="col-sm-12"><div class="alert alert-info"><i class="fas fa-info-circle"></i> Tidak ada program promo yang tersedia saat ini.</div></div>';
                                }
                            } catch(PDOException $e) {
                                echo '<div class="col-sm-6 mg-b-10 program-checkbox">';
                                echo '<div class="custom-control custom-checkbox">';
                                echo '<input type="checkbox" name="selected_programs[]" value="program_vb" id="prog_vb" class="custom-control-input">';
                                echo '<label class="custom-control-label" for="prog_vb"><i class="fas fa-percentage"></i> Program VB (Volume Bonus)</label>';
                                echo '</div>';
                                echo '</div>';
                                
                                echo '<div class="col-sm-6 mg-b-10 program-checkbox">';
                                echo '<div class="custom-control custom-checkbox">';
                                echo '<input type="checkbox" name="selected_programs[]" value="program_diskon" id="prog_diskon" class="custom-control-input">';
                                echo '<label class="custom-control-label" for="prog_diskon"><i class="fas fa-tag"></i> Program Diskon Khusus</label>';
                                echo '</div>';
                                echo '</div>';
                                
                                echo '<div class="col-sm-6 mg-b-10 program-checkbox">';
                                echo '<div class="custom-control custom-checkbox">';
                                echo '<input type="checkbox" name="selected_programs[]" value="program_cashback" id="prog_cashback" class="custom-control-input">';
                                echo '<label class="custom-control-label" for="prog_cashback"><i class="fas fa-money-bill-wave"></i> Program Cashback</label>';
                                echo '</div>';
                                echo '</div>';
                                
                                echo '<div class="col-sm-6 mg-b-10 program-checkbox">';
                                echo '<div class="custom-control custom-checkbox">';
                                echo '<input type="checkbox" name="selected_programs[]" value="program_bundling" id="prog_bundling" class="custom-control-input">';
                                echo '<label class="custom-control-label" for="prog_bundling"><i class="fas fa-box-open"></i> Program Bundling</label>';
                                echo '</div>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            
        </div><!-- row -->
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <div class="row row-sm">
            <div class="col-sm-12">
                <a href="<?php echo("$sistem/fsales"); ?>" title="Batal"><button type="button" class="btn btn-secondary">Batal</button></a>
                <button type="submit" id="bsave" class="btn btn-dark">Simpan</button>
                <div id="imgloading"></div>
            </div>
		</div>
		</form>
    </div>
</div>

<script>
// Simpan semua options konsinyasi di sini agar bisa di-filter ulang
var allKonsiOptions = [];

function filterKonsinyasiByOutlet() {
    var outletId   = document.getElementById('filter_outlet_konsi').value;
    var konsiEl    = document.getElementById('id_faktur_konsinyasi');
    var outletEl   = document.getElementById('outlet');
    var fkoutEl    = document.getElementById('fkout');

    // Reset pilihan SJ dan autofill
    konsiEl.value = '';
    if (outletEl) {
        outletEl.value = '';
        if (typeof $ !== 'undefined' && $.fn.select2) {
            $(outletEl).val('').trigger('change');
        }
    }
    if (fkoutEl) fkoutEl.value = '';

    // Repopulate select berdasarkan outlet yang dipilih
    konsiEl.innerHTML = '';
    allKonsiOptions.forEach(function(opt) {
        if (opt.value === '') {
            // Placeholder: ganti teks sesuai kondisi
            var ph = document.createElement('option');
            ph.value = '';
            ph.text  = outletId ? '-- Pilih SJ Konsinyasi --' : '-- Pilih Outlet Dulu --';
            konsiEl.appendChild(ph);
            return;
        }
        if (!outletId || opt.outlet === outletId) {
            var newOpt = document.createElement('option');
            newOpt.value = opt.value;
            newOpt.text  = opt.text;
            newOpt.setAttribute('data-outlet', opt.outlet);
            newOpt.setAttribute('data-kode',   opt.kode);
            newOpt.setAttribute('data-sj',     opt.sj);
            konsiEl.appendChild(newOpt);
        }
    });

    // Re-init select2 agar dropdown terupdate
    if (typeof $ !== 'undefined' && $.fn.select2) {
        try { $('#id_faktur_konsinyasi').select2('destroy'); } catch(e) {}
        $('#id_faktur_konsinyasi').select2();
    }
}

function toggleKonsinyasiMode() {
    console.log('toggleKonsinyasiMode called');
    
    const konsinyasiYa = document.getElementById('konsinyasi_ya');
    const konsinyasiListContainer = document.getElementById('konsinyasi_list_container');
    const outletSelect = document.getElementById('outlet');
    const nomorFakturInput = document.getElementById('fkout');
    const outletAutoLabel = document.getElementById('outlet-auto-label');
    const fakturAutoLabel = document.getElementById('faktur-auto-label');
    
    console.log('konsinyasiYa.checked:', konsinyasiYa.checked);
    console.log('konsinyasiListContainer:', konsinyasiListContainer);
    
    if (konsinyasiYa.checked) {
        console.log('Mode konsinyasi AKTIF');
        // Mode konsinyasi
        konsinyasiListContainer.style.display = 'block';
        konsinyasiListContainer.style.opacity = '0';
        konsinyasiListContainer.style.transform = 'translateY(-10px)';
        setTimeout(() => {
            konsinyasiListContainer.style.transition = 'all 0.3s ease';
            konsinyasiListContainer.style.opacity = '1';
            konsinyasiListContainer.style.transform = 'translateY(0)';
        }, 10);
        
        // Show auto labels
        if(outletAutoLabel) outletAutoLabel.style.display = 'inline';
        if(fakturAutoLabel) fakturAutoLabel.style.display = 'inline';
        
        // JANGAN disable outlet (agar value tetap ter-submit), tapi buat tidak bisa diklik
        outletSelect.style.pointerEvents = 'none';
        outletSelect.style.background = '#e9ecef';
        outletSelect.style.opacity = '0.8';
        outletSelect.style.cursor = 'not-allowed';
        
        nomorFakturInput.setAttribute('readonly', 'readonly');
        nomorFakturInput.style.background = '#e9ecef';
        nomorFakturInput.placeholder = 'Akan terisi otomatis...';
        
        console.log('Outlet set to readonly with pointer-events:none');
        
    } else {
        // Mode normal
        konsinyasiListContainer.style.display = 'none';
        document.getElementById('id_faktur_konsinyasi').value = '';
        // Reset outlet filter konsinyasi
        var filterOutlet = document.getElementById('filter_outlet_konsi');
        if (filterOutlet) {
            filterOutlet.value = '';
            if (typeof $ !== 'undefined' && $.fn.select2) {
                $(filterOutlet).val('').trigger('change.select2');
            }
        }
        // Kembalikan options SJ ke semua data (tanpa filter)
        filterKonsinyasiByOutlet();
        
        // Hide auto labels
        if(outletAutoLabel) outletAutoLabel.style.display = 'none';
        if(fakturAutoLabel) fakturAutoLabel.style.display = 'none';
        
        // Enable kembali outlet dan nomor faktur
        outletSelect.style.pointerEvents = 'auto';
        outletSelect.style.background = '';
        outletSelect.style.opacity = '1';
        outletSelect.style.cursor = 'pointer';
        
        nomorFakturInput.removeAttribute('readonly');
        nomorFakturInput.style.background = '';
        nomorFakturInput.placeholder = 'Ketik nomor faktur di sini...';
        outletSelect.value = '';
        nomorFakturInput.value = '';
    }
}

function loadKonsinyasiData() {
    const select = document.getElementById('id_faktur_konsinyasi');
    const selectedOption = select.options[select.selectedIndex];
    
    console.log('loadKonsinyasiData called, selected value:', select.value);
    
    if (select.value) {
        // Ambil data dari option attribute
        const outletId = selectedOption.getAttribute('data-outlet');
        const kodeFaktur = selectedOption.getAttribute('data-kode');
        const sjKonsi = selectedOption.getAttribute('data-sj');
        
        console.log('outletId from konsinyasi:', outletId);
        console.log('kodeFaktur from konsinyasi:', kodeFaktur);
        
        // Set outlet select LANGSUNG (JANGAN gunakan hidden field atau disabled)
        const outletSelect = document.getElementById('outlet');
        outletSelect.value = outletId;
        
        // Trigger select2 update jika ada
        if (typeof $ !== 'undefined' && $.fn.select2) {
            $(outletSelect).val(outletId).trigger('change');
        }
        
        // Buat tidak bisa diklik tapi TETAP enabled (agar value ter-submit)
        outletSelect.style.pointerEvents = 'none';
        outletSelect.style.background = '#e9ecef';
        outletSelect.style.opacity = '0.8';
        outletSelect.style.cursor = 'not-allowed';
        
        console.log('Outlet value set to:', outletSelect.value);
        
        // Set nomor faktur dengan prefix
        const nomorFakturInput = document.getElementById('fkout');
        nomorFakturInput.value = 'PNJ/' + kodeFaktur;
        
        // Optional: Set nomor SJ jika mau sama
        // document.getElementById('koout').value = sjKonsi;
        
        // Show success message
        if (typeof swal !== 'undefined') {
            swal({
                title: "Data Terisi!",
                text: "Outlet dan Nomor Faktur sudah terisi otomatis dari konsinyasi",
                icon: "success",
                timer: 2000,
                buttons: false
            });
        }
    } else {
        // Reset jika dikosongkan
        document.getElementById('outlet').value = '';
        document.getElementById('fkout').value = '';
    }
}

function toggleProgramList() {
    const programYa = document.getElementById('program_promo_ya');
    const programListContainer = document.getElementById('program_list_container');
    
    if (programYa.checked) {
        programListContainer.style.display = 'block';
        programListContainer.style.opacity = '0';
        programListContainer.style.transform = 'translateY(-10px)';
        setTimeout(() => {
            programListContainer.style.transition = 'all 0.3s ease';
            programListContainer.style.opacity = '1';
            programListContainer.style.transform = 'translateY(0)';
        }, 10);
    } else {
        programListContainer.style.display = 'none';
        const checkboxes = document.querySelectorAll('input[name="selected_programs[]"]');
        checkboxes.forEach(cb => cb.checked = false);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Simpan semua options konsinyasi sebelum di-filter
    var konsiEl = document.getElementById('id_faktur_konsinyasi');
    if (konsiEl) {
        Array.from(konsiEl.options).forEach(function(opt) {
            allKonsiOptions.push({
                value : opt.value,
                text  : opt.text,
                outlet: opt.getAttribute('data-outlet') || '',
                kode  : opt.getAttribute('data-kode')   || '',
                sj    : opt.getAttribute('data-sj')     || ''
            });
        });
    }

    // Init select2 pada filter outlet konsinyasi (searchable)
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $('#filter_outlet_konsi').select2({
            placeholder: '-- Pilih Outlet Dulu --',
            allowClear: true,
            width: '100%'
        }).on('change', function() {
            filterKonsinyasiByOutlet();
        });
    }

    // Force reset form fields on load to prevent browser cache keeping old values
    var server_keycode = "<?php echo $new_keycode; ?>";
    if (document.getElementById('keycode')) {
        document.getElementById('keycode').value = server_keycode;
    }
    if (document.getElementById('koout')) {
        document.getElementById('koout').value = '';
    }
    if (document.getElementById('fkout')) {
        document.getElementById('fkout').value = '';
    }
    if (document.getElementById('outlet')) {
        document.getElementById('outlet').value = '';
        if (typeof $ !== 'undefined' && $.fn.select2) {
            $('#outlet').val('').trigger('change.select2');
        }
    }

    const style = document.createElement('style');
    style.textContent = `
        #program_list_container {
            background: #f8f9fa;
            border: 2px dashed #007bff;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
        }
        
        #program_list_container label {
            font-weight: 500;
            color: #495057;
        }
        
        .program-promo-section {
            background: linear-gradient(135deg, #f8f9ff 0%, #e3f2fd 100%);
            padding: 20px;
            border-radius: 10px;
            margin: 15px 0;
            border: 1px solid #e3f2fd;
        }
        
        input[type="checkbox"], input[type="radio"] {
            transform: scale(1.2);
            margin-right: 8px;
        }
        
        .program-checkbox:hover {
            background: #e8f4fd;
            border-radius: 5px;
            padding: 5px;
        }
        
        /* CSS untuk dropdown konsinyasi agar lebih besar */
        .select2-konsinyasi + .select2-container {
            width: 100% !important;
        }
        
        .select2-konsinyasi + .select2-container .select2-selection {
            height: 60px !important;
            padding: 15px 20px !important;
            font-size: 20px !important;
            line-height: 30px !important;
            width: 100% !important;
        }
        
        .select2-konsinyasi + .select2-container .select2-selection__rendered {
            line-height: 30px !important;
            font-size: 20px !important;
        }
        
        .select2-konsinyasi + .select2-container .select2-selection__arrow {
            height: 58px !important;
        }
        
        /* Dropdown list yang muncul - perlebar */
        .select2-container--default.select2-container--open .select2-dropdown {
            min-width: 600px !important;
            width: auto !important;
        }
        
        .select2-container--default .select2-results__option {
            font-size: 18px !important;
            padding: 15px 20px !important;
            line-height: 1.5 !important;
            white-space: normal !important;
            word-wrap: break-word !important;
        }
        
        .select2-container--default .select2-results__option--highlighted {
            background-color: #2196f3 !important;
        }
        
        .select2-container--default .select2-search--dropdown .select2-search__field {
            font-size: 18px !important;
            padding: 10px 15px !important;
        }
    `;
    document.head.appendChild(style);
});
// Toggle section MR berdasarkan outlet yang dipilih (deteksi via nama option)
function checkOutletMR() {
    var outletEl   = document.getElementById('outlet');
    var section    = document.getElementById('section_mr_info');
    var sectionKet = document.getElementById('section_ket_mr');
    var idMr       = document.getElementById('id_mr');
    var ketMr      = document.getElementById('ket_mr');
    if (!section || !outletEl) return;

    var selectedOpt = outletEl.options[outletEl.selectedIndex];
    var namaNow = selectedOpt ? (selectedOpt.getAttribute('data-nama') || selectedOpt.text || '') : '';
    var namaNormalized = namaNow.toUpperCase();

    var tampil = (namaNormalized.indexOf('MUDITA PHARMA') !== -1 || namaNormalized.indexOf('JALI FARMA') !== -1);

    if (tampil) {
        section.style.display = 'block';
        if (sectionKet) sectionKet.style.display = 'block';
        // init select2 MR
        if (typeof $ !== 'undefined' && $.fn.select2 && idMr) {
            try {
                if (!$(idMr).data('select2')) {
                    $(idMr).select2({ width: '100%', placeholder: '-- Pilih MR --', allowClear: true });
                }
            } catch(e) {}
        }
    } else {
        section.style.display = 'none';
        if (sectionKet) sectionKet.style.display = 'none';
        if (idMr) { idMr.value = ''; if (typeof $ !== 'undefined' && $.fn.select2) try { $(idMr).val('').trigger('change.select2'); } catch(e){} }
        if (ketMr) ketMr.value = '';
    }
}

// Pasang event — harus setelah DOM siap
document.addEventListener('DOMContentLoaded', function() {
    var outletEl = document.getElementById('outlet');
    if (outletEl) {
        outletEl.addEventListener('change', checkOutletMR);
        if (typeof $ !== 'undefined' && $.fn.select2) {
            $(outletEl).on('select2:select select2:unselect', checkOutletMR);
        }
    }
});

// Hapus booking nomor faktur otomatis saat halaman di-refresh atau ditutup
window.addEventListener('beforeunload', function() {
    var keycode = document.getElementById("keycode") ? document.getElementById("keycode").value : $("input[name='keycode']").val();
    var usuper_val = (typeof usuper !== 'undefined') ? usuper : '';
    if (keycode && keycode !== '') {
        navigator.sendBeacon(usuper_val + '/ajax/ceksales/hapus_booking.php', new URLSearchParams({ keycode: keycode }));
    }
});
</script>
