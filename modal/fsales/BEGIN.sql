BEGIN
DELETE
FROM
	report_produk
WHERE
	id_pro = produk;

INSERT
	INTO
	report_produk
SELECT
	'Order' AS jenis,
	A.id_pro,
	A.bcode_trd,
	D.nama_sup,
	A.id_tre,
	C.kode_tor,
    A.gudang,
	B.fak_tre,
	B.tgl_tre,
	A.jumlah_trd
FROM
	transaksi_receivedetail AS A
LEFT JOIN transaksi_receive AS B ON
	A.id_tre = B.id_tre
LEFT JOIN transaksi_order AS C ON
	B.id_tor = C.id_tor
LEFT JOIN supplier AS D ON
	B.id_sup = D.id_sup
WHERE
	A.id_pro = produk;

INSERT
	INTO
	report_produk
SELECT
	'Sales' AS jenis,
	A.id_pro,
	C.no_bcode,
	D.nama_out,
	A.id_tfk,
	B.sj_tfk,
    C.gudang,
	B.kode_tfk,
	B.tgl_tfk,
	A.jumlah_tfd
FROM
	transaksi_fakturdetail AS A
LEFT JOIN transaksi_faktur AS B ON
	A.id_tfk = B.id_tfk
LEFT JOIN produk_stokdetail AS C ON
	A.id_psd = C.id_psd
LEFT JOIN outlet AS D ON
	B.id_out = D.id_out
WHERE
	A.id_pro = produk;
    

INSERT
	INTO
	report_produk
SELECT
	'Donasi' AS jenis,
	A.id_pro,
	C.no_bcode,
	D.nama_out,
	A.id_tfk,
	B.sj_tfk,
    C.gudang,
	B.kode_tfk,
	B.tgl_tfk,
	A.jumlah_tfd
FROM
	transaksi_fakturdetail_d AS A
LEFT JOIN transaksi_faktur_d AS B ON
	A.id_tfk = B.id_tfk
LEFT JOIN produk_stokdetail AS C ON
	A.id_psd = C.id_psd
LEFT JOIN outlet AS D ON
	B.id_out = D.id_out
WHERE
	A.id_pro = produk;


INSERT
	INTO
	report_produk
SELECT
	'Pinjaman' AS jenis,
	A.id_pro,
	C.no_bcode,
	D.nama_out,
	A.id_tfk,
	B.sj_tfk,
    C.gudang,
	B.kode_tfk,
	B.tgl_tfk,
	A.jumlah_tfd
FROM
	transaksi_fakturdetail_p AS A
LEFT JOIN transaksi_faktur_p AS B ON
	A.id_tfk = B.id_tfk
LEFT JOIN produk_stokdetail AS C ON
	A.id_psd = C.id_psd
LEFT JOIN outlet AS D ON
	B.id_out = D.id_out
WHERE
	A.id_pro = produk;


INSERT
	INTO
	report_produk
SELECT
	'Retur' AS jenis,
	A.id_pro,
	C.no_bcode,
	D.nama_out,
	A.id_tfk,
	B.sj_tfk,
    C.gudang,
	B.kode_tfk,
	B.tgl_tfk,
	A.jumlah_tfd
FROM
	transaksi_fakturdetail_r AS A
LEFT JOIN transaksi_faktur_r AS B ON
	A.id_tfk = B.id_tfk
LEFT JOIN produk_stokdetail AS C ON
	A.id_psd = C.id_psd
LEFT JOIN outlet AS D ON
	B.id_out = D.id_out
WHERE
	A.id_pro = produk;
    

INSERT
	INTO
	report_produk
SELECT
	'Lain-Lain' AS jenis,
	A.id_pro,
	C.no_bcode,
	D.nama_out,
	A.id_tfk,
	B.sj_tfk,
    C.gudang,
	B.kode_tfk,
	B.tgl_tfk,
	A.jumlah_tfd
FROM
	transaksi_fakturdetail_l AS A
LEFT JOIN transaksi_faktur_l AS B ON
	A.id_tfk = B.id_tfk
LEFT JOIN produk_stokdetail AS C ON
	A.id_psd = C.id_psd
LEFT JOIN outlet AS D ON
	B.id_out = D.id_out
WHERE
	A.id_pro = produk;


INSERT
	INTO
	report_produk
