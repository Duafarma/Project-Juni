<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item">Mitra</li>
                <li class="breadcrumb-item active" aria-current="page">Input Data Stockopname</li>
            </ol>
        </nav>
        <h4 class="content-title">Input Data - Stockopname Cendo</h4>
    </div>
</div>
<!--
<input type="hidden" name="caridata" id="caridata" value="-" readonly="readonly" />
<input type="hidden" name="halaman" id="halaman" value="1" readonly="readonly" />
<input type="hidden" name="maximal" id="maximal" value="15" readonly="readonly" />
-->
<form id="formtransaksi" action="#" method="post" autocomplete="off">
<input type="hidden" name="nmenu" id="nmenu" value="stockopname" readonly="readonly" />
<input type="hidden" name="nact" id="nact" value="input" readonly="readonly" />
<div class="content-body">
    <div class="component-section no-code">
        <div class="row">
             <div class="form-group col-sm-3">
                <label>Nama Produk <span class="tx-danger">*</span></label>
                <select name="id_pro" id="id_pro" class="form-control select2" onchange="" required="required">
                <option value="">-- Pilih  Produk--</option>
                <?php
                
                $master	= $conn->prepare("SELECT id_pro,nama_p, nama_pro FROM produk  ORDER BY nama_pro ASC"); 
                 // Static value
                $master->execute();
                  while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
                ?>
                 <option value="<?php echo($hasil['id_pro']); ?>"><?php echo($hasil['nama_pro'] ); ?></option>
                <?php } ?>
                </select>
            </div>
			<div class="form-group col-sm-3">
                <label>Nomor Batch <span class="tx-danger">*</span></label>
                <input type="text" name="no_bcode" class="form-control" placeholder="Type here..." required="required" />
            </div>
            <div class="col-sm-3 mg-t-10">
                <label>ED<span class="tx-danger">*</span></label>
                <input type="text" name="tgl_expired" class="form-control datepicker"  placeholder="9999-99-99" />
            </div>
			<div class="form-group col-sm-3">
                <label>qty so <span class="tx-danger">*</span></label>
                <input type="number" name="qty_so" class="form-control" placeholder="Type here..." required="required" />
            </div>
			
        </div>
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <div class="row">
            <div class="col-sm-12">
                <a href="" title="Batal"><button type="button" class="btn btn-secondary btn-xs">Batal</button></a>
                <button type="submit" id="bsave" class="btn btn-dark btn-xs">Simpan</button>
                <div id="imgloading"></div>
            </div>
		</div>
		</form>
    </div>
</div>