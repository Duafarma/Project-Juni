<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Inventory</a></li>
                <li class="breadcrumb-item active" aria-current="page">Transfer Stok</li>
            </ol>
        </nav>
        <h4 class="content-title">Inventory - Transfer Stok</h4>
    </div>
</div>
<?php
	$kode	= $secu->injection($_GET['keycode']);
	$q 		= "SELECT
					tt.id_ttr,
					tt.kode_ttr,
					tt.tipe_ttr,
					a2.nama_apl AS apl_from,
					a3.nama_apl AS apl_to,
					tt.ket_ttr,
					tt.tgl_ttr,
					tt.status_ttr
				FROM
					transaksi_transferstock tt
				LEFT JOIN aplikasi a2 ON
					tt.id_app_from = a2.id_apl
				LEFT JOIN aplikasi a3 ON
					tt.id_app_to = a3.id_apl
				WHERE
					tt.id_ttr =:kode
				LIMIT 1";
	$read	= $conn->prepare($q);
	$read->bindParam(':kode', $kode, PDO::PARAM_STR);
	$read->execute();
	$view	= $read->fetch(PDO::FETCH_ASSOC);
	switch ($view['status_ttr']) {
		case 'Waiting':
			$badgeColor = "warning";
			break;

		case 'Process':
			$badgeColor = "primary";
			break;

		case 'Approved':
			$badgeColor = "success";
			break;

		case 'Rejected':
			$badgeColor = "danger";
			break;
		
		default:
			$badgeColor = "secondary";
			break;
	}
	if ($view['status_ttr'] == 'Waiting') {
		$badgeColor = "warning";
	}
