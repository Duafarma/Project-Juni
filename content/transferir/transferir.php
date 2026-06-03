<?php
$cari = $secu->injection(@$_GET['cari'] ?? '');
?>
<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Menu</a></li>
                <li class="breadcrumb-item active">Transfer ke Inventory Retur</li>
            </ol>
        </nav>
        <h4 class="content-title"><i class="fa fa-exchange-alt text-warning"></i> Daftar Transfer ke Inventory Retur</h4>
    </div>
</div>
<input type="hidden" id="caridata" value="<?php echo $cari; ?>">
<input type="hidden" id="halaman"  value="1">
<input type="hidden" id="maximal"  value="15">

<div class="content-body">
    <div class="row mg-b-10">
        <div class="col-sm-6">
            <?php if($data->akses($admin, $menu, 'A.create_status') === 'Active'): ?>
            <a href="<?php echo $sistem; ?>/transferir/i">
                <button class="btn btn-primary btn-pill btn-sm"><i class="fa fa-plus-circle"></i> Buat Transfer Baru</button>
            </a>
            <?php endif; ?>
            <a href="<?php echo $sistem; ?>/inventory">
                <button class="btn btn-secondary btn-pill btn-sm"><i class="fa fa-warehouse"></i> Inventory</button>
            </a>
        </div>
        <div class="col-sm-6 text-right">
            <button class="btn btn-warning btn-pill btn-sm" onclick="muat()"><i class="fa fa-sync"></i> Refresh</button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-bordered mg-b-0" id="tbData">
            <thead class="thead-light">
                <tr>
                    <th width="4%"><center>#</center></th>
                    <th width="14%"><center>No. Transfer</center></th>
                    <th width="22%">Keterangan</th>
                    <th width="15%"><center>Jumlah Item</center></th>
                    <th width="13%"><center>Status</center></th>
                    <th width="10%"><center>Operator</center></th>
                    <th width="10%"><center>Tanggal</center></th>
                    <th width="10%"><center>Aksi</center></th>
                </tr>
            </thead>
            <tbody id="tbBody">
                <tr><td colspan="8" class="text-center"><i class="fa fa-spinner fa-spin"></i> Memuat...</td></tr>
            </tbody>
        </table>
    </div>
    <div id="paginasi" class="mt-2"></div>
</div>

<script>
var halamanAktif = 1;

function muat(hal) {
    hal = hal || 1;
    halamanAktif = hal;
    var cari = $('#caridata').val();
    var max  = $('#maximal').val();
    $.ajax({
        url: '<?php echo $data->sistem('url_sis'); ?>/json/transferir/transferir.php',
        type: 'POST',
        data: {cari: cari, halaman: hal, maximal: max},
        success: function(res) {
            var tb = '';
            if (!res.data || res.data.length === 0) {
                tb = '<tr><td colspan="8" class="text-center text-muted">Belum ada data transfer.</td></tr>';
            } else {
                $.each(res.data, function(i, r) {
                    tb += '<tr>' +
                        '<td><center>' + r.no + '</center></td>' +
                        '<td><center>' + r.no_tir + '</center></td>' +
                        '<td>' + r.keterangan + '</td>' +
                        '<td><center>' + r.jml_item + '</center></td>' +
                        '<td><center>' + r.status + '</center></td>' +
                        '<td><center><small>' + r.operator + '</small></center></td>' +
                        '<td><center><small>' + r.tgl + '</small></center></td>' +
                        '<td><center>' + r.aksi + '</center></td>' +
                        '</tr>';
                });
            }
            $('#tbBody').html(tb);

            // Pagination
            var total = res.recordsTotal;
            var max2  = parseInt($('#maximal').val());
            var totalPage = Math.ceil(total / max2);
            var pg = '';
            if (totalPage > 1) {
                pg = '<ul class="pagination pagination-sm">';
                for (var p = 1; p <= totalPage; p++) {
                    pg += '<li class="page-item ' + (p === halamanAktif ? 'active' : '') + '">' +
                          '<a class="page-link" href="#" onclick="muat(' + p + ');return false;">' + p + '</a></li>';
                }
                pg += '</ul>';
            }
            $('#paginasi').html(pg);
        },
        error: function() {
            $('#tbBody').html('<tr><td colspan="8" class="text-center text-danger">Gagal memuat data.</td></tr>');
        }
    });
}

$(document).ready(function() { muat(1); });
</script>
