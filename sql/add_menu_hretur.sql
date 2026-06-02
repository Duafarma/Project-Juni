-- Menambahkan menu Histori Retur Konsinyasi ke database
-- File ini akan menambahkan submenu baru ke menu yang sudah ada

-- Pastikan menu konsinyasi sudah ada, jika belum ada buat dulu
-- Cek apakah submenu Faktur Konsinyasi sudah ada untuk mencari id_menu nya

-- Tambahkan submenu Histori Retur Konsinyasi
-- Sesuaikan id_smu dan id_menu dengan data yang ada di database Anda
-- Contoh insert (sesuaikan dengan struktur database Anda):

-- INSERT INTO sub_menu (id_smu, id_menu, nama_smu, url_smu, urutan_smu) 
-- VALUES ('smu_hretur_001', '[ID_MENU_KONSINYASI]', 'Histori Retur', 'hretur', 99);

-- Tambahkan role untuk admin (sesuaikan id_adm dengan admin yang ada)
-- INSERT INTO role_menu (id_rme, id_adm, id_smu)
-- SELECT CONCAT('rme_hretur_', id_adm), id_adm, 'smu_hretur_001'
-- FROM administrator;

-- CATATAN:
-- Jalankan query di atas setelah menyesuaikan:
-- 1. id_smu (ID submenu baru)
-- 2. id_menu (ID menu induk - cari dari menu Konsinyasi/Penjualan)
-- 3. urutan_smu (urutan tampilan menu)
-- 4. id_rme (ID role menu)

-- Untuk mengetahui id_menu yang tepat, jalankan query ini:
-- SELECT * FROM menu WHERE nama_menu LIKE '%konsinyasi%' OR nama_menu LIKE '%penjualan%';

-- Untuk mengetahui submenu yang sudah ada di menu tersebut:
-- SELECT * FROM sub_menu WHERE id_menu = '[ID_MENU_DARI_QUERY_ATAS]';
