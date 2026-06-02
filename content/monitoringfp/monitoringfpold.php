<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Penjualan</a></li>
                <li class="breadcrumb-item active" aria-current="page">Faktur Pajak</li>
            </ol>
        </nav>
        <h4 class="content-title">Monitoring Faktur Pajak</h4>
    </div>
</div>
<?php 
$cari = $secu->injection(@$_GET['cari']); 

// Ambil data untuk dropdown
$produkQuery = $conn->query("SELECT id_pro, nama_pro FROM produk ORDER BY nama_pro ASC");
$outletQuery = $conn->query("SELECT id_out, nama_out FROM outlet ORDER BY nama_out ASC");
$cabangQuery = $conn->query("SELECT id_apl, nama_apl FROM aplikasi WHERE active_apl = 1 ORDER BY nama_apl ASC");

// In getFakturPajak.php - filter is already applied to the count query
if (!empty($periode_dari)) {
    $where .= " AND A.tgl_tfk >= :periode_dari";
}
if (!empty($periode_sampai)) {
    $where .= " AND A.tgl_tfk <= :periode_sampai";
}

$where = "(
    A.kode_tfk LIKE '%$cari%'
) AND A.upload_f_pajak = 'belum'";
?>
<input type="hidden" name="caridata" id="caridata" value="<?php echo($cari); ?>" readonly="readonly" />
<input type="hidden" name="halaman" id="halaman" value="1" readonly="readonly" />
<input type="hidden" name="maximal" id="maximal" value="80" readonly="readonly" />
<input type="hidden" name="urgent_start" id="urgent_start" value="1" readonly="readonly" />
<input type="hidden" name="normal_start" id="normal_start" value="1" readonly="readonly" />
<div class="content-body">
<div class="row mg-b-10">
        <div class="col-sm-10">
            <button class="btn btn-primary btn-pill btn-xs" data-toggle="modal" data-target="#modalPeriode">
                <i class="fa fa-calendar"></i> Periode
            </button>
            <a href="#" id="btnExportExcel" target="_blank" class="btn btn-success btn-pill btn-xs ml-1">
                <i class="fa fa-file-excel"></i> Excel
                <span 
                    tabindex="0"
                    class="ml-1"
                    data-toggle="tooltip"
                    data-placement="right"
                    title="Gunakan tombol Periode untuk menampilkan data excel faktur pajak berdasarkan rentang tanggal tertentu.">
                    <i class="fa fa-exclamation-circle text-warning" style="font-size:1em; cursor:pointer;"></i>
                </span>
            </a>
            <a href="<?php echo($data->sistem('url_sis').'/monitoringfp'); ?>">
                <button class="btn btn-info btn-pill btn-xs">
                    <i class="fa fa-spinner"></i> Refresh
                </button>
            </a>
        </div>
    </div>

    <!-- Tambahkan Dropdown -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form id="formMonitoringFP" autocomplete="off">
                <div class="row">
                    <!-- Dropdown Cabang -->
                    <div class="col-lg-6 col-md-6 col-sm-12 mb-3">
                        <label for="cabang" class="form-label fw-medium">Pilih Cabang:</label>
                        <select id="cabang" class="form-control select2">
                            <option value="">-- Pilih Cabang --</option>
                            <option value="all_cabang">Semua Cabang</option>
                            <?php 
                            while($rcabang = $cabangQuery->fetch(PDO::FETCH_ASSOC)){ 
                                echo '<option value="'.$rcabang['id_apl'].'">'.$rcabang['nama_apl'].'</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <!-- Dropdown Outlet -->
                    <div class="col-lg-6 col-md-6 col-sm-12 mb-3">
                        <label for="outlet" class="form-label fw-medium">Pilih Outlet:</label>
                        <select id="outlet" class="form-control select2">
                            <option value="">-- Pilih Outlet --</option>
                            <option value="All">Semua Outlet</option>
                            <?php 
                            while($routlet = $outletQuery->fetch(PDO::FETCH_ASSOC)){ 
                                echo '<option value="'.$routlet['id_out'].'">'.$routlet['nama_out'].'</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>
    

    <?php require_once('config/frame/alert.php'); ?>

    <!-- Scroll bar atas -->
    <div class="table-scroll-top">
        <div>
            <table class="table table-hover mg-b-0" style="height:1px;pointer-events:none;visibility:hidden;width:max-content;">
                <thead>
                    <tr>
                        <th style="width:40px"></th>
                        <th style="width:180px"></th>
                        <th style="width:100px"></th>
                        <th style="width:200px"></th>
                        <th style="width:200px"></th>
                        <th style="width:200px"></th>
                        <th style="width:100px"></th>
                        <th style="width:100px"></th>
                        <th style="width:100px"></th>
                        <th style="width:100px"></th>
                        <th style="width:160px"></th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <!-- Scroll bar bawah + tabel asli -->
    <div class="table-scroll-bottom table-responsive">
        <table class="table table-hover mg-b-0" style="width:max-content;">
            <thead>
                <tr>
                    <th style="width:40px"><center>NO</center></th>
                    <th style="width:180px">Nomor Faktur
                        <a href="#modalCabangSearch" data-toggle="modal" class="btn btn-outline-info btn-icon btn-xs rounded-circle ml-2" title="Cari berdasarkan nomor faktur, nama cabang dan nama outlet">
                            <i class="fa fa-search"></i>
                        </a>
                    </th>
                    <th style="width:100px"><center>Action</center></th>
                    <th style="width:100px">Tgl</th>
                    <th style="width:120px">Cabang</th>
                    <th style="width:220px">Outlet</th>
                    <th style="width:100px"><center>DPP</center></th>
                    <th style="width:100px">PPN</th>
                    <th style="width:120px"><center>TOTAL</center></th>
                    <th style="width:100px">Jenis Faktur</th> <!-- New column -->
                </tr>
            </thead>
            <tbody id="isitabel"></tbody>
        </table>
        <div class="mg-t-10">
            <nav aria-label="Page navigation example">
                <ul class="pagination pagination-circle mg-b-0" id="paginasi"></ul>
            </nav>
        </div>
    </div>
</div>

<!-- Modal for Cabang Search -->
<div class="modal fade" id="modalCabangSearch" tabindex="-1" role="dialog" aria-labelledby="modalCabangSearchTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalCabangSearchTitle">Pencarian Faktur Pajak</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="alert alert-info mb-3">
          <small><i class="fa fa-info-circle mr-1"></i> Anda dapat mencari hanya berdasarkan nomor faktur.</small>
        </div>
        <div class="form-group">
          <label for="modal_cari_cabang">Masukkan kata kunci pencarian:</label>
          <input type="text" id="modal_cari_cabang" class="form-control" placeholder="Masukan nomor faktur tanpa sepasi...">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-info" id="btnSearchCabang">
          <i class="fa fa-search mr-1"></i> Cari
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Pilih Periode -->
<div class="modal fade" id="modalPeriode" tabindex="-1" role="dialog" aria-labelledby="modalPeriodeLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content shadow-lg border-0">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalPeriodeLabel">
          <i class="fa fa-calendar mr-2"></i>Pilih Periode Faktur Pajak
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body pb-0">
        <form id="formPeriode">
          <div class="alert alert-light border mb-3 py-2 px-3 d-flex align-items-center">
            <div class="mr-3">
              <i class="fa fa-info-circle fa-lg text-info"></i>
            </div>
            <div>
              <div class="mb-1"><strong>Filter data berdasarkan periode tanggal faktur pajak.</strong></div>
              <div class="small text-muted">Pilih rentang tanggal untuk menampilkan data sesuai periode yang diinginkan.</div>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="periode_dari">Dari Tanggal</label>
              <input type="date" class="form-control" id="periode_dari" name="periode_dari">
            </div>
            <div class="form-group col-md-6">
              <label for="periode_sampai">Sampai Tanggal</label>
              <input type="date" class="form-control" id="periode_sampai" name="periode_sampai">
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer bg-light border-0">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">
          <i class="fa fa-times mr-1"></i> Batal
        </button>
        <button type="button" class="btn btn-primary" id="btnPilihPeriode">
          <i class="fa fa-check mr-1"></i> Terapkan
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Update Faktur Pajak -->
<div class="modal fade" id="modalUpdateFaktur" tabindex="-1" role="dialog" aria-labelledby="modalUpdateFakturLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content shadow-lg border-0">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalUpdateFakturLabel">
          <i class="fa fa-file-text mr-2"></i>Update Status Faktur Pajak
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body pb-0">
        <form id="formUpdateFaktur" enctype="multipart/form-data">
          <!-- Info Faktur -->
          <div class="alert alert-light border mb-3 py-2 px-3 d-flex align-items-center">
            <div class="mr-3">
              <i class="fa fa-barcode fa-lg text-info"></i>
            </div>
            <div>
              <div class="mb-1"><strong>No. Faktur:</strong> <span id="modalUpdateFakturKode" class="text-primary">-</span></div>
              <div><strong>Outlet:</strong> <span id="modalUpdateFakturOutlet" class="text-dark">-</span></div>
            </div>
          </div>
          <input type="hidden" id="update_id_tfk" name="id_tfk">
          <input type="hidden" id="update_cabang" name="cabang">

          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="status_f_pajak">Status Faktur Pajak</label>
              <select class="form-control" id="status_f_pajak" name="status_f_pajak">
                <option value="belum terbit">Belum Terbit</option>
                <option value="sudah terbit">Sudah Terbit</option>
              </select>
            </div>
            <div class="form-group col-md-6">
              <label for="upload_f_pajak">Upload Faktur Pajak</label>
              <select class="form-control" id="upload_f_pajak" name="upload_f_pajak">
                <option value="belum">Belum Upload</option>
                <option value="sudah">Sudah Upload</option>
              </select>
            </div>
          </div>

          <div id="upload-progress" class="progress mt-2 d-none">
            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
          </div>

          <div id="update-message" class="mt-3"></div>
        </form>
      </div>
      <div class="modal-footer bg-light border-0">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">
          <i class="fa fa-times mr-1"></i> Batal
        </button>
        <button type="button" class="btn btn-primary" id="btnSaveUpdateFaktur">
          <i class="fa fa-save mr-1"></i> Simpan
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Add this new modal for file uploads -->
<div class="modal fade" id="modalUploadFaktur" tabindex="-1" role="dialog" aria-labelledby="modalUploadFakturLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content shadow-lg border-0">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title" id="modalUploadFakturLabel">
          <i class="fa fa-upload mr-2"></i>Upload Dokumen Faktur Pajak
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="formUploadFaktur" enctype="multipart/form-data">
          <!-- Info Invoice -->
          <div class="alert alert-light border mb-3 py-2 px-3 d-flex align-items-center">
            <div class="mr-3">
              <i class="fa fa-barcode fa-lg text-info"></i>
            </div>
            <div>
              <div><strong>Outlet:</strong> <span id="uploadFakturOutlet" class="text-dark">-</span></div>
            </div>
          </div>

          <input type="hidden" id="upload_id_tfk" name="id_tfk">
          <input type="hidden" id="upload_cabang" name="cabang">

          <div class="form-group">
            <label for="no_faktur">Nomor Faktur</label>
            <input type="text" class="form-control" id="no_faktur" name="no_faktur" placeholder="Masukkan nomor faktur pajak" readonly>
          </div>

          <div class="form-group">
            <label for="keterangan">Keterangan</label>
            <textarea class="form-control" id="keterangan" name="keterangan" rows="2" placeholder="Keterangan dokumen"></textarea>
          </div>

          <div class="form-group">
            <label>Unggah Dokumen</label>
            <div class="custom-file">
              <input type="file" class="custom-file-input" id="file_faktur" name="file_faktur" accept=".pdf,.jpg,.jpeg,.png">
              <label class="custom-file-label" for="file_faktur">Pilih file...</label>
            </div>
            <small class="form-text text-muted">Format yang diizinkan: PDF, JPG, JPEG, PNG. Maksimal 5MB</small>
          </div>

          <div id="upload-progress" class="progress mt-3 d-none">
            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
          </div>

          <div id="upload-message" class="mt-3"></div>
          
          <!-- Uncomment dan aktifkan section "Dokumen Terdahulu" -->
          <div class="mt-4" id="previousUploads">
            <h6 class="mb-3 border-bottom pb-2"><i class="fa fa-history mr-1"></i> Dokumen Terdahulu</h6>
            <div id="uploadedFilesList" class="mb-3">
              <div class="text-center text-muted">
                <i>Memuat dokumen...</i>
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer bg-light border-0">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">
          <i class="fa fa-times mr-1"></i> Batal
        </button>
        <button type="button" class="btn btn-info" id="btnUploadFaktur">
          <i class="fa fa-upload mr-1"></i> Upload
        </button>
      </div>
    </div>
  </div>
</div>

<script>
// Gantikan bagian dokumen ready yang sudah ada dengan yang berikut:

$(document).ready(function() {
    // 1. Buat hidden input untuk periode jika belum ada
    if ($('#periode_dari_hidden').length === 0) {
        // Langsung set dengan nilai default
        var currentYear = new Date().getFullYear();
        var defaultStartDate = currentYear + '-01-01';
        var today = new Date().toISOString().split('T')[0];
        
        $('body').append('<input type="hidden" id="periode_dari_hidden" value="' + defaultStartDate + '">');
        $('body').append('<input type="hidden" id="periode_sampai_hidden" value="' + today + '">');
        
        console.log("Default periode langsung diterapkan - Dari:", defaultStartDate, "Sampai:", today);
    }
    
    // 2. Set nilai default untuk form input di modal
    $('#periode_dari').val($('#periode_dari_hidden').val() || (new Date().getFullYear() + '-01-01'));
    $('#periode_sampai').val($('#periode_sampai_hidden').val() || new Date().toISOString().split('T')[0]);
    
    // 3. Pastikan modal juga mendapatkan default yang sama
    $('#modalPeriode').on('show.bs.modal', function() {
        if (!$('#periode_dari').val()) {
            $('#periode_dari').val($('#periode_dari_hidden').val());
        }
        if (!$('#periode_sampai').val()) {
            $('#periode_sampai').val($('#periode_sampai_hidden').val());
        }
    });
    
    // 4. Load data dengan filter tanggal yang sudah diterapkan
    loadMonitoringFP();
    
    // 5. Tampilkan indikator filter aktif
    updateActiveFilters();
});

// Tambahkan indikator loading khusus tanggal saat halaman pertama dimuat
$(document).ready(function() {
    var currentYear = new Date().getFullYear();
    $('#isitabel').html('<tr><td colspan="9" class="text-center"><i class="fa fa-spinner fa-spin"></i> Memuat data dari 01-01-' + currentYear + ' sampai hari ini...</td></tr>');
});

$(document).ready(function() {
    // Initialize select2 with empty value as placeholder
    $('#cabang').select2({
        placeholder: "-- Pilih Cabang --",
        allowClear: true
    });
    
    $('#outlet').select2({
        placeholder: "-- Pilih Outlet --",
        allowClear: true
    });

    // Variables for preventing duplicate AJAX requests
    var loadingData = false;
    var pendingRequest = null;

    // OPTIMIZED: Single event handler for cabang
    $('#cabang').on('change', function() {
        // Reset outlet selection when cabang changes
        $('#outlet').val(null).trigger('change');
        
        // Reset pagination
        $('#halaman').val(1);
        
        // Reset numbering counters
        $('#urgent_start').val(1);
        $('#normal_start').val(1);
        
        // Call loadMonitoringFP with reset flag
        loadMonitoringFP(true);
    });

    $('#outlet').on('change', function() {
        // Reset pagination when changing outlets
        $('#halaman').val(1);
        
        // Use debounced loading instead of direct call
        debouncedLoadData();
    });
    
    // Initialize tooltips
    $('body').tooltip({
        selector: '[data-toggle="tooltip"]'
    });

    // Debounce function to prevent rapid consecutive calls
    var loadTimer = null;
    function debouncedLoadData() {
        clearTimeout(loadTimer);
        loadTimer = setTimeout(function() {
            loadMonitoringFP();
        }, 300); // 300ms delay to prevent multiple rapid calls
    }

    // Modified loadMonitoringFP with reset flag for branch changes
    window.loadMonitoringFP = function(resetNumbering) {
        // If a request is already in progress, abort it
        if (loadingData && pendingRequest) {
            pendingRequest.abort();
        }

        var id_apl = $('#cabang').val();
        var id_out = $('#outlet').val();
        var caridata = $('#caridata').val();
        var cari_cabang = $('#cari_cabang').val();
        var halaman = $('#halaman').val();
        var maximal = $('#maximal').val();
        var menudata = '<?php echo $menu; ?>';
        
        // Pastikan nilai default periode digunakan jika belum ada
        var currentYear = new Date().getFullYear();
        var defaultStartDate = currentYear + '-01-01';
        var today = new Date().toISOString().split('T')[0];
        
        var periode_dari = $('#periode_dari_hidden').val() || defaultStartDate;
        var periode_sampai = $('#periode_sampai_hidden').val() || today;
        
        // Pastikan nilai selalu ada di hidden input
        $('#periode_dari_hidden').val(periode_dari);
        $('#periode_sampai_hidden').val(periode_sampai);
        
        var urgentStartNo = resetNumbering ? 1 : $('#urgent_start').val();
        var normalStartNo = resetNumbering ? 1 : $('#normal_start').val();
        
        console.log("Loading data with date filter applied - Dari:", periode_dari, "Sampai:", periode_sampai);
        
        // Show loading indicator
        $('#isitabel').html('<tr><td colspan="9" class="text-center"><i class="fa fa-spinner fa-spin"></i> Memuat data...</td></tr>');
        
        // Set loading flag
        loadingData = true;
        
        console.log("Request params - Page:", halaman, "Urgent start:", urgentStartNo, "Normal start:", normalStartNo);
        
        // Jalankan AJAX request untuk mendapatkan data
        pendingRequest = $.ajax({
            url: 'json/monitoringfp/monitoringfp.php',
            type: 'GET',
            data: {
                caridata: caridata,
                halaman: halaman,
                maximal: maximal,
                menudata: menudata,
                id_apl: id_apl,
                id_out: id_out,
                cari_cabang: cari_cabang,
                periode_dari: periode_dari,
                periode_sampai: periode_sampai,
                upload_f_pajak: 'belum',
                urgent_start: urgentStartNo,
                normal_start: normalStartNo,
                reset_numbering: resetNumbering ? 1 : 0
            },
            dataType: 'json',
            success: function(response) {
                console.log("✅ Response from server:", {
                    page: response.halaman,
                    next_urgent: response.next_urgent_start,
                    next_normal: response.next_normal_start,
                    items_count: response.data ? response.data.length : 0
                });
                
                // Process response data
                if (response && response.success && Array.isArray(response.data)) {
                    var html = '';
                    if (response.data.length > 0) {
                        response.data.forEach(function(row) {
                            // Get display number (now all items use regular numbers)
                            var displayNumber = row.display_no || row.no;
                            
                            // Check if this is an urgent item
                            var isUrgent = row.urgent_flag === true;
                            
                            // Add urgent-row class if it's an urgent item
                            html += '<tr' + (isUrgent ? ' class="urgent-row"' : '') + '>';
                            
                            // Display number column - regular formatting for all numbers regardless of urgency
                            html += '<td align="center">' + displayNumber + '</td>';
                            
                            // Add invoice code and flag for urgent items
                            html += '<td>' + row.kode_tfk;
                            
                            // Still display the urgent flag for urgent items
                            if (isUrgent) {
                                html += ' <span class="palestinian-flag" title="URGENT - Prioritas Tinggi" data-toggle="tooltip" data-placement="right"></span>';
                            }
                            
                            html += '</td>';
                            
                            // Action buttons dan kolom lainnya
                            html += '<td align="center">';
                            if (row.action) {
                                var statusBtnClass = row.status_f_pajak === 'sudah terbit' ? 'btn-success' : 'btn-danger';
                                var statusBtnIcon = row.status_f_pajak === 'sudah terbit' ? 'fa-check' : 'fa-times';
                                var statusBtnTitle = row.status_f_pajak === 'sudah terbit' ? 'Sudah Terbit' : 'Belum Terbit';
                                
                                // Improved print button with better styling
                                // Improved print button with better styling and text label
                                html += '<a href="' + row.action.faktur_url + '" target="_blank" ' +
                                        'class="btn btn-xs rounded-circle mx-1 btn-print" ' +
                                        'title="Cetak Faktur Sales" data-toggle="tooltip" data-placement="top" ' +
                                        'aria-label="Cetak Faktur Sales">' +
                                        '<i class="fa fa-print"></i></a>';
                                html += '<button type="button" class="btn btn-xs rounded-pill mx-1 ' + statusBtnClass + '" ' +
                                        'onclick="toggleFakturStatus(\'' + row.id_tfk + '\', \'' + row.nama_cabang + '\', \'' + row.status_f_pajak + '\')" ' +
                                        'title="' + statusBtnTitle + '">' +
                                        '<i class="fa ' + statusBtnIcon + '"></i></button>';
                                
                                // In the loadMonitoringFP function - modify the upload button section
                                if (row.status_f_pajak === 'sudah terbit') {
                                    var uploadBtnClass = row.upload_f_pajak === 'sudah' ? 'btn-info' : 'btn-outline-info';
                                    var uploadBtnIcon = row.upload_f_pajak === 'sudah' ? 'fa-check' : 'fa-upload';
                                    
                                    html += '<button type="button" class="btn btn-xs rounded-pill mx-1 ' + uploadBtnClass + '" ' +
                                            'onclick="toggleUploadStatus(\'' + row.id_tfk + '\', \'' + row.nama_cabang + '\', \'' + row.upload_f_pajak + '\', \'' + row.kode_tfk + '\', \'' + row.nama_out.replace(/'/g, "\\'") + '\')" ' +
                                            'title="Status Upload Faktur Pajak">' +
                                            '<i class="fa ' + uploadBtnIcon + '"></i></button>';
                                }
                            }
                            html += '</td>';
                            
                            html += '<td>' + row.tgl_tfk + '</td>';
                            html += '<td>' + (row.nama_cabang || '') + '</td>';
                            html += '<td>' + row.nama_out + '</td>';
                            html += '<td align="right">' + row.subtot_tfk + '</td>';
                            html += '<td align="right">' + row.ppn_tfk + '</td>';
                            html += '<td align="right">' + row.total_tfk + '</td>';
                            html += '<td>' + row.jenis + '</td>'; // Add jenis column
                            html += '</tr>';
                        });
                    } else {
                        html = '<tr><td colspan="9" class="text-center">Data tidak ditemukan.</td></tr>';
                    }
                    $('#isitabel').html(html);
                    $('#paginasi').html(response.paginasi || '');
                    updateActiveFilters();
                    updatePaginationInfo(response.total, parseInt($('#halaman').val()), parseInt($('#maximal').val()));
                    
                    // Update nilai untuk halaman berikutnya
                    if (response.next_urgent_start) {
                        $('#urgent_start').val(response.next_urgent_start);
                        console.log("Updated urgent_start to:", response.next_urgent_start);
                    }
                    
                    if (response.next_normal_start) {
                        $('#normal_start').val(response.next_normal_start);
                        console.log("Updated normal_start to:", response.next_normal_start);
                    }
                } else {
                    $('#isitabel').html('<tr><td colspan="9" class="text-center">'+
                        (response && response.message ? response.message : 'Data tidak ditemukan.')+
                        '</td></tr>');
                    $('#paginasi').html('');
                }
            },
            error: function(xhr, status, error) {
                if (status !== 'abort') { // Don't show error for aborted requests
                    console.error("AJAX Error:", status, error);
                    $('#isitabel').html('<tr><td colspan="9" class="text-center">Terjadi kesalahan: ' + error + '<br>Coba refresh halaman</td></tr>');
                    $('#paginasi').html('');
                }
            },
            complete: function() {
                // Reset loading flag when request completes
                loadingData = false;
                pendingRequest = null;
            }
        });
    };

    // Initial load
    loadMonitoringFP();
});

$(document).ready(function() {
    $('#cabang').select2();

    $('#cabang').on('change', function() {
        loadMonitoringFP();
    });

    $('#outlet').on('change', function() {
        // Reset pagination when changing outlets
        $('#halaman').val(1);
        
        // Log the selected outlet for debugging
        console.log("Selected outlet: " + $(this).val());
        
        // Load data with the selected outlet
        loadMonitoringFP();
    });

    // Modal search handling
    $('#btnSearchCabang').on('click', function() {
        // Ambil nilai input dari modal
        var searchTerm = $('#modal_cari_cabang').val();
        
        // Reset pagination when performing a new search
        $('#halaman').val(1);
        
        // Reset numbering when performing a new search
        $('#urgent_start').val(1);
        $('#normal_start').val(1);

        // Simpan nilai ke hidden input
        $('#caridata').val(searchTerm);

        // Tutup modal
        $('#modalCabangSearch').modal('hide');

        // Show search loading indicator
        $('#isitabel').html('<tr><td colspan="9" class="text-center"><i class="fa fa-spinner fa-spin"></i> Mencari data dengan kata kunci: "' + searchTerm + '"...</td></tr>');
        
        // Muat ulang data dengan filter
        loadMonitoringFP(true);
        
        // Add visual feedback about search term
        updateActiveFilters();
    });
    
    // Juga tangani pencarian dengan tombol Enter
    $('#modal_cari_cabang').on('keyup', function(e) {
        if (e.key === 'Enter' || e.keyCode === 13) {
            $('#btnSearchCabang').click();
        }
    });

    // Add hidden input for cabang search if it doesn't exist yet
    if ($('#cari_cabang').length === 0) {
        $('body').append('<input type="hidden" id="cari_cabang" value="">');
    }

    // Add hidden input for periode if it doesn't exist yet
    if ($('#periode_dari_hidden').length === 0) {
        $('body').append('<input type="hidden" id="periode_dari_hidden" value="">');
        $('body').append('<input type="hidden" id="periode_sampai_hidden" value="">');
    }

    loadMonitoringFP();
});

$('#btnPilihPeriode').on('click', function() {
    // Gunakan nilai input atau default ke 1 Januari tahun ini
    var currentYear = new Date().getFullYear();
    var dari = $('#periode_dari').val() || (currentYear + '-01-01');
    var sampai = $('#periode_sampai').val() || new Date().toISOString().split('T')[0];
    
    // Reset pagination ke halaman 1 ketika menerapkan filter tanggal
    $('#halaman').val(1);
    
    // Simpan ke hidden input
    $('#periode_dari_hidden').val(dari);
    $('#periode_sampai_hidden').val(sampai);
    $('#modalPeriode').modal('hide');
    
    // Tampilkan indikator loading
    $('#isitabel').html('<tr><td colspan="9" class="text-center"><i class="fa fa-spinner fa-spin"></i> Memuat data sesuai periode...</td></tr>');
    
    loadMonitoringFP();
    
    // Tambahkan umpan balik visual tentang filter aktif
    updateActiveFilters();
});

// Add this to your existing script section to improve UX

$(document).ready(function() {
    // Inisialisasi select2 dengan pengaturan lebar yang tepat
    $('#cabang, #outlet').select2({
        placeholder: "-- Pilih --",
        allowClear: true,
        width: '100%',
        dropdownParent: $('#formMonitoringFP'),
        minimumResultsForSearch: 10,
        dropdownAutoWidth: false,
        containerCssClass: 'select2-container-fixed-width',
        dropdownCssClass: 'select2-dropdown-fixed-width'
    });
    
    // Hapus select2 ganda yang mungkin menyebabkan masalah lebar
    // Kode ini akan mencegah inisialisasi ganda
});

// Pastikan fungsi loadMonitoringFP() selalu menggunakan filter tanggal

function loadMonitoringFP(resetNumbering) {
    var id_apl = $('#cabang').val();
    var id_out = $('#outlet').val();
    var caridata = $('#caridata').val();
    var cari_cabang = $('#cari_cabang').val();
    var halaman = $('#halaman').val();
    var maximal = $('#maximal').val();
    var menudata = '<?php echo $menu; ?>';
    
    // Pastikan nilai default periode digunakan jika belum ada
    var currentYear = new Date().getFullYear();
    var defaultStartDate = currentYear + '-01-01';
    var today = new Date().toISOString().split('T')[0];
    
    var periode_dari = $('#periode_dari_hidden').val() || defaultStartDate;
    var periode_sampai = $('#periode_sampai_hidden').val() || today;
    
    // Pastikan nilai selalu ada di hidden input
    $('#periode_dari_hidden').val(periode_dari);
    $('#periode_sampai_hidden').val(periode_sampai);
    
    var urgentStartNo = resetNumbering ? 1 : $('#urgent_start').val();
    var normalStartNo = resetNumbering ? 1 : $('#normal_start').val();
    
    console.log("Loading data with date filter applied - Dari:", periode_dari, "Sampai:", periode_sampai);
    
    // Show loading indicator
    $('#isitabel').html('<tr><td colspan="9" class="text-center"><i class="fa fa-spinner fa-spin"></i> Memuat data...</td></tr>');
    
    $.ajax({
        url: 'json/monitoringfp/monitoringfp.php',
        type: 'GET',
        data: {
            caridata: caridata,
            halaman: halaman,
            maximal: maximal,
            menudata: menudata,
            id_apl: id_apl,
            id_out: id_out,
            cari_cabang: cari_cabang,
            periode_dari: periode_dari,
            periode_sampai: periode_sampai,
            upload_f_pajak: 'belum',
            urgent_start: urgentStartNo,
            normal_start: normalStartNo,
            reset_numbering: resetNumbering ? 1 : 0
        },
        dataType: 'json',
        success: function(response) {
            console.log("✅ Response from server:", {
                page: response.halaman,
                next_urgent: response.next_urgent_start,
                next_normal: response.next_normal_start,
                items_count: response.data ? response.data.length : 0
            });
            
            // Process response data
            if (response && response.success && Array.isArray(response.data)) {
                var html = '';
                if (response.data.length > 0) {
                    response.data.forEach(function(row) {
                        // Get display number (now all items use regular numbers)
                        var displayNumber = row.display_no || row.no;
                        
                        // Check if this is an urgent item
                        var isUrgent = row.urgent_flag === true;
                        
                        // Add urgent-row class if it's an urgent item
                        html += '<tr' + (isUrgent ? ' class="urgent-row"' : '') + '>';
                        
                        // Display number column - regular formatting for all numbers regardless of urgency
                        html += '<td align="center">' + displayNumber + '</td>';
                        
                        // Add invoice code and flag for urgent items
                        html += '<td>' + row.kode_tfk;
                        
                        // Still display the urgent flag for urgent items
                        if (isUrgent) {
                            html += ' <span class="palestinian-flag" title="URGENT - Prioritas Tinggi" data-toggle="tooltip" data-placement="right"></span>';
                        }
                        
                        html += '</td>';
                        
                        // Action buttons dan kolom lainnya
                        html += '<td align="center">';
                        if (row.action) {
                            var statusBtnClass = row.status_f_pajak === 'sudah terbit' ? 'btn-success' : 'btn-danger';
                            var statusBtnIcon = row.status_f_pajak === 'sudah terbit' ? 'fa-check' : 'fa-times';
                            var statusBtnTitle = row.status_f_pajak === 'sudah terbit' ? 'Sudah Terbit' : 'Belum Terbit';
                            
                            html += '<a href="' + row.action.faktur_url + '" target="_blank" ' +
                                    'class="btn btn-xs rounded-circle mx-1 btn-print" ' +
                                    'title="Cetak Faktur Sales" data-toggle="tooltip" data-placement="top" ' +
                                    'aria-label="Cetak Faktur Sales">' +
                                    '<i class="fa fa-print"></i></a>';
                            html += '<button type="button" class="btn btn-xs rounded-pill mx-1 ' + statusBtnClass + '" ' +
                                    'onclick="toggleFakturStatus(\'' + row.id_tfk + '\', \'' + row.nama_cabang + '\', \'' + row.status_f_pajak + '\')" ' +
                                    'title="' + statusBtnTitle + '">' +
                                    '<i class="fa ' + statusBtnIcon + '"></i></button>';
                                
                            // In the loadMonitoringFP function - modify the upload button section
                            if (row.status_f_pajak === 'sudah terbit') {
                                var uploadBtnClass = row.upload_f_pajak === 'sudah' ? 'btn-info' : 'btn-outline-info';
                                var uploadBtnIcon = row.upload_f_pajak === 'sudah' ? 'fa-check' : 'fa-upload';
                                
                                html += '<button type="button" class="btn btn-xs rounded-pill mx-1 ' + uploadBtnClass + '" ' +
                                        'onclick="toggleUploadStatus(\'' + row.id_tfk + '\', \'' + row.nama_cabang + '\', \'' + row.upload_f_pajak + '\', \'' + row.kode_tfk + '\', \'' + row.nama_out.replace(/'/g, "\\'") + '\')" ' +
                                        'title="Status Upload Faktur Pajak">' +
                                        '<i class="fa ' + uploadBtnIcon + '"></i></button>';
                            }
                        }
                        html += '</td>';
                        
                        html += '<td>' + row.tgl_tfk + '</td>';
                        html += '<td>' + (row.nama_cabang || '') + '</td>';
                        html += '<td>' + row.nama_out + '</td>';
                        html += '<td align="right">' + row.subtot_tfk + '</td>';
                        html += '<td align="right">' + row.ppn_tfk + '</td>';
                        html += '<td align="right">' + row.total_tfk + '</td>';
                        html += '<td>' + row.jenis + '</td>'; // Add jenis column
                        html += '</tr>';
                    });
                } else {
                    html = '<tr><td colspan="9" class="text-center">Data tidak ditemukan.</td></tr>';
                }
                $('#isitabel').html(html);
                $('#paginasi').html(response.paginasi || '');
                updateActiveFilters();
                updatePaginationInfo(response.total, parseInt($('#halaman').val()), parseInt($('#maximal').val()));
                
                // Update nilai untuk halaman berikutnya
                if (response.next_urgent_start) {
                    $('#urgent_start').val(response.next_urgent_start);
                    console.log("Updated urgent_start to:", response.next_urgent_start);
                }
                
                if (response.next_normal_start) {
                    $('#normal_start').val(response.next_normal_start);
                    console.log("Updated normal_start to:", response.next_normal_start);
                }
            } else {
                $('#isitabel').html('<tr><td colspan="9" class="text-center">'+
                    (response && response.message ? response.message : 'Data tidak ditemukan.')+
                    '</td></tr>');
                $('#paginasi').html('');
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error:", status, error);
            console.error("Response Text:", xhr.responseText);
            $('#isitabel').html('<tr><td colspan="9" class="text-center">Terjadi kesalahan: ' + error + '<br>Coba refresh halaman</td></tr>');
            $('#paginasi').html('');
        }
    });
}

// Improved pagination handler for consistent numbering
$(document).on('click', '.pagination a', function(e) {
    e.preventDefault();
    var page = $(this).data('page') || $(this).attr('data-page');
    
    // Parse page number from pagination click
    if (!page) {
        page = $(this).text();
        if (page === "«") {
            page = parseInt($('#halaman').val()) - 1;
        } else if (page === "»") {
            page = parseInt($('#halaman').val()) + 1;
        }
    }
    
    // Validate page number
    if (!page || isNaN(page) || page === parseInt($('#halaman').val())) return;
    
    // Update page number
    $('#halaman').val(page);
    
    // IMPORTANT: For all_cabang, calculate start numbers based ONLY on page number
    // This ensures server and client use the same calculation logic
    var maximal = parseInt($('#maximal').val());
    var page_start = ((page - 1) * maximal) + 1;
    
    // Set values for both hidden fields 
    $('#urgent_start').val(page_start);
    $('#normal_start').val(page_start);
    
    console.log("Pagination - Page:", page, "Start:", page_start);
    
    // Load data without resetting numbering
    loadMonitoringFP(false);
});

// Add these additional functions for better UX when loading all branches
$(document).ready(function() {
    // Update the maximal value when all_cabang is selected to prevent 
    // too many results overwhelming the page
    $('#cabang').on('change', function() {
        if ($(this).val() === 'all_cabang') {
            // Set a reasonable limit for combined results
            $('#maximal').val(50);
            
            
            // Reset to default
            $('#maximal').val(80);
        }
    });
});

function showLoadingMessage(message) {
    // You can implement this using any toast/notification library
    // or a simple alert for now
    alert(message);
}

// Add this after your AJAX success handler

function updateActiveFilters() {
    // Remove any existing filter alerts first
    $('.filter-alert').remove();
    
    var activeFilters = [];
    
    // Check which filters are active
    if ($('#cabang').val()) {
        var cabangText = $('#cabang').val() === 'all_cabang' ? 
            'Semua Cabang ' : $('#cabang option:selected').text();
        activeFilters.push('Cabang: ' + cabangText);
    }
    
    if ($('#outlet').val()) {
        var outletText = $('#outlet').val() === 'All' ? 
            'Semua Outlet' : $('#outlet option:selected').text();
        activeFilters.push('Outlet: ' + outletText);
    }
    
    if ($('#cari_cabang').val()) {
        activeFilters.push('Filter: "' + $('#cari_cabang').val() + '"');
    }
    
    // Add date period filters if they exist
    var periode_dari = $('#periode_dari_hidden').val();
    var periode_sampai = $('#periode_sampai_hidden').val();
    
    if (periode_dari && periode_sampai) {
        // Format dates for display (optional)
        var dari_formatted = formatDate(periode_dari);
        var sampai_formatted = formatDate(periode_sampai);
        activeFilters.push('Periode: ' + dari_formatted + ' s/d ' + sampai_formatted);
    } else if (periode_dari) {
        var dari_formatted = formatDate(periode_dari);
        activeFilters.push('Periode dari: ' + dari_formatted);
    } else if (periode_sampai) {
        var sampai_formatted = formatDate(periode_sampai);
        activeFilters.push('Periode sampai: ' + sampai_formatted);
    }
    
    // Display active filters if any
    if (activeFilters.length > 0) {
        var filterHtml = '<div class="alert alert-info mt-2 mb-2 p-2 filter-alert">' +
            '<small><i class="fa fa-filter mr-1"></i>Filter aktif: ' + 
            activeFilters.join(' | ') + 
            '<button type="button" class="close ml-2" aria-label="Close" onclick="clearAllFilters()">' +
            '<span aria-hidden="true">&times;</span></button></small></div>';
        
        // Add this before the table
        $('.table-responsive').before(filterHtml);
    }
}

// Helper function to format date (DD-MM-YYYY)
function formatDate(dateString) {
    if (!dateString) return '';
    
    var date = new Date(dateString);
    var day = date.getDate().toString().padStart(2, '0');
    var month = (date.getMonth() + 1).toString().padStart(2, '0');
    var year = date.getFullYear();
    
    return day + '-' + month + '-' + year;
}

// Function to clear all filters
function clearAllFilters() {
    // Reset all filter controls
    $('#cabang').val('').trigger('change');
    $('#outlet').val('').trigger('change');
    $('#cari_cabang').val('');
    $('#periode_dari_hidden').val('');
    $('#periode_sampai_hidden').val('');
    
    // Remove filter notification
    $('.filter-alert').remove();
    
    // Reset pagination
    $('#halaman').val(1);
    
    // Reload data
    loadMonitoringFP();
}

// On document ready, add a helper class to make the alerts dismissible
$(document).ready(function() {
    // Add CSS for the filter alert close button
    $("<style>")
        .prop("type", "text/css")
        .html(`
            .filter-alert .close {
                padding: 0 0.25rem;
                font-size: 0.85rem;
                opacity: 0.7;
            }
            .filter-alert .close:hover {
                opacity: 1;
            }
        `)
        .appendTo("head");
});

// Event handler untuk klik pada paginasi
$(document).on('click', '.pagination a', function(e) {
    e.preventDefault();
    var page = $(this).data('page') || $(this).attr('data-page');
    
    // Parse halaman dari klik pagination
    if (!page) {
        page = $(this).text();
        if (page === "«") {
            page = parseInt($('#halaman').val()) - 1;
        } else if (page === "»") {
            page = parseInt($('#halaman').val()) + 1;
        }
    }
    
    // Validasi nomor halaman
    if (!page || isNaN(page) || page === parseInt($('#halaman').val())) return;
    
    // Update nomor halaman
    $('#halaman').val(page);
    
    // IMPORTANT: For all_cabang, calculate start numbers based ONLY on page number
    // This ensures server and client use the same calculation logic
    var maximal = parseInt($('#maximal').val());
    var page_start = ((page - 1) * maximal) + 1;
    
    // Set values for both hidden fields 
    $('#urgent_start').val(page_start);
    $('#normal_start').val(page_start);
    
    console.log("Pagination - Page:", page, "Start:", page_start);
    
    // Load data without resetting numbering
    loadMonitoringFP(false);
});

// Add this after your AJAX success handler

function updatePaginationInfo(total, currentPage, maxPerPage) {
    // Remove previous pagination-info
    $('.pagination-info').remove();

    var totalPages = Math.ceil(total / maxPerPage);
    var startRecord = total === 0 ? 0 : ((currentPage - 1) * maxPerPage) + 1;
    var endRecord = Math.min(currentPage * maxPerPage, total);
    
    // Create pagination info text
    var paginationInfo = '';
    if (total > 0) {
        // Base pagination info showing record range and total
        paginationInfo = 'Menampilkan ' + startRecord + ' sampai ' + endRecord +
                         ' dari ' + total + ' data';
        
        // Add info about numbering continuing (U-numbers followed by regular numbers)
        var numbersInfo = '';
        paginationInfo += numbersInfo;
    
        // Check if date filter is active and add that info
        var dateRange = '';
        var dari = $('#periode_dari_hidden').val();
        var sampai = $('#periode_sampai_hidden').val();

        if (dari && sampai) {
            dateRange = ' untuk periode ' + formatDate(dari) + ' s/d ' + formatDate(sampai);
        } else if (dari) {
            dateRange = ' sejak ' + formatDate(dari);
        } else if (sampai) {
            dateRange = ' hingga ' + formatDate(sampai);
        }

        paginationInfo += dateRange;
    } else {
        paginationInfo = 'Tidak ada data yang ditemukan';
        if ($('#periode_dari_hidden').val() || $('#periode_sampai_hidden').val()) {
            paginationInfo += ' untuk periode yang dipilih';
        }
    }

    // Create pagination info HTML element
    var paginationInfoHtml = '<div class="alert alert-info mt-2 mb-2 p-2 pagination-info">' +
        '<small><i class="fa fa-list-ol mr-1"></i>' + paginationInfo + '</small></div>';
    
    // Add this element below the table
    $('.table-scroll-bottom').after(paginationInfoHtml);
}

// Fungsi untuk menampilkan modal update faktur
function openFakturModal(id_tfk, cabang, kode_tfk, nama_out) {
    // Reset form and messages
    $('#formUpdateFaktur')[0].reset();
    $('#update-message').html('').removeClass('alert alert-success alert-danger');
    $('#upload-progress').addClass('d-none');

    // Set values to hidden fields
    $('#update_id_tfk').val(id_tfk);
    $('#update_cabang').val(cabang);

    // Tampilkan kode_tfk dan nama_out di modal
    $('#modalUpdateFakturKode').text(kode_tfk || '-');
    $('#modalUpdateFakturOutlet').text(nama_out || '-');

    // Show the modal
    $('#modalUpdateFaktur').modal('show');
}

// Handle file input display
$(document).on('change', '.custom-file-input', function() {
    let fileName = $(this).val().split('\\').pop();
    $(this).next('.custom-file-label').html(fileName || 'Pilih file...');
});

// Handle form submission
$('#btnSaveUpdateFaktur').on('click', function() {
    var id_tfk = $('#update_id_tfk').val();
    var cabang = $('#update_cabang').val();
    var status = $('#status_f_pajak').val();
    var upload_status = $('#upload_f_pajak').val();
    
    // Validate inputs if needed
    if (!id_tfk || !status || !upload_status) {
        $('#update-message').html('Data tidak lengkap').addClass('alert alert-danger');
        return;
    }
    
    // Clear previous messages
    $('#update-message').html('').removeClass('alert alert-success alert-danger');
    
    // Disable the save button during processing
    $('#btnSaveUpdateFaktur').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Proses...');
    
    // Send AJAX request
    $.ajax({
        url: 'json/monitoringfp/updatefaktur.php',
        type: 'POST',
        data: {
            id_tfk: id_tfk,
            cabang: cabang,
            status_f_pajak: status,
            upload_f_pajak: upload_status
        },
        dataType: 'json',
        success: function(response) {
            console.log("Update Response:", response);
            
            if (response.success) {
                $('#update-message').html(response.message).addClass('alert alert-success');
                
                // Close modal after a short delay
                setTimeout(function() {
                    $('#modalUpdateFaktur').modal('hide');
                    // Reload data
                    loadMonitoringFP();
                }, 1500);
            } else {
                $('#update-message').html('Gagal update: ' + (response.message || 'Unknown error')).addClass('alert alert-danger');
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error:", status, error);
            console.error("Response Text:", xhr.responseText);
            $('#update-message').html('Terjadi kesalahan: ' + error).addClass('alert alert-danger');
        },
        complete: function() {
            // Re-enable save button
            $('#btnSaveUpdateFaktur').prop('disabled', false).html('Simpan');
        }
    });
});

// Sinkronkan scroll horizontal atas & bawah
$(function() {
    $('.table-scroll-top').on('scroll', function() {
        $('.table-scroll-bottom').scrollLeft($(this).scrollLeft());
    });
    $('.table-scroll-bottom').on('scroll', function() {
        $('.table-scroll-top').scrollLeft($(this).scrollLeft());
    });
});

$('#btnExportExcel').on('click', function(e) {
    e.preventDefault();
    var periode_dari = $('#periode_dari_hidden').val();
    var periode_sampai = $('#periode_sampai_hidden').val();
    var url = '<?php echo $data->sistem('url_sis'); ?>/laporan/xls/monitoringfp/monitoringfp.php?';
    if (periode_dari) url += 'periode_dari=' + encodeURIComponent(periode_dari) + '&';
    if (periode_sampai) url += 'periode_sampai=' + encodeURIComponent(periode_sampai);
    window.open(url, '_blank');
});

$(function () {
  $('[data-toggle="tooltip"]').tooltip()
})

// Replace the toggleFakturStatus function with this improved version:

function toggleFakturStatus(id_tfk, cabang, currentStatus) {
    // Tentukan status baru (toggle)
    var newStatus = currentStatus === 'sudah terbit' ? 'belum terbit' : 'sudah terbit';
    
    // Temukan elemen tombol
    var $button = $('button[onclick*="toggleFakturStatus(\'' + id_tfk + '\'"]');
    var originalHtml = $button.html();
    
    // Temukan informasi faktur (kode_tfk dan nama_out) dari baris saat ini
    var $row = $button.closest('tr');
    var kode_tfk = $row.find('td:nth-child(2)').text().trim(); // Kolom nomor faktur
    var nama_out = $row.find('td:nth-child(6)').text().trim(); // Kolom nama outlet
    
    // Tampilkan status loading
    $button.html('<i class="fa fa-spinner fa-spin"></i> ').prop('disabled', true);
    
    // PENTING: Dapatkan status upload saat ini sebelum update
    var currentUploadStatus = 'belum'; // Default
    
    // Cari tombol upload jika ada untuk menentukan status upload saat ini
    var $uploadBtn = $button.parent().find('button[onclick*="toggleUploadStatus"]');
    if ($uploadBtn.length > 0) {
        // Ekstrak status dari atribut onClick
        var onclickAttr = $uploadBtn.attr('onclick');
        if (onclickAttr.includes("'sudah'")) {
            currentUploadStatus = 'sudah';
        }
    }
    
    // Kirim permintaan AJAX dengan KEDUA parameter
    $.ajax({
        url: 'json/monitoringfp/updatefaktur.php',
        type: 'POST',
        data: {
            id_tfk: id_tfk,
            cabang: cabang,
            status_f_pajak: newStatus,
            upload_f_pajak: currentUploadStatus // Tambahkan parameter upload_f_pajak
        },
        dataType: 'json',
        success: function(response) {
            console.log("Response:", response);
            
            if (response.success) {
                // Tampilkan notifikasi sukses
                if (typeof toastr !== 'undefined') {
                    toastr.success('Status faktur pajak berhasil diubah');
                } else {
                    console.log('Status faktur pajak berhasil diubah');
                }
                
                // Segera perbarui tampilan tombol
                var newBtnClass = newStatus === 'sudah terbit' ? 'btn-success' : 'btn-danger';
                var newBtnIcon = newStatus === 'sudah terbit' ? 'fa-check' : 'fa-times';
                var newBtnTitle = newStatus === 'sudah terbit' ? 'Sudah Terbit' : 'Belum Terbit';
                
                $button.removeClass('btn-success btn-warning btn-danger')
                       .addClass(newBtnClass)
                       .html('<i class="fa ' + newBtnIcon + '"></i>')
                       .attr('title', newBtnTitle)
                       .prop('disabled', false);
                
                // Jika status sekarang "sudah terbit", tambahkan tombol upload jika belum ada
                if (newStatus === 'sudah terbit') {
                    var $uploadBtn = $button.parent().find('button[onclick*="toggleUploadStatus"]');
                    if ($uploadBtn.length === 0) {
                        // PERBAIKAN: Tambahkan parameter kode_tfk dan nama_out ke dalam onclick handler
                        var uploadBtn = '<button type="button" class="btn btn-xs rounded-pill mx-1 btn-outline-info" ' +
                                        'onclick="toggleUploadStatus(\'' + id_tfk + '\', \'' + cabang + '\', \'belum\', \'' + kode_tfk + '\', \'' + nama_out.replace(/'/g, "\\'") + '\')" ' +
                                        'title="Status Upload Faktur Pajak">' +
                                        '<i class="fa fa-upload"></i></button>';
                        $button.after(uploadBtn);
                    }
                } else {
                    // Jika status sekarang "belum terbit", hapus tombol upload
                    $button.parent().find('button[onclick*="toggleUploadStatus"]').remove();
                }
                
                // Segera perbarui handler onclick dengan status baru
                $button.attr('onclick', 'toggleFakturStatus(\'' + id_tfk + '\', \'' + cabang + '\', \'' + newStatus + '\')');
                
                // Cache data faktur untuk digunakan nanti
                // Simpan dalam localStorage atau variabel global
                window['faktur_' + id_tfk] = {
                    id_tfk: id_tfk,
                    cabang: cabang, 
                    kode_tfk: kode_tfk,
                    nama_out: nama_out
                };
                
                // Tidak perlu memuat ulang seluruh tabel, UI sudah diperbarui
            } else {
                if (typeof toastr !== 'undefined') {
                    toastr.error('Gagal update: ' + (response.message || 'Unknown error'));
                } else {
                    alert('Gagal update: ' + (response.message || 'Unknown error'));
                }
                $button.html(originalHtml).prop('disabled', false);
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error:", xhr.responseText);
            if (typeof toastr !== 'undefined') {
                toastr.error('Terjadi kesalahan: ' + error);
            } else {
                alert('Terjadi kesalahan: ' + error);
            }
            $button.html(originalHtml).prop('disabled', false);
        }
    });
}

// Toggle upload_f_pajak between "belum" and "sudah"
function toggleUploadStatus(id_tfk, cabang, currentStatus) {
    // Tentukan status baru (toggle)
    var newStatus = currentStatus === 'sudah' ? 'belum' : 'sudah';
    
    // Tampilkan status loading pada tombol
    var $button = $(event.target).closest('button');
    var originalHtml = $button.html();
    $button.html('<i class="fa fa-spinner fa-spin"></i> Updating...').prop('disabled', true);
    
    // Kirim permintaan AJAX untuk memperbarui status
    $.ajax({
        url: 'json/monitoringfp/updatefaktur.php',
        type: 'POST',
        data: {
            id_tfk: id_tfk,
            cabang: cabang,
            status_f_pajak: 'sudah terbit', // Selalu "sudah terbit" saat toggle status upload
            upload_f_pajak: newStatus
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Tampilkan notifikasi sukses
                toastr.success('Status upload berhasil diubah');
                
                // Segera perbarui tampilan tombol
                var newBtnClass = newStatus === 'sudah' ? 'btn-info' : 'btn-outline-info';
                var newBtnIcon = newStatus === 'sudah' ? 'fa-check' : 'fa-upload';
                var newBtnText = newStatus === 'sudah' ? 'Sudah Upload' : 'Upload';
                
                $button.removeClass('btn-info btn-outline-info')
                       .addClass(newBtnClass)
                       .html('<i class="fa ' + newBtnIcon + ' mr-1"></i>' + newBtnText)
                       .prop('disabled', false);
                
                // Perbarui handler onclick dengan status baru
                $button.attr('onclick', 'toggleUploadStatus(\'' + id_tfk + '\', \'' + cabang + '\', \'' + newStatus + '\')');
                
                // Jika kita sudah mengatur status ke "sudah", baris ini harus disembunyikan
                // karena kita memfilter untuk upload_f_pajak = 'belum'
                if (newStatus === 'sudah') {
                    setTimeout(function() {
                        // Fade out dan hapus baris
                        $button.closest('tr').fadeOut(500, function() {
                            $(this).remove();
                            // Perbarui tabel jika sekarang kosong
                            if ($('#isitabel tr').length === 0) {
                                $('#isitabel').html('<tr><td colspan="9" class="text-center">Data tidak ditemukan.</td></tr>');
                            }
                        });
                    }, 1000);
                } else {
                    // Opsional: Muat ulang setelah jeda singkat untuk konsistensi
                    setTimeout(function() {
                        loadMonitoringFP();
                    }, 2000);
                }
            } else {
                toastr.error('Gagal update: ' + (response.message || 'Unknown error'));
                $button.html(originalHtml).prop('disabled', false);
            }
        },
        error: function(xhr, status, error) {
            toastr.error('Terjadi kesalahan: ' + error);
            $button.html(originalHtml).prop('disabled', false);
        }
    });
}

// Replace the toggleUploadStatus function with this new version

function toggleUploadStatus(id_tfk, cabang, currentStatus, kode_tfk, nama_out) {
    // Jika kita sudah dalam status "uploaded", tunjukkan riwayat unggahan
    if (currentStatus === 'sudah') {
        openUploadHistoryModal(id_tfk, cabang, kode_tfk, nama_out);
        return;
    }
    
    // Jika tidak, buka modal unggah
    openUploadModal(id_tfk, cabang, kode_tfk, nama_out);
}

// Function to open the upload modal
function openUploadModal(id_tfk, cabang, kode_tfk, nama_out) {
    // Reset form and messages
    $('#formUploadFaktur')[0].reset();
    $('#upload-message').html('').removeClass('alert alert-success alert-danger');
    $('#upload-progress').addClass('d-none');
    
    // Set values to hidden fields
    $('#upload_id_tfk').val(id_tfk);
    $('#upload_cabang').val(cabang);
    
    // Set invoice info in the modal
    $('#uploadFakturKode').text(kode_tfk || '-');
    $('#uploadFakturOutlet').text(nama_out || '-');
    
    // Auto-fill the faktur number
    $('#no_faktur').val(kode_tfk || '');
    
    // Load any previous uploads
    loadUploadHistory(id_tfk);
    
    // Show the modal
    $('#modalUploadFaktur').modal('show');
}

// Function to load upload history
function loadUploadHistory(id_tfk) {
    $('#uploadedFilesList').html('<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Memuat dokumen...</div>');
    
    $.ajax({
        url: 'json/monitoringfp/getUploadHistory.php',
        type: 'GET',
        data: {
            id_tfk: id_tfk
        },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data && response.data.length > 0) {
                var html = '<div class="list-group">';
                
                response.data.forEach(function(file) {
                    var fileIcon = getFileIcon(file.file_type);
                    var formattedDate = new Date(file.created_at).toLocaleString('id-ID', { 
                        day: '2-digit', month: 'short', year: 'numeric', 
                        hour: '2-digit', minute: '2-digit'
                    });
                    
                    html += '<div class="list-group-item list-group-item-action py-2 d-flex justify-content-between align-items-center">';
                    html += '<div>';
                    html += '<div class="d-flex align-items-center">';
                    html += '<div class="mr-2">' + fileIcon + '</div>';
                    html += '<div>';
                    html += '<h6 class="mb-0">' + file.no_faktur + '</h6>';
                    html += '<small class="text-muted">' + file.ket + '</small>';
                    html += '</div>';
                    html += '</div>';
                    html += '<small class="d-block text-muted mt-1">' + formattedDate + '</small>';
                    html += '</div>';
                    html += '<div>';
                    html += '<a href="' + file.url_upload + '" target="_blank" class="btn btn-sm btn-outline-info mr-1" title="Lihat Dokumen"><i class="fa fa-eye"></i></a>';
                    html += '<button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteUpload(' + file.id_tfbd + ', \'' + id_tfk + '\')" title="Hapus Dokumen"><i class="fa fa-trash"></i></button>';
                    html += '</div>';
                    html += '</div>';
                });
                
                html += '</div>';
                $('#uploadedFilesList').html(html);
            } else {
                $('#uploadedFilesList').html('<div class="text-center text-muted"><i>Belum ada dokumen terunggah</i></div>');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error details:', {
                status: status,
                error: error,
                responseText: xhr.responseText
            });
            $('#uploadedFilesList').html('<div class="text-center text-danger">Gagal memuat dokumen: ' + error + '</div>');
        }
    });
}

// Helper function to get appropriate icon for file type
function getFileIcon(fileType) {
    var iconClass = 'fa-file';
    
    if (fileType === 'pdf') {
        iconClass = 'fa-file-pdf';
    } else if (['jpg', 'jpeg', 'png', 'gif'].includes(fileType)) {
        iconClass = 'fa-file-image';
    }
    
    return '<i class="fa ' + iconClass + ' fa-lg text-secondary"></i>';
}

// Function to delete an uploaded file
function deleteUpload(id_tfbd, id_tfk) {
    if (confirm('Apakah Anda yakin ingin menghapus dokumen ini?')) {
        $.ajax({
            url: 'json/monitoringfp/deleteUpload.php',
            type: 'POST',
            data: {
                id_tfbd: id_tfbd
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Reload upload history
                    loadUploadHistory(id_tfk);
                    toastr.success('Dokumen berhasil dihapus');
                } else {
                    toastr.error('Gagal menghapus dokumen: ' + response.message);
                }
            },
            error: function() {
                toastr.error('Terjadi kesalahan saat menghapus dokumen');
            }
        });
    }
}

// Modify the btnUploadFaktur click handler:

$('#btnUploadFaktur').on('click', function() {
    var formData = new FormData($('#formUploadFaktur')[0]);
    var fileInput = $('#file_faktur')[0];
    var id_tfk = $('#upload_id_tfk').val();
    var cabang = $('#upload_cabang').val();
    var no_faktur = $('#no_faktur').val();
    var keterangan = $('#keterangan').val();
    
    // Validate inputs
    if (!id_tfk || !no_faktur) {
        $('#upload-message').html('Nomor faktur harus diisi').addClass('alert alert-danger');
        return;
    }
    
    if (fileInput.files.length === 0) {
        $('#upload-message').html('Pilih file untuk diunggah').addClass('alert alert-danger');
        return;
    }
    
    // Validate file size (max 5MB)
    if (fileInput.files[0].size > 5 * 1024 * 1024) {
        $('#upload-message').html('Ukuran file maksimal 5MB').addClass('alert alert-danger');
        return;
    }
    
    // Debug info
    console.log("Uploading file:", fileInput.files[0].name, "Size:", fileInput.files[0].size, "Type:", fileInput.files[0].type);
    
    // Show progress bar
    $('#upload-progress').removeClass('d-none');
    $('#upload-message').html('').removeClass('alert alert-success alert-danger');
    
    // Disable the upload button
    $('#btnUploadFaktur').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Mengupload...');
    
    $.ajax({
        url: 'json/monitoringfp/uploadFaktur.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        xhr: function() {
            var xhr = $.ajaxSettings.xhr();
            if (xhr.upload) {
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        var percent = Math.round((e.loaded / e.total) * 100);
                        $('#upload-progress .progress-bar').css('width', percent + '%').attr('aria-valuenow', percent);
                    }
                }, false);
            }
            return xhr;
        },
        success: function(response) {
            console.log("Upload response:", response);
            
            if (response.success) {
                $('#upload-message').html('Dokumen berhasil diunggah').addClass('alert alert-success');
                
                // Update button status directly in the table
                var $button = $('button[onclick*="toggleUploadStatus(\'' + id_tfk + '\'"]');
                $button.removeClass('btn-outline-info').addClass('btn-info')
                       .html('<i class="fa fa-check mr-1"></i>Sudah Upload')
                       .attr('onclick', 'toggleUploadStatus(\'' + id_tfk + '\', \'' + cabang + '\', \'sudah\')');
                
                // Reload upload history
                loadUploadHistory(id_tfk);
                
                // Clear file input
                $('#file_faktur').val('');
                $('.custom-file-label').html('Pilih file...');
                
                // Optional: Close modal after a short delay
                setTimeout(function() {
                    $('#modalUploadFaktur').modal('hide');
                    
                    // Refresh table row to reflect new status
                    loadMonitoringFP();
                }, 2000);
            } else {
                $('#upload-message').html('Gagal upload: ' + response.message).addClass('alert alert-danger');
            }
        },
        error: function(xhr, status, error) {
            console.error("Upload error:", error);
            console.error("Response:", xhr.responseText);
            $('#upload-message').html('Terjadi kesalahan: ' + error + '<br>Pastikan direktori upload ada dan memiliki izin yang tepat').addClass('alert alert-danger');
        },
        complete: function() {
            // Re-enable upload button
            $('#btnUploadFaktur').prop('disabled', false).html('<i class="fa fa-upload mr-1"></i> Upload');
               }
    });
});

