<?php
require_once('../../config/connection/connection.php');
require_once('../../config/connection/security.php');
require_once('../../config/function/data.php');

$secu	= new Security;
$base	= new DB;
$data	= new Data;
$conn	= $base->open();
$catat	= date('Y-m-d H:i:s');
$admin	= $secu->injection(@$_COOKIE['adminkuy']);
$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);

header('Content-Type: application/json');

// Validasi login
if($secu->validadmin($admin, $kunci)==false){
	echo json_encode(['status' => 'error', 'message' => 'Session login anda habis']);
	exit;
}

// Cek apakah ada file yang diupload
if (!isset($_FILES['file_excel']) || $_FILES['file_excel']['error'] !== UPLOAD_ERR_OK) {
	echo json_encode(['status' => 'error', 'message' => 'File tidak ditemukan atau error saat upload']);
	exit;
}

$file = $_FILES['file_excel']['tmp_name'];
$fileName = $_FILES['file_excel']['name'];
$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

// Validasi ekstensi file
if (!in_array($fileExt, ['xlsx', 'xls', 'csv'])) {
	echo json_encode(['status' => 'error', 'message' => 'Format file harus .xlsx, .xls, atau .csv']);
	exit;
}

$rows = [];

// Proses CSV
if ($fileExt == 'csv') {
	if (($handle = fopen($file, "r")) !== FALSE) {
		while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
			$rows[] = $data;
		}
		fclose($handle);
	} else {
		echo json_encode(['status' => 'error', 'message' => 'Gagal membaca file CSV']);
		exit;
	}
} else {
	// Proses Excel dengan PhpSpreadsheet
	$vendorPath = '../../vendor/autoload.php';
	if (!file_exists($vendorPath)) {
		echo json_encode([
			'status' => 'error', 
			'message' => 'Library PhpSpreadsheet belum terinstall. Gunakan file CSV atau jalankan: composer install'
		]);
		exit;
	}
	
	require_once($vendorPath);
	
	try {
		$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
		$worksheet = $spreadsheet->getActiveSheet();
		$rows = $worksheet->toArray();
	} catch (Exception $e) {
		echo json_encode(['status' => 'error', 'message' => 'Error membaca file Excel: ' . $e->getMessage()]);
		exit;
	}
}

