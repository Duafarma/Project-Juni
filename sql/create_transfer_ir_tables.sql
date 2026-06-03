-- ============================================================
-- Tabel Transfer Inventory Retur ke Penjualan
-- Jalankan di semua aplikasi yang ingin pakai modul transferir
-- ============================================================

CREATE TABLE IF NOT EXISTS `transfer_ir` (
  `id_tir`        int(11)      NOT NULL AUTO_INCREMENT,
  `no_tir`        varchar(20)  NOT NULL,
  `keterangan`    varchar(255) DEFAULT NULL,
  `status_tir`    enum('draft','pending','approved','rejected') NOT NULL DEFAULT 'draft',
  `notes_tir`     text         DEFAULT NULL,
  `created_at`    datetime     NOT NULL,
  `created_by`    varchar(20)  NOT NULL,
  `submitted_at`  datetime     DEFAULT NULL,
  `submitted_by`  varchar(20)  DEFAULT NULL,
  `approved_at`   datetime     DEFAULT NULL,
  `approved_by`   varchar(20)  DEFAULT NULL,
  `updated_at`    datetime     DEFAULT NULL,
  `updated_by`    varchar(20)  DEFAULT NULL,
  -- Kolom tambahan untuk transfer lintas app
  `sumber_apl`    varchar(5)   DEFAULT NULL COMMENT 'id_apl asal jika dari app lain',
  `tujuan_apl`    varchar(5)   DEFAULT NULL COMMENT 'id_apl tujuan jika ke app lain',
  `ref_tir_remote` varchar(20) DEFAULT NULL COMMENT 'no_tir di app tujuan (untuk cross-app)',
  PRIMARY KEY (`id_tir`),
  UNIQUE KEY `no_tir` (`no_tir`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `transfer_ir_detail` (
  `id_tird`       int(11)      NOT NULL AUTO_INCREMENT,
  `id_tir`        int(11)      NOT NULL,
  `id_psd`        int(11)      NOT NULL COMMENT 'id_i_r dari inventory_retur',
  `id_pro`        varchar(20)  NOT NULL,
  `no_bcode`      varchar(50)  DEFAULT NULL,
  `tgl_expired`   date         DEFAULT NULL,
  `gudang`        varchar(50)  DEFAULT NULL,
  `jumlah`        int(11)      NOT NULL DEFAULT 0,
  `created_at`    datetime     NOT NULL,
  `created_by`    varchar(20)  NOT NULL,
  PRIMARY KEY (`id_tird`),
  KEY `id_tir` (`id_tir`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================================
-- ALTER TABLE untuk tabel yang sudah ada (jalankan sekali)
-- ============================================================
ALTER TABLE `transfer_ir`
  ADD COLUMN IF NOT EXISTS `sumber_apl`     varchar(5)  DEFAULT NULL COMMENT 'id_apl asal jika dari app lain',
  ADD COLUMN IF NOT EXISTS `tujuan_apl`     varchar(5)  DEFAULT NULL COMMENT 'id_apl tujuan jika ke app lain',
  ADD COLUMN IF NOT EXISTS `ref_tir_remote` varchar(20) DEFAULT NULL COMMENT 'no_tir di app tujuan (untuk cross-app)';
