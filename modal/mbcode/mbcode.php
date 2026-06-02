<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$tgl	= date('Y-m-d');
	$conn	= $base->open();
	$nomor	= $secu->injection($_POST['x']);
	$kode	= $secu->injection($_POST['y']);
?>
    <div class="modal-header">
        <h6 class="modal-title" id="exampleModalLabel">Input Data - Provinsi</h6>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
        </button>
    </div>
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-12">
			<table class="tabelmini">
            	<thead>
                	<tr>
                    	<th><center>No.</center></th>
                    	<th>Kode</th>
                    	<th><center>Tanggal</center></th>
                    </tr>
                </thead>
                <tbody>
				<?php
					$no		= 1;
					$master	= $conn->prepare("SELECT id_mbc, kode_mbc, tgl_mbc FROM master_batchcode WHERE tgl_mbc>:tgl ORDER BY tgl_mbc DESC");
					$master->bindParam(':tgl', $tgl, PDO::PARAM_STR);
					$master->execute();
					while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
				?>
                	<tr onclick="<?php echo("getmbcode('$nomor', '$hasil[id_mbc]', '$hasil[kode_mbc]', '$hasil[tgl_mbc]')"); ?>">
                    	<td><center><?php echo($no); ?></center></td>
                    	<td><?php echo($hasil['kode_mbc']); ?></td>
                    	<td><center><?php echo($hasil['tgl_mbc']); ?></center></td>
                    </tr>
				<?php $no++; } ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Close</button>
    </div>
<?php
	$conn	= $base->close();
?>