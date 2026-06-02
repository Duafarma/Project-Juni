# Histori Retur Konsinyasi - Dokumentasi

## Ringkasan
Fitur untuk melihat dan mencetak histori/riwayat pengembalian barang konsinyasi yang tidak terjual dari outlet ke gudang.

## File yang Dibuat

### 1. Content (Halaman Utama)
**File:** `content/hretur/hretur.php`
- **Fungsi:** Menampilkan daftar histori retur konsinyasi
- **Fitur:**
  - Tabel daftar semua transaksi retur
  - Kolom: No. Retur, Tanggal, No. Faktur, Outlet, Total Item, Total Qty, Status, Aksi
  - Search box untuk mencari berdasarkan No. Retur, Outlet, atau Faktur
  - Tombol detail untuk melihat rincian retur
  - Link ke faktur konsinyasi terkait

### 2. Modal Detail
**File:** `modal/returkonsinyasi/detail.php`
- **Fungsi:** Menampilkan detail lengkap transaksi retur dalam modal
- **Fitur:**
  - Header info: No. Retur, Tanggal, Status, Faktur, Outlet, Keterangan
  - Summary cards: Total Item dan Total Quantity
  - Tabel detail barang: Kode, Nama, Qty Retur, Sisa Sebelum, Sisa Sesudah
  - Tombol cetak untuk membuat laporan PDF
  - Desain gradient (purple) sesuai tema konsinyasi

### 3. Laporan Cetak
**File:** `laporan/retur_konsinyasi.php`
- **Fungsi:** Generate dokumen cetak untuk bukti retur
- **Fitur:**
  - Header: BUKTI RETUR KONSINYASI + No. Retur
  - Info transaksi lengkap (2 kolom)
  - Tabel detail barang dengan total
  - Summary box: Total Item & Quantity
  - Area tanda tangan (Outlet & Gudang)
  - Print-friendly design
  - Timestamp cetak

### 4. Routing
**File:** `config/frame/content.php` (modified)
- **Penambahan:**
  ```php
  case "hretur":
      require_once("content/hretur/hretur.php");
  break;
  ```

### 5. SQL Helper
**File:** `sql/add_menu_hretur.sql`
- **Fungsi:** Template untuk menambahkan menu ke database
- **Catatan:** Perlu disesuaikan dengan struktur database yang ada

## Database Tables yang Digunakan

### Tabel Utama
1. **transaksi_retur_konsinyasi** (header)
   - id_trk, no_retur, tgl_retur, id_tfk, id_out
   - total_item, total_qty, keterangan, status_trk

2. **transaksi_retur_konsinyasi_detail** (detail items)
   - id_trkd, id_trk, id_tfd, id_psd, id_pro
   - qty_retur, qty_sisa_sebelum, qty_sisa_sesudah

### Relasi
- JOIN transaksi_faktur_konsinyasi (untuk data faktur)
- JOIN outlet (untuk data outlet)
- JOIN produk (untuk data produk)

## Query Patterns

### List Histori
```sql
SELECT 
    trk.*,
    tfk.kode_tfk,
    outl.nama_out
FROM transaksi_retur_konsinyasi trk
LEFT JOIN transaksi_faktur_konsinyasi tfk ON trk.id_tfk = tfk.id_tfk
LEFT JOIN outlet outl ON trk.id_out = outl.id_out
ORDER BY trk.tgl_retur DESC, trk.created_at DESC
```

### Detail Retur
```sql
-- Header
SELECT trk.*, tfk.kode_tfk, outl.nama_out, outl.kode_out
FROM transaksi_retur_konsinyasi trk
LEFT JOIN transaksi_faktur_konsinyasi tfk ON trk.id_tfk = tfk.id_tfk
LEFT JOIN outlet outl ON trk.id_out = outl.id_out
WHERE trk.id_trk = :id_trk

-- Items
SELECT trkd.*, pro.nama_pro, pro.kode_pro
FROM transaksi_retur_konsinyasi_detail trkd
LEFT JOIN produk pro ON trkd.id_pro = pro.id_pro
WHERE trkd.id_trk = :id_trk
ORDER BY trkd.id_trkd
```

## URL Routes

### Halaman Utama
- **List:** `sistem.php?menu=hretur`
- **Search:** `sistem.php?menu=hretur&cari=[keyword]`

### AJAX Endpoints
- **Detail Modal:** `modal/returkonsinyasi/detail.php` (POST: id_trk)
- **Cetak:** `laporan/retur_konsinyasi.php?id=[id_trk]`

## Fitur Search
- Kolom yang dicari: `no_retur`, `kode_tfk`, `nama_out`
- Method: LIKE dengan wildcard (%keyword%)
- Binding: PDO prepared statement untuk keamanan
- Trigger: Enter key di search box

## UI/UX Design