// Di bagian penanganan respons ajax setelah upload berhasil
function handleUploadSuccess(response) {
    if (response.success && response.uploaded_to_other_branch) {
        // Simpan ID ke localStorage
        let hiddenIds = JSON.parse(localStorage.getItem('fakturUploadedToOtherBranch' || '[]'));
       
        if (!hiddenIds.includes(response.id_to_hide)) {
            hiddenIds.push(response.id_to_hide);
            localStorage.setItem('fakturUploadedToOtherBranch', JSON.stringify(hiddenIds));
        }
        
        // Sembunyikan baris dari tabel
        $(`tr[data-id="${response.id_to_hide}"]`).fadeOut();
    }
}

// Fungsi untuk menyembunyikan data yang sudah diupload ke cabang lain
function hideUploadedToBranchItems() {
    let hiddenIds = JSON.parse(localStorage.getItem('fakturUploadedToOtherBranch' || '[]'));
    if (hiddenIds.length > 0) {
        hiddenIds.forEach(id => {
            $(`tr[data-id="${id}"]`).hide();
        });
    }
}

// Panggil setelah DataTable selesai loading
$(document).ready(function() {
    // Existing code...
    
    $('#dataTables-serverSide').on('draw.dt', function() {
        hideUploadedToBranchItems();
    });
});

