<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Menu</a></li>
                <li class="breadcrumb-item"><a href="#">Master Data</a></li>
                <li class="breadcrumb-item active" aria-current="page">Pengiriman Barang </li>
            </ol>
        </nav>
        <h4 class="content-title">Belum Terkirim</h4>
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
                    <th><center>Nama Kurir</center></th>
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
                                A.status_tfkkb,
                                A.id_out,
                                B.nama_out,
                                D.nama_adm
                            FROM
                                transaksi_faktur AS A
					        LEFT JOIN outlet AS B ON
                                A.id_out = B.id_out
                            LEFT JOIN transaksi_faktur_kirim_b AS C ON
                                A.id_tfk = C.id_tfk
                            LEFT JOIN adminz AS D ON
                                C.id_adm = D.id_adm
                            
                            WHERE
                                 A.tgl_tfk=:kode AND A.status_tfkkb='Belum Dikirim'";
                $master	= $conn->prepare($qMaster);
                $master->bindParam(':kode', $kode, PDO::PARAM_STR);
                $master->execute();
				while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			?>
				<tr>
                	<td><center><?php echo($nomor); ?></center></td>
                    <td><center><?php echo($hasil['nama_out']); ?></center></td>
                    <td><center><?php echo($hasil['kode_tfk']); ?></center></td>
                    <td><center><?php echo($hasil['tgl_tfk']); ?></center></td>
                    <td><center><?php echo($hasil['nama_adm']); ?></center></td>
                    
				</tr>
			<?php $nomor++; } ?>
            </tbody>
        </table>
        </table>

    </div>
</div>