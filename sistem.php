<!DOCTYPE html>
<html lang="en">
<?php
	require_once('config/connection/connection.php');
	require_once('config/connection/security.php');
	require_once('config/function/data.php');
	require_once('config/function/date.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$date	= new Date;
	$menu	= $secu->injection(@$_GET['menu']);
	$sistem	= $data->sistem('url_sis');
	$admin	= $secu->injection(@$_COOKIE['adminkuy']);
	$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
	$level	= $secu->injection(@$_COOKIE['jeniskuy']);
    // add by suryo
    $self_apl    = $data->self_apl();
    // end
	$valid	= $secu->validadmin($admin, $kunci);
	if($valid==false){ header("location:$sistem/signout"); } else {
	$conn	= $base->open();
?>
    <head>
        <!-- Required meta tags -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <!-- Meta -->
        <meta name="description" content="Inventory System">
        <meta name="author" content="Fazlurr">
        <!-- Favicon -->
        <!-- <link rel="shortcut icon" type="image/x-icon" href="<?php echo("$sistem/berkas/sistem/".$data->sistem('favicon_sis')); ?>"> -->
        <title><?php echo($data->sistem('app_sis')); ?></title>
        <!-- vendor css -->
        <link href="<?php echo("$sistem/lib/@fortawesome/fontawesome-free/css/all.min.css"); ?>" rel="stylesheet">
        <link href="<?php echo("$sistem/lib/ionicons/css/ionicons.min.css"); ?>" rel="stylesheet">
        <link href="<?php echo("$sistem/lib/prismjs/themes/prism-tomorrow.css"); ?>" rel="stylesheet">
        <link href="<?php echo("$sistem/sweetalert/css/sweetalert.css"); ?>" rel="stylesheet">
        <link href="<?php echo("$sistem/lib/select2/css/select2.min.css"); ?>" rel="stylesheet">
        <link href="<?php echo("$sistem/config/css/fazlurr.css"); ?>" rel="stylesheet">
                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />    
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <!-- template css -->
        <link rel="stylesheet" href="<?php echo("$sistem/assets/css/cassie.css"); ?>">
    </head>
	<body data-spy="scroll" data-target="#navSection" data-offset="100">
       <div class="modal fade" id="modal1" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                </div>
            </div>
        </div>
        <div class="modal fade" id="modal2" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content modal-content-lg">
                </div>
            </div>
        </div>
        <div class="modal fade" id="modalUploadExcel" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel3" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Upload Excel - Pembayaran Sales</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form id="formUploadExcel" enctype="multipart/form-data">
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Cek Status Faktur (Opsional)</label>
                                <div class="input-group">
                                    <input type="text" id="cek_kode_faktur" class="form-control" placeholder="Masukkan kode faktur untuk cek status">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-info" id="btnCekFaktur">Cek</button>
                                    </div>
                                </div>
                                <div id="hasilCekFaktur" class="mt-2"></div>
                            </div>
                            <hr>
                            <div class="form-group">
                                <label>File Excel (.xlsx, .xls, atau .csv)</label>
                                <input type="file" name="file_excel" id="file_excel" class="form-control" accept=".xlsx,.xls,.csv" required>
                                <small class="form-text text-muted">Format: Kode Faktur (contoh: 0015.01.01/FKT/AP/III/26) | Bank | No Rekening | Nama | Jumlah Bayar | Tanggal</small>
                            </div>
                            <div id="uploadProgress" style="display:none;">
                                <div class="progress">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                                </div>
                                <p class="text-center mt-2">Sedang memproses...</p>
                            </div>
                            <div id="uploadResult"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                            <button type="submit" class="btn btn-primary" id="btnUpload">Upload</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="sidebar">
            <div class="sidebar-header">
                <div>
                    <a href="<?php echo("$sistem/home"); ?>" class="sidebar-logo"><span><?php echo($data->sistem('app_sis')); ?></span></a>
                    <small class="sidebar-logo-headline"><?php echo($data->sistem('tagline_sis')); ?></small>
                </div>
            </div>
            <!-- sidebar-header -->
            <div id="dpSidebarBody" class="sidebar-body">
			<?php require_once('config/frame/sidebar.php'); ?>
            </div>
            <!-- sidebar-body -->
        </div>
        <!-- sidebar -->

        <!-- content -->
        <div class="content">
        <?php
            require_once('config/frame/header.php');
            require_once('config/frame/content.php');
        ?>
            <div class="content-footer">
            &copy; 2025. All Rights Reserved. Created by <a href="#" target="_blank">ThemePixels X Tegar Satya Negara</a>
            </div><!-- content-footer -->
        </div>
        <!-- content -->
    

        <script type="text/javascript" src="<?php echo("$sistem/lib/jquery/jquery.min.js"); ?>"></script>
		<script type="text/javascript" src="<?php echo("$sistem/lib/jqueryui/jquery-ui.min.js"); ?>"></script>
        <script type="text/javascript" src="<?php echo("$sistem/lib/bootstrap/js/bootstrap.bundle.min.js"); ?>"></script>
        <script type="text/javascript" src="<?php echo("$sistem/lib/feather-icons/feather.min.js"); ?>"></script>
        <script type="text/javascript" src="<?php echo("$sistem/lib/perfect-scrollbar/perfect-scrollbar.min.js"); ?>"></script>
        <script type="text/javascript" src="<?php echo("$sistem/lib/prismjs/prism.js"); ?>"></script>
	    <script type="text/javascript" src="<?php echo("$sistem/lib/parsleyjs/parsley.min.js"); ?>"></script>
		<script type="text/javascript" src="<?php echo("$sistem/lib/select2/js/select2.min.js"); ?>"></script>
        <script type="text/javascript" src="<?php echo("$sistem/lib/js-cookie/js.cookie.js"); ?>"></script>
        <script type="text/javascript" src="<?php echo("$sistem/assets/js/cassie.js"); ?>"></script>
        <script type="text/javascript" src="<?php echo("$sistem/sweetalert/js/sweetalert.min.js"); ?>"></script>
        <script type="text/javascript" src="<?php echo("$sistem/config/js/jquery.maskedinput.js"); ?>"></script>
        <script type="text/javascript" src="<?php echo("$sistem/config/js/fazlurr.js"); ?>?t=<?=time()?>"></script>
        
        <script>
            $(function(){
                'use strict'
                $('.select2').select2({
                    placeholder: '-- Pilih Data --',
                    searchInputPlaceholder: 'Search options'
                });
                $('.datepicker').datepicker({
                    dateFormat: 'yy-mm-dd',
                    showOtherMonths: true,
                    selectOtherMonths: true,
                    changeMonth: true,
                    changeYear: true
                });
            });
            // add by tegar
            $(function(){
                var self_apl = "<?php echo $self_apl["id_apl"]; ?>";
                var self_apl_name = "<?php echo $self_apl["nama_apl"]; ?>";
                $('#transfer_apl_type').on('change', function() {
                    var type = $("#transfer_apl_type option:selected").val();
                    transferType(type, self_apl);
                });
                $('#transfer_apl_from').on('change', function() {
                    var type = $("#transfer_apl_type option:selected").val();
                    checkTransferApl(type, 'from', self_apl, self_apl_name);
                });
                $('#transfer_apl_to').on('change', function() {
                    var type = $("#transfer_apl_type option:selected").val();
                    checkTransferApl(type, 'to', self_apl, self_apl_name);
                });
            });
            // $(document).ready(function(){
            //     setInterval(function(){
            //         loadTransferStokNotification('transferstok');
            //     }, 20000);
            // });
            // end
            
            // Cek Faktur Handler
            $('#btnCekFaktur').on('click', function() {
                var kode = $('#cek_kode_faktur').val().trim();
                if (!kode) {
                    $('#hasilCekFaktur').html('<div class="alert alert-warning">Masukkan kode faktur terlebih dahulu</div>');
                    return;
                }
                
                $('#hasilCekFaktur').html('<div class="text-info"><i class="fa fa-spinner fa-spin"></i> Mengecek...</div>');
                
                $.ajax({
                    url: '<?php echo($data->sistem("url_sis")); ?>/modal/psales/cek_faktur.php',
                    type: 'POST',
                    data: { kode_faktur: kode },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success') {
                            var d = res.data;
                            var statusClass = d.status_faktur === 'Lunas' ? 'success' : (d.status_faktur === 'Bayar' ? 'warning' : 'danger');
                            var html = '<div class="alert alert-' + statusClass + '">';
                            html += '<strong>Status: ' + d.status_faktur + '</strong><br>';
                            html += 'Total Faktur: Rp ' + d.total_faktur + '<br>';
                            html += 'Sudah Dibayar: Rp ' + d.sudah_dibayar + '<br>';
                            html += '<strong>Sisa Tagihan: Rp ' + d.sisa_tagihan + '</strong><br>';
                            html += '<small>Jumlah pembayaran: ' + d.jumlah_pembayaran + ' kali</small>';
                            if (d.sisa_tagihan_raw > 0) {
                                html += '<br><strong>Max bayar: Rp ' + d.sisa_tagihan + '</strong>';
                            }
                            html += '</div>';
                            $('#hasilCekFaktur').html(html);
                        } else {
                            $('#hasilCekFaktur').html('<div class="alert alert-danger">' + res.message + '</div>');
                        }
                    },
                    error: function() {
                        $('#hasilCekFaktur').html('<div class="alert alert-danger">Terjadi kesalahan saat mengecek faktur</div>');
                    }
                });
            });
            
            // Upload Excel Handler
            $('#formUploadExcel').on('submit', function(e) {
                e.preventDefault();
                
                var fileInput = $('#file_excel')[0];
                if (!fileInput.files || !fileInput.files[0]) {
                    swal('Error', 'Silakan pilih file Excel terlebih dahulu', 'error');
                    return;
                }
                
                var formData = new FormData(this);
                
                $('#uploadProgress').show();
                $('#btnUpload').prop('disabled', true);
                $('#uploadResult').html('');
                
                $.ajax({
                    url: '<?php echo($data->sistem("url_sis")); ?>/modal/psales/upload_excel.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $('#uploadProgress').hide();
                        $('#btnUpload').prop('disabled', false);
                        
                        if (response.status === 'success') {
                            swal('Berhasil!', response.message, 'success').then(function() {
                                $('#modalUploadExcel').modal('hide');
                                $('#formUploadExcel')[0].reset();
                                loaddata('psales', 1, 15, '', 'salesb');
                            });
                        } else {
                            $('#uploadResult').html('<div class="alert alert-danger">' + response.message + '</div>');
                            swal('Error', response.message, 'error');
                        }
                    },
                    error: function() {
                        $('#uploadProgress').hide();
                        $('#btnUpload').prop('disabled', false);
                        swal('Error', 'Terjadi kesalahan saat upload file', 'error');
                    }
                });
            });
            
            // Reset modal saat ditutup
            $('#modalUploadExcel').on('hidden.bs.modal', function () {
                $('#formUploadExcel')[0].reset();
                $('#uploadResult').html('');
                $('#uploadProgress').hide();
                $('#cek_kode_faktur').val('');
                $('#hasilCekFaktur').html('');
            });
            
        </script>
    </body>
<?php $conn	= $base->close(); } ?>
</html>
