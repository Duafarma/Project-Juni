# CARA MENGGUNAKAN FITUR FAKTUR PENJUALAN DARI KONSINYASI

## 📋 Gambaran Umum

Sistem ini memungkinkan Anda membuat faktur penjualan menggunakan stok dari barang konsinyasi. Ketika barang konsinyasi dijual, stok akan otomatis dikurangi dari `produk_stokdetail_konsinyasi` dan tercatat dengan baik.

## ✅ Langkah-Langkah Penggunaan

### 1. **Buat Faktur Penjualan Baru**
   - Masuk ke menu: **Penjualan → Faktur Penjualan**
   - Klik tombol **Input Data** atau **Tambah Faktur**

### 2. **Pilih Opsi Konsinyasi**
   Di form input faktur, Anda akan melihat section:
   
   **"Apakah faktur dari stok konsinyasi?"**
   - ✅ **YA** - Pilih ini jika ingin menjual barang dari stok konsinyasi
   - ❌ **TIDAK** - Pilih ini untuk penjualan normal (stok biasa)

### 3. **Pilih Faktur Konsinyasi (Jika "YA")**
   Jika Anda memilih "YA", akan muncul dropdown:
   
   **"Pilih Faktur Konsinyasi"**
   - Dropdown ini menampilkan semua faktur konsinyasi yang masih aktif
   - Format tampilan: `Nomor Faktur - Nama Outlet (Tanggal)`
   - Pilih faktur konsinyasi yang ingin Anda gunakan
   - Stok akan diambil HANYA dari faktur konsinyasi yang dipilih

### 4. **Isi Data Faktur Seperti Biasa**
   - Nomor SJ, Nomor Faktur, Tanggal, dll
   - Pilih outlet
   - Isi data lainnya seperti biasa

### 5. **Klik Simpan**
   - System akan menyimpan faktur dengan penanda konsinyasi
   - Anda akan diarahkan ke halaman **Input Item**

### 6. **Tambah Item Produk**
   Ketika menambah item:
   - Klik tombol **"Pilih"** untuk memilih produk
   - **OTOMATIS**: System akan menampilkan produk dari stok konsinyasi yang dipilih
   - Pilih produk yang ingin dijual
   - Masukkan jumlah, harga, diskon
   - Klik **Tambah** untuk memasukkan ke keranjang

### 7. **Simpan Transaksi**
   - Review semua item yang sudah dipilih
   - Pastikan total sudah benar
   - Klik **Simpan** untuk memproses

### 8. **Stok Otomatis Berkurang**
   Setelah disimpan:
   - ✅ Stok di `produk_stokdetail_konsinyasi` BERKURANG otomatis
   - ✅ Tercatat dengan baik (keluar_psd bertambah, sisa_psd berkurang)
   - ✅ Data tersimpan di `transaksi_faktur` dengan flag `dari_konsinyasi = 'ya'`

## 🎯 Keuntungan Fitur Ini

1. **Tracking Jelas**: Tahu persis mana penjualan dari konsinyasi, mana dari stok normal
2. **Stok Terpisah**: Stok konsinyasi dan stok normal tidak tercampur
3. **Otomatis**: Pengurangan stok dilakukan otomatis oleh system
4. **Terekam Lengkap**: Semua transaksi tercatat lengkap di database

## 📊 Cara Cek Stok Konsinyasi

Untuk melihat sisa stok konsinyasi:
- Menu: **Inventory → Stok Konsinyasi**
- Atau gunakan menu yang sudah dibuat: **stokkonsinyasi**

## ⚠️ Hal yang Perlu Diperhatikan

1. **Dropdown Kosong?**
   - Pastikan ada faktur konsinyasi dengan status "Konsinyasi" atau "Sebagian"
   - Cek di menu **Faktur Konsinyasi** (fsalesk)

2. **Produk Tidak Muncul?**
   - Pastikan produk ada di faktur konsinyasi yang dipilih
   - Pastikan stok masih tersedia (sisa_psd > 0)

3. **Tidak Bisa Edit Setelah Disimpan?**
   - Ini normal untuk menjaga integritas data
   - Jika ada kesalahan, hubungi admin untuk koreksi

## 📝 Contoh Alur Lengkap

```
1. Buat Faktur Penjualan Baru
   ↓
2. Pilih: "Ya, dari konsinyasi"
   ↓
3. Pilih: Faktur Konsinyasi #FAK/KONSI/001/2026
   ↓
4. Isi data faktur (SJ, PO, dll)
   ↓
5. Simpan → Masuk halaman Item
   ↓
6. Klik "Pilih" → Muncul produk dari konsinyasi terpilih
   ↓
7. Pilih produk → Isi jumlah → Tambah
   ↓
8. Simpan Transaksi
   ↓
9. ✅ Selesai! Stok konsinyasi berkurang otomatis
```

## 🆘 Troubleshooting

**Q: Dropdown konsinyasi tidak muncul?**
A: Pastikan Anda memilih "YA" pada opsi konsinyasi

**Q: Produk tidak muncul saat pilih item?**
A: Cek apakah faktur konsinyasi yang dipilih benar-benar memiliki stok produk tersebut

**Q: Stok tidak berkurang?**
A: Hubungi admin/developer untuk cek log error

## 📞 Kontak Support

Jika ada masalah atau pertanyaan, hubungi:
- IT Support
- Developer System

---

**Versi**: 1.0  
**Terakhir Update**: 5 Februari 2026  
**Dibuat Untuk**: Sistem Inventory Management - DUA FARMA
