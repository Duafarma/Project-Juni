<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
	header("Content-Type: application/force-download");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Laporan");
	header("content-disposition:attachment; filename=historis-produk-filter.xls");
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
	if($secu->validadmin($admin, $kunci)==false){ header('location:'.$data->sistem('url_sis').'/signout'); exit; }
	$conn	= $base->open();

	$produk	= $secu->injection(@$_GET['produk']);
	$bulan	= $secu->injection(@$_GET['bulan']);
	$tahun	= $secu->injection(@$_GET['tahun']);

	if($bulan !== '' && (!is_numeric($bulan) || $bulan < 1 || $bulan > 12)) $bulan = '';
	if($tahun !== '' && (!is_numeric($tahun) || strlen($tahun) !== 4)) $tahun = '';

	$bulanNames = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

	// Jalankan stored procedure jika ada filter produk
	if($produk !== '') {
		$stmt_sp = $conn->query("CALL reportproduk('".addslashes($produk)."')");
		if($stmt_sp) {
			do { $stmt_sp->fetchAll(); } while($stmt_sp->nextRowset());
			$stmt_sp->closeCursor();
		}
	}

	// Build query
	$where  = [];
	$params = [];
	if($produk !== '') {
		$where[]  = 'id_pro = :produk';
		$params[':produk'] = $produk;
	}
	if($bulan !== '') {
		$where[]  = 'MONTH(tgl_rpo) = :bulan';
		$params[':bulan'] = (int)$bulan;
	}
	if($tahun !== '') {
		$where[]  = 'YEAR(tgl_rpo) = :tahun';
		$params[':tahun'] = (int)$tahun;
	}

	if(empty($where)) {
		$sql  = "SELECT jenis_rpo, bcode_rpo, mitra_rpo, kode_rpo, gudang, faktur_rpo, tgl_rpo, jumlah_rpo FROM report_produk ORDER BY tgl_rpo ASC LIMIT 500";
		$master = $conn->query($sql);
	} else {
		$sql = "SELECT jenis_rpo, bcode_rpo, mitra_rpo, kode_rpo, gudang, faktur_rpo, tgl_rpo, jumlah_rpo
				FROM report_produk
				WHERE " . implode(' AND ', $where) . "
				ORDER BY tgl_rpo ASC";
		$master = $conn->prepare($sql);
		foreach($params as $key => $val) {
			$type = is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR;
			$master->bindValue($key, $val, $type);
		}
		$master->execute();
	}

	$jenis_in  = ['Order','TF-IN','IN-Konsinyasi','Transfer-StockCancel'];
	$jenis_out = ['Sales','TF-OUT','Donasi','Pinjaman','Retur','Lain-Lain','Retur-P','Konsinyasi'];

	// Stok Awal H-1
	$tgl_h1   = null;
	$stok_awal = 0;
	if($bulan !== '' && $tahun !== '') {
		$tgl_h1 = date('Y-m-d', strtotime($tahun.'-'.str_pad($bulan, 2, '0', STR_PAD_LEFT).'-01 -1 day'));
	} elseif($tahun !== '') {
		$tgl_h1 = ($tahun - 1).'-12-31';
	}
	if($tgl_h1 !== null) {
		$so_produk_cond = '';
		$so_params = [':tgl_h1' => $tgl_h1];
		if($produk !== '') {
			$so_produk_cond      = 'AND TRIM(id_pro) = TRIM(:so_produk)';
			$so_params[':so_produk'] = $produk;
		}
		$so_sql = "SELECT COALESCE(SUM(qty_so), 0) as stok_awal
		           FROM so
		           WHERE DATE(created_at) = :tgl_h1
		           $so_produk_cond";
		$so_stmt = $conn->prepare($so_sql);
		foreach($so_params as $k => $v) $so_stmt->bindValue($k, $v, PDO::PARAM_STR);
		$so_stmt->execute();
		$so_row    = $so_stmt->fetch(PDO::FETCH_ASSOC);
		$stok_awal = (int)$so_row['stok_awal'];
	}
