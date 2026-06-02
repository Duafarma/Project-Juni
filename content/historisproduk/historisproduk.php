<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Menu</a></li>
                <li class="breadcrumb-item"><a href="#">Laporan</a></li>
                <li class="breadcrumb-item active" aria-current="page">Historis Produk</li>
            </ol>
        </nav>
        <h4 class="content-title">Historis Produk</h4>
    </div>
</div>
<div class="content-body">
    <div class="component-section no-code">
        <!-- Filter Section -->
        <div class="row row-sm mg-b-15">
            <div class="col-sm-12">
                <div class="card card-body pd-20">
                    <div class="row">
                        <div class="col-sm-5">
                            <label class="form-label">Produk</label>
                            <select id="filter_produk" class="form-control select2" style="width:100%;">
                                <option value="">-- Semua Produk --</option>
                                <?php
                                    $produkList = $conn->query("SELECT id_pro, nama_pro FROM produk ORDER BY nama_pro ASC");
                                    while($rp = $produkList->fetch(PDO::FETCH_ASSOC)){
                                ?>
                                <option value="<?php echo $rp['id_pro']; ?>"><?php echo htmlspecialchars($rp['nama_pro']); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-sm-2">
                            <label class="form-label">Bulan</label>
                            <select id="filter_bulan" class="form-control">
                                <option value="">-- Semua --</option>
                                <option value="1">Januari</option>
                                <option value="2">Februari</option>
                                <option value="3">Maret</option>
                                <option value="4">April</option>
                                <option value="5">Mei</option>
                                <option value="6">Juni</option>
                                <option value="7">Juli</option>
                                <option value="8">Agustus</option>
                                <option value="9">September</option>
                                <option value="10">Oktober</option>
                                <option value="11">November</option>
                                <option value="12">Desember</option>
                            </select>
                        </div>
                        <div class="col-sm-2">
                            <label class="form-label">Tahun</label>
                            <select id="filter_tahun" class="form-control">
                                <option value="">-- Semua --</option>
                                <?php
                                    $thn = date('Y');
                                    for($y = $thn; $y >= $thn - 5; $y--) {
                                        echo "<option value=\"$y\">$y</option>";
                                    }
                                ?>
                            </select>
                        </div>
                        <div class="col-sm-3 d-flex align-items-end">
                            <button type="button" id="btn_tampil" class="btn btn-primary btn-xs mg-r-5">
                                <i class="fa fa-search"></i> Tampilkan
                            </button>
                            <button type="button" id="btn_reset" class="btn btn-secondary btn-xs mg-r-5">
                                <i class="fa fa-sync"></i> Reset
                            </button>
                            <button type="button" id="btn_cetak" class="btn btn-outline-success btn-xs" style="display:none;">
                                <i class="fa fa-file-excel" style="color:#217346;"></i> Download Excel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Result Section -->
        <div class="row row-sm" id="result_section" style="display:none;">
            <div class="col-sm-12">
                <div id="info_filter" class="mg-b-10 tx-12 tx-gray-500"></div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" id="tabel_historis">
                        <thead class="thead-light">
                            <tr>
                                <th><center>No.</center></th>
                                <th>Jenis</th>
                                <th>Supplier/Outlet</th>
                                <th>Kode</th>
                                <th>Faktur</th>
                                <th>Tanggal</th>
                                <th>Batchcode</th>
                                <th>Gudang</th>
                                <th><div align="right">In</div></th>
                                <th><div align="right">Out</div></th>
                            </tr>
                        </thead>
                        <tbody id="isi_historis">
                            <tr><td colspan="10" class="text-center tx-gray-400">Belum ada data. Silakan pilih filter lalu klik Tampilkan.</td></tr>
                        </tbody>
                        <tfoot id="foot_historis"></tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div id="loading_historis" style="display:none; text-align:center; padding:30px;">
            <i class="fa fa-spinner fa-spin fa-2x"></i><br>
            <span>Memuat data...</span>
        </div>

    </div>
</div>

<script>
$(document).ready(function(){

    // ---- Init Select2 produk ----
    $('#filter_produk').select2({
        placeholder: '-- Semua Produk --',
        allowClear: true,
        width: '100%'
    });

    // ---- Tampilkan ----
    $('#btn_tampil').on('click', function(){
        loadHistoris();
    });

    // ---- Reset ----
    $('#btn_reset').on('click', function(){
        $('#filter_produk').val('').trigger('change');
        $('#filter_bulan').val('');
        $('#filter_tahun').val('');
        $('#result_section').hide();
        $('#btn_cetak').hide();
        $('#isi_historis').html('<tr><td colspan="10" class="text-center tx-gray-400">Belum ada data. Silakan pilih filter lalu klik Tampilkan.</td></tr>');
        $('#foot_historis').html('');
    });

    // ---- Cetak ----
    $('#btn_cetak').on('click', function(){
        var produk = $('#filter_produk').val();
        var bulan  = $('#filter_bulan').val();
        var tahun  = $('#filter_tahun').val();
        var url    = '<?php echo($sistem); ?>/laporan/xls/produk/historis_filter.php?produk='+produk+'&bulan='+bulan+'&tahun='+tahun;
        window.open(url, '_blank');
    });

    function loadHistoris(){
        var produk = $('#filter_produk').val();
        var bulan  = $('#filter_bulan').val();
        var tahun  = $('#filter_tahun').val();

        $('#loading_historis').show();
        $('#result_section').hide();
        $('#btn_cetak').hide();

        $.ajax({
            url : '<?php echo($sistem); ?>/ajax/historisproduk/getdata.php',
            type: 'POST',
            data: {
                produk : produk,
                bulan  : bulan,
                tahun  : tahun
            },
            dataType: 'json',
            success: function(obj){
                $('#loading_historis').hide();
                if(obj.status === 'ok'){
                    renderTable(obj);
                    buildInfoFilter(produk, bulan, tahun, obj.total_rows);
                    $('#result_section').show();
                    $('#btn_cetak').show();
                } else {
                    alert('Gagal: ' + obj.message);
                }
            },
            error: function(xhr){
                $('#loading_historis').hide();
                alert('AJAX error ' + xhr.status + ':\n' + xhr.responseText);
            }
        });
    }

    function renderTable(obj){
        var rows = obj.rows;
        var html = '';
        if(rows.length === 0){
            html = '<tr><td colspan="10" class="text-center tx-gray-400">Tidak ada data ditemukan.</td></tr>';
        } else {
            for(var i=0; i<rows.length; i++){
                var r = rows[i];
                html += '<tr>';
                html += '<td><center>'+(i+1)+'</center></td>';
                html += '<td>'+r.jenis+'</td>';
                html += '<td>'+r.mitra+'</td>';
                html += '<td>'+r.kode+'</td>';
                html += '<td>'+r.faktur+'</td>';
                html += '<td>'+r.tanggal+'</td>';
                html += '<td>'+r.batchcode+'</td>';
                html += '<td>'+r.gudang+'</td>';
                html += '<td><div align="right">'+r.in_qty+'</div></td>';
                html += '<td><div align="right">'+r.out_qty+'</div></td>';
                html += '</tr>';
            }
        }
        $('#isi_historis').html(html);

        var foot = '';
        foot += '<tr><th></th><th colspan="7"><div align="right">TOTAL</div></th>';
        foot += '<th><div align="right">'+obj.total_in+'</div></th>';
        foot += '<th><div align="right">'+obj.total_out+'</div></th></tr>';
        foot += '<tr><th></th><th colspan="7"><div align="right">BALANCE</div></th>';
        foot += '<th colspan="2"><div align="center">'+obj.balance+'</div></th></tr>';
        $('#foot_historis').html(foot);
    }

    function buildInfoFilter(produk, bulan, tahun, total){
        var bulanNames = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        var info = '<b>Filter :</b> ';
        info += (produk ? 'Produk: <b>'+$('#filter_produk option:selected').text()+'</b>' : 'Semua Produk');
        info += ' | ';
        info += (bulan ? 'Bulan: <b>'+bulanNames[parseInt(bulan)]+'</b>' : 'Semua Bulan');
        info += ' | ';
        info += (tahun ? 'Tahun: <b>'+tahun+'</b>' : 'Semua Tahun');
        info += ' &nbsp; <span class="badge badge-primary">'+total+' baris data</span>';
        $('#info_filter').html(info);
    }

});
</script>
