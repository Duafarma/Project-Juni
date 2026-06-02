<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Menu</a></li>
                <li class="breadcrumb-item"><a href="#">Report</a></li>
                <li class="breadcrumb-item active" aria-current="page">Stockopname</li>
            </ol>
        </nav>
        <h4 class="content-title">Report Stockopname </h4>
    </div>
</div>
<?php
	$cari	= $secu->injection(@$_GET['cari']);
	$pecah	= explode('_', $cari);
?>
<input type="hidden" name="caridata" id="caridata" value="<?php echo($cari); ?>" readonly="readonly" />
<input type="hidden" name="halaman" id="halaman" value="1" readonly="readonly" />
<input type="hidden" name="maximal" id="maximal" value="15" readonly="readonly" />
<div class="content-body">
	<div class="row mg-b-10">
        <div class="col-sm-6">
			<a href="<?php echo("$sistem/rstockopname"); ?>" title="Refresh"><button class="btn btn-info btn-pill btn-xs"><i class="fa fa-spinner"></i> Refresh</button></a>
        	<?php echo(($data->akses($admin, $menu, 'A.read_status')==='Active') ? '<a target="_blank" href="'.$sistem.'/laporan/xls/rstockopname/rstockopname.php?key='.$cari.'" title="XLS"><button class="btn btn-success btn-pill btn-xs"><i class="fa fa-print"></i> XLS</button></a>' : ''); ?>
        	<?php echo(($data->akses($admin, $menu, 'A.read_status')==='Active') ? '<a target="_blank" href="'.$sistem.'/laporan/xls/rstockopname/rstockopname2.php?key='.$cari.'" title="XLS2"><button class="btn btn-success btn-pill btn-xs"><i class="fa fa-print"></i> XLS2</button></a>' : ''); ?>
        	<?php echo(($data->akses($admin, $menu, 'A.read_status')==='Active') ? '<a target="_blank" href="'.$sistem.'/laporan/xls/reportstockopname/report_so.php?key='.$cari.'" title="XLS2"><button class="btn btn-success btn-pill btn-xs"><i class="fa fa-print"></i> XLS Tahunan</button></a>' : ''); ?>
        </div>
        <div class="col-sm-6">
			<span class="badge badge-pill badge-danger"><i class="fa fa-search"></i> <?php echo("Outlet : ".$data->outlet(@$pecah[0], 'nama_out')." | Obat : ".$data->produk(@$pecah[1], 'nama_pro')." | Periode I : ".@$pecah[2]." | Periode II : ".@$pecah[3]); ?></span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mg-b-0">
            <thead>
                <tr>
                    <th><center>#</center></th>
                    <th><center>Tgl SO</center></th>
                    <th>Nama Produk</th>
                    <th><center>Nomor Batch Inventory</center></th>
                    <th>Qty Inventory</th>
                    <th>Nomor Batch SO</th>
                    <th>Qty SO</th>
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