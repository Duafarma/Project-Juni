var	server	= window.location.pathname.split("/");
var	usuper	= window.location.origin + "/" + server[1];
var menu	= server[2];
var halaman	= $("#halaman").val();
var maximal	= $("#maximal").val();
$(document).ready(function(){
	$("form").bind("keypress", function(e){
		if(e.keyCode==13){
			return false;
		}
	});
	viewdata(menu, maximal, halaman);
});
$(".fortgl").mask("9999-99-99");
function disableEnterKey(e){ 
	var key; 
    if(window.event){ 
		key = window.event.keyCode; 
    } else { 
		key = e.which;      
    } 
    if(key == 13){ 
		return false; 
    } else { 
		return true; 
    } 
} 

function angka(objek){
	a = objek.value;
	b = a.replace(/[^\d]/g,"");
	c = "";
	panjang = b.length;
	j = 0;
	for(i = panjang; i > 0; i--){
		j = j + 1;
		if(((j % 3) == 1) && (j != 1)){
			c = b.substr(i-1,1) + "." + c;
		} else {
			c = b.substr(i-1,1) + c;
		}
	}
	objek.value = c;
}

function titik(nilai){
	var	reverse = nilai.toString().split('').reverse().join(''),
		ribuan	= reverse.match(/\d{1,3}/g);
		ribuan	= ribuan.join('.').split('').reverse().join('');
	return ribuan;
}

function bersih(nilai){
	//var x	= (nilai==='') ? '' : nilai;
	//var y	= x.match(/\d/g);
	//var	z	= y.join("");
	//return z;
	var bersih	= nilai.split('.').join('');
	return bersih;
}

$("#harga").change(function(){
	var	harga	= bersih($("#harga").val());
	var ppn		= (parseInt(harga) * 10) / 100;
	var total	= parseInt(harga) + parseInt(ppn);
	$("#hargappn").val(titik(total));
});

function caritagihan(){
	var kode	= $("#kodeorder").val();
	$.ajax({
		url			: usuper+"/ajax/caritagihan/caritagihan.php",
		type		: "POST",
		//dataType: "json",
		async		: true,
		dataType	: "text",
		//cache	: false,
		data		: { "x" : kode },
		success		: function(data){
			var json	= JSON.parse(data);
			$("#namasup").val(json.namasup);
			$("#tglterima").val(json.tglterima);
			$("#tgltempo").val(json.tgltempo);
			$("#tagihan").val(json.tagihan);
			$("#dibayar").val(json.dibayar);
			$("#sisa").val(json.sisa);
		}
	});
}

function carisales(){
	var kode	= $("#kodesales").val();
	$.ajax({
		url			: usuper+"/ajax/carisales/carisales.php",
		type		: "POST",
		//dataType: "json",
		async		: true,
		dataType	: "text",
		//cache	: false,
		data		: { "x" : kode },
		success		: function(data){
			var json	= JSON.parse(data);
			$("#namaoutlet").val(json.namaoutlet);
			$("#tglfaktur").val(json.tglfaktur);
			$("#tgltempo").val(json.tgltempo);
			$("#tagihan").val(json.tagihan);
			$("#dibayar").val(json.dibayar);
			$("#sisa").val(json.sisa);
		}
	});
}

function caritukerfaktur(){
	var kode	= $("#kodetukerfaktur").val();
	$.ajax({
		url			: usuper+"/ajax/caritukerfaktur/caritukerfaktur.php",
		type		: "POST",
		//dataType: "json",
		async		: true,
		dataType	: "text",
		//cache	: false,
		data		: { "x" : kode },
		success		: function(data){
			var json	= JSON.parse(data);
			$("#namaoutlet").val(json.namaoutlet);
			$("#tglfaktur").val(json.tglfaktur);
		}
	});
}

function detailsales(){
	var kode	= $("#kodesales").val();
	
	$.ajax({
		url			: usuper+"/ajax/detailsales/detailsales.php",
		type		: "POST",
		//dataType: "json",
		async		: true,
		dataType	: "text",
		//cache	: false,
		data		: { "x" : kode },
		success		: function(data){
			var json	= JSON.parse(data);
			$("#dataaddfaktur").html(json.tabel);
			$("#footeraddfaktur").html(json.footer);
			$("#nofaktur").val(json.nofaktur);
			$("#tglsj").val(json.tglsj);
			$("#namaoutlet").val(json.namaoutlet);
			$("#alamatkirim").val(json.alamatkirim);
			$("#npwp").val(json.npwp);
			$("#nomorpo").val(json.nomorpo);
		}
	});
}

