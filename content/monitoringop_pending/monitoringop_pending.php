<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Penjualan</a></li>
                <li class="breadcrumb-item active" aria-current="page">Monitoring Tukar Faktur</li>
            </ol>
        </nav>
        <h4 class="content-title">Monitoring Tukar Faktur</h4>
    </div>
</div>
<?php
// Ambil data pencarian
$cari = $secu->injection(@$_GET['key']);
?>

<!-- HTML untuk monitoring operational pending -->
<div class="content-body">
    <div class="row mg-t-20 d-flex">
        <div class="col-12 mb-2 d-flex justify-content-end align-items-center">
            <!-- Date controls removed as requested -->
            <div></div>
         </div>
        
        <div class="col-12 mb-2">
            <div class="alert alert-warning py-2" style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div class="pending-controls">
                        <div class="search-wrapper">
                            <input type="text" id="caridata" class="form-control form-control-sm" placeholder="Cari faktur atau outlet..." value="<?php echo htmlspecialchars($cari); ?>">
                        </div>
                        <select id="filterCabang" class="form-control form-control-sm filter-cabang" aria-label="Filter Cabang">
                            <option value="">Semua Cabang</option>
                        </select>

                        <div class="pending-buttons"> <!-- wrapper for all action buttons -->
                            <!-- moved pendingFilterInfo to right side (after total pending) -->
     
                            <a href="/192.268.908.09/laporan/xls/monitoringop_pending/monitoringop_pending.php" id="exportExcel" class="btn-excel-icon icon-button tooltip-container" target="_blank" rel="noopener" tabindex="0" aria-describedby="exportTooltip" aria-label="Export ke Excel" role="button" aria-pressed="false">
                                <!-- excel svg -->
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M3 3h12l6 6v12a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z" fill="none"/>
                                    <path d="M15 3v6h6" fill="none"/>
                                    <path d="M8.2 9.5 L11.8 13.1" stroke="currentColor" stroke-width="1.8"/>
                                    <path d="M11.8 9.5 L8.2 13.1" stroke="currentColor" stroke-width="1.8"/>
                                    <path d="M6 15h12" stroke="currentColor" stroke-width="1.1"/>
                                    <path d="M6 17.5h12" stroke="currentColor" stroke-width="1.1"/>
                                </svg>
                                <span id="exportTooltip" class="tooltip-text" role="tooltip">Export daftar pending ke Excel</span>
                            </a>

                            <a href="#" id="refreshBtn" class="btn-refresh-icon icon-button tooltip-container" tabindex="0" aria-describedby="refreshTooltip" aria-label="Refresh daftar" role="button" aria-pressed="false">
                                <!-- refresh svg -->
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M21 12a9 9 0 1 1-3.356-6.644" />
                                    <path d="M21 3v6h-6" />
                                </svg>
                                <span id="refreshTooltip" class="tooltip-text" role="tooltip">Refresh</span>
                            </a>
                        </div> <!-- /.pending-buttons -->
                     </div>
                    <!-- <strong>Filter: Hanya menampilkan faktur yang masih memiliki status pending (❌)</strong> -->
                </div>
                <div class="text-right" style="display:flex; align-items:center; justify-content:flex-end;">
                    <div style="display:inline-flex; align-items:center; gap:8px;">
                        <span id="info-total" style="line-height:1; white-space:nowrap;">Loading...</span>
                        <!-- pendingFilterInfo aligned next to total -->
                        <a href="#" id="pendingFilterInfo" class="btn-warning-icon icon-button tooltip-container" tabindex="0" aria-describedby="pendingTooltip" aria-label="Informasi Filter Pending" role="button" aria-pressed="false" style="width:36px;height:36px; padding:6px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                <path d="M8.982 1.566a1.13 1.13 0 0 0-1.964 0L.165 13.233c-.457.778.091 1.767.982 1.767h13.706c.89 0 1.438-.99.982-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 8a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                            </svg>
                            <span id="pendingTooltip" class="tooltip-text" role="tooltip">Klik: Informasi filter pending</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hidden inputs -->
        <input type="hidden" id="halaman" value="1">
        <input type="hidden" id="maximal" value="100">
        <input type="hidden" id="tanggal" value="<?php echo date('Y-m-d'); ?>">

        <!-- Tabel kolom kiri -->
        <div class="col-lg-6 col-md-6" style="display: flex; flex-direction: column; height: 100%;">
            <div class="block" style="flex-grow: 1;">
                <div class="block-content" style="height: 100%; overflow-y: auto;">
                    <table class="table table-striped table-vcenter" style="font-size: 10px;">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Tanggal</th>
                                <th>No. Faktur</th>
                                <th style="min-width:161px;">Outlet</th>
                                <th><center>Cabang</center></th>
                                <th>Durasi</th>
                                <th>Barang Terkirim</th>
                                <th><center>Dokumen kembali</center></th>
                                <th><center>Failing Dokumen</center></th> <!-- ditambahkan -->
                                <th><center>FP</center></th>
                                <th><center>TF</center></th>
                                <th><center>Jenis Faktur</center></th>
                            </tr>
                        </thead>
                        <tbody id="isitabel-kiri">
                            <tr>
                                <td colspan="12" class="text-center"> <!-- colspan disesuaikan -->
                                    <div class="spinner-border spinner-border-sm" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    Loading data...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tabel kolom kanan -->
        <div class="col-lg-6 col-md-6" style="display: flex; flex-direction: column; height: 100%;">
            <div class="block" style="flex-grow: 1;">
                <div class="block-content" style="height: 100%; overflow-y: auto;">
                    <table class="table table-striped table-vcenter" style="font-size: 10px;">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Tanggal</th>
                                <th>No. Faktur</th>
                                <th style="min-width:161px;">Outlet</th>
                                <th><center>Cabang</center></th>
                                <th>Durasi</th>
                                <th>Barang Terkirim</th>
                                <th><center>Dokumen kembali</center></th>
                                <th><center>Failing Dokumen</center></th> <!-- ditambahkan -->
                                <th><center>FP</center></th>
                                <th><center>TF</center></th>
                                <th><center>Jenis Faktur</center></th>
                            </tr>
                        </thead>
                        <tbody id="isitabel-kanan">
                            <tr>
                                <td colspan="12" class="text-center"> <!-- colspan disesuaikan -->
                                    <div class="spinner-border spinner-border-sm" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    Loading data...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Pagination controls -->
    <div class="row mt-2">
        <div class="col-12 d-flex justify-content-center align-items-center">
            <nav aria-label="Pagination">
                <ul class="pagination" id="pendingPagination" style="margin:0;">
                    <!-- JS akan mengisi -->
                </ul>
            </nav>
        </div>
    </div>
 </div>
 