?>
<div class="content-body">
    <div class="component-section no-code">
		<div style="margin-top:10px; margin-bottom:25px;">
			<div class="row row-sm">
				<div class="col-sm-4 text-right">
					<?php if ($view['status_ttr'] == 'Waiting') { ?>
					<a href="#modal1" id="btnApprove" onclick="approvalTransferStokModal('transferstok','<?php echo $view['id_ttr']; ?>','1')" class="btn btn-success btn-xs" data-toggle="modal"><i class="fa fa-check"></i> APPROVE</a>
					<a href="#modal1" id="btnReject" onclick="approvalTransferStokModal('transferstok','<?php echo $view['id_ttr']; ?>','0')" class="btn btn-danger btn-xs" data-toggle="modal"><i class="fa fa-times"></i> REJECT</a>
					<?php } ?>
				</div>
			</div>
        </div>
		<div class="row row-sm">
            <div class="col-sm-3">
                <label>Kode <span class="tx-danger">*</span></label>
                <input type="text" class="form-control" value="<?php echo($view['kode_ttr']); ?>" placeholder="-" required="required" disabled/>
            </div>
            <div class="col-sm-3">
                <label>Tipe Transfer <span class="tx-danger">*</span></label>
				<input type="text" class="form-control" value="<?=($view['tipe_ttr'] == 'IN') ? 'Masuk' : 'Keluar'?>" placeholder="-" required="required" disabled/>
            </div>
            <div class="col-sm-3">
                <label>Tanggal <span class="tx-danger">*</span></label>
                <input type="text" class="form-control" value="<?php echo($view['tgl_ttr']); ?>" placeholder="9999-99-99" required="required" readonly/>
            </div>
			<div class="col-sm-3">
                <label>Status</label>
                <h6><span class="badge badge-<?=$badgeColor?>"><?=$view['status_ttr']?></span></h6>
            </div>
        </div>
        <div class="row row-sm">
            <div class="col-sm-3">
                <label>Asal <span class="tx-danger">*</span></label>
				<input type="text" class="form-control" value="<?php echo($view['apl_from']); ?>" placeholder="-" required="required" disabled/>
            </div>
            <div class="col-sm-3">
                <label>Tujuan <span class="tx-danger">*</span></label>
				<input type="text" class="form-control" value="<?php echo($view['apl_to']); ?>" placeholder="-" required="required" disabled/>
            </div>
            <div class="col-sm-6">
                <label>Keterangan</label>
                <input type="text" class="form-control" placeholder="Ketik keterangan di sini..." value="<?php echo($view['ket_ttr']); ?>" disabled/>
            </div>
        </div><!-- row -->
        <div class="row row-sm">
            <div class="col-sm-12">
                <div class="clearfix mg-t-15 mg-b-15"></div>
            	<div class="table-responsive">
				<table class="table table-bordered">
                	<thead>
                    	<tr>
                            <th>Product</th>
                            <th>Detail</th>
                            <th>Batchcode</th>
							<th>Expired Date</th>
                            <th>Jumlah <?=($view['tipe_ttr'] == 'IN') ? 'Masuk' : 'Keluar'?></th>
                            <th>Satuan Qty.</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
						$id_ttr = $view['id_ttr'];
						$qMaster = "SELECT
										c.id_pro,
										c.kode_pro,
										c.nama_pro,
										d.nama_kpr,
										c.berat_pro,
										d.satuan_kpr,
										b.tgl_expired,
										b.no_bcode,
										a.jumlah_ttd,
										e.nama_spr
									FROM
										transaksi_transferstockdetail a
									LEFT JOIN produk_stokdetail b ON
										a.id_psd = b.id_psd
									LEFT JOIN produk c ON
										b.id_pro = c.id_pro
									LEFT JOIN kategori_produk d ON
										c.id_kpr = d.id_kpr
									LEFT JOIN satuan_produk AS e ON
										c.id_spr = e.id_spr
									WHERE
										a.id_ttr =:id_ttr";
						$master	= $conn->prepare($qMaster);
						$master->bindParam(':id_ttr', $id_ttr, PDO::PARAM_STR);
						$master->execute();
						while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
							$proddetail	= $hasil['nama_kpr']." (".$hasil['berat_pro']." ".$hasil['satuan_kpr'].")";
					?>
                    	<tr>
                        	<td><?php echo("(".$hasil['kode_pro'].") ".$hasil['nama_pro']); ?></td>
                        	<td><?php echo($proddetail); ?></td>
                        	<td><?php echo($hasil['no_bcode']); ?></td>
							<td><?php echo($hasil['tgl_expired']); ?></td>
                        	<td><?php echo($hasil['jumlah_ttd']); ?></td>
							<td><?php echo($hasil['nama_kpr']); ?></td>
                        </tr>
                  	<?php
                    	}
					?>
                    </tbody>
                </table>
                </BR>
                 <table width="100%">
                	<tr>
                    	<td width="30%">Penerimaan Pesanan</td>
                    	<td width="40%"></td>
                    	<td width="30%"><div style="text-transform:capitalize;"><?php echo(strtolower($data->sistem('pt_sis'))); ?></div></td>
                    </tr>
                	<tr>
                    	<td>Penanggung Jawab</td>
                    	<td></td>
                    	<td>Penanggung Jawab</td>
                    </tr>
                	<tr>
                    	<td height="60"></td>
        				<td></td>
        				<td><img src="<?php echo("../../../berkas/sistem/".$data->sistem('cap_apoteker')); ?>" width="65%" /></td>
                    </tr>
                	<tr>
                    	<td>(....................)</td>
                    	<td></td>
                    	<td>(....................)</td>
                    </tr>
                	<tr>
                    	<td>SIPA NO</td>
                    	<td></td>
                    	<td>SIPA NO</td>
                    </tr>
                </table>
            </div>
        </div>
		<?php require_once('config/frame/alert.php'); ?>
        <div class="row row-sm">
            <div class="col-sm-12">
                <a href="<?php echo("$sistem/transferstok"); ?>" title="Kembai"><button type="button" class="btn btn-secondary btn-xs"><i class="fa fa-chevron-circle-left"></i> Kembali</button></a>
				<div id="imgloading"></div>
            </div>
		</div>
		</form>
    </div>
</div>