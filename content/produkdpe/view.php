<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Menu</a></li>
                <li class="breadcrumb-item"><a href="#">Master Data</a></li>
                <li class="breadcrumb-item active" aria-current="page">Produk</li>
            </ol>
        </nav>
        <h4 class="content-title">Historis</h4>
    </div>
</div>
<?php
	$kode	= $secu->injection($_GET['keycode']);
	$batch	= $secu->injection(@$_GET['batch']);
    $mitra	= $secu->injection(@$_GET['mitra']);
	$read	= $conn->prepare("SELECT nama_pro FROM produk WHERE id_pro=:kode");
	$read->bindParam(':kode', $kode, PDO::PARAM_STR);
	$read->execute();
	$view	= $read->fetch(PDO::FETCH_ASSOC);
	$proses = $conn->query("CALL reportproduk('$kode')");
	do { $proses->fetchAll(); } while ($proses->nextRowset());
	$proses->closeCursor();
	$listbatch	= $conn->prepare("SELECT DISTINCT bcode_rpo FROM report_produk WHERE id_pro=:kode AND bcode_rpo IS NOT NULL AND bcode_rpo != '' ORDER BY bcode_rpo");
	$listbatch->bindParam(':kode', $kode, PDO::PARAM_STR);
	$listbatch->execute();
	$batchOptions = $listbatch->fetchAll(PDO::FETCH_ASSOC);
    $listmitra	= $conn->prepare("SELECT DISTINCT mitra_rpo FROM report_produk WHERE id_pro=:kode AND mitra_rpo IS NOT NULL AND mitra_rpo != '' ORDER BY mitra_rpo");
    $listmitra->bindParam(':kode', $kode, PDO::PARAM_STR);
    $listmitra->execute();
    $mitraOptions = $listmitra->fetchAll(PDO::FETCH_ASSOC);
    $xlsUrl = "$sistem/laporan/xls/produk/historis.php?key=".urlencode($kode);
    if($batch != '') { $xlsUrl .= "&batch=".urlencode($batch); }
    if($mitra != '') { $xlsUrl .= "&mitra=".urlencode($mitra); }
