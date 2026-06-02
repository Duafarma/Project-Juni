<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Menu</a></li>
                <li class="breadcrumb-item"><a href="#">Master Data</a></li>
                <li class="breadcrumb-item active" aria-current="page">Dokumen Belum Balik </li>
            </ol>
        </nav>
        <h4 class="content-title">Dokumen Belum Kembali </h4>
    </div>
</div>
<?php
	$kode	= $secu->injection($_GET['keycode']);
?>

    
<div class="content-body">
    <div class="row mg-b-10">
        <div class="col-sm-6">
            <!-- <a target="_blank" href="<?php echo($data->sistem('url_sis')."/laporan/xls/outlet/profitmargin.php?key=$cari"); ?>" title=".XLS"><button class="btn btn-success btn-pill btn-xs"><i class="fa fa-print"></i> .XLS</button></a> -->
            <a href="<?php echo("$sistem/alurdokumen"); ?>" title="Kembai"><button type="button" class="btn btn-secondary btn-xs"><i class="fa fa-chevron-circle-left"></i> Kembali</button></a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mg-b-0">
            <thead>
                <tr>
                    <th><center>#</center></th>
                    <th><center>Nama Outlet </center></th>
                    <th><center>Nomor Faktur</center></th>
                    <th><center>Tgl Faktur</center></th>
                    <th><center>Kirim</center></th>
                    <th><center>Dokumen Kembali</center></th>
                    <th><center>Dokumen Filing</center></th>
                    <th><center>Input PPN</center></th>
                    <th><center>Upload PPN</center></th>
                    <th><center>Pemberkasan</center></th>
                    <th><center>TF</center></th>
                    <th><center>Pembayaran</center></th>
                </tr>
            </thead>
            <tbody>
            <?php
				$nomor	= 1;
				$qMaster = "SELECT
                                A.id_tfk,
                                A.tgl_tfk,
                                A.kode_tfk,
                                A.total_tfk,
                                A.status_dokumen,
                                A.id_out,
                                B.nama_out,
                                C.created_at,
                                D.tgl_tfkkb,
                                E.updated_at,
                                F.tanggal_i_p,
                                H.tanggal_u_p,
                                J.tanggal_faktur,
                                K.tgl_tfkkf,
                                L.tgl_pfk
                            FROM
                                transaksi_faktur AS A
					        LEFT JOIN outlet AS B ON
                                A.id_out = B.id_out
                            LEFT JOIN dokumen_balik_detail AS C ON 
                                A.id_tfk = C.no_faktur
                            LEFT JOIN transaksi_faktur_kirim_b AS D ON
                                A.id_tfk = D.id_tfk
                            LEFT JOIN dokumen_failing_detail AS E ON 
                                A.id_tfk = E.no_faktur 
                            LEFT JOIN faktur_pajak AS F ON
                                A.id_tfk = F.id_tfk
                            LEFT JOIN upload_f_pajak_detail AS G ON
                                A.id_tfk = G.no_faktur
                            LEFT JOIN upload_f_pajak AS H ON
                                G.id_tfb = H.id_tfb
                            LEFT JOIN finance_detail AS I ON
                                A.id_tfk = I.no_kwitansi
                            LEFT JOIN finance AS J ON 
                                I.id_finance = J.id_finance
                            LEFT JOIN transaksi_faktur_kirim_f AS K ON
                                A.id_tfk = K.id_tfk
                            LEFT JOIN pembayaran_faktur AS L ON
                                A.id_tfk = L.id_tfk
                            WHERE
                                 A.tgl_tfk=:kode AND A.status_dokumen='belum balik'";
                $master	= $conn->prepare($qMaster);
                $master->bindParam(':kode', $kode, PDO::PARAM_STR);
                $master->execute();
				while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			?>
				<tr>
                	<td><center><?php echo($nomor); ?></center></td>
                    <td><center><?php echo($hasil['nama_out']); ?></center></td>
                    <td><center><?php echo($hasil['kode_tfk']); ?></center></td>
                    <td><center><?php echo($data->tgldd($hasil['tgl_tfk'])); ?></center></td>
                    <td><center><?php echo($hasil['tgl_tfkkb']); ?></center></td>
                    <td><center><?php echo($hasil['created_at']); ?></center></td>
                    <td><center><?php echo($hasil['updated_at']); ?></center></td>
                    <td><center><?php echo($hasil['tanggal_i_p']); ?></center></td>
                    <td><center><?php echo($hasil['tanggal_u_p']); ?></center></td>
                    <td><center><?php echo($hasil['tanggal_faktur']); ?></center></td>
                    <td><center><?php echo($hasil['tgl_tfkkf']); ?></center></td>
                    <td><center><?php echo($hasil['tgl_pfk']); ?></center></td>
				</tr>
			<?php $nomor++; } ?>
            </tbody>
        </table>
        </table>

    </div>
</div>