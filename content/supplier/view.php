<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Menu</a></li>
                <li class="breadcrumb-item"><a href="#">Mitra</a></li>
                <li class="breadcrumb-item active" aria-current="page">Outlet</li>
            </ol>
        </nav>
        <h4 class="content-title">Detail Data - Outlet</h4>
    </div>
</div>
<?php
	$kode	= $secu->injection($_GET['keycode']);
	$read	= $conn->prepare("SELECT A.nama_sup, A.cp_sup, A.npwp_sup, B.telp_sal, B.hp_sal, B.fax_sal, B.email_sal, C.parameter_sdi, C.diskon1_sdi, C.diskon2_sdi FROM supplier AS A LEFT JOIN supplier_alamat AS B ON A.id_sup=B.id_sup LEFT JOIN supplier_diskon AS C ON A.id_sup=C.id_sup WHERE A.id_sup=:kode");
	$read->bindParam(':kode', $kode, PDO::PARAM_STR);
	$read->execute();
	$view	= $read->fetch(PDO::PARAM_STR);
?>
<div class="content-body">
    <div class="table-responsive">
        <table class="table table-hover mg-b-0">
            <tr>
                <td width="30%">Nama</td>
				<td width="5%"><center>:</center></td>
                <td width="65%"><?php echo($view['nama_sup']); ?></td>
            </tr>
            <tr>
                <td>Contact Person</td>
				<td><center>:</center></td>
                <td><?php echo($view['cp_sup']); ?></td>
            </tr>
            <tr>
                <td>NPWP</td>
				<td><center>:</center></td>
                <td><?php echo($view['npwp_sup']); ?></td>
            </tr>
            <tr>
                <td>Telp.</td>
				<td><center>:</center></td>
                <td><?php echo($view['telp_sal']); ?></td>
            </tr>
            <tr>
                <td>Mobile / WA</td>
				<td><center>:</center></td>
                <td><?php echo($view['hp_sal']); ?></td>
            </tr>
            <tr>
                <td>Fax</td>
				<td><center>:</center></td>
                <td><?php echo($view['fax_sal']); ?></td>
            </tr>
            <tr>
                <td>Email</td>
				<td><center>:</center></td>
                <td><?php echo($view['email_sal']); ?></td>
            </tr>
            <tr>
                <td rowspan="2">Info Diskon</td>
				<td rowspan="2"><center>:</center></td>
                <td><?php echo("Pembelian di bawah $view[parameter_sdi] diskon $view[diskon1_sdi]%"); ?></td>
            </tr>
            <tr>
                <td><?php echo("Pembelian di atas $view[parameter_sdi] diskon $view[diskon2_sdi]%"); ?></td>
            </tr>
        </table>
    </div>
    <div class="clearfix mg-t-25 mg-b-25"></div>
    <div class="row row-sm">
        <div class="col-sm-12">
            <a href="<?php echo("$sistem/supplier"); ?>" title="Kembai"><button type="button" class="btn btn-secondary btn-xs"><i class="fa fa-chevron-circle-left"></i> Kembali</button></a>
        </div>
    </div>
    
</div>