<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
    header("Content-Type: application/force-download");
    header("Cache-Control: no-cache, must-revalidate");
    header("Expires: Laporan"); 
    header("content-disposition:attachment; filename=tf.xls");
    require_once('../../../config/connection/connection.php');
    require_once('../../../config/connection/security.php');
    require_once('../../../config/function/data.php');
    require_once('../../../config/function/date.php');
    $secu    = new Security;
    $base    = new DB;
    $data    = new Data;
    $date    = new Date;
    $tanggal = date('Y-m-d');
    $cari = isset($_GET['caridata']) ? $secu->injection($_GET['caridata']) : '';
    $admin   = $secu->injection(@$_COOKIE['adminkuy']);
    $kunci   = $secu->injection(@$_COOKIE['kuncikuy']);
    $secu->validadmin($admin, $kunci);
    if($secu->validadmin($admin, $kunci) == false) { 
        header('location:'.$data->sistem('url_sis').'/signout'); 
    } else {
        $conn = $base->open();
        $cari = $secu->injection(@$_GET['key']);
        $search = $data->cekcari($cari, '-', ' ');
        $pecah = explode('_', $cari);
?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Data Kwitansi</title>
    </head>
    <body>
        <table>
            <tr>
                <th colspan="26">Data TF</th>
            </tr>
            <tr>
                <td colspan="26"></td>
            </tr>
        </table>
        <table border="1">
            <thead>
                <tr>
                    <th><center>#</center></th>
                    <th>Nama Outlet</th>
                    <th>Cek</th>
                    <th>Nomor Faktur</th>
                    <th>Tanggal Faktur</th>
                    <th>Nominal Faktur</th>
                    <th>Tujuan</th>
                    <th>Tanggal TF</th>
                    <th>Nama Kurir TF</th>
                    <th>Yes</th>
                    <th>No</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
            <?php
            	$pecah	= explode('_', $cari);
        		$cari	= $data->cekcari($pecah[0], '-', ' ');
        		$tgl1	= empty($pecah[1]) ? "" : "AND tgl_tfk>='$pecah[1]'"; 
        		$tgl2	= empty($pecah[2]) ? "" : "AND tgl_tfk<='$pecah[2]'"; 
                $nomor = 1;
                $previousName = ''; // Variable to store previous outlet name
                $master = $conn->prepare("SELECT
                                                A.id_tf,
                                                A.tujuan,
                                                A.tanggal,
                                                B.kode_tfk,
                                                B.tgl_tfk,
                                                B.tgl_tfk,
                                                C.nama_out,
                                                B.total_tfk
                                            FROM
                                                jadwal_tf AS A
                                            LEFT JOIN jadwal_tf_detail AS D ON
                                                A.id_tf = D.id_tf
                                            LEFT JOIN transaksi_faktur AS B ON
                                                D.no_faktur = B.id_tfk
                                            LEFT JOIN outlet AS C ON
                                                B.id_out = C.id_out
                                            WHERE
                                                A.id_tf LIKE '%$cari%' AND B.tgl_tfk LIKE '%$cari%' $tgl1 $tgl2
                                            ORDER BY
                                                A.tanggal DESC");
                $master->execute();
                while($hasil = $master->fetch(PDO::FETCH_ASSOC)){
                    $nameColumn = ($previousName !== $hasil['nama_out']) ? $hasil['nama_out'] : '';

                    // Display the row
            ?>
                <tr>
                    <td><center><?php echo($nomor); ?></center></td>
                    <td><center><?php echo($nameColumn); ?></center></td>
                    <td></td>
                    <td><?php echo($hasil['kode_tfk']); ?></td>
                    <td><?php echo($hasil['tgl_tfk']); ?></td>
                    <td>Rp. <?php echo($data->angka($hasil['total_tfk'])); ?></td>
                    <td><?php echo($hasil['tujuan']); ?></td>
                    <td><?php echo($hasil['tanggal']); ?></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            <?php
                    $nomor++;
                    $previousName = $hasil['nama_out']; // Update previous name
                }
            ?>
            </tbody>
        </table>
        <?php $conn = $base->close(); ?>
    </body>
<?php } ?>
</html>
