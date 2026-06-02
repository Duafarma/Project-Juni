<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Pengguna</a></li>
                <li class="breadcrumb-item active" aria-current="page">Administrator</li>
            </ol>
        </nav>
        <h4 class="content-title">Input Data - Administrator</h4>
    </div>
</div>
<?php
	$uniq	= $secu->injection($_GET['key']);
	$kode	= base64_decode($uniq);
	$read	= $conn->prepare("SELECT id_cabang, jenis_adm, nama_adm, email_adm FROM adminz WHERE id_adm=:kode");
	$read->bindParam(':kode', $kode, PDO::PARAM_STR);
	$read->execute();
	$view	= $read->fetch(PDO::FETCH_ASSOC);
?>
<div class="content-body">
    <div class="component-section no-code">
        <h5 id="section1" class="tx-semibold">Data Administrator</h5>
        <p class="mg-b-25">Gunakan email dan password yang unik.</p>
        <form id="formtransaksi" action="#" method="post" autocomplete="off">
        <input type="hidden" name="nmenu" id="nmenu" value="administrator" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="update" readonly="readonly" />
        <input type="hidden" name="keycode" value="<?php echo($uniq); ?>" readonly="readonly" />
        <div class="row">
            <div class="form-group col-sm-3">
                <label>Cabang <span class="tx-danger">*</span></label>
                <select name="cabang" class="form-control" required="required">
                	<option value="">-- Select Cabang --</option>
				<?php
					$master	= $conn->prepare("SELECT id_cabang, nama_cabang FROM cabang ORDER BY nama_cabang ASC");
					$master->execute();
					while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
						$pilih	= ($view['id_cabang']===$hasil['id_cabang']) ? 'selected="selected"' : '';
				?>
                	<option value="<?php echo($hasil['id_cabang']); ?>" <?php echo($pilih); ?>><?php echo($hasil['nama_cabang']); ?></option>
                <?php } ?>
                </select>
            </div>
        </div><!-- row -->
        <div class="row">
            <div class="form-group col-sm-3">
                <label>Nama <span class="tx-danger">*</span></label>
                <input type="text" name="nama" class="form-control" value="<?php echo($view['nama_adm']); ?>" placeholder="Type here..." required="required" />
            </div>
            <div class="form-group col-sm-3">
                <label>Level <span class="tx-danger">*</span></label>
                <select name="jenis" class="form-control" required="required">
                    <option value="">-- Select Level --</option>
                    <option value="Super" <?php echo(($view['jenis_adm']==='Super') ? 'selected="selected"' : ''); ?>>Super</option>
                    <option value="Admin" <?php echo(($view['jenis_adm']==='Admin') ? 'selected="selected"' : ''); ?>>Admin</option>
                    <option value="Finance" <?php echo(($view['jenis_adm']==='Finance') ? 'selected="selected"' : ''); ?>>Finance</option>
                </select>
            </div>
            <div class="form-group col-sm-3">
                <label>Email <span class="tx-danger">*</span></label>
                <input type="email" name="emailz" class="form-control" value="<?php echo($view['email_adm']); ?>"placeholder="Type here..." required="required" />
            </div>
            <div class="form-group col-sm-3">
                <label>Password <span class="tx-default">*</span></label>
                <input type="password" name="passz" class="form-control" placeholder="Type here..." />
            </div>
        </div><!-- row -->
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <h5 id="section1" class="tx-semibold">Role Menu</h5>
        <p class="mg-b-25">Pilih sub menu yang dapat diakses user.</p>
        <div class="row row-sm">
            <div class="col-sm-12">
            	<div class="table-responsive">
				<table class="tabeltransaksi">
                	<thead>
                        <tr>
                            <th><center>Pilih</center></th>
                            <th>Sub Menu</th>
                            <th><center>Create</center></th>
                            <th><center>Read</center></th>
                            <th><center>Update</center></th>
                            <th><center>Delete</center></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
						$master	= $conn->prepare("SELECT id_smu, nama_smu FROM sub_menu ORDER BY nama_smu ASC");
						$master->execute();
						while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
							$read	= $conn->prepare("SELECT id_smu, create_status, read_status, update_status, delete_status FROM role_menu WHERE id_adm=:kode AND id_smu=:submenu");
							$read->bindParam(':kode', $kode, PDO::PARAM_STR);
							$read->bindParam(':submenu', $hasil['id_smu'], PDO::PARAM_STR);
							$read->execute();
							$view	= $read->fetch(PDO::FETCH_ASSOC);
							$pilih	= empty($view['id_smu']) ? '' : (($hasil['id_smu']===$view['id_smu']) ? 'checked="checked"' : '');
							$tambah	= empty($view['create_status']) ? '' : (($view['create_status']==='Active') ? 'checked="checked"' : '');
							$lihat	= empty($view['read_status']) ? '' : (($view['read_status']==='Active') ? 'checked="checked"' : '');
							$ubah	= empty($view['update_status']) ? '' : (($view['update_status']==='Active') ? 'checked="checked"' : '');
							$hapus	= empty($view['delete_status']) ? '' : (($view['delete_status']==='Active') ? 'checked="checked"' : '');
					?>
                    	<tr>
                        	<td><center><input type="checkbox" name="submenu[]" value="<?php echo($hasil['id_smu']); ?>" <?php echo($pilih); ?> /></center></td>
							<td><?php echo($hasil['nama_smu']); ?></td>
                        	<td><center><input type="checkbox" name="<?php echo("t$hasil[id_smu]"); ?>" value="Active" <?php echo($tambah); ?> /></center></td>
                        	<td><center><input type="checkbox" name="<?php echo("l$hasil[id_smu]"); ?>" value="Active" <?php echo($lihat); ?> /></center></td>
                        	<td><center><input type="checkbox" name="<?php echo("u$hasil[id_smu]"); ?>" value="Active" <?php echo($ubah); ?> /></center></td>
                        	<td><center><input type="checkbox" name="<?php echo("h$hasil[id_smu]"); ?>" value="Active" <?php echo($hapus); ?> /></center></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
                <div id="imgloading"></div>
                </div>
            </div>
        </div>
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <div class="row row-sm">
            <div class="col-sm-12">
                <a href="<?php echo("$sistem/administrator"); ?>" title="Batal"><button type="button" class="btn btn-secondary btn-xs">Batal</button></a>
                <button type="submit" id="bsave" class="btn btn-dark btn-xs">Update</button>
            </div>
		</div>
		</form>
    </div>
</div>