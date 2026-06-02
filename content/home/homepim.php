<?php
    $admin = $secu->injection(@$_COOKIE['adminkuy']);
    $menu	= $secu->injection(@$_GET['menudata']);
    $tahun	= date('Y');
	$tiga	= date("Y", strtotime("-2 Year", strtotime($tahun)));
	$tanggal= date('Y-m-d');
	$bulan1	= date('Y-m');
	$bulan2	= date("Y-m", strtotime("-1 Month", strtotime($bulan1)));
	$sumary	= $conn->query("SELECT SUM(total_tfk) AS total FROM transaksi_faktur_pim WHERE YEAR(tgl_tfk)='$tahun'")->fetch(PDO::FETCH_ASSOC);
	 $viewsl	= $conn->query("SELECT COUNT(B.id_pro) AS total FROM(SELECT id_pro, SUM(sisa_psd) AS jumlah FROM produk_stokdetail GROUP BY id_pro) AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro LEFT JOIN kategori_produk AS C ON B.id_kpr=C.id_kpr LEFT JOIN satuan_produk AS D ON B.id_spr=D.id_spr WHERE A.jumlah AND B.nama_p ='MP0000000003'")->fetch(PDO::FETCH_ASSOC);
	$viewse	= $conn->query("SELECT COUNT(B.id_pro) AS total FROM(SELECT id_pro, SUM(sisa_psd) AS jumlah FROM produk_stokdetail GROUP BY id_pro) AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro LEFT JOIN kategori_produk AS C ON B.id_kpr=C.id_kpr LEFT JOIN satuan_produk AS D ON B.id_spr=D.id_spr WHERE A.jumlah<=B.minstok_pro AND B.nama_p ='MP0000000003'")->fetch(PDO::FETCH_ASSOC);
	$viewed	= $conn->query("SELECT COUNT(id_psd) AS total FROM produk_stokdetail WHERE TIMESTAMPDIFF(DAY, '$tanggal', tgl_expired)<=".$data->sistem('limit_expired')." OR TIMESTAMPDIFF(DAY, tgl_expired, '$tanggal')>0")->fetch(PDO::FETCH_ASSOC);
	$viewin	= $conn->query("SELECT COUNT(id_tre) AS total FROM transaksi_receive WHERE status_tre!='Lunas' AND (TIMESTAMPDIFF(DAY, '$tanggal', tgl_limit)<=".$data->sistem('limit_supplier')." OR TIMESTAMPDIFF(DAY, tgl_limit, '$tanggal')>0)")->fetch(PDO::FETCH_ASSOC);
	$viewout= $conn->query("SELECT COUNT(id_tfk) AS total FROM transaksi_faktur WHERE status_tfk!='Lunas' AND (TIMESTAMPDIFF(DAY, '$tanggal', tgl_limit)<=".$data->sistem('limit_outlet')." OR TIMESTAMPDIFF(DAY, tgl_limit, '$tanggal')>0)")->fetch(PDO::FETCH_ASSOC);
	$viewsiz= $conn->query("SELECT COUNT(A.id_out) AS total FROM outlet AS A INNER JOIN outlet_legal AS B ON A.id_out=B.id_out INNER JOIN kategori_legal AS C ON B.id_klg=C.id_klg WHERE C.id_klg='KLG01' AND TIMESTAMPDIFF(MONTH, '$tanggal', B.expired_ole)<=C.parameter_klg")->fetch(PDO::FETCH_ASSOC);
	$viewspa= $conn->query("SELECT COUNT(A.id_out) AS total FROM outlet AS A INNER JOIN outlet_legal AS B ON A.id_out=B.id_out INNER JOIN kategori_legal AS C ON B.id_klg=C.id_klg WHERE C.id_klg='KLG02' AND TIMESTAMPDIFF(MONTH, '$tanggal', B.expired_ole)<=C.parameter_klg")->fetch(PDO::FETCH_ASSOC);
    $viewni= $conn->query("SELECT COUNT(id_pro) AS total FROM produk WHERE TIMESTAMPDIFF(DAY, '$tanggal', tgl_nie)<=".$data->sistem('limit_expired')." OR TIMESTAMPDIFF(DAY, tgl_nie, '$tanggal')>0 AND nama_p= 'MP0000000003'")->fetch(PDO::FETCH_ASSOC);

