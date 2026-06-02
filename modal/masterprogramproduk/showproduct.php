<?php
    require_once('../../config/connection/connection.php');
    require_once('../../config/connection/security.php');
    require_once('../../config/function/data.php');
    $secu   = new Security;
    $base   = new DB;
    $data   = new Data;
    $tgl    = date('Y-m-d');
    $conn   = $base->open();
    $nomor  = $secu->injection($_POST['x']);
?>
    <link href="<?php echo($data->sistem('url_sis').'/DataTables/datatables.min.css'); ?>" rel="stylesheet" />
    <div class="modal-header">
        <h6 class="modal-title" id="exampleModalLabel">Pilih Produk untuk Master Program</h6>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
        </button>
    </div>
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-12">
            <table id="mpProductTable" class="tabelgetdata table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Detail</th>
                        <th>Harga</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                    $status = 'Active';
                    $master = $conn->prepare("SELECT A.id_pro, A.kode_pro, A.nama_pro, A.berat_pro, B.harga_phg, C.nama_kpr, C.satuan_kpr, D.nama_spr FROM produk AS A LEFT JOIN produk_harga AS B ON A.id_pro=B.id_pro LEFT JOIN kategori_produk AS C ON A.id_kpr=C.id_kpr LEFT JOIN satuan_produk AS D ON A.id_spr=D.id_spr WHERE B.status_phg=:status GROUP BY A.id_pro, A.kode_pro, A.nama_pro, A.berat_pro, B.harga_phg, C.nama_kpr, C.satuan_kpr, D.nama_spr ORDER BY A.nama_pro ASC");
                    $master->bindParam(':status', $status, PDO::PARAM_STR);
                    $master->execute();
                    while($hasil = $master->fetch(PDO::FETCH_ASSOC)){
                ?>
                    <tr onclick="showproductmp('<?php echo($nomor); ?>', '<?php echo($hasil['id_pro']); ?>', '<?php echo(addslashes($hasil['nama_pro'])); ?>', '<?php echo(addslashes($hasil['kode_pro'])); ?>', '<?php echo($hasil['berat_pro']); ?>', '<?php echo(addslashes($hasil['nama_kpr'])); ?>', '<?php echo(addslashes($hasil['satuan_kpr'])); ?>', '<?php echo(addslashes($hasil['nama_spr'])); ?>', '<?php echo($hasil['harga_phg']); ?>')">
                        <td><?php echo($hasil['kode_pro']); ?></td>
                        <td><?php echo($hasil['nama_pro']); ?></td>
                        <td><?php echo($hasil['nama_kpr'].' ('.$hasil['berat_pro'].' '.$hasil['nama_spr'].')'); ?></td>
                        <td><?php echo(number_format($hasil['harga_phg'],0,',','.')); ?></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Close</button>
    </div>
<?php
    $conn = $base->close();
?>
    <script type="text/javascript" src="<?php echo($data->sistem('url_sis').'/DataTables/datatables.min.js'); ?>"></script>
    <script type="text/javascript">
    $('#mpProductTable').DataTable({
      language: {
        searchPlaceholder: 'Search...',
        sSearch: '',
        lengthMenu: 'Show _MENU_ data',
        info: '_START_ to _END_ of _TOTAL_ data',
        paginate: {
            next: 'Last',
            previous: 'First'
        }
      }
    });
    </script>
