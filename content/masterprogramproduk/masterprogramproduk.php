<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Menu</a></li>
                <li class="breadcrumb-item"><a href="#">Master Data</a></li>
                <li class="breadcrumb-item active" aria-current="page">Master Program Produk</li>
            </ol>
        </nav>
        <h4 class="content-title"><i class="fas fa-box-open"></i> Master Program Produk</h4>
    </div>
</div>
<?php $cari = $secu->injection(@$_GET['cari']); ?>
<?php if(@$_GET['s']==='1'): ?>
<script>
window.addEventListener('load', function(){
    swal("Selamat!", "Data program berhasil disimpan.", "success");
    if (history.replaceState) {
        var newUrl = window.location.pathname + window.location.hash;
        history.replaceState(null, null, newUrl);
    }
});
</script>
<?php endif; ?>
<input type="hidden" name="caridata" id="caridata" value="<?php echo($cari); ?>" readonly="readonly" />
<input type="hidden" name="halaman" id="halaman" value="1" readonly="readonly" />
<input type="hidden" name="maximal" id="maximal" value="15" readonly="readonly" />
<div class="content-body">
    <div class="row mg-b-10">
        <div class="col-sm-6">
            <?php echo(($data->akses($admin, $menu, 'A.create_status')==='Active') ? '<a href="'.$sistem.'/masterprogramproduk/i"><button class="btn btn-primary btn-pill btn-xs"><i class="fa fa-plus-circle"></i> Tambah Program</button></a>' : ''); ?>
            <a href="#modal1" onclick="<?php echo("caridata('caridata', '$menu', '$cari')"); ?>" data-toggle="modal"><button class="btn btn-warning btn-pill btn-xs"><i class="fa fa-search"></i> Cari Data</button></a>
            <a href="<?php echo("$sistem/$menu"); ?>"><button class="btn btn-info btn-pill btn-xs"><i class="fa fa-spinner"></i> Refresh</button></a>
        </div>
        <div class="col-sm-6">
            <span class="badge badge-pill badge-danger"><i class="fa fa-search"></i> Search : <?php echo($cari); ?></span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mg-b-0">
            <thead>
                <tr>
                    <th width="50"><center>#</center></th>
                    <th>Nama Program</th>
                    <th width="140"><center>Tipe Program</center></th>
                    <th width="120"><center>Jumlah Produk</center></th>
                    <th width="120"><center>Jumlah Outlet</center></th>
                    <th width="100"><center>Action</center></th>
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