<!-- Modal: pending filter info -->
<style>
/* simple modal styles (self-contained) */
#pendingFilterModalOverlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1050; align-items:center; justify-content:center; }
#pendingFilterModal { background: #fff; border-radius:6px; max-width:480px; width:90%; padding:18px; box-shadow:0 6px 24px rgba(0,0,0,0.2); }
#pendingFilterModal .modal-header { display:flex; align-items:center; gap:12px; }
#pendingFilterModal .modal-body { margin-top:8px; font-size:14px; color: #333; }
#pendingFilterModal .btn-close-modal {
    background: #ffecb5ff; /* sama seperti background header info Bootstrap */
    color: #856404;      /* warna teks header info Bootstrap */
    border: 0;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
}

/* Base class untuk semua icon buttons */
.icon-button {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:0;
    width:44px;
    height:44px;
    padding:6px;
    border-radius:8px;
    background:transparent;
    color: #856404;            /* warna dasar (warning) */
    transition: background .12s ease, color .12s ease, transform .06s ease;
    cursor:pointer;
    border:1px solid transparent;
    text-decoration:none;
    box-sizing: border-box;
    line-height: 0;
    font-size: 0;
}
.icon-button svg { 
    display:block; 
    pointer-events:none; 
    margin:0 auto; 
    width:24px; 
    height:24px; 
    vertical-align: middle; 
}

/* Warning icon button */
.btn-warning-icon {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:0;
    width:44px;
    height:44px;
    padding:6px;
    border-radius:8px;
    background:transparent;
    color: #856404; /* default color */
    transition: background .12s ease, color .12s ease, transform .06s ease;
    cursor:pointer;
    border:1px solid transparent;
    text-decoration:none;
    box-sizing: border-box;
    line-height: 0;
    font-size: 0;
}
.btn-warning-icon svg { 
    display:block; 
    pointer-events:none; 
    margin:0 auto; 
    width:24px; 
    height:24px; 
    vertical-align: middle; 
}

