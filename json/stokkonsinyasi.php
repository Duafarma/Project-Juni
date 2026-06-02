<?php
	require_once('../config/connection/connection.php');
	require_once('../config/connection/security.php');
	require_once('../config/function/data.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$conn	= $base->open();
	$admin	= $secu->injection(@$_COOKIE['adminkuy']);
	$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
	
	// DataTables parameters
	$draw = $secu->injection($_POST['draw']);
	$row = $secu->injection($_POST['start']);
	$rowperpage = $secu->injection($_POST['length']);
	$columnIndex = $_POST['order'][0]['column'];
	$columnName = $_POST['columns'][$columnIndex]['data'];
	$columnSortOrder = $_POST['order'][0]['dir'];
	$searchValue = $secu->injection($_POST['search']['value']);
	
	// Search
	$searchQuery = "";
	if($searchValue != ''){
		$searchQuery = " AND (
			psk.no_bcode LIKE '%".$searchValue."%' OR
			psk.gudang LIKE '%".$searchValue."%' OR
			pro.kode_pro LIKE '%".$searchValue."%' OR
			pro.nama_pro LIKE '%".$searchValue."%' OR
			tfk.sj_tfk LIKE '%".$searchValue."%'
		)";
	}
	
	// Total records
	$qTotal = "SELECT COUNT(*) as total 
				FROM produk_stokdetail_konsinyasi psk
				LEFT JOIN produk pro ON psk.id_pro = pro.id_pro
				LEFT JOIN transaksi_faktur_konsinyasi tfk ON psk.id_tfk = tfk.id_tfk
				WHERE 1=1 ".$searchQuery;
	$recordsTotal = $conn->query($qTotal)->fetch(PDO::FETCH_ASSOC)['total'];
	
	// Fetch records
	$qData = "SELECT 
				psk.id_psd,
				psk.tgl_psd,
				psk.no_bcode,
				psk.tgl_expired,
				psk.masuk_psd,
				psk.keluar_psd,
				psk.sisa_psd,
				psk.gudang,
				psk.status_barang,
				pro.kode_pro,
				pro.nama_pro,
				tfk.sj_tfk
			FROM produk_stokdetail_konsinyasi psk
			LEFT JOIN produk pro ON psk.id_pro = pro.id_pro
			LEFT JOIN transaksi_faktur_konsinyasi tfk ON psk.id_tfk = tfk.id_tfk
			WHERE 1=1 ".$searchQuery."
			ORDER BY psk.tgl_psd DESC
			LIMIT ".$row.",".$rowperpage;
	
	$records = $conn->query($qData);
	
	$dataArr = array();
	$no = $row + 1;
	
	while($record = $records->fetch(PDO::FETCH_ASSOC)){
		$tgl_psd = date('d/m/Y', strtotime($record['tgl_psd']));
		$tgl_expired = date('d/m/Y', strtotime($record['tgl_expired']));
		
		$status_badge = '';
		if($record['status_barang'] == 'active'){
			$status_badge = '<span class="label label-success">Active</span>';
		} else {
			$status_badge = '<span class="label label-danger">Inactive</span>';
		}
		
		$dataArr[] = array(
			"no" => $no,
			"tgl_psd" => $tgl_psd,
			"nomor_faktur" => $record['sj_tfk'] ? $record['sj_tfk'] : '-',
			"kode_produk" => $record['kode_pro'] ? $record['kode_pro'] : '-',
			"nama_produk" => $record['nama_pro'] ? $record['nama_pro'] : '-',
			"no_bcode" => $record['no_bcode'] ? $record['no_bcode'] : '-',
			"tgl_expired" => $tgl_expired,
			"gudang" => $record['gudang'] ? $record['gudang'] : '-',
			"masuk_psd" => number_format($record['masuk_psd'], 0, ',', '.'),
			"keluar_psd" => number_format($record['keluar_psd'], 0, ',', '.'),
			"sisa_psd" => number_format($record['sisa_psd'], 0, ',', '.'),
			"status_barang" => $status_badge
		);
		$no++;
	}
	
	$response = array(
		"draw" => intval($draw),
		"recordsTotal" => $recordsTotal,
		"recordsFiltered" => $recordsTotal,
		"data" => $dataArr
	);
	
	$conn = $base->close();
	echo json_encode($response);
?>
