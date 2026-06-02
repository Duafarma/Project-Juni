<?php
    $id_edit = intval($secu->injection(@$_GET['keycode']));
    $header  = $conn->prepare("SELECT id_pp, nama_program, jenis_program, min_qty, diskon_persen FROM program_produk WHERE id_pp=:id");
    $header->bindParam(':id', $id_edit, PDO::PARAM_INT);
    $header->execute();
    $prog    = $header->fetch(PDO::FETCH_ASSOC);
    if(!$prog){ echo('<div class="content-body"><div class="alert alert-danger">Data tidak ditemukan.</div></div>'); return; }
    $outletSel = $conn->prepare("SELECT id_out FROM program_produk_outlet WHERE id_pp=:id");
    $outletSel->bindParam(':id', $id_edit, PDO::PARAM_INT);
    $outletSel->execute();
    $selectedOutlets = $outletSel->fetchAll(PDO::FETCH_COLUMN, 0);
    $details = $conn->prepare("SELECT B.id_ppd, B.id_pro, B.harga_program, C.kode_pro, C.nama_pro, C.berat_pro, D.nama_kpr, E.nama_spr FROM program_produk_detail AS B LEFT JOIN produk AS C ON B.id_pro=C.id_pro LEFT JOIN kategori_produk AS D ON C.id_kpr=D.id_kpr LEFT JOIN satuan_produk AS E ON C.id_spr=E.id_spr WHERE B.id_pp=:id ORDER BY C.nama_pro ASC");
    $details->bindParam(':id', $id_edit, PDO::PARAM_INT);
    $details->execute();
    $rows = $details->fetchAll(PDO::FETCH_ASSOC);
    $jmlRow = count($rows) > 0 ? count($rows) : 1;
?>
<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="<?php echo($sistem); ?>/masterprogramproduk">Master Program Produk</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit Program</li>
            </ol>
        </nav>
        <h4 class="content-title"><i class="fas fa-edit"></i> Edit Program Produk</h4>
    </div>
