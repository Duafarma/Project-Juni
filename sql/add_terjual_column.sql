-- Tambahkan kolom terjual_tfd ke transaksi_fakturdetail_konsinyasi
-- untuk tracking berapa yang sudah terjual dari setiap item konsinyasi

ALTER TABLE `transaksi_fakturdetail_konsinyasi`
ADD COLUMN `terjual_tfd` INT(11) NOT NULL DEFAULT 0 AFTER `jumlah_tfd`,
ADD COLUMN `sisa_tfd` INT(11) NOT NULL DEFAULT 0 AFTER `terjual_tfd`;

-- Update existing data: set sisa_tfd = jumlah_tfd untuk data yang sudah ada
UPDATE `transaksi_fakturdetail_konsinyasi`
SET `sisa_tfd` = `jumlah_tfd`
WHERE `sisa_tfd` = 0;

COMMIT;
