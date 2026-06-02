<div class="content-header">
	<div>
		<nav aria-label="breadcrumb">
			<ol class="breadcrumb">
				<li class="breadcrumb-item"><a href="#">Home</a></li>
				<li class="breadcrumb-item"><a href="#">Pengiriman</a></li>
				<li class="breadcrumb-item active" aria-current="page">Pengiriman Ekspedisi</li>
			</ol>
		</nav>
		<h4 class="content-title">Input Data - Pengiriman Luar Kota</h4>
	</div>
</div>
<div class="content-body">
	<div class="component-section no-code">
    <form id="formtransaksi" action="#" method="post" enctype="multipart/form-data" autocomplete="off">
	   <input type="hidden" name="nmenu" id="nmenu" value="pengirimanlk" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="input" readonly="readonly" />
		<div class="form-row">
		<div class="form-group col-sm-6">
                <label>Nomor Faktur <span class="tx-danger">*</span></label>
                <input type="text" name="id_tfk" class="form-control" placeholder="Ketik nomor Resi di sini..." />
            </div>
			<div class="form-group col-sm-6">
                <label>Nama Outlet <span class="tx-danger">*</span></label>
                <select name="id_out" id="id_out" class="form-control select2" onchange="" required="required">
                <option value="">-- Pilih  Outlet--</option>
                <?php
                $status = 'Active';
                $master	= $conn->prepare("SELECT id_out, nama_out,status_out FROM outlet WHERE status_out=:status ORDER BY nama_out ASC");      
            	$master->bindParam(':status', $status, PDO::PARAM_STR);
                $master->execute();
                  while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
                ?>
                 <option value="<?php echo($hasil['id_out']); ?>"><?php echo($hasil['nama_out'] ); ?></option>
                <?php } ?>
                </select>
            </div>
            <div class="form-group col-sm-6">
                <label>Cabang <span class="tx-danger">*</span></label>
                <select name="cabang" id="cabang" class="form-control select2" onchange="" required="required">
                <option value="">-- Pilih  Gudang--</option>
               
                 <option value="Jakarta">Jakarta</option>
                 <option value="Bekasi">Bekasi</option>
                 <option value="Cibinong">Cibinong</option>
                 <option value="Surabaya">Surabaya</option>
                <option value="Medan">Medan</option>
                 <option value="Bali">Bali</option>

                </select>
            </div>
			<div class="form-group col-sm-6">
				<label>Ekspedisi <span class="tx-danger">*</span></label>
				<select name="id_vendor" id="id_vendor" class="form-control select2" required="required">
					<option value="">-- Pilih --</option>
					<?php
						$datas = $conn->prepare("SELECT id_vendor, nama_vendor FROM vendor_pengiriman ORDER BY nama_vendor ASC");
						$datas->execute();
						while($hasil= $datas->fetch(PDO::FETCH_ASSOC)){
					?>
						<option value="<?php echo($hasil['id_vendor']); ?>"><?php echo($hasil['nama_vendor']); ?></option>
					<?php } ?>
				</select>
			</div>
		    <div class="form-group col-sm-6">
				<label>Status Pengiriman <span class="tx-danger">*</span></label>
				<select name="id_pengiriman" id="id_pengiriman" class="form-control select2" required="required">
					<option value="">-- Pilih --</option>
					<?php
						$datas = $conn->prepare("SELECT id_pengiriman, tahap_pengiriman FROM master_pengiriman ORDER BY tahap_pengiriman ASC");
						$datas->execute();
						while($hasil= $datas->fetch(PDO::FETCH_ASSOC)){
					?>
						<option value="<?php echo($hasil['id_pengiriman']); ?>"><?php echo($hasil['tahap_pengiriman']); ?></option>
					<?php } ?>
				</select>
			</div>
			 <div class="form-group  col-sm-6 ">
                <label>Tanggal Faktur <span class="tx-danger">*</span></label>
                <input type="text" name="tanggal_faktur" class="form-control datepicker" value="<?php echo(date('Y-m-d')); ?>" placeholder="9999-99-99" />
            </div>
            <div class="form-group col-sm-6">
                <label>Nomor Resi <span class="tx-danger">*</span></label>
                <input type="text" name="nomor_resi" class="form-control" placeholder="Ketik nomor Resi di sini..." />
            </div>
			<div class="form-group  col-sm-6 ">
                <label>Tanggal Pengiriman <span class="tx-danger">*</span></label>
                <input type="text" name="tanggal_pengiriman" class="form-control datepicker" value="<?php echo(date('Y-m-d')); ?>" placeholder="9999-99-99" />
            </div>
		</div>
		<div class="row">
			<div class="textarea col-sm-12">
				<button type="submit" id="bsave" class="btn btn-dark">Simpan</button>
				<div id="imgloading"></div>
			</div>
		</div>
		</form>
	</div>
</div>