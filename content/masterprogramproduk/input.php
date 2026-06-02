<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="<?php echo($sistem); ?>/masterprogramproduk">Master Program Produk</a></li>
                <li class="breadcrumb-item active" aria-current="page">Tambah Program</li>
            </ol>
        </nav>
        <h4 class="content-title"><i class="fas fa-box-open"></i> Tambah Program Produk</h4>
    </div>
</div>
<div class="content-body">
    <div class="component-section no-code">
        <form id="formmenu" action="#" method="post" autocomplete="off">
        <input type="hidden" name="namamodal" id="namamodal" value="masterprogramproduk" readonly="readonly" />
        <input type="hidden" name="namamenu" value="input" readonly="readonly" />
        <input type="hidden" name="jumaddorder" id="jumaddorder" value="1" />

        <h5 class="tx-semibold">Informasi Program</h5>
        <div class="row row-sm mg-b-15">
            <div class="col-sm-6">
                <label>Nama Program <span class="tx-danger">*</span></label>
                <input type="text" name="nama_program" id="nama_program" class="form-control" placeholder="Nama program..." required="required" />
            </div>
        </div>
        <div class="row row-sm mg-b-15">
            <div class="col-sm-3">
                <label>Jenis Program <span class="tx-danger">*</span></label>
                <select name="jenis_program" id="jenis_program" class="form-control select2" required="required">
                    <option value="">Pilih jenis program</option>
                    <option value="Paket Starter">Paket Starter</option>
                    <option value="Growth Pack">Growth Pack</option>
                    <option value="Custom">Custom</option>
                </select>
            </div>
            <div class="col-sm-2">
                <label>Qty Minimal <span class="tx-danger">*</span></label>
                <input type="number" name="min_qty" id="min_qty" class="form-control" min="1" value="6" required="required" />
            </div>
            <div class="col-sm-2">
                <label>Diskon (%) <span class="tx-danger">*</span></label>
                <input type="number" name="diskon_persen" id="diskon_persen" class="form-control" min="0" max="100" value="20" required="required" />
            </div>
            <div class="col-sm-5">
                <label>Outlet (bisa lebih dari 1) <span class="tx-danger">*</span></label>
                <select name="outlet[]" id="outlet" class="form-control select2" multiple="multiple" style="width:100%;" required="required">
                    <?php
                        $outlets = $conn->query("SELECT id_out, nama_out FROM outlet ORDER BY nama_out ASC");
                        while($out = $outlets->fetch(PDO::FETCH_ASSOC)){
                            echo('<option value="'.$out['id_out'].'">'.htmlspecialchars($out['nama_out']).'</option>');
                        }
                    ?>
                </select>
            </div>
        </div>

        <div class="clearfix mg-t-15 mg-b-10"></div>
        <h5 class="tx-semibold">Daftar Produk</h5>
        <p class="mg-b-10">Tambahkan produk beserta harga beli dalam program ini.</p>

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
                        /* baris pertama dimuat langsung agar form tidak kosong */
                        include('ajax/addprogramproduk/addprogramproduk.php');
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
        <button type="submit" class="btn btn-primary btn-pill">
            <i class="fa fa-save"></i> Simpan Program
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
    var nama = $("#nama_program").val().trim();
    if(nama === ''){
        swal("Maaf!", "Isi nama program terlebih dahulu...", "error");
        return;
    }
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
