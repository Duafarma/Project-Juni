<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Mitra</a></li>
                <li class="breadcrumb-item"><a href="<?php echo($sistem.'/outlet'); ?>">Outlet</a></li>
                <li class="breadcrumb-item active" aria-current="page">Diskon by Principle</li>
            </ol>
        </nav>
        <h4 class="content-title">Update Diskon Berdasarkan Principle</h4>
    </div>
</div>

<?php
    $kode = $secu->injection($_GET['keycode']);
    $principle = $secu->injection(@$_GET['principle']);
    
    // Get outlet info dan diskon default
    $read = $conn->prepare("SELECT o.nama_out, o.resmi_out, od.diskon_odi 
                           FROM outlet o 
                           INNER JOIN outlet_diskon od ON o.id_out = od.id_out 
                           WHERE o.id_out=:kode");
    $read->bindParam(':kode', $kode, PDO::PARAM_STR);
    $read->execute();
    $outlet = $read->fetch(PDO::FETCH_ASSOC);
?>

<div class="content-body">
    <div class="component-section no-code">
        <div class="row">
            <div class="col-sm-12 text-center mg-b-30">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mg-b-0">
                            <i class="fa fa-hospital-o mg-r-10"></i>
                            Outlet: <?php echo($outlet['resmi_out']); ?>
                        </h5>
                    </div>
                    <div class="card-body bg-light">
                        <p class="text-muted mg-b-0">
                            <i class="fa fa-percent mg-r-5"></i>
                            Atur diskon produk berdasarkan principle untuk outlet ini
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-8">
                <!-- Kosongkan bagian ini karena info sudah dipindah ke atas -->
            </div>
            <div class="col-sm-4 text-right">
                <a href="<?php echo($sistem.'/outlet'); ?>" class="btn btn-secondary btn-xs">
                    <i class="fa fa-arrow-left"></i> Kembali ke Outlet
                </a>
            </div>
        </div>

        <!-- Alert Keterangan -->
        <div class="alert alert-danger alert-dismissible mg-b-20">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <div class="d-flex align-items-center">
                <i class="fa fa-info-circle fa-2x mg-r-10"></i>
                <div>
                    <strong>Petunjuk Penggunaan:</strong><br>
                    Isi diskon lalu klik <strong>"Update Semua"</strong> untuk mengubah semua produk sekaligus, atau edit diskon individual per produk.
                </div>
            </div>
        </div>

        <!-- Pilih Principle -->
        <div class="row mg-b-20">
            <div class="col-sm-4">
                <label>Pilih Principle <span class="tx-danger">*</span></label>
                <select id="principle" class="form-control" onchange="loadProdukByPrinciple()">
                    <option value="">-- Pilih Principle --</option>
                    <?php
                        $master = $conn->prepare("SELECT mp.id_mp, mp.nama_principle, COUNT(p.id_pro) as jumlah_produk
                                                 FROM master_principle mp
                                                 LEFT JOIN produk p ON mp.id_mp = p.nama_p AND LOWER(p.status_pro) = 'active'
                                                 GROUP BY mp.id_mp, mp.nama_principle
                                                 ORDER BY mp.nama_principle");
                        $master->execute();
                        while($hasil = $master->fetch(PDO::FETCH_ASSOC)){
                            $selected = ($principle === $hasil['id_mp']) ? 'selected' : '';
                    ?>
                        <option value="<?php echo($hasil['id_mp']); ?>" <?php echo($selected); ?>>
                            <?php echo($hasil['nama_principle']); ?> (<?php echo($hasil['jumlah_produk']); ?> produk)
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-sm-4">
                <label>Update Diskon Semua <span class="tx-black">*</span></label>
                <div class="input-group">
                    <input type="number" id="diskonSemua" class="form-control" placeholder="<?php echo($outlet['diskon_odi']); ?>" value="<?php echo($outlet['diskon_odi']); ?>" min="0" max="100" step="1" />
                    <div class="input-group-append">
                        <button class="btn btn-primary btn-sm" type="button" onclick="updateSemuaDiskon()">
                            <i class="fa fa-refresh"></i> Update Semua
                        </button>
                    </div>
                </div>
                <small class="text-muted">Default outlet: <?php echo($outlet['diskon_odi']); ?>% - Isi diskon lalu klik Update Semua untuk mengubah semua produk sekaligus</small>
            </div>
              <div class="col-sm-4">
                <label>Download Excel <span class="tx-black">*</span></label>
                <div>
                    <button class="btn btn-success btn-sm" type="button" onclick="downloadExcelDiskonPrinciple()">
                        <i class="fa fa-file-excel-o"></i> Download Excel
                    </button>
                </div>
                <small class="text-muted">Download daftar produk + diskon untuk outlet & principle yang dipilih</small>
            </div>
        </div>

        <!-- Loading -->
        <div id="loading" style="display: none;">
            <div class="text-center">
                <img src="<?php echo($sistem); ?>/berkas/gif/tunggu.gif" style="width:5%;" />
                <p>Memuat data produk...</p>
            </div>
        </div>

        <!-- Tabel Produk -->
        <div id="tabelProduk" style="display: none;">
            <form id="formDiskonPrinciple" method="post" autocomplete="off">
                <input type="hidden" name="nmenu" value="outlet" />
                <input type="hidden" name="nact" value="updateDiskonBulk" />
                <input type="hidden" name="keycode" value="<?php echo($kode); ?>" />
                <input type="hidden" name="principle" id="hiddenPrinciple" value="" />
                
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="thead-dark">
                            <tr>
                                <th width="5%"><center>#</center></th>
                                <th width="60%">Nama Produk</th>
                                <th width="15%"><center>Diskon (%)</center></th>
                            </tr>
                        </thead>
                        <tbody id="listProduk">
                            <!-- Data akan dimuat via AJAX -->
                        </tbody>
                    </table>
                </div>
                
                <div class="mg-t-20">
                    <button type="button" class="btn btn-secondary btn-xs" onclick="window.location.href='<?php echo($sistem.'/outlet'); ?>'">
                        <i class="fa fa-times"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-success btn-xs">
                        <i class="fa fa-save"></i> Simpan Semua Diskon
                    </button>
                    <div id="imgloading" style="display: inline-block; margin-left: 10px;"></div>
                </div>
            </form>
        </div>

        <!-- Info jika tidak ada produk -->
        <div id="noProduk" style="display: none;">
            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i> Silakan pilih principle untuk melihat produk yang tersedia di outlet ini.
            </div>
        </div>
    </div>
</div>

<script>
var usuper = "<?php echo($sistem); ?>";
var outletId = "<?php echo($kode); ?>";

function downloadExcelDiskonPrinciple() {
    var principleId = $("#principle").val();
    if (principleId === "") {
        swal("Warning", "Silakan pilih principle terlebih dahulu!", "warning");
        return;
    }
    var url = usuper + "/ajax/outlet/export_diskonprinciple_excel.php?outlet_id=" + encodeURIComponent(outletId) + "&principle_id=" + encodeURIComponent(principleId);
    window.location.href = url;
}

function loadProdukByPrinciple() {
    var principleId = $("#principle").val();
    $("#hiddenPrinciple").val(principleId);
    
    console.log("Loading produk untuk principle:", principleId);
    
    if (principleId === "") {
        $("#tabelProduk").hide();
        $("#noProduk").show();
        $("#loading").hide();
        return;
    }
    
    $("#loading").show();
    $("#tabelProduk").hide();
    $("#noProduk").hide();
    
    $.ajax({
        url: usuper + "/ajax/outlet/loadprodukprinciple.php",
        type: "POST",
        dataType: "json",
        data: {
            outlet_id: outletId,
            principle_id: principleId
        },
        success: function(response) {
            console.log("Response dari server:", response);
            $("#loading").hide();
            if (response.status === "success") {
                $("#listProduk").html(response.data);
                $("#tabelProduk").show();
                console.log("Tabel berhasil dimuat dengan", response.count, "produk");
                
                // Debug: cek apakah input diskon sudah ada
                setTimeout(function() {
                    var inputCount = $(".diskon-input").length;
                    console.log("Jumlah input diskon setelah load:", inputCount);
                }, 100);
            } else {
                $("#noProduk").html('<div class="alert alert-warning"><i class="fa fa-exclamation-triangle"></i> ' + response.message + '</div>').show();
            }
        },
        error: function(xhr, status, error) {
            console.log("Error AJAX:", status, error);
            $("#loading").hide();
            $("#noProduk").html('<div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i> Terjadi kesalahan saat memuat data: ' + error + '</div>').show();
        }
    });
}

function updateSemuaDiskon() {
    var diskon = $("#diskonSemua").val();
    console.log("Diskon yang akan diupdate:", diskon);
    
    if (diskon === "" || diskon < 0 || diskon > 100) {
        swal("Error", "Masukkan diskon yang valid (0-100)!", "error");
        return;
    }
    
    // Cek apakah ada input diskon
    var inputDiskon = $(".diskon-input");
    console.log("Jumlah input diskon ditemukan:", inputDiskon.length);
    
    if (inputDiskon.length === 0) {
        swal("Warning", "Silakan pilih principle terlebih dahulu untuk melihat produk!", "warning");
        return;
    }
    
    // Update semua input diskon
    inputDiskon.val(diskon);
    
    // Visual feedback
    inputDiskon.addClass("border-success");
    setTimeout(function() {
        inputDiskon.removeClass("border-success");
    }, 1000);
    
    swal("Success", "Diskon " + inputDiskon.length + " produk telah diubah ke " + diskon + "%", "success");
}

// Auto-load jika principle sudah dipilih dari URL
$(document).ready(function() {
    var principleFromUrl = "<?php echo($principle); ?>";
    if (principleFromUrl !== "") {
        loadProdukByPrinciple();
    } else {
        $("#noProduk").show();
    }
});

// Form submission
$("#formDiskonPrinciple").submit(function(e) {
    e.preventDefault();
    $("#imgloading").html('<img src="' + usuper + '/berkas/gif/tunggu.gif" style="width:15%;" />');
    
    $.ajax({
        url: usuper + "/modal/outlet/action.php?act=updateDiskonBulk",
        type: "POST",
        data: new FormData(this),
        contentType: false,
        cache: false,
        processData: false,
        success: function(data) {
            $("#imgloading").html('');
            if (data === "success") {
                swal("Success", "Diskon berhasil diupdate!", "success").then(function() {
                    loadProdukByPrinciple(); // Reload data
                });
            } else {
                swal("Error", "Gagal mengupdate diskon!", "error");
            }
        },
        error: function() {
            $("#imgloading").html('');
            swal("Error", "Terjadi kesalahan!", "error");
        }
    });
});
</script>