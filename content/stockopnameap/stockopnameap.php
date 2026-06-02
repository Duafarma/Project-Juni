<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Menu</a></li>
                <li class="breadcrumb-item active" aria-current="page">Portal</li>
            </ol>
        </nav>
        <h4 class="content-title">PORTAL APPROVEL STOCK OPNAME</h4>
    </div>
</div>
<?php $cari = $secu->injection(@$_GET['cari']); ?>
<input type="hidden" name="caridata" id="caridata" value="<?php echo($cari); ?>" readonly="readonly" />
<input type="hidden" name="cariitem" id="halaman" value="1" readonly="readonly" />
<input type="hidden" name="maximal" id="maximal" value="15" readonly="readonly" />
<input type="hidden" name="namamodal" id="namamodal" value="stockopnameap" readonly="readonly" />
<input type="hidden" name="namamenu" id="namamenu" value="stockopnameap" readonly="readonly" />

<div class="content-body">
    <div class="row mg-b-10 align-items-center">
        <div class="col-sm-3">
            <?php
            $pl = $conn->prepare("SELECT id_mp, nama_principle FROM master_principle ORDER BY nama_principle ASC");
            $pl->execute();
            $plList = $pl->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <select id="cariPrinciple" class="form-control form-control-sm">
                <option value="">-- Semua Principle --</option>
                <?php foreach($plList as $p): ?>
                <option value="<?php echo htmlspecialchars($p['id_mp']); ?>"><?php echo htmlspecialchars($p['nama_principle']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-5">
            <small class="text-muted"><i class="fa fa-info-circle"></i> Hanya menampilkan data dengan selisih &plusmn; (berbeda dari inventory)</small>
        </div>
        <div class="col-sm-4 text-right">
            <a href="<?php echo($data->sistem('url_sis').'/stockopnameap/i'); ?>" class="btn btn-success btn-xs">
                <i class="fa fa-check-circle"></i> Proses Approve Semua
            </a>
            <a href="<?php echo($data->sistem('url_sis').'/stockopnameap/v'); ?>" class="btn btn-info btn-xs">
                <i class="fa fa-list"></i> Lihat Rekap
            </a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-bordered mg-b-0">
            <thead class="thead-light">
                <tr>
                    <th colspan="2"><center>INVENTORY</center></th>
                    <th colspan="2"><center>STOCK OPNAME</center></th>
                    <th colspan="3"></th>
                </tr>
                <tr>
                    <th><center>Principle</center></th>
                    <th>Nama Produk</th>
                    <th><center>No. Batch</center></th>
                    <th><center>QTY Inventory</center></th>
                    <th><center>QTY SO</center></th>
                    <th><center>Selisih</center></th>
                    <th><center>Aksi</center></th>
                </tr>
            </thead>
            <tbody id="isitabel"></tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function(){
    viewdata('stockopnameap', 15, 1);

    var baseApproveUrl = '<?php echo($data->sistem('url_sis').'/stockopnameap/i'); ?>';

    $('#cariPrinciple').on('change', function(){
        var principleId = $(this).val();
        var keyword = $(this).find('option:selected').text().trim().toLowerCase();

        // Update approve button URL
        $('#btnApprove').attr('href', baseApproveUrl + (principleId ? '?principle=' + principleId : ''));

        // Filter table rows
        $('#isitabel tr').each(function(){
            var principle = $(this).find('td:first').text().trim().toLowerCase();
            $(this).toggle(principleId === '' || principle === keyword);
        });
    });
});
</script>
