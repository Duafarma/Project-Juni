<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Inventory</a></li>
                <li class="breadcrumb-item active" aria-current="page">Transfer Faktur ke Stok</li>
            </ol>
        </nav>
        <h4 class="content-title">Transfer Faktur ke Stok <small class="text-muted">Riwayat Transfer</small></h4>
    </div>
</div>
<?php $cari = $secu->injection(@$_GET['cari']); ?>
<input type="hidden" name="caridata" id="caridata" value="<?php echo($cari); ?>" readonly="readonly" />
<input type="hidden" name="halaman" id="halaman" value="1" readonly="readonly" />
<input type="hidden" name="maximal" id="maximal" value="15" readonly="readonly" />
<div class="content-body">
    <div class="row mg-b-10">
        <div class="col-sm-6">
            <a href="<?php echo("$sistem/transferfakturstok/add"); ?>">
                <button class="btn btn-primary btn-pill btn-xs"><i class="fa fa-plus-circle"></i> Transfer Baru</button>
            </a>
            <a href="<?php echo("$sistem/transferfakturstok"); ?>">
                <button class="btn btn-info btn-pill btn-xs"><i class="fa fa-spinner"></i> Refresh</button>
            </a>
        </div>
        <div class="col-sm-6">
            <span class="badge badge-pill badge-danger"><i class="fa fa-search"></i> Search : <?php echo($cari); ?></span>
        </div>
    </div>
    <?php require_once('config/frame/alert.php'); ?>
    <div class="alert alert-info py-2">
        <i class="fa fa-info-circle"></i> <strong>Info:</strong> Tabel ini menampilkan riwayat transfer item dari <strong>Transaksi Faktur</strong> ke <strong>Produk Stok Detail</strong>. Setelah transfer, jumlah item pada faktur akan berkurang dan nominal faktur diperbarui.
    </div>
    <div class="table-responsive">
        <table class="table table-hover mg-b-0">
            <thead>
                <tr>
                    <th><center>#</center></th>
                    <th>Nomor Transfer</th>
                    <th>Kode Faktur</th>
                    <th>Nama Outlet</th>
                    <th><center>Tgl. Faktur</center></th>
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
<div class="modal fade" id="tfsDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" id="tfsDetailContent"></div>
    </div>
</div>

<script>
// viewdata('transferfakturstok', maximal, halaman) sudah dipanggil otomatis oleh fazlurr.js

function viewDetailTfs(idTfs) {
    $('#tfsDetailContent').html('<div class="modal-body text-center py-4"><i class="fa fa-spinner fa-spin fa-2x"></i><br>Memuat data...</div>');
    $('#tfsDetailModal').modal('show');

    $.ajax({
        url: usuper + '/ajax/transferfakturstok/getDetail.php',
        type: 'GET',
        data: { id_tfs: idTfs },
        success: function(response) {
            $('#tfsDetailContent').html(response);
        },
        error: function() {
            $('#tfsDetailContent').html('<div class="modal-body"><div class="alert alert-danger">Gagal memuat data</div></div>');
        }
    });
}
</script>
