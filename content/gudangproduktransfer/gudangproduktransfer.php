<?php
$cari = $secu->injection(@$_GET['cari']);
?>
<!-- Content Header with Improved Styling -->
<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Inventory</a></li>
                <li class="breadcrumb-item active" aria-current="page">Transfer Gudang</li>
            </ol>
        </nav>
        <h4 class="content-title">Transfer Gudang</h4>
    </div>
</div>

<input type="hidden" name="caridata" id="caridata" value="<?php echo ($cari); ?>" readonly="readonly" />
<input type="hidden" name="halaman" id="halaman" value="0" readonly="readonly" />
<input type="hidden" name="maximal" id="maximal" value="15" readonly="readonly" />

<div class="content-body">
    <div class="row mg-b-10">
        <div class="col-sm-6">
            <!-- Tetap pertahankan tombol utama -->
            <a href="<?php echo $sistem; ?>/gudangproduktransfer/i"><button class="btn btn-primary btn-pill btn-xs"><i class="fa fa-plus-circle"></i> Transfer Baru</button></a>
            <a href="#modal1" onclick="<?php echo ("caridata('caridata', 'gudangproduktransfer', '$cari')"); ?>" data-toggle="modal"><button class="btn btn-warning btn-pill btn-xs"><i class="fa fa-search"></i> Cari Data</button></a>
            <a href="<?php echo ($data->sistem('url_sis') . '/gudangproduktransfer'); ?>"><button class="btn btn-info btn-pill btn-xs"><i class="fa fa-spinner"></i> Refresh</button></a>
        </div>
        <div class="col-sm-6">
            <?php if (!empty($cari)): ?>
                <span class="badge badge-pill badge-danger"><i class="fa fa-search"></i> Search : <?php echo ($cari); ?></span>
            <?php endif; ?>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mg-b-0">
            <thead>
                <tr>
                    <th>
                        <center>#</center>
                    </th>
                    <th>No Referensi</th>
                    <th>Tgl</th>
                    <th>Gudang Asal</th>
                    <th>Gudang Tujuan</th>
                    <th>Status</th>
                    <th>
                        <center>Action</center>
                    </th>
                </tr>
            </thead>
            <tbody id="viewdata">
                <!-- Data will be loaded dynamically via AJAX -->
            </tbody>
        </table>
        <div class="mg-t-10">
            <nav aria-label="Page navigation example">
                <ul class="pagination pagination-circle mg-b-0" id="paginasi"></ul>
            </nav>
            <div id="page-info"></div>
        </div>
    </div>
</div>

<!-- Modal untuk operasi detail dan pencarian -->
<div id="modal1" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="modal1Label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-vertical-center" role="document">
        <div class="modal-content bd-0 tx-14">
            <div class="modal-body pd-0">
                <div class="row flex-row-reverse">
                    <div class="col-lg-12 pd-lg-25 pd-0">
                        <div id="Content"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk pencarian -->