// Tambahkan di bagian $(document).ready untuk responsif select2
$(document).ready(function() {
    // Inisialisasi select2 dengan pengaturan lebar yang tepat
    $('#cabang, #outlet').select2({
        placeholder: "-- Pilih --",
        allowClear: true,
        width: '100%',
        dropdownParent: $('#formMonitoringFP'),
        minimumResultsForSearch: 10,
        dropdownAutoWidth: false,
        containerCssClass: 'select2-container-fixed-width',
        dropdownCssClass: 'select2-dropdown-fixed-width'
    });
    
    // Hapus select2 ganda yang mungkin menyebabkan masalah lebar
    // Kode ini akan mencegah inisialisasi ganda
});

// Fungsi untuk mencetak faktur sales
function printFakturSales(id_tfk) {
    var url = '<?php echo($data->sistem('url_sis')); ?>/laporan/xps/monitoringfp/monitoringfp.php?key=' + id_tfk;
    window.open(url, '_blank');
}
</script>

<style>
.btn-icon:hover {
    transform: scale(1.1);
    transition: transform 0.2s;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.btn-xs.rounded-circle {
    padding: 0.15rem 0.25rem;
    font-size: 0.65rem;
    line-height: 0.5;
}

/* Enhanced buttons */
.btn-outline-info:hover, .btn-outline-success:hover {
    transform: translateY(-2px);
    transition: all 0.2s;
    box-shadow: 0 3px 5px rgba(0,0,0,0.2);
}

.btn-xs.rounded-pill {
    padding: 0.2rem 0.5rem;
    font-size: 0.7rem;
    transition: all 0.3s ease;
}

/* Tambahkan CSS ini ke dalam tag <style> yang sudah ada */
.custom-file-label {
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.progress {
    height: 0.5rem;
}

.modal-header {
    background-color: #e3f2fd !important; /* Warna biru muda */
    color: #000000 !important; /* Teks hitam */
    border-bottom: 1px solid #d1e7f5; /* Garis bawah biru lebih gelap */
}

.modal-header .close {
    color: #000000 !important; /* Warna ikon close */
    opacity: 0.8;
}

.modal-header .close:hover {
    opacity: 1;
}

.modal-footer {
    background-color: #f8f9fa;
    border-top: 1px solid #e9ecef;
}

#update-message {
    padding: 0.5rem;
    border-radius: 0.25rem;
}

.alert {
    margin-bottom: 0;
}

.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
       /* Optional: tambahkan batas bawah agar lebih jelas */
    border-bottom: 1px solid #e9ecef;
}

