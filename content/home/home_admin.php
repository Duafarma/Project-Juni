<?php
    $admin = $secu->injection(@$_COOKIE['adminkuy']);
    $menu	= $secu->injection(@$_GET['menudata']);
    $tahun	= date('Y');
	$tiga	= date("Y", strtotime("-2 Year", strtotime($tahun)));
	$tanggal= date('Y-m-d');
	$bulan1	= date('Y-m');
	$bulan2	= date("Y-m", strtotime("-1 Month", strtotime($bulan1)));
	$sumary	= $conn->query("SELECT SUM(total_tfk) AS total FROM transaksi_faktur WHERE YEAR(tgl_tfk)='$tahun'")->fetch(PDO::FETCH_ASSOC);
	$viewsl	= $conn->query("SELECT COUNT(B.id_pro) AS total FROM(SELECT id_pro, SUM(sisa_psd) AS jumlah FROM produk_stokdetail GROUP BY id_pro) AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro LEFT JOIN kategori_produk AS C ON B.id_kpr=C.id_kpr LEFT JOIN satuan_produk AS D ON B.id_spr=D.id_spr WHERE A.jumlah")->fetch(PDO::FETCH_ASSOC);
	$viewse	= $conn->query("SELECT COUNT(B.id_pro) AS total FROM(SELECT id_pro, SUM(sisa_psd) AS jumlah FROM produk_stokdetail GROUP BY id_pro) AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro LEFT JOIN kategori_produk AS C ON B.id_kpr=C.id_kpr LEFT JOIN satuan_produk AS D ON B.id_spr=D.id_spr WHERE A.jumlah<=B.minstok_pro")->fetch(PDO::FETCH_ASSOC);
	$viewed	= $conn->query("SELECT COUNT(id_psd) AS total FROM produk_stokdetail WHERE TIMESTAMPDIFF(DAY, '$tanggal', tgl_expired)<=".$data->sistem('limit_expired')." OR TIMESTAMPDIFF(DAY, tgl_expired, '$tanggal')>0")->fetch(PDO::FETCH_ASSOC);
	$viewin	= $conn->query("SELECT COUNT(id_tre) AS total FROM transaksi_receive WHERE status_tre!='Lunas' AND (TIMESTAMPDIFF(DAY, '$tanggal', tgl_limit)<=".$data->sistem('limit_supplier')." OR TIMESTAMPDIFF(DAY, tgl_limit, '$tanggal')>0)")->fetch(PDO::FETCH_ASSOC);
	$viewout= $conn->query("SELECT COUNT(id_tfk) AS total FROM transaksi_faktur WHERE status_tfk!='Lunas' AND (TIMESTAMPDIFF(DAY, '$tanggal', tgl_limit)<=".$data->sistem('limit_outlet')." OR TIMESTAMPDIFF(DAY, tgl_limit, '$tanggal')>0)")->fetch(PDO::FETCH_ASSOC);
	$viewsiz= $conn->query("SELECT COUNT(A.id_out) AS total FROM outlet AS A INNER JOIN outlet_legal AS B ON A.id_out=B.id_out INNER JOIN kategori_legal AS C ON B.id_klg=C.id_klg WHERE C.id_klg='KLG01' AND TIMESTAMPDIFF(MONTH, '$tanggal', B.expired_ole)<=C.parameter_klg")->fetch(PDO::FETCH_ASSOC);
	$viewspa= $conn->query("SELECT COUNT(A.id_out) AS total FROM outlet AS A INNER JOIN outlet_legal AS B ON A.id_out=B.id_out INNER JOIN kategori_legal AS C ON B.id_klg=C.id_klg WHERE C.id_klg='KLG02' AND TIMESTAMPDIFF(MONTH, '$tanggal', B.expired_ole)<=C.parameter_klg")->fetch(PDO::FETCH_ASSOC);
    $viewni= $conn->query("SELECT COUNT(id_pro) AS total FROM produk WHERE TIMESTAMPDIFF(DAY, '$tanggal', tgl_nie)<=".$data->sistem('limit_expired')." OR TIMESTAMPDIFF(DAY, tgl_nie, '$tanggal')>0")->fetch(PDO::FETCH_ASSOC);