?>
<div class="content-body">
    <div class="component-section no-code">
        <div class="row row-sm">
            <div class="col-sm-12">
                <div style="padding:0 15px;">
                    <table style="width:100%;border-collapse:collapse;" cellpadding="0" cellspacing="0">
                        <tr>
                            <td style="width:80px;vertical-align:top;padding:0;">Produk</td>
                            <td style="width:20px;vertical-align:top;padding:0;"><div style="text-align:center;">:</div></td>
                            <td style="vertical-align:top;padding:0;"><?php echo($view['nama_pro']); ?></td>
                        </tr>
                    </table>
                </div>
                <div class="clearfix mg-t-15 mg-b-15"></div>
                <div class="card mg-b-15" style="border:1px solid #dee2e6;border-radius:6px;">
                    <div class="card-body pd-15">
                        <form method="GET" action="<?php echo("$sistem/sistem.php"); ?>" id="formFilterBatch">
                            <input type="hidden" name="menu" value="vproduk">
                            <input type="hidden" name="keycode" value="<?php echo(htmlspecialchars($kode, ENT_QUOTES, 'UTF-8')); ?>">
                            <table style="width:100%;border-collapse:collapse;" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="width:80px;vertical-align:bottom;padding:0;">
                                        <label class="mg-b-0 tx-semibold"><i class="fa fa-filter"></i> Filter</label>
                                    </td>
                                    <td style="width:20px;vertical-align:bottom;padding:0;"><div style="text-align:center;">:</div></td>
                                    <td style="padding:0;">
                                        <div class="row align-items-end">
                                            <div class="col-sm-5">
                                                <label class="mg-b-5 tx-semibold">Nomor Batch</label>
                                                <select name="batch" id="selectBatch" class="form-control select-batch-filter" style="width:100%">
                                                    <option value=""></option>
                                                    <?php foreach($batchOptions as $b): $sel = ($b['bcode_rpo'] == $batch) ? ' selected' : ''; ?>
                                                    <option value="<?php echo htmlspecialchars($b['bcode_rpo'], ENT_QUOTES); ?>"<?php echo $sel; ?>><?php echo htmlspecialchars($b['bcode_rpo'], ENT_QUOTES); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-sm-5">
                                                <label class="mg-b-5 tx-semibold">Outlet</label>
                                                <select name="mitra" id="selectMitra" class="form-control select-mitra-filter" style="width:100%">
                                                    <option value=""></option>
                                                    <?php foreach($mitraOptions as $m): $sel = ($m['mitra_rpo'] == $mitra) ? ' selected' : ''; ?>
                                                    <option value="<?php echo htmlspecialchars($m['mitra_rpo'], ENT_QUOTES); ?>"<?php echo $sel; ?>><?php echo htmlspecialchars($m['mitra_rpo'], ENT_QUOTES); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-sm-2">
                                                <button type="submit" class="btn btn-primary btn-sm mg-r-5"><i class="fa fa-search"></i> Filter</button>
                                                <?php if($batch != '' || $mitra != '') { ?>
                                                <a class="btn btn-secondary btn-sm" href="<?php echo("$sistem/sistem.php?menu=vproduk&keycode=".urlencode($kode)); ?>"><i class="fa fa-times"></i> Reset</a>
                                                <?php } ?>
                                                <a target="_blank" href="<?php echo($xlsUrl); ?>" title="Download Excel" class="btn btn-success btn-sm mg-t-5"><i class="fa fa-download"></i> Excel</a>
                                                <?php if($batch != '') { ?>
                                                <div class="mg-t-5"><span class="badge badge-pill badge-primary"><i class="fa fa-tag"></i> <?php echo(htmlspecialchars($batch, ENT_QUOTES)); ?></span></div>
                                                <?php } ?>
                                                <?php if($mitra != '') { ?>
                                                <div class="mg-t-5"><span class="badge badge-pill badge-info"><i class="fa fa-store"></i> <?php echo(htmlspecialchars($mitra, ENT_QUOTES)); ?></span></div>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </form>
                    </div>
                </div>
                <script>
                $(function(){
                    setTimeout(function(){
                        if($('#selectBatch').data('select2')) { $('#selectBatch').select2('destroy'); }
                        $('#selectBatch').select2({
                            placeholder: '-- Semua Batch --',
                            allowClear: true,
                            width: '100%'
                        });
                        if($('#selectMitra').data('select2')) { $('#selectMitra').select2('destroy'); }
                        $('#selectMitra').select2({
                            placeholder: '-- Semua Outlet --',
                            allowClear: true,
                            width: '100%'
                        });
                        var curBatch = <?php echo json_encode($batch); ?>;
                        if(curBatch !== '') {
                            $('#selectBatch').val(curBatch).trigger('change');
                        }
                        var curMitra = <?php echo json_encode($mitra); ?>;
                        if(curMitra !== '') {
                            $('#selectMitra').val(curMitra).trigger('change');
                        }
                    }, 0);
                });
                </script>
            	<div class="table-responsive">
				<table class="table table-bordered">
                	<thead>
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
                    <tbody>
                    <?php
						$in		= 0;
						$out	= 0;
						$tin	= 0;
						$tout	= 0;
                        $no		= 1;
                        $where = "WHERE id_pro=:kode";
                        if($batch != '') { $where .= " AND bcode_rpo=:batch"; }
                        if($mitra != '') { $where .= " AND mitra_rpo=:mitra"; }
                        $master	= $conn->prepare("SELECT jenis_rpo, bcode_rpo, mitra_rpo, kode_rpo, gudang, faktur_rpo, tgl_rpo, jumlah_rpo FROM report_produk $where ORDER BY tgl_rpo ASC");
                        $master->bindParam(':kode', $kode, PDO::PARAM_STR);
                        if($batch != '') { $master->bindParam(':batch', $batch, PDO::PARAM_STR); }
                        if($mitra != '') { $master->bindParam(':mitra', $mitra, PDO::PARAM_STR); }
						$master->execute();
						while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
							$in		= ($hasil['jenis_rpo']=='Order' || $hasil['jenis_rpo']=='TF-IN'|| $hasil['jenis_rpo']=='IN-Konsinyasi' || $hasil['jenis_rpo']=='Transfer-StockCancel') ? $hasil['jumlah_rpo'] : 0;
							
							$out	= ($hasil['jenis_rpo']=='Sales' || $hasil['jenis_rpo']=='TF-OUT'|| $hasil['jenis_rpo']=='Donasi' || $hasil['jenis_rpo']=='Pinjaman' || $hasil['jenis_rpo']== 'Retur' || $hasil['jenis_rpo']== 'Lain-Lain'|| $hasil['jenis_rpo']== 'Retur-P' || $hasil['jenis_rpo']== 'Konsinyasi') ? $hasil['jumlah_rpo'] : 0;
							
							$tin	+= $in;
							$tout	+= $out;
					?>
                    	<tr>
                        	<td><center><?php echo($no); ?></center></td>
                        	<td><?php echo($hasil['jenis_rpo']); ?></td>
                        	<td><?php echo($hasil['mitra_rpo']); ?></td>
                        	<td><?php echo($hasil['kode_rpo']); ?></td>
                        	<td><?php echo($hasil['faktur_rpo']); ?></td>
                        	<td><?php echo($hasil['tgl_rpo']); ?></td>
                        	<td><?php echo($hasil['bcode_rpo']); ?></td>
                        	<td><?php echo($hasil['gudang']); ?></td>
                        	<td><div align="right"><?php echo($data->angka($in)); ?></div></td>
                        	<td><div align="right"><?php echo($data->angka($out)); ?></div></td>
                        </tr>
                  	<?php $no++; } ?>
                    	<tr>
                    		<th></th>
                        	<th colspan="7"><div align="right">TOTAL</div></th>
                        	<th><div align="right"><?php echo($data->angka($tin)); ?></div></th>
                        	<th><div align="right"><?php echo($data->angka($tout)); ?></div></th>
                        </tr>
                    	<tr>
                    		<th></th>
                        	<th colspan="7"><div align="right">BALANCE</div></th>
                        	<th colspan="2"><div align="center"><?php echo($data->angka($tin - $tout)); ?></div></th>
                        </tr>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <div class="row row-sm">
            <div class="col-sm-12">
                <a href="<?php echo("$sistem/produk"); ?>" title="Kembai"><button type="button" class="btn btn-secondary btn-xs"><i class="fa fa-chevron-circle-left"></i> Kembali</button></a>
            </div>
		</div>
    </div>
</div>