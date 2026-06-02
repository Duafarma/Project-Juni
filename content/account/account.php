<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Menu</a></li>
                <li class="breadcrumb-item"><a href="#">Pengguna</a></li>
                <li class="breadcrumb-item active" aria-current="page">Account</li>
            </ol>
        </nav>
        <h4 class="content-title">Account</h4>
    </div>
</div>
<div class="content-body">
    <?php require_once('config/frame/alert.php'); ?>
    <div class="table-responsive">
        <table class="table table-hover mg-b-0">
			<tr>
            	<td colspan="3"><b>DATA ACCOUNT</b></td>
            </tr>
            <tr>
                <td>Foto</td>
				<td><center>:</center></td>
                <td><img src="<?php echo("$sistem/berkas/adminz/".$data->myadmin($admin, 'foto_adm')); ?>" width="100" /></td>
            </tr>
            <tr>
                <td>Nama</td>
				<td><center>:</center></td>
                <td><?php echo($data->myadmin($admin, 'nama_adm')); echo(($data->akses($admin, $menu, 'A.update_status')==='Active') ? ' <a href="#modal1" onclick="crud(\'account\', \'update\', \''.$admin.'\')" data-toggle="modal" class="span span-info" title="Edit Data"><i class="fa fa-edit"></i></a> ' : ''); ?></td>
            </tr>
            <tr>
                <td>Level</td>
				<td><center>:</center></td>
                <td><?php echo($data->myadmin($admin, 'jenis_adm')); ?></td>
            </tr>
            <tr>
                <td>Email</td>
				<td><center>:</center></td>
                <td><?php echo($data->myadmin($admin, 'email_adm')); echo(($data->akses($admin, $menu, 'A.update_status')==='Active') ? ' <a href="#modal1" onclick="crud(\'account\', \'email\', \''.$admin.'\')" data-toggle="modal" class="span span-info" title="Edit Data"><i class="fa fa-edit"></i></a> ' : ''); ?></td>
            </tr>
            <tr>
                <td>Password</td>
				<td><center>:</center></td>
                <td>**********<?php echo(($data->akses($admin, $menu, 'A.update_status')==='Active') ? ' <a href="#modal1" onclick="crud(\'account\', \'password\', \''.$admin.'\')" data-toggle="modal" class="span span-info" title="Edit Data"><i class="fa fa-edit"></i></a> ' : ''); ?></td>
            </tr>
            <tr>
                <td>Status</td>
				<td><center>:</center></td>
                <td><?php echo($data->myadmin($admin, 'status_adm')); ?></td>
            </tr>
        </table>
    </div>
</div>