?>

<style>
/* Quick Actions Menu Styling */
.quick-actions-card {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    border: none;
    border-radius: 10px;
    position: relative;
    z-index: 1;
}

.quick-actions-card .card-header {
    background: linear-gradient(135deg, #007bff, #0056b3);
    border-radius: 10px 10px 0 0;
    border: none;
}

.quick-actions-card .quick-action-btn {
    padding: 20px;
    height: 120px;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    transition: all 0.3s ease;
    text-decoration: none;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    background: #fff;
    position: relative;
    z-index: 2;
}

.quick-actions-card .quick-action-btn:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    text-decoration: none;
    border-color: #007bff;
}

.quick-actions-card .quick-action-btn.btn-outline-primary:hover {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

.quick-actions-card .quick-action-btn.btn-outline-success:hover {
    background: #28a745;
    color: white;
    border-color: #28a745;
}

.quick-actions-card .quick-action-btn.btn-outline-info:hover {
    background: #17a2b8;
    color: white;
    border-color: #17a2b8;
}

.quick-actions-card .quick-action-btn.btn-outline-warning:hover {
    background: #ffc107;
    color: white;
    border-color: #ffc107;
}

.quick-actions-card .quick-action-icon {
    font-size: 2rem;
    margin-bottom: 8px;
}

.quick-actions-card .quick-action-title {
    font-weight: bold;
    margin-bottom: 4px;
}

.quick-actions-card .quick-action-desc {
    font-size: 0.85rem;
    opacity: 0.8;
}

/* Ensure sidebar is clickable */
.nav-sidebar {
    position: relative;
    z-index: 999 !important;
}

.nav-sidebar .nav-link,
.nav-sidebar .nav-sub-link {
    position: relative;
    z-index: 1000 !important;
    pointer-events: auto !important;
}

.nav-sidebar .nav-link:hover,
.nav-sidebar .nav-sub-link:hover {
    cursor: pointer !important;
}

@media (max-width: 768px) {
    .quick-actions-card .quick-action-btn {
        height: 100px;
        padding: 15px;
    }
    
    .quick-actions-card .quick-action-icon {
        font-size: 1.5rem;
    }
}
</style>

<div class="content-header d-flex justify-content-between align-items-center">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Dashboard Cendo</li>
            </ol>
        </nav>
        <h4 class="content-title">Dashboard Cendo</h4>
    </div>
    <div>
         <a href="<?php echo($data->sistem('url_sis').'/homepim'); ?>" class="btn btn-secondary" style="width: 150px;">Dashboard PIM</a>
    </div>
</div>

<!-- Quick Actions Menu -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card quick-actions-card">
            <div class="card-header">
                <h6 class="card-title mb-0 text-white"><i class="fas fa-rocket"></i> Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                        <a href="fsales" class="quick-action-btn btn-outline-primary">
                            <i class="fas fa-shopping-cart quick-action-icon"></i>
                            <div class="quick-action-title">Faktur Penjualan</div>
                            <div class="quick-action-desc">Faktur Penjualan</div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                        <a href="inventory" class="quick-action-btn btn-outline-success">
                            <i class="fas fa-cubes quick-action-icon"></i>
                            <div class="quick-action-title">Inventory</div>
                            <div class="quick-action-desc">Stok Produk</div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                        <a href="outlet" class="quick-action-btn btn-outline-info">
                            <i class="fas fa-store-alt quick-action-icon"></i>
                            <div class="quick-action-title">Outlet</div>
                            <div class="quick-action-desc">Data Outlet</div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                        <a href="monitoringfi" class="quick-action-btn btn-outline-warning">
                            <i class="fas fa-chart-line quick-action-icon"></i>
                            <div class="quick-action-title">Monitoring AR</div>
                            <div class="quick-action-desc">Laporan Finance</div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
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
        	<a onclick="tampilin('expireddate')">
            <div class="card card-hover card-social-one">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mg-b-10">
                        <h1 class="card-value"><?php echo($viewed['total']); ?></h1>
                        <div class="chart-wrapper">
                        </div>
                    </div>
                    <h5 class="card-title tx-primary">Expired Date</h5>
                    <p class="card-desc">Daftar produk yang mendekatai tanggal expired.</p>
                </div>
            </div>
			</a>
        </div>
        <div class="form-group col-sm-4">
        	<a onclick="tampilin('stoklimapuluh')">
            <div class="card card-hover card-social-one">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mg-b-10">
                        <h1 class="card-value"><?php echo($viewse['total']); ?></h1>
                        <div class="chart-wrapper">
                        </div>
                    </div>
                    <h5 class="card-title tx-warning">Produk Stok Tipis</h5>
                    <p class="card-desc">Daftar produk yang memiliki jumlah stok kurang dari minimal.</p>
                </div>
            </div>
			</a>
        </div>
           <div class="form-group col-sm-4">
        	<a onclick="tampilin('stokfull')">
            <div class="card card-hover card-social-one">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mg-b-10">
                        <h1 class="card-value"><?php echo($viewsl['total']); ?></h1>
                        <div class="chart-wrapper">
                        </div>
                    </div>
                    <h5 class="card-title tx-green">Produk Stok Total Per Item</h5>
                    <p class="card-desc">Daftar produk yang memiliki jumlah stok Full Per Item.</p>
                </div>
            </div>
			</a>
        </div>
        <div class="form-group col-sm-4">
        	<a onclick="tampilin('tagihansupplier')">
            <div class="card card-hover card-social-one">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mg-b-10">
                        <h1 class="card-value"><?php echo($viewin['total']); ?></h1>
                        <div class="chart-wrapper">
                        </div>
                    </div>
                    <h5 class="card-title tx-pink">Tagihan Supplier</h5>
                    <p class="card-desc">Jumlah transaksi yang harus dibayar ke supplier.</p>
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
        <div class="form-group col-sm-4">
        	<a onclick="tampilin('suratizinoutlet')">
            <div class="card card-hover card-social-one">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mg-b-10">
                        <h1 class="card-value"><?php echo($viewsiz['total']); ?></h1>
                        <div class="chart-wrapper">
                        </div>
                    </div>
                    <h5 class="card-title tx-info">Surat Izin Outlet</h5>
                    <p class="card-desc">Jumlah outlet yang surat izinnya mendekati expired.</p>
                </div>
            </div>
            </a>
        </div>
        <div class="form-group col-sm-4">
        	<a onclick="tampilin('sipaoutlet')">
            <div class="card card-hover card-social-one">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mg-b-10">
                        <h1 class="card-value"><?php echo($viewspa['total']); ?></h1>
                        <div class="chart-wrapper">
                        </div>
                    </div>
                    <h5 class="card-title tx-success">SIPA Outlet</h5>
                    <p class="card-desc">Jumlah outlet yang SIPA-nya mendekati expired.</p>
                </div>
            </div>
            </a>
        </div>
         <div class="form-group col-sm-4">
        	<a onclick="tampilin('nie')">
            <div class="card card-hover card-social-one">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mg-b-10">
                        <h1 class="card-value"><?php echo($viewni['total']); ?></h1>
                        <div class="chart-wrapper">
                        </div>
                    </div>
                    <h5 class="card-title tx-danger">Expired Date NIE</h5>
                    <p class="card-desc">Daftar Nie produk yang mendekatai tanggal expired.</p>
                </div>
            </div>
			</a>
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
    <div class="row row-sm">
        <div class="col-sm-12">
            <div id="container" style="min-width: 400px; height: 400px; margin: 0 auto"></div>
        </div>
    </div>
    <div class="row mg-t-20">
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
							$master	= $conn->prepare("SELECT C.nama_out, C.total FROM(SELECT B.nama_out, SUM(A.total_tfk) AS total FROM transaksi_faktur AS A LEFT JOIN outlet AS B ON A.id_out=B.id_out WHERE LEFT(tgl_tfk, 7)=:bulan2 GROUP BY B.id_out) AS C ORDER BY C.total DESC LIMIT 10");
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
							$master	= $conn->prepare("SELECT C.nama_out, C.total FROM(SELECT B.nama_out, SUM(A.total_tfk) AS total FROM transaksi_faktur AS A LEFT JOIN outlet AS B ON A.id_out=B.id_out WHERE LEFT(tgl_tfk, 7)=:bulan1 GROUP BY B.id_out) AS C ORDER BY C.total DESC LIMIT 10");
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
	</div>
	<div class="row mg-t-20">
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
	</div>
    <div class="row mg-t-20">
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
	</div>
	 <div class="row mg-t-20">
        <div class="form-group col-sm-12">
        	<div class="alert alert-danger"><strong>Top 30 Produk - Kuantitas Penjualan</strong></div>
        </div>
        <div class="form-group col-sm-6">
            <div class="block">
                <div class="block-header block-header-default">
                    <h5 class="block-title"></h5>
                    <div class="block-options">
                        <div class="block-options-item">
                            <!--<code><?php echo($date->getBulan(substr($bulan2, 5, 2))." ".substr($bulan2, 0, 4)); ?></code>-->
                        </div>
                    </div>
                </div>
                <div class="block-content">
                    <table class="table table-striped table-vcenter">
                    	<thead>
                        	<tr>
                            	<th>No.</th>
                            	<th>Nama</th>
                            	<th>Kategori</th>
                            	<th>Stok</th>
                            </tr>
                        </thead>
                        <tbody>
						<?php
							$nomor	= 1;
							$master	= $conn->prepare("SELECT SUM(F.sisa_psd) AS jumlah, C.nama_pro, C.kode_pro, D.nama_spr, E.nama_kpr FROM produk AS C LEFT JOIN satuan_produk AS D ON C.id_spr=D.id_spr LEFT JOIN kategori_produk AS E ON C.id_kpr=E.id_kpr LEFT JOIN produk_stokdetail AS F ON C.id_pro=F.id_pro WHERE F.sisa_psd GROUP BY C.id_pro ORDER BY SUM(F.sisa_psd ) DESC LIMIT 30");
							// $master->bindParam(':bulan2', $bulan2, PDO::PARAM_STR);
							$master->execute();
							while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
						?>
                        	<tr>
                            	<td><?php echo($nomor); ?></td>
                            	<td><?php echo($hasil['nama_pro']); ?></td>
								<td><?php echo($hasil['nama_kpr']); ?></td>
                                <td><?php echo($data->angka($hasil['jumlah'])); ?></td>

                            </tr>
						<?php $nomor++; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
		</div>
       
	</div>
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

// Quick Actions Animation Enhancement
$(document).ready(function() {
    // Add entrance animation to quick action buttons only
    $('.quick-actions-card .quick-action-btn').each(function(index) {
        $(this).css('opacity', '0').css('transform', 'translateY(50px)');
        $(this).delay(index * 100).animate({
            opacity: 1
        }, 500).css('transform', 'translateY(0)');
    });
    
    // Add click ripple effect only to quick action buttons
    $('.quick-actions-card .quick-action-btn').on('click', function(e) {
        var ripple = $('<div class="ripple"></div>');
        var size = Math.max($(this).width(), $(this).height());
        var x = e.pageX - $(this).offset().left - size / 2;
        var y = e.pageY - $(this).offset().top - size / 2;
        
        ripple.css({
            width: size + 'px',
            height: size + 'px',
            left: x + 'px',
            top: y + 'px'
        });
        
        $(this).append(ripple);
        
        setTimeout(function() {
            ripple.remove();
        }, 600);
    });
    
    // Ensure sidebar navigation still works
    $('.nav-sidebar .nav-link, .nav-sub-link').off('click.quickActions');
});
</script>

<style>
.quick-actions-card .ripple {
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.6);
    transform: scale(0);
    animation: ripple-animation 0.6s linear;
    pointer-events: none;
}

@keyframes ripple-animation {
    to {
        transform: scale(4);
        opacity: 0;
    }
}

/* Ensure sidebar navigation is not affected */
.nav-sidebar .nav-link,
.nav-sidebar .nav-sub-link {
    pointer-events: auto !important;
    z-index: 1000;
}
</style>