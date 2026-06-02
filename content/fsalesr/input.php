<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Retur</a></li>
                <li class="breadcrumb-item active" aria-current="page">Faktur Retur</li>
            </ol>
        </nav>
        <h4 class="content-title">Input Data - Faktur Retur</h4>
    </div>
</div>
<div class="content-body">
    <div class="component-section no-code">
        <h5 id="section1" class="tx-semibold"><?php echo($data->sistem('pt_sis')); ?></h5>
        <div style="margin-top:10px; margin-bottom:25px;">
            <div>Izin PBF No : <?php echo($data->sistem('pbf_sis')); ?></div>
            <div>NPWP No : <?php echo($data->sistem('npwp_sis')); ?></div>
            <div>Alamat : <?php echo($data->sistem('alamat_sis')); ?></div>
        </div>
        <form id="formsalespnpr" action="#" method="post" autocomplete="off">
        <input type="hidden" name="namamodal" id="namamodal" value="fsalesr" readonly="readonly" />
        <input type="hidden" name="namamenu" id="namamenu" value="faktur" readonly="readonly" />
        <input type="hidden" name="keycode" value="<?php echo(base64_encode('FAK'.time())); ?>" readonly="readonly" />
        <div class="row row-sm">
            <div class="col-sm-3">
                <label>Nomor SJ Retur <span class="tx-danger">*</span></label>
                <input type="text" name="invoice" id="koout" class="form-control" placeholder="-" />
            </div>
            <div class="col-sm-3">
                <label>Outlet <span class="tx-danger">*</span></label>
				<select name="outlet" id="outlet" class="form-control select2" onchange="ceksalesr()" required="required">
                	<option value="">-- Pilih --</option>
				<?php
					$status	= 'Active';
					$master	= $conn->prepare("SELECT id_out, kode_out, nama_out FROM outlet WHERE status_out=:status ORDER BY nama_out ASC");
					$master->bindParam(':status', $status, PDO::PARAM_STR);
					$master->execute();
					while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
				?>
                	<option value="<?php echo($hasil['id_out']); ?>"><?php echo($hasil['nama_out']); ?></option>
                <?php } ?>
                </select>
            </div>
            <div class="col-sm-3">
                <label>Nomor Faktur Retur <span class="tx-danger">*</span></label>
                <input type="text" name="nomorfaktur" id="fkout" class="form-control" placeholder="Ketik nomor Faktur Donasi di sini Gaess..." />
            </div>
            <div class="col-sm-3">
                <label>Tanggal Retur <span class="tx-danger">*</span></label>
                <input type="text" name="tglfaktur" class="form-control datepicker" value="<?php echo(date('Y-m-d')); ?>" placeholder="9999-99-99" />
            </div>
            <div class="col-sm-3 mg-t-10">
                <label>Tanggal SJ Retur <span class="tx-danger">*</span></label>
                <input type="text" name="tglsales" class="form-control datepicker" value="<?php echo(date('Y-m-d')); ?>" placeholder="9999-99-99" />
            </div>
            <div class="col-sm-3 mg-t-10">
                <label>Keterangan Retur <span class="tx-danger">*</span></label>
                <input type="text" name="nomorpo" class="form-control" placeholder="Ketik nomor donasi di sini..." />
            </div>
        </div><!-- row -->
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <div class="row row-sm">
            <div class="col-sm-12">
                <a href="<?php echo("$sistem/fsalesr"); ?>" title="Batal"><button type="button" class="btn btn-secondary">Batal</button></a>
                <button type="submit" id="bsave" class="btn btn-dark">Simpan</button>
                <div id="imgloading"></div>
            </div>
		</div>
		</form>
    </div>
</div>
