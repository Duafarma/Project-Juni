<!DOCTYPE html>
<html>
<?php
require_once('../../../config/connection/connection.php');
require_once('../../../config/connection/security.php');
require_once('../../../config/function/data.php');
require_once('../../../config/function/date.php');
$secu   = new Security;
$base   = new DB;
$data   = new Data;
$date   = new Date;
$admin  = $secu->injection(@$_COOKIE['adminkuy']);
$kunci  = $secu->injection(@$_COOKIE['kuncikuy']);
$secu->validadmin($admin, $kunci);
if($secu->validadmin($admin, $kunci)==false){ header('location:'.$data->sistem('url_sis').'/signout'); } else {
    $conn   = $base->open();
    $kode   = $secu->injection(@$_GET['key']);
    $read   = $conn->prepare("SELECT tt.*, a2.nama_apl AS dari, a3.nama_apl AS ke FROM transaksi_transferstock tt LEFT JOIN aplikasi a2 ON tt.id_app_from=a2.id_apl LEFT JOIN aplikasi a3 ON tt.id_app_to=a3.id_apl WHERE tt.id_ttr=:kode");
    $read->bindParam(':kode', $kode, PDO::PARAM_STR);
    $read->execute();
    $view   = $read->fetch(PDO::FETCH_ASSOC);
?>
<head>
    <meta charset="utf-8" />
    <title>Formulir Penerimaan Barang </title>
    <link href="<?php echo($data->sistem('url_sis')."/config/css/laporan.css"); ?>" rel="stylesheet">
    <style type="text/css" media="print">
        @media print {
            .garis{
                width:100%;
                border:solid 1px #333333;
                background-color:#333333;
                margin:20px 0px 20px 0px;
            }
        }
        @page { size: portrait; }
    </style>
</head>
<body>
    <div>
        <div style="float:left; font-size:60px;"><img src="<?php echo("../../../berkas/sistem/".$data->sistem('logo_sis')); ?>" height="70" width="100" /></div>
        <div align="right" style="font-size:10px;"><?php echo($data->sistem('pt_sis')); ?></div>
        <div align="right" style="font-size:10px;"><?php echo($data->sistem('alamat_sis')); ?></div>
        <div align="right" style="font-size:10px;">PHONE <?php echo($data->sistem('telp_sis')); ?></div>
        <div align="right" style="font-size:10px;">NPWP : <?php echo($data->sistem('npwp_sis')); ?></div>
        <div align="right" style="font-size:10px;">IZIN PBF : <?php echo($data->sistem('pbf_sis')); ?></div>
        <div align="right" style="font-size:10px;">SIPA APJ : <?php echo($data->sistem('sipa_sis')); ?></div>
        <div align="right" style="font-size:10px;">CDOB : <?php echo($data->sistem('cdob_sis')); ?></div>
        <!--<div align="right" style="font-size:10px;">CDOB CCP : CDOB2777/S/1-1844/01/2024</div>-->
    </div>
    <br />
    <div style="text-align:center; font-size:22px; font-weight:bold; margin:10px 0px 10px 0px;">FORMULIR PENERIMAAN BARANG </div>
    <table style="font-weight:bold;">
        <tr>
            <td>No. TRANSFER</td>
            <td><center>:</center></td>
            <td width="65%"><?php echo($view['kode_ttr']); ?></td>
        </tr>
        <tr>
            <td>TANGGAL</td>
            <td><center>:</center></td>
            <td width="65%"><?php echo($view['tgl_ttr']); ?></td>
        </tr>
        <tr>
            <td>APLIKASI DARI</td>
            <td><center>:</center></td>
            <td width="65%"><?php echo($view['dari']); ?></td>
        </tr>
        <tr>
            <td>APLIKASI KE</td>
            <td><center>:</center></td>
            <td width="65%"><?php echo($view['ke']); ?></td>
        </tr>
        <tr>
            <td>KETERANGAN</td>
            <td><center>:</center></td>
            <td width="65%"><?php echo($view['ket_ttr']); ?></td>
        </tr>
    </table>
    <p></p>
    <div class="table-responsive">
        <table class="tabel">
            <thead>
                <tr>
                    <th>NO</th>
                    <th>NAMA BARANG</th>
                    <th>BATCH</th>
                    <th>ED</th>
                    <th>JUMLAH</th>
                    <th>NIE</th>
                </tr>
            </thead>
            <tbody>
            <?php
                $nomor = 1;
                $master = $conn->prepare("SELECT td.*, p.nama_pro, p.no_nie,C.no_bcode, C.tgl_expired FROM transaksi_transferstockdetail td LEFT JOIN produk p ON td.id_pro=p.id_pro LEFT JOIN 	produk_stokdetail  C ON td.id_psd=C.id_psd WHERE td.id_ttr=:kode");
                $master->bindParam(':kode', $kode, PDO::PARAM_STR);
                $master->execute();
                while($hasil = $master->fetch(PDO::FETCH_ASSOC)){
            ?>
                <tr>
                    <td><center><?php echo($nomor); ?></center></td>
                    <td><center><?php echo($hasil['nama_pro']); ?></center></td>
                    <td><center><?php echo($hasil['no_bcode']); ?></center></td>
                    <td><center><?php echo($hasil['tgl_expired']); ?></center></td>
                    <td><div align="right"><?php echo($hasil['jumlah_ttd']); ?></div></td>
                    <td><center><?php echo($hasil['no_nie']); ?></center></td>
                </tr>
            <?php $nomor++; } ?>
            </tbody>
        </table>
    </div>
    <br />
    <table width="125%">
        <tr>
            <td width="33%"></td>
            <td width="20%"></td>
            <td width="33%"><div style="text-transform:capitalize;"></div></td>
        </tr>
        <tr>
            <td style="font-size:15px;">Petugas Gudang</td>
            <td style="font-size:15px;"></td>
            <td style="font-size:15px;">Mengetahui, </td>
        </tr>
        <tr>
            <td height="60"></td>
            <td height="60"></td>
            <td><img src="" width="60%" /></td>
        </tr>
        <tr>
            <td height="60"></td>
            <td height="60"></td>
            <td><img src="" width="60%" /></td>
        </tr>
        <tr>
            <td style="font-size:15px;">(Petugas  Penerimaan)</td>
            <td style="font-size:15px;"></td>
            <td style="font-size:15px;">(Apoteker Penanggung Jawab)</td>
        </tr>
    </table>
    <script type="text/javascript">window.print();</script>
</body>
<?php } ?>
</html>
