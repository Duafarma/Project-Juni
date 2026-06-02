# UPDATE: FITUR KONSINYASI LEBIH MUDAH! 🎉

## ✨ Perubahan Terbaru

Sistem konsinyasi sudah diupdate menjadi **JAUH LEBIH MUDAH** dan **INTUITIF**!

### 🔝 Perubahan Utama

#### **1. Section Konsinyasi Dipindah ke ATAS**
- Sekarang pilihan konsinyasi ada di **PALING ATAS** form
- Background biru cerah dengan icon yang jelas
- User langsung tahu: "Ini konsinyasi atau bukan?"

#### **2. Auto-Fill Outlet & Nomor Faktur** ⚡
Begitu pilih faktur konsinyasi:
- ✅ **Outlet LANGSUNG TERISI** otomatis
- ✅ **Nomor Faktur LANGSUNG TERISI** otomatis (format: PNJ/[nomor_konsinyasi])
- ✅ Field di-disable agar tidak bisa diubah
- ✅ Muncul label "(Otomatis dari konsinyasi)" berwarna biru

#### **3. Stok Item Otomatis dari Konsinyasi**
- Ketika tambah item, produk **HANYA dari konsinyasi terpilih**
- Tidak bingung lagi stok mana yang dipakai

---

## 📖 Cara Menggunakan (SUPER MUDAH!)

### Step 1: Buka Form Input Faktur
Menu: **Penjualan → Faktur Penjualan → Input Data**

### Step 2: Pilih Mode (Paling Atas)
Anda akan lihat kotak biru besar:

**"Apakah faktur ini dari stok konsinyasi?"**

- 🔘 **TIDAK** - Penjualan Normal (Stok Biasa)
- 🔘 **YA** - Dari Konsinyasi

### Step 3: Jika Pilih "YA"
Akan muncul dropdown dalam kotak putih:

**"Pilih Faktur Konsinyasi:"**

Pilih salah satu, format tampilan:
```
FAK/KONSI/001/2026 - Nama Outlet (05/02/2026)
```

### Step 4: Otomatis Terisi! ✨
Begitu pilih, LANGSUNG:
- ✅ Field **Outlet** terisi + label "(Otomatis dari konsinyasi)"
- ✅ Field **Nomor Faktur** terisi + label "(Otomatis dari konsinyasi)"
- ✅ Field di-lock (background abu-abu)
- ✅ Muncul notifikasi sukses

### Step 5: Isi Data Lain
Tinggal isi:
- Nomor SJ
- Tanggal Faktur
- Nomor PO
- Tanggal PO
- Jatuh Tempo
- CCP, Cito, dll

### Step 6: Simpan
Klik **Simpan** → Masuk halaman Item

### Step 7: Tambah Item
- Klik **"Pilih"** untuk pilih produk
- **OTOMATIS** produk hanya dari konsinyasi yang dipilih
- Pilih produk → Isi jumlah → Tambah
- Ulangi sampai semua item masuk

### Step 8: Simpan Transaksi
- Review data
- Klik **Simpan**
- ✅ **Stok konsinyasi berkurang otomatis!**

---

## 🎯 Keuntungan Update Ini

### Untuk User:
1. **Tidak Bingung Lagi** - Pilihan konsinyasi ada di atas, jelas
2. **Lebih Cepat** - Outlet & nomor faktur auto-fill
3. **Tidak Salah Input** - Field di-lock, tidak bisa diubah
4. **Visual Jelas** - Ada label biru "(Otomatis dari konsinyasi)"

### Untuk System:
1. **Tracking Lebih Baik** - Data lebih akurat
2. **Stok Terpisah** - Konsinyasi vs normal jelas
3. **Audit Trail** - Semua tercatat lengkap

---

## 🖼️ Preview Tampilan

### Sebelum Pilih Konsinyasi:
```
┌─────────────────────────────────────────────────┐
│ Apakah faktur ini dari stok konsinyasi?         │
│                                                   │
│ ○ TIDAK - Penjualan Normal (Stok Biasa)         │
│ ○ YA - Dari Konsinyasi                          │
└─────────────────────────────────────────────────┘

[Nomor SJ]  [Outlet ▼]  [Nomor Faktur]  [Tgl Faktur]
```

