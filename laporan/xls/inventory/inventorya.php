<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
	header("Content-Type: application/vnd.ms-excel");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: 0"); 
	header("Content-Disposition: attachment; filename=inventory.xls");

	require_once('../../../config/connection/connection.php');
	require_once('../../../config/connection/security.php');
	require_once('../../../config/function/data.php');
	require_once('../../../config/function/date.php');

	$secu = new Security;
	$base = new DB;
	$data = new Data;
	$date = new Date;
	$admin = $secu->injection(@$_COOKIE['adminkuy']);
	$kunci = $secu->injection(@$_COOKIE['kuncikuy']);

	if (!$secu->validadmin($admin, $kunci)) {
		header('Location: ' . $data->sistem('url_sis') . '/signout');
		exit;
	}

	try {
		$conn = $base->open();
		$cari = $secu->injection(@$_GET['key']);
		$search = $data->cekcari($cari, '-', ' ');
		$active = 'Active';

		$query = "
			SELECT A.jumlah, B.nama_pro
			FROM (
				SELECT id_pro, SUM(sisa_psd) AS jumlah
				FROM produk_stokdetail
				GROUP BY id_pro
			) AS A
			LEFT JOIN produk AS B ON A.id_pro = B.id_pro
			WHERE A.jumlah IS NOT NULL
		";

		$master = $conn->prepare($query);
		$master->execute();
	} catch (PDOException $e) {
		echo "Error: " . $e->getMessage();
		exit;
	}
?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Inventory</title>
    </head>

    <body>
		<table>
        	<tr>
            	<th colspan="10">INVENTORY ANALISIS</th>
            </tr>
            <tr>
            	<td colspan="10"></td>
            </tr>
        </table>
    	<table border="1">
        	<thead>
            	<tr>
                    <th><center>#</center></th>
                    <th>NAMA PRODUK</th>
                    <th><div align="right">KUANTITAS</div></th>
				</tr>
    		</thead>
            <tbody>
			<?php
				$nomor = 1;
				while ($hasil = $master->fetch(PDO::FETCH_ASSOC)) {
			?>
                <tr>
                    <td><center><?php echo($nomor); ?></center></td>
                    <td><?php echo($hasil['nama_pro']); ?></td>
                    <td><div align="right"><?php echo($hasil['jumlah']); ?></div></td>
                </tr>
			<?php
				$nomor++;
            	}
			?>
            </tbody>
        </table>
    </body>
</html>