function cart(nomor, kode, nama){
	var jumlah  = $("#kodestok"+nomor).val(),
		jumlah	= (jumlah=='') ? '' : jumlah;
	var total	= $("#cart"+nama).val();
	if(jumlah==''){
		if(total==''){
			var hasil	= "-"+kode;
		} else {
			var hasil	= total+"-"+kode;
		}
	} else {
		var hasil	= total.replace("-"+jumlah, "").replace(jumlah, ""),
			hasil	= hasil=='' ? "-"+kode : hasil+"-"+kode;
	}
	document.getElementById("cart"+nama).value	= (hasil.substr(0, 1)=="-") ? hasil.substr(1) : hasil;
}

function viewdata(menu, maximal, halaman){
	var caridata= $("#caridata").val();
	$.ajax({
		url			: usuper+"/json/"+menu+"/"+menu+".php",
		type		: "GET",
		//dataType: "json",
		async		: true,
		dataType	: "text",
		//cache	: false,
		data		: { "caridata" : caridata, "halaman" : halaman, "maximal" : maximal, "menudata" : menu },
		success		: function(data){
			var json	= JSON.parse(data);
			$("#isitabel").html(json.tabel);
			$("#halaman").val(json.halaman);
			$("#paginasi").html(json.paginasi);
		}
	});
}

function selectdata(asal, cari, target){
	var keycode	= $("#"+asal).val();
	$.ajax({
		url			: usuper+"/ajax/"+cari+"/"+cari+".php",
		type		: "GET",
		async		: true,
		dataType	: "text",
		cache		: false,
		data		: { "keycode" : keycode, },
		success		: function(data){
			var json	= JSON.parse(data);
			$("#"+target).html(json.tabel);
		}
	});
}

function namafile(tombol, target){
	$("#"+target).click();
	$("#"+target).change(function(){
 		var nama		= $("#"+target)[0].files[0].name;
 		var jumlah		= nama.length;
		var validExt 	= ".png, .jpeg, .jpg";
		var filePath 	= $("#"+target)[0].files[0].name;
		var getFileExt 	= filePath.substring(filePath.lastIndexOf('.') + 1).toLowerCase();
		var pos 		= validExt.indexOf(getFileExt);
		var maxSize 	= '1024';
		var fsize 		= $("#"+target)[0].files[0].size / 1024;

		if(pos < 0){
			swal('Informasi!', 'Gunakan Format (.png, .jpeg, .jpg)', 'error');
			$("#"+tombol).html('Upload di sini...');
			$("#"+target).val('');
		} else {
			if(fsize > maxSize){
				swal('Informasi!', 'File tidak boleh lebih dari 1 Mb', 'error');
				$("#"+tombol).html('Upload di sini...');
				$("#"+target).val('');
			} else {
				$("#"+tombol).html('');
				var hasil	= (parseInt(jumlah)<24) ? nama : nama.substr(1, 9)+"....."+nama.substr(-9);
				$("#"+tombol).append('<i class="fa fa-cloud-upload"></i> '+hasil);
			}
		}
	});
}

$("#formtransaksi").submit(function(e){
	e.preventDefault();
	var nmenu	= $("#nmenu").val();
	var nact	= $("#nact").val();
	$("#bsave").prop("disabled", true);
	$("#imgloading").html('<img src="'+usuper+'/berkas/gif/tunggu.gif" style="width:15%;"  />');
	$.ajax({
		url			: usuper+"/modal/"+nmenu+"/action.php?act="+nact,
		type		: "POST",
		async		: true,
		dataType	: "text",
		data		: new FormData(this),
		contentType	: false,
		cache		: false,
		processData	: false,
		beforeSend	: function(){},
		success: function(data){
			if(data=="success"){
				swal({
					title	: "Selamat!",
					text	: "Data berhasil di"+nact+"...",
					type	: "success",
					timer	: 2000,
					showCancelButton: false,
					showConfirmButton: false
				}, function(){
					window.location.href = usuper+"/"+nmenu;
				});
			} else { swal("Maaf!", "Data gagal di"+nact+"...", "error"); }
		},
		error: function(data){ swal("Maaf!", "Proses data error...", "error"); },
		complete: function(data){
			$("#bsave").prop("disabled", false);
			$("#formtransaksi").get(0).reset();
			$("#imgloading").html('');
		}
	});
});

