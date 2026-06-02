<!DOCTYPE html>
<html>
<?php
	header("Content-Type: application/force-download");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Template"); 
	header("content-disposition:attachment; filename=template_upload_pembayaran_sales.xls");
?>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Template Upload Pembayaran Sales</title>
</head>
<body>
	<table>
		<tr>
			<th colspan="6"><center><b>TEMPLATE UPLOAD PEMBAYARAN SALES</b></center></th>
		</tr>
		<tr>
			<td colspan="6"></td>
		</tr>
		<tr>
			<td colspan="6"><b>Format Data:</b></td>
		</tr>
		<tr>
			<td colspan="6">- Kode Faktur: Nomor faktur seperti yang terlihat di aplikasi (contoh: 0015.01.01/FKT/AP/III/26) - WAJIB</td>
		</tr>
		<tr>
			<td colspan="6">- Bank: Nama bank (opsional)</td>
		</tr>
		<tr>
			<td colspan="6">- No Rekening: Nomor rekening (opsional)</td>
		</tr>
		<tr>
			<td colspan="6">- Nama: Nama penerima/pengirim (opsional)</td>
		</tr>
		<tr>
			<td colspan="6">- Jumlah Bayar: Nominal pembayaran dalam angka (wajib)</td>
		</tr>
		<tr>
			<td colspan="6">- Tanggal: Format YYYY-MM-DD atau DD/MM/YYYY (opsional, default hari ini)</td>
		</tr>
		<tr>
			<td colspan="6"></td>
		</tr>
	</table>
	
	<table border="1">
		<thead>
			<tr style="background-color: #4CAF50; color: white;">
				<th><b>Kode Faktur</b></th>
				<th><b>Bank</b></th>
				<th><b>No Rekening</b></th>
				<th><b>Nama</b></th>
				<th><b>Jumlah Bayar</b></th>
				<th><b>Tanggal</b></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>0015.01.01/FKT/AP/III/26</td>
				<td>BCA</td>
				<td>1234567890</td>
				<td>Toko ABC</td>
				<td>1000000</td>
				<td>2026-03-31</td>
			</tr>
			<tr>
				<td>0016.01.01/FKT/AP/III/26</td>
				<td>Mandiri</td>
				<td>0987654321</td>
				<td>Toko XYZ</td>
				<td>500000</td>
				<td>2026-03-31</td>
			</tr>
			<tr>
				<td>0017.01.01/FKT/AP/III/26</td>
				<td>BNI</td>
				<td>1122334455</td>
				<td>Apotek Sehat</td>
				<td>2500000</td>
				<td>31/03/2026</td>
			</tr>
		</tbody>
	</table>
	
	<table>
		<tr>
			<td colspan="6"></td>
		</tr>
		<tr>
			<td colspan="6"><b>Catatan:</b></td>
		</tr>
		<tr>
			<td colspan="6">1. Hapus baris contoh di atas sebelum mengisi data</td>
		</tr>
		<tr>
			<td colspan="6">2. Jangan mengubah urutan kolom header</td>
		</tr>
		<tr>
			<td colspan="6">3. Pastikan Kode Faktur sesuai dengan yang ada di sistem (copy paste dari aplikasi)</td>
		</tr>
		<tr>
			<td colspan="6">4. Jumlah bayar tidak boleh melebihi sisa tagihan</td>
		</tr>
		<tr>
			<td colspan="6">5. Format tanggal: YYYY-MM-DD (contoh: 2026-03-31)</td>
		</tr>
	</table>
</body>
</html>
