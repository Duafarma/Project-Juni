<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Menu</a></li>
                <li class="breadcrumb-item"><a href="#">Master Data</a></li>
                <li class="breadcrumb-item active" aria-current="page">Produk Harga</li>
            </ol>
        </nav>
        <h4 class="content-title">Produk Harga</h4>
    </div>
</div>
<?php $kode	= $secu->injection($_GET['keycode']); ?>
        <form id="formsalespnp" action="#" method="post" autocomplete="off">
<input type="hidden" name="caridata" id="caridata" value="<?php echo($kode); ?>" readonly="readonly" />
<input type="hidden" name="halaman" id="halaman" value="1" readonly="readonly" />
<input type="hidden" name="maximal" id="maximal" value="15" readonly="readonly" />
<div class="content-body">
	<div class="row mg-b-10">
        <div class="col-sm-6">
			<a href="#modal1" onclick="<?php echo("crud('hproduk', 'input', '$kode')"); ?>" data-toggle="modal"><button class="btn btn-light btn-pill btn-xs"><i class="fa fa-plus-circle"></i> Tambah Data</button></a>
			<button class="btn btn-light btn-pill btn-xs"><i class="fa fa-search"></i> Cari Data</button>
        </div>
        <div class="col-sm-6">
			<span class="badge badge-pill badge-danger"><i class="fa fa-search"></i> Search : <?php echo($data->produk($kode, 'nama_pro')); ?></span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mg-b-0">
            <thead>
                <tr>
                    <th><center>#</center></th>
                    <th><center>Tercatat</center></th>
                    <th>Produk</th>
                    <th>Kode / Nomor</th>
                    <th><div align="right">Harga</div></th>
                    <th><div align="right">Harga + PPN</div></th>
                    <th>Status</th>
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
</form>