$("#formtrasaction").submit(function(e){
	e.preventDefault();
	var nmenu	= $("#nmenu").val();
	var nact	= $("#nact").val();
	$("#bsave").prop("disabled", true);
	$("#imgloading").html('<img src="'+usuper+'/berkas/gif/tunggu.gif" style="width:15%;"  />');
	$.ajax({
		url			: usuper+"/modal/"+nmenu+"/action.php?act="+nact,
		type		: "POST",
		async		: true,
		dataType	: "text",
		data		: new FormData(this),
		contentType	: false,
		cache		: false,
		processData	: false,
		beforeSend	: function(){},
		success: function(data){
			var json	= JSON.parse(data);
			swal({
				title	: json.judul,
				text	: json.pesan,
				type	: json.tipe,
				timer	: 2000,
				showCancelButton: false,
				showConfirmButton: false
			}, function(){
				window.location.href = usuper+"/"+json.url;
			});
		},
		error: function(data){ swal("Maaf!", "Proses data error...", "error"); },
		complete: function(data){
			$("#bsave").prop("disabled", false);
			$("#formtransaksi").get(0).reset();
			$("#imgloading").html('');
		}
	});
});

$("#formlogin").submit(function(e){
	//$(".someElement").attr("disabled", "");
	//$(".someElement").removeAttr("disabled");
	$("#blogin").prop("disabled", true);
	e.preventDefault();
	$.ajax({
		url			: usuper+"/json/signin/signin.php",
		type		: "POST",
		//enctype	: "multipart/form-data",
		//dataType: "json",
		async		: true,
		dataType	: "text",
		//cache	: false,
		data		: new FormData(this),
		contentType	: false,
		cache		: false,
		processData	: false,
		beforeSend	: function(){
		},
		success: function(data){
			var json	= JSON.parse(data);
			if(json.hasil=="success"){
				swal({
					title	: "Selamat!",
					text	: "Login berhasil, halaman akan dialihkan...",
					type	: "success",
					timer	: 2000,
					showCancelButton: false,
					showConfirmButton: false
				}, function(){
					window.location.href = usuper+"/home";
				});
			} else {
				swal("Maaf!", "Username dan password tidak cocok...", "error");
			}
		},
		error: function(data){
			swal("Maaf!", "Registrasi akun baru gagal...", "error");
		},
		complete: function(data){
			//document.getElementById("simpan").disabled	= false;
			$("#blogin").prop("disabled", false);
			$("#formlogin").get(0).reset();
			//$("#gifloading").fadeOut();
			//$("#gifloading").css("visibility","hidden");
		}
	});
});

function crud(modal, menu, kode){
	$.ajax({
		type	: "GET",
		url		: usuper+"/modal/"+modal+"/"+modal+".php?modal="+menu,
		data	: {"keycode" : kode},
		success	: function(data){ $(".modal-content").html(data); }
	});
}

function mbcode(nomor){
	var kode	= $("#batchcode"+nomor).val();
	$.ajax({
		url		: usuper+"/modal/mbcode/mbcode.php",
		type	: "POST",
		async	: true,
		dataType: "text",
		cache	: false,
		data	: { "x" : nomor, "y" : kode},
		success	: function(data){ $(".modal-content").html(data); }
	});
}

function mproduct(nomor, menu){
	var kode	= $("#product"+nomor).val();
	$.ajax({
		url		: usuper+"/modal/"+menu+"/"+menu+".php",
		type	: "POST",
		async	: true,
		dataType: "text",
		cache	: false,
		data	: { "x" : nomor, "y" : kode},
		success	: function(data){ $(".modal-content").html(data); }
	});
}

function addsales(nomor){
	var cart	= $("#cartaddsales").val();
	$.ajax({
		url		: usuper+"/modal/addsales/addsales.php",
		type	: "POST",
		async	: true,
		dataType: "text",
		cache	: false,
		data	: { "x" : nomor, "y" : cart },
		success	: function(data){ $(".modal-content").html(data); }
	});
}