.table-responsive table {
    white-space: nowrap;
}

.table-scroll-top,
.table-scroll-bottom {
    overflow-x: auto;
    overflow-y: hidden;
    background: #f8f9fa;
    padding: 0 !important;
    margin: 0 !important;
}
.table-scroll-top {
    height: 16px;
}
.table-scroll-top > div,
.table-scroll-bottom > table {
    width: max-content;
    min-width: 100%;
    margin: 0 !important;
    padding: 0 !important;
}
.table-scroll-top table {
    height: 1px;
    pointer-events: none;
    margin: 0 !important;
    padding: 0 !important;
    border-spacing: 0;
    border-collapse: separate;
    white-space: nowrap;
}
.table-scroll-bottom table {
    white-space: nowrap;
}



.table-scroll-top,
.table-scroll-bottom {
    scrollbar-color: rgba(120,120,120,0.15) rgba(0,0,0,0); /* Firefox */
    scrollbar-width: thin; /* Firefox */
}

/* Webkit (Chrome, Edge, Safari) */
.table-scroll-top::-webkit-scrollbar,
.table-scroll-bottom::-webkit-scrollbar {
    height: 8px;
    background: transparent;
}
.table-scroll-top::-webkit-scrollbar-thumb,
.table-scroll-bottom::-webkit-scrollbar-thumb {
       background: rgba(155, 155, 155, 0.15);
    border-radius: 4px;
    border: 2px solid transparent;
    background-clip: padding-box;
}
.table-scroll-top::-webkit-scrollbar-track,
.table-scroll-bottom::-webkit-scrollbar-track {
    background: transparent;
}

