<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Menu</a></li>
                <li class="breadcrumb-item active" aria-current="page">Portal</li>
            </ol>
        </nav>
        <h4 class="content-title">REKAP FISIK - REKAP KARTU STOK</h4>
    </div>
</div>
<?php $cari = $secu->injection(@$_GET['cari']); ?>
<input type="hidden" name="caridata" id="caridata" value="<?php echo($cari); ?>" readonly="readonly" />
<input type="hidden" name="cariitem" id="halaman" value="1" readonly="readonly" />
<input type="hidden" name="maximal" id="maximal" value="15" readonly="readonly" />

<div class="content-body">
    <div class="row mg-b-10">
        <div class="col-sm-6">
			<a href="<?php echo($data->sistem('url_sis').'/stockopnamer'); ?>"><button class="btn btn-info btn-pill btn-xs"><i class="fa fa-spinner"></i> Refresh</button></a>
             <?php echo(($data->akses($admin, $menu, 'A.read_status')==='Active') ? '<a target="_blank" href="'.$sistem.'/laporan/xls/reportstockopname/stockopname.php?key='.$cari.'" title="Produk"><button class="btn btn-success btn-pill btn-xs"><i class="fa fa-print"></i>Report Kartu Stok</button></a>' : ''); ?>
             <?php echo(($data->akses($admin, $menu, 'A.read_status')==='Active') ? '<a target="_blank" href="'.$sistem.'/laporan/xls/reportstockopname/sotahunan.php?key='.$cari.'" title="Produk"><button class="btn btn-success btn-pill btn-xs"><i class="fa fa-print"></i>Report Tahunan</button></a>' : ''); ?>

        </div>
    </div>
        <table border="5"  class="table table-hover mg-b-30">
            <thead>
                <tr>
                  
                    
                    <th colspan="2"><center>STOK AWAL</center></th> 
                    <th colspan="1"><center>BARANG IN</center></th>
                    <th colspan="1"><center>BARANG OUT</center></th>
                    <th colspan="1"><center>TOTAL STOK</center></th>
                    <th colspan="1"><center>SO</center></th>
                    <th colspan="1"><center>SELISIH</center></th>


                </tr>
                <tr>
                    <th><center>Nama Produk</center></th>
                    <th><center>QTY</center></th>
                   
                    <th><center>QTY</center></th>
                    <th><center>QTY</center></th>
                    <th><center>STOK</center></th>
                    
                </tr>
                <tr>
                     
                </tr>
            </thead>
            <tbody id="isitabel"></tbody>
        </table>
        <!--
        <div class="mg-t-10">
            <nav aria-label="Page navigation example">
                <ul class="pagination pagination-circle mg-b-0" id="paginasi"></ul>
            </nav>
        </div>
        -->
    
</div>