# UPDATE SISTEM KONSINYASI - TRACKING TERJUAL DAN SISA

## Perubahan Database

### Tabel: transaksi_fakturdetail_konsinyasi
Ditambahkan 2 kolom baru:
- `terjual_tfd` INT(11) - Jumlah yang sudah terjual
- `sisa_tfd` INT(11) - Sisa yang belum terjual

Script SQL: `sql/add_terjual_column.sql`

## Alur Sistem

### 1. Ketika Membuat Faktur Konsinyasi (fsalesk)
File: `modal/fsalesk/action.php`
- INSERT ke `transaksi_fakturdetail_konsinyasi` dengan:
  - `jumlah_tfd` = jumlah item konsinyasi
  - `terjual_tfd` = 0 (awal belum ada yang terjual)
  - `sisa_tfd` = jumlah_tfd (semua masih tersedia)

### 2. Ketika Menjual Item dari Konsinyasi (fsales)
File: `modal/fsales/action.php` (lines 248-320)

Ketika item terjual, sistem melakukan 3 update berurutan:

#### A. Update Stok Detail Konsinyasi
```php
UPDATE produk_stokdetail_konsinyasi 
SET keluar_psd = keluar_psd + :jumlah, 
    sisa_psd = sisa_psd - :jumlah 
WHERE id_psd = :kodestok
```

#### B. Update Detail Faktur Konsinyasi (BARU!)
```php
UPDATE transaksi_fakturdetail_konsinyasi 
SET terjual_tfd = terjual_tfd + :jumlah,
    sisa_tfd = sisa_tfd - :jumlah
WHERE id_tfk = :id_tfk_konsinyasi 
  AND id_psd = :kodestok
```

#### C. Update Status Faktur Konsinyasi
```php
// Cek total sisa
SELECT SUM(sisa_tfd) as total_sisa 
FROM transaksi_fakturdetail_konsinyasi 
WHERE id_tfk = :id_tfk_konsinyasi

// Update status berdasarkan sisa
IF total_sisa <= 0:
    status = 'Selesai'  // Semua item sudah terjual
ELSE:
    status = 'Sebagian' // Masih ada yang tersisa
```

## Tampilan

### Halaman View Faktur Konsinyasi
File: `content/fsalesk/view.php`

Tabel detail menampilkan 3 kolom tracking:
- **Jumlah** - Total item konsinyasi
- **Terjual** - Badge hijau menampilkan jumlah terjual
- **Sisa** - Badge biru (ada sisa) atau abu-abu (habis)

### Laporan PDF
File: `laporan/pdf_fsalesk.php`

Tabel PDF juga menampilkan:
- Kolom Qty (jumlah awal)
- Kolom Terjual (warna hijau, bold)
- Kolom Sisa (warna biru jika > 0, abu-abu jika 0, bold)

## Status Flow

```
┌──────────────┐
│  Konsinyasi  │ ← Initial (semua item masih utuh)
└──────┬───────┘
       │
       │ (Ada penjualan, tapi masih ada sisa)
       ▼
┌──────────────┐
│   Sebagian   │ ← Partial (sebagian terjual)
└──────┬───────┘
       │
       │ (Semua item habis terjual)
       ▼
┌──────────────┐
│   Selesai    │ ← Complete (semua terjual)
└──────────────┘
```

## Testing

### Test Case 1: Buat Konsinyasi Baru
1. Buat faktur konsinyasi dengan 2 item (masing-masing 10 pcs)
2. Verifikasi di database:
   - `jumlah_tfd` = 10
   - `terjual_tfd` = 0
   - `sisa_tfd` = 10
   - `status_tfk` = 'Konsinyasi'

### Test Case 2: Jual Sebagian
1. Buat faktur penjualan dari konsinyasi, ambil 5 pcs dari item pertama
2. Verifikasi:
   - Item 1: `terjual_tfd` = 5, `sisa_tfd` = 5
   - Item 2: `terjual_tfd` = 0, `sisa_tfd` = 10
   - `status_tfk` = 'Sebagian'

### Test Case 3: Jual Habis
1. Lanjutkan jual sisa 5 pcs item pertama dan 10 pcs item kedua
2. Verifikasi:
   - Item 1: `terjual_tfd` = 10, `sisa_tfd` = 0
   - Item 2: `terjual_tfd` = 10, `sisa_tfd` = 0
   - `status_tfk` = 'Selesai'

## File yang Dimodifikasi

1. ✅ `sql/add_terjual_column.sql` - Script ALTER TABLE
2. ✅ `add_terjual_column.php` - Script executor
3. ✅ `modal/fsalesk/action.php` - INSERT dengan terjual_tfd dan sisa_tfd
4. ✅ `modal/fsales/action.php` - UPDATE detail konsinyasi saat penjualan
5. ✅ `content/fsalesk/view.php` - Tampilan tabel dengan kolom baru
6. ✅ `laporan/pdf_fsalesk.php` - PDF dengan kolom Terjual dan Sisa

## Sinkronisasi Data

Sekarang ada 3 layer tracking:
1. **transaksi_faktur_konsinyasi** - Header (status: Konsinyasi/Sebagian/Selesai)
2. **transaksi_fakturdetail_konsinyasi** - Detail per item (terjual_tfd, sisa_tfd)
3. **produk_stokdetail_konsinyasi** - Stok fisik (keluar_psd, sisa_psd)

Ketiga layer ini selalu sinkron karena update dilakukan bersamaan dalam satu transaksi penjualan.
