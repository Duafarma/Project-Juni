# Quick Start - Histori Retur Konsinyasi

## 🚀 Cara Menggunakan

### 1. Akses Menu
- Buka browser, navigasi ke sistem
- Di sidebar, cari menu **"Histori Retur"** (di bawah Konsinyasi/Penjualan)
- Atau akses langsung: `sistem.php?menu=hretur`

### 2. Melihat Daftar Histori
- Halaman akan menampilkan semua transaksi retur yang sudah dilakukan
- Informasi yang ditampilkan:
  - No. Retur (format: 0001/RTK/MM/YY)
  - Tanggal retur
  - No. Faktur Konsinyasi (klik untuk lihat faktur)
  - Nama Outlet
  - Total Item & Quantity
  - Status transaksi

### 3. Mencari Histori
- Gunakan search box di atas tabel
- Ketik: No. Retur, No. Faktur, atau Nama Outlet
- Tekan **Enter** untuk mencari
- Hasil akan difilter otomatis

### 4. Melihat Detail Retur
- Klik tombol **mata** (👁️) di kolom Aksi
- Modal akan muncul dengan detail lengkap:
  - Info transaksi (tanggal, outlet, faktur, keterangan)
  - Summary (total item & qty)
  - Detail barang yang diretur dengan qty sebelum/sesudah
- Klik **Tutup** untuk menutup modal

### 5. Mencetak Bukti Retur
- Di dalam modal detail, klik tombol **"Cetak"**
- Akan membuka halaman print preview di tab baru
- Klik tombol **"Cetak"** atau tekan Ctrl+P
- Pilih printer atau Save as PDF
- Dokumen berisi:
  - Header bukti retur
  - Detail transaksi lengkap
  - Tabel barang yang diretur
  - Area tanda tangan (Outlet & Gudang)

## 📊 Informasi Penting

### Status Badge
- **🟢 Success** - Transaksi berhasil/selesai

### Kolom Detail
- **Qty Retur** - Jumlah yang dikembalikan
- **Sisa Sebelum** - Qty di outlet sebelum retur
- **Sisa Sesudah** - Qty di outlet setelah retur (should be 0 if all returned)

### Link ke Faktur
- Klik nomor faktur untuk melihat detail konsinyasi asal
- Opens in new tab
- Dapat melihat semua item konsinyasi termasuk yang sudah terjual

## 🔧 Setup Menu (Jika Menu Belum Muncul)

### Opsi 1: Manual via Database
```sql
-- 1. Cari ID menu induk
SELECT id_menu, nama_menu FROM menu WHERE nama_menu LIKE '%konsinyasi%';

-- 2. Insert submenu (ganti [ID_MENU] dengan hasil langkah 1)
INSERT INTO sub_menu (id_smu, id_menu, nama_smu, url_smu, urutan_smu) 
VALUES (UUID(), '[ID_MENU]', 'Histori Retur', 'hretur', 99);

-- 3. Ambil id_smu yang baru dibuat
SELECT id_smu FROM sub_menu WHERE url_smu = 'hretur';

-- 4. Tambahkan role untuk admin (ganti [ID_SMU] dengan hasil langkah 3)
INSERT INTO role_menu (id_rme, id_adm, id_smu)
SELECT UUID(), id_adm, '[ID_SMU]'
FROM administrator;
```

### Opsi 2: Via PhpMyAdmin
1. Buka PhpMyAdmin
2. Pilih database
3. Browse tabel `menu` → cari menu "Konsinyasi" atau "Penjualan"
4. Catat `id_menu`
5. Browse tabel `sub_menu` → klik **Insert**
   - id_smu: (auto/manual)
   - id_menu: (dari langkah 4)
   - nama_smu: Histori Retur
   - url_smu: hretur
   - urutan_smu: 99
6. Browse tabel `role_menu` → klik **Insert** untuk setiap admin
   - id_rme: (auto/manual)
   - id_adm: (pilih admin)
   - id_smu: (dari langkah 5)

## ✅ Verifikasi Setup

### Cek Routing
File: `config/frame/content.php`
```php
case "hretur":
    require_once("content/hretur/hretur.php");
break;
```
✅ Sudah ditambahkan

### Cek Files
- ✅ `content/hretur/hretur.php` - Halaman utama
- ✅ `modal/returkonsinyasi/detail.php` - Modal detail
- ✅ `laporan/retur_konsinyasi.php` - Cetak laporan

### Test Akses
1. Akses URL langsung: `http://[domain]/sistem.php?menu=hretur`
2. Jika muncul halaman histori → **Setup Berhasil**
3. Jika error/blank → cek file path dan database

## 📞 Troubleshooting

### "Menu tidak muncul di sidebar"
- Periksa database: tabel `sub_menu` dan `role_menu`
- Clear browser cache (Ctrl+Shift+Del)
- Logout dan login kembali

### "Data tidak muncul"
- Pastikan sudah ada transaksi retur (via menu Retur Konsinyasi)
- Cek tabel `transaksi_retur_konsinyasi` ada data
- Periksa error log PHP

### "Modal tidak load"
- Buka Developer Tools (F12) → tab Console
- Lihat error JavaScript/AJAX
- Cek path file `modal/returkonsinyasi/detail.php`

### "Print tidak rapi"
- Gunakan browser Chrome/Edge (lebih bagus dari Firefox)
- Set margin: Default atau Custom (10mm semua sisi)
- Orientation: Portrait
- Scale: 100%

## 💡 Tips

1. **Search**: Gunakan sebagian kata untuk hasil lebih luas (contoh: "PIM" akan cari semua outlet PIM)
2. **Print**: Untuk hasil terbaik, simpan sebagai PDF dulu sebelum print fisik
3. **Archive**: Cetak dan simpan bukti retur untuk dokumentasi
4. **Cross-Reference**: Gunakan link ke faktur untuk verifikasi data konsinyasi

## 📱 Kompatibilitas
- ✅ Chrome/Edge (Recommended)
- ✅ Firefox
- ✅ Safari
- ✅ Mobile Browser (responsive)

## 🔐 Security
- Semua query menggunakan prepared statements
- Parameter di-filter dengan injection protection
- ID di URL di-encode dengan base64
- User harus login untuk akses

---

**Siap Digunakan!** 🎉

Jika ada pertanyaan atau butuh bantuan, hubungi developer.
