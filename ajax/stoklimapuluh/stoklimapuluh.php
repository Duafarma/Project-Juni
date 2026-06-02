<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	require_once('../../config/function/paging.php');
	$base	= new DB;
	$secu	= new Security;
	$data	= new Data;
	$paging	= new Paging;
	$conn	= $base->open();
	//$limit	= $data->sistem('limit_stok');
	$jumlah	= $conn->query("SELECT COUNT(B.id_pro) AS total FROM(SELECT id_pro, SUM(sisa_psd) AS jumlah FROM produk_stokdetail GROUP BY id_pro) AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro LEFT JOIN kategori_produk AS C ON B.id_kpr=C.id_kpr LEFT JOIN satuan_produk AS D ON B.id_spr=D.id_spr")->fetch(PDO::FETCH_ASSOC);
	if(empty($jumlah['total'])){
		$tabel	= '<h6 class="text text-danger"><i>Tidak Ada Produk</i></h6>';	
	} else {
		$no		= 1;
		$tabel	= '<h6>Produk Stok Tipis</h6><table class="table table-bordered table-sm"><thead><tr><th><center>No.</center></th><th>Produk</th><th>Sediaan</th><th><div align="right">Total Stok</div></th><th><div align="right">Minimal Stok</div></th><th>Satuan Qty.</th><th><center>Aksi</center></th></tr></thead><tbody>';
		$master	= $conn->prepare("SELECT A.jumlah, B.id_pro, B.nama_pro, B.berat_pro, B.minstok_pro, C.nama_kpr, C.satuan_kpr, D.nama_spr FROM(SELECT id_pro, SUM(sisa_psd) AS jumlah FROM produk_stokdetail GROUP BY id_pro) AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro LEFT JOIN kategori_produk AS C ON B.id_kpr=C.id_kpr LEFT JOIN satuan_produk AS D ON B.id_spr=D.id_spr ORDER BY A.jumlah ASC");
		$master->execute();
		while($hasil	= $master->fetch(PDO::FETCH_ASSOC)){
			$id_pro		= htmlspecialchars($hasil['id_pro']);
			$tabel	.= '<tr>
				<td><center>'.$no.'</center></td>
				<td>'.$hasil['nama_pro'].'</td>
				<td>'.$hasil['nama_kpr'].' ('.$hasil['berat_pro'].' '.$hasil['nama_spr'].')</td>
				<td><div align="right"><strong class="text-danger">'.$data->angka($hasil['jumlah']).'</strong></div></td>
				<td>
					<input type="number" class="form-control form-control-sm minstok-input text-right" 
						id="minstok_'.$id_pro.'" 
						data-id="'.$id_pro.'" 
						value="'.(int)$hasil['minstok_pro'].'" 
						min="0" style="width:90px; display:inline-block;">
				</td>
				<td>'.$hasil['satuan_kpr'].'</td>
				<td><center>
					<button class="btn btn-sm btn-primary btn-save-minstok" data-id="'.$id_pro.'" title="Simpan minimal stok">
						<i class="fa fa-save"></i> Simpan
					</button>
				</center></td>
			</tr>';
			$no++;
		}
		$tabel	.= '</tbody></table>';
		$tabel	.= '<script>
(function(){
	$(document).off("click", ".btn-save-minstok").on("click", ".btn-save-minstok", function(){
		var btn		= $(this);
		var id_pro	= btn.data("id");
		var minstok	= $("#minstok_" + id_pro).val();
		btn.prop("disabled", true).html(\'<i class="fa fa-spinner fa-spin"></i>\');
		$.ajax({
			url		: usuper + "/ajax/stoklimapuluh/updateminstok.php",
			type	: "POST",
			data	: { id_pro: id_pro, minstok: minstok },
			dataType: "json",
			success	: function(res){
				if(res.status === "success"){
					btn.html(\'<i class="fa fa-check"></i> Tersimpan\').addClass("btn-success").removeClass("btn-primary");
					setTimeout(function(){
						btn.html(\'<i class="fa fa-save"></i> Simpan\').addClass("btn-primary").removeClass("btn-success").prop("disabled", false);
					}, 2000);
				} else {
					alert("Gagal: " + res.message);
					btn.html(\'<i class="fa fa-save"></i> Simpan\').prop("disabled", false);
				}
			},
			error: function(){
				alert("Terjadi kesalahan, coba lagi.");
				btn.html(\'<i class="fa fa-save"></i> Simpan\').prop("disabled", false);
			}
		});
	});
})();
</script>';
	}
	$conn	= $base->close();

	$json	= array("tabel" => $tabel);
	http_response_code(200);
	header("Access-Control-Allow-Origin: *");
	header("Content-type: application/json; charset=utf-8");
	//header('content-type: application/json');
	//header('Content-type: text/html; charset=UTF-8');
	echo(json_encode($json));
?>