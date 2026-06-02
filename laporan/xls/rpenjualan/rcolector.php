<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
    header("Content-Type: application/force-download");
    header("Cache-Control: no-cache, must-revalidate");
    header("Expires: Laporan");
    header("content-disposition:attachment; filename=report_colector_A.xls");

    require_once('../../../config/connection/connection.php');
    require_once('../../../config/connection/security.php');
    require_once('../../../config/function/data.php');
    require_once('../../../config/function/date.php');

    $secu = new Security;
    $base = new DB;
    $data = new Data;
    $date = new Date;
    $tanggal = date('Y-m-d');
    $admin = $secu->injection(@$_COOKIE['adminkuy']);
    $kunci = $secu->injection(@$_COOKIE['kuncikuy']);
    $secu->validadmin($admin, $kunci);
    
    if ($secu->validadmin($admin, $kunci) == false) {
        header('location:'.$data->sistem('url_sis').'/signout');
    } else {
        $conn = $base->open();
        $cari = $secu->injection(@$_GET['key']);
        $pecah = explode('_', $cari);
        $cari = $data->cekcari($pecah[0], '-', ' ');
        $tgl1 = empty($pecah[1]) ? "" : "AND A.tgl_tfk>='$pecah[1]'"; 
        $tgl2 = empty($pecah[2]) ? "" : "AND A.tgl_tfk<='$pecah[2]'"; 
?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Report colector</title>
    </head>

    <body>
        <table>
            <tr>
                <th colspan="18">REPORT COLECTOR </th>
            </tr>
            <tr>
                <td colspan="18"></td>
            </tr>
        </table>
        <table border="1">
            <thead>
                <tr>
                    <th><center>#</center></th>
                    <th><center>Nama Outlet</center></th>
                    <th>Nomor Faktur</th>
                    <th>Tanggal Faktur</th>
                    <th>Total Faktur</th>
                    <th><center>Kode Outlet</center></th>
                    <th><center>Tanggal Tukar Faktur</center></th>
                    <th><center>Waktu</center></th>
                    <th><center>Nama</center></th>
                    <th><center>Status Tukar Faktur</center></th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $nomor = 1;
                    $qmaster = "SELECT A.id_tfk, 
                                A.kode_tfk, 
                                A.tgl_tfk,
                                A.total_tfk,
                                B.nama_out, 
                                B.kode_rs,
                                C.status_tfkkf,
                                C.created_at,
                                D.nama_adm 
                                FROM transaksi_faktur AS A 
                                LEFT JOIN outlet AS B ON A.id_out=B.id_out 
                                LEFT JOIN transaksi_faktur_kirim_f AS C ON A.id_tfk=C.id_tfk 
                                LEFT JOIN adminz AS D ON C.id_adm=D.id_adm 
                                WHERE (A.kode_tfk LIKE '%$cari%' OR B.nama_out LIKE '%$cari%') $tgl1 $tgl2 
                                ORDER BY A.tgl_tfk DESC, A.kode_tfk DESC";
                    $master = $conn->prepare($qmaster);
                    $master->execute();
                    while ($hasil = $master->fetch(PDO::FETCH_ASSOC)) {
                ?>
                <tr>
                    <td><center><?php echo($nomor); ?></center></td>
                    <td><?php echo(strtoupper($hasil['nama_out'])); ?></td>
                    <td><?php echo($hasil['kode_tfk']); ?></td>
                    <td><center><?php echo($hasil['tgl_tfk']); ?></center></td>
                    <td><center><?php echo($data->angka($hasil['total_tfk'])); ?></center></td>
                    <td><center><?php echo($hasil['kode_rs']); ?></center></td>
                    <td>
                        <?php
                        // Mengecek apakah 'created_at' ada dan formatnya benar
                        if (isset($hasil['created_at']) && !empty($hasil['created_at']) && strtotime($hasil['created_at']) !== false) {
                            echo date('Y-m-d', strtotime($hasil['created_at']));
                        } else {
                            echo ''; // Jika kosong atau tidak valid
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        // Mengecek apakah 'created_at' ada dan formatnya benar
                        if (isset($hasil['created_at']) && !empty($hasil['created_at']) && strtotime($hasil['created_at']) !== false) {
                            echo date('H:i:s', strtotime($hasil['created_at']));
                        } else {
                            echo ''; // Jika kosong atau tidak valid
                        }
                        ?>
                    </td>
                    <td><?php echo($hasil['nama_adm']); ?></td>
                    <td><?php echo($hasil['status_tfkkf']); ?></td>
                </tr>
                <?php $nomor++; } ?>
            </tbody>
        </table>
        <?php $conn = $base->close(); ?>
    </body>
<?php } ?>
</html>
