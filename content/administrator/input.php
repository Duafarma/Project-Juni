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
<div class="content-body">
    <div class="component-section no-code">
        <h5 id="section1" class="tx-semibold">Data Administrator</h5>
        <p class="mg-b-25">Gunakan email dan password yang unik.</p>
        <form id="formtransaksi" action="#" method="post" autocomplete="off">
        <input type="hidden" name="nmenu" id="nmenu" value="administrator" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="input" readonly="readonly" />
        <div class="row">
            <div class="form-group col-sm-3">
                <label>Cabang <span class="tx-danger">*</span></label>
                <select name="cabang" class="form-control" required="required">
                	<option value="">-- Select Cabang --</option>
				<?php
					$master	= $conn->prepare("SELECT id_cabang, nama_cabang FROM cabang ORDER BY nama_cabang ASC");
					$master->execute();
					while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
				?>
                	<option value="<?php echo($hasil['id_cabang']); ?>"><?php echo($hasil['nama_cabang']); ?></option>
                <?php } ?>
                </select>
            </div>
        </div><!-- row -->
        <div class="row">
            <div class="form-group col-sm-3">
                <label>Nama <span class="tx-danger">*</span></label>
                <input type="text" name="nama" class="form-control" placeholder="Type here..." required="required" />
            </div>
            <div class="form-group col-sm-3">
                <label>Level <span class="tx-danger">*</span></label>
                <select name="jenis" class="form-control" required="required">
                    <option value="">-- Select Level --</option>
                    <option value="Super">Super</option>
                    <option value="Admin">Admin</option>
                    <option value="Finance">Finance</option>
                </select>
            </div>
            <div class="form-group col-sm-3">
                <label>Email <span class="tx-danger">*</span></label>
                <input type="email" name="emailz" class="form-control" placeholder="Type here..." required="required" />
            </div>
            <div class="form-group col-sm-3">
                <label>Password <span class="tx-default">*</span></label>
                <input type="password" name="passz" class="form-control" placeholder="Type here..." required="required" />
            </div>
        </div><!-- row -->
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <h5 id="section1" class="tx-semibold">Role Menu</h5>
        <p class="mg-b-25">Pilih sub menu yang dapat diakses user.</p>
        <div class="row row-sm">
            <div class="col-sm-12">
            	<div class="table-responsive">
				<table class="tabelrole">
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
					?>
                    	<tr>
                        	<td><center><input type="checkbox" name="submenu[]" value="<?php echo($hasil['id_smu']); ?>" /></center></td>
							<td><?php echo($hasil['nama_smu']); ?></td>
                        	<td><center><input type="checkbox" name="<?php echo("t$hasil[id_smu]"); ?>" value="Active" /></center></td>
                        	<td><center><input type="checkbox" name="<?php echo("l$hasil[id_smu]"); ?>" value="Active" /></center></td>
                        	<td><center><input type="checkbox" name="<?php echo("u$hasil[id_smu]"); ?>" value="Active" /></center></td>
                        	<td><center><input type="checkbox" name="<?php echo("h$hasil[id_smu]"); ?>" value="Active" /></center></td>
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
                <button type="submit" id="bsave" class="btn btn-dark btn-xs">Simpan</button>
            </div>
		</div>
		</form>
    </div>
</div>