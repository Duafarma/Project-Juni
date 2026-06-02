# Troubleshooting - Tombol Proses Retur Tidak Bisa Diklik

## Masalah yang Diperbaiki

### 1. **Context Issue di AJAX** ✅
**Problem:** `$(this).serialize()` di dalam callback AJAX tidak merujuk ke form
**Fix:** Simpan reference form ke variable `$form` sebelum AJAX

```javascript
var $form = $(this); // Simpan reference
$.ajax({
    data: $form.serialize() // Gunakan variable
});
```

### 2. **Validasi Keterangan** ✅
**Problem:** Tombol tetap disabled meskipun item sudah dipilih
**Fix:** Tambahkan validasi untuk textarea keterangan

```javascript
var keterangan = $('textarea[name="keterangan"]').val().trim();
var isValid = hasChecked && hasValidQty && keterangan.length > 0;
```

### 3. **Session Admin** ✅
**Problem:** Error jika $_SESSION['id_adm'] tidak ada
**Fix:** Fallback ke $_COOKIE['adminkuy']

```php
$admin = isset($_SESSION['id_adm']) ? $_SESSION['id_adm'] : 
         (isset($_COOKIE['adminkuy']) ? $_COOKIE['adminkuy'] : null);
```

### 4. **Console Debugging** ✅
**Added:** Console.log untuk tracking validation status

```javascript
console.log('Validation:', {
    hasChecked: hasChecked,
    hasValidQty: hasValidQty,
    keterangan: keterangan.length,
    isValid: isValid
});
```

## Cara Test

### Langkah 1: Buka Form Retur
1. Pergi ke menu **Retur Konsinyasi**
2. Klik tombol **Retur** pada salah satu faktur
3. Form input akan terbuka

### Langkah 2: Cek Console
1. Tekan **F12** untuk buka Developer Tools
2. Pilih tab **Console**
3. Lihat output validation setiap kali checkbox/qty/keterangan berubah

### Langkah 3: Aktifkan Tombol
1. **Centang** minimal 1 item (checkbox)
2. Pastikan **qty > 0** (otomatis terisi saat centang)
3. **Isi keterangan** (wajib diisi)
4. Tombol **"Proses Retur"** akan aktif (biru, tidak disabled)

### Langkah 4: Submit
1. Klik tombol **"Proses Retur"**
2. Konfirmasi SweetAlert muncul
3. Klik **"Ya, Proses!"**
4. Loading indicator muncul
5. Jika sukses: redirect ke list retur
6. Jika error: lihat console untuk detail

## Debug Checklist

### Jika Tombol Masih Disabled:

- [ ] **Cek Console** - Lihat output validation
  - `hasChecked` harus `true`
  - `hasValidQty` harus `true`
  - `keterangan` length harus `> 0`

- [ ] **Cek Checkbox** - Pastikan checkbox bisa dicentang
  ```javascript
  $('.item-check').length // Should be > 0
  $('.item-check:checked').length // Should be > 0 when checked
  ```

- [ ] **Cek Qty Input** - Pastikan value valid
  ```javascript
  $('.qty-input').each(function() {
      console.log($(this).val(), $(this).prop('disabled'));
  });
  ```

- [ ] **Cek Keterangan** - Pastikan textarea tidak kosong
  ```javascript
  $('textarea[name="keterangan"]').val().length // Should be > 0
  ```

### Jika Submit Gagal:

- [ ] **Cek Network Tab** - Lihat request/response
  - Method: POST
  - URL: modal/returkonsinyasi/action.php
  - Status: 200
  - Response: JSON dengan status/message

- [ ] **Cek Console Error** - Lihat error JavaScript
  - SyntaxError: Invalid JSON
  - TypeError: Cannot read property
  - AJAX error details

- [ ] **Cek Backend Error** - Lihat PHP error log
  - File: error_log di root
  - Check SQL syntax error
  - Check missing parameters

## JavaScript Console Commands untuk Debug

```javascript
// Cek berapa item yang ada
$('.item-check').length

// Cek berapa yang dicentang
$('.item-check:checked').length

// Cek status tombol
$('#btnSubmit').prop('disabled')

// Paksa aktifkan tombol (untuk test)
$('#btnSubmit').prop('disabled', false)

// Cek data form
$('#formRetur').serialize()

// Trigger validasi manual
validateForm() // Jika fungsi global

// Cek keterangan
$('textarea[name="keterangan"]').val()
```

## File yang Dimodifikasi

1. **content/returkonsinyasi/input.php**
   - Line 268-291: Enhanced validation dengan keterangan
   - Line 293-296: Event listener untuk textarea
   - Line 289-297: Form submit dengan $form reference
   - Line 332: AJAX data: `$form.serialize()`
   - Line 346: Enhanced error handler dengan console.log

2. **modal/returkonsinyasi/action.php**
   - Line 16-22: Session/Cookie fallback untuk admin
   - Tambahan: Error response jika session expired

## Expected Behavior

### Normal Flow:
1. **Page Load** → Tombol disabled (tidak ada yang dipilih)
2. **Centang Item** → Qty auto-fill, tombol masih disabled (keterangan kosong)
3. **Isi Keterangan** → Tombol aktif (semua syarat terpenuhi)
4. **Click Tombol** → Konfirmasi SweetAlert
5. **Konfirmasi** → Loading indicator
6. **AJAX Success** → Success alert → Redirect
7. **AJAX Error** → Error alert dengan detail

### Console Output Example:
```
Validation: {hasChecked: true, hasValidQty: true, keterangan: 25, isValid: true}
```

## Common Issues & Solutions

### Issue 1: Tombol tidak pernah aktif
**Cause:** Keterangan tidak terdeteksi
**Solution:** Pastikan textarea memiliki `name="keterangan"`

### Issue 2: Submit tidak jalan
**Cause:** Form submit di-prevent tapi tidak ada AJAX
**Solution:** Cek error di console, pastikan jQuery loaded

### Issue 3: AJAX error
**Cause:** URL salah atau response bukan JSON
**Solution:** Cek network tab, lihat actual response

### Issue 4: Success tapi data tidak tersimpan
**Cause:** Backend error tapi return success
**Solution:** Cek database, cek error_log

## Next Steps if Still Not Working

1. **Copy error dari console** dan kirim ke developer
2. **Check network tab** - klik request, lihat Preview/Response
3. **Check PHP error_log** - lihat file error_log di root
4. **Test dengan browser lain** - Chrome/Firefox/Edge
5. **Clear cache** - Ctrl+Shift+Del, clear all
6. **Check jQuery loaded** - Console: `typeof jQuery` should be "function"

---

## Quick Fix Commands

Jika masih stuck, jalankan di console:

```javascript
// Force enable button
$('#btnSubmit').prop('disabled', false);

// Check if form can submit
$('#formRetur').submit();

// Manual AJAX test
$.ajax({
    url: 'modal/returkonsinyasi/action.php',
    type: 'POST',
    data: $('#formRetur').serialize(),
    success: function(r) { console.log('Success:', r); },
    error: function(e) { console.log('Error:', e); }
});
```