?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Historis Produk</title>
    </head>
    <body>
		<table>
        	<tr>
            	<th colspan="10" style="background-color:#1a7a3c;color:#ffffff;font-size:14pt;">HISTORIS PRODUK</th>
            </tr>
        	<tr>
            	<th colspan="10" style="background-color:#1a7a3c;color:#ffffff;"><?php echo($data->sistem('pt_sis')); ?></th>
            </tr>
            <tr><td colspan="10"></td></tr>
        </table>
		<table>
			<tr>
            	<td><b>Produk</b></td>
            	<td>:</td>
            	<td><?php echo($produk ? $data->produk($produk, 'nama_pro') : 'Semua Produk'); ?></td>
            </tr>
			<tr>
            	<td><b>Bulan</b></td>
            	<td>:</td>
            	<td><?php echo($bulan ? $bulanNames[(int)$bulan] : 'Semua'); ?></td>
            </tr>
			<tr>
            	<td><b>Tahun</b></td>
            	<td>:</td>
            	<td><?php echo($tahun ? $tahun : 'Semua'); ?></td>
            </tr>
            <tr><td colspan="3"></td></tr>
        </table>
    	<table border="1">
            <thead>
                <tr style="background-color:#217346;color:#ffffff;">
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
				$tin  = 0;
				$tout = 0;
				$no   = 1;
				// Baris Stock Awal (H-1 bulan filter)
				if($tgl_h1 !== null):
					$tin += $stok_awal;
			?>
                <tr style="background-color:#e2efda;">
                    <td><center>1</center></td>
                    <td><b>Stock Awal</b></td>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                    <td><?php echo $tgl_h1; ?></td>
                    <td>-</td>
                    <td>-</td>
                    <td><div align="right"><?php echo $data->angka($stok_awal); ?></div></td>
                    <td><div align="right">0</div></td>
                </tr>
            <?php $no++; endif; ?>
            <?php
				while($hasil = $master->fetch(PDO::FETCH_ASSOC)){
					$in  = in_array($hasil['jenis_rpo'], $jenis_in)  ? (int)$hasil['jumlah_rpo'] : 0;
					$out = in_array($hasil['jenis_rpo'], $jenis_out) ? (int)$hasil['jumlah_rpo'] : 0;
					if($in == 0 && $out == 0) continue;
					$tin  += $in;
					$tout += $out;
			?>
                <tr>
                    <td><center><?php echo $no; ?></center></td>
                    <td><?php echo htmlspecialchars($hasil['jenis_rpo']); ?></td>
                    <td><?php echo htmlspecialchars($hasil['mitra_rpo']); ?></td>
                    <td><?php echo htmlspecialchars($hasil['kode_rpo']); ?></td>
                    <td><?php echo htmlspecialchars($hasil['faktur_rpo']); ?></td>
                    <td><?php echo htmlspecialchars($hasil['tgl_rpo']); ?></td>
                    <td><?php echo htmlspecialchars($hasil['bcode_rpo']); ?></td>
                    <td><?php echo htmlspecialchars($hasil['gudang']); ?></td>
                    <td><div align="right"><?php echo $data->angka($in); ?></div></td>
                    <td><div align="right"><?php echo $data->angka($out); ?></div></td>
                </tr>
            <?php $no++; } ?>
                <tr style="background-color:#c6efce;font-weight:bold;">
                    <th colspan="8"><div align="right">TOTAL</div></th>
                    <th><div align="right"><?php echo $data->angka($tin); ?></div></th>
                    <th><div align="right"><?php echo $data->angka($tout); ?></div></th>
                </tr>
                <tr style="background-color:#a9d18e;font-weight:bold;">
                    <th colspan="8"><div align="right">BALANCE</div></th>
                    <th colspan="2"><div align="center"><?php echo $data->angka($tin - $tout); ?></div></th>
                </tr>
            </tbody>
        </table>
    </body>
</html>