/* Excel button */
.btn-excel-icon {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:0;
    width:44px;
    height:44px;
    padding:6px;
    border-radius:8px;
    background:transparent;
    color: #856404; /* default color sama dengan warning */
    transition: background .12s ease, color .12s ease, transform .06s ease;
    cursor:pointer;
    border:1px solid transparent;
    text-decoration:none;
    box-sizing: border-box;
    line-height: 0;
    font-size: 0;
}
.btn-excel-icon svg { 
    display:block; 
    pointer-events:none; 
    margin:0 auto; 
    width:24px; 
    height:24px; 
    vertical-align: middle; 
}

/* Refresh button (sama desain) */
.btn-refresh-icon {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:0;
    width:44px;
    height:44px;
    padding:6px;
    border-radius:8px;
    background:transparent;
    color: #856404; /* default color sama dengan tombol lain */
    transition: background .12s ease, color .12s ease, transform .06s ease;
    cursor:pointer;
    border:1px solid transparent;
    text-decoration:none;
    box-sizing: border-box;
    line-height: 0;
    font-size: 0;
}
.btn-refresh-icon svg { 
    display:block; 
    pointer-events:none; 
    margin:0 auto; 
    width:24px; 
    height:24px; 
    vertical-align: middle; 
}

/* Hover effects - Warning button: icon berubah kuning */
.btn-warning-icon:hover,
.btn-warning-icon:focus {
    background: #fff3cd;
    color: #ffc107; /* kuning Bootstrap warning */
    border-color: #ffeeba;
    transform: translateY(-1px);
    outline: none;
    box-shadow: 0 2px 6px rgba(0,0,0,0.06);
}

/* Hover effects - Excel button: icon berubah hijau */
.btn-excel-icon:hover,
.btn-excel-icon:focus {
    background: #fff3cd;
    color: #198754; /* hijau Bootstrap success */
    border-color: #ffeeba;
    transform: translateY(-1px);
    outline: none;
    box-shadow: 0 2px 6px rgba(0,0,0,0.06);
}

/* Hover effects - Refresh button: icon berubah biru (primary) */
.btn-refresh-icon:hover,
.btn-refresh-icon:focus {
    background: #fff3cd;
    color: #0d6efd; /* biru Bootstrap primary */
    border-color: #ffeeba;
    transform: translateY(-1px);
    outline: none;
    box-shadow: 0 2px 6px rgba(0,0,0,0.06);
}

/* Active states */
.btn-warning-icon:active,
.btn-warning-icon[aria-pressed="true"] {
    background: #ffecb5ff;
    color: #856404;
    border-color: #856404;
    transform: translateY(0);
}

.btn-excel-icon:active,
.btn-excel-icon[aria-pressed="true"] {
    background: #ffecb5ff;
    color: #157347; /* hijau lebih gelap saat active */
    border-color: #157347;
    transform: translateY(0);
}

/* Active state untuk refresh */
.btn-refresh-icon:active,
.btn-refresh-icon[aria-pressed="true"] {
    background: #ffecb5ff;
    color: #0b5ed7;
    border-color: #0b5ed7;
    transform: translateY(0);
}

/* Focus visible untuk accessibility */
.btn-warning-icon:focus-visible,
.btn-excel-icon:focus-visible {
    box-shadow: 0 0 0 3px rgba(133,100,4,0.12);
}

/* Focus visible untuk accessibility (sama seperti tombol lain) */
.btn-refresh-icon:focus-visible {
    box-shadow: 0 0 0 3px rgba(13,110,253,0.12);
}

/* Layout untuk controls */
.pending-controls {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    margin-left: 20px;
}
.pending-controls .search-wrapper { 
    display:block;
    min-width:0; /* allow input to shrink inside flex */
}

/* Make search input and filter dropdown equal size and responsive */
.pending-controls input#caridata,
.pending-controls select.filter-cabang,
.pending-controls select#filterCabang {
    flex: 0 1 175px;   /* preferred width 170px, but can shrink */
    min-width: 200px;  /* avoid too small on narrow screens */
    padding: 6px 8px;
    height: 38px;
    box-sizing: border-box;
}

/* Ensure the input fills its wrapper when needed */
.pending-controls .search-wrapper input {
    width: 100%;
    display: block;
}