// Proses data dari Excel/CSV
try {
	
	$success = 0;
	$errors = [];
	$row_num = 0;
	
	foreach ($rows as $key => $row) {
		$row_num = $key + 1;
		
		// Skip header row
		if ($key == 0) continue;
		
		// Skip empty rows
		if (empty(array_filter($row))) continue;
		
		// Data dari Excel
		// Format: Kode Faktur | Bank | No Rekening | Nama | Jumlah Bayar | Tanggal
		$kode_faktur = trim($row[0]);
		$bank = trim($row[1]);
		$norek = trim($row[2]);
		$nama = trim($row[3]);
		$jumlah_bayar = trim($row[4]);
		$tanggal = trim($row[5]);
		
		// Validasi data wajib
		if (empty($kode_faktur)) {
			$errors[] = "Baris $row_num: Kode Faktur tidak boleh kosong";
			continue;
		}
		
		if (empty($jumlah_bayar) || !is_numeric($jumlah_bayar)) {
			$errors[] = "Baris $row_num: Jumlah bayar harus berupa angka";
			continue;
		}
		
		// Cek apakah faktur ada di database berdasarkan kode_tfk (kode faktur user-friendly)
		$cek_faktur = $conn->prepare("SELECT id_tfk, total_tfk, status_tfk FROM transaksi_faktur WHERE kode_tfk = :faktur");
		$cek_faktur->bindParam(':faktur', $kode_faktur, PDO::PARAM_STR);
		$cek_faktur->execute();
		$faktur_data = $cek_faktur->fetch(PDO::FETCH_ASSOC);
		
		if (!$faktur_data) {
			$errors[] = "Baris $row_num: Faktur $kode_faktur tidak ditemukan";
			continue;
		}
		
		$id_tfk = $faktur_data['id_tfk'];
		
		// Cek total pembayaran yang sudah ada
		$cek_bayar = $conn->prepare("SELECT IFNULL(SUM(jumlah_pfk), 0) AS total_bayar FROM pembayaran_faktur WHERE id_tfk = :faktur");
		$cek_bayar->bindParam(':faktur', $id_tfk, PDO::PARAM_STR);
		$cek_bayar->execute();
		$bayar_data = $cek_bayar->fetch(PDO::FETCH_ASSOC);
		$total_bayar = $bayar_data['total_bayar'];
		
		$sisa = $faktur_data['total_tfk'] - ($total_bayar + $jumlah_bayar);
		
		// Validasi pembayaran tidak melebihi total
		if ($sisa < 0) {
			$errors[] = "Baris $row_num: Jumlah bayar melebihi sisa tagihan";
			continue;
		}
		
		// Format tanggal
		if (!empty($tanggal)) {
			// Coba parse berbagai format tanggal
			if (is_numeric($tanggal) && $fileExt != 'csv') {
				// Excel date serial number (hanya untuk file Excel)
				try {
					$tanggal = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($tanggal)->format('Y-m-d');
				} catch (Exception $e) {
					// Jika gagal convert, gunakan tanggal hari ini
					$tanggal = date('Y-m-d');
				}
			} else {
				// String date
				$parsed = strtotime($tanggal);
				$tanggal = $parsed ? date('Y-m-d', $parsed) : date('Y-m-d');
			}
		} else {
			$tanggal = date('Y-m-d');
		}
		
		// Generate ID pembayaran
		$id_bayar = $data->basecode('', 1, 'id_pfk', 'pembayaran_faktur');
		
		// Status faktur
		$status = ($sisa == 0) ? 'Lunas' : 'Bayar';
		
		// Insert pembayaran
		$insert = $conn->prepare("INSERT INTO pembayaran_faktur 
			(id_pfk, id_tfk, bank_pfk, norek_pfk, anam_pfk, jumlah_pfk, file_pfk, tgl_pfk, created_at, created_by, updated_at, updated_by) 
			VALUES (:id, :faktur, :bank, :norek, :nama, :bayar, '', :tgl, :catat, :admin, :catat, :admin)");
		
		$insert->bindParam(':id', $id_bayar, PDO::PARAM_STR);
		$insert->bindParam(':faktur', $id_tfk, PDO::PARAM_STR);
		$insert->bindParam(':bank', $bank, PDO::PARAM_STR);
		$insert->bindParam(':norek', $norek);
		$insert->bindParam(':nama', $nama);
		$insert->bindParam(':bayar', $jumlah_bayar, PDO::PARAM_INT);
		$insert->bindParam(':tgl', $tanggal, PDO::PARAM_STR);
		$insert->bindParam(':catat', $catat, PDO::PARAM_STR);
		$insert->bindParam(':admin', $admin, PDO::PARAM_STR);
		
		if ($insert->execute()) {
			// Update status faktur
			$update = $conn->prepare("UPDATE transaksi_faktur SET status_tfk = :status, updated_at = :catat, updated_by = :admin WHERE id_tfk = :faktur");
			$update->bindParam(':status', $status, PDO::PARAM_STR);
			$update->bindParam(':catat', $catat, PDO::PARAM_STR);
			$update->bindParam(':admin', $admin, PDO::PARAM_STR);
			$update->bindParam(':faktur', $id_tfk, PDO::PARAM_STR);
			$update->execute();
			
			// Insert riwayat
			$conn->query("INSERT INTO riwayat VALUES('', '$id_bayar', 'Pembayaran Outlet (Upload Excel)', 'Create', '', '$catat', '$admin')");
			
			$success++;
		} else {
			$errors[] = "Baris $row_num: Gagal menyimpan data";
		}
	}
	
	$conn = $base->close();
	
	// Response
	if ($success > 0 && empty($errors)) {
		echo json_encode([
			'status' => 'success',
			'message' => "Berhasil upload $success data pembayaran"
		]);
	} else if ($success > 0 && !empty($errors)) {
		echo json_encode([
			'status' => 'success',
			'message' => "Berhasil upload $success data. Ada " . count($errors) . " data yang gagal:\n" . implode("\n", array_slice($errors, 0, 5))
		]);
	} else {
		echo json_encode([
			'status' => 'error',
			'message' => "Gagal upload data:\n" . implode("\n", array_slice($errors, 0, 5))
		]);
	}
	
} catch (Exception $e) {
	echo json_encode([
		'status' => 'error',
		'message' => 'Error: ' . $e->getMessage()
	]);
}
?>
