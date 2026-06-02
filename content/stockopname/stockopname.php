<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Menu</a></li>
                <li class="breadcrumb-item active" aria-current="page">Portal</li>
            </ol>
        </nav>
        <h4 class="content-title">HITUNG FISIK CENDO</h4>
    </div>
</div>
<?php $cari = $secu->injection(@$_GET['cari']); ?>
<input type="hidden" name="caridata" id="caridata" value="<?php echo($cari); ?>" readonly="readonly" />
<input type="hidden" name="cariitem" id="halaman" value="1" readonly="readonly" />
<input type="hidden" name="maximal" id="maximal" value="15" readonly="readonly" />


<div class="content-body">
    <div class="row mg-b-10">
        <div class="col-sm-6">
            <a href="#modal1" onclick="<?php echo("caridata('caridata', 'stockopname', '$cari')"); ?>" data-toggle="modal"><button class="btn btn-warning btn-pill btn-xs"><i class="fa fa-search"></i> Cari Data</button></a>
            <?php echo(($data->akses($admin, $menu, 'A.create_status')==='Active') ? '<a href="'.$sistem.'/stockopname/i"><button class="btn btn-primary btn-pill btn-xs"><i class="fa fa-plus-circle"></i> Tambah Batch baru</button></a>' : ''); ?>
            <a href="<?php echo("$sistem/stockopname"); ?>"><button class="btn btn-info btn-pill btn-xs"><i class="fa fa-spinner"></i> Refresh</button></a>          
        </div>
       
    </div>
        <table border="5"  class="table table-hover mg-b-30">
            <thead>
                <tr>
                  
                    
                    <th colspan="4"><center>STOK INVENTORY</center></th>
                    <th ><center>STOCK OPNAME</center></th>
                </tr>
                <tr>
                     <th>#</th>
                    <th><center>Nama Produk</center></th>
                    <th><center>No. Batch</center></th>
                    <th><center>QTY</center></th>
                    
                    <th><center>QTY</center></th>
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
