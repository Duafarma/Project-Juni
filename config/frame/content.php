<?php
switch ($menu) {
	default:
		require_once("content/home/home.php");
		break;

	case "":
		require_once("content/home/home.php");
		break;
	case "home":
		require_once("content/home/home.php");
		break;
	case "homepim":
		require_once("content/home/homepim.php");
		break;
	case "provinsi":
		require_once("content/provinsi/provinsi.php");
		break;
	case "kabupaten":
		require_once("content/kabupaten/kabupaten.php");
		break;
	case "koutlet":
		require_once("content/koutlet/koutlet.php");
		break;
	case "ksupplier":
		require_once("content/ksupplier/ksupplier.php");
		break;
	case "kproduk":
		require_once("content/kproduk/kproduk.php");
		break;
	case "klegal":
		require_once("content/klegal/klegal.php");
		break;
	case "sproduk":
		require_once("content/sproduk/sproduk.php");
		break;
	case "outlet":
		require_once("content/outlet/outlet.php");
		break;
	case "dispro":
		require_once("content/dispro/dispro.php");
		break;
	case "ioutlet":
		require_once("content/outlet/input.php");
		break;
	case "eoutlet":
		require_once("content/outlet/edit.php");
		break;
	case "voutlet":
		require_once("content/outlet/view.php");
		break;
	case "poutlet":
		require_once("content/outlet/platform.php");
		break;
	case "supplier":
		require_once("content/supplier/supplier.php");
		break;
	case "isupplier":
		require_once("content/supplier/input.php");
		break;
	case "esupplier":
		require_once("content/supplier/edit.php");
		break;
	case "vsupplier":
		require_once("content/supplier/view.php");
		break;
	case "batchcode":
		require_once("content/batchcode/batchcode.php");
		break;
	case "produk":
		require_once("content/produk/produk.php");
		break;
	case "vproduk":
		require_once("content/produk/view.php");
		break;
	case "hproduk":
		require_once("content/hproduk/hproduk.php");
		break;
	case "order":
		require_once("content/order/order.php");
		break;
	case "iorder":
		require_once("content/order/input.php");
		break;
	case "eorder":
		require_once("content/order/edit.php");
		break;
	case "vorder":
		require_once("content/order/view.php");
		break;
	case "rorder":
		require_once("content/rorder/rorder.php");
		break;
	case "irorder":
		require_once("content/rorder/input.php");
		break;
	case "erorder":
		require_once("content/rorder/edit.php");
		break;
	case "vrorder":
		require_once("content/rorder/view.php");
		break;
	case "inventory":
		require_once("content/inventory/inventory.php");
		break;
	case "inventorypim":
		require_once("content/inventorypim/inventorypim.php");
		break;
	case "inventoryrhea":
		require_once("content/inventoryrhea/inventoryrhea.php");
		break;
	case "inventoryrudo":
		require_once("content/inventoryrudo/inventoryrudo.php");
		break;
	case "tfstok":
		require_once("content/tfstok/tfstok.php");
		break;
	case "itfstok":
		require_once("content/tfstok/input.php");
		break;
	case "etfstok":
		require_once("content/tfstok/edit.php");
		break;
	case "vtfstok":
		require_once("content/tfstok/view.php");
		break;
	case "sales":
		require_once("content/sales/sales.php");
		break;
	case "isales":
		require_once("content/sales/input.php");
		break;
	case "vsales":
		require_once("content/sales/view.php");
		break;
	case "porder":
		require_once("content/porder/porder.php");
		break;
	case "fsales":
		require_once("content/fsales/fsales.php");
		break;
	case "ifsales":
		require_once("content/fsales/input.php");
		break;
	case "itemsales":
		require_once("content/fsales/item.php");
		break;
	case "manualfsales":
		require_once("content/fsales/manual_item.php");
		break;
	case "daftarmanualfsales":
		require_once("content/fsales/daftar_manual.php");
		break;
	case "efsales":
		require_once("content/fsales/edit.php");
		break;
	case "tfsales":
		require_once("content/fsales/tf.php");
		break;
	case "vfsales":
		require_once("content/fsales/view.php");
		break;
	case "psales":
		require_once("content/psales/psales.php");
		break;
	case "tfaktur":
		require_once("content/tfaktur/tfaktur.php");
		break;
	case "sistem":
		require_once("content/sistem/sistem.php");
		break;
	case "esistem":
		require_once("content/sistem/edit.php");
		break;
	case "rstok":
		require_once("content/rstok/rstok.php");
		break;
	case "rpenjualan":
		require_once("content/rpenjualan/rpenjualan.php");
		break;
	case "rspenjualan":
		require_once("content/rspenjualan/rspenjualan.php");
		break;
	case "rpembelian":
		require_once("content/rpembelian/rpembelian.php");
		break;
	case "red":
		require_once("content/red/red.php");
		break;
	case "rjtoutlet":
		require_once("content/rjtoutlet/rjtoutlet.php");
		break;
	case "rjtsupplier":
		require_once("content/rjtsupplier/rjtsupplier.php");
		break;
	case "rpjoutlet":
		require_once("content/rpjoutlet/rpjoutlet.php");
		break;
	case "rpjobat":
		require_once("content/rpjobat/rpjobat.php");
		break;
	case "gpembelian":
		require_once("content/gpembelian/gpembelian.php");
		break;
	case "gpembeliandpe":
		require_once("content/gpembeliandpe/gpembeliandpe.php");
		break;
	case "gpembelianpim":
		require_once("content/gpembelianpim/gpembelianpim.php");
		break;
	case "gpenjualan":
		require_once("content/gpenjualan/gpenjualan.php");
		break;
	case "administrator":
		require_once("content/administrator/administrator.php");
		break;
	case "iadministrator":
		require_once("content/administrator/input.php");
		break;
	case "eadministrator":
		require_once("content/administrator/edit.php");
		break;
	case "hakakses":
		require_once("content/hakakses/hakakses.php");
		break;
	case "apoteker":
		require_once("content/apoteker/apoteker.php");
		break;
	case "riwayat":
		require_once("content/riwayat/riwayat.php");
		break;
	case "rankpenjualan":
		require_once("content/rankpenjualan/rankpenjualan.php");
		break;
	case "rankpembelian":
		require_once("content/rankpembelian/rankpembelian.php");
		break;
	case "kmenu":
		require_once("content/kmenu/kmenu.php");
		break;
	case "icon":
		require_once("content/icon/icon.php");
		break;
	case "menu":
		require_once("content/menu/menu.php");
		break;
	case "submenu":
		require_once("content/submenu/submenu.php");
		break;
	case "rolemenu":
		require_once("content/rolemenu/rolemenu.php");
		break;
	case "account":
		require_once("content/account/account.php");
		break;
	case "rproduk":
		require_once("content/rproduk/rproduk.php");
		break;
	case "cabang":
		require_once("content/cabang/cabang.php");
		break;
	case "gudang":
		require_once("content/gudang/gudang.php");
		break;
	case "transferstok":
		require_once("content/transferstok/transferstok.php");
		break;
	case "itransferstok":
		require_once("content/transferstok/input.php");
		break;
	case "etransferstok":
		require_once("content/transferstok/edit.php");
		break;
	case "vtransferstok":
		require_once("content/transferstok/view.php");
		break;
	case "rtransferstok":
		require_once("content/rtransferstok/rtransferstok.php");
		break;
	case "fsalesd":
		require_once("content/fsalesd/fsales.php");
		break;
	case "ifsalesd":
		require_once("content/fsalesd/input.php");
		break;
	case "itemsalesd":
		require_once("content/fsalesd/item.php");
		break;
	case "efsalesd":
		require_once("content/fsalesd/edit.php");
		break;
	// case "tfsalesd":
	// 	require_once("content/fsalesdd/tf.php");
	// break;
	case "vfsalesd":
		require_once("content/fsalesd/view.php");
		break;
	case "rpenjualand":
		require_once("content/rpenjualand/rpenjualand.php");
		break;

	case "fsalesp":
		require_once("content/fsalesp/fsales.php");
		break;
	case "ifsalesp":
		require_once("content/fsalesp/input.php");
		break;
	case "itemsalesp":
		require_once("content/fsalesp/item.php");
		break;
	case "efsalesp":
		require_once("content/fsalesp/edit.php");
		break;
	case "rpenjualanp":
		require_once("content/rpenjualanp/rpenjualanp.php");
		break;

	case "fsalesr":
		require_once("content/fsalesr/fsales.php");
		break;
	case "ifsalesr":
		require_once("content/fsalesr/input.php");
		break;
	case "itemsalesr":
		require_once("content/fsalesr/item.php");
		break;
	case "efsalesr":
		require_once("content/fsalesr/edit.php");
		break;
	case "rpenjualanr":
		require_once("content/rpenjualanr/rpenjualanr.php");
		break;

	case "fsalesl":
		require_once("content/fsalesl/fsales.php");
		break;
	case "ifsalesl":
		require_once("content/fsalesl/input.php");
		break;
	case "itemsalesl":
		require_once("content/fsalesl/item.php");
		break;
	case "efsalesl":
		require_once("content/fsalesl/edit.php");
		break;
	case "rpenjualanl":
		require_once("content/rpenjualanl/rpenjualanl.php");
		break;

	case "fsalespe":
		require_once("content/fsalespe/fsales.php");
		break;

	case "fsalesk":
		require_once("content/fsalesk/fsales.php");
		break;
	case "ifsalesk":
		require_once("content/fsalesk/input.php");
		break;
	case "itemsalesk":
		require_once("content/fsalesk/item.php");
		break;
	case "efsalesk":
		require_once("content/fsalesk/edit.php");
		break;
	case "vfsalesk":
		require_once("content/fsalesk/view.php");
		break;
	case "rsalesk":
		require_once("content/rsalesk/rsalesk.php");
		break;

	case "stokkonsinyasi":
		require_once("content/stokkonsinyasi/index.php");
		break;

	case "historisproduk":
		require_once("content/historisproduk/historisproduk.php");
		break;

	case "returkonsinyasi":
		require_once("content/returkonsinyasi/returkonsinyasi.php");
		break;
	case "ireturkonsinyasi":
		require_once("content/returkonsinyasi/input.php");
		break;

	case "hretur":
		require_once("content/hretur/hretur.php");
		break;

	case "rpenjualanc":
		require_once("content/rpenjualanc/rpenjualanc.php");
		break;

	case "pengiriman":
		require_once("content/pengiriman/pengiriman.php");
		break;
	case "fpengiriman":
		require_once("content/fpengiriman/fpengiriman.php");
		break;

	case "rpengiriman":
		require_once("content/rpengiriman/rpengiriman.php");
		break;
	case "rfpengiriman":
		require_once("content/rfpengiriman/rfpengiriman.php");
		break;

	case "outletbaru":
		require_once("content/outletbaru/outletbaru.php");
		break;
	case "ioutletbaru":
		require_once("content/outletbaru/input.php");
		break;
	case "voutletbaru":
		require_once("content/outletbaru/view.php");
		break;
	case "eoutletbaru":
		require_once("content/outletbaru/edit.php");
		break;


	case "stockopname":
		require_once("content/stockopname/stockopname.php");
		break;

	case "istockopname":
		require_once("content/stockopname/input.php");
		break;

	case "estockopname":
		require_once("content/stockopname/edit.php");
		break;
	case "stockopnamek":
		require_once("content/stockopnamek/stockopnamek.php");
		break;
	case "istockopnamek":
		require_once("content/stockopnamek/input.php");
		break;

	case "istockopname":
		require_once("content/stockopname/input.php");
		break;

	case "estockopname":
		require_once("content/stockopname/edit.php");
		break;
	case "stockopnamer":
		require_once("content/stockopnamer/stockopnamer.php");
		break;

	case "istockopname":
		require_once("content/stockopname/input.php");
		break;

	case "estockopname":
		require_once("content/stockopname/edit.php");
		break;
	case "stockopnamea":
		require_once("content/stockopnamea/stockopnamea.php");
		break;

	case "stockopnameplus":
		require_once("content/stockopnameplus/stockopnameplus.php");
		break;

	case "istockopname":
		require_once("content/stockopname/input.php");
		break;
	case "vstockopname":
		require_once("content/stockopname/view.php");
		break;
	case "estockopname":
		require_once("content/stockopname/edit.php");
		break;
	case "stockopnameap":
		require_once("content/stockopnameap/stockopnameap.php");
		break;
	case "istockopnameap":
		require_once("content/stockopnameap/input.php");
		break;

	case "vstockopnameap":
		require_once("content/stockopnameap/view.php");
		break;

	case "istockopname":
		require_once("content/stockopname/input.php");
		break;

	case "estockopname":
		require_once("content/stockopname/edit.php");
		break;
	case "stockopnamein":
		require_once("content/stockopnamein/stockopnamein.php");
		break;
	case "istockopnamein":
		require_once("content/stockopnamein/input.php");
		break;

	case "dokumen":
		require_once("content/dokumen/dokumen.php");
		break;
	case "idiskonadmin":
		require_once("content/diskonadmin/diskonadmin.php");
		break;
	case "edokumen":
		require_once("content/dokumen/edit.php");
		break;
	case "vdokumen":
		require_once("content/dokumen/view.php");
		break;


	case "fakturpajak":
		require_once("content/fakturpajak/fakturpajak.php");
		break;
	case "ifakturpajak":
		require_once("content/fakturpajak/input.php");
		break;
	case "efakturpajak":
		require_once("content/fakturpajak/edit.php");
		break;
	case "vfakturpajak":
		require_once("content/fakturpajak/view.php");
		break;

	case "rstockopname":
		require_once("content/rstockopname/rstockopname.php");
		break;

	case "dokumenbalik":
		require_once("content/dokumenbalik/dokumenbalik.php");
		break;
	case "idokumenbalik":
		require_once("content/dokumenbalik/input.php");
		break;
	case "efinance":
		require_once("content/finance/edit.php");
		break;
	case "vfinance":
		require_once("content/finance/view.php");
		break;

	case "dokumenfailing":
		require_once("content/dokumenfailing/dokumenfailing.php");
		break;
	case "idokumenfailing":
		require_once("content/dokumenfailing/input.php");
		break;
	case "efinance":
		require_once("content/finance/edit.php");
		break;
	case "vfinance":
		require_once("content/finance/view.php");
		break;

	case "tukerfakturbalik":
		require_once("content/tukerfakturbalik/tukerfakturbalik.php");
		break;
	case "itukerfakturbalik":
		require_once("content/tukerfakturbalik/input.php");
		break;
	case "efinance":
		require_once("content/finance/edit.php");
		break;
	case "vfinance":
		require_once("content/finance/view.php");
		break;

	case "marginp":
		require_once("content/marginp/marginp.php");
		break;
	case "emarginp":
		require_once("content/marginp/edit.php");
		break;

	case "kalkulasi":
		require_once("content/kalkulasi/kalkulasi.php");
		break;
	case "emarginp":
		require_once("content/marginp/edit.php");
		break;

	case "selesaifailing":
		require_once("content/selesaifailing/selesaifailing.php");
		break;
	case "iselesaifailing":
		require_once("content/selesaifailing/input.php");
		break;

	case "selesaifakturpajak":
		require_once("content/selesaifakturpajak/selesaifakturpajak.php");
		break;
	case "iselesaifakturpajak":
		require_once("content/selesaifakturpajak/input.php");
		break;

	case "selesaipemberkasan":
		require_once("content/selesaipemberkasan/selesaipemberkasan.php");
		break;
	case "iselesaipemberkasan":
		require_once("content/selesaipemberkasan/input.php");
		break;


	case "alurdokumen":
		require_once("content/alurdokumen/alurdokumen.php");
		break;

	case "uploadpajak":
		require_once("content/uploadpajak/uploadpajak.php");
		break;
	case "iuploadpajak":
		require_once("content/uploadpajak/input.php");
		break;
	case "valurdokumen":
		require_once("content/alurdokumen/view.php");
		break;

	case "balurdokumen":
		require_once("content/alurdokumen/kembali.php");
		break;
	case "fsalurdokumen":
		require_once("content/alurdokumen/sudah_filing.php");
		break;
	case "fbalurdokumen":
		require_once("content/alurdokumen/belum_filing.php");
		break;
	case "psalurdokumen":
		require_once("content/alurdokumen/sudah_pajak.php");
		break;
	case "pbalurdokumen":
		require_once("content/alurdokumen/belum_pajak.php");
		break;
	case "usalurdokumen":
		require_once("content/alurdokumen/sudah_upload.php");
		break;
	case "ubalurdokumen":
		require_once("content/alurdokumen/belum_upload.php");
		break;
	case "tsalurdokumen":
		require_once("content/alurdokumen/sudah_pemberkasan.php");
		break;
	case "tbalurdokumen":
		require_once("content/alurdokumen/belum_pemberkasan.php");
		break;
	case "tfsalurdokumen":
		require_once("content/alurdokumen/siap_tf.php");
		break;
	case "tfbalurdokumen":
		require_once("content/alurdokumen/belum_tf.php");
		break;
	case "spalurdokumen":
		require_once("content/alurdokumen/sudah_pembayaran.php");
		break;
	case "stpalurdokumen":
		require_once("content/alurdokumen/setengah_pembayaran.php");
		break;
	case "bpalurdokumen":
		require_once("content/alurdokumen/belum_pembayaran.php");
		break;
	// psalurdokumen

	case "alurtukerfaktur":
		require_once("content/alurtukerfaktur/alurtukerfaktur.php");
		break;
	case "home_finance":
		require_once("content/home/home_finance.php");
		break;
	case "bltalurdokumen":
		require_once("content/alurdokumen/pengiriman_barang.php");
		break;

	case "finance":
		require_once("content/finance/finance.php");
		break;
	case "ifinance":
		require_once("content/finance/input.php");
		break;
	case "efinance":
		require_once("content/finance/edit.php");
		break;
	case "vfinance":
		require_once("content/finance/view.php");
		break;

	case "fpenggantianbarang":
		require_once("content/fpenggantianbarang/fpenggantianbarang.php");
		break;
	case "ifpenggantianbarang":
		require_once("content/fpenggantianbarang/input.php");
		break;
	case "itemfpenggantianbarang":
		require_once("content/fpenggantianbarang/item.php");
		break;
	case "efpenggantianbarang":
		require_once("content/fpenggantianbarang/edit.php");
		break;
	case "rpenggantianbarang":
		require_once("content/rpenggantianbarang/rpenggantianbarang.php");
		break;
	case "rpenggantianbarang":
		require_once("content/rpenggantianbarang/rpenggantianbarang.php");
		break;
	case "masterprinciple":
		require_once("content/masterprinciple/masterprinciple.php");
		break;

	case "programpromo":
		require_once("content/programpromo/programpromo.php");
		break;

	case "inventorydpe":
		require_once("content/inventorydpe/inventorydpe.php");
		break;

	case "siaptf":
		require_once("content/siaptf/siaptf.php");
		break;
	case "isiaptf":
		require_once("content/siaptf/input.php");
		break;
	case "vsiaptf":
		require_once("content/siaptf/view.php");
		break;

	case "kategoripj":
		require_once("content/kategoripj/kategoripj.php");
		break;

	case "fpengiriman":
		require_once("content/fpengiriman/fpengiriman.php");
		break;
	case "ifpengiriman":
		require_once("content/fpengiriman/input.php");
		break;

	case "rpengiriman":
		require_once("content/rpengiriman/rpengiriman.php");
		break;

	case "masterpengiriman":
		require_once("content/masterpengiriman/masterpengiriman.php");
		break;

	case "masterrekening":
		require_once("content/masterrekening/masterrekening.php");
		break;
	case "omasterrekening":
		require_once("content/masterrekening/rekout.php");
		break;

	case "vendorpengiriman":
		require_once("content/vendorpengiriman/vendorpengiriman.php");
		break;


	case "pengirimanlk":
		require_once("content/pengirimanlk/pengirimanlk.php");
		break;
	case "ipengirimanlk":
		require_once("content/pengirimanlk/input.php");
		break;

	case "inventorygabungan":
		require_once("content/inventorygabungan/inventorygabungan.php");
		break;


	case "fsalespim":
		require_once("content/fsalespim/fsalespim.php");
		break;
	case "ifsalespim":
		require_once("content/fsalespim/input.php");
		break;
	case "itemsalespim":
		require_once("content/fsalespim/item.php");
		break;
	case "efsalespim":
		require_once("content/fsalespim/edit.php");
		break;
	case "rpenjualanp":
		require_once("content/rpenjualanp/rpenjualanp.php");
		break;
	case "rpenjualanpim":
		require_once("content/rpenjualanpim/rpenjualanpim.php");
		break;

	case "stockopnamedpe":
		require_once("content/stockopnamedpe/stockopnamedpe.php");
		break;
	case "istockopnamedpe":
		require_once("content/stockopnamedpe/input.php");
		break;
	case "retur":
		require_once("content/retur/retur.php");
		break;
	case "iretur":
		require_once("content/retur/input.php");
		break;


	case "inventoryretur":
		require_once("content/inventoryretur/inventoryretur.php");
		break;
	case "transferretur":
		require_once("content/inventoryretur/transferretur.php");
		break;

	case "produkdpe":
		require_once("content/produkdpe/produkdpe.php");
		break;
	case "vprodukdpe":
		require_once("content/produkdpe/view.php");
		break;
	case "hprodukdpe":
		require_once("content/hprodukdpe/hprodukdpe.php");
		break;


	case "produkpim":
		require_once("content/produkpim/produkpim.php");
		break;
	case "vprodukpim":
		require_once("content/produkpim/view.php");
		break;
	case "hprodukpim":
		require_once("content/hprodukpim/hprodukpim.php");
		break;

	case "rorderpim":
		require_once("content/rorderpim/rorderpim.php");
		break;
	case "irorderpim":
		require_once("content/rorderpim/input.php");
		break;
	case "erorderpim":
		require_once("content/rorderpim/edit.php");
		break;
	case "vrorderpim":
		require_once("content/rorderpim/view.php");
		break;

	case "rorderdpe":
		require_once("content/rorderdpe/rorderdpe.php");
		break;
	case "irorderdpe":
		require_once("content/rorderdpe/input.php");
		break;
	case "erorderdpe":
		require_once("content/rorderdpe/edit.php");
		break;
	case "vrorderdpe":
		require_once("content/rorderdpe/view.php");
		break;

	case "stockopnamepim":
		require_once("content/stockopnamepim/stockopnamepim.php");
		break;
	case "istockopnamepim":
		require_once("content/stockopnamepim/input.php");
		break;

	case "master_grup":
		require_once("content/master_grup/master_grup.php");
		break;
	case "hpimoutlet":
		require_once("content/outlet/updatehpim.php");
		break;
	case "vretur":
		require_once("content/retur/view.php");
		break;
	case "grafikproduk":
		require_once("content/grafikproduk/grafikproduk.php");
		break;

	case "ipoutlet":
		require_once("content/outlet/updateitem.php");
		break;
	case "monitoringfp":
		require_once("content/monitoringfp/monitoringfp.php");
		break;

	case "monitoringfi":
		require_once("content/monitoringfi/monitoringfi.php");
		break;
	case "fsalesb":
		require_once("content/fsalesb/fsalesb.php");
		break;
	case "ifsalesb":
		require_once("content/fsalesb/input.php");
		break;
	case "itemsalesb":
		require_once("content/fsalesb/item.php");
		break;
	case "efsalesb":
		require_once("content/fsalesb/edit.php");
		break;
	case "tfsalesb":
		require_once("content/fsalesb/tf.php");
		break;
	case "vfsalesb":
		require_once("content/fsalesb/view.php");
		break;


	case "gproduk":
		require_once("content/produk/grafik.php");
		break;
	case "transferretur":
		require_once("content/transferretur/transferretur.php");
		break;
	case "itransferretur":
		require_once("content/transferretur/input.php");
		break;
	case "vtransferretur":
		require_once("content/transferretur/view.php");
		break;
	case "transferir":
		require_once("content/transferir/transferir.php");
		break;
	case "itransferir":
		require_once("content/transferir/input.php");
		break;
	case "vtransferir":
		require_once("content/transferir/view.php");
		break;

	case "faktur_retur":
		require_once("content/faktur_retur/faktur_retur.php");
		break;
	case "ifaktur_retur":
		require_once("content/faktur_retur/input.php");
		break;
	case "itemretur":
		require_once("content/faktur_retur/item.php");
		break;

	// case "gudangproduktransfer":
	case "gudangproduktransfer":
		if (isset($_GET['submenu']) && $_GET['submenu'] == 'history') {
			require_once("content/gudangproduktransfer/history.php");
		} else {
			require_once("content/gudangproduktransfer/gudangproduktransfer.php");
		}
		break;
	case "igudangproduktransfer":
		require_once("content/gudangproduktransfer/input.php");
		break;
	case "egudangproduktransfer":
		require_once("content/gudangproduktransfer/edit.php");
		break;
	case "vgudangproduktransfer":
		require_once("content/gudangproduktransfer/view.php");
		break;

	case "fsalespim2":
		require_once("content/fsalespim2/fsalespim2.php");
		break;
	case "ifsalespim2":
		require_once("content/fsalespim2/input.php");
		break;
	case "itemsalespim2":
		require_once("content/fsalespim2/item.php");
		break;
	case "efsalespim2":
		require_once("content/fsalespim2/edit.php");
		break;

	case "rpenjualandpe":
		require_once("content/rpenjualandpe/rpenjualandpe.php");
		break;

	case "edititems":
		require_once("content/fsales/edititems.php");
		break;
	case "edititempim":
		require_once("content/fsalespim/edititems.php");
		break;
	case "edititempim2":
		require_once("content/fsalespim2/edititems.php");
		break;
	case "backup-fsales":
		require_once("content/backup-fsales.php");
		break;
	case "monitoringop_pending":
		require_once("content/monitoringop_pending/monitoringop_pending.php");
		break;
	case "diskonprinciple":
		require_once("content/outlet/diskonprinciple.php");
		break;
	case "flimit":
		require_once("content/flimit/flimit.php");
		break;

	case "stockcancel":
		require_once("content/stockcancel/stockcancel.php");
		break;
	case "transferstockcancel":
		require_once("content/transferstockcancel/transferstockcancel.php");
		break;
	case "transferstockcancel_add":
		require_once("content/transferstockcancel/add.php");
		break;
	case "transferfakturstok":
		require_once("content/transferfakturstok/transferfakturstok.php");
		break;
	case "transferfakturstok_add":
		require_once("content/transferfakturstok/add.php");
		break;

	case "legaloutlet":
		require_once("content/outlet/legaloutlet.php");
		break;

	case "rdokumenbalik":
		require_once("content/rdokumenbalik/rdokumenbalik.php");
		break;
	case "rdokumenfailing":
		require_once("content/rdokumenfailing/rdokumenfailing.php");
		break;
	case "masterprogramproduk":
		require_once("content/masterprogramproduk/masterprogramproduk.php");
		break;

	case "imasterprogramproduk":
		require_once("content/masterprogramproduk/input.php");
		break;

	case "emasterprogramproduk":
		require_once("content/masterprogramproduk/edit.php");
		break;

	case "mastermr":
		require_once("content/mastermr/mastermr.php");
		break;

	case "master_program_produk":
		require_once("content/master_program_produk/master_program_produk.php");
		break;

	case "imaster_program_produk":
		require_once("content/master_program_produk/input.php");
		break;

	case "emaster_program_produk":
		require_once("content/master_program_produk/edit.php");
		break;
}
