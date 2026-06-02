<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Menu</a></li>
                <li class="breadcrumb-item"><a href="#">Inventory</a></li>
                <li class="breadcrumb-item active" aria-current="page">Stok Cancel</li>
            </ol>
        </nav>
        <h4 class="content-title">Stok Cancel (Faktur Beda Bulan)</h4>
    </div>
</div>
<?php $cari	= $secu->injection(@$_GET['cari']); ?>
<input type="hidden" name="caridata" id="caridata" value="<?php echo($cari); ?>" readonly="readonly" />
<input type="hidden" name="halaman" id="halaman" value="1" readonly="readonly" />
<input type="hidden" name="maximal" id="maximal" value="15" readonly="readonly" />
<div class="content-body">
	<div class="row mg-b-10">
        <div class="col-sm-6">
			<a href="#modal1" onclick="<?php echo("caridata('caritanggal', 'stockcancel', '$cari')"); ?>" data-toggle="modal"><button class="btn btn-warning btn-pill btn-xs"><i class="fa fa-search"></i> Cari Data</button></a>
			<a href="<?php echo("$sistem/stockcancel"); ?>"><button class="btn btn-info btn-pill btn-xs"><i class="fa fa-spinner"></i> Refresh</button></a>
        </div>
        <div class="col-sm-6">
			<span class="badge badge-pill badge-danger"><i class="fa fa-search"></i> Search : <?php echo($cari); ?></span>
        </div>
    </div>
    <?php require_once('config/frame/alert.php'); ?>
    <div class="alert alert-info py-2">
        <i class="fa fa-info-circle"></i> <strong>Info:</strong> Tabel ini berisi stok dari faktur yang dihapus pada bulan berbeda dari bulan faktur. 
        Stok tidak dikembalikan ke inventory aktif karena sudah berbeda periode.
    </div>
    <div class="table-responsive">
        <table class="table table-hover mg-b-0">
            <thead>
                <tr>
                    <th><center>#</center></th>
                    <th>Kode Faktur</th>
                    <th>Nama Produk</th>
                    <th>Batch/Barcode</th>
                    <th><center>Tgl. Faktur</center></th>
                    <th><center>Tgl. Expired</center></th>
                    <th><center>Jumlah Cancel</center></th>
                    <th>Gudang</th>
                    <th>Keterangan</th>
                    <th><center>Status</center></th>
                    <th><center>Cancel By</center></th>
                    <th><center>Cancel At</center></th>
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

<!-- Modal Detail Stok Cancel -->
<div class="modal fade" id="modalDetailCancel" tabindex="-1" role="dialog" aria-labelledby="modalDetailCancelLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="modalDetailCancelLabel"><i class="fa fa-info-circle"></i> Detail Stok Cancel</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="detailCancelContent">
                <!-- Content akan diisi via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-times"></i> Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
function showDetailCancel(id) {
    $('#detailCancelContent').html('<div class="text-center py-4"><i class="fa fa-spinner fa-spin fa-2x"></i><br>Memuat data...</div>');
    
    $.ajax({
        url: usuper + '/modal/stockcancel/stockcancel.php',
        type: 'GET',
        data: { keycode: id, act: 'view' },
        success: function(response) {
            $('#detailCancelContent').html(response);
        },
        error: function() {
            $('#detailCancelContent').html('<div class="alert alert-danger">Gagal memuat data</div>');
        }
    });
}
</script>
</script>
