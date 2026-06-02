<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Menu</a></li>
                <li class="breadcrumb-item"><a href="#">Grafik</a></li>
                <li class="breadcrumb-item active" aria-current="page">Rank Penjualan</li>
            </ol>
        </nav>
        <h4 class="content-title">Rank Penjualan</h4>
    </div>
</div>
<?php
	$cari	= $secu->injection(@$_GET['cari']);
	$pecah	= explode('_', $cari);
	$rank	= empty($pecah[0]) ? 10 : $pecah[0];
?>
<input type="hidden" name="caridata" id="caridata" value="<?php echo($cari); ?>" readonly="readonly" />
<input type="hidden" name="halaman" id="halaman" value="1" readonly="readonly" />
<input type="hidden" name="maximal" id="maximal" value="15" readonly="readonly" />
<div class="content-body">
	<div class="row mg-b-10">
        <div class="col-sm-6">
			<a href="#modal1" onclick="<?php echo("caridata('carirank', 'rankpenjualan', '$cari')"); ?>" data-toggle="modal"><button class="btn btn-warning btn-pill btn-xs"><i class="fa fa-search"></i> Cari Data</button></a>
			<a href="<?php echo($data->sistem('url_sis').'/rankpenjualan'); ?>"><button class="btn btn-info btn-pill btn-xs"><i class="fa fa-spinner"></i> Refresh</button></a>
        	<?php echo(($data->akses($admin, $menu, 'A.read_status')==='Active') ? '<a target="_blank" href="'.$sistem.'/laporan/xps/rankpenjualan/rankpenjualan.php?key='.$cari.'" title="XPS"><button class="btn btn-danger btn-pill btn-xs"><i class="fa fa-print"></i> XPS</button></a>' : ''); ?>
        	<?php echo(($data->akses($admin, $menu, 'A.read_status')==='Active') ? '<a target="_blank" href="'.$sistem.'/laporan/xls/rankpenjualan/rankpenjualan.php?key='.$cari.'" title="XLS"><button class="btn btn-success btn-pill btn-xs"><i class="fa fa-print"></i> XLS</button></a>' : ''); ?>
        </div>
        <div class="col-sm-6">
			<span class="badge badge-pill badge-danger"><i class="fa fa-search"></i> <?php echo("Max. Rank : $rank | Periode 1 : ".@$pecah[1]." | Periode 2 : ".@$pecah[2]); ?></span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mg-b-0">
            <thead>
                <tr>
                    <th><center>Rank</center></th>
                    <th>Outlet</th>
                    <th>Total</th>
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