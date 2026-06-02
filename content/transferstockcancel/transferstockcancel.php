<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Menu</a></li>
                <li class="breadcrumb-item"><a href="#">Inventory</a></li>
                <li class="breadcrumb-item active" aria-current="page">Transfer Stock Cancel</li>
            </ol>
        </nav>
        <h4 class="content-title">Transfer Stock Cancel <small class="text-muted">History Transfer</small></h4>
    </div>
</div>
<?php $cari = $secu->injection(@$_GET['cari']); ?>
<input type="hidden" name="caridata" id="caridata" value="<?php echo($cari); ?>" readonly="readonly" />
<input type="hidden" name="halaman" id="halaman" value="1" readonly="readonly" />
<input type="hidden" name="maximal" id="maximal" value="15" readonly="readonly" />
<div class="content-body">
    <div class="row mg-b-10">
        <div class="col-sm-6">
            <a href="#modal1" onclick="<?php echo("caridata('caritanggal', 'transferstockcancel', '$cari')"); ?>" data-toggle="modal"><button class="btn btn-warning btn-pill btn-xs"><i class="fa fa-search"></i> Cari Data</button></a>
            <a href="<?php echo("$sistem/transferstockcancel"); ?>"><button class="btn btn-info btn-pill btn-xs"><i class="fa fa-spinner"></i> Refresh</button></a>
            <a href="<?php echo("$sistem/transferstockcancel/add"); ?>"><button class="btn btn-primary btn-pill btn-xs"><i class="fa fa-plus"></i> Tambah Transfer</button></a>
                        <button class="btn btn-success btn-pill btn-xs" onclick="downloadExcel()"><i class="fa fa-file-excel"></i> Download Excel</button>

        </div>
        <div class="col-sm-6">
            <span class="badge badge-pill badge-danger"><i class="fa fa-search"></i> Search : <?php echo($cari); ?></span>
        </div>
    </div>
    <?php require_once('config/frame/alert.php'); ?>
    <div class="alert alert-info py-2">
        <i class="fa fa-info-circle"></i> <strong>Info:</strong> Tabel ini berisi histori transfer stok dari stok cancel ke inventory tujuan.
    </div>
    <div class="table-responsive">
        <table class="table table-hover mg-b-0">
            <thead>
                <tr>
                    <th><center>#</center></th>
                    <th>Nomor Transfer</th>
                    <th>Kode Faktur</th>
                    <th><center>Tgl. Faktur</center></th>
                    <th>Inventory Tujuan</th>
                    <th><center>Tgl. Transfer</center></th>
                    <th><center>Total Item</center></th>
                    <th><center>Total Qty</center></th>
                    <th><center>Status</center></th>
                    <th><center>Detail</center></th>
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

<!-- Modal View Detail -->
<div class="modal fade" id="vmodal" tabindex="-1" role="dialog" aria-labelledby="viewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" id="vkontent"></div>
    </div>
</div>

<script>
$(document).ready(function(){
    tabeldata('transferstockcancel');
});

function downloadExcel() {
    var cari   = $("#caridata").val();
    var pecah  = cari.split('_');
    var search = pecah[0] || '';
    var tgl1   = pecah[1] || '';
    var tgl2   = pecah[2] || '';
    var url    = usuper + '/laporan/excel_transferstockcancel.php'
               + '?search=' + encodeURIComponent(search)
               + '&tgl1='   + encodeURIComponent(tgl1)
               + '&tgl2='   + encodeURIComponent(tgl2);
    window.open(url, '_blank');
}


function viewDetail(idTsc) {
    $('#vkontent').html('<div class="modal-body text-center py-4"><i class="fa fa-spinner fa-spin fa-2x"></i><br>Memuat data...</div>');
    $('#vmodal').modal('show');
    
    $.ajax({
        url: usuper + '/modal/transferstockcancel/transferstockcancel.php',
        type: 'GET',
        data: { id_tsc: idTsc, act: 'view' },
        success: function(response) {
            $('#vkontent').html(response);
        },
        error: function() {
            $('#vkontent').html('<div class="modal-body"><div class="alert alert-danger">Gagal memuat data</div></div>');
        }
    });
}
</script>
