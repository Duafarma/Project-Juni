<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Finance AR</title>
    
    <!-- Add these in the <head> section before any scripts that use select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- Your existing CSS and JS links -->
    <link rel="stylesheet" href="path/to/your/existing/styles.css">
    <script src="path/to/your/existing/scripts.js"></script>
</head>
<body>
<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Penjualan</a></li>
                <li class="breadcrumb-item active" aria-current="page">Finance AR</li>
            </ol>
        </nav>
        <h4 class="content-title">Monitoring Finance AR</h4>
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
) AND A.status_f_pajak = 'belum terbit'";
?>
<input type="hidden" name="caridata" id="caridata" value="<?php echo($cari); ?>" readonly="readonly" />
<input type="hidden" name="halaman" id="halaman" value="1" readonly="readonly" />
<input type="hidden" name="maximal" id="maximal" value="80" readonly="readonly" />
<input type="hidden" id="confirm_current_status" value="">
<div class="content-body">
<div class="row mg-b-10">
        <div class="col-sm-10">
            <button class="btn btn-primary btn-pill btn-xs" data-toggle="modal" data-target="#modalPeriode">
                <i class="fa fa-calendar"></i> Periode
            </button>
            <a href="<?php echo($data->sistem('url_sis').'/laporan/xls/monitoringfi/monitoringfi.php'); ?>" 
               id="btnExportExcel" 
               target="_blank" 
               class="btn btn-success btn-pill btn-xs ml-1">
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
            <a href="<?php echo($data->sistem('url_sis').'/monitoringfi'); ?>">
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
                        <select id="cabang" class="form-control select2" data-placeholder="-- Pilih Cabang --">
                            <option value=""></option>
                            <option value="all_cabang">Semua Cabang</option>
                            <?php while ($row = $cabangQuery->fetch(PDO::FETCH_ASSOC)): ?>
                                <option value="<?= $row['id_apl'] ?>"><?= $row['nama_apl'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <!-- Dropdown Outlet -->
                    <div class="col-lg-6 col-md-6 col-sm-12 mb-3">
                        <label for="outlet" class="form-label fw-medium">Pilih Outlet:</label>
                        <select id="outlet" class="form-control select2" data-placeholder="-- Pilih Outlet --">
                            <option value=""></option>
                            <option value="All">All Outlet</option>
                            <?php while ($row = $outletQuery->fetch(PDO::FETCH_ASSOC)): ?>
                                <option value="<?= $row['id_out'] ?>"><?= $row['nama_out'] ?></option>
                            <?php endwhile; ?>
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
                        <th style="width:200px"></th>
                        <th style="width:200px"></th>
                        <th style="width:115px"></th>
                        
                        <th style="width:100px"></th>
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
                    <th style="width:40px"><center>Action</center></th>
                    <th style="width:180px">Nomor Faktur
                        <a href="#modalCabangSearch" data-toggle="modal" class="btn btn-outline-info btn-icon btn-xs rounded-circle ml-2" title="Cari berdasarkan nomor faktur, nama cabang dan nama outlet">
                            <i class="fa fa-search"></i>
                        </a>
                    </th>
                    <th style="width:200px">Outlet</th>
                    <th style="width:100px">Tgl Faktur</th>
                    <th style="width:120px"><center>TOTAL</center></th>
                    <th style="width:120px">Cabang</th>
                    <th style="width:100px">Jenis Faktur</th> <!-- Add this line -->
                    <th style="width:120px">Dokumen Filing</th>
                    <th style="width:120px">Status TF</th>
                    <th style="width:120px">Upload F.Pajak</th>
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
        <h5 class="modal-title" id="modalCabangSearchTitle">Cari Berdasarkan Nomor Faktur</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label for="modal_cari_cabang">Cari Berdasarkan Nomor Faktur:</label>
          <input type="text" id="modal_cari_cabang" class="form-control" placeholder="Masukkan nomor faktur...">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-info" id="btnSearchCabang">Cari</button>
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

<!-- Add this new modal for displaying uploaded documents -->
<div class="modal fade" id="modalViewDocuments" tabindex="-1" role="dialog" aria-labelledby="modalViewDocumentsLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content shadow-lg border-0">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title" id="modalViewDocumentsLabel">
          <i class="fa fa-file-pdf mr-2"></i>Dokumen Faktur
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="alert alert-light border mb-3 py-2 px-3 d-flex align-items-center">
          <div class="mr-3">
            <i class="fa fa-barcode fa-lg text-info"></i>
          </div>
          <div>
            <div><strong>No. Faktur:</strong> <span id="viewFakturKode" class="text-dark">-</span></div>
            <div><strong>Outlet:</strong> <span id="viewFakturOutlet" class="text-dark">-</span></div>
          </div>
        </div>
        
        <!-- This div will contain the list of uploaded files -->
        <div id="uploadedFilesList" class="mt-3">
          <div class="text-center"><i class="fa fa-spinner fa-spin"></i> Memuat dokumen...</div>
        </div>
      </div>
      <div class="modal-footer bg-light border-0">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">
          <i class="fa fa-times mr-1"></i> Tutup
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Error Modal -->
<div class="modal fade" id="errorModal" tabindex="-1" role="dialog" aria-labelledby="errorModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content shadow-lg border-0">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="errorModalLabel">
          <i class="fa fa-exclamation-circle mr-2"></i>Peringatan
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="d-flex align-items-center">
          <div class="mr-3">
            <i class="fa fa-file-pdf fa-2x text-muted"></i>
          </div>
          <div>
            <p id="errorModalContent" class="mb-0">Tidak dapat menemukan dokumen untuk faktur ini.</p>
          </div>
        </div>
      </div>
      <div class="modal-footer bg-light border-0">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">
          <i class="fa fa-times mr-1"></i> Tutup
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Konfirmasi Update Status -->
<div class="modal fade" id="confirmUpdateModal" tabindex="-1" role="dialog" aria-labelledby="confirmUpdateModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content shadow-lg border-0">
      <div class="modal-header">
        <h5 class="modal-title" id="confirmUpdateModalLabel">Konfirmasi Ubah Status</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p id="confirmMessage"></p>
        <div>
          <strong>Faktur:</strong> <span id="confirm_kode_tfk"></span><br>
          <strong>Outlet:</strong> <span id="confirm_nama_out"></span>
        </div>
        <!-- Add hidden fields -->
        <input type="hidden" id="confirm_id_tfk">
        <input type="hidden" id="confirm_cabang">
        <input type="hidden" id="confirm_current_status">
        <input type="hidden" id="confirm_jenis_faktur"> <!-- Hidden field for jenis_faktur -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="btnConfirmUpdate">Konfirmasi</button>
      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
    // 1. Create hidden inputs for period and set with default values
    if ($('#periode_dari_hidden').length === 0) {
        // Set default period (January 1st of current year to today)
        var currentYear = new Date().getFullYear();
        var defaultStartDate = currentYear + '-01-01';
        var today = new Date().toISOString().split('T')[0];
        
        $('body').append('<input type="hidden" id="periode_dari_hidden" value="' + defaultStartDate + '">');
        $('body').append('<input type="hidden" id="periode_sampai_hidden" value="' + today + '">');
        
        console.log("Default period applied - From:", defaultStartDate, "To:", today);
    }
    
    // 2. Set default values for form inputs in modal
    $('#periode_dari').val($('#periode_dari_hidden').val() || (new Date().getFullYear() + '-01-01'));
    $('#periode_sampai').val($('#periode_sampai_hidden').val() || new Date().toISOString().split('T')[0]);
    
    // 3. Ensure modal uses same defaults
    $('#modalPeriode').on('show.bs.modal', function() {
        if (!$('#periode_dari').val()) {
            $('#periode_dari').val($('#periode_dari_hidden').val());
        }
        if (!$('#periode_sampai').val()) {
            $('#periode_sampai').val($('#periode_sampai_hidden').val());
        }
    });

    // Show date-specific loading indicator on first load
    var currentYear = new Date().getFullYear();
    $('#isitabel').html('<tr><td colspan="9" class="text-center"><i class="fa fa-spinner fa-spin"></i> Memuat data dari 01-01-' + currentYear + ' sampai hari ini...</td></tr>');
    
    // Initialize Select2 only once per element
    $('#cabang').select2({
        width: '100%',
        dropdownAutoWidth: true,
        responsive: true,
        placeholder: '-- Pilih Cabang --',
        allowClear: true
    });

    // Destroy existing Select2 instances before initializing to prevent duplicates
    if ($('#outlet').hasClass('select2-hidden-accessible')) {
        $('#outlet').select2('destroy');
    }
    
    $('#outlet').select2({
        width: '100%',
        dropdownAutoWidth: true,
        responsive: true,
        placeholder: '-- Pilih Outlet --',
        allowClear: true
    });
    
    // Use .off() to prevent duplicate event handlers
    $('#cabang').off('change').on('change', function() {
        loadMonitoringFP();
    });

    $('#outlet').off('change').on('change', function() {
        // Reset pagination when changing outlets
        $('#halaman').val(1);
        loadMonitoringFP();
    });
    
    // Modal search handling - prevent duplicate handlers
    $('#btnSearchCabang').off('click').on('click', function() {
        // Ambil nilai input dari modal
        var kode_tfk = $('#modal_cari_cabang').val();

        // Simpan nilai ke hidden input
        $('#caridata').val(kode_tfk);

        // Tutup modal
        $('#modalCabangSearch').modal('hide');

        // Muat ulang data dengan filter kode_tfk
        loadMonitoringFP();
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
    var dari = $('#periode_dari').val();
    var sampai = $('#periode_sampai').val();
    
    // Reset pagination to page 1 when applying date filters
    $('#halaman').val(1);
    
    // Simpan ke hidden input atau variabel global
    $('#periode_dari_hidden').val(dari);
    $('#periode_sampai_hidden').val(sampai);
    $('#modalPeriode').modal('hide');
    
    // Show loading indicator
    $('#isitabel').html('<tr><td colspan="9" class="text-center"><i class="fa fa-spinner fa-spin"></i> Memuat data sesuai periode...</td></tr>');
    
    loadMonitoringFP();
    
    // Add visual feedback about active filters
    updateActiveFilters();
});

// Add this to your existing script section to improve UX

$(document).ready(function() {
    // Optional: Add a clear selection button to outlet dropdown
    $('#outlet').select2({
        placeholder: "-- Pilih Outlet --",
        allowClear: true
    });
    
    // Show a count of results when selecting "All Outlet"
    $('#outlet').on('change', function() {
        if ($(this).val() === 'All') {
            console.log("All outlets selected - showing data from all outlets");
        }
    });
});

// Detect touch devices and optimize Select2 for touch
if ('ontouchstart' in window || navigator.msMaxTouchPoints) {
    $('.select2').each(function() {
        $(this).select2({
            minimumResultsForSearch: 10,
            width: '100%',
            dropdownAutoWidth: true,
            dropdownParent: $(this).parent(), // Fixes positioning issues on some mobile devices
            selectionCssClass: 'select2-selection--touch'
        });
    });
    
    // Add a class to body for touch-specific styling
    $('body').addClass('touch-device');
}

// Handle orientation change for mobile devices
$(window).on('orientationchange', function() {
    $('.select2-container').css('width', '100%');
});

function loadMonitoringFP() {
    var id_apl = $('#cabang').val();
    var id_out = $('#outlet').val();
    var caridata = $('#caridata').val();
    var cari_cabang = $('#cari_cabang').val();
    var halaman = $('#halaman').val();
    var maximal = $('#maximal').val();
    var menudata = '<?php echo $menu; ?>';
    
    // Ensure default period values are used if not set
    var currentYear = new Date().getFullYear();
    var defaultStartDate = currentYear + '-01-01';
    var today = new Date().toISOString().split('T')[0];
    
    var periode_dari = $('#periode_dari_hidden').val() || defaultStartDate;
    var periode_sampai = $('#periode_sampai_hidden').val() || today;
    
    // Always make sure hidden inputs have values
    $('#periode_dari_hidden').val(periode_dari);
    $('#periode_sampai_hidden').val(periode_sampai);
    
    // Remove previous filter notifications before loading new data
    $('.filter-alert').remove();

    // Show appropriate loading message
    var loadingMessage = 'Loading data...';
    if (id_apl === 'all_cabang') {
        loadingMessage = 'Memuat data dari semua cabang (ini mungkin memerlukan waktu beberapa saat)...';
    }
    if (id_out === 'All') {
        loadingMessage += ' Menampilkan semua outlet.';
    }
    
    $('#isitabel').html('<tr><td colspan="9" class="text-center"><i class="fa fa-spinner fa-spin"></i> ' + 
        loadingMessage + '</td></tr>');

    // Send AJAX request with period parameters always included
    $.ajax({
        url: 'json/monitoringfi/monitoringfi.php',
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
            status_f_pajak: 'belum terbit'
        },
        dataType: 'json',
        success: function(response) {
            console.log("API Response:", response); // Debug logging
            
            if (response.success && Array.isArray(response.data)) {
                var html = '';
                if (response.data.length > 0) {
                    response.data.forEach(function(row) {
                        // Check if row is urgent
                        var isUrgent = false;
                        if (row.urgent_flag === true || (row.status_urgent && 
                           (row.status_urgent.toLowerCase() === 'urgent' || row.status_urgent === '1'))) {
                            isUrgent = true;
                        }
                        
                        // Start row with urgent class if needed
                        html += '<tr' + (isUrgent ? ' class="urgent-row"' : '') + '>';
                        
                        html += '<td align="center">';
                        // Keep the bold styling for urgent items but make all numbers black
                        if (isUrgent) {
                            html += '<span class="font-weight-bold">' + row.no + '</span>';
                        } else {
                            html += row.no;
                        }
                        html += '</td>';
                        
                        html += '<td align="center">';
                        if (row.action) {
                            // Ganti tombol upload status dengan badge seperti status failing
                            if (row.upload_f_pajak === 'sudah') {
                                html += '<span class="badge badge-success rounded-circle p-2 status-badge" ' +
                                        'onclick="toggleUploadStatus(\'' + row.id_tfk + '\', \'' + row.nama_cabang + '\', \'sudah\', \'' + row.kode_tfk + '\', \'' + row.nama_out + '\')" ' +
                                        'data-toggle="tooltip" title="Status upload: sudah">' +
                                        '<i class="fa fa-check"></i></span>';
                            } else {
                                html += '<span class="badge badge-danger rounded-circle p-2 status-badge" ' +
                                        'onclick="toggleUploadStatus(\'' + row.id_tfk + '\', \'' + row.nama_cabang + '\', \'belum\', \'' + row.kode_tfk + '\', \'' + row.nama_out + '\')" ' +
                                        'data-toggle="tooltip" title="Status upload: belum">' +
                                        '<i class="fa fa-times"></i></span>';
                            }
                            
                            // Tombol lihat dokumen hanya muncul jika status upload "sudah"
                            if (row.upload_f_pajak === 'sudah' || (row.action.print_enabled === true)) {
                                html += '<button type="button" class="btn btn-xs btn-outline-primary rounded-pill mx-1" ' +
                                        'onclick="openFakturPrint(\'' + row.id_tfk + '\', \'' + row.nama_cabang + '\')" ' +
                                        'title="Lihat Dokumen"><i class="fa fa-file-pdf"></i></button>';
                            }
                        }
                        html += '</td>';
                        
                        // Add urgent flag indicator + Revisi handling in one cell (fix duplicate kode_tfk)
                        var kodeHtml = row.kode_tfk || '-';
                        if (row.status_tfk && String(row.status_tfk).toLowerCase() === 'revisi') {
                            kodeHtml = '<span class="revisi-number">' + kodeHtml + '</span> <span class="r-logo" title="Revisi">R</span>';
                        }

                        html += '<td>';
                        if (isUrgent) {
                            html += '<div class="d-flex align-items-center">' +
                                    kodeHtml +
                                    '<span class="palestinian-flag ml-2" title="Urgent"></span>' +
                                    '</div>';
                        } else {
                            html += kodeHtml;
                        }
                        html += '</td>';
                        // --- START MODIFIED: render kode_tfk with Revisi handling ---
                        // compute kode cell HTML: blue + R logo when status_tfk == 'Revisi'
                        var kodeHtml = row.kode_tfk || '-';
                        if (row.status_tfk && String(row.status_tfk).toLowerCase() === 'revisi') {
                            // use same CSS class name used in monitoringfp to keep consistent look
                            kodeHtml = '<span class="revisi-number">' + kodeHtml + '</span> <span class="r-logo" title="Revisi">R</span>';
                        }
                        
                        // Rest of your columns remain the same
                        html += '<td>' + row.nama_out + '</td>';
                        html += '<td>' + row.tgl_tfk + '</td>';
                        html += '<td align="center">' + row.subtot_tfk + '</td>';
                        html += '<td>' + (row.nama_cabang || '') + '</td>';
                        html += '<td>' + (row.jenis_faktur || '') + '</td>'; <!-- Add this line -->
                        
                        html += '<td align="center">';
                        if (row.status_failing === 'sudah failing' || row.status_failing === 'Sudah failing') {
                            html += '<span class="badge badge-success rounded-circle p-2" data-toggle="tooltip" title="sudah failing">' + 
                                    '<i class="fa fa-check"></i></span>';
                        } else {
                            html += '<span class="badge badge-danger rounded-circle p-2" data-toggle="tooltip" title="belum failing">' + 
                                    '<i class="fa fa-times"></i></span>';
                        }
                        html += '</td>';
                        
                        html += '<td align="center">';
                        if (row.status_tfkkf === 'Sudah Dikirim' || row.status_tfkkf === 'Sudah Dikirim') {
                            html += '<span class="badge badge-success rounded-circle p-2" data-toggle="tooltip" title="Sudah Dikirim">' + 
                                    '<i class="fa fa-check"></i></span>';
                        } else {
                            html += '<span class="badge badge-danger rounded-circle p-2" data-toggle="tooltip" title="Belum Dikirim">' + 
                                    '<i class="fa fa-times"></i></span>';
                        }
                        html += '</td>';
                        
                        html += '<td align="center">' + row.status_tfk + '</td>';
                        html += '</td>';
                        html += '</tr>';
                    });
                } else {
                    html = '<tr><td colspan="10" class="text-center">Data tidak ditemukan.</td></tr>';
                }
                $('#isitabel').html(html);
                $('#paginasi').html(response.paginasi || '');
                updateActiveFilters(); 
                updatePaginationInfo(response.total, parseInt($('#halaman').val()), parseInt($('#maximal').val())); // Update pagination info
                
                // Aktifkan tooltip untuk semua elemen baru
                $('[data-toggle="tooltip"]').tooltip();
            } else {
                $('#isitabel').html('<tr><td colspan="10" class="text-center">'+
                    (response.message || 'Data tidak ditemukan atau format salah.')+
                    '</td></tr>');
                $('#paginasi').html('');
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error:", status, error);
            console.error("Response:", xhr.responseText);
            $('#isitabel').html('<tr><td colspan="9" class="text-center">Terjadi kesalahan saat mengambil data.</td></tr>');
            $('#paginasi').html('');
        }
    });
}

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
            'Semua Cabang' : $('#cabang option:selected').text();
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

// Delegate event untuk klik pada paginasi
$(document).on('click', '.pagination a', function(e) {
    e.preventDefault();
    var halaman = $(this).data('page') || $(this).attr('data-page') || $(this).text();
    if (!halaman || isNaN(halaman)) return;
    $('#halaman').val(halaman);
    loadMonitoringFP();
});

function updatePaginationInfo(total, currentPage, maxPerPage) {
    // Hapus elemen pagination-info sebelumnya
    $('.pagination-info').remove();

    var totalPages = Math.ceil(total / maxPerPage);
    var startRecord = total === 0 ? 0 : ((currentPage - 1) * maxPerPage) + 1;
    var endRecord = Math.min(currentPage * maxPerPage, total);

    // Buat teks informasi pagination
    var paginationInfo = '';
    if (total > 0) {
        paginationInfo = 'Menampilkan ' + startRecord + ' sampai ' + endRecord +
                         ' dari ' + total + ' data';

        // Periksa apakah filter tanggal aktif
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

    // Buat elemen div untuk informasi pagination
    var paginationInfoHtml = '<div class="alert alert-info mt-2 mb-2 p-2 pagination-info">' +
        '<small><i class="fa fa-list-ol mr-1"></i>' + paginationInfo + '</small></div>';

    // Tambahkan elemen ini di bawah tabel
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

// Replace the current openUploadedDocument function with this enhanced version
function openUploadedDocument(id_tfk, cabang, kode_tfk, nama_out) {
    // Set details in the modal
    $('#viewFakturKode').text(kode_tfk || '-');
    $('#viewFakturOutlet').text(nama_out || '-');
    
    // Load document history
    loadUploadHistory(id_tfk, cabang);
    
    // Show the modal
    $('#modalViewDocuments').modal('show');
}

// Add this function to load upload history
function loadUploadHistory(id_tfk, cabang) {
    $('#uploadedFilesList').html('<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Memuat dokumen...</div>');
    
    $.ajax({
        url: 'json/monitoringfi/getUploadHistory.php',
        type: 'GET',
        data: {
            id_tfk: id_tfk,
            cabang: cabang
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
                    html += '<a href="' + file.url_upload + '" target="_blank" class="btn btn-sm btn-outline-info" title="Lihat Dokumen"><i class="fa fa-eye"></i></a>';
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

// Add this new function after the existing openFakturModal function:

function openFakturPrint(id_tfk, cabang) {
    // First, get the latest uploaded document URL for this invoice
    $.ajax({
        url: 'json/monitoringfi/getLatestUpload.php',
        type: 'GET',
        data: {
            id_tfk: id_tfk,
            cabang: cabang
        },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.url) {
                // Open the XPS viewer in a new tab with the document URL
                var printUrl = '<?php echo $data->sistem('url_sis'); ?>/laporan/xps/monitoringfi/monitoringfi.php?id_tfk=' + 
                              id_tfk + '&url=' + encodeURIComponent(response.url);
                window.open(printUrl, '_blank');
            } else {
                // Show error modal instead of alert
                $('#errorModalContent').text('Tidak dapat menemukan dokumen untuk faktur ini.');
                $('#errorModal').modal('show');
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error:", status, error);
            // Show error modal with more specific error message
            $('#errorModalContent').text('Terjadi kesalahan saat mengambil data dokumen: ' + error);
            $('#errorModal').modal('show');
        }
    });
}

// Function to toggle upload_f_pajak between "belum" and "sudah"
function toggleUploadStatus(id_tfk, cabang, currentStatus, kode_tfk, nama_out, jenis_faktur) {
    // Reset modal state
    $('.modal-body .alert-success, .modal-body .alert-danger, .modal-body .loading-message').remove();
    
    // Pastikan jenis_faktur tidak undefined dan dilakukan pengkodean karakter dengan benar
    if (!jenis_faktur || jenis_faktur === undefined) {
        // Default ke PIM jika ID faktur mengandung pola tertentu (misalnya dimulai dengan PIM)
        if (kode_tfk && kode_tfk.toUpperCase().indexOf('PIM') !== -1) {
            jenis_faktur = 'PIM';
        } else {
            jenis_faktur = 'Cendo & DPE';
        }
    }
    
    // Pastikan jenis_faktur hanya berisi "PIM" atau "Cendo & DPE" (decode html entity jika ada)
    jenis_faktur = jenis_faktur.replace(/&amp;/g, '&');
    
    console.log('Toggle upload status for invoice: ' + id_tfk + ', Type: ' + jenis_faktur);
    
    // Set values to hidden fields
    $('#confirm_id_tfk').val(id_tfk);
    $('#confirm_cabang').val(cabang);
    $('#confirm_kode_tfk').text(kode_tfk || '-');
    $('#confirm_nama_out').text(nama_out || '-');
    $('#confirm_current_status').val(currentStatus);
    $('#confirm_jenis_faktur').val(jenis_faktur); // Set jenis_faktur dengan nilai yang sudah dibersihkan
    
    // Update modal title and message
    var modalTitle = "Faktur " + (jenis_faktur === 'PIM' ? 'PIM' : 'Cendo & DPE');
    $('#confirmUpdateModalLabel').text('Konfirmasi Ubah Status - ' + modalTitle);
    
    if (currentStatus === 'sudah') {
        $('#confirmMessage').html('Apakah anda yakin ingin mengubah status upload faktur pajak <strong>' + kode_tfk + '</strong> dari <strong>SUDAH</strong> menjadi <strong>BELUM</strong> diupload? (Jenis: ' + jenis_faktur + ')');
    } else {
        $('#confirmMessage').html('Apakah anda yakin ingin mengubah status upload faktur pajak <strong>' + kode_tfk + '</strong> dari <strong>BELUM</strong> menjadi <strong>SUDAH</strong> diupload? (Jenis: ' + jenis_faktur + ')');
    }
    
    // Show the confirmation modal
    $('#confirmUpdateModal').modal('show');
}

// Handler untuk tombol konfirmasi update
$('#btnConfirmUpdate').on('click', function() {
    var id_tfk = $('#confirm_id_tfk').val();
    var cabang = $('#confirm_cabang').val();
    var currentStatus = $('#confirm_current_status').val();
    var jenis_faktur = $('#confirm_jenis_faktur').val();
    var kode_tfk = $('#confirm_kode_tfk').text();
    
    // Normalize jenis_faktur
    if (jenis_faktur === 'Cendo &amp; DPE') {
        jenis_faktur = 'Cendo & DPE';
    }
    
    // Log untuk debugging
    console.log('Update request untuk faktur:', {id_tfk, cabang, currentStatus, jenis_faktur});
    
    // Tentukan status baru
    var newStatus = (currentStatus === 'sudah') ? 'belum' : 'sudah';
    
    // Nonaktifkan tombol selama proses
    $('#btnConfirmUpdate').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Proses...');
    
    // Clear previous messages
    $('.modal-body .alert-success, .modal-body .alert-danger, .modal-body .loading-message').remove();
    
    // Tambahkan pesan loading
    $('.modal-body').append('<div class="loading-message text-center mt-3"><i class="fa fa-spinner fa-spin"></i> Memverifikasi faktur...</div>');
    
    // Verifikasi faktur terlebih dahulu dengan jenis yang benar
    $.ajax({
        url: '<?php echo $sistem; ?>/json/monitoringfi/check_invoice.php',
        type: 'POST',
        data: {
            id_tfk: id_tfk,
            cabang: cabang,
            jenis_faktur: jenis_faktur
        },
        dataType: 'json',
        success: function(checkResponse) {
            console.log('Check response:', checkResponse);
            
            if (checkResponse.exists) {
                // Gunakan jenis_faktur yang dikembalikan dari check_invoice.php
                $('.loading-message').html('<i class="fa fa-spinner fa-spin"></i> Memproses perubahan status...');
                
                // Kirim permintaan untuk update status
                $.ajax({
                    url: '<?php echo $sistem; ?>/json/monitoringfi/upload_status_api.php',
                    type: 'POST',
                    data: {
                        id_tfk: id_tfk,
                        cabang: cabang,
                        upload_f_pajak: newStatus,
                        jenis_faktur: checkResponse.jenis_faktur // Gunakan jenis dari respons
                    },
                    dataType: 'json',
                    success: function(response) {
                        // Process response
                        $('#btnConfirmUpdate').prop('disabled', false).html('Konfirmasi');
                        $('.loading-message').remove();
                        
                        if (response.success) {
                            // Tampilkan pesan sukses tanpa tombol refresh
                            $('.modal-body').append('<div class="alert alert-success mt-3">' + response.message + '</div>');
                            
                            // Update badge status di tabel secara dinamis
                            updateStatusBadgeInTable(id_tfk, newStatus);
                            
                            // Tidak perlu menambahkan tombol tutup
                            
                            // Perbarui nilai di hidden field untuk status berikutnya
                            $('#confirm_current_status').val(newStatus);
                            
                            // Auto close modal setelah beberapa detik
                            setTimeout(function() {
                                $('#confirmUpdateModal').modal('hide');
                            }, 1200); // Tutup setelah 1.2 detik
                        } else {
                            $('.modal-body').append('<div class="alert alert-danger mt-3">' + response.message + '</div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#btnConfirmUpdate').prop('disabled', false).html('Konfirmasi');
                        $('.loading-message').remove();
                        $('.modal-body').append('<div class="alert alert-danger mt-3">Error: ' + error + '</div>');
                    }
                });
            } else {
                // Faktur tidak ditemukan
                $('#btnConfirmUpdate').prop('disabled', false).html('Konfirmasi');
                $('.loading-message').remove();
                $('.modal-body').append('<div class="alert alert-danger mt-3">' + checkResponse.message + '</div>');
            }
        },
        error: function(xhr, status, error) {
            $('#btnConfirmUpdate').prop('disabled', false).html('Konfirmasi');
            $('.loading-message').remove();
            $('.modal-body').append('<div class="alert alert-danger mt-3">Error: ' + error + '</div>');
        }
    });
});

// Fungsi untuk memperbarui status badge di tabel
function updateStatusBadgeInTable(id_tfk, newStatus) {
    // Temukan elemen badge yang terkait dengan faktur ini
    var badgeElement = $('span.status-badge[onclick*="' + id_tfk + '"]');
    
    if (badgeElement.length > 0) {
        // Hapus kelas yang ada
        badgeElement.removeClass('badge-success badge-danger');
        
        // Tambahkan kelas sesuai status baru
        if (newStatus === 'sudah') {
            badgeElement.addClass('badge-success');
            badgeElement.html('<i class="fa fa-check"></i>');
            badgeElement.attr('title', 'Status upload: sudah');
            
            // Tambahkan tombol lihat dokumen jika belum ada
            var actionCell = badgeElement.parent();
            if (actionCell.find('button.btn-outline-primary').length === 0) {
                var kode_tfk = $('#confirm_kode_tfk').text();
                var cabang = $('#confirm_cabang').val();
                var nama_out = $('#confirm_nama_out').text();
                
                actionCell.append(
                    '<button type="button" class="btn btn-xs btn-outline-primary rounded-pill mx-1 status-changed" ' +
                    'onclick="openFakturPrint(\'' + id_tfk + '\', \'' + cabang + '\')" ' +
                    'title="Lihat Dokumen"><i class="fa fa-file-pdf"></i></button>'
                );
            }
        } else {
            badgeElement.addClass('badge-danger');
            badgeElement.html('<i class="fa fa-times"></i>');
            badgeElement.attr('title', 'Status upload: belum');
            
            // Hapus tombol lihat dokumen jika ada
            badgeElement.parent().find('button.btn-outline-primary').remove();
        }
        
        // Tambahkan animasi untuk menunjukkan perubahan
        badgeElement.addClass('status-changed');
        setTimeout(function() {
            badgeElement.removeClass('status-changed');
        }, 1000);
        
        // Perbarui onclick handler untuk status baru
        var onclickAttr = badgeElement.attr('onclick');
        var updatedOnclick = onclickAttr.replace(
            /\'(belum|sudah)\'/,
            '\'' + newStatus + '\''
        );
        badgeElement.attr('onclick', updatedOnclick);
    }
}

// Fungsi untuk memperbarui pesan konfirmasi jika pengguna ingin mengubah status lagi
function updateConfirmMessage(kode_tfk, currentStatus, jenis_faktur) {
    if (currentStatus === 'sudah') {
        $('#confirmMessage').html('Apakah anda yakin ingin mengubah status upload faktur pajak <strong>' + kode_tfk + '</strong> dari <strong>SUDAH</strong> menjadi <strong>BELUM</strong> diupload? (Jenis: ' + jenis_faktur + ')');
    } else {
        $('#confirmMessage').html('Apakah anda yakin ingin mengubah status upload faktur pajak <strong>' + kode_tfk + '</strong> dari <strong>BELUM</strong> menjadi <strong>SUDAH</strong> diupload? (Jenis: ' + jenis_faktur + ')');
    }
}

// Add this function to dynamically update Excel export URL with current filters
function updateExcelUrl() {
    var baseUrl = '<?php echo($data->sistem('url_sis').'/laporan/xls/monitoringfi/monitoringfi.php'); ?>';
    var params = {};
    
    // Get filter values
    var id_apl = $('#cabang').val() || '';
    var id_out = $('#outlet').val() || '';
    var caridata = $('#caridata').val() || '';
    var periode_dari = $('#periode_dari_hidden').val() || '';
    var periode_sampai = $('#periode_sampai_hidden').val() || '';
    
    // Add parameters if they have values
    if (id_apl) params.id_apl = id_apl;
    if (id_out && id_out !== 'All') params.id_out = id_out;
    if (caridata) params.caridata = caridata;
    if (periode_dari) params.periode_dari = periode_dari;
    if (periode_sampai) params.periode_sampai = periode_sampai;
    
    // Convert params object to query string
    var queryString = Object.keys(params)
        .map(function(key) { 
            return encodeURIComponent(key) + '=' + encodeURIComponent(params[key]); 
        })
        .join('&');
    
    // Update button URL
    var excelUrl = baseUrl + (queryString ? '?' + queryString : '');
    $('#btnExportExcel').attr('href', excelUrl);
    
    console.log("Excel URL updated:", excelUrl);
}

// Call this function whenever filters change
$(document).ready(function() {
    // Initial URL setup
    updateExcelUrl();
    
    // Update URL when filters change
    $('#cabang, #outlet').on('change', function() {
        updateExcelUrl();
    });
    
    // After applying date filters
    $('#btnPilihPeriode').on('click', function() {
        setTimeout(updateExcelUrl, 100); // Small delay to ensure hidden fields are updated
    });
    
    // After search
    $('#btnSearchCabang').on('click', function() {
        setTimeout(updateExcelUrl, 100);
    });
    
    // Direct click handler for the Excel button to ensure latest filters
    $('#btnExportExcel').on('click', function(e) {
        e.preventDefault();
        updateExcelUrl();
        
        // Open in new tab with latest URL
        var url = $(this).attr('href');
        window.open(url, '_blank');
    });
});

$(document).ready(function() {
    // Ensure dropdown containers only have one select element
    if ($('#dropdownCabangContainer').find('select').length > 1) {
        $('#dropdownCabangContainer').find('select:gt(0)').remove();
    }
    
    if ($('#dropdownOutletContainer').find('select').length > 1) {
        $('#dropdownOutletContainer').find('select:gt(0)').remove();
    }
    
    if ($('#dropdownDataContainer').find('select').length > 1) {
        $('#dropdownDataContainer').find('select:gt(0)').remove();
    }
    
    // Remove any duplicate Select2 instances if they exist
    if ($('#cabang').hasClass('select2-hidden-accessible')) {
        $('#cabang').select2('destroy');
    }
    
    if ($('#outlet').hasClass('select2-hidden-accessible')) {
        $('#outlet').select2('destroy');
    }
    
    if ($('#pilih_data').hasClass('select2-hidden-accessible')) {
        $('#pilih_data').select2('destroy');
    }
    
    // Then initialize Select2
    $('#cabang, #outlet, #pilih_data').select2({
        width: '100%',
        dropdownAutoWidth: true,
        responsive: true,
        allowClear: true
    });
    
    // Set specific placeholders
    $('#cabang').select2('destroy').select2({
        width: '100%',
        dropdownAutoWidth: true,
        responsive: true,
        placeholder: '-- Pilih Cabang --',
        allowClear: true
    });
    
    $('#outlet').select2('destroy').select2({
        width: '100%',
        dropdownAutoWidth: true,
        responsive: true,
        placeholder: '-- Pilih Outlet --',
        allowClear: true
    });
    
    $('#pilih_data').select2('destroy').select2({
        width: '100%',
        dropdownAutoWidth: true,
        responsive: true,
        placeholder: '-- Pilih Data --',
        allowClear: true
    });
});
</script>

<style>
.btn-icon:hover {
    transform: scale(1.1);
    transition: transform 0.2s;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.btn-xs.rounded-circle {
    width: 26px;
    height: 26px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin: 0 3px;
    transition: all 0.2s;
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
    border-bottom: 1px solid #e9ecef;
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

/* Tambahkan ke bagian <style> yang sudah ada */

/* Styling untuk badge status */
.badge.rounded-circle {
  width: 28px;
  height: 28px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  margin: 0 2px;
}

.badge.rounded-circle:hover {
  transform: scale(1.1);
}

/* Status badge styling for clickable badges */
.status-badge {
    cursor: pointer;
    width: 28px;
    height: 28px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    margin: 0 2px;
}

.status-badge:hover {
    transform: scale(1.15);
    box-shadow: 0 0 5px rgba(0, 0, 0, 0.3);
}

/* Success badge (green checkmark) */
.badge.badge-success {
    background-color: #28a745;
    border: 2px solid #1e7e34;
}

/* Danger badge (red X) */
.badge.badge-danger {
    background-color: #dc3545;
    border: 2px solid #c82333;
}

/* Highlight animation for when badge is clicked */
@keyframes badgeClick {
    0% { transform: scale(1); }
    50% { transform: scale(0.8); }
    100% { transform: scale(1); }
}

.status-badge:active {
    animation: badgeClick 0.3s ease-in-out;
}

/* Animasi untuk menarik perhatian pada status yang butuh tindakan */
@keyframes pulse-red {
  0% {
    box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
  }
  70% {
    box-shadow: 0 0 0 5px rgba(220, 53, 69, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
  }
}

.badge.badge-danger.rounded-circle {
  animation: pulse-red 1.5s infinite;
}

/* Styling for urgent items */
tr.urgent-row {
    background-color: #fff8ed !important; /* Light orange background */
}

tr.urgent-row:hover {
    background-color: #ffefd5 !important; /* Slightly darker orange on hover */
}

/* Urgent flag indicator */
.palestinian-flag {
    display: inline-block;
    position: relative;
    width: 13px;
    height: 10px;
    margin-left: 7px;
    background-color: #ffd700; /* Yellow color */
    border-radius: 2px 4px 4px 2px;
    vertical-align: middle;
    box-shadow: 0 1px 3px rgba(0,0,0,0.3);
    animation: wave 2s infinite;
    transform-origin: left center;
}

/* Add exclamation mark with circle on the flag */
.palestinian-flag:after {
    content: '!';

    position: relative;
    left: 5px;
    top: 0px;

    color: #000;
    font-size: 7px;
    font-weight: bold;
    line-height: 11px;

    width: 7px;
    height: 7px;

    background-color: rgb(233, 198, 3);
    border-radius: 50%;

    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;

    box-shadow: 0 0 1px rgba(0,0,0,0.5);
    transform: translateX(-1.5px);
}

/* Flag pole with more natural wood color */
.palestinian-flag:before {
    content: '';

    position: absolute;
    left: -1px;
    top: -1px;

    width: 2.5px;
    height: 18px;

    background: linear-gradient(to right, 
        #8B4513 0%,
        #A0522D 40%,
        #D2691E 50%,
        #A0522D 60%,
        #8B4513 100%
    );

    border-radius: 1px;

    box-shadow: -1px 1px 2px rgba(0,0,0,0.2);
}

/* Flag waving animation */
@keyframes wave {
    0% { transform: rotate(0deg) skewX(0deg); }
    25% { transform: rotate(2deg) skewX(-1deg); }
    50% { transform: rotate(0deg) skewX(0deg); }
    75% { transform: rotate(-2deg) skewX(1deg); }
    100% { transform: rotate(0deg) skewX(0deg); }
}

/* Ensure proper alignment in table cells */
td .palestinian-flag {
    margin-top: -1px;
    display: inline-flex;
    align-items: center;
}

/* Add to your existing styles */
/* Responsive Select2 styles */
.select2-container {
    width: 100% !important;
    max-width: 100%;
}

.select2-container .select2-selection--single {
    height: 38px;
    display: flex;
    align-items: center;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px;
}

/* Mobile adjustments */
@media (max-width: 767.98px) {
    .select2-container .select2-dropdown {
        width: auto !important;
        min-width: 100% !important;
    }
    
    .select2-results {
        max-height: 200px;
        overflow-y: auto;
    }
    
    /* Improve tap target size on mobile */
    .select2-container--default .select2-results__option {
        padding: 8px 12px;
    }
    
    /* Fix Select2 dropdown positioning on mobile */
    .select2-container--open .select2-dropdown {
        left: 0;
    }
}

/* Make the dropdowns full width on small screens with some margin */
@media (max-width: 576px) {
    .col-sm-12 {
        padding-left: 10px;
        padding-right: 10px;
    }
    
    .card-body {
        padding: 15px 10px;
    }
}

/* Add these styles to the existing <style> section */

/* Loading animation */
.loading-message {
    padding: 10px;
    border-radius: 5px;
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
}

/* Success animation */
@keyframes fadeInSuccess {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.alert-success {
    animation: fadeInSuccess 0.3s ease-in-out;
}

/* Button transitions */
.btn {
    transition: all 0.2s ease-in-out;
}

.btn:disabled {
    cursor: not-allowed;
    opacity: 0.7;
}

/* Status Badge Animation */
@keyframes statusChange {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

.status-changed {
    animation: statusChange 0.5s ease-in-out;
}
.r-logo {
    display: inline-block;
    margin-left: 6px;
    background: #007bff;
    color: #ffffff;
    font-weight: 700;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    line-height: 18px;
    text-align: center;
    font-size: 12px;
    vertical-align: middle;
    box-shadow: 0 1px 2px rgba(0,0,0,0.15);
}
.revisi-number {
    color: #007bff !important; /* bootstrap primary blue */
    font-weight: 700;
}
</style>
</body>
</html>