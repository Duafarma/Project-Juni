<?php
    $admin = $secu->injection(@$_COOKIE['adminkuy']);
    $role = $secu->injection(@$_COOKIE['jeniskuy']);
    $menu	= $secu->injection(@$_GET['menudata']);
    $tahun	            = date('Y');
	$tiga	            = date("Y", strtotime("-2 Year", strtotime($tahun)));
	$tanggal            = date('Y-m-d');
	$bulan1	            = date('Y-m');
	$bulan2	            = date("Y-m", strtotime("-1 Month", strtotime($bulan1)));
	$viewout            = $conn->query("SELECT COUNT(id_tfk) AS total FROM transaksi_faktur WHERE status_tfk!='Lunas' AND (TIMESTAMPDIFF(DAY, '$tanggal', tgl_limit)<=".$data->sistem('limit_outlet')." OR TIMESTAMPDIFF(DAY, tgl_limit, '$tanggal')>0)")->fetch(PDO::FETCH_ASSOC);
    $tfb                = $conn->query("SELECT count(*) AS jumlah FROM transaksi_faktur WHERE status_tfkkf='Belum Dikirim' AND tgl_tfk=CURRENT_DATE()")->fetch(PDO::FETCH_ASSOC);
    $tfs                = $conn->query("SELECT count(*) AS jumlah FROM transaksi_faktur WHERE status_tfkkf='Sudah Dikirim'")->fetch(PDO::FETCH_ASSOC);
    $balik              = $conn->query("SELECT count(*) AS jumlah FROM transaksi_faktur WHERE tgl_tfk AND status_dokumen='belum balik' AND tgl_tfk >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch(PDO::FETCH_ASSOC);
    $failing            = $conn->query("SELECT count(*) AS jumlah FROM transaksi_faktur WHERE status_failing='belum failing' AND tgl_tfk >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch(PDO::FETCH_ASSOC);
    $fakturpajak        = $conn->query("SELECT count(*) AS jumlah FROM transaksi_faktur WHERE status_f_pajak='belum terbit' AND tgl_tfk >= DATE_SUB(NOW(), INTERVAL 120 DAY)")->fetch(PDO::FETCH_ASSOC);
    $uploadppn          = $conn->query("SELECT count(*) AS jumlah FROM transaksi_faktur WHERE upload_f_pajak='belum' AND tgl_tfk >= DATE_SUB(NOW(), INTERVAL 60 DAY)")->fetch(PDO::FETCH_ASSOC);
    $dokumen            = $conn->query("SELECT count(*) AS jumlah FROM transaksi_faktur WHERE status_dokumen='belum balik' AND tgl_tfk >= DATE_SUB(NOW(), INTERVAL 120 DAY)")->fetch(PDO::FETCH_ASSOC);
    $dokumentasi        = $conn->query("SELECT count(*) AS jumlah FROM transaksi_faktur WHERE status_dokumentasi='sudah siap' AND tgl_tfk >= DATE_SUB(NOW(), INTERVAL 120 DAY)")->fetch(PDO::FETCH_ASSOC);


?>
<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
            </ol>
        </nav>
        <h4 class="content-title">Dashboard AR</h4>
    </div>
</div>

<div class="content-body">
    <div class="row row-sm">
		<div class="col-sm-12">
        	<div id="tampilih"></div>
        </div>
    </div>
    <div class="row">
        <div class="form-group col-sm-4">
        	<a onclick="tampilin('balik')">
            <div class="card card-hover card-social-one">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mg-b-10">
                        <h1 class="card-value"><?php echo($balik['jumlah']); ?></h1>
                        <div class="chart-wrapper">
                        </div>
                    </div>
                    <h5 class="card-title tx-danger">Dokumen Belum Kembali</h5>
                    <p class="card-desc">Daftar Faktur Pengiriman Barang yang Belum Kembali Ke Kantor.</p>
                </div>
            </div>
			</a>
        </div>
        <div class="form-group col-sm-4">
        	<a onclick="tampilin('failing')">
            <div class="card card-hover card-social-one">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mg-b-10">
                        <h1 class="card-value"><?php echo($failing['jumlah']); ?></h1>
                        <div class="chart-wrapper">
                        </div>
                    </div>
                    <h5 class="card-title tx-danger">Dokumen Belum Filing</h5>
                    <p class="card-desc">Daftar Faktur Pengiriman Barang yang Belum Filing.</p>
                </div>
            </div>
			</a>
        </div>
        <div class="form-group col-sm-4">
        	<a onclick="tampilin('fakturpajak')">
            <div class="card card-hover card-social-one">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mg-b-10">
                        <h1 class="card-value"><?php echo($fakturpajak['jumlah']); ?></h1>
                        <div class="chart-wrapper">
                        </div>
                    </div>
                    <h5 class="card-title tx-danger">Belum Input PPN</h5>
                    <p class="card-desc">Daftar Faktur Penjualan Belum Input PPN .</p>
                </div>
            </div>
			</a>
        </div>
        <div class="form-group col-sm-4">
        	<a onclick="tampilin('uploadppn')">
            <div class="card card-hover card-social-one">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mg-b-10">
                        <h1 class="card-value"><?php echo($uploadppn['jumlah']); ?></h1>
                        <div class="chart-wrapper">
                        </div>
                    </div>
                    <h5 class="card-title tx-danger">Belum Upload PPN</h5>
                    <p class="card-desc">Daftar Faktur Pajak Yang Belum Upload PPN.</p>
                </div>
            </div>
			</a>
        </div>
        <div class="form-group col-sm-4">
        	<a onclick="tampilin('dokumentasi')">
            <div class="card card-hover card-social-one">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mg-b-10">
                        <h1 class="card-value"><?php echo($dokumentasi['jumlah']); ?></h1>
                        <div class="chart-wrapper">
                        </div>
                    </div>
                    <h5 class="card-title tx-danger">Siap Tukar Faktur</h5>
                    <p class="card-desc">Daftar Faktur yang Sudah Selesai Pemeberkasan.</p>
                </div>
            </div>
			</a>
        </div>
        <div class="form-group col-sm-4">
        	<a onclick="tampilin('tfb')">
            <div class="card card-hover card-social-one">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mg-b-10">
                        <h1 class="card-value"><?php echo($tfb['jumlah']); ?></h1>
                        <div class="chart-wrapper">
                        </div>
                    </div>
                    <h5 class="card-title tx-danger">Belum Tukar Faktur</h5>
                    <p class="card-desc">Daftar Faktur yang Belum Tertukar Faktur.</p>
                </div>
            </div>
			</a>
        </div>
        <div class="form-group col-sm-4">
        	<a onclick="tampilin('tfs')">
            <div class="card card-hover card-social-one">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mg-b-10">
                        <h1 class="card-value"><?php echo($tfs['jumlah']); ?></h1>
                        <div class="chart-wrapper">
                        </div>
                    </div>
                    <h5 class="card-title tx-danger">Sudah Tukar Faktur</h5>
                    <p class="card-desc">Daftar Faktur yang Sudah Tertukar Faktur.</p>
                </div>
            </div>
			</a>
        </div>
      
        <div class="form-group col-sm-4">
        	<a onclick="tampilin('dokumen')">
            <div class="card card-hover card-social-one">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mg-b-10">
                        <h1 class="card-value"><?php echo($dokumen['jumlah']); ?></h1>
                        <div class="chart-wrapper">
                        </div>
                    </div>
                    <h5 class="card-title tx-danger">Berkas Tanda Terima Tuker Faktur</h5>
                    <p class="card-desc">Daftar Tanda Terima Tuker Faktur Yang Belum Balik Ke kantor.</p>
                </div>
            </div>
			</a>
        </div>
        <div class="form-group col-sm-4">
        	<a onclick="tampilin('tagihanoutlet')">
            <div class="card card-hover card-social-one">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mg-b-10">
                        <h1 class="card-value"><?php echo($viewout['total']); ?></h1>
                        <div class="chart-wrapper">
                        </div>
                    </div>
                    <h5 class="card-title tx-teal">Tagihan Outlet</h5>
                    <p class="card-desc">Jumlah transaksi yang harus dibayar oleh outlet.</p>
                </div>
            </div>
            </a>
        </div>
    </div>
    <div class="row mg-t-20">
        <div class="form-group col-sm-12">
        	<h3><div class="alert alert-primary"><strong><center>Jadwal Tukar Faktur</center> </strong>
            <div><strong><center><?php echo($date->getHari(date('Y-m-d')).', '.$date->tgl_indo(date('Y-m-d'))); ?> </center></strong></h3>

        
        </div>
            <!-- <div><strong> </strong></div> -->

        </div>
        <div class="form-group col-sm-12">
            <div class="block">
                <div class="block-header block-header-default">
                    <!-- <h5 class="block-title">Bulan Lalu</h5> -->
                    <div class="block-options">
                        
                    </div>
                </div>
                <div class="block-content">
                    <table class="table table-striped table-vcenter">
                    	<thead>
                        	<tr>
                            	<th>No.</th>
                            	<th>Nama Outlet</th>
                            	<th>Nomor faktur</th>
                            	<th>Tgl Faktur</th>
                                <th>Jadwal TF</th>
                                <th>Keterangan</th>
                                <th>Kurir</th>
                            </tr>
                        </thead>
                        <tbody>
						<?php
							$nomor	= 1;
							$qMaster = "SELECT
                                D.kode_tfk,
                                C.no_kwitansi,
                                C.created_at,
                                B.nama_out,
                                A.tanggal_faktur,
                                D.tgl_tfk,
                                G.nama_adm
                            FROM
                                finance AS A
					        LEFT JOIN outlet AS B ON
                                A.nama_outlet = B.id_out
                            LEFT JOIN finance_detail AS C ON
                                A.id_finance = C.id_finance
                            LEFT JOIN transaksi_faktur AS D ON
                                C.no_kwitansi = D.id_tfk
                            LEFT JOIN transaksi_faktur_kirim_f AS E ON
                                D.id_tfk=E.id_tfk
                             LEFT JOIN adminz AS G ON
                                E.id_adm=G.id_adm
                            WHERE
                                 DATE(A.tanggal_faktur) = CURDATE() ORDER BY D.tgl_tfk ASC";
                            $master	= $conn->prepare($qMaster);
                            // $master->bindParam(':kode', $kode, PDO::PARAM_STR);
                            $master->execute();
							while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
						?>
                        	<tr>
                            	<td><?php echo($nomor); ?></td>
                            	<td><?php echo($hasil['nama_out']); ?></td>
                            	<td><?php echo($hasil['kode_tfk']); ?></td>
                                <td><?php echo($hasil['tgl_tfk']); ?></td>
                            	<td><?php echo($date->getHari(date('Y-m-d')).', '.$date->tgl_indo(date('Y-m-d'))); ?></td>
                                <td></td>
                            	<td><?php echo($hasil['nama_adm']); ?></td>
                            </tr>
						<?php $nomor++; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
		</div>
	</div>
    <?php /*
    <div class="row mg-t-20">
        <div class="col-sm-6">
            <div class="block">
                <div class="block-header block-header-default">
                    <h5 class="block-title">Legal Outlet</h5>
                    <div class="block-options">
                        <div class="block-options-item">
                            <code><?php echo($date->getBulan(substr($bulan1, 5, 2))." ".substr($bulan1, 0, 4)); ?></code>
                        </div>
                    </div>
                </div>
                <div class="block-content">
                    <table class="table table-striped table-vcenter">
                    	<thead>
                        	<tr>
                            	<th>No.</th>
                            	<th>Legal</th>
                            	<th>Parameter</th>
                            	<th>Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
						<?php
							$nomor	= 1;
							$master	= $conn->prepare("SELECT C.id_klg, C.nama_klg, C.parameter_klg, COUNT(C.id_ole) AS total FROM(SELECT A.id_ole, B.id_klg, B.nama_klg, B.parameter_klg, B.notif_klg, TIMESTAMPDIFF(MONTH, :tanggal, A.expired_ole) AS selisih FROM outlet_legal AS A LEFT JOIN kategori_legal AS B ON A.id_klg=B.id_klg) AS C WHERE C.notif_klg='Active' AND C.selisih<=C.parameter_klg GROUP BY C.id_klg ORDER BY C.nama_klg");
							$master->bindParam(':tanggal', $tanggal, PDO::PARAM_STR);
							$master->execute();
							while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
						?>
                        	<tr>
                            	<td><?php echo($nomor); ?></td>
                            	<td><?php echo($hasil['nama_klg']); ?></td>
                            	<td><?php echo($hasil['parameter_klg']); ?> Bulan</td>
                            	<td><a onclick="<?php echo("notiflegal('$hasil[id_klg]', 'legal')"); ?>" title="Lihat Outlet"><span class="badge badge-danger"><?php echo($hasil['total']); ?></span></a></td>
                            </tr>
						<?php $nomor++; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
		</div>
        <div class="col-sm-6">
            <div class="block">
                <div class="block-header block-header-default">
                    <h5 class="block-title">Daftar Outlet</h5>
                    <div class="block-options">
                        <div class="block-options-item" id="namalegal">
                            <code>Nama Legal</code>
                        </div>
                    </div>
                </div>
                <div class="block-content">
                    <table class="table table-striped table-vcenter">
                    	<thead>
                        	<tr>
                            	<th><center>No.</center></th>
                            	<th>Outlet</th>
                            	<th>Tgl. Expired</th>
                            	<th>Selisih</th>
                            </tr>
                        </thead>
                        <tbody id="outletlegal">
                        	<tr><td colspan="4">Pilih Legal</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
		</div>
	</div>
	*/ ?>
  
    <!-- <div class="row mg-t-20">
        <div class="form-group col-sm-12">
        	<div class="alert alert-primary"><strong>Top 10 Outlet - Volume Penjualan</strong></div>
        </div>
        <div class="form-group col-sm-6">
            <div class="block">
                <div class="block-header block-header-default">
                    <h5 class="block-title">Bulan Lalu</h5>
                    <div class="block-options">
                        <div class="block-options-item">
                            <code><?php echo($date->getBulan(substr($bulan2, 5, 2))." ".substr($bulan2, 0, 4)); ?></code>
                        </div>
                    </div>
                </div>
                <div class="block-content">
                    <table class="table table-striped table-vcenter">
                    	<thead>
                        	<tr>
                            	<th>No.</th>
                            	<th>Outlet</th>
                            	<th>Pembelian</th>
                            </tr>
                        </thead>
                        <tbody>
						<?php
							$nomor	= 1;
							$master	= $conn->prepare("SELECT C.nama_out, C.total FROM(SELECT B.nama_out, SUM(A.total_tfk) AS total FROM transaksi_faktur AS A LEFT JOIN outlet AS B ON A.id_out=B.id_out WHERE LEFT(tgl_tfk, 7)=:bulan2 GROUP BY B.id_out) AS C ORDER BY C.total ");
							$master->bindParam(':bulan2', $bulan2, PDO::PARAM_STR);
							$master->execute();
							while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
						?>
                        	<tr>
                            	<td><?php echo($nomor); ?></td>
                            	<td><?php echo($hasil['nama_out']); ?></td>
                            	<td><?php echo($data->angka($hasil['total'])); ?></td>
                            </tr>
						<?php $nomor++; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
		</div>
        <div class="form-group col-sm-6">
            <div class="block">
                <div class="block-header block-header-default">
                    <h5 class="block-title">Bulan Berjalan</h5>
                    <div class="block-options">
                        <div class="block-options-item">
                            <code><?php echo($date->getBulan(substr($bulan1, 5, 2))." ".substr($bulan1, 0, 4)); ?></code>
                        </div>
                    </div>
                </div>
                <div class="block-content">
                    <table class="table table-striped table-vcenter">
                    	<thead>
                        	<tr>
                            	<th>No.</th>
                            	<th>Outlet</th>
                            	<th>Pembelian</th>
                            </tr>
                        </thead>
                        <tbody>
						<?php
							$nomor	= 1;
							$master	= $conn->prepare("SELECT C.nama_out, C.total FROM(SELECT B.nama_out, SUM(A.total_tfk) AS total FROM transaksi_faktur AS A LEFT JOIN outlet AS B ON A.id_out=B.id_out WHERE LEFT(tgl_tfk, 7)=:bulan1 GROUP BY B.id_out) AS C ORDER BY C.total ");
							$master->bindParam(':bulan1', $bulan1, PDO::PARAM_STR);
							$master->execute();
							while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
						?>
                        	<tr>
                            	<td><?php echo($nomor); ?></td>
                            	<td><?php echo($hasil['nama_out']); ?></td>
                            	<td><?php echo($data->angka($hasil['total'])); ?></td>
                            </tr>
						<?php $nomor++; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
		</div>
	</div> -->
	<!-- <div class="row mg-t-20">
        <div class="form-group col-sm-12">
        	<div class="alert alert-warning"><strong>Penjualan Sales</strong></div>
        </div>
        <div class="form-group col-sm-6">
            <div class="block">
                <div class="block-header block-header-default">
                    <h5 class="block-title">Bulan Lalu</h5>
                    <div class="block-options">
                        <div class="block-options-item">
                            <code><?php echo($date->getBulan(substr($bulan2, 5, 2))." ".substr($bulan2, 0, 4)); ?></code>
                        </div>
                    </div>
                </div>
                <div class="block-content">
                    <table class="table table-striped table-vcenter">
                    	<thead>
                        	<tr>
                            	<th>No.</th>
                            	<th>Nama Sales</th>
                            	<th>Penjualan</th>
                            </tr>
                        </thead>
                        <tbody>
						<?php
							$nomor	= 1;
							$master	= $conn->prepare("SELECT C.ofcode_out, C.total FROM(SELECT B.ofcode_out, SUM(A.subtot_tfk) AS total FROM transaksi_faktur AS A LEFT JOIN outlet AS B ON A.id_out=B.id_out WHERE LEFT(tgl_tfk, 7)=:bulan2 GROUP BY B.ofcode_out) AS C ORDER BY C.total DESC LIMIT 10 ");
							$master->bindParam(':bulan2', $bulan2, PDO::PARAM_STR);
							$master->execute();
							while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
						?>
                        	<tr>
                            	<td><?php echo($nomor); ?></td>
                            	<td><?php echo($hasil['ofcode_out']); ?></td>
                            	<td><?php echo($data->angka($hasil['total'])); ?></td>
                            </tr>
						<?php $nomor++; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
		</div>
        <div class="form-group col-sm-6">
            <div class="block">
                <div class="block-header block-header-default">
                    <h5 class="block-title">Bulan Berjalan</h5>
                    <div class="block-options">
                        <div class="block-options-item">
                            <code><?php echo($date->getBulan(substr($bulan1, 5, 2))." ".substr($bulan1, 0, 4)); ?></code>
                        </div>
                    </div>
                </div>
                <div class="block-content">
                    <table class="table table-striped table-vcenter">
                    	<thead>
                        	<tr>
                            	<th>No.</th>
                            	<th>Nama Sales</th>
                            	<th>Penjualan</th>
                            </tr>
                        </thead>
                        <tbody>
						<?php
							$nomor	= 1;
							$master	= $conn->prepare("SELECT C.ofcode_out, C.total FROM(SELECT B.ofcode_out, SUM(A.subtot_tfk) AS total FROM transaksi_faktur AS A LEFT JOIN outlet AS B ON A.id_out=B.id_out WHERE LEFT(tgl_tfk, 7)=:bulan1 GROUP BY B.ofcode_out) AS C ORDER BY C.total DESC LIMIT 10");
							$master->bindParam(':bulan1', $bulan1, PDO::PARAM_STR);
							$master->execute();
							while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
						?>
                        	<tr>
                            	<td><?php echo($nomor); ?></td>
                            	<td><?php echo($hasil['ofcode_out']); ?></td>
                            	<td><?php echo($data->angka($hasil['total'])); ?></td>
                            </tr>
						<?php $nomor++; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
		</div>
	</div> -->
    <!-- <div class="row mg-t-20">
        <div class="form-group col-sm-12">
        	<div class="alert alert-danger"><strong>Top 10 Produk - Kuantitas Penjualan</strong></div>
        </div>
        <div class="form-group col-sm-6">
            <div class="block">
                <div class="block-header block-header-default">
                    <h5 class="block-title">Bulan Lalu</h5>
                    <div class="block-options">
                        <div class="block-options-item">
                            <code><?php echo($date->getBulan(substr($bulan2, 5, 2))." ".substr($bulan2, 0, 4)); ?></code>
                        </div>
                    </div>
                </div>
                <div class="block-content">
                    <table class="table table-striped table-vcenter">
                    	<thead>
                        	<tr>
                            	<th>No.</th>
                            	<th>Nama</th>
                            	<th>Kode</th>
                            	<th>Kategori</th>
                            	<th>Satuan</th>
                            	<th>Penjualan</th>
                            </tr>
                        </thead>
                        <tbody>
						<?php
							$nomor	= 1;
							$master	= $conn->prepare("SELECT A.id_pro, C.nama_pro, C.kode_pro, SUM(A.jumlah_tfd) AS total, D.nama_spr, E.nama_kpr FROM transaksi_fakturdetail AS A LEFT JOIN transaksi_faktur AS B ON A.id_tfk=B.id_tfk LEFT JOIN produk AS C ON A.id_pro=C.id_pro LEFT JOIN satuan_produk AS D ON C.id_spr=D.id_spr LEFT JOIN kategori_produk AS E ON C.id_kpr=E.id_kpr WHERE LEFT(B.tgl_tfk, 7)=:bulan2 GROUP BY A.id_pro ORDER BY SUM(A.jumlah_tfd) DESC LIMIT 10");
							$master->bindParam(':bulan2', $bulan2, PDO::PARAM_STR);
							$master->execute();
							while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
						?>
                        	<tr>
                            	<td><?php echo($nomor); ?></td>
                            	<td><?php echo($hasil['nama_pro']); ?></td>
								<td><?php echo($hasil['kode_pro']); ?></td>
								<td><?php echo($hasil['nama_kpr']); ?></td>
								<td><?php echo($hasil['nama_spr']); ?></td>
                            	<td><?php echo($data->angka($hasil['total'])); ?></td>
                            </tr>
						<?php $nomor++; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
		</div>
        <div class="form-group col-sm-6">
            <div class="block">
                <div class="block-header block-header-default">
                    <h5 class="block-title">Bulan Berjalan</h5>
                    <div class="block-options">
                        <div class="block-options-item">
                            <code><?php echo($date->getBulan(substr($bulan1, 5, 2))." ".substr($bulan1, 0, 4)); ?></code>
                        </div>
                    </div>
                </div>
                <div class="block-content">
                    <table class="table table-striped table-vcenter">
                    	<thead>
                        	<tr>
                            	<th>No.</th>
                            	<th>Nama</th>
                            	<th>Kode</th>
                            	<th>Kategori</th>
                            	<th>Satuan</th>
                            	<th>Penjualan</th>
                            </tr>
                        </thead>
                        <tbody>
						<?php
							$nomor	= 1;
							$master	= $conn->prepare("SELECT A.id_pro, C.nama_pro, C.kode_pro, SUM(A.jumlah_tfd) AS total, D.nama_spr, E.nama_kpr FROM transaksi_fakturdetail AS A LEFT JOIN transaksi_faktur AS B ON A.id_tfk=B.id_tfk LEFT JOIN produk AS C ON A.id_pro=C.id_pro LEFT JOIN satuan_produk AS D ON C.id_spr=D.id_spr LEFT JOIN kategori_produk AS E ON C.id_kpr=E.id_kpr WHERE LEFT(B.tgl_tfk, 7)=:bulan1 GROUP BY A.id_pro ORDER BY SUM(A.jumlah_tfd) DESC LIMIT 10");
							$master->bindParam(':bulan1', $bulan1, PDO::PARAM_STR);
							$master->execute();
							while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
						?>
                        	<tr>
                            	<td><?php echo($nomor); ?></td>
                            	<td><?php echo($hasil['nama_pro']); ?></td>
								<td><?php echo($hasil['kode_pro']); ?></td>
								<td><?php echo($hasil['nama_kpr']); ?></td>
								<td><?php echo($hasil['nama_spr']); ?></td>
                            	<td><?php echo($data->angka($hasil['total'])); ?></td>
                            </tr>
						<?php $nomor++; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
		</div>
	</div> -->
    <!-- <div class="row mg-t-20">
        <div class="form-group col-sm-12">
        	<div class="alert alert-danger"><strong>Total Faktur Terbentuk</strong></div>
        </div>
        <div class="form-group col-sm-6">
            <div class="block">
                <div class="block-header block-header-default">
                    <h5 class="block-title">Bulan Lalu</h5>
                    <div class="block-options">
                        <div class="block-options-item">
                            <code><?php echo($date->getBulan(substr($bulan2, 5, 2))." ".substr($bulan2, 0, 4)); ?></code>
                        </div>
                    </div>
                </div>
                <div class="block-content">
                    <table class="table table-striped table-vcenter">
                    	<thead>
                        	<tr>
                            	<th>No.</th>
                            	<th>Total Faktur Terbentuk</th>
                            	
                            </tr>
                        </thead>
                        <tbody>
						<?php
							$nomor	= 1;
                            $master	= $conn->query("SELECT count(*) AS jumlah FROM transaksi_faktur WHERE  MONTH(tgl_tfk) = MONTH(CURRENT_DATE()) AND YEAR(tgl_tfk) = YEAR(CURRENT_DATE()) ");
							// $master->bindParam(':bulan2', $bulan2, PDO::PARAM_STR);
							$master->execute();
							while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
						?>
                        	<tr>
                            	<td><?php echo($nomor); ?></td>
                            	<td><?php echo($hasil['jumlah']); ?></td>
							
                            </tr>
						<?php $nomor++; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
		</div>
        <div class="form-group col-sm-6">
            <div class="block">
                <div class="block-header block-header-default">
                    <h5 class="block-title">Bulan Berjalan</h5>
                    <div class="block-options">
                        <div class="block-options-item">
                            <code><?php echo($date->getBulan(substr($bulan1, 5, 2))." ".substr($bulan1, 0, 4)); ?></code>
                        </div>
                    </div>
                </div>
                <div class="block-content">
                    <table class="table table-striped table-vcenter">
                    	<thead>
                        <tr>
                            	<th>No.</th>
                            	<th>Total Faktur Terbentuk</th>
                            	
                            </tr>
                        </thead>
                        <tbody>
						<?php
							$nomor	= 1;
                            $master	= $conn->query("SELECT count(*) AS jumlah FROM transaksi_faktur WHERE  MONTH(tgl_tfk) = MONTH(CURRENT_DATE()) AND YEAR(tgl_tfk) = YEAR(CURRENT_DATE()) ");
							// $master->bindParam(':bulan1', $bulan1, PDO::PARAM_STR);
							$master->execute();
							while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
						?>
                        	<tr>
                            <td><?php echo($nomor); ?></td>
                            	<td><?php echo($hasil['jumlah']); ?></td>
                            </tr>
						<?php $nomor++; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
		</div>
	</div> -->
    
	
</div>
<script type="text/javascript" src="<?php echo("$sistem/highcart/js/jquery.min.js"); ?>"></script>
<script type="text/javascript" src="<?php echo("$sistem/highcart/js/highcharts.js"); ?>"></script>
<script type="text/javascript">
var chart = new Highcharts.Chart({
	chart: {
		renderTo: 'container', //letakan grafik di div id container
		//Type grafik, anda bisa ganti menjadi area,bar,column dan bar
		type: 'line',  
		marginRight: 130,
		marginBottom: 25
	},
	title: {
		text: '<?php echo("GRAFIK TRANSAKSI PENJUALAN"); ?>',
		x: -20 //center
	},
	subtitle: {
		text: '<?php echo("SEPANJANG TAHUN $tiga - $tahun"); ?>',
		x: -20
	},
	xAxis: { //X axis menampilkan data bulan 
		categories: ['Januari','Febuari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember']
	},
	yAxis: {
		title: {  //label yAxis
			text: 'Jumlah Penjualan'
		},
		plotLines: [{
			value: 0,
			width: 1,
			color: '#808080' //warna dari grafik line
		}]
	},
	tooltip: { 
	//fungsi tooltip, ini opsional, kegunaan dari fungsi ini 
	//akan menampikan data di titik tertentu di grafik saat mouseover
		formatter: function() {
				return '<b>'+ this.series.name +'</b><br/>'+
				this.x +': Rp. '+ titik(this.y) +',-';
		}
	},
	legend: {
		layout: 'vertical',
		align: 'right',
		verticalAlign: 'top',
		x: -10,
		y: 100,
		borderWidth: 0
	},
	//series adalah data yang akan dibuatkan grafiknya,

	series: [
	<?php while($tiga<=$tahun){ ?>
	{ 
		name: '<?php echo($tiga); ?>',
		
		data: [
		<?php
			$rbulan			= $conn->prepare("SELECT id_mbu FROM master_bulan ORDER BY id_mbu ASC");
			$rbulan->execute();
			while($vbulan	= $rbulan->fetch(PDO::FETCH_ASSOC)){
					$jual	= $data->jumlahjual($tiga, $vbulan['id_mbu']) * 1;
					echo("$jual,");
			}
		?>
		]
	},
	<?php $tiga++; } ?>
	]
});
</script>