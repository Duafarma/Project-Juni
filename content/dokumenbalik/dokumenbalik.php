<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Menu</a></li>
                <li class="breadcrumb-item"><a href="#">Report</a></li>
                <li class="breadcrumb-item active" aria-current="page">Master Dokumen</li>
            </ol>
        </nav>
        <h4 class="content-title">Dokumen Balik dan Dokumen Failing</h4>
    </div>
</div>
<?php
	$cari	= $secu->injection(@$_GET['cari']);
?>
<input type="hidden" name="caridata" id="caridata" value="<?php echo($cari); ?>" readonly="readonly" />
<input type="hidden" name="halaman" id="halaman" value="0" readonly="readonly" />
<input type="hidden" name="maximal" id="maximal" value="15" readonly="readonly" />
<div class="content-body">
	<div class="row mg-b-10">
        <div class="col-sm-12">
            <?php echo(($data->akses($admin, $menu, 'A.create_status')==='Active') ? '<a href="'.$sistem.'/dokumenbalik/i"><button class="btn btn-primary btn-pill btn-xs"><i class="fa fa-plus-circle"></i> Tambah Data Dokumen Balik</button></a>' : ''); ?>
            <?php echo(($data->akses($admin, $menu, 'A.create_status')==='Active') ? '<a href="'.$sistem.'/dokumenfailing/i"><button class="btn btn-success btn-pill btn-xs"><i class="fa fa-plus-circle"></i> Tambah Data Dokumen Failing</button></a>' : ''); ?>
            	<a href="#modal1" onclick="<?php echo("caridata('caridokumenbalik', 'dokumenbalik', '$cari')"); ?>" data-toggle="modal"><button class="btn btn-warning btn-pill btn-xs"><i class="fa fa-search"></i> Cari Data</button></a>
            <a href="<?php echo("$sistem/dokumenbalik"); ?>" title="Refresh"><button class="btn btn-info btn-pill btn-xs"><i class="fa fa-spinner"></i> Refresh</button></a>
            <a target="_blank" href="<?php echo($data->sistem('url_sis')."/laporan/xls/finance/dokumenbalik.php?key=$cari"); ?>" title=".XLS"><button class="btn btn-success btn-pill btn-xs"><i class="fa fa-print"></i> .XLS</button></a>
        </div>
       
    </div>
    <div class="table-responsive">
        <table class="table table-hover mg-b-0">
            <thead>
                <tr>
                    <th><center>#</center></th>
                    <th>Nomer Faktur</th>
                    <th>Nama Outlet</th>
                    <th>Tanggal Faktur</th>

                    <th>Status Dokumen Balik</th>
                    <th>Status Dokumen Failing</th>
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