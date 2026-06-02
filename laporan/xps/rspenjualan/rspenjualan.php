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
	$admin	= $secu->injection(@$_COOKIE['adminkuy']);
	$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
	$secu->validadmin($admin, $kunci);
	if($secu->validadmin($admin, $kunci)==false){ header('location:'.$data->sistem('url_sis').'/signout'); } else {
	$conn	= $base->open();
	$cari	= $secu->injection(@$_GET['key']);
	$pecah	= explode('_', $cari);
	$outlet	= empty($pecah[0]) ? "id_out!=''" : "id_out IN('".str_replace("-", "', '", "$pecah[0]")."')"; 
?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Report Summary Penjualan</title>
        <link href="<?php echo($data->sistem('url_sis')."/config/css/laporan.css"); ?>" rel="stylesheet">
		<style type="text/css" media="print">
          @page { size: portrait; }
        </style>
    </head>
    <body>
    	<h4>REPORT SUMMARY PENJUALAN</h4>
        <p>Periode : <?php echo("$pecah[1] - $pecah[2]"); ?></p>
    	<table class="tabel">
            <thead>
                <tr>
                    <th><center>#</center></th>
                    <th><div align="left">Outlet</div></th>
				<?php
					if(!empty($pecah[1]) && !empty($pecah[2])){
                    $awal	= strtotime($pecah[1]);
                    $akhir	= strtotime($pecah[2]);
                    while($awal<=$akhir){
						$bulan	= date('Y-m', $awal);
				?>
					<th><div align="right"><?php echo($bulan); ?></div></th>                
                <?php
                        $awal	= strtotime("+1 month", $awal);
                    }
					}
                ?>
                </tr>
            </thead>
            <tbody>
            <?php
				if(empty($pecah[1]) or empty($pecah[2])){
					echo('<tr><td colspan="2">Pilih outlet dan tanggal...</td></tr>');
				} else {
				$nomor		= 1;
				$master		= $conn->prepare("SELECT id_out, nama_out FROM outlet WHERE $outlet ORDER BY nama_out ASC");
				$master->execute();
				while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			?>
				<tr>
                	<td><center><?php echo($nomor); ?></center></td>
                    <td><?php echo($hasil['nama_out']); ?></td>
				<?php
					$awal	= empty($pecah[1]) ? "" : strtotime($pecah[1]); 
					$akhir	= empty($pecah[2]) ? "" : strtotime($pecah[2]); 
					while($awal<=$akhir){
						$bulan	= date('Y-m', $awal);
						$total	= $data->outletjual($hasil['id_out'], $bulan);
				?>
						<td><div align="right"><?php echo($data->angka($total)); ?></div></td>
				<?php $awal	= strtotime("+1 month", $awal); } ?>
				</tr>
			<?php $nomor++; } } ?>
            </tbody>
        </table>
        <?php $conn	= $base->close(); ?>
		<script type="text/javascript">window.print();</script>
    </body>
<?php } ?>
</html>
