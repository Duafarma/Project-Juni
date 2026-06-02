<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Penjualan</a></li>
                <li class="breadcrumb-item active" aria-current="page">Pembayaran Sales</li>
            </ol>
        </nav>
        <h4 class="content-title">Pembayaran Sales</h4>
    </div>
</div>
<?php $cari	= $secu->injection(@$_GET['cari']); ?>
<input type="hidden" name="caridata" id="caridata" value="<?php echo($cari); ?>" readonly="readonly" />
<input type="hidden" name="halaman" id="halaman" value="1" readonly="readonly" />
<input type="hidden" name="maximal" id="maximal" value="15" readonly="readonly" />
<div class="content-body">
	<div class="row mg-b-10">
        <div class="col-sm-12">
        	<?php echo(($data->akses($admin, $menu, 'A.create_status')==='Active') ? '<a href="#modal1" onclick="crud(\'psales\', \'input\', \'\')" data-toggle="modal"><button class="btn btn-primary btn-pill btn-xs"><i class="fa fa-plus-circle"></i> Tambah Data</button></a>' : ''); ?>
        	<?php echo(($data->akses($admin, $menu, 'A.create_status')==='Active') ? '<a href="#modalUploadExcel" data-toggle="modal"><button class="btn btn-primary btn-pill btn-xs"><i class="fa fa-upload"></i> Upload Excel</button></a>' : ''); ?>
			<a href="#modal1" onclick="<?php echo("caridata('caridata', 'psales', '$cari')"); ?>" data-toggle="modal"><button class="btn btn-warning btn-pill btn-xs"><i class="fa fa-search"></i> Cari Data</button></a>
			<a href="<?php echo($data->sistem('url_sis').'/psales'); ?>"><button class="btn btn-info btn-pill btn-xs"><i class="fa fa-spinner"></i> Refresh</button></a>
        	<a target="_blank" href="<?php echo($data->sistem('url_sis')."/laporan/xls/psales/psales.php?key=$cari"); ?>" title=".XLS"><button class="btn btn-success btn-pill btn-xs"><i class="fa fa-print"></i>Report Pembayaran</button></a>
        	<a target="_blank" href="<?php echo($data->sistem('url_sis')."/laporan/xls/psales/smec.php?key=$cari"); ?>" title=".XLS"><button class="btn btn-success btn-pill btn-xs"><i class="fa fa-print"></i>Rekap Pembayaran Smec</button></a>
        	<a href="<?php echo($data->sistem('url_sis').'/modal/psales/template_upload.php'); ?>" download><button class="btn btn-secondary btn-pill btn-xs"><i class="fa fa-download"></i> Template Excel</button></a>
        	<a href="<?php echo($data->sistem('url_sis').'/modal/psales/template_upload.csv'); ?>" download><button class="btn btn-secondary btn-pill btn-xs"><i class="fa fa-download"></i> Template CSV</button></a>


        </div>
   <!--     <div class="col-sm-4">-->
			<!--<span class="badge badge-pill badge-danger"><i class="fa fa-search"></i> Search : <?php echo($cari); ?></span>-->
   <!--     </div>-->
    </div>
    <div class="table-responsive">
        <table class="table table-hover mg-b-0">
            <thead>
                <tr>
                    <th><center>#</center></th>
                    <th>No. Faktur</th>
                    <th>Outlet</th>
                    <th>Tgl. Faktur</th>
                    <th>Jatuh Tempo</th>
                    <th>Status</th>
                    <th><center>Histori</center></th>
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