<div id="modalcari" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="modalcariLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-vertical-center" role="document">
        <div class="modal-content bd-0 tx-14">
            <div class="modal-header pd-y-20 pd-x-25">
                <h6 class="tx-16 mg-b-0 tx-uppercase tx-inverse tx-bold">Cari Data</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="form_cari" action="#" method="get" autocomplete="off">
                <div class="modal-body pd-25">
                    <div class="form-group">
                        <input type="text" name="cari" id="caridatax" class="form-control" placeholder="Masukan kata kunci..." value="<?php echo $cari; ?>" />
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary tx-11 tx-uppercase pd-y-12 pd-x-25 tx-mont pd-r-15" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary tx-11 tx-uppercase pd-y-12 pd-x-25 tx-mont pd-r-15">Cari Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        // Inisialisasi - Pastikan menggunakan halaman 1 sebagai default
        $('#halaman').val(1);
        transmuted('<?php echo ($data->sistem('url_sis')); ?>/json/gudangproduktransfer/gudangproduktransfer.php');

        // Event form submit pencarian
        $('#form_cari').submit(function(e) {
            e.preventDefault();
            $('#caridata').val($('#caridatax').val());
            $('#modalcari').modal('hide');
            $('#halaman').val(1); // Reset ke halaman 1 saat pencarian
            transmuted('<?php echo ($data->sistem('url_sis')); ?>/json/gudangproduktransfer/gudangproduktransfer.php');
        });
    });

    function transmuted(url) {
        var cari = $('#caridata').val();
        var hlm = $('#halaman').val();
        var maximal = $('#maximal').val();
        
        // Debug konsistensi nilai halaman
        console.log("Requesting page:", hlm, "with search:", cari);

        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            data: {
                'caridata': cari,
                'halaman': hlm, // Pastikan nilai ini selalu >= 1
                'maximal': maximal,
                'menudata': 'gudangproduktransfer'
            },
            beforeSend: function() {
                $('#viewdata').html('<tr><td colspan="7" align="center"><i class="fa fa-spinner fa-spin"></i> Memuat Data...</td></tr>');
                $('#paginasi').html('');
                $('#page-info').html('');
            },
            success: function(response) {
                console.log("Response received:", response);
                
                // Validasi respons
                if (!response || !response.success) {
                    $('#viewdata').html('<tr><td colspan="7" align="center">Terjadi kesalahan saat memuat data</td></tr>');
                    return;
                }
                
                // Tampilkan data tabel - gunakan respons.tabel jika tersedia
                if (response.tabel) {
                    $('#viewdata').html(response.tabel);
                } else if (response.data && response.data.transfers) {
                    renderTableFromData(response.data.transfers, response.data.pagination);
                } else {
                    $('#viewdata').html('<tr><td colspan="7" align="center">Tidak ada data transfer gudang yang ditemukan</td></tr>');
                }

                // Tampilkan paginasi
                if (response.paginasi) {
                    // Ganti onclick handler dari viewdata ke transmuted
                    var paginasiHtml = response.paginasi.replace(/viewdata\([^)]+\)/g, function(match) {
                        // Extract parameters
                        var paramsMatch = match.match(/viewdata\('([^']+)',\s*(\d+),\s*(\d+)\)/);
                        if (paramsMatch) {
                            return "transmutedPage(" + paramsMatch[3] + ")";
                        }
                        return match;
                    });
                    $('#paginasi').html(paginasiHtml);
                } else if (response.data && response.data.pagination) {
                    renderPaginationFromData(response.data.pagination);
                } else {
                    $('#paginasi').html('');
                }
                
                // Update halaman di hidden field untuk konsistensi
                if (response.halaman !== undefined) {
                    $('#halaman').val(response.halaman);
                    console.log("Updated page value to:", response.halaman);
                }
                
                // Tampilkan info halaman
                if (response.data && response.data.pagination) {
                    var pagination = response.data.pagination;
                    var total = parseInt(pagination.total);
                    var page = parseInt(pagination.page);
                    var limit = parseInt(pagination.limit);
                    
                    var start = ((page - 1) * limit) + 1;
                    var end = Math.min(page * limit, total);
                    
                    var pageInfo = '<div class="text-muted text-center mt-2">Menampilkan ' + 
                        start + ' sampai ' + end + ' dari ' + total + ' data</div>';
                    $('#page-info').html(pageInfo);
                }
            },
            error: function(xhr, status, error) {
                $('#viewdata').html('<tr><td colspan="7" align="center">Error: ' + error + '</td></tr>');
                console.error("AJAX Error:", xhr.responseText);
            }
        });
    }

    // Helper untuk navigasi halaman
    function transmutedPage(page) {
        $('#halaman').val(page);
        transmuted('<?php echo($data->sistem('url_sis')); ?>/json/gudangproduktransfer/gudangproduktransfer.php');
    }

    // Helper function untuk membuat tabel dari data JSON
    function renderTableFromData(transfers, pagination) {
        var html = '';
        if (transfers && transfers.length > 0) {
            var offset = ((pagination.page - 1) * pagination.limit);
            
            transfers.forEach(function(item, index) {
                var no = offset + index + 1; // Nomor urut yang benar
                
                html += '<tr>';
                html += '<td align="center">' + no + '</td>';
                html += '<td>' + item.kode_ttg + '</td>';
                html += '<td>' + item.tgl_ttg_formatted + '</td>';
                html += '<td>' + item.gudang_asal_nama + '</td>';
                html += '<td>' + (item.gudang_tujuan_nama || '-') + '</td>';
                html += '<td><span class="badge badge-' + item.status_class + '">' + item.status_ttg + '</span></td>';
                html += '<td align="center">';
                html += '<button class="btn btn-outline-primary btn-xs" onclick="window.open(\'<?php echo $data->sistem('url_sis'); ?>/modal/gudangproduktransfer/detail_standalone.php?keycode=' + item.id_ttg + '\', \'detailWindow\', \'width=800,height=600,scrollbars=yes\')"><i class="fa fa-eye"></i></button> ';
                html += '<a href="<?php echo $sistem; ?>/gudangproduktransfer/history/' + item.id_ttg + '" title="History Transfer"><button class="btn btn-outline-info btn-xs"><i class="fa fa-history"></i></button></a>';
                html += '</td>';
                html += '</tr>';
            });
        } else {
            html = '<tr><td colspan="7" align="center">Tidak ada data transfer gudang yang ditemukan</td></tr>';
        }
        
        $('#viewdata').html(html);
    }
    
    // Helper function untuk membuat pagination dari data JSON
    function renderPaginationFromData(pagination) {
        if (!pagination || parseInt(pagination.total_pages) <= 1) {
            $('#paginasi').html('');
            return;
        }
        
        var html = '';
        var page = parseInt(pagination.page);
        var totalPages = parseInt(pagination.total_pages);
        
        // Previous button
        if (page > 1) {
            html += '<li class="page-item"><a class="page-link page-link-icon" href="javascript:void(0)" onclick="transmutedPage(' + (page - 1) + ')"><i class="fa fa-angle-double-left"></i></a></li>';
        } else {
            html += '<li class="page-item disabled"><a class="page-link page-link-icon" href="javascript:void(0)"><i class="fa fa-angle-double-left"></i></a></li>';
        }
        
        // Tampilkan halaman dengan logika yang lebih cerdas
        var showPages = 5; // Jumlah maksimal halaman yang ditampilkan
        var startPage = Math.max(1, page - Math.floor(showPages / 2));
        var endPage = Math.min(totalPages, startPage + showPages - 1);
        
        // Sesuaikan startPage jika endPage terlalu dekat dengan totalPages
        if (endPage - startPage + 1 < showPages) {
            startPage = Math.max(1, endPage - showPages + 1);
        }
        
        // First page jika perlu
        if (startPage > 1) {
            html += '<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="transmutedPage(1)">1</a></li>';
            if (startPage > 2) {
                html += '<li class="page-item disabled"><a class="page-link">...</a></li>';
            }
        }
        
        // Page numbers
        for (var i = startPage; i <= endPage; i++) {
            if (i === page) {
                html += '<li class="page-item active"><a class="page-link" href="javascript:void(0)">' + i + '</a></li>';
            } else {
                html += '<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="transmutedPage(' + i + ')">' + i + '</a></li>';
            }
        }
        
        // Last page jika perlu
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                html += '<li class="page-item disabled"><a class="page-link">...</a></li>';
            }
            html += '<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="transmutedPage(' + totalPages + ')">' + totalPages + '</a></li>';
        }
        
        // Next button
        if (page < totalPages) {
            html += '<li class="page-item"><a class="page-link page-link-icon" href="javascript:void(0)" onclick="transmutedPage(' + (page + 1) + ')"><i class="fa fa-angle-double-right"></i></a></li>';
        } else {
            html += '<li class="page-item disabled"><a class="page-link page-link-icon" href="javascript:void(0)"><i class="fa fa-angle-double-right"></i></a></li>';
        }
        
        $('#paginasi').html(html);
    }
    
    // Fungsi untuk membuka modal pencarian
    function caridata(id, menu, cari) {
        $('#' + id).val(cari);
        $('#caridatax').val(cari);
        $('#modalcari').modal('show');
    }
</script>