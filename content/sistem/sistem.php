<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Menu</a></li>
                <li class="breadcrumb-item"><a href="#">Perusahaan</a></li>
                <li class="breadcrumb-item active" aria-current="page">Perusahaan</li>
            </ol>
        </nav>
        <h4 class="content-title">Perusahaan</h4>
    </div>
</div>
<div class="content-body">
	<div class="row mg-b-10">
		<div class="col-sm-6">
        	<?php echo(($data->akses($admin, $menu, 'A.update_status')==='Active') ? '<a href="sistem/e" title="Edit Data"><button class="btn btn-info btn-pill btn-xs"><i class="fa fa-edit"></i> Edit Data</button></a>' : ''); ?>
		</div>
	</div>
    <div class="table-responsive">
        <table class="table table-hover mg-b-0">
			<tr>
            	<td colspan="3"><b>LOGO & GAMBAR</b></td>
            </tr>
            <tr>
                <td width="30%">Logo</td>
				<td width="5%"><center>:</center></td>
                <td width="65%"><img src="<?php echo("berkas/sistem/".$data->sistem('logo_sis')); ?>" width="250" /></td>
            </tr>
            <tr>
                <td>Favicon</td>
				<td><center>:</center></td>
                <td><img src="<?php echo("berkas/sistem/".$data->sistem('favicon_sis')); ?>" width="25" /></td>
            </tr>
            <tr>
                <td>Cap Apoteker</td>
				<td><center>:</center></td>
                <td><img src="<?php echo("berkas/sistem/".$data->sistem('cap_apoteker')); ?>" width="75" /></td>
            </tr>
			<tr>
            	<td colspan="3"><b>DATA PERUSAHAAN</b></td>
            </tr>
            <tr>
                <td>Nama Perusahaan</td>
				<td><center>:</center></td>
                <td><?php echo($data->sistem('pt_sis')); ?></td>
            </tr>
            <tr>
                <td>NPWP</td>
				<td><center>:</center></td>
                <td><?php echo($data->sistem('npwp_sis')); ?></td>
            </tr>
            <tr>
                <td>Telepon</td>
				<td><center>:</center></td>
                <td><?php echo($data->sistem('telp_sis')); ?></td>
            </tr>
            <tr>
                <td>Email</td>
				<td><center>:</center></td>
                <td><?php echo($data->sistem('email_sis')); ?></td>
            </tr>
            <tr>
                <td>Nomor Izin PBF</td>
				<td><center>:</center></td>
                <td><?php echo($data->sistem('pbf_sis')); ?></td>
            </tr>
            <tr>
                <td>Jatuh Tempo Izin PBF</td>
				<td><center>:</center></td>
                <td><?php echo($data->sistem('tpbf_sis')); ?></td>
            </tr>
            <tr>
                <td>Nomor Izin CDOB</td>
				<td><center>:</center></td>
                <td><?php echo($data->sistem('cdob_sis')); ?></td>
            </tr>
            <tr>
                <td>Jatuh Tempo Izin CDOB</td>
				<td><center>:</center></td>
                <td><?php echo($data->sistem('tcdob_sis')); ?></td>
            </tr>
            <tr>
                <td>Nomor Izin SIPA</td>
				<td><center>:</center></td>
                <td><?php echo($data->sistem('sipa_sis')); ?></td>
            </tr>
            <tr>
                <td>Jatuh Tempo Izin SIPA</td>
				<td><center>:</center></td>
                <td><?php echo($data->sistem('tsipa_sis')); ?></td>
            </tr>
            <tr>
                <td>Apoteker Penaggung Jawab</td>
				<td><center>:</center></td>
                <td><?php echo($data->sistem('apoteker_sis')); ?></td>
            </tr>
            <tr>
                <td>Alamat</td>
				<td><center>:</center></td>
                <td><?php echo($data->sistem('alamat_sis')); ?></td>
            </tr>
			<tr>
            	<td colspan="3"><b>ACCOUNT BANK</b></td>
            </tr>
            <tr>
                <td>Bank</td>
				<td><center>:</center></td>
                <td><?php echo($data->sistem('bank_sis')); ?></td>
            </tr>
            <tr>
                <td>No. Rekening</td>
				<td><center>:</center></td>
                <td><?php echo($data->sistem('norek_sis')); ?></td>
            </tr>
            <tr>
                <td>Account</td>
				<td><center>:</center></td>
                <td><?php echo($data->sistem('anam_sis')); ?></td>
            </tr>
			<tr>
            	<td colspan="3"><b>LIMIT EXPIRED, STOK & TAGIHAN</b></td>
            </tr>
            <tr>
                <td>Expired Date</td>
				<td><center>:</center></td>
                <td><?php echo($data->sistem('limit_expired')); ?> Hari</td>
            </tr>
            <tr>
                <td>Minimal Stok</td>
				<td><center>:</center></td>
                <td><?php echo($data->sistem('limit_stok')); ?> Stok</td>
            </tr>
            <tr>
                <td>Tagihan Supplier</td>
				<td><center>:</center></td>
                <td><?php echo($data->sistem('limit_supplier')); ?> Hari</td>
            </tr>
            <tr>
                <td>Tagihan Outlet</td>
				<td><center>:</center></td>
                <td><?php echo($data->sistem('limit_outlet')); ?> Hari</td>
            </tr>
			<tr>
            	<td colspan="3"><b>DATA SISTEM</b></td>
            </tr>
            <tr>
                <td>Nama Sistem</td>
				<td><center>:</center></td>
                <td><?php echo($data->sistem('nama_sis')); ?></td>
            </tr>
            <tr>
                <td>Nama Aplikasi</td>
				<td><center>:</center></td>
                <td><?php echo($data->sistem('app_sis')); ?></td>
            </tr>
            <tr>
                <td>Tagline</td>
				<td><center>:</center></td>
                <td><?php echo($data->sistem('tagline_sis')); ?></td>
            </tr>
            <tr>
                <td>URL Sistem</td>
				<td><center>:</center></td>
                <td><?php echo($data->sistem('url_sis')); ?></td>
            </tr>
        </table>
    </div>
</div>