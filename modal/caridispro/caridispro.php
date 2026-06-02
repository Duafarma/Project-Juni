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
?>
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
                <input type="text" name="kakun" id="kakun" class="form-control" value="<?php echo($data->cekcari($cari, '-', ' ')); ?>" placeholder="ex. Kata kunci" required="required" />
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
    <script type="text/javascript" src="<?php echo($data->sistem('url_sis').'/config/js/searching.js'); ?>"></script>
	<script type="text/javascript">
	$("#btncari").click(function(){
		var	kakun	= $("#kakun").val();
		//var	kakun	= $("#kakun").val();
		//var kata	= cekcari(kakun);
		var gabung	= "cari="+kata;
		window.location.href = "<?php echo("$sistem/$menu"); ?>"+gabung;
	});
    </script>