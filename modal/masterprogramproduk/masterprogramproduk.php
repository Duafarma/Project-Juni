<?php
require_once('../../config/connection/connection.php');
require_once('../../config/connection/security.php');
require_once('../../config/function/data.php');
$secu= new Security;
$base= new DB;
$data= new Data;
$conn= $base->open();
$modal= $secu->injection(@$_GET['modal']);
switch($modal){
case "detail":
$id= intval($secu->injection(@$_GET['keycode']));
$header= $conn->prepare("SELECT id_pp, nama_program, jenis_program, min_qty, diskon_persen FROM program_produk WHERE id_pp=:id");
$header->bindParam(':id', $id, PDO::PARAM_INT);
$header->execute();
$prog= $header->fetch(PDO::FETCH_ASSOC);
$detail= $conn->prepare("SELECT B.id_pro, C.kode_pro, C.nama_pro, C.berat_pro, D.nama_kpr, E.nama_spr, B.harga_program FROM program_produk_detail AS B LEFT JOIN produk AS C ON B.id_pro=C.id_pro LEFT JOIN kategori_produk AS D ON C.id_kpr=D.id_kpr LEFT JOIN satuan_produk AS E ON C.id_spr=E.id_spr WHERE B.id_pp=:id ORDER BY C.nama_pro ASC");
$detail->bindParam(':id', $id, PDO::PARAM_INT);
$detail->execute();
?>
        <div class="modal-header bg-info text-white">
            <h6 class="modal-title"><i class="fas fa-list"></i> Detail Produk — <?php echo($prog['nama_program']); ?></h6>
            <button type="button" class="close text-white" data-dismiss="modal">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <div class="modal-body">
            <div class="mb-3">
                <div><strong>Jenis Program:</strong> <?php echo($prog['jenis_program'] ?: '-'); ?></div>
                <div><strong>Minimal Qty:</strong> <?php echo($prog['min_qty'] ?: '1'); ?></div>
                <div><strong>Diskon:</strong> <?php echo($prog['diskon_persen'] ?: '0'); ?>%</div>
                <div><strong>Outlets:</strong>
                    <?php
                        $outletQuery = $conn->prepare("SELECT B.nama_out FROM program_produk_outlet AS A LEFT JOIN outlet AS B ON A.id_out=B.id_out WHERE A.id_pp=:id ORDER BY B.nama_out ASC");
                        $outletQuery->bindParam(':id', $id, PDO::PARAM_INT);
                        $outletQuery->execute();
                        $outletNames = [];
                        while($outletRow = $outletQuery->fetch(PDO::FETCH_ASSOC)){
                            if(!empty($outletRow['nama_out'])) $outletNames[] = $outletRow['nama_out'];
                        }
                        echo(!empty($outletNames) ? implode(', ', $outletNames) : '-');
                    ?>
                </div>
            </div>
            <table class="table table-sm table-hover mg-b-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama Produk</th>
                        <th>Detail</th>
                        <th class="text-right">Harga Program</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                    $no = 1;
                    while($row = $detail->fetch(PDO::FETCH_ASSOC)){
                        echo('<tr>
                            <td>'.$no.'</td>
                            <td>'.$row['nama_pro'].'</td>
                            <td><small class="text-muted">'.$row['nama_kpr'].' ('.$row['berat_pro'].' '.$row['nama_spr'].')</small></td>
                            <td class="text-right">Rp. '.number_format($row['harga_program'],0,',','.').',-</td>
                        </tr>');
                        $no++;
                    }
                ?>
                </tbody>
            </table>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Tutup</button>
        </div>
<?php
break;
case "delete":
$id= intval($secu->injection(@$_GET['keycode']));
$read= $conn->prepare("SELECT id_pp, nama_program FROM program_produk WHERE id_pp=:id");
$read->bindParam(':id', $id, PDO::PARAM_INT);
$read->execute();
$view= $read->fetch(PDO::FETCH_ASSOC);
$jml= $conn->query("SELECT COUNT(id_ppd) AS total FROM program_produk_detail WHERE id_pp=$id")->fetch(PDO::FETCH_ASSOC);
?>
        <div class="modal-header bg-danger text-white">
            <h6 class="modal-title" id="exampleModalLabel"><i class="fas fa-trash"></i> Konfirmasi Hapus Program</h6>
            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
            </button>
        </div>
        <form id="formtransaksi" action="#" method="post" autocomplete="off">
        <input type="hidden" name="nmenu" id="nmenu" value="masterprogramproduk" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="delete" readonly="readonly" />
        <input type="hidden" name="keycode" id="keycode" value="<?php echo($id); ?>" readonly="readonly" />
        <div class="modal-body">
            <div class="alert alert-danger">
                <strong>Konfirmasi!</strong> Hapus program <b><?php echo($view['nama_program']); ?></b>?<br/>
                <small>Seluruh <?php echo($jml['total']); ?> produk dalam program ini juga akan dihapus.</small>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Batal</button>
            <button type="submit" id="bsave" class="btn btn-danger btn-xs"><i class="fas fa-trash"></i> Hapus Program</button>
        </div>
        </form>
        <script>
        (function(){
            // Bind a modal-scoped delete handler and prevent other global handlers
            $(document).off('submit', '#formtransaksi.moduleDelete');
            $(document).on('submit', '#formtransaksi.moduleDelete', function(e){
                e.preventDefault();
                e.stopImmediatePropagation();
                var nmenu = $(this).find('#nmenu').val();
                if(nmenu !== 'masterprogramproduk') return;
                var $btn = $('#bsave');
                $btn.prop('disabled', true);
                if($('#imgloading').length){ $('#imgloading').html('<img src="'+usuper+'/berkas/gif/tunggu.gif" style="width:15%;"  />'); }
                $.ajax({
                    url: usuper+"/modal/masterprogramproduk/action.php?act=delete",
                    type: 'POST',
                    dataType: 'text',
                    data: new FormData(this),
                    contentType: false,
                    cache: false,
                    processData: false,
                    success: function(data){
                        if(String(data).trim() === 'success'){
                            swal({
                                title: "Selamat!",
                                text: "Data berhasil dihapus.",
                                type: "success",
                                timer: 2000,
                                showCancelButton: false,
                                showConfirmButton: false
                            });
                            setTimeout(function(){ window.location.href = usuper+"/masterprogramproduk"; }, 2000);
                        } else {
                            swal("Maaf!", "Data gagal dihapus...", "error");
                        }
                    },
                    error: function(){ swal("Maaf!", "Proses data error...", "error"); },
                    complete: function(){ $btn.prop('disabled', false); if($('#imgloading').length){ $('#imgloading').html(''); } }
                });
            });
            // mark the form so our off() targets the right handler next time
            $('#formtransaksi').addClass('moduleDelete');
        })();
        </script>
<?php
break;
}
?>
