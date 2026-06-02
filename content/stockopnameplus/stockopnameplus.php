<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Menu</a></li>
                <li class="breadcrumb-item active" aria-current="page">Portal</li>
            </ol>
        </nav>
        <h4 class="content-title">PORTAL TOTAL PLUS STOCK OPNAME</h4>
    </div>
</div>
<?php $cari = $secu->injection(@$_GET['cari']); ?>
<input type="hidden" name="caridata" id="caridata" value="<?php echo($cari); ?>" readonly="readonly" />
<input type="hidden" name="cariitem" id="halaman" value="1" readonly="readonly" />
<input type="hidden" name="maximal" id="maximal" value="15" readonly="readonly" />

<div class="content-body">
    <div class="row mg-b-10">
    <div class="col-sm-6">
    <a target="_blank" href="<?php echo($data->sistem('url_sis')."/laporan/xls/outlet/outlet.php?key=$cari"); ?>" title=".XLS"><button class="btn btn-success btn-pill btn-xs"><i class="fa fa-print"></i> .XLS</button></a>

    </div>
       
    </div>
        <table border="5"  class="table table-hover mg-b-30">
            <thead>
                <tr>
                  
                    
                    <th><center>ITEM</center></th> 
                    <th ><center>Plus SO</center></th>
                    <th><center>HNA</center></th>
                    <th><center>TOTAL</center></th>

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