function addmaximal(menu, outlet, maksimal){
	var jumlah	= $("#jum"+menu).val();
	var diskon	= $("#diskon1").val();
	var mitra	= $("#"+outlet).val();
	if(mitra==''){
		swal("Maaf!", "Pilih "+outlet+" dulu...", "error");
	} else {
		if(parseInt(jumlah)>=parseInt(maksimal)){
			swal("Maaf!", "Jumlah melebihi maksimal...", "error");
		} else {
			$.ajax({
				type	: "GET",
				url		: usuper+"/ajax/"+menu+"/"+menu+".php",
				data	: { "jumlah" : jumlah, "diskon" : diskon },
				success	: function(data){
					$("#pilihoutlet").remove();
					$("#data"+menu).append(data);
					var total	= parseInt(jumlah) + 1;
					$("#jum"+menu).val(total);
				}
			});
		}
	}
}

function remmaximal(menu, nomor){
	var jumlah	= $("#jum"+menu).val();
	var total	= $("#ptotal"+nomor).val();
	var pstotal	= $("#pstotal").val();
	//var pgtotal	= $("#pgtotal").val();
	var stotal	= parseInt(bersih(pstotal)) - parseInt(bersih(total));
	var ppn		= (stotal * 10) / 100;
	var gtotal	= parseInt(stotal) + parseInt(ppn);
	if(parseInt(jumlah)<=1){
		swal("Maaf!", "Tidak boleh dikosongkan...", "error");
	} else {
		$("#tr"+menu+""+nomor).remove();
		var jitem	= parseInt(jumlah) - 1;
		$("#jum"+menu).val(jitem);
		$("#pstotal").val(titik(stotal));
		$("#pppn").val(titik(ppn));
		$("#pgtotal").val(titik(gtotal));
	}
}

function delsales(nomor){
	var stokid	= $("#kodestok"+nomor).val();
	var cart	= $("#cartaddsales").val();
	var jumlah	= $("#jumaddsales").val();
	var total	= $("#ptotal"+nomor).val();
	var pstotal	= $("#pstotal").val();
	var stotal	= parseInt(bersih(pstotal)) - parseInt(bersih(total));
	var ppn		= (stotal * 10) / 100;
	var gtotal	= parseInt(stotal) + parseInt(ppn);
	if(parseInt(jumlah)<=1){
		swal("Maaf!", "Tidak boleh dikosongkan...", "error");
	} else {
		var hasil	= cart.replace("-"+stokid, "").replace(stokid, ""),
			hasil	= (hasil.substr(0, 1)=="-") ? hasil.substr(1) : hasil;
		var jitem	= parseInt(jumlah) - 1;
		$("#cartaddsales").val(hasil);
		$("#traddsales"+nomor).remove();
		$("#jumaddsales").val(jitem);
		$("#pstotal").val(titik(stotal));
		$("#pppn").val(titik(ppn));
		$("#pgtotal").val(titik(gtotal));
	}
}

function deleteorder(nomor){
	var jumlah	= $("#jumaddorder").val();
	if(parseInt(jumlah)<=1){
		swal("Maaf!", "Tidak boleh dikosongkan...", "error");
	} else {
		$("#traddorder"+nomor).remove();
		var total	= parseInt(jumlah) - 1;
		$("#jumaddorder").val(total);
	}
}

function trackinginv(menu, kode){
	$.ajax({
		url			: usuper+"/ajax/inventory/inventory.php?menu="+menu,
		type		: "GET",
		async		: true,
		dataType	: "text",
		cache		: false,
		data		: { "keycode" : kode },
		success		: function(data){
			$("#trackinginv").html(data);
		}
	});
}

function cariorder(){
	var kode	= $("#invoice").val();
	$("#imgloading1").html('<img src="'+usuper+'/berkas/gif/tunggu.gif" style="width:15%;"  />');
	$.ajax({
		url			: usuper+"/ajax/cariorder/cariorder.php",
		type		: "POST",
		async		: true,
		dataType	: "text",
		cache		: false,
		data		: { "x" : kode },
		success		: function(data){
			var json	= JSON.parse(data);
			$("#supplier").val(json.supplier);
			$("#tglorder").val(json.tglorder);
			$("#ketorder").val(json.ketorder);
			$("#jumaddorder").val(json.jumaddorder);
			$("#jatuhtempo").val(json.jatuhtempo);
			$("#dataaddorder").html(json.tabel);
			$("#footerorder").html(json.footer);
		},
		error: function(data){ swal("Error", "Proses data error...", "error"); },
		complete: function(data){
			$("#imgloading1").html('');
		}
	});
}