/* Responsive */
@media (max-width: 520px) {
    .pending-controls { 
        margin-left: 8px; 
        gap:6px; 
    }
    .pending-controls input#caridata,
    .pending-controls select.filter-cabang,
    .pending-controls select#filterCabang {
        flex: 0 1 160px;
        min-width: 120px;
    }
}
</style>
<div id="pendingFilterModalOverlay" role="dialog" aria-modal="true" aria-hidden="true">
    <div id="pendingFilterModal" role="document" aria-labelledby="pendingFilterTitle">
        <div class="modal-header">
            <div>
                <h5 id="pendingFilterTitle" style="margin:0;color: #000000ff;">Informasi Filter Pending</h5>
                <small style="color: #6c757d;">Perhatian</small>
            </div>
        </div>
        <div class="modal-body">
            Filter ini hanya menampilkan faktur yang masih memiliki satu atau lebih status "pending" (❌).
            dan hanya menampilkan faktur dari 3 bulan terakhir berdasarkan tanggal faktur.
            <br><br>
            <strong>Durasi Waktu:</strong><br>- Jika warna text berwarna kuning faktur melebihi 60 hari
            <br>- Jika warna text berwarna merah faktur melebihi 80 hari
        </div>
        <div style="margin-top:12px; text-align:right;">
            <button type="button" class="btn-close-modal" id="closePendingFilterModal">Tutup</button>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const cariInput = document.getElementById('caridata');
 
    loadData();
 
     if (cariInput) {
         let searchTimeout;
         cariInput.addEventListener('input', function() {
             clearTimeout(searchTimeout);
             searchTimeout = setTimeout(function() {
                 document.getElementById('halaman').value = 1;
                 loadData();
             }, 500);
         });
     }
 });
 
 function loadData(page = null) {
     const caridata = document.getElementById('caridata').value;
     const namaApl = document.getElementById('filterCabang') ? document.getElementById('filterCabang').value : '';
     const halamanEl = document.getElementById('halaman');
     const halaman = page !== null ? page : parseInt(halamanEl.value || 1);
     const maximal = parseInt(document.getElementById('maximal').value || 100);
     const tanggal = document.getElementById('tanggal').value;
     
     // simpan halaman ke hidden
     halamanEl.value = halaman;
      
     document.getElementById('isitabel-kiri').innerHTML = '<tr><td colspan="12" class="text-center"><div class="spinner-border spinner-border-sm" role="status"></div> Loading...</td></tr>';
     document.getElementById('isitabel-kanan').innerHTML = '<tr><td colspan="12" class="text-center"><div class="spinner-border spinner-border-sm" role="status"></div> Loading...</td></tr>';
     
     const namaAplParam = namaApl ? `&nama_apl=${encodeURIComponent(namaApl)}` : '';
     fetch(`/192.268.908.09/json/monitoringop_pending/monitoringop_pending.php?caridata=${encodeURIComponent(caridata)}&halaman=${halaman}&maximal=${maximal}&menudata=monitoringop&tgl=${tanggal}${namaAplParam}`)
         .then(response => response.json())
         .then(data => {
             if (data.error) {
                 document.getElementById('isitabel-kiri').innerHTML = '<tr><td colspan="12" class="text-center text-danger">Error: ' + data.error + '</td></tr>';
                 document.getElementById('isitabel-kanan').innerHTML = '<tr><td colspan="12" class="text-center text-danger">Error: ' + data.error + '</td></tr>';
             } else {
                 document.getElementById('isitabel-kiri').innerHTML = data.tabel_kiri;
                 document.getElementById('isitabel-kanan').innerHTML = data.tabel_kanan;
                 
                 // isi dropdown dari daftar aplikasi (nama_apl) yang dikirim backend
                 if (Array.isArray(data.aplikasi)) {
                     const sel = document.getElementById('filterCabang');
                     const prev = sel.value;
                     sel.innerHTML = '<option value="">Semua Cabang</option>';
                     data.aplikasi.forEach(n => {
                         const o = document.createElement('option');
                         o.value = n;
                         o.text = n;
                         sel.appendChild(o);
                     });
                     if (prev) sel.value = prev;
                     if (!sel._bound) { sel.addEventListener('change', ()=>{ document.getElementById('halaman').value = 1; loadData(); }); sel._bound = 1; }
                 }
 
                 // hitung rentang 3 bulan terakhir: dari (tanggal sekarang - 3 bulan) s/d sekarang
                 const today = new Date();
                 const start = new Date();
                 start.setMonth(start.getMonth() - 16);
 
                 const fmt = d => {
                     const y = d.getFullYear();
                     const m = ('0' + (d.getMonth() + 1)).slice(-2);
                     const dd = ('0' + d.getDate()).slice(-2);
                     return `${dd}-${m}-${y}`; // format DMY
                 };
 
                  const totalPending = (Number(data.local_count) || 0) + (Number(data.api_count) || 0);
                  document.getElementById('info-total').innerHTML =
                      ' Total Pending: ' + totalPending +
                      ' (Lokal: ' + data.local_count + ' + API: ' + data.api_count + ')';
 
                  // Pagination rendering
                  renderPagination({
                      page: data.page || halaman,
                      total_pages: data.total_pages || 1,
                      total_records: data.total_records || 0
                  });
             }
         })
         .catch(error => {
             console.error('Error:', error);
             document.getElementById('isitabel-kiri').innerHTML = '<tr><td colspan="12" class="text-center text-danger">Error loading data</td></tr>';
             document.getElementById('isitabel-kanan').innerHTML = '<tr><td colspan="12" class="text-center text-danger">Error loading data</td></tr>';
         });
 }
 
 function renderPagination(meta) {
     const ul = document.getElementById('pendingPagination');
     ul.innerHTML = '';
     const page = Number(meta.page || 1);
     const total = Number(meta.total_pages || 1);
 
     function li(content, disabled, onClick) {
         const el = document.createElement('li');
         el.className = 'page-item' + (disabled ? ' disabled' : '');
         const a = document.createElement('a');
         a.className = 'page-link';
         a.href = '#';
         a.innerHTML = content;
         if (!disabled && onClick) a.addEventListener('click', function(e){ e.preventDefault(); onClick(); });
         el.appendChild(a);
         return el;
     }
 
     // Prev
     ul.appendChild(li('&laquo; Prev', page <= 1, function(){ goToPage(page - 1); }));
 
     // Show up to 7 page numbers centered around current
     const start = Math.max(1, page - 3);
     const end = Math.min(total, page + 3);
     if (start > 1) {
         ul.appendChild(li('1', false, function(){ goToPage(1); }));
         if (start > 2) {
             const dots = document.createElement('li'); dots.className = 'page-item disabled'; dots.innerHTML = '<span class="page-link">...</span>'; ul.appendChild(dots);
         }
     }
 
     for (let p = start; p <= end; p++) {
         const isActive = p === page;
         const item = li(p, false, function(){ goToPage(p); });
         if (isActive) item.classList.add('active');
         ul.appendChild(item);
     }
 
     if (end < total) {
         if (end < total - 1) {
             const dots = document.createElement('li'); dots.className = 'page-item disabled'; dots.innerHTML = '<span class="page-link">...</span>'; ul.appendChild(dots);
         }
         ul.appendChild(li(total, false, function(){ goToPage(total); }));
     }
 
     // Next
     ul.appendChild(li('Next &raquo;', page >= total, function(){ goToPage(page + 1); }));
 
     // show compact info
     const info = document.createElement('li');
     info.className = 'page-item disabled';
     info.innerHTML = '<span class="page-link">Halaman ' + page + ' / ' + total + ' &nbsp;(' + (meta.total_records || 0) + ')</span>';
     ul.appendChild(info);
 }
 
 function goToPage(n) {
     const max = Number(document.getElementById('maximal').value || 100);
     if (n < 1) n = 1;
     document.getElementById('halaman').value = n;
     loadData(n);
 }
 
 // Modal show/hide
 document.addEventListener('DOMContentLoaded', function() {
     const trigger = document.getElementById('pendingFilterInfo');
     const overlay = document.getElementById('pendingFilterModalOverlay');
     const closeBtn = document.getElementById('closePendingFilterModal');

     function showModal() { overlay.style.display = 'flex'; overlay.setAttribute('aria-hidden','false'); }
     function hideModal() { overlay.style.display = 'none'; overlay.setAttribute('aria-hidden','true'); }

     if (trigger) trigger.addEventListener('click', function(e){ e.preventDefault(); showModal(); });
     if (closeBtn) closeBtn.addEventListener('click', hideModal);
     if (overlay) overlay.addEventListener('click', function(e){ if(e.target === overlay) hideModal(); });

     // Refresh button: panggil loadData()
     const refreshBtn = document.getElementById('refreshBtn');
     if (refreshBtn) {
         refreshBtn.addEventListener('click', function(e){
             e.preventDefault();
             document.getElementById('halaman').value = 1;
             loadData();
         });
     }
 });
</script>