<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Menu</a></li>
                <li class="breadcrumb-item active" aria-current="page">Portal</li>
            </ol>
        </nav>
        <h4 class="content-title">REVIEW FISIK</h4>
    </div>
</div>
<?php $cari = $secu->injection(@$_GET['cari']); ?>
<input type="hidden" name="caridata" id="caridata" value="<?php echo($cari); ?>" readonly="readonly" />
<input type="hidden" name="cariitem" id="halaman" value="1" readonly="readonly" />
<input type="hidden" name="maximal" id="maximal" value="15" readonly="readonly" />

<div class="content-body">
    <div class="row mg-b-10">
        <div class="col-sm-6">
            <a href="<?php echo("$sistem/stockopnamek"); ?>"><button class="btn btn-info btn-pill btn-xs"><i class="fa fa-spinner"></i> Refresh</button></a>
            <a target="_blank" href="<?php echo($data->sistem('url_sis')."/laporan/xls/reportstockopname/soinventory.php?key=$cari"); ?>" title=".XLS"><button class="btn btn-success btn-pill btn-xs"><i class="fa fa-print"></i> .XLS</button></a>

        </div>
       
    </div>
        <table border="5"  class="table table-hover mg-b-30">
            <thead>
                <tr>
                  
                    
                    <th colspan="4"><center>STOK INVENTORY</center></th>
                    <th colspan="2"><center>STOCK OPNAME</center></th>
                    <!-- <th><center>SELISIS</center></th> -->
                    <!-- <th colspan="1"><center>STATUS</center></th> -->
                    <!-- <th colspan="3"><center>ACTION</center></th> -->

                </tr>
                <tr>
                     <th>#</th>
                    <th><center>Nama Produk</center></th>
                    <th><center> Batch </center></th>
                    <th><center>QTY</center></th>
                   
                    <th><center>No. Batch</center></th>
                    <th><center>QTY</center></th>
                    <th><center>SELISIH</center></th>
                    <th><center>STATUS</center></th>
                    <th><center>ACTION</center></th>

                    <!-- <th><center>QTY</center></th> -->
                </tr>
                <tr>
                     
                </tr>
            </thead>
            <tbody id="isitabel"></tbody>
        </table>
        <div class="modal-footer">
            <!-- <button type="submit" id="bsave" class="btn btn-danger btn-xs">Tidak</button> -->
            
            <a href="<?php echo("$sistem/stockopnamein"); ?>">   <button type="button" class="btn btn-danger btn-xs" data-dismiss="modal">Rekap Total Inventory dan Stockopname</button></a>
            <a href="<?php echo("$sistem/stockopnamek/i"); ?>">  <button type="submit" id="bsave" class="btn btn-success btn-xs">Selesai Stock Opname</button></a>
        </div>
</div>