function ceksales(){
	var jumlah	= $("#jumaddsales").val();
	var kode	= $("#outlet").val();
	$("#imgloading1").html('<img src="'+usuper+'/berkas/gif/tunggu.gif" style="width:15%;"  />');
	$.ajax({
		url			: usuper+"/ajax/ceksales/ceksales.php",
		type		: "POST",
		async		: true,
		dataType	: "text",
		cache		: false,
		data		: { "x" : kode },
		success		: function(data){
			var json	= JSON.parse(data);
			$("#koout").val(json.koout);
			$("#minorder").val(json.minorder);
			$("#diskon1").val(json.diskon1);
			$("#diskon2").val(json.diskon2);
			$("#jatuhtempo").val(json.jatuhtempo);
			$("#dataaddsales").html('<tr id="pilihoutlet"><td colspan="10">Tambah produk...</td></tr>');
		},
		error: function(data){ swal("Error", "Proses data error...", "error"); },
		complete: function(data){
			$("#imgloading1").html('');
		}
	});
}
/*
$('.search-box').SumoSelect({
	//placeholder: 'This is a placeholder',
	csvDispCount: 3,
	search: true
});

$('.select2').select2({
	placeholder: '-- Pilih Data --',
	searchInputPlaceholder: 'Search options'
});
*/
function getmbcode(nomor, id, kode, tgl){
	$("#nobcode"+nomor).html(kode+' <div>('+tgl+')</div>');
	$("#batchcode"+nomor).val(id);
	$('#modal1').modal('hide');
	//return false;
	//$('#modal1').modal('toggle'); 
}

function showproduct(nomor, id, nama, code, berat, kategori, satuankpr, satuan){
	$("#noproduct"+nomor).html('('+code+') '+nama);
	$("#satuanqty"+nomor).html(satuankpr);
	$("#detailproduct"+nomor).html(kategori+' ('+berat+' '+satuan+')');
	$("#product"+nomor).val(id);
	$("#noproduct"+nomor).html('('+code+') '+nama);
	$('#modal1').modal('hide');
}

function cekproduk(nomor){
	var produk	= $("#product"+nomor).val();
	if(produk==''){
		$("#pjumlah"+nomor).val('');
		swal("Error", "Pilih produk...", "error");	
	}
}

function getproduct(nomor, id, nama, code, harga, berat, kategori, satuan){
	var jumlah	= $("#pjumlah"+nomor).val(),
		jumlah	= jumlah=='' ? 1 : jumlah;
	var diskon	= $("#pdiskon"+nomor).val(),
		diskon	= diskon=='' ? 0 : diskon;
	var ptotal	= $("#ptotal"+nomor).val(),
		ptotal	= ptotal=='' ? 0 : bersih(ptotal);
	var pstotal	= $("#pstotal").val(),
		pstotal	= pstotal=='' ? 0 : bersih(pstotal);
	var pgtotal	= $("#pgtotal").val(),
		pgtotal	= pgtotal=='' ? 0 : bersih(pgtotal);
	var pppn	= $("#pppn").val(),
		pppn	= pppn=='' ? 0 : bersih(pppn);
	var subtot	= (jumlah * harga);
	var total	= Math.round((parseInt(subtot) - ((subtot * diskon) / 100)), 0);
	var stotal	= parseInt(total) + parseInt(pstotal) - parseInt(ptotal);
	var ppn		= Math.round(((stotal * 10) / 100), 0);
	var gtotal	= parseInt(stotal) + parseInt(ppn);
	$("#noproduct"+nomor).html('('+code+') '+nama);
	$("#detailproduct"+nomor).html(kategori+' ('+berat+' '+satuan+')');
	$("#product"+nomor).val(id);
	$("#pharga"+nomor).val(titik(harga));
	$("#psubtotal"+nomor).val(titik(subtot));
	$("#ptotal"+nomor).val(titik(total));
	$("#pstotal").val(titik(stotal));
	$("#pppn").val(titik(ppn));
	$("#pgtotal").val(titik(gtotal));
	$("#noproduct"+nomor).html('('+code+') '+nama);
	//$("#product"+nomor).val(id);
	$('#modal1').modal('hide');
}

