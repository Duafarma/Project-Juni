<div class="content-header">
	<div>
		<nav aria-label="breadcrumb">
			<ol class="breadcrumb">
				<li class="breadcrumb-item"><a href="#">Home</a></li>
				<li class="breadcrumb-item"><a href="#">Pengiriman</a></li>
				<li class="breadcrumb-item active" aria-current="page">Pengiriman Kurir</li>
			</ol>
		</nav>
		<h4 class="content-title">Input Data - Nomor Seri Faktur Pajak</h4>
	</div>
</div>
<div class="content-body">
	<div class="component-section no-code">
    <form id="formtransaksi" action="#" method="post" enctype="multipart/form-data" autocomplete="off">
        <input type="hidden" name="nmenu" id="nmenu" value="fakturpajak" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="input" readonly="readonly" />
		<div class="form-row">
			<div class="form-group col-sm-6">
				<label>Nomor Faktur <span class="tx-danger">*</span></label>
				<select name="id_tfk" id="id_tfk" class="form-control select2"  required="required">
				<option value="">-- Pilih --</option>
				<?php
					$datas = $conn->prepare("
						SELECT
							id_tfk,
							kode_tfk,
                            status_f_pajak
						FROM 
							transaksi_faktur 
						WHERE 
							created_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)
						AND
							status_f_pajak = 'belum terbit' 
							
						ORDER BY kode_tfk ASC");
				  $datas->execute();
				  while($hasil= $datas->fetch(PDO::FETCH_ASSOC)){
				?>
				<option value="<?php echo($hasil['id_tfk']); ?>"><?php echo($hasil['kode_tfk']); ?></option>
				<?php } ?>
				</select>
			</div>
		
			
			<div class="form-group col-sm-6">
                <label>Nomor Seri Faktur Pajak <span class="tx-danger">*</span></label>
                <input type="text" name="nomor_seri" class="form-control" placeholder="Type here..." required="required" />
            </div>
             <div class="col-sm-6">
                <label>Tanggal <span class="tx-danger">*</span></label>
                <input type="text" name="tanggal" class="form-control datepicker" value="<?php echo(date('Y-m-d')); ?>" placeholder="9999-99-99" required="required" />
            </div>
            <div class="form-group col-sm-6">
                <label>Keterangan <span class="tx-default">*</span></label>
				<textarea name="keterangan" class="form-control" placeholder="Type here..."></textarea>
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