/* Styles for modalUpdateFaktur */
#modalUpdateFaktur .modal-header {
    background-color: #f3f6f9 !important;
    color: #000000 !important; /* Teks hitam */
    border-bottom: 1px  #b0c4de; /* Garis bawah biru lebih gelap */
}

/* Styles for modalPeriode */
#modalPeriode .modal-header {
    background-color: #f3f6f9 !important;
    color: #000000 !important; /* Teks hitam */
    border-bottom: 1px  #b0c4de; /* Garis bawah biru lebih gelap */
}
#modalPeriode .modal-footer {
    background: #e3f2fd; /* biru muda */
    border-top: none;
}

.pagination-info {
    position: relative; /* Tetap di posisi relatif terhadap container */
    z-index: 10; /* Pastikan elemen ini berada di atas elemen lain */
    margin-top: 10px;
    margin-bottom: 20px;
    font-size: 14px;
    text-align: center;
}

/* Dropdown improvement styles */
.select2-container {
    width: 100% !important;
}

/* Match dropdown width to container width */
.select2-container--default .select2-selection--single {
    height: 38px !important;
    border-color: #ced4da;
    border-radius: 0.25rem;
}

/* Improve alignment of text in dropdown */
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 36px !important;
    padding-left: 12px;
    color: #495057;
}



