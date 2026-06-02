

<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Pengguna</a></li>
                <li class="breadcrumb-item active" aria-current="page">Administrator</li>
            </ol>
        </nav>
        <h4 class="content-title">Approvel Stock Opname</h4>
    </div>
</div>
<div class="content-body">
    <div class="component-section no-code">
        <h5 id="section1" class="tx-semibold">Data Stockopname </h5>
        <p class="mg-b-25">Data Yang Sudah Selesai Dari Proses Checker dan Meker</p>
        <?php echo(($data->akses($admin, $menu, 'A.create_status')==='Active') ? '<a href="'.$sistem.'/stockopnameap/v"><button class="btn btn-primary btn-pill btn-xs"><i class="fa fa-plus-circle"></i> Rekap Hasil Inventory Sebelum </button></a>' : ''); ?>

        <?php
        $filterPrincipleId = $secu->injection(@$_GET['principle'] ?? '');
        $principle_list = $conn->prepare("SELECT id_mp, nama_principle FROM master_principle ORDER BY nama_principle ASC");
        $principle_list->execute();
        $principles = $principle_list->fetchAll(PDO::FETCH_ASSOC);
        $filterPrincipleName = '';
        foreach($principles as $p){
            if($p['id_mp'] === $filterPrincipleId) $filterPrincipleName = $p['nama_principle'];
        }
        ?>
        <div class="row row-sm mg-b-15">
            <div class="col-sm-4">
                <label class="form-label tx-semibold">Filter Principle</label>
                <select id="filterPrinciple" class="form-control">
                    <option value="">-- Semua Principle --</option>
                    <?php foreach($principles as $p): ?>
                    <option value="<?php echo htmlspecialchars($p['id_mp']); ?>"><?php echo htmlspecialchars($p['nama_principle']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-8 text-right" style="padding-top:28px;">
                <a href="<?php echo($sistem.'/stockopnameap'); ?>" class="btn btn-secondary btn-xs"><i class="fa fa-arrow-left"></i> Kembali ke Portal</a>
            </div>
        </div>

        <?php
        $active = 'Active';
        $sql = "SELECT A.id_psd, A.no_bcode, A.id_pro, A.status_barang, A.tgl_expired, A.gudang,A.status, A.tgl_psd, A.qty_so, A.sisa_psd,A.status, B.nama_pro, B.berat_pro,B.minstok_pro, B.nama_p, C.harga_phg, C.hargap_phg, E.nama_spr, F.nama_principle FROM produk_stokdetail AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro LEFT JOIN produk_harga AS C ON B.id_pro=C.id_pro LEFT JOIN kategori_produk AS D ON B.id_kpr=D.id_kpr LEFT JOIN satuan_produk AS E ON B.id_spr=E.id_spr LEFT JOIN master_principle AS F ON B.nama_p=F.id_mp WHERE (A.sisa_psd > 0 OR (A.sisa_psd = 0 AND A.qty_so > 0)) AND A.qty_so != A.sisa_psd AND A.id_psd NOT IN (SELECT id_psd FROM stock WHERE id_psd IS NOT NULL) AND C.status_phg=:active";
        if(!empty($filterPrincipleId)) $sql .= " AND B.nama_p = :filterPrinciple";
        $sql .= " ORDER BY F.nama_principle ASC, B.nama_pro ASC";
        $master = $conn->prepare($sql);
        $master->bindParam(':active', $active, PDO::PARAM_STR);
        if(!empty($filterPrincipleId)) $master->bindParam(':filterPrinciple', $filterPrincipleId, PDO::PARAM_STR);
        $master->execute();
        $allRows = $master->fetchAll(PDO::FETCH_ASSOC);

        // Cek item belum approve termasuk selisih=0 (untuk tampilkan tombol Setuju walaupun tidak ada selisih)
        $sqlPending = "SELECT COUNT(*) FROM produk_stokdetail AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro LEFT JOIN produk_harga AS C ON B.id_pro=C.id_pro WHERE A.sisa_psd >= 0 AND (A.sisa_psd > 0 OR (A.qty_so > 0)) AND A.id_psd NOT IN (SELECT id_psd FROM stock WHERE id_psd IS NOT NULL) AND C.status_phg=:active";
        if(!empty($filterPrincipleId)) $sqlPending .= " AND B.nama_p = :filterPrinciple";
        $stmtPending = $conn->prepare($sqlPending);
        $stmtPending->bindParam(':active', $active, PDO::PARAM_STR);
        if(!empty($filterPrincipleId)) $stmtPending->bindParam(':filterPrinciple', $filterPrincipleId, PDO::PARAM_STR);
        $stmtPending->execute();
        $totalPending = (int)$stmtPending->fetchColumn();
        // $totalPending > 0 artinya masih ada yang belum approve (meski semua selisih=0)

        // Ambil daftar principle yang masih punya item pending (termasuk selisih=0) untuk JS
        $sqlPendingPerPrinciple = "SELECT DISTINCT B.nama_p FROM produk_stokdetail AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro LEFT JOIN produk_harga AS C ON B.id_pro=C.id_pro WHERE A.sisa_psd >= 0 AND (A.sisa_psd > 0 OR (A.qty_so > 0)) AND A.id_psd NOT IN (SELECT id_psd FROM stock WHERE id_psd IS NOT NULL) AND C.status_phg=:active2";
        $stmtPendingPP = $conn->prepare($sqlPendingPerPrinciple);
        $stmtPendingPP->bindParam(':active2', $active, PDO::PARAM_STR);
        $stmtPendingPP->execute();
        $pendingPrincipleIds = $stmtPendingPP->fetchAll(PDO::FETCH_COLUMN);
        $pendingPrincipleIdsJson = json_encode($pendingPrincipleIds);
        ?>

        <form id="formtransaksis" action="#" method="post"  enctype="multipart/form-data" autocomplete="off">
        <input type="hidden" name="nmenu" id="nmenu" value="stockopnameap" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="input" readonly="readonly" />
        <input type="hidden" name="filter_principle" id="filter_principle" value="" />
       
        <div class="row row-sm">
            <div class="col-sm-12">
            	<div id="tableWrapper" class="table-responsive" <?php echo(count($allRows)===0 ? 'style="display:none;"' : ''); ?>>
				<table class="tabel">
                	<thead>
                        <tr>
                            <th><center>#</center></th>
                            <th><center>Principle</center></th>
                            <th><center>Id Produk</center></th>
                            <th><center>Nama Produk</center></th>
                            <th><center>No. Batch</center></th>
                            <th><center>QTY Inventory</center></th>
                            <th><center>No. Batch SO</center></th>
                            <th><center>QTY SO</center></th>
                            <th><center>Selisih</center></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                        if(count($allRows) > 0): foreach($allRows as $hasil){
                            $a = $hasil['sisa_psd'];
                            $b = $hasil['qty_so'];
                            $selisih = ($b - $a);
                            $nomor = $secu->injection(@$_POST['n']);
                        ?>
                        	<tr id="<?php echo("id_pro$nomor"); ?>" data-principle-id="<?php echo(htmlspecialchars($hasil['nama_p'] ?? '')); ?>">


                             <td>
                                <input type="hidden" name="id_principle[]" value="<?php echo(htmlspecialchars($hasil['nama_p'] ?? '')); ?>" />
                                <input type="text" name="id_psd[]" id="id_psd[]" class="form-control" value="<?php echo($hasil['id_psd']); ?>"   readonly="readonly"/>
                            </td>
                            <td>
                                <span class="badge badge-primary"><?php echo(htmlspecialchars($hasil['nama_principle'] ?? '-')); ?></span>
                            </td>
                            <td>
                                <input type="text" name="id_pro[]" id="id_pro[]" class="form-control" value="<?php echo($hasil['id_pro']); ?> " readonly="readonly"/>
                            </td>
                            <td>
                                <input type="text" name="nama_pro[]" id="nama_pro[]" class="form-control" value="<?php echo($hasil['nama_pro']); ?> " readonly="readonly"/>
                            </td>
                            <td>
                                <input type="text" name="no_bcode[]" id="no_bcode[]" class="form-control" value="<?php echo($hasil['no_bcode']); ?>"  readonly="readonly"/>
                            </td>
                            <td>
                                <input type="text" name="qty[]" id="qty[]" class="form-control" value="<?php echo($hasil['sisa_psd']); ?>" readonly="readonly"/>
                            </td>
                            <td>
                                <input type="text" name="bcode_so[]" id="bcode_so[]" class="form-control" value="<?php echo($hasil['no_bcode']); ?>" readonly="readonly"/>
                            </td>
                            <td>
                                <input type="text" name="qty_so[]" id="qty_so[]" class="form-control" value="<?php echo($hasil['qty_so']); ?>" readonly="readonly"/>
                            </td>

                            <td>
                                <input type="text" name="selisih[]" id="selisih[]" class="form-control" value="<?php echo($selisih); ?>" readonly="readonly"/>
                            </td>
                                
                        </tr>
                    <?php } endif; ?>
                    </tbody>
                </table>
                <div id="imgloading"></div>
                </div><!-- end tableWrapper -->
            </div>
        </div>

        <?php if($totalPending === 0): ?>
        <div id="phpApprovedMessage" class="text-center pd-y-20">
            <i class="fa fa-check-circle text-success" style="font-size:2.5rem;"></i>
            <p class="tx-semibold mg-t-10 mg-b-5">
                <?php if(!empty($filterPrincipleName)): ?>
                Principle <span class="badge badge-primary"><?php echo htmlspecialchars($filterPrincipleName); ?></span> Sudah Di-Approve
                <?php else: ?>Semua Data Sudah Di-Approve<?php endif; ?>
            </p>
            <p class="tx-color-03">Tidak ada data stock opname yang perlu diproses.</p>
            <a href="<?php echo($sistem.'/stockopnameap'); ?>" class="btn btn-secondary btn-xs mg-t-5"><i class="fa fa-arrow-left"></i> Kembali ke Portal</a>
            <a href="<?php echo($sistem.'/stockopnameap/v'); ?>" class="btn btn-info btn-xs mg-t-5"><i class="fa fa-list"></i> Lihat Rekap</a>
        </div>
        <?php elseif(count($allRows)===0 && $totalPending > 0): ?>
        <div id="phpNoSelisihMessage" class="text-center pd-y-20">
            <i class="fa fa-info-circle text-info" style="font-size:2.5rem;"></i>
            <p class="tx-semibold mg-t-10 mg-b-5">Semua Produk Tidak Ada Selisih</p>
            <p class="tx-color-03">Tidak ada perbedaan QTY, silakan langsung tekan <strong>Setuju</strong> untuk menyelesaikan approve.</p>
        </div>
        <?php endif; ?>

        <div id="noSelisihMessage" class="text-center pd-y-20" style="display:none;">
            <i class="fa fa-check-circle text-info" style="font-size:2.5rem;"></i>
            <p class="tx-semibold mg-t-10 mg-b-5" id="noSelisihMessageText"></p>
            <p class="tx-color-03">Tidak ada perbedaan QTY untuk principle ini, silakan tekan <strong>Setuju</strong> untuk menyelesaikan approve.</p>
        </div>

        <div id="approvedMessage" class="text-center pd-y-20" style="display:none;">
            <i class="fa fa-check-circle text-success" style="font-size:2.5rem;"></i>
            <p class="tx-semibold mg-t-10 mg-b-5" id="approvedMessageText"></p>
            <p class="tx-color-03">Tidak ada selisih stock opname yang perlu diproses untuk principle ini.</p>
            <a href="<?php echo($sistem.'/stockopnameap'); ?>" class="btn btn-secondary btn-xs mg-t-5"><i class="fa fa-arrow-left"></i> Kembali ke Portal</a>
            <a href="<?php echo($sistem.'/stockopnameap/v'); ?>" class="btn btn-info btn-xs mg-t-5"><i class="fa fa-list"></i> Lihat Rekap</a>
        </div>
        <div id="formButtons" class="row row-sm mg-t-15" <?php echo($totalPending === 0 ? 'style="display:none;"' : ''); ?>>
            <div class="col-sm-12">
                <a href="<?php echo("$sistem/stockopnameap"); ?>" title="Batal"><button type="button" class="btn btn-danger btn-xs">Revisi</button></a>
                <button type="submit" id="bsave" class="btn btn-success btn-xs">Setuju</button>
            </div>
        </div>
		</form>
    </div>
</div>

<script>
$(document).ready(function(){
    var principleNames = {};
    $('#filterPrinciple option').each(function(){
        if($(this).val()) principleNames[$(this).val()] = $(this).text();
    });

    // Daftar principle yang masih punya item pending (termasuk selisih=0)
    var pendingPrincipleIds = <?php echo $pendingPrincipleIdsJson; ?>;

    function checkVisibleRows(isPendingOverride){
        var sel = $('#filterPrinciple').val();
        $('#tableWrapper').show();
        var visible = $('#formtransaksis tbody tr:visible').length;

        if(sel === ''){
            // Kembali ke semua principle
            $('#approvedMessage').hide();
            $('#noSelisihMessage').hide();
            var totalPendingJS = <?php echo $totalPending; ?>;
            if(totalPendingJS === 0){
                // Benar-benar sudah semua approve
                $('#tableWrapper').hide();
                $('#formButtons').hide();
            } else {
                var totalVisible = $('#formtransaksis tbody tr:visible').length;
                if(totalVisible > 0){
                    $('#tableWrapper').show();
                }
                $('#formButtons').show();
            }
        } else if(visible === 0){
            var isPending = pendingPrincipleIds.indexOf(sel) !== -1;
            if(isPending){
                // Principle punya item pending tapi semua selisih=0
                var name = principleNames[sel] || sel;
                $('#noSelisihMessageText').html('Tidak ada selisih di Principle <span class="badge badge-primary">' + name + '</span>, data siap di-approve.');
                $('#noSelisihMessage').show();
                $('#approvedMessage').hide();
                $('#tableWrapper').hide();
                $('#formButtons').show();
            } else {
                // Principle benar-benar sudah di-approve semua
                var name = principleNames[sel] || sel;
                $('#approvedMessageText').html('Principle <span class="badge badge-primary">' + name + '</span> Sudah Di-Approve');
                $('#approvedMessage').show();
                $('#noSelisihMessage').hide();
                $('#formButtons').hide();
                $('#tableWrapper').hide();
            }
        } else {
            // Principle tertentu dipilih dan masih ada data selisih
            $('#approvedMessage').hide();
            $('#noSelisihMessage').hide();
            $('#formButtons').show();
        }
    }

    $('#filterPrinciple').on('change', function(){
        var selectedPrinciple = $(this).val();
        $('#filter_principle').val(selectedPrinciple);
        $('#formtransaksis tbody tr').each(function(){
            var rowPrinciple = $(this).data('principle-id');
            if(selectedPrinciple === '' || String(rowPrinciple) === String(selectedPrinciple)){
                $(this).show();
                $(this).find('input, select, textarea').prop('disabled', false);
            } else {
                $(this).hide();
                $(this).find('input, select, textarea').prop('disabled', true);
            }
        });
        // Sembunyikan PHP approved div saat principle tertentu dipilih
        if(selectedPrinciple !== ''){
            $('#phpApprovedMessage').hide();
            $('#phpNoSelisihMessage').hide();
            // Cek real-time ke server
            $.getJSON(usuper + '/json/stockopnameap/check_pending.php', { principle: selectedPrinciple }, function(res){
                checkVisibleRows(res.pending > 0);
            }).fail(function(){
                checkVisibleRows();
            });
        } else {
            $('#phpApprovedMessage').show();
            $('#phpNoSelisihMessage').show();
            checkVisibleRows();
        }
    });

    // Override redirect tegar.js: setelah approve kembali ke /i agar data PHP refresh
    $('#formtransaksis').off('submit').on('submit', function(e){
        e.preventDefault();
        var nmenu = $('#nmenu').val();
        var nact  = $('#nact').val();
        $('#bsave').prop('disabled', true);
        $('#imgloading').html('<img src="'+usuper+'/berkas/gif/tunggu.gif" style="width:15%;" />');
        $.ajax({
            url         : usuper + '/modal/' + nmenu + '/action.php?act=' + nact,
            type        : 'POST',
            async       : true,
            dataType    : 'text',
            data        : new FormData(this),
            contentType : false,
            cache       : false,
            processData : false,
            success: function(data){
                if(data == 'success'){
                    swal({
                        title  : 'Selamat!',
                        text   : 'Data berhasil di approve!',
                        type   : 'success',
                        timer  : 2000,
                        showCancelButton  : false,
                        showConfirmButton : false
                    }, function(){
                        window.location.href = usuper + '/stockopnameap/i';
                    });
                    setTimeout(function(){ window.location.href = usuper + '/stockopnameap/i'; }, 2100);
                } else {
                    swal('Maaf!', 'Data gagal di approve...', 'error');
                    $('#bsave').prop('disabled', false);
                }
            },
            error: function(){ swal('Maaf!', 'Proses data error...', 'error'); $('#bsave').prop('disabled', false); },
            complete: function(){ $('#imgloading').html(''); }
        });
    });
});
</script>