function getproductsales(nomor, kode, produk, nama, code, harga, berat, kategori, satuanqty, satuan, bcode, tgled, stok){
	var jumlah	= $("#pjumlah"+nomor).val(),
		jumlah	= jumlah=='' ? 1 : jumlah;
	//var diskon	= $("#pdiskon"+nomor).val(),
		//diskon	= diskon=='' ? 0 : diskon;
	var diskon	= $("#diskon1").val();
	var ptotal	= $("#ptotal"+nomor).val(),
		ptotal	= ptotal=='' ? 0 : bersih(ptotal);
	var pstotal	= $("#pstotal").val(),
		pstotal	= pstotal=='' ? 0 : bersih(pstotal);
	var pgtotal	= $("#pgtotal").val(),
		pgtotal	= pgtotal=='' ? 0 : bersih(pgtotal);
	var pppn	= $("#pppn").val(),
		pppn	= pppn=='' ? 0 : bersih(pppn);
	var subtot	= (jumlah * harga);
	var total	= Math.round((parseInt(subtot) - ((subtot * diskon) / 100)), 0);
	var stotal	= parseInt(total) + parseInt(pstotal) - parseInt(ptotal);
	var ppn		= Math.round(((stotal * 10) / 100), 0);
	var gtotal	= parseInt(stotal) + parseInt(ppn);
	$("#noproduct"+nomor).html('('+code+') '+nama);
	$("#satuanqty"+nomor).html(satuanqty);
	$("#nobcode"+nomor).html(bcode);
	$("#tgled"+nomor).html(tgled);
	$("#prostok"+nomor).val(stok);
	$("#prodetail"+nomor).html(kategori+' ('+berat+' '+satuan+')');
	$("#product"+nomor).val(produk);
	$("#pharga"+nomor).val(titik(harga));
	//$("#psubtotal"+nomor).val(titik(subtot));
	$("#ptotal"+nomor).val(titik(total));
	$("#pstotal").val(titik(stotal));
	$("#pppn").val(titik(ppn));
	$("#pgtotal").val(titik(gtotal));
	$("#noproduct"+nomor).html('('+code+') '+nama);
	//$("#product"+nomor).val(id);
	cart(nomor, kode, 'addsales');
	$("#kodestok"+nomor).val(kode);
	$('#modal1').modal('hide');
}

function jumlahorder(nomor){
	var harga	= $("#pharga"+nomor).val(),
		harga	= harga=='' ? 0 : bersih(harga);
	var jumlah	= $("#pjumlah"+nomor).val(),
		jumlah	= jumlah=='' ? 0 : bersih(jumlah);
	var	minorder= $("#minorder").val();
	var	diskon1	= $("#diskon1").val();
	var	diskon2	= $("#diskon2").val();
	var diskon	= (parseInt(jumlah)<=parseInt(minorder)) ? diskon1 : diskon2;
	//var diskon	= $("#pdiskon"+nomor).val(),
		//diskon	= diskon=='' ? 0 : diskon;
	var ptotal	= $("#ptotal"+nomor).val(),
		ptotal	= ptotal=='' ? 0 : bersih(ptotal);
	var pstotal	= $("#pstotal").val(),
		pstotal	= pstotal=='' ? 0 : bersih(pstotal);
	var pgtotal	= $("#pgtotal").val(),
		pgtotal	= pgtotal=='' ? 0 : bersih(pgtotal);
	var subtot	= (jumlah * harga);
	var total	= Math.round((parseInt(subtot) - ((subtot * diskon) / 100)), 0);
	var stotal	= parseInt(total) + parseInt(pstotal) - parseInt(ptotal);
	var ppn		= Math.round(((stotal * 10) / 100), 0);
	var gtotal	= parseInt(stotal) + parseInt(ppn);
	$("#pdiskon"+nomor).val(diskon);
	$("#pjumlah"+nomor).val(titik(jumlah));
	$("#pharga"+nomor).val(titik(harga));
	$("#ptotal"+nomor).val(titik(total));
	$("#pstotal").val(titik(stotal));
	$("#pppn").val(titik(ppn));
	$("#pgtotal").val(titik(gtotal));
}