/* Styling for urgent items */
tr.urgent-row {
    background-color: #fff8ed !important; /* Light orange background */
}

tr.urgent-row:hover {
    background-color: #ffefd5 !important; /* Slightly darker orange on hover */
}

/* Palestinian flag styling */
/* Ganti styling bendera Palestina dengan bendera merah standar */
.palestinian-flag {
    display: inline-block;
    position: relative;
    width: 13px; /* Ukuran sedikit lebih besar */
    height: 10px; /* Ukuran sedikit lebih besar */
    margin-left: 7px; /* Memberi ruang pada gagang */
    background-color: #ffd700; /* Warna kuning */
    border-radius: 2px 4px 4px 2px; /* Melengkungkan ujung bendera */
    vertical-align: middle;
    box-shadow: 0 1px 3px rgba(0,0,0,0.3); /* Bayangan lebih kuat */
    animation: wave 2s infinite; /* Animasi kibaran bendera */
    transform-origin: left center; /* Titik pivot animasi di bagian kiri */
}

/* Tambahkan simbol warning (!) dengan lingkaran pada bendera */
.palestinian-flag:after {
    content: '!';
    position: relative;
    left: 5px;
    top: 0px;
    color: #000; /* Warna hitam untuk tanda seru */
    font-size: 7px; /* Ukuran font lebih kecil untuk tanda seru */
    font-weight: bold;
    line-height: 11px;
    width: 7px; /* Lebar lingkaran */
    height: 7px; /* Tinggi lingkaran */
    background-color:rgb(233, 198, 3); /* Background putih untuk lingkaran */
    border-radius: 50%; /* Membuat lingkaran sempurna */
    text-align: center; /* Pusatkan tanda seru */
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 0 1px rgba(0,0,0,0.5); /* Outline tipis untuk lingkaran */
    transform: translateX(-1.5px); /* Geser ke kiri untuk posisi yang tepat */
}