</div>
<div class="content-body">
    <div class="component-section no-code">
        <form id="formmenu" action="#" method="post" autocomplete="off">
        <input type="hidden" name="namamodal" id="namamodal" value="masterprogramproduk" readonly="readonly" />
        <input type="hidden" name="namamenu" value="update" readonly="readonly" />
        <input type="hidden" name="keycode" value="<?php echo($id_edit); ?>" readonly="readonly" />
        <input type="hidden" name="jumaddorder" id="jumaddorder" value="<?php echo($jmlRow); ?>" />

        <h5 class="tx-semibold">Informasi Program</h5>
        <div class="row row-sm mg-b-15">
            <div class="col-sm-6">
                <label>Nama Program <span class="tx-danger">*</span></label>
                <input type="text" name="nama_program" id="nama_program" class="form-control"
                       value="<?php echo(htmlspecialchars($prog['nama_program'])); ?>"
                       placeholder="Nama program..." required="required" />
            </div>
        </div>
        <div class="row row-sm mg-b-15">
            <div class="col-sm-3">
                <label>Jenis Program <span class="tx-danger">*</span></label>
                <select name="jenis_program" id="jenis_program" class="form-control select2" required="required">
                    <option value="">Pilih jenis program</option>
                    <option value="Paket Starter" <?php echo($prog['jenis_program']==='Paket Starter' ? 'selected' : ''); ?>>Paket Starter</option>
                    <option value="Growth Pack" <?php echo($prog['jenis_program']==='Growth Pack' ? 'selected' : ''); ?>>Growth Pack</option>
                    <option value="Custom" <?php echo($prog['jenis_program']==='Custom' ? 'selected' : ''); ?>>Custom</option>
                </select>
            </div>
            <div class="col-sm-2">
                <label>Qty Minimal <span class="tx-danger">*</span></label>
                <input type="number" name="min_qty" id="min_qty" class="form-control" min="1" value="<?php echo(intval($prog['min_qty']?:6)); ?>" required="required" />
            </div>
            <div class="col-sm-2">
                <label>Diskon (%) <span class="tx-danger">*</span></label>
                <input type="number" name="diskon_persen" id="diskon_persen" class="form-control" min="0" max="100" value="<?php echo(intval($prog['diskon_persen']?:20)); ?>" required="required" />
            </div>
            <div class="col-sm-5">
                <label>Outlet (bisa lebih dari 1) <span class="tx-danger">*</span></label>
                <select name="outlet[]" id="outlet" class="form-control select2" multiple="multiple" style="width:100%;" required="required">
                    <?php
                        $outlets = $conn->query("SELECT id_out, nama_out FROM outlet ORDER BY nama_out ASC");
                        while($out = $outlets->fetch(PDO::FETCH_ASSOC)){
                            $selected = in_array($out['id_out'], $selectedOutlets) ? 'selected' : '';
                            echo('<option value="'.$out['id_out'].'" '.$selected.'>'.htmlspecialchars($out['nama_out']).'</option>');
                        }
                    ?>
                </select>
            </div>
        </div>

        <div class="clearfix mg-t-15 mg-b-10"></div>
        <h5 class="tx-semibold">Daftar Produk</h5>
        <p class="mg-b-10">Edit produk beserta harga beli dalam program ini.</p>

        <div class="table-responsive">
            <table class="table table-hover mg-b-0">
                <thead>
                    <tr>
                        <th width="250">Produk</th>
                        <th>Detail</th>
                        <th width="180">Harga Program (Rp)</th>
                        <th width="60"><center>Hapus</center></th>
                    </tr>
                </thead>
                <tbody id="dataaddprogramproduk">
                <?php
                    if(count($rows) > 0){
                        foreach($rows as $i => $row){
                            $nomor = $i + 1;
                            $detail = $row['nama_kpr'].' ('.$row['berat_pro'].' '.$row['nama_spr'].')';
                            echo('
                            <tr id="traddorder'.$nomor.'">
                                <td>
                                    <a href="#modal1" onclick="mproductmp('.$nomor.')" data-toggle="modal">
                                        <div id="noproduct'.$nomor.'">('.$row['kode_pro'].') '.$row['nama_pro'].'</div>
                                    </a>
                                    <input type="hidden" name="product[]" id="product'.$nomor.'" class="itemproduct" value="'.$row['id_pro'].'" readonly="readonly" />
                                </td>
                                <td><div id="detailproduct'.$nomor.'">'.$detail.'</div></td>
                                <td>
                                    <input type="text" name="harga[]" id="pharga'.$nomor.'"
                                           class="inputangka form-control form-control-sm"
                                           onkeyup="angka(this)"
                                           value="'.number_format($row['harga_program'],0,',','.').',"
                                           placeholder="0" required="required" />
                                </td>
                                <td>
                                    <center>
                                        <a onclick="deleteorder('.$nomor.')">
                                            <span class="badge badge-danger"><i class="fa fa-times-circle"></i></span>
                                        </a>
                                    </center>
                                </td>
                            </tr>');
                        }
                    } else {
                        include('ajax/addprogramproduk/addprogramproduk.php');
                    }
                ?>
                </tbody>
            </table>
        </div>

        <div class="mg-t-10 mg-b-15">
            <button type="button" class="btn btn-secondary btn-pill btn-xs" onclick="addprogramproduk()">
                <i class="fa fa-plus-circle"></i> Tambah Produk
            </button>
        </div>

        <div class="clearfix mg-t-20"></div>
        <button type="submit" class="btn btn-warning btn-pill">
            <i class="fa fa-save"></i> Update Program
        </button>
        <a href="<?php echo($sistem); ?>/masterprogramproduk">
            <button type="button" class="btn btn-secondary btn-pill mg-l-5">
                <i class="fa fa-arrow-left"></i> Kembali
            </button>
        </a>
        </form>
    </div>
</div>

<script>
function addprogramproduk(){
    var jumlah = parseInt($("#jumaddorder").val());
    $.ajax({
        type    : "GET",
        url     : usuper+"/ajax/addprogramproduk/addprogramproduk.php",
        data    : { "jumlah" : jumlah },
        success : function(data){
            $("#dataaddprogramproduk").append(data);
            $("#jumaddorder").val(jumlah + 1);
        }
    });
}

function mproductmp(nomor){
    $.ajax({
        url     : usuper+"/modal/masterprogramproduk/showproduct.php",
        type    : "POST",
        async   : true,
        dataType: "text",
        cache   : false,
        data    : { "x" : nomor },
        success : function(data){ $(".modal-content").html(data); }
    });
}

function showproductmp(nomor, id, nama, code, berat, kategori, satuankpr, satuan, harga){
    $("#noproduct"+nomor).html('('+code+') '+nama);
    $("#detailproduct"+nomor).html(kategori+' ('+berat+' '+satuan+')');
    $("#product"+nomor).val(id);
    if(typeof harga !== 'undefined' && harga !== '' && $("#pharga"+nomor).length){
        $("#pharga"+nomor).val(titik(harga));
    }
    $('#modal1').modal('hide');
}

$(document).ready(function(){
    $("#formmenu").off('submit').on('submit', function(e){
        e.preventDefault();
        var modal = $("#namamodal").val();
        if(modal !== 'masterprogramproduk') return;
        $("#formmenu button[type=submit]").prop('disabled', true);
        if($("#imgloading").length){
            $("#imgloading").html('<img src="'+usuper+'/berkas/gif/tunggu.gif" style="width:15%;"  />');
        }
        $.ajax({
            url: usuper+"/modal/"+modal+"/action.php",
            type: 'POST',
            async: true,
            dataType: 'text',
            data: new FormData(this),
            contentType: false,
            cache: false,
            processData: false,
            success: function(data){
                swal({
                    title: "Selamat!",
                    text: "Data berhasil disimpan.",
                    type: "success",
                    timer: 2000,
                    showCancelButton: false,
                    showConfirmButton: false
                }, function(){
                    window.location.href = usuper+"/"+data;
                });
            },
            error: function(){ swal("Maaf!", "Proses data error...", "error"); },
            complete: function(){
                $("#formmenu button[type=submit]").prop('disabled', false);
                if($("#imgloading").length){
                    $("#imgloading").html('');
                }
            }
        });
    });
});
</script>