function hitungorder(nomor){
	var harga	= $("#pharga"+nomor).val(),
		harga	= harga=='' ? 0 : bersih(harga);
	var jumlah	= $("#pjumlah"+nomor).val(),
		jumlah	= jumlah=='' ? 0 : bersih(jumlah);
	var diskon	= $("#pdiskon"+nomor).val(),
		diskon	= diskon=='' ? 0 : diskon;
	var ptotal	= $("#ptotal"+nomor).val(),
		ptotal	= ptotal=='' ? 0 : bersih(ptotal);
	var pstotal	= $("#pstotal").val(),
		pstotal	= pstotal=='' ? 0 : bersih(pstotal);
	var pgtotal	= $("#pgtotal").val(),
		pgtotal	= pgtotal=='' ? 0 : bersih(pgtotal);
	var subtot	= (jumlah * harga);
	var total	= Math.round((parseInt(subtot) - ((subtot * diskon) / 100)), 0);
	var stotal	= parseInt(total) + parseInt(pstotal) - parseInt(ptotal);
	var ppn		= Math.round(((stotal * 10) / 100), 0);
	var gtotal	= parseInt(stotal) + parseInt(ppn);
	$("#pjumlah"+nomor).val(titik(jumlah));
	$("#pharga"+nomor).val(titik(harga));
	$("#ptotal"+nomor).val(titik(total));
	$("#pstotal").val(titik(stotal));
	$("#pppn").val(titik(ppn));
	$("#pgtotal").val(titik(gtotal));
}

function hitungsales(nomor){
	var pjumlah	= bersih($("#pjumlah"+nomor).val());
		//pjumlah	= pjumlah=='' ? '' : bersih(pjumlah);
	var harga	= $("#pharga"+nomor).val(),
		harga	= harga=='' ? 0 : bersih(harga);
	var stok	= $("#prostok"+nomor).val(),
		stok	= stok=='' ? 0 : bersih(stok);
	var jumlah	= bersih($("#pjumlah"+nomor).val()),
		jumlah 	= (parseInt(jumlah)==0 || jumlah=='' || parseInt(jumlah)>parseInt(stok)) ? 1 : bersih(jumlah);
	var diskon	= $("#pdiskon"+nomor).val(),
		diskon	= diskon=='' ? 0 : diskon;
	var ptotal	= $("#ptotal"+nomor).val(),
		ptotal	= ptotal=='' ? 0 : bersih(ptotal);
	var pstotal	= $("#pstotal").val(),
		pstotal	= pstotal=='' ? 0 : bersih(pstotal);
	var pgtotal	= $("#pgtotal").val(),
		pgtotal	= pgtotal=='' ? 0 : bersih(pgtotal);
	var subtot	= (jumlah * harga);
	var total	= Math.round((parseInt(subtot) - ((subtot * diskon) / 100)), 0);
	var stotal	= parseInt(total) + parseInt(pstotal) - parseInt(ptotal);
	var ppn		= Math.round(((stotal * 10) / 100), 0);
	var gtotal	= parseInt(stotal) + parseInt(ppn);
	if(pjumlah==0 || pjumlah==''){
		swal("Error", "Jumlah order tidak boleh kosong...", "error");
		$("#pjumlah"+nomor).val(1);
	} else {
		if(parseInt(pjumlah)>parseInt(stok)){
			swal("Error", "Jumlah order melebihi jumlah stok...", "error");
			$("#pjumlah"+nomor).val(1);
		}
	}
	$("#pharga"+nomor).val(titik(harga));
	//$("#psubtotal"+nomor).val(titik(subtot));
	$("#ptotal"+nomor).val(titik(total));
	$("#pstotal").val(titik(stotal));
	$("#pppn").val(titik(ppn));
	$("#pgtotal").val(titik(gtotal));
}