/* Gagang bendera dengan warna kayu yang lebih hidup */
.palestinian-flag:before {
    content: '';
    position: absolute;
    left: -1px; /* Posisi lebih ke kiri */
    top: -1px; /* Sedikit lebih tinggi */
    width: 2.5px; /* Ketebalan gagang */
    height: 18px; /* Gagang lebih tinggi */
    /* Gradien warna kayu yang lebih natural */
    background: linear-gradient(to right, 
        #8B4513 0%, /* SaddleBrown */
        #A0522D 40%, /* Sienna */
        #D2691E 50%, /* Chocolate */
        #A0522D 60%, /* Sienna */
        #8B4513 100% /* SaddleBrown */
    );
    border-radius: 1px;
    box-shadow: -1px 1px 2px rgba(0,0,0,0.2); /* Bayangan pada gagang */
}

/* Animasi kibaran bendera yang lebih hidup */
@keyframes wave {
    0% { transform: rotate(0deg) skewX(0deg); }
    25% { transform: rotate(2deg) skewX(-1deg); }
    50% { transform: rotate(0deg) skewX(0deg); }
    75% { transform: rotate(-2deg) skewX(1deg); }
    100% { transform: rotate(0deg) skewX(0deg); }
}

/* Perbaikan tampilan dalam baris tabel */
td .palestinian-flag {
    margin-top: -1px;
    display: inline-flex; /* Untuk konsistensi dalam baris */
    align-items: center;
}

