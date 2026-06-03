<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Menu</a></li>
                <li class="breadcrumb-item active" aria-current="page">Inventory Cendo</li>
            </ol>
        </nav>
        <h4 class="content-title">Inventory Cendo</h4>
    </div>
</div>
<?php $cari = $secu->injection(@$_GET['cari']); ?>
<input type="hidden" name="caridata" id="caridata" value="<?php echo($cari); ?>" readonly="readonly" />
<input type="hidden" name="cariitem" id="halaman" value="1" readonly="readonly" />
<input type="hidden" name="maximal" id="maximal" value="15" readonly="readonly" />

<div class="content-body">
    <div class="row mg-b-10">
        <div class="col-sm-6">
            <a href="#modal1" onclick="<?php echo("caridata('caridata', 'inventory', '$cari')"); ?>" data-toggle="modal"><button class="btn btn-warning btn-pill btn-xs"><i class="fa fa-search"></i> Cari Data</button></a>
            
            <a href="<?php echo("$sistem/inventory"); ?>"><button class="btn btn-info btn-pill btn-xs"><i class="fa fa-spinner"></i> Refresh</button></a>
            <a href="<?php echo("$sistem/transferir/i"); ?>"><button class="btn btn-warning btn-pill btn-xs"><i class="fa fa-exchange-alt"></i> Transfer ke Retur</button></a>
            <a href="<?php echo("$sistem/transferir"); ?>"><button class="btn btn-secondary btn-pill btn-xs"><i class="fa fa-list"></i> Daftar Transfer</button></a>
            <?php echo(($data->akses($admin, $menu, 'A.read_status')==='Active') ? '<a target="_blank" href="'.$sistem.'/laporan/xls/inventory/inventory.php?key='.$cari.'" title="XLS"><button class="btn btn-success btn-pill btn-xs"><i class="fa fa-print"></i> XLS</button></a>' : ''); ?>
            <?php echo(($data->akses($admin, $menu, 'A.read_status')==='Active') ? '<a target="_blank" href="'.$sistem.'/laporan/xls/inventory/inventorya.php?key='.$cari.'" title="Analisa Stok"><button class="btn btn-success btn-pill btn-xs"><i class="fa fa-print"></i> Analisa Stok</button></a>' : ''); ?>
            <!--<?php echo(($data->akses($admin, $menu, 'A.create_status')==='Active') ? '<a href="'.$sistem.'/transferstok/i"><button class="btn btn-primary btn-pill btn-xs"><i class="fa fa-plus-circle"></i> Transfer Stok</button></a>' : ''); ?>-->
        </div>
        <div class="col-sm-6">
            <span class="badge badge-pill badge-danger"><i class="fa fa-search"></i> Search : <?php echo($cari); ?></span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mg-b-0">
            <thead>
               <tr>
                    <th><center>#</center></th>
                    <th>Nama Produk</th>
                    <th>Gudang</th>
                    <th>Ukuran</th>
                    <th>No. Batch</th>
                    <th>ED</th>
                    <th><div align="right">Rentang Waktu</div></th>
                    <th><div align="right">Harga</div></th>
                    <th><div align="right">Harga + PPN</div></th>
                    <th><div align="right">Kuantitas</div></th>
                    <th><center>Total Stok </center></th>
                    <th><center>Aksi</center></th>
                    <!-- <th>Keterangan</th> -->
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
</div>

<!-- Modal Transfer ke Retur -->
<div class="modal fade" id="modalTransferRetur" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="fa fa-exchange-alt"></i> Transfer ke Inventory Retur</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="formTransferRetur">
            <div class="modal-body">
                <input type="hidden" id="tr_id_psd" name="id_psd" />
                <div class="row mg-b-10">
                    <div class="col-sm-4"><label>Produk</label></div>
                    <div class="col-sm-8"><strong id="tr_nama_pro">-</strong></div>
                </div>
                <div class="row mg-b-10">
                    <div class="col-sm-4"><label>No. Batch</label></div>
                    <div class="col-sm-8" id="tr_bcode">-</div>
                </div>
                <div class="row mg-b-10">
                    <div class="col-sm-4"><label>Stok Tersedia</label></div>
                    <div class="col-sm-8"><strong id="tr_sisa" class="text-primary">0</strong></div>
                </div>
                <div class="form-group">
                    <label>Jumlah Transfer <span class="text-danger">*</span></label>
                    <input type="number" id="tr_jumlah" name="jumlah" class="form-control" min="1" placeholder="0" required />
                </div>
                <div class="form-group">
                    <label>Keterangan</label>
                    <input type="text" id="tr_keterangan" name="keterangan" class="form-control" placeholder="Alasan transfer..." />
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-warning"><i class="fa fa-exchange-alt"></i> Transfer</button>
            </div>
            </form>
        </div>
    </div>
</div>

<script>
function bukaTransferRetur(id_psd, nama_pro, bcode, sisa) {
    document.getElementById('tr_id_psd').value = id_psd;
    document.getElementById('tr_nama_pro').textContent = nama_pro;
    document.getElementById('tr_bcode').textContent = bcode;
    document.getElementById('tr_sisa').textContent = sisa;
    document.getElementById('tr_jumlah').value = '';
    document.getElementById('tr_jumlah').max = sisa;
    document.getElementById('tr_keterangan').value = '';
}

document.getElementById('formTransferRetur').addEventListener('submit', function(e) {
    e.preventDefault();
    var jumlah = parseInt(document.getElementById('tr_jumlah').value);
    var sisa   = parseInt(document.getElementById('tr_sisa').textContent.replace(/\./g,''));
    if(jumlah < 1 || jumlah > sisa) {
        Swal.fire('Error', 'Jumlah tidak valid (max: ' + sisa + ')', 'error');
        return;
    }
    var fd = new FormData(this);
    fd.append('namamodal', 'inventory');
    fd.append('namamenu', 'transfer_retur');
    fd.append('keterangan', document.getElementById('tr_keterangan').value);
    fetch('<?php echo $data->sistem('url_sis'); ?>/modal/inventory/action_transfer.php', {
        method: 'POST',
        body: fd
    }).then(r => r.json()).then(res => {
        $('#modalTransferRetur').modal('hide');
        if(res.status === 'success') {
            Swal.fire('Berhasil', res.message, 'success').then(() => {
                caridata('caridata', 'inventory', document.getElementById('caridata').value);
            });
        } else {
            Swal.fire('Gagal', res.message, 'error');
        }
    });
});
</script>