function jumlahsales(nomor){
	var outlet	= $("#outlet").val();
	var product	= $("#product"+nomor).val();
	var pjumlah	= bersih($("#pjumlah"+nomor).val());
	var harga	= $("#pharga"+nomor).val(),
		harga	= harga=='' ? 0 : bersih(harga);
	var stok	= $("#prostok"+nomor).val(),
		stok	= stok=='' ? 0 : bersih(stok);
	var jumlah	= bersih($("#pjumlah"+nomor).val()),
		jumlah 	= (parseInt(jumlah)==0 || jumlah=='' || parseInt(jumlah)>parseInt(stok)) ? 1 : bersih(jumlah);
	var	minorder= $("#minorder").val();
	var	diskon1	= $("#diskon1").val();
	var	diskon2	= $("#diskon2").val();
	var diskon	= (parseInt(jumlah)<=parseInt(minorder)) ? diskon1 : diskon2;
	var ptotal	= $("#ptotal"+nomor).val(),
		ptotal	= ptotal=='' ? 0 : bersih(ptotal);
	var pstotal	= $("#pstotal").val(),
		pstotal	= pstotal=='' ? 0 : bersih(pstotal);
	var pgtotal	= $("#pgtotal").val(),
		pgtotal	= pgtotal=='' ? 0 : bersih(pgtotal);
	var subtot	= (jumlah * harga);
	var total	= Math.round((parseInt(subtot) - ((subtot * diskon) / 100)), 0);
	//var total	= parseInt(subtot) - ((subtot * diskon) / 100);
	var stotal	= parseInt(total) + parseInt(pstotal) - parseInt(ptotal);
	var ppn		= Math.round(((stotal * 10) / 100), 0);
	var gtotal	= parseInt(stotal) + parseInt(ppn);
	if(outlet==''){
		swal("Error", "Pilih outlet dulu...", "error");
		$("#pjumlah"+nomor).val(1);
	} else {
		if(product==''){
			swal("Error", "Pilih produk dulu...", "error");
			$("#pjumlah"+nomor).val(1);
		} else {
			if(pjumlah==0 || pjumlah==''){
				swal("Error", "Jumlah order tidak boleh kosong...", "error");
				$("#pjumlah"+nomor).val(1);
			} else {
				if(parseInt(pjumlah)>parseInt(stok)){
					swal("Error", "Jumlah order melebihi jumlah stok...", "error");
					$("#pjumlah"+nomor).val(1);
				}
			}
			$("#pdiskon"+nomor).val(diskon);
			$("#pharga"+nomor).val(titik(harga));
			//$("#psubtotal"+nomor).val(titik(subtot));
			$("#ptotal"+nomor).val(titik(total));
			$("#pstotal").val(titik(stotal));
			$("#pppn").val(titik(ppn));
			$("#pgtotal").val(titik(gtotal));
		}
	}
}

function addrorder(nomor, kode){
	var jumlah	= $("#jumaddorder").val();
	var diskon1	= $("#diskon1").val();
	var pstotal	= $("#pstotal").val(),
		pstotal	= pstotal=='' ? 0 : bersih(pstotal);
	$.ajax({
		url		: usuper+"/ajax/addrorder/addrorder.php",
		type	: "POST",
		async	: true,
		dataType: "text",
		cache	: false,
		data	: { "j" : jumlah, "k" : kode, "l" : diskon1 },
		success	: function(data){
			var json	= JSON.parse(data);
			$("#dataaddorder").append(json.tabel);
			//$("#data"+menu).append(data);
			var total	= parseInt(jumlah) + 1;
			var stotal	= parseInt(pstotal) + parseInt(json.total);
			var ppn		= (stotal * 10) / 100;
			var gtotal	= parseInt(stotal) + parseInt(ppn);
			$("#jumaddorder").val(total);
			$("#pstotal").val(titik(stotal));
			$("#pppn").val(titik(ppn));
			$("#pgtotal").val(titik(gtotal));
		}
	});
}

function jumlahbayar(){
	var sisa	= $("#sisa").val(),
		sisa	= sisa=='' ? 0 : bersih(sisa);
	var bayar	= $("#bayar").val(),
		bayar	= bayar=='' ? 0 : bersih(bayar);
	if(parseInt(bayar)==0){
		swal("Error", "Jumlah bayar tidak boleh kosong...", "error");
		$("#bayar").val('');
	} else if(parseInt(bayar)>parseInt(sisa)){
		swal("Error", "Jumlah bayar lebih besar dari sisa tagihan...", "error");
		$("#bayar").val('');
	}
}

function caridata(modal, menu, cari){
	$.ajax({
		//contentType	: "application/json; charset=utf-8",
		url			: usuper+"/modal/"+modal+"/"+modal+".php",
		type		: "POST",
		async		: true,
		dataType	: "text",
		cache		: false,
		data		: { "menu" : menu, "cari" : cari },
		success	: function(data) {
			$(".modal-content").html(data);
		}
	});
}

function tampilin(menu){
	$("#tampilin").html('');
	$.ajax({
		url		: usuper+"/ajax/"+menu+"/"+menu+".php",
		type	: "POST",
		async	: true,
		dataType: "text",
		cache	: false,
		data	: {},
		success	: function(data){
			var json	= JSON.parse(data);
			$("#tampilih").html(json.tabel);
		}
	});
}