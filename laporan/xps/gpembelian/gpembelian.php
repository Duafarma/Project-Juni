<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
	require_once('../../../config/connection/connection.php');
	require_once('../../../config/connection/security.php');
	require_once('../../../config/function/data.php');
	require_once('../../../config/function/date.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$date	= new Date;
	$sistem	= $data->sistem('url_sis');
	$tanggal= date('Y-m-d');
	$admin	= $secu->injection(@$_COOKIE['adminkuy']);
	$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
	$secu->validadmin($admin, $kunci);
	if($secu->validadmin($admin, $kunci)==false){ header('location:'.$data->sistem('url_sis').'/signout'); } else {
	$conn	= $base->open();
	$cari	= $secu->injection(@$_GET['key']);
	$pecah	= explode('_', $cari);
	$tahun1	= empty($pecah[0]) ? date('Y') : $pecah[0];
	$tahun2	= empty($pecah[1]) ? date('Y') : $pecah[1];
?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Report Penjualan</title>
        <link href="<?php echo("$sistem/config/css/laporan.css"); ?>" rel="stylesheet">
		<style type="text/css" media="print">
          @page { size: portrait; }
        </style>
    </head>


    <body>
    	<h4>GRAFIK PEMBELIAN</h4>
        <div id="container" style="width:100%; height: auto; margin: 0 auto"></div>
    	
		<?php $conn	= $base->open(); ?>
        <script type="text/javascript" src="<?php echo("$sistem/lib/jquery/jquery.min.js"); ?>"></script>
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
				text: '<?php echo("GRAFIK TRANSAKSI PEMBELIAN"); ?>',
				x: -20 //center
			},
			subtitle: {
				text: '<?php echo("SEPANJANG TAHUN $tahun1 - $tahun2"); ?>',
				x: -20
			},
			xAxis: { //X axis menampilkan data bulan 
				categories: ['Januari','Febuari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember']
			},
			yAxis: {
				title: {  //label yAxis
					text: 'Jumlah Pembelian'
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
			<?php while($tahun1<=$tahun2){ ?>
			{ 
				name: '<?php echo($tahun1); ?>',
				
				data: [
				<?php
					$rbulan			= $conn->prepare("SELECT id_mbu FROM master_bulan ORDER BY id_mbu ASC");
					$rbulan->execute();
					while($vbulan	= $rbulan->fetch(PDO::FETCH_ASSOC)){
							$jual	= $data->jumlahbeli($tahun1, $vbulan['id_mbu']) * 1;
							echo("$jual,");
					}
				?>
				]
			},
			<?php $tahun1++; } ?>
			]
		});
        </script>
    </body>
<?php } ?>
</html>
