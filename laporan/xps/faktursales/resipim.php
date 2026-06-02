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
	$kode	= $secu->injection(@$_GET['key']);
	$read	= $conn->prepare("SELECT A.kode_tfk, A.tgl_tfk, A.tgl_limit, A.po_tfk, TIMESTAMPDIFF(DAY, A.tgl_tfk, A.tgl_limit) AS jarak, B.nama_out, B.npwp_out, C.pengiriman_ola FROM transaksi_faktur_pim AS A LEFT JOIN outlet AS B ON A.id_out=B.id_out LEFT JOIN outlet_alamat AS C ON B.id_out=C.id_out WHERE A.id_tfk=:kode");
	$read->bindParam(':kode', $kode, PDO::PARAM_STR);
	$read->execute();
	$view	= $read->fetch(PDO::FETCH_ASSOC);
	//$limit	= $date->oprPeriode("Y-m-d", "+$view[top_odi] DAY", $view['tgl_tfk']);
?>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Print Resi Pengiriman</title>
    <link href="<?php echo($data->sistem('url_sis').'/config/css/laporan.css'); ?>" rel="stylesheet">
    <style type="text/css" media="print">
        @media print {
            .garis {
                width: 100%;
                border: solid 10px #333333;
                background-color: #333333;
                margin: 10px 0;
            }
            .tabel {
                border: 5px solid #333333;
                border-collapse: collapse;
                width: 600px;
                margin-top: 5px;
            }
            .tabel th, .tabel td {
                border: none; /* Menghilangkan border pada sel dalam */
                padding: 5px; /* Menambah jarak dalam sel */
            }
            .obat {
                font-weight: bold;
                text-align: center;
                font-size: 60px;
            }
            .regfont {
                font-size: 18px;
            }
            @page {
                size: portrait;
            }
        }
    </style>
</head>

<body>
    <table class="tabel" style="font-weight:bold;">
        <tr>
            <td colspan="3" class="obat">OBAT</td>
        </tr>
        <tr>
            <td class="regfont">Pengirim</td>
            <td>:</td>
            <td class="regfont">PT. DUA FARMA MAHAKARSA (081384852488)</td>
        </tr>
        <tr>
            <td class="regfont">Penerima</td>
            <td>:</td>
            <td class="regfont"><?php echo($view['nama_out']); ?></td>
        </tr>
        <tr>
            <td> </td>
            <td>:</td>
            <td class="regfont"><?php echo($view['pengiriman_ola']); ?></td>
        </tr>
        <tr>
            <td class="regfont">Nomor Handphone</td>
            <td>:</td>
            <td class="regfont"></td>
        </tr>
    </table>
    
    <table class="tabel" style="font-weight:bold;">
        <tr>
            <td colspan="3" class="obat">OBAT</td>
        </tr>
        <tr>
            <td class="regfont">Pengirim</td>
            <td>:</td>
            <td class="regfont">PT. DUA FARMA MAHAKARSA (081384852488)</td>
        </tr>
        <tr>
            <td class="regfont">Penerima</td>
            <td>:</td>
            <td class="regfont"><?php echo($view['nama_out']); ?></td>
        </tr>
        <tr>
            <td> </td>
            <td>:</td>
            <td class="regfont"><?php echo($view['pengiriman_ola']); ?></td>
        </tr>
        <tr>
            <td class="regfont">Nomor Handphone</td>
            <td>:</td>
            <td class="regfont"></td>
        </tr>
    </table>

    <script type="text/javascript">window.print();</script>
</body>



<?php } ?>
</html>
