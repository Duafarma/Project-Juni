<?php
    // gunakan __DIR__ untuk path yang konsisten
    require_once(__DIR__ . '/../../config/connection/connection.php');
    require_once(__DIR__ . '/../../config/connection/security.php');
    require_once(__DIR__ . '/../../config/function/data.php');
    $secu	= new Security;
    $base	= new DB;
    $data	= new Data;
    $conn	= $base->open();
    $catat	= date('Y-m-d H:i:s');
    $admin	= $secu->injection(@$_COOKIE['adminkuy']);
?>
<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Menu</a></li>
                <li class="breadcrumb-item"><a href="#">Master Data</a></li>
                <li class="breadcrumb-item active" aria-current="page">Tambah Rekening ke Outlet</li>
            </ol>
        </nav>
        <h4 class="content-title">Tambah Rekening ke Outlet</h4>
    </div>
</div>

<div class="content-body">
    <div class="row mg-b-10">
        <div class="col-sm-12">
            <a href="<?php echo($data->sistem('url_sis').'/masterrekening'); ?>" class="btn btn-secondary btn-xs"><i class="fa fa-arrow-left"></i> Kembali</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form id="formAssignRek" autocomplete="off">
                <!-- Mode Selection -->
                <div class="row mb-3">
                    <div class="form-group col-md-12">
                        <label>Mode Assignment <span class="tx-danger">*</span></label>
                        <div class="mt-2">
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" class="custom-control-input" id="modeOutlet" name="assignment_mode" value="outlet" checked>
                                <label class="custom-control-label" for="modeOutlet">Per Outlet</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" class="custom-control-input" id="modeGrup" name="assignment_mode" value="grup">
                                <label class="custom-control-label" for="modeGrup">Per Grup</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Dropdown Outlet -->
                    <div class="form-group col-md-6" id="divOutlet">
                        <label>Outlet <span class="tx-danger">*</span></label>
                        <select name="id_out" id="id_out" class="form-control select2" style="width:100%;">
                            <option value="">-- Pilih Outlet --</option>
                            <?php
                                $stmt = $conn->query("SELECT id_out, nama_out FROM outlet ORDER BY nama_out ASC");
                                while($o = $stmt->fetch(PDO::FETCH_ASSOC)){
                                    echo '<option value="'.htmlspecialchars($o['id_out']).'">'.htmlspecialchars($o['nama_out']).'</option>';
                                }
                            ?>
                        </select>
                    </div>
    
                    <!-- Dropdown Grup (hidden by default) -->
                    <div class="form-group col-md-6" id="divGrup" style="display: none;">
                        <label>Grup <span class="tx-danger">*</span></label>
                        <select name="id_mg" id="id_mg" class="form-control select2" style="width:100%;">
                            <option value="">-- Pilih Grup --</option>
                            <?php
                                $stmt3 = $conn->query("SELECT id_mg, nama_mg FROM master_grup ORDER BY nama_mg ASC");
                                while($g = $stmt3->fetch(PDO::FETCH_ASSOC)){
                                    echo '<option value="'.htmlspecialchars($g['id_mg']).'">'.htmlspecialchars($g['nama_mg']).'</option>';
                                }
                            ?>
                        </select>
                    </div>
    
                    <div class="form-group col-md-6">
                        <label>Rekening (Bank) <span class="tx-danger">*</span></label>
                        <select name="id_rek" id="id_rek" class="form-control select2" style="width:100%;" required>
                            <option value="">-- Pilih Rekening --</option>
                            <?php
                                $stmt2 = $conn->query("SELECT id_rek, nama_rekening, nomor_rekening, atas_nama FROM master_rekening ORDER BY nama_rekening ASC");
                                while($r = $stmt2->fetch(PDO::FETCH_ASSOC)){
                                    echo '<option value="'.htmlspecialchars($r['id_rek']).'">'.htmlspecialchars($r['nama_rekening'].' - '.$r['nomor_rekening'].' - '.$r['atas_nama']).'</option>';
                                }
                            ?>
                        </select>
                    </div>
                </div>
    
                <div class="form-group">
                    <button type="submit" id="btnSave" class="btn btn-success btn-xs"><i class="fa fa-save"></i> Simpan</button>
                    <button type="button" id="btnCancelEdit" class="btn btn-outline-secondary btn-xs" style="display:none;">Batal Edit</button>
                </div>
            </form>
    
            <div id="result" style="display:none;" class="alert mt-2"></div>
    
            <div class="card mt-2">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Daftar Outlet - Rekening</h6>
                        <form id="searchForm" class="form-inline mb-3">
                            <div class="input-group input-group-sm">
                                <input type="text" id="search_q" class="form-control" placeholder="Cari Nama atau ID Outlet" />
                                <div class="input-group-append">
                                    <button type="button" id="btnSearch" class="btn btn-primary">Cari</button>
                                    <button type="button" id="btnClearSearch" class="btn btn-outline-secondary">Reset</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover" id="tblOutletRek">
                            <thead>
                                <tr>
                                    <th style="width:60px;"><center>No</center></th>
                                    <th>Outlet</th>
                                    <th>Rekening (Bank)</th>
                                    <th>Nomor Rekening</th>
                                    <th>Atas Nama</th>
                                    <th><center>Action</center></th>
                                </tr>
                            </thead>
                            <tbody id="tbodyOutletRek">
                                <!-- akan diisi oleh JS -->
                            </tbody>
                        </table>
                    </div>
                    <div class="mg-t-10">
                        <nav aria-label="Page navigation">
                            <ul class="pagination pagination-circle mg-b-0" id="pagerOutletRek"></ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function(){
        const form = document.getElementById('formAssignRek');
        const resultBox = document.getElementById('result');
        const tbody = document.getElementById('tbodyOutletRek');
        const pager = document.getElementById('pagerOutletRek');

        const modeOutlet = document.getElementById('modeOutlet');
        const modeGrup = document.getElementById('modeGrup');
        const divOutlet = document.getElementById('divOutlet');
        const divGrup = document.getElementById('divGrup');
        const selOut = document.getElementById('id_out');
        const selGrup = document.getElementById('id_mg');
        const selRek = document.getElementById('id_rek');
        const searchQ = document.getElementById('search_q');
        const btnSearch = document.getElementById('btnSearch');
        const btnClearSearch = document.getElementById('btnClearSearch');
        const btnSave = document.getElementById('btnSave');
        const btnCancelEdit = document.getElementById('btnCancelEdit');

        const pageSize = 10;
        let currentPage = 1;
        let isEditMode = false;

        // Toggle mode assignment
        modeOutlet.addEventListener('change', function(){
            if(this.checked){
                divOutlet.style.display = 'block';
                divGrup.style.display = 'none';
                selOut.required = true;
                selGrup.required = false;
                if (window.jQuery && jQuery.fn.select2){
                    jQuery(selGrup).val('').trigger('change');
                }
                cancelEdit(); // keluar dari mode edit saat ganti mode
            }
        });

        modeGrup.addEventListener('change', function(){
            if(this.checked){
                divOutlet.style.display = 'none';
                divGrup.style.display = 'block';
                selOut.required = false;
                selGrup.required = true;
                if (window.jQuery && jQuery.fn.select2){
                    jQuery(selOut).val('').trigger('change');
                }
                cancelEdit(); // keluar dari mode edit saat ganti mode
            }
        });

        function setResult(type, msg){
            resultBox.style.display = 'block';
            resultBox.className = 'alert alert-' + type;
            resultBox.innerText = msg;
        }

        function clearResult(){
            resultBox.style.display = 'none';
            resultBox.className = 'alert';
            resultBox.innerText = '';
        }

        function renderPager(paginationHtml){
            pager.innerHTML = paginationHtml || '';
        }

        function loadOutletRek(page = 1){
            currentPage = page;
            tbody.innerHTML = '<tr><td colspan="6">Memuat...</td></tr>';
            const baseUrl = '<?php echo $data->sistem("url_sis"); ?>/modal/masterrekening/action.php?act=list_outlet';
            const params = new URLSearchParams();
            params.set('page', page);
            params.set('limit', pageSize);
            if (searchQ && searchQ.value.trim() !== '') params.set('q', searchQ.value.trim());
            const urlList = baseUrl + '&' + params.toString();
            fetch(urlList, { method: 'GET', credentials: 'same-origin' })
            .then(r => r.json())
            .then(data => {
                if ((!data.rows || data.rows === '') && page > 1 && data.total !== undefined && ((page - 1) * pageSize) >= data.total) {
                    loadOutletRek(page - 1);
                    return;
                }
                tbody.innerHTML = data.rows || '<tr><td colspan="6">Tidak ada data.</td></tr>';
                renderPager(data.pagination);
            })
            .catch(() => {
                tbody.innerHTML = '<tr><td colspan="6">Error memuat data.</td></tr>';
                renderPager('');
            });
        }

        pager.addEventListener('click', function(e){
            const el = e.target.closest('[data-page]');
            if(el){
                e.preventDefault();
                const page = parseInt(el.getAttribute('data-page'), 10);
                if(!isNaN(page)){
                    loadOutletRek(page);
                }
            }
        });

        form.addEventListener('submit', function(e){
            e.preventDefault();
            btnSave.disabled = true;
            btnSave.innerText = isEditMode ? 'Mengupdate...' : 'Menyimpan...';

            const formData = new FormData(form);
            
            // tentukan mode berdasarkan radio button
            const mode = modeGrup.checked ? 'grup' : 'outlet';
            formData.set('assignment_mode', mode);

            if(isEditMode){
                formData.set('id_out', selOut.value);
            }

            const url = '<?php echo $data->sistem("url_sis"); ?>/modal/masterrekening/action.php?act=outlet';
            fetch(url, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(r => r.text())
            .then(text => {
                btnSave.disabled = false;
                btnSave.innerHTML = '<i class="fa fa-save"></i> ' + (isEditMode ? 'Update' : 'Simpan');
                if(text.trim() === 'success'){
                    setResult('success', isEditMode ? 'Berhasil diupdate.' : 'Berhasil menyimpan.');
                    let successMsg = 'Data Berhasil di Simpan';
                    if(isEditMode){
                        successMsg = 'Data Berhasil di Update';
                    } else if(mode === 'grup'){
                        successMsg = 'Data Berhasil di Simpan untuk Semua Outlet dalam Grup';
                    }
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: successMsg,
                        confirmButtonText: 'OK'
                    });
                    loadOutletRek(currentPage);
                    cancelEdit();
                    form.reset();
                    // reset ke mode outlet setelah save
                    modeOutlet.checked = true;
                    divOutlet.style.display = 'block';
                    divGrup.style.display = 'none';
                    selOut.required = true;
                    selGrup.required = false;
                    if (window.jQuery && jQuery.fn.select2){
                        jQuery(selOut).val('').trigger('change');
                        jQuery(selGrup).val('').trigger('change');
                        jQuery(selRek).val('').trigger('change');
                    }
                } else {
                    setResult('danger', 'Terjadi kesalahan: ' + text);
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: isEditMode ? ('Data Gagal di Update ('+text+')') : ('Data Gagal di Simpan ('+text+')'),
                        confirmButtonText: 'Tutup'
                    });
                }
            })
            .catch(() => {
                btnSave.disabled = false;
                btnSave.innerHTML = '<i class="fa fa-save"></i> ' + (isEditMode ? 'Update' : 'Simpan');
                setResult('danger', 'Network error');
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: isEditMode ? 'Data Gagal di Update (Network error)' : 'Data Gagal di Simpan (Network error)',
                    confirmButtonText: 'Tutup'
                });
            });
        });

        form.addEventListener('reset', function(){
            setTimeout(cancelEdit, 0);
            clearResult();
        });

        // fungsi hapus mapping (SweetAlert2 confirm)
        window.deleteOutlet = function(id_out){
            Swal.fire({
                title: 'Hapus Data?',
                text: 'Hapus mapping untuk outlet ' + id_out + ' ?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal',
            }).then((result) => {
                if (!result.isConfirmed) return;

                const fd = new FormData();
                fd.append('id_out', id_out);
                const url = '<?php echo $data->sistem("url_sis"); ?>/modal/masterrekening/action.php?act=delete_outlet';
                fetch(url, {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin'
                })
                .then(r => r.text())
                .then(text => {
                    if(text.trim() === 'success'){
                        setResult('success', 'Berhasil dihapus.');
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: 'Data Berhasil di Hapus',
                            confirmButtonText: 'OK'
                        });
                        loadOutletRek(currentPage);
                    } else {
                        setResult('danger', 'Gagal menghapus: ' + text);
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: 'Data Gagal di Hapus ('+text+')',
                            confirmButtonText: 'Tutup'
                        });
                    }
                })
                .catch(() => {
                    setResult('danger', 'Network error');
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Data Gagal di Hapus (Network error)',
                        confirmButtonText: 'Tutup'
                    });
                });
            });
        }

        // fungsi edit: set dropdown sesuai data baris
        window.editOutlet = function(id_out, id_rek){
            isEditMode = true;
            // mode edit hanya untuk per outlet
            modeOutlet.checked = true;
            divOutlet.style.display = 'block';
            divGrup.style.display = 'none';
            selOut.required = true;
            selGrup.required = false;
            
            if (window.jQuery && jQuery.fn.select2){
                jQuery(selOut).val(id_out).trigger('change');
                jQuery(selRek).val(id_rek).trigger('change');
                jQuery(selOut).prop('disabled', true);
                // Disable radio buttons saat mode edit
                jQuery('#modeOutlet').prop('disabled', true);
                jQuery('#modeGrup').prop('disabled', true);
            } else {
                selOut.value = id_out;
                selRek.value = id_rek;
                selOut.disabled = true;
                // Disable radio buttons saat mode edit
                modeOutlet.disabled = true;
                modeGrup.disabled = true;
            }
            btnSave.innerHTML = '<i class="fa fa-save"></i> Update';
            btnCancelEdit.style.display = 'inline-block';
            setResult('info', 'Mode edit untuk Outlet: ' + id_out);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function cancelEdit(){
            isEditMode = false;
            if (window.jQuery && jQuery.fn.select2){
                jQuery(selOut).prop('disabled', false);
                // Enable kembali radio buttons saat keluar dari mode edit
                jQuery('#modeOutlet').prop('disabled', false);
                jQuery('#modeGrup').prop('disabled', false);
            } else {
                selOut.disabled = false;
                // Enable kembali radio buttons saat keluar dari mode edit
                modeOutlet.disabled = false;
                modeGrup.disabled = false;
            }
            btnSave.innerHTML = '<i class="fa fa-save"></i> Simpan';
            btnCancelEdit.style.display = 'none';
        }

        btnCancelEdit.addEventListener('click', function(){
            cancelEdit();
            clearResult();
        });

        // inisialisasi daftar saat load
        document.addEventListener('DOMContentLoaded', function(){
            loadOutletRek(1);
        });

        // event pencarian (single field 'search_q' untuk nama atau id)
        if(btnSearch){
            btnSearch.addEventListener('click', function(e){
                e.preventDefault();
                // tampilkan indikator mode cari seperti yang diminta
                const val = (searchQ && searchQ.value.trim() !== '') ? searchQ.value.trim() : 'nama_out atau id_out';
                setResult('info', 'Mode Cari Data : ' + val);
                loadOutletRek(1);
            });
        }
        if(btnClearSearch){
            btnClearSearch.addEventListener('click', function(e){
                e.preventDefault();
                if(searchQ) searchQ.value = '';
                // setelah reset, hapus pesan mode cari / tunjukkan semua data
                setResult('info', 'Menampilkan semua data');
                loadOutletRek(1);
                // hilangkan pesan setelah 2.5 detik
                setTimeout(clearResult, 2500);
            });
        }
        // Enter untuk input search_q — juga tunjukkan pesan Mode Cari Data
        if(searchQ){
            searchQ.addEventListener('keypress', function(ev){
                if(ev.key === 'Enter'){
                    ev.preventDefault();
                    const val = searchQ.value.trim() !== '' ? searchQ.value.trim() : 'nama_out atau id_out';
                    setResult('info', 'Mode Cari Data : ' + val);
                    loadOutletRek(1);
                }
            });
        }
    })();
</script>

<!-- Select2: pastikan jQuery sudah tersedia di layout Anda -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Init Select2 dengan width 100% (jika jQuery sudah ter-load di layout) -->
<script>
    (function(){
        if(window.jQuery && jQuery.fn.select2){
            jQuery(function(){
                // paksa semua .select2 menggunakan lebar 100% supaya dropdown tidak kecil,
                // berguna saat elemen awalnya tersembunyi (display:none)
                jQuery('.select2').each(function(){
                    jQuery(this).css('width', '100%');
                    jQuery(this).select2({ width: '100%' });
                });
            });
        }
    })();
</script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    
<?php
    $conn = $base->close();
?>