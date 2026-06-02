

<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="<?php echo($sistem.'/stockopnameap'); ?>">Portal Approvel</a></li>
                <li class="breadcrumb-item active" aria-current="page">Hasil Approve</li>
            </ol>
        </nav>
        <h4 class="content-title">Rekap Hasil Stock Opname &mdash; <?php echo(date('F Y')); ?></h4>
    </div>
</div>
<div class="content-body">
    <div class="component-section no-code">
        <div class="row mg-b-15">
            <div class="col-sm-6">
                <h5 class="tx-semibold mg-b-0">Data Yang Sudah Di-Approve</h5>
                <p class="tx-color-03 mg-b-0">Perubahan inventory berdasarkan hasil stock opname</p>
            </div>
            <div class="col-sm-6 text-right">
                <a href="<?php echo($sistem.'/stockopnameap'); ?>">
                    <button type="button" class="btn btn-secondary btn-xs"><i class="fa fa-arrow-left"></i> Kembali ke Portal</button>
                </a>
                <a href="<?php echo($sistem.'/stockopnameap/i'); ?>">
                    <button type="button" class="btn btn-primary btn-xs"><i class="fa fa-check-circle"></i> Approve Lagi</button>
                </a>
                <a id="btnDownloadExcel" href="<?php echo($data->sistem('url_sis').'/laporan/excel_stockopname_approve.php'); ?>" target="_blank">
                    <button type="button" class="btn btn-success btn-xs"><i class="fa fa-file-excel-o"></i> Download Excel</button>
                </a>
            </div>
        </div>

        <?php
        $principle_list = $conn->prepare("SELECT id_mp, nama_principle FROM master_principle ORDER BY nama_principle ASC");
        $principle_list->execute();
        $principles = $principle_list->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <div class="row row-sm mg-b-15">
            <div class="col-sm-4">
                <label class="form-label tx-semibold">Filter Principle</label>
                <select id="filterPrincipleView" class="form-control">
                    <option value="">-- Semua Principle --</option>
                    <?php foreach($principles as $p): ?>
                    <option value="<?php echo htmlspecialchars($p['nama_principle']); ?>"><?php echo htmlspecialchars($p['nama_principle']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="row row-sm">
            <div class="col-sm-12">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered mg-b-0" id="tableView">
                        <thead class="thead-light">
                            <tr>
                                <th><center>#</center></th>
                                <th><center>Principle</center></th>
                                <th>Nama Produk</th>
                                <th><center>No. Batch</center></th>
                                <th><center>QTY Sebelum SO</center></th>
                                <th><center>QTY Sesudah SO</center></th>
                                <th><center>Selisih</center></th>
                                <th><center>QTY Inventory Sekarang</center></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                            $nomor  = 1;
                            $master = $conn->prepare("SELECT A.id, A.id_psd, A.id_pro, A.nama_pro, A.qty, A.qty_so, A.no_bcode, A.bcode_so, A.selisih, B.sisa_psd, C.nama_p, D.nama_principle FROM stock AS A LEFT JOIN produk_stokdetail AS B ON A.id_psd=B.id_psd LEFT JOIN produk AS C ON A.id_pro=C.id_pro LEFT JOIN master_principle AS D ON C.nama_p=D.id_mp WHERE A.qty_so > 0 ORDER BY D.nama_principle ASC, A.nama_pro ASC");
                            $master->execute();
                            while($hasil = $master->fetch(PDO::FETCH_ASSOC)){
                                $selisih = (int)$hasil['qty_so'] - (int)$hasil['qty'];
                                if($selisih > 0){
                                    $selisihBadge = '<span class="badge badge-success">+'.number_format($selisih).'</span>';
                                } elseif($selisih < 0){
                                    $selisihBadge = '<span class="badge badge-danger">'.number_format($selisih).'</span>';
                                } else {
                                    $selisihBadge = '<span class="badge badge-secondary">0</span>';
                                }
                        ?>
                            <tr data-principle="<?php echo htmlspecialchars($hasil['nama_principle'] ?? ''); ?>">
                                <td><center><?php echo($nomor); ?></center></td>
                                <td><center><span class="badge badge-primary"><?php echo(htmlspecialchars($hasil['nama_principle'] ?? '-')); ?></span></center></td>
                                <td><?php echo(htmlspecialchars($hasil['nama_pro'])); ?></td>
                                <td><center><?php echo(htmlspecialchars($hasil['bcode_so'])); ?></center></td>
                                <td><center><?php echo(number_format($hasil['qty'])); ?></center></td>
                                <td><center><?php echo(number_format($hasil['qty_so'])); ?></center></td>
                                <td><center><?php echo($selisihBadge); ?></center></td>
                                <td><center><?php echo(number_format($hasil['sisa_psd'])); ?></center></td>
                            </tr>
                        <?php $nomor++; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="row mg-t-20">
            <div class="col-sm-12 text-right">
                <a href="<?php echo($sistem.'/stockopnameap'); ?>">
                    <button type="button" class="btn btn-secondary btn-xs"><i class="fa fa-arrow-left"></i> Kembali ke Portal</button>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    var baseExportUrl = $('#btnDownloadExcel').attr('href').split('?')[0];
    $('#filterPrincipleView').on('change', function(){
        var sel = $(this).val();
        $('#tableView tbody tr').each(function(){
            if(sel === '' || $(this).data('principle') === sel){
                $(this).show();
            } else {
                $(this).hide();
            }
        });
        // Update URL download Excel dengan filter principle
        if(sel !== ''){
            $('#btnDownloadExcel').attr('href', baseExportUrl + '?principle=' + encodeURIComponent(sel));
        } else {
            $('#btnDownloadExcel').attr('href', baseExportUrl);
        }
    });
});
</script>