SELECT
	'TF-IN' AS jenis,
	A.id_pro,
	C.no_bcode,
	CONCAT(D.nama_apl, " Ke ", E.nama_apl),
	B.id_ttr,
	B.kode_ttr,
    C.gudang,
	'',
	B.tgl_ttr,
	A.jumlah_ttd
FROM
	transaksi_transferstockdetail AS A
LEFT JOIN transaksi_transferstock AS B ON
	A.id_ttr = B.id_ttr
LEFT JOIN produk_stokdetail AS C ON
	A.id_psd = C.id_psd
LEFT JOIN aplikasi AS D ON
	B.id_app_from = D.id_apl
LEFT JOIN aplikasi AS E ON
	B.id_app_to = E.id_apl
WHERE
	A.id_pro = produk AND
	B.tipe_ttr = 'IN';


INSERT
	INTO
	report_produk
SELECT
	'TF-OUT' AS jenis,
	A.id_pro,
	C.no_bcode,
	CONCAT(D.nama_apl, " Ke ", E.nama_apl),
	B.id_ttr,
	B.kode_ttr,
    C.gudang,
	'',
	B.tgl_ttr,
	A.jumlah_ttd
FROM
	transaksi_transferstockdetail AS A
LEFT JOIN transaksi_transferstock AS B ON
	A.id_ttr = B.id_ttr
LEFT JOIN produk_stokdetail AS C ON
	A.id_psd = C.id_psd
LEFT JOIN aplikasi AS D ON
	B.id_app_from = D.id_apl
LEFT JOIN aplikasi AS E ON
	B.id_app_to = E.id_apl
WHERE
	A.id_pro = produk AND
	B.tipe_ttr = 'OUT' AND 
    B.status_ttr = 'Approved';

INSERT
	INTO
	report_produk
SELECT
	'Retur' AS jenis,
	A.id_pro,
	C.no_bcode,
	D.nama_out,
	A.id_tfk,
	B.sj_tfk,
    C.gudang,
	B.kode_tfkk,
	B.tgl_tfk,
	A.jumlah_tfd
FROM
	transaksi_faktur_penggantian_barang_detail AS A
LEFT JOIN transaksi_faktur_penggantian_barang AS B ON
	A.id_tfk = B.id_tfk
LEFT JOIN produk_stokdetail AS C ON
	A.id_psd = C.id_psd
LEFT JOIN outlet AS D ON
	B.id_out = D.id_out
WHERE
	A.id_pro = produk;


INSERT
	INTO
	report_produk
SELECT
	'Sales' AS jenis,
	A.id_pro,
	C.no_bcode,
	D.nama_out,
	A.id_tfk,
	B.sj_tfk,
    C.gudang,
	B.kode_tfk,
	B.tgl_tfk,
	A.jumlah_tfd
FROM
	transaksi_fakturdetail_pim AS A
LEFT JOIN transaksi_faktur_pim AS B ON
	A.id_tfk = B.id_tfk
LEFT JOIN produk_stokdetail AS C ON
	A.id_psd = C.id_psd
LEFT JOIN outlet AS D ON
	B.id_out = D.id_out
WHERE
	A.id_pro = produk;
    

INSERT
	INTO
	report_produk
SELECT
	'Konsinyasi' AS jenis,
	A.id_pro,
	C.no_bcode,
	D.nama_out,
	A.id_tfk,
	B.sj_tfk,
    C.gudang,
	B.kode_tfk,
	B.tgl_tfk,
	A.jumlah_tfd
FROM
	transaksi_fakturdetail_konsinyasi AS A
LEFT JOIN transaksi_faktur_konsinyasi AS B ON
	A.id_tfk = B.id_tfk
LEFT JOIN produk_stokdetail AS C ON
	A.id_psd = C.id_psd
LEFT JOIN outlet AS D ON
	B.id_out = D.id_out
WHERE
	A.id_pro = produk;


INSERT
	INTO
	report_produk
SELECT
	'IN-Konsinyasi' AS jenis,
	A.id_pro,
	C.no_bcode,
	D.nama_out,
	A.id_trk,
	B.no_retur,
    C.gudang,
	B.no_retur,
	B.tgl_retur,
	A.qty_retur
FROM
	transaksi_retur_konsinyasi_detail AS A
LEFT JOIN transaksi_retur_konsinyasi AS B ON
	A.id_trk = B.id_trk
LEFT JOIN produk_stokdetail AS C ON
	A.id_psd = C.id_psd
LEFT JOIN outlet AS D ON
	B.id_out = D.id_out
WHERE
	A.id_pro = produk;
   

END