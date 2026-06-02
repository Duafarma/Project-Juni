<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Mitra</a></li>
                <li class="breadcrumb-item active" aria-current="page">Outlet</li>
            </ol>
        </nav>
        <h4 class="content-title">Diskon Produk</h4>
    </div>
</div>
<?php
	$uniq	= $secu->injection(@$_GET['keycode']);
	$kode	= base64_decode($uniq);
	$cari	= $secu->injection(@$_GET['cari']);
?>
<input type="hidden" name="caridata" id="caridata" value="<?php echo($uniq.'outlet'.$cari); ?>" readonly="readonly" />
<input type="hidden" name="halaman" id="halaman" value="1" readonly="readonly" />
<input type="hidden" name="maximal" id="maximal" value="15" readonly="readonly" />
<div class="content-body">
	<div class="row mg-b-10">
        <div class="col-sm-6">
			<a href="#modal1" onclick="<?php echo("caridata('caridispro', 'dispro/out=$uniq&cari=', '$cari')"); ?>" data-toggle="modal"><button class="btn btn-warning btn-pill btn-xs"><i class="fa fa-search"></i> Cari Data</button></a>
			<a href="<?php echo("$sistem/dispro/out=$uniq&cari=$cari"); ?>"><button class="btn btn-info btn-pill btn-xs"><i class="fa fa-spinner"></i> Refresh</button></a>
			<a href="<?php echo("$sistem/outlet"); ?>" title="Kembali"><button class="btn btn-success btn-pill btn-xs"><i class="fa fa-chevron-circle-left"></i> Kembali</button></a>
        </div>
        <div class="col-sm-6">
			<span class="badge badge-pill badge-danger"><i class="fa fa-search"></i> Outlet : <?php echo($data->outlet($kode, 'nama_out')); ?></span>
        </div>
    </div>
	<?php require_once('config/frame/alert.php'); ?>
    <div class="table-responsive">
        <table class="table table-hover mg-b-0">
            <thead>
                <tr>
                    <th><center>#</center></th>
                    <th>Produk</th>
                    <th>Diskon</th>
                    <th><center>Action</center></th>
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