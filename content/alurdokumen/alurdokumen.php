<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Menu</a></li>
                <li class="breadcrumb-item"><a href="#">Master Data</a></li>
                <li class="breadcrumb-item active" aria-current="page">Alur Dokumen Faktur</li>
            </ol>
        </nav>
        <h4 class="content-title">Dashboard Faktur Penjualan</h4>
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
        <!-- <?php echo(($data->akses($admin, $menu, 'A.create_status')==='Active') ? '<a href="'.$sistem.'/diskon/i"><button class="btn btn-primary btn-pill btn-xs"><i class="fa fa-plus-circle"></i> Tambah Data</button></a>' : ''); ?> -->
			<a href="#modal1" onclick="<?php echo("caridata('caridokumen', 'alurdokumen', '$cari')"); ?>" data-toggle="modal"><button class="btn btn-warning btn-pill btn-xs"><i class="fa fa-search"></i> Periode</button></a>
			<a href="<?php echo($data->sistem('url_sis').'/alurdokumen'); ?>"><button class="btn btn-info btn-pill btn-xs"><i class="fa fa-spinner"></i> Refresh</button></a>
            <?php echo(($data->akses($admin, $menu, 'A.read_status')==='Active') ? '<a target="_blank" href="'.$sistem.'/laporan/xls/fakturpajak/fakturpajak.php?key='.$cari.'" title="XLS"><button class="btn btn-success btn-pill btn-xs"><i class="fa fa-print"></i> XLS</button></a>' : ''); ?>

        </div>
        <div class="col-sm-3">
            <!-- <a target="_blank" href="<?php echo($data->sistem('url_sis')."/laporan/xls/outlet/profitmargin.php?key=$cari"); ?>" title=".XLS"><button class="btn btn-success btn-pill btn-xs"><i class="fa fa-print"></i> .XLS</button></a> -->
            <!-- <a href="<?php echo("$sistem/alurdokumen"); ?>" title="Kembai"><button type="button" class="btn btn-secondary btn-xs"><i class="fa fa-chevron-circle-left"></i> Kembali</button></a> -->
        </div> 
        
    </div>
    <?php require_once('config/frame/alert.php'); ?>
    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
    <table border="5" class="table table-hover mg-b-0">
        <thead>
            <tr>
                <th rowspan="2" class="sticky-header"><center>#</center></th>
                <th rowspan="2" class="sticky-header">Tanggal Faktur</th>
                <th rowspan="2" class="sticky-header"><center>Faktur Terbentuk</center></th>
                <th colspan="2" class="sticky-header"><center>Faktur Terkirim</center></th>
                <th colspan="2" class="sticky-header"><center>Faktur Kembali</center></th>
                <th colspan="2" class="sticky-header"><center>Filing Faktur</center></th>
                <th colspan="2" class="sticky-header"><center>Buat PPN</center></th>
                <th colspan="2" class="sticky-header"><center>Upload PPN</center></th>
                <th colspan="2" class="sticky-header"><center>Pemberkasan</center></th>
                <th colspan="2" class="sticky-header"><center>TF</center></th>
                <th colspan="3" class="sticky-header"><center>Pembayaran</center></th>
            </tr>
            <tr>
                <th class="sticky-header"><center>Sudah</center></th>
                <th class="sticky-header"><center>Belum</center></th>
                <th class="sticky-header"><center>Sudah</center></th>
                <th class="sticky-header"><center>Belum</center></th>
                <th class="sticky-header"><center>Sudah</center></th>
                <th class="sticky-header"><center>Belum</center></th>
                <th class="sticky-header"><center>Sudah</center></th>
                <th class="sticky-header"><center>Belum</center></th>
                <th class="sticky-header"><center>Sudah</center></th>
                <th class="sticky-header"><center>Belum</center></th>
                <th class="sticky-header"><center>Sudah</center></th>
                <th class="sticky-header"><center>Belum</center></th>
                <th class="sticky-header"><center>Sudah</center></th>
                <th class="sticky-header"><center>Belum</center></th>
                <th class="sticky-header"><center>Lunas</center></th>
                <th class="sticky-header"><center>Sebagian</center></th>
                <th class="sticky-header"><center>Belum</center></th>
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

<style>
    .table-responsive {
    max-height: 400px; 
    overflow-y: auto; /* Allows for vertical scrolling */
}

th {
    position: sticky;  /* Make the header sticky */
    top: 0;            /* Fixes the header at the top of the scrolling area */
    background-color: #f8f9fa; /* Background color for contrast */
    z-index: 10;      /* Keeps the header above other elements */
    padding: 10px;    /* Padding for better spacing */
}

th:nth-child(1) {
    left: 0;          /* Keeps the first column fixed */
    z-index: 20;     /* Higher z-index to stay on top */
}

thead tr th[rowspan] {
    z-index: 0;     /* Ensure rowspan headers are above other headers */
}

tbody tr:nth-child(even) {
    background-color: #f2f2f2; /* Alternating row colors for readability */
}

</style>




</div>