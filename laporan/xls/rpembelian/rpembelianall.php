<?php
// Perbaiki sintaks PHP opening tag yang salah
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
    header("Content-Type: application/force-download");
    header("Cache-Control: no-cache, must-revalidate");
    header("Expires: Laporan"); 
    header("content-disposition:attachment; filename=report_pembelian.xls");
    require_once('../../../config/connection/connection.php');
    require_once('../../../config/connection/security.php');
    require_once('../../../config/function/data.php');
    require_once('../../../config/function/date.php');
    $secu	= new Security;
    $base	= new DB;
    $data	= new Data;
    $date	= new Date;
    $tanggal= date('Y-m-d');
    $admin	= $secu->injection(@$_COOKIE['adminkuy']);
    $kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
    $secu->validadmin($admin, $kunci);
    if($secu->validadmin($admin, $kunci)==false){ header('location:'.$data->sistem('url_sis').'/signout'); } else {
    $conn	= $base->open();
    $cari	= $secu->injection(@$_GET['key']);
    $pecah	= explode('_', $cari);
   
?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Report Pembelian</title>
    </head>

    <body>
        <table>
            <tr>
                <th colspan="6">REPORT PEMBELIAN</th>
            </tr>
            <tr>
                <td colspan="6"></td>
            </tr>
        </table>
        <table border="1">
            <thead>
                <tr>
                    <th><center>#</center></th>
                    <th>Supplier</th>
                    <th>Nomor Faktur</th>
                    <th><center>Tgl. Faktur</center></th>
                    <th><center>Tgl. Terima Barang</center></th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
            <?php
                $nomor	= 1;
                // Query yang sudah dihapus transaksi_receivedetail dan transaksi_order
                $master	= $conn->prepare("
                    SELECT 
                        B.fak_tre,
                        B.tgl_tre, 
                        B.tglfak_tre, 
                        B.total_tre,
                        F.nama_sup 
                    FROM 
                        transaksi_receive AS B 
                    LEFT JOIN 
                        supplier AS F ON B.id_sup=F.id_sup 
                    WHERE 
                        B.id_tre!='' 
                    ORDER BY 
                        B.tgl_tre DESC
                ");
                $master->execute();
                while($hasil = $master->fetch(PDO::FETCH_ASSOC)){
            ?>
                <tr>
                    <td><center><?php echo($nomor); ?></center></td>
                    <td><?php echo($hasil['nama_sup']); ?></td>
                    <td><?php echo($hasil['fak_tre']); ?></td>
                    <td><center><?php echo($hasil['tglfak_tre']); ?></center></td>
                    <td><center><?php echo($hasil['tgl_tre']); ?></center></td>
                    <td><center><?php echo($hasil['total_tre']); ?></center></td>
                </tr>
            <?php $nomor++; } ?>
            </tbody>
        </table>
        <?php $conn	= $base->close(); ?>
    </body>
<?php } ?>
</html>