?>
<div class="content-header d-flex justify-content-between align-items-center">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Dashboard PIM</li>
            </ol>
        </nav>
        <h4 class="content-title">Dashboard PIM</h4>
    </div>
    <div>
         <a href="<?php echo($data->sistem('url_sis').'/home'); ?>" class="btn btn-secondary" style="width: 150px;">Dashboard Cendo</a>
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
        	<div class="alert alert-primary"><strong>Top 10 Outlet - Volume Penjualan PIM</strong></div>
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
							$master	= $conn->prepare("SELECT C.nama_out, C.total FROM(SELECT B.nama_out, SUM(A.total_tfk) AS total FROM transaksi_faktur_pim AS A LEFT JOIN outlet AS B ON A.id_out=B.id_out WHERE LEFT(tgl_tfk, 7)=:bulan2 GROUP BY B.id_out) AS C ORDER BY C.total DESC LIMIT 10");
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
							$master	= $conn->prepare("SELECT C.nama_out, C.total FROM(SELECT B.nama_out, SUM(A.total_tfk) AS total FROM transaksi_faktur_pim AS A LEFT JOIN outlet AS B ON A.id_out=B.id_out WHERE LEFT(tgl_tfk, 7)=:bulan1 GROUP BY B.id_out) AS C ORDER BY C.total DESC LIMIT 10");
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
							$master	= $conn->prepare("SELECT C.ofcode_out, C.total FROM(SELECT B.ofcode_out, SUM(A.subtot_tfk) AS total FROM transaksi_faktur_pim AS A LEFT JOIN outlet AS B ON A.id_out=B.id_out WHERE LEFT(tgl_tfk, 7)=:bulan2 GROUP BY B.ofcode_out) AS C ORDER BY C.total DESC LIMIT 10 ");
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
							$master	= $conn->prepare("SELECT C.ofcode_out, C.total FROM(SELECT B.ofcode_out, SUM(A.subtot_tfk) AS total FROM transaksi_faktur_pim AS A LEFT JOIN outlet AS B ON A.id_out=B.id_out WHERE LEFT(tgl_tfk, 7)=:bulan1 GROUP BY B.ofcode_out) AS C ORDER BY C.total DESC LIMIT 10");
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
							$master	= $conn->prepare("SELECT A.id_pro, C.nama_pro, C.kode_pro, SUM(A.jumlah_tfd) AS total, D.nama_spr, E.nama_kpr FROM transaksi_fakturdetail_pim AS A LEFT JOIN transaksi_faktur_pim AS B ON A.id_tfk=B.id_tfk LEFT JOIN produk AS C ON A.id_pro=C.id_pro LEFT JOIN satuan_produk AS D ON C.id_spr=D.id_spr LEFT JOIN kategori_produk AS E ON C.id_kpr=E.id_kpr WHERE LEFT(B.tgl_tfk, 7)=:bulan2 GROUP BY A.id_pro ORDER BY SUM(A.jumlah_tfd) DESC LIMIT 10");
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
							$master	= $conn->prepare("SELECT A.id_pro, C.nama_pro, C.kode_pro, SUM(A.jumlah_tfd) AS total, D.nama_spr, E.nama_kpr FROM transaksi_fakturdetail_pim AS A LEFT JOIN transaksi_faktur_pim AS B ON A.id_tfk=B.id_tfk LEFT JOIN produk AS C ON A.id_pro=C.id_pro LEFT JOIN satuan_produk AS D ON C.id_spr=D.id_spr LEFT JOIN kategori_produk AS E ON C.id_kpr=E.id_kpr WHERE LEFT(B.tgl_tfk, 7)=:bulan1 GROUP BY A.id_pro ORDER BY SUM(A.jumlah_tfd) DESC LIMIT 10");
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
		text: '<?php echo("GRAFIK TRANSAKSI PENJUALAN PIM"); ?>',
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
					$jual	= $data->jumlahjualpim($tiga, $vbulan['id_mbu']) * 1;
					echo("$jual,");
			}
		?>
		]
	},
	<?php $tiga++; } ?>
	]
});
</script>