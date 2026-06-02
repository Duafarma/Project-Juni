<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Menu</a></li>
                <li class="breadcrumb-item"><a href="#">Grafik</a></li>
                <li class="breadcrumb-item active" aria-current="page">Penjualan</li>
            </ol>
        </nav>
        <h4 class="content-title">Penjualan</h4>
    </div>
</div>
<?php
	$cari	= $secu->injection(@$_GET['cari']);
	$pecah	= explode('_', $cari);
	$tahun1	= empty($pecah[0]) ? date('Y') : $pecah[0];
	$tahun2	= empty($pecah[1]) ? date('Y') : $pecah[1];
	$gabung	= $tahun1."_".$tahun2;
?>
<input type="hidden" name="caridata" id="caridata" value="<?php echo($cari); ?>" readonly="readonly" />
<input type="hidden" name="halaman" id="halaman" value="1" readonly="readonly" />
<input type="hidden" name="maximal" id="maximal" value="15" readonly="readonly" />
<div class="content-body">
	<div class="row mg-b-10">
        <div class="col-sm-6">
			<a href="#modal1" onclick="<?php echo("caridata('caritahun', 'gpenjualan', '$gabung')"); ?>" data-toggle="modal"><button class="btn btn-warning btn-pill btn-xs"><i class="fa fa-search"></i> Cari Data</button></a>
			<a href="<?php echo("$sistem/gpenjualan"); ?>" title="Refresh"><button class="btn btn-info btn-pill btn-xs"><i class="fa fa-spinner"></i> Refresh</button></a>
        	<?php echo(($data->akses($admin, $menu, 'A.read_status')==='Active') ? '<a target="_blank" href="'.$sistem.'/laporan/xps/gpenjualan/gpenjualan.php?key='.$gabung.'" title="XPS"><button class="btn btn-danger btn-pill btn-xs"><i class="fa fa-print"></i> XPS</button></a>' : ''); ?>
        </div>
        <div class="col-sm-6">
			<span class="badge badge-pill badge-danger"><i class="fa fa-search"></i> <?php echo("Tahun : $tahun1 - $tahun2"); ?></span>
        </div>
    </div>
    <div class="row row-sm">
        <div class="col-sm-12">
            <div id="container" style="min-width: 400px; height: 400px; margin: 0 auto"></div>
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
		text: '<?php echo("SEPANJANG TAHUN $tahun1 - $tahun2"); ?>',
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
	<?php while($tahun1<=$tahun2){ ?>
	{ 
		name: '<?php echo($tahun1); ?>',
		
		data: [
		<?php
			$rbulan			= $conn->prepare("SELECT id_mbu FROM master_bulan ORDER BY id_mbu ASC");
			$rbulan->execute();
			while($vbulan	= $rbulan->fetch(PDO::FETCH_ASSOC)){
					$jual	= $data->jumlahjual($tahun1, $vbulan['id_mbu']) * 1;
					echo("$jual,");
			}
		?>
		]
	},
	<?php $tahun1++; } ?>
	]
});
</script>