### Warna & Styling
- **Header table:** Gradient purple (#667eea → #764ba2)
- **Badge:** 
  - Info (blue) untuk Total Item
  - Primary (purple) untuk Total Qty
  - Success (green) untuk Status
- **Cards:** Border kiri berwarna sesuai tema
- **Modal:** Header gradient, body dengan spacing optimal

### Responsif
- Table: `table-responsive` wrapper
- Modal: `modal-lg` untuk detail lengkap
- Print: Media query `@media print` untuk hide elemen `.no-print`

## Integrasi dengan Sistem Lain

### Link ke Faktur Konsinyasi
```php
<a href="<?php echo "$sistem/fsalesk/v/".base64_encode($hasil['id_tfk']); ?>" target="_blank">
    <?php echo $hasil['kode_tfk']; ?>
</a>
```
- Opens in new tab
- Base64 encoded ID untuk keamanan

### Relasi dengan Menu Lain
1. **Faktur Konsinyasi** → Lihat detail konsinyasi asal
2. **Retur Konsinyasi** → Menu untuk melakukan retur
3. **Stok Konsinyasi** → Lihat stok yang tersisa

## Testing Checklist

- [ ] List histori menampilkan semua retur yang sudah dilakukan
- [ ] Search berfungsi dengan keyword: no. retur, faktur, outlet
- [ ] Tombol detail membuka modal dengan data lengkap
- [ ] Link ke faktur konsinyasi berfungsi (new tab)
- [ ] Cetak menghasilkan dokumen yang rapi
- [ ] Badge dan warna sesuai desain
- [ ] Responsive di berbagai ukuran layar
- [ ] Data qty sebelum/sesudah sesuai dengan transaksi

## Penambahan Menu ke Sidebar

### Langkah Manual
1. Login ke phpMyAdmin atau database tool
2. Cari menu induk (Konsinyasi/Penjualan):
   ```sql
   SELECT * FROM menu WHERE nama_menu LIKE '%konsinyasi%';
   ```
3. Ambil `id_menu` dari hasil query
4. Insert submenu baru:
   ```sql
   INSERT INTO sub_menu (id_smu, id_menu, nama_smu, url_smu, urutan_smu) 
   VALUES ('generated_id', 'ID_MENU_DARI_LANGKAH_2', 'Histori Retur', 'hretur', 99);
   ```
5. Tambahkan role untuk semua admin:
   ```sql
   INSERT INTO role_menu (id_rme, id_adm, id_smu)
   SELECT CONCAT('rme_hretur_', id_adm), id_adm, 'generated_id'
   FROM administrator;
   ```

### Verifikasi
- Refresh halaman sistem
- Cek menu sidebar apakah "Histori Retur" muncul
- Test klik menu menuju `sistem.php?menu=hretur`

## Best Practices

### Security
- ✅ PDO prepared statements untuk semua query
- ✅ Parameter binding untuk mencegah SQL injection
- ✅ Base64 encoding untuk ID di URL
- ✅ Injection filter via `$secu->injection()`

### Performance
- Hanya load data yang diperlukan (tidak select semua kolom)
- LEFT JOIN untuk optional relations
- ORDER BY dengan index (tgl_retur, created_at)

### Maintainability
- Komentar yang jelas di setiap section
- Struktur HTML yang rapi dan indented
- Query SQL yang readable dengan formatting
- Konsisten dengan design pattern sistem lain

## Troubleshooting

### Menu tidak muncul di sidebar
- Periksa tabel `sub_menu` apakah data sudah insert
- Periksa tabel `role_menu` apakah admin punya akses
- Refresh browser atau clear cache

### Data tidak muncul
- Periksa apakah tabel `transaksi_retur_konsinyasi` sudah ada data
- Cek query di file untuk syntax error
- Debug dengan print_r() atau var_dump()

### Modal tidak load
- Periksa path AJAX: `modal/returkonsinyasi/detail.php`
- Cek console browser untuk error JavaScript
- Verifikasi parameter POST `id_trk` terkirim

### Print tidak rapi
- Gunakan browser Chrome untuk hasil terbaik
- Atur margin cetak di print settings
- Gunakan portrait orientation

## Future Enhancements (Opsional)

1. **Export Excel/PDF** - Export list histori ke file
2. **Filter Tanggal** - Filter berdasarkan range tanggal retur
3. **Filter Status** - Filter berdasarkan status_trk
4. **Grafik** - Statistik retur per bulan/outlet
5. **Email Notification** - Kirim email saat retur dilakukan
6. **Approval System** - Sistem persetujuan retur oleh manager
7. **Return Reversal** - Kemampuan untuk membatalkan retur
8. **Batch Print** - Cetak multiple bukti retur sekaligus

## Changelog

### Version 1.0 (Initial Release)
- ✅ Halaman list histori retur
- ✅ Modal detail retur dengan info lengkap
- ✅ Laporan cetak bukti retur
- ✅ Search functionality
- ✅ Link integrasi ke faktur konsinyasi
- ✅ Responsive design
- ✅ Security dengan prepared statements