/* File Upload Styles */
.file-item {
    border-left: 3px solid #17a2b8;
    padding: 10px;
    margin-bottom: 10px;
    background-color: #f8f9fa;
    border-radius: 4px;
    transition: all 0.2s;
}

.file-item:hover {
    background-color: #e9ecef;
}

.file-item .file-icon {
    font-size: 24px;
    color: #6c757d;
}

.file-item .file-pdf {
    color: #dc3545;
}

.file-item .file-image {
    color: #28a745;
}

.file-item .file-actions {
    opacity: 0.7;
    transition: opacity 0.2s;
}

.file-item:hover .file-actions {
    opacity: 1;
}

/* Pastikan tooltip Bootstrap terlihat dengan baik */
.tooltip-inner {
    background-color:rgb(243, 242, 242); /* Warna biru untuk urgent */
    max-width: 300px;
    padding: 5px 10px;
    color: #000000;
    font-weight: bold;
    border-radius: 4px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.tooltip.bs-tooltip-right .arrow:before {
    border-right-color:rgb(243, 242, 242);
}

/* Improved print button styling */
.btn-print {
    background: linear-gradient(to bottom, #17a2b8, #138496);
    color: white;
    border: none;
    width: 26px;
    height: 26px;
    padding: 0;
    font-size: 0.75rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.12);
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-align: center;
}

.btn-print .fa {
    margin: 0;
    line-height: 1;
}

.btn-print:hover {
    background: linear-gradient(to bottom, #138496, #0f6674);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0,0,0,0.15);
    text-decoration: none;
}

.btn-print:active {
    transform: translateY(0);
    box-shadow: 0 1px 2px rgba(0,0,0,0.15);
}

.btn-print .fa {
    font-size: 0.8rem;
}

/* Optional: Add this pulse effect for the first button to draw attention */
@keyframes pulse-blue {
    0% { box-shadow: 0 0 0 0 rgba(23, 162, 184, 0.7); }
    70% { box-shadow: 0 0 0 6px rgba(23, 162, 184, 0); }
    100% { box-shadow: 0 0 0 0 rgba(23, 162, 184, 0); }
}

.table-responsive .btn-print {
    animation: pulse-blue 3s infinite;
    box-shadow: 0 0 0 0 rgba(23, 162, 184, 0.7);
}
</style>