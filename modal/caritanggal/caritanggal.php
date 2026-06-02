<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$conn	= $base->open();
	$sistem	= $data->sistem('url_sis');
	$menu	= $secu->injection(@$_POST['menu']);
	$cari	= $secu->injection(@$_POST['cari']);
	$pecah	= explode('_', $cari);
?>
    <link rel="stylesheet" href="<?php echo("$sistem/zebrapicker/zebra_datepicker.min.css"); ?>" type="text/css" />
    <div class="modal-header">
        <h6 class="modal-title" id="exampleModalLabel">Pencarian</h6>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
        </button>
    </div>
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-12">
                <label>Kata Kunci <span class="tx-danger">*</span></label>
                <input type="text" name="kakun" id="kakun" class="form-control" value="<?php echo($data->cekcari($pecah[0], '-', ' ')); ?>" placeholder="Type here..." required="required" />
            </div>
        </div>
        <div class="row">
            <div class="form-group col-md-6">
                <label>Periode I <span class="tx-danger">*</span></label>
                <input type="text" name="tgl1" id="tgl1" class="tahunbulan fortgl zdatepicker" value="<?php echo(@$pecah[1]); ?>" placeholder="9999-99-99" required="required" />
            </div>
            <div class="form-group col-md-6">
                <label>Priode II <span class="tx-danger">*</span></label>
                <input type="text" name="tgl2" id="tgl2" class="tahunbulan fortgl zdatepicker" value="<?php echo(@$pecah[2]); ?>" placeholder="9999-99-99" required="required" />
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Batal</button>
        <button type="button" id="btncari" class="btn btn-dark btn-xs">Cari</button>
        <div id="imgloading"></div>
    </div>
<?php
	$conn	= $base->close();
?>
	<script type="text/javascript" src="<?php echo("$sistem/zebrapicker/zebra_datepicker.min.js"); ?>"></script>
    <script type="text/javascript" src="<?php echo("$sistem/config/js/searching.js"); ?>"></script>
	<script type="text/javascript">
	$(".fortgl").mask("9999-99-99");
	$('.zdatepicker').Zebra_DatePicker({
		format: 'Y-m-d',
	});
	$("#btncari").click(function(){
		var	kata	= $("#kakun").val();
		//var	kakun	= $("#kakun").val();
		//var kata	= cekcari(kakun);
		var tgl1	= $("#tgl1").val();
		var tgl2	= $("#tgl2").val();
		var gabung	= "cari="+kata+"_"+tgl1+"_"+tgl2;
		window.location.href = "<?php echo("$sistem/$menu/"); ?>"+gabung;
	});
    </script>