### Setelah Pilih "YA":
```
┌─────────────────────────────────────────────────┐
│ Apakah faktur ini dari stok konsinyasi?         │
│                                                   │
│ ○ TIDAK - Penjualan Normal (Stok Biasa)         │
│ ● YA - Dari Konsinyasi                          │
│                                                   │
│  ┌───────────────────────────────────────────┐  │
│  │ Pilih Faktur Konsinyasi:                  │  │
│  │ [FAK/KONSI/001/2026 - Apotek A (05/...)▼]│  │
│  │ ℹ Setelah pilih, Outlet dan Nomor Faktur │  │
│  │   akan terisi otomatis                    │  │
│  └───────────────────────────────────────────┘  │
└─────────────────────────────────────────────────┘

[Nomor SJ]  
[Outlet: Apotek A 🔒 (Otomatis dari konsinyasi)]  
[Nomor Faktur: PNJ/FAK/KONSI/001/2026 🔒 (Otomatis)]
```

---

## ⚠️ Catatan Penting

### Field yang Di-Lock Saat Mode Konsinyasi:
1. **Outlet** - Diambil dari faktur konsinyasi
2. **Nomor Faktur** - Auto-generate dengan format PNJ/[kode_konsinyasi]

### Field yang Masih Manual:
- Nomor SJ
- Tanggal (semua)
- Nomor PO
- Jatuh Tempo
- CCP, Cito, dll

### Jika Ganti Pilihan:
- Ganti ke konsinyasi lain → Outlet & Nomor Faktur update otomatis
- Ganti ke "TIDAK" → Field unlock, bisa input manual lagi

---

## 🆘 Troubleshooting

**Q: Dropdown konsinyasi tidak muncul?**
A: Pastikan sudah pilih radio button **"YA"**

**Q: Outlet tidak terisi otomatis?**
A: Pastikan sudah **pilih faktur konsinyasi** dari dropdown

**Q: Nomor faktur formatnya salah?**
A: Format otomatis: `PNJ/[nomor_konsinyasi]`. Ini normal dan sesuai standar.

**Q: Mau ubah outlet tapi disabled?**
A: Ini by design. Outlet harus sesuai konsinyasi. Kalau mau beda outlet, pilih konsinyasi yang lain atau mode "TIDAK".

**Q: Bisa edit nomor faktur yang auto-generate?**
A: Tidak bisa. Ini untuk menjaga konsistensi. Nomor faktur akan sesuai dengan konsinyasi.

---

## 🔄 Perbandingan Sebelum & Sesudah

### ❌ Cara Lama (Ribet):
1. Scroll ke bawah cari section konsinyasi
2. Centang "Ya"
3. Pilih faktur konsinyasi
4. Balik ke atas
5. Pilih outlet **MANUAL** (rawan salah!)
6. Ketik nomor faktur **MANUAL** (rawan salah!)
7. Isi data lain
8. Simpan

### ✅ Cara Baru (MUDAH):
1. Langsung lihat pilihan di atas
2. Centang "YA"
3. Pilih faktur konsinyasi
4. **OTOMATIS** outlet & nomor faktur terisi!
5. Isi data lain
6. Simpan

**Hemat 3 langkah + Tidak salah input!** 🎉

---

## 📊 Flow Diagram

```
START
  ↓
[Buka Form Input Faktur]
  ↓
┌─────────────────────────┐
│ Konsinyasi atau Normal? │ ← DECISION POINT (PALING ATAS)
└─────────────────────────┘
  ↓              ↓
[TIDAK]        [YA]
  ↓              ↓
Input          [Pilih Faktur Konsinyasi]
Manual           ↓
  ↓            [AUTO-FILL]
  ↓            • Outlet ✓
  ↓            • Nomor Faktur ✓
  ↓              ↓
  └──────────────┘
        ↓
  [Isi Data Lain]
        ↓
    [Simpan]
        ↓
  [Tambah Item]
        ↓
   ✓ SELESAI
```

---

## 🎓 Tips & Best Practice

1. **Selalu Cek di Atas Dulu**
   - Sebelum isi apa-apa, tentukan dulu: Konsinyasi atau Normal?
   
2. **Biarkan Auto-Fill Bekerja**
   - Jangan coba-coba ubah field yang di-lock
   - Percaya pada system
   
3. **Cek Notifikasi**
   - Akan muncul notifikasi sukses saat data terisi
   - Jika tidak muncul, coba pilih ulang
   
4. **Review Sebelum Simpan**
   - Pastikan outlet & nomor faktur sudah benar
   - Cek semua data sebelum simpan

---

## 📞 Support

Jika ada masalah atau pertanyaan:
- **IT Support**: ext. XXX
- **Developer**: ext. XXX
- **Email**: it@duafarma.com

---

**Update Version**: 2.0  
**Release Date**: 5 Februari 2026  
**Breaking Changes**: Tidak ada, backward compatible  
**Migration Required**: Tidak perlu

---

💡 **Pro Tip**: Bookmark halaman ini untuk referensi cepat!
