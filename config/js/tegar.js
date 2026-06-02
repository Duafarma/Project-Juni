var	server	= window.location.pathname.split("/");
var	usuper	= window.location.origin + "/" + server[1];
var menu	= server[2].replace('.php','');
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
	var ppn		= (parseInt(harga) * 11) / 100;
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

function harpoout(){
	var jumlah	= $("#jumitem").val();
	var nomor	= parseInt(jumlah) + 1;
	$("#jumitem").val(nomor);
	$.ajax({
		url			: usuper+"/ajax/harpoout/harpoout.php",
		type		: "POST",
		//dataType: "json",
		async		: true,
		dataType	: "text",
		//cache	: false,
		data		: { "n" : nomor },
		success		: function(data){
			//var json	= JSON.parse(data);
			$("#tblharga").append(data);
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
		// dataType: "json",
		async		: true,
		dataType	: "text",
		// cache	: false,
		data		: { "x" : kode },
		success		: function(data){
			var json	= JSON.parse(data);
			$("#namaoutlet").val(json.namaoutlet);
			$("#tglfaktur").val(json.tglfaktur);
			$("#tgltempo").val(json.tgltempo);
		}
	});
}

function disprotlet(){
	var jumlah	= $("#jumitem").val();
	var nomor	= parseInt(jumlah) + 1;
	$("#jumitem").val(nomor);
	$.ajax({
		url			: usuper+"/ajax/disprotlet/disprotlet.php",
		type		: "POST",
		//dataType: "json",
		async		: true,
		dataType	: "text",
		//cache	: false,
		data		: { "n" : nomor },
		success		: function(data){
			//var json	= JSON.parse(data);
			$("#tbldiskon").append(data);
		}
	});
}

function additem(tabel, hitung, lokasi){
	var jumlah	= $("#"+hitung).val();
	var nomor	= parseInt(jumlah) + 1;
	$("#"+hitung).val(nomor);
	$.ajax({
		url			: usuper+"/ajax/"+lokasi+"/"+lokasi+".php",
		type		: "POST",
		//dataType: "json",
		async		: true,
		dataType	: "text",
		//cache	: false,
		data		: { "n" : nomor },
		success		: function(data){
			//var json	= JSON.parse(data);
			$("#"+tabel).append(data);
		}
	});
}

function removeitem(hitung, kolom, nomor){
	var jumlah	= $("#"+hitung).val();
	$("#"+kolom+nomor).remove();
	var total	= parseInt(jumlah) - 1;
	$("#"+hitung).val(total);
}

function notiflegal(kode, nama){
	$.ajax({
		url			: usuper+"/ajax/notif"+nama+"/notif"+nama+".php",
		type		: "POST",
		//dataType: "json",
		async		: true,
		dataType	: "text",
		//cache	: false,
		data		: { "c" : kode },
		success		: function(data){
			var json	= JSON.parse(data);
			$("#outlet"+nama).html(json.tabel);
			$("#nama"+nama).html(json.nama);
		},
		error: function(data){ swal("Error", "Proses data error...", "error"); },
		complete: function(data){}
	});
}

$("#formtransaksi1").submit(function(e){
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
			} 
		},
		complete: function(data){
			$("#bsave").prop("disabled", false);
			$("#formtransaksi1").get(0).reset();
			$("#imgloading").html('');
		}
	});
});

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
	var caridata	= $("#caridata").val();
	$.ajax({
		url			: usuper+"/json/"+menu+"/"+menu+".php",
		type		: "GET",
		//dataType: "json",
		async		: true,
		dataType	: "text",
		//contentType	: "application/x-www-form-urlencoded;charset=UTF-8",
		//contentType	: "text/html;charset=windows-1252",
		//contentType	: "application/x-www-form-urlencoded; charset=iso-8859-1",
		//cache	: false,
		data		: { "caridata" : caridata, "halaman" : halaman, "maximal" : maximal, "menudata" : menu },
		//encode		: true,
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

$("#formmenu").submit(function(e){
	e.preventDefault();
	var modal	= $("#namamodal").val();
	$("#btnsave").prop("disabled", true);
	$("#imgloading").html('<img src="'+usuper+'/berkas/gif/tunggu.gif" style="width:15%;"  />');
	$.ajax({
		url			: usuper+"/modal/"+modal+"/action.php",
		type		: "POST",
		async		: true,
		dataType	: "text",
		data		: new FormData(this),
		contentType	: false,
		cache		: false,
		processData	: false,
		beforeSend	: function(){},
		success: function(data){
			var redirectUrl = usuper+"/"+data;
			if(modal === 'masterprogramproduk'){
				swal({
					title	: "Selamat!",
					text	: "Data berhasil disimpan.",
					type	: "success",
					timer	: 2000,
					showCancelButton: false,
					showConfirmButton: false
				}, function(){
					window.location.href = redirectUrl;
				});
			} else {
				window.location.href = redirectUrl;
			}
		},
		error: function(data){ swal("Maaf!", "Proses data error...", "error"); },
		complete: function(data){
			$("#btnsave").prop("disabled", false);
			$("#formmenu").get(0).reset();
			$("#imgloading").html('');
		}
	});
});


$("#formtransaksi2").submit(function(e){
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
			} else { swal("berhasil!", "Data berhasil di"+nact+"...", "error"); }
		},
		error: function(data){ swal("Maaf!", "Proses data berhasil...", "error"); },
		complete: function(data){
			$("#bsave").prop("disabled", false);
			$("#formtransaksi2").get(0).reset();
			$("#imgloading").html('');
		}
	});
});


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

function harpoout(){
	var jumlah	= $("#jumitem").val();
	var nomor	= parseInt(jumlah) + 1;
	$("#jumitem").val(nomor);
	$.ajax({
		url			: usuper+"/ajax/harpoout/harpoout.php",
		type		: "POST",
		//dataType: "json",
		async		: true,
		dataType	: "text",
		//cache	: false,
		data		: { "n" : nomor },
		success		: function(data){
			//var json	= JSON.parse(data);
			$("#tblharga").append(data);
		}
	});
}

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

function addsales(nomor, outlet) {
    var cart = $("#cartaddsales").val();  // Ambil nilai dari input dengan id cartaddsales
    var mitra = $("#" + outlet).val();  // Ambil nilai dari elemen outlet

    $.ajax({
        url: usuper + "/modal/addsales/addsales.php",  // URL untuk AJAX
        type: "POST",
        async: true,
        dataType: "text",
        cache: false,
        data: {
            "m": mitra,
            "x": nomor,
            "y": cart,
        },
        success: function(data) {
            $(".modal-content").html(data);  // Tampilkan hasil di dalam modal
        }
    });
}
$('.product-link').click(function() {
    var id_tfk = $(this).data('id_tfk'); // Mengambil id_tfk dari data-id_tfk
    $.ajax({
        url: 'ambil_hargapim.php', // Ganti dengan file PHP untuk mengambil data harga
        method: 'GET',
        data: { id_tfk: id_tfk },
        success: function(response) {
            $('#harga').text(response); // Menampilkan harga pada elemen dengan id "harga"
        }
    });
});


// function addtransfer(nomor, outlet){
// 	var cart	= $("#cartaddtransfer").val();
// 	var mitra	= $("#"+outlet).val();
// 	$.ajax({
// 		url		: usuper+"/modal/addtransfer/addtransfer.php",
// 		type	: "POST",
// 		async	: true,
// 		dataType: "text",
// 		cache	: false,
// 		data	: { "m" : mitra, "x" : nomor, "y" : cart },
// 		success	: function(data){ $(".modal-content").html(data); }
// 	});
// }

function addmaximal(menu, outlet, maksimal){
	var total	= $('.itemproduct').length;
	var jumlah	= $("#jum"+menu).val();
	var diskon	= $("#diskon1").val();
	var mitra	= $("#"+outlet).val();
	if(mitra==''){
		swal("Maaf!", "Pilih "+outlet+" dulu...", "error");
	} else {
		if(parseInt(total)>=parseInt(maksimal)){
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
	var ppn		= (stotal * 11) / 100;
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
	var ppn		= (stotal * 11) / 100;
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

function deletetransfer(nomor){
	var jumlah	= $("#jumaaddtransfer").val();
	if(parseInt(jumlah)<=1){
		swal("Maaf!", "Tidak boleh dikosongkan...", "error");
	} else {
		$("#tratransfer"+nomor).remove();
		var total	= parseInt(jumlah) - 1;
		$("#jumaaddtransfer").val(total);
	}
}

function delprotlet(nomor){
	var jumlah	= $("#jumitem").val();
	$("#item"+nomor).remove();
	var total	= parseInt(jumlah) - 1;
	$("#jumitem").val(total);
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
			$("#fkout").val(json.fkout);
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

function showproduct(nomor, id, nama, code, berat, kategori, satuankpr, satuan, harga){
	$("#noproduct"+nomor).html('('+code+') '+nama);
	$("#satuanqty"+nomor).html(satuankpr);
	$("#detailproduct"+nomor).html(kategori+' ('+berat+' '+satuan+')');
	$("#product"+nomor).val(id);
	if(typeof harga !== 'undefined' && harga !== '' && $("#pharga"+nomor).length){
		$("#pharga"+nomor).val(titik(harga));
	}
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
	var ppn		= Math.round(((stotal * 11) / 100), 0);
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

function getproductsales(nomor, kode, produk, nama, code, harga, berat, kategori, satuanqty, satuan, bcode, tgled, gudang, stok, diskon){
	var jumlah	= $("#pjumlah"+nomor).val(),
		jumlah	= jumlah=='' ? 1 : jumlah;
	//var diskon	= $("#pdiskon"+nomor).val(),
		//diskon	= diskon=='' ? 0 : diskon;
	//var diskon	= $("#diskon1").val();
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
	var ppn		= Math.round(((stotal * 11) / 100), 0);
	var gtotal	= parseInt(stotal) + parseInt(ppn);
	$("#noproduct"+nomor).html('('+code+') '+nama);
	$("#satuanqty"+nomor).html(satuanqty);
	$("#nobcode"+nomor).html(bcode);
	$("#tgled"+nomor).html(tgled);
	$("#gudang"+nomor).html(gudang);
	$("#pdiskon"+nomor).val(diskon);
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
	var ppn		= Math.round(((stotal * 11) / 100), 0);
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
	var ppn		= Math.round(((stotal * 11) / 100), 0);
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
	var ppn		= Math.round(((stotal * 11) / 100), 0);
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
	//var	diskon1	= $("#diskon1").val();
	//var	diskon2	= $("#diskon2").val();
	//var diskon	= (parseInt(jumlah)<=parseInt(minorder)) ? diskon1 : diskon2;
	var diskon	= $("#pdiskon"+nomor).val();
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
	var ppn		= Math.round(((stotal * 11) / 100), 0);
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

			// Update booking draft dengan qty terbaru
			var id_tfk_faktur = $("#id_tfk_faktur").val();
			var stokid = $("#kodestok"+nomor).val();
			if (id_tfk_faktur && id_tfk_faktur !== '' && stokid && stokid !== '') {
				$.ajax({
					url: usuper + '/ajax/addsales/savebooking.php',
					type: 'POST',
					data: { action: 'update', id_tfk: id_tfk_faktur, id_psd: stokid, jumlah: jumlah },
					dataType: 'json',
					error: function() { /* silent fail */ }
				});
			}
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
			var ppn		= (stotal * 11) / 100;
			var gtotal	= parseInt(stotal) + parseInt(ppn);
			$("#jumaddorder").val(total);
			$("#pstotal").val(titik(stotal));
			$("#pppn").val(titik(ppn));
			$("#pgtotal").val(titik(gtotal));
		}
	});
}

function addtransfer(nomor, kode){
	var jumlah	= $("#jumaaddtransfer").val();
		pstotal	= pstotal=='' ? 0 : bersih(pstotal);
	// var pstotal	= $("#pstotal").val(),
	// 	pstotal	= pstotal=='' ? 0 : bersih(pstotal);
	$.ajax({
		url		: usuper+"/ajax/addtransfer/addtransfer.php",
		type	: "POST",
		async	: true,
		dataType: "text",
		cache	: false,
		data	: { "j" : jumlah, "k" : kode  },
		success	: function(data){
			var json	= JSON.parse(data);
			$("#dataaddtransfer").append(json.tabel);
			var total	= parseInt(jumlah) + 1;
			// var stotal	= parseInt(pstotal) + parseInt(json.total);
			// var ppn		= (stotal * 10) / 100;
			// var gtotal	= parseInt(stotal) + parseInt(ppn);
			$("#jumaaddtransfer").val(total);
			$("#pstotal").val(titik(stotal));
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

function sumitemrorder(){
	var jumlah	= $("#jumlah").val(),
		jumlah	= jumlah==='' ? 0 : bersih(jumlah);
	var harga	= $("#harga").val(),
		harga	= harga==='' ? 0 : bersih(harga);
	var diskon	= $("#diskon").val();
	var subtot	= jumlah * harga;
	var potong	= (subtot * diskon) / 100;
	var total	= parseInt(subtot) - parseInt(potong);
	$("#total").val(titik(total));
}

// add by suryo
function transferType(type, self_apl) {
	if (type == 'IN') {
		$("#transfer_apl_from").val("");
		$("#transfer_apl_from").trigger('change');
		$("#transfer_apl_to").val(self_apl);
		$("#transfer_apl_to").trigger('change');
		$("#labelTransferType").text("Jumlah Masuk");
	}
	if (type == 'OUT') {
		$("#transfer_apl_from").val(self_apl);
		$("#transfer_apl_from").trigger('change');
		$("#transfer_apl_to").val("");
		$("#transfer_apl_to").trigger('change');
		$("#labelTransferType").text("Jumlah Keluar");
	}
	if (type == '') {
		$("#transfer_apl_from").val("");
		$("#transfer_apl_from").trigger('change');
		$("#transfer_apl_to").val("");
		$("#transfer_apl_to").trigger('change');
		$("#labelTransferType").text("Jumlah");
	}
	cleanTransferStok();
}

function addProductTransfer(menu, type, from, to, date, self, maksimal){
	var total	= $('.itemproduct').length;
	var jumlah	= $("#count"+menu).val();
	var ttype	= $("#"+type).val();
	var tfrom	= $("#"+from).val();
	var tto		= $("#"+to).val();
	var tdate	= $("#"+date).val();
	var tfromnm	= $("#"+from+" option:selected").text();
	var ttonm	= $("#"+to+" option:selected").text();
	var ttypenm	= $("#"+type+" option:selected").text();
	if (ttype == '' || tfrom == '' || tto == '' || tdate == ''){
		swal("Maaf!", "Tipe, Tanggal, Transfer Asal & Tujuan tidak boleh kosong", "warning");
	} else if (tfrom == tto) {
		swal("Maaf!", "Transfer Asal & Tujuan tidak boleh dari system yang sama", "warning");
	} else if (parseInt(total) >= parseInt(maksimal)){
		swal("Maaf!", "Jumlah "+ttypenm+" melebihi maksimal "+maksimal+" produk", "warning");
	} else if (!checkAnyEmptyJumlah(menu)) {
		swal("Maaf!", "Jumlah "+ttypenm+" tidak boleh ada yang kosong", "warning");
	} else if (ttype == 'IN' && (tfrom == self || tto != self)) {
		swal("Warning", "Pilihan Transfer Asal dan Tujuan tidak benar", "warning");
	} else if (ttype == 'OUT' && (tfrom != self || tto == self)) {
		swal("Warning", "Pilihan Transfer Asal dan Tujuan tidak benar", "warning");
	} else {
		$.ajax({
			type	: "GET",
			url		: usuper+"/ajax/"+menu+"/"+menu+".php",
			data	: { "jumlah" : jumlah, "from": tfrom, "to": tto, "self": self, "fromnm": tfromnm, "tonm": ttonm, "type": ttype },
			success	: function(data){
				$("#data"+menu).append(data);
				var total	= parseInt(jumlah) + 1;
				$("#count"+menu).val(total);
			}
		});
	}
}

function removeTransferStok(menu, nomor){
	var jumlah	= $("#count"+menu).val();
	var total	= $("#ptotal"+nomor).val();
	var pstotal	= $("#pstotal").val();
	//var pgtotal	= $("#pgtotal").val();
	var stotal	= parseInt(bersih(pstotal)) - parseInt(bersih(total));
	var ppn		= (stotal * 1);
	var gtotal	= parseInt(stotal) + parseInt(ppn);
	if(parseInt(jumlah)<=1){
		swal("Maaf!", "Tidak boleh dikosongkan...", "error");
	} else {
		$("#tr"+menu+""+nomor).remove();
		var jitem = parseInt(jumlah) - 1;
		$("#count"+menu).val(jitem);
		$("#pstotal").val(titik(stotal));
		$("#pppn").val(titik(ppn));
		$("#pgtotal").val(titik(gtotal));
	}
}

function cleanTransferStok() {
	$("#dataaddProductTransfer tr").remove();
	$("#cartaddProductTransfer").val('');
	$("#countaddProductTransfer").val('0');
}

function checkTransferApl(type, tapl, self_apl, self_apl_name) {
	if (type != '') {
		if (tapl == 'from') {
			var from = $("#transfer_apl_from option:selected").val();
			if (type == 'IN' && from == self_apl) {
				swal("Warning", "Transfer "+type+" harus dari luar "+self_apl_name, "warning");
			}
			if (type == 'OUT' && from != self_apl) {
				swal("Warning", "Transfer "+type+" harus dari "+self_apl_name, "warning");
			}		
		}
		if (tapl == 'to') {
			var to = $("#transfer_apl_to option:selected").val();
			if (type == 'IN' && to != self_apl) {
				swal("Warning", "Transfer "+type+" harus dari "+self_apl_name, "warning");
			}
			if (type == 'OUT' && to == self_apl) {
				swal("Warning", "Transfer "+type+" harus dari luar "+self_apl_name, "warning");
			}
		}	
	} else {
		swal("Warning", "Tipe Transfer harus dipilih", "warning");
	}
	cleanTransferStok();
}

function addProductTransferModal(nomor, menu, type, fromnm, from){
	var cart	= $("#cartaddProductTransfer").val();
	var kode	= $("#product"+nomor).val();
	$.ajax({
		url		: usuper+"/modal/"+menu+"/"+menu+type+".php",
		type	: "POST",
		async	: true,
		dataType: "text",
		cache	: false,
		data	: { "x" : nomor, "y" : kode, "fromnm": fromnm, "type": type, "cart": cart, "from": from},
		success	: function(data){ $(".modal-content-lg").html(data); }
	});
}

function deleteProductTransfer(nomor){
	var jumlah	= $("#countaddProductTransfer").val();
	if(parseInt(jumlah)<=1){
		swal("Maaf!", "Tidak boleh dikosongkan...", "error");
	} else {
		$("#addProductTransfer"+nomor).remove();
		var total	= parseInt(jumlah) - 1;
		$("#countaddProductTransfer").val(total);
	}
}


function showProductTransfer(nomor, kode, produk, nama, code, harga, berat, kategori, satuanqty, satuan, bcode, tgled, stok, id_trd, tgl_psd, gudang) {
	$("#noproduct"+nomor).html('('+code+') '+nama);
	$("#product"+nomor).val(produk);
	$("#namaproduct"+nomor).val(nama);
	$("#kodestok"+nomor).val(kode);
	$("#prodetail"+nomor).html(kategori+' ('+berat+' '+satuan+')');
	$("#nobcode"+nomor).html(bcode);
	$("#bcode"+nomor).val(bcode);
	$("#idpsd"+nomor).val(kode);
	$("#id_trd"+nomor).val(id_trd);
	$("#tgl_expired"+nomor).val(tgled);
	$("#tgl_psd"+nomor).val(tgl_psd);
	$("#gudang"+nomor).val(gudang);
	$("#tgled"+nomor).html(tgled);
	$("#prostok"+nomor).html(stok);
	$("#pharga"+nomor).val(titik(harga));
	$("#satuanqty"+nomor).html(satuanqty);
	cart(nomor, kode, 'addProductTransfer');
	$('#modal2').modal('hide');
}

function cekProdukTransfer(nomor){
	var produk	= $("#product"+nomor).val();
	if(produk==''){
		$("#pjumlah"+nomor).val('');
		swal("Error", "Pilih produk...", "error");	
	} else {
		var stok = parseInt($("#prostok"+nomor).text());
		var jumlah = parseInt($("#pjumlah"+nomor).val().replace('.', ''));
		if (jumlah == 0 || jumlah == '') {
			swal("Maaf!", "Jumlah transfer harus diisi", "warning");
		}
		if (jumlah > stok) {
			$("#pjumlah"+nomor).val('');
			swal("Maaf!", "Jumlah yang diinput tidak boleh melebihi stok", "warning");
		}
	}
}

$("#formProductTransfer").submit(function(e){
	e.preventDefault();
	var nmenu	= $("#nmenu").val();
	var nact	= $("#nact").val();
	var type	= $("#transfer_apl_type").val();
	var count	= $("#countaddProductTransfer").val();
	var ttypenm	= $("#transfer_apl_type option:selected").text();
	// validation null product
	if (count == 0 || count == '0' || count == '') {
		swal("Maaf!", "Produk tidak boleh kosong", "error");
	// validation any null jumlah
	} else if (!checkAnyEmptyJumlah('addProductTransfer')) {
		swal("Maaf!", "Jumlah "+ttypenm+" tidak boleh ada yang kosong", "warning");
	} else {
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
				if(json.result=="Success"){
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
				} else { 
					swal("Maaf!", "Data gagal di"+nact+"..., pesan: "+json.result, "error"); 
				}
			},
			error: function(data){ swal("Maaf!", "Proses data error...", "error"); },
			complete: function(data){
				$("#bsave").prop("disabled", false);
				$("#formProductTransfer").get(0).reset();
				$("#imgloading").html('');
			}
		});
	}
});

function checkAnyEmptyJumlah(menu) {
	var count = $("#count"+menu).val();
	if (count != '0' && count != '') {
		for (let i = 1; i <= parseInt(count); i++) {
			var jumlah = $("#pjumlah"+i).val();
			if (jumlah == '0' || jumlah == '') {
				return false;
			}
		}
	}
	return true;
}

function approvalTransferStokModal(menu,id_ttr,approval) {
	$.ajax({
		url		: usuper+"/modal/"+menu+"/approval.php",
		type	: "POST",
		async	: true,
		dataType: "text",
		cache	: false,
		data	: {"id_ttr": id_ttr, "approval": approval},
		success	: function(data){
			$(".modal-content").html(data);
		}
	});
}

function formApprovalTransferStokModal() {
	var nmenu	= $("#nmenu").val();
	var nact	= $("#nact").val();
	var approval= $("#approval").val();
	var kode	= $("#kode").val();
	var kode_ext= $("#kode_ext").val();
	var id		= $("#id").val();
	var type	= $("#type").val();
	var id_app_from	= $("#id_app_from").val();
	var id_app_to	= $("#id_app_to").val();
	// validation null product
	$("#btnApprove").prop("disabled", true);
	$("#btnReject").prop("disabled", true);
	$("#imgloading").html('<img src="'+usuper+'/berkas/gif/tunggu.gif" style="width:15%;"  />');
	$.ajax({
		url			: usuper+"/modal/"+nmenu+"/action.php?act="+nact,
		type		: "POST",
		async		: true,
		dataType	: "text",
		data		: {"kode": kode, "approval": approval, "id": id, "type": type, "id_app_from": id_app_from, "id_app_to": id_app_to, "kode_ext": kode_ext},
		success: function(data){
			var json	= JSON.parse(data);
			if(json.result=="Success"){
				var approvalLabel = (approval === 1 || approval === '1') ? 'Approve' : 'Reject';
				swal({
					title	: "Selamat!",
					text	: "Data berhasil di "+approvalLabel+"...",
					type	: "success",
					timer	: 2000,
					showCancelButton: false,
					showConfirmButton: false
				}, function(){
					window.location.href = usuper+"/"+nmenu;
				});
			} else { 
				$("#btnApprove").prop("disabled", false);
				$("#btnReject").prop("disabled", false);
				$("#imgloading").html('');
				swal("Maaf!", "Data gagal di"+nact+"..., pesan: "+json.result, "error"); 
			}
		},
		error: function(data){ swal("Maaf!", "Proses data error...", "error"); },
		complete: function(data){
			$("#bsave").prop("disabled", false);
			$("#imgloading").html('');
			$("#btnApprove").prop("disabled", false);
			$("#btnReject").prop("disabled", false);
		}
	});
}

function loadTransferStokNotification(menu){
	$.ajax({
		url		: usuper+"/modal/"+menu+"/notification.php",
		type	: "GET",
		async	: true,
		dataType: "text",
		data	: {"menu": menu},
		success	: function(data){
			if (data!='') {
				$(".modal-content").html(data);
				$("#modal1").modal('show');
				setInterval(function(){
					$("#modal1").modal('hide');
				}, 10000);				
			}
		}
	});
}

$("#formsalespnp").submit(function(e){
	e.preventDefault();
	var modal	= $("#namamodal").val();
	$("#btnsave").prop("disabled", true);
	$("#imgloading").html('<img src="'+usuper+'/berkas/gif/tunggu.gif" style="width:15%;"  />');
	$.ajax({
		url			: usuper+"/modal/"+modal+"/action.php",
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
			if (json.status === "Success") {
				window.location.href = usuper+"/"+json.url;	
			} else {
				$("#btnsave").prop("disabled", false);
				$("#imgloading").html('');
				swal("Maaf!", "Pesan error: " + json.message, "warning");
			}
		},
		error: function(data) { 
			swal("Maaf!", "Proses data error...", "error"); 
		},
		complete: function(data){
			$("#btnsave").prop("disabled", false);
			$("#formmenu").get(0).reset();
			$("#imgloading").html('');
		}
	});
});


// end

//add by tegar

// FAKTUR DONASI
function getproductsalesd(nomor, kode, produk, nama, code, harga, berat, kategori, satuanqty, satuan, bcode, tgled, gudang, stok, diskon){
	var jumlah	= $("#pjumlah"+nomor).val(),
		jumlah	= jumlah=='' ? 1 : jumlah;
	//var diskon	= $("#pdiskon"+nomor).val(),
		//diskon	= diskon=='' ? 0 : diskon;
	//var diskon	= $("#diskon1").val();
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
	var ppn		= Math.round(((stotal * 11) / 100), 0);
	var gtotal	= parseInt(stotal) + parseInt(ppn);
	$("#noproduct"+nomor).html('('+code+') '+nama);
	$("#satuanqty"+nomor).html(satuanqty);
	$("#nobcode"+nomor).html(bcode);
	$("#tgled"+nomor).html(tgled);
	$("#gudang"+nomor).html(gudang);
	$("#pdiskon"+nomor).val(diskon);
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

function hitungsalesd(nomor){
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
	var ppn		= Math.round(((stotal * 11) / 100), 0);
	var gtotal	= parseInt(stotal) + parseInt(ppn);
	if(pjumlah==0 || pjumlah==''){
		swal("Error", "Jumlah Donasi tidak boleh kosong...", "error");
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

function jumlahsalesd(nomor){
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
	//var	diskon1	= $("#diskon1").val();
	//var	diskon2	= $("#diskon2").val();
	//var diskon	= (parseInt(jumlah)<=parseInt(minorder)) ? diskon1 : diskon2;
	var diskon	= $("#pdiskon"+nomor).val();
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
	var ppn		= Math.round(((stotal * 11) / 100), 0);
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
				swal("Error", "Jumlah Donasi tidak boleh kosong...", "error");
				$("#pjumlah"+nomor).val(1);
			} else {
				if(parseInt(pjumlah)>parseInt(stok)){
					swal("Error", "Jumlah Donasi melebihi jumlah stok...", "error");
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

function addsalesd(nomor, outlet){
	var cart	= $("#cartaddsales").val();
	var mitra	= $("#"+outlet).val();
	$.ajax({
		url		: usuper+"/modal/addsalesd/addsalesd.php",
		type	: "POST",
		async	: true,
		dataType: "text",
		cache	: false,
		data	: { "m" : mitra, "x" : nomor, "y" : cart },
		success	: function(data){ $(".modal-content").html(data); }
	});
}

function ceksalesd(){
	var jumlah	= $("#jumaddsales").val();
	var kode	= $("#outlet").val();
	$("#imgloading1").html('<img src="'+usuper+'/berkas/gif/tunggu.gif" style="width:15%;"  />');
	$.ajax({
		url			: usuper+"/ajax/ceksalesd/ceksalesd.php",
		type		: "POST",
		async		: true,
		dataType	: "text",
		cache		: false,
		data		: { "x" : kode },
		success		: function(data){
			var json	= JSON.parse(data);
			$("#koout").val(json.koout);
			$("#fkout").val(json.fkout);
			$("#minorder").val(json.minorder);
			$("#diskon1").val(json.diskon1);
			$("#diskon2").val(json.diskon2);
			// $("#jatuhtempo").val(json.jatuhtempo);
			$("#dataaddsales").html('<tr id="pilihoutlet"><td colspan="10">Tambah produk...</td></tr>');
		},
		error: function(data){ swal("Error", "Proses data error...", "error"); },
		complete: function(data){
			$("#imgloading1").html('');
		}
	});
}
function detailsalesd(){
	var kode	= $("#kodesales").val();
	$.ajax({
		url			: usuper+"/ajax/detailsalesd/detailsalesd.php",
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

function delsalesd(nomor){
	var stokid	= $("#kodestok"+nomor).val();
	var cart	= $("#cartaddsales").val();
	var jumlah	= $("#jumaddsales").val();
	var total	= $("#ptotal"+nomor).val();
	var pstotal	= $("#pstotal").val();
	var stotal	= parseInt(bersih(pstotal)) - parseInt(bersih(total));
	var ppn		= (stotal * 11) / 100;
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

$("#formsalespnpd").submit(function(e){
	e.preventDefault();
	var modal	= $("#namamodal").val();
	$("#btnsave").prop("disabled", true);
	$("#imgloading").html('<img src="'+usuper+'/berkas/gif/tunggu.gif" style="width:15%;"  />');
	$.ajax({
		url			: usuper+"/modal/"+modal+"/action.php",
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
			if (json.status === "Success") {
				window.location.href = usuper+"/"+json.url;	
			} else {
				$("#btnsave").prop("disabled", false);
				$("#imgloading").html('');
				swal("Maaf!", "Pesan error: " + json.message, "warning");
			}
		},
		error: function(data) { 
			swal("Maaf!", "Proses data error...", "error"); 
		},
		complete: function(data){
			$("#btnsave").prop("disabled", false);
			$("#formmenu").get(0).reset();
			$("#imgloading").html('');
		}
	});
});

// FAKTUR DONASI END

// FAKTUR PINJAMAN

function getproductsalesp(nomor, kode, produk, nama, code, harga, berat, kategori, satuanqty, satuan, bcode, tgled, gudang, stok, diskon){
	var jumlah	= $("#pjumlah"+nomor).val(),
		jumlah	= jumlah=='' ? 1 : jumlah;
	//var diskon	= $("#pdiskon"+nomor).val(),
		//diskon	= diskon=='' ? 0 : diskon;
	//var diskon	= $("#diskon1").val();
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
	var ppn		= Math.round(((stotal * 11) / 100), 0);
	var gtotal	= parseInt(stotal) + parseInt(ppn);
	$("#noproduct"+nomor).html('('+code+') '+nama);
	$("#satuanqty"+nomor).html(satuanqty);
	$("#nobcode"+nomor).html(bcode);
	$("#tgled"+nomor).html(tgled);
	$("#gudang"+nomor).html(gudang);
	$("#pdiskon"+nomor).val(diskon);
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

function hitungsalesp(nomor){
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
	var ppn		= Math.round(((stotal * 11) / 100), 0);
	var gtotal	= parseInt(stotal) + parseInt(ppn);
	if(pjumlah==0 || pjumlah==''){
		swal("Error", "Jumlah Donasi tidak boleh kosong...", "error");
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

function jumlahsalesp(nomor){
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
	//var	diskon1	= $("#diskon1").val();
	//var	diskon2	= $("#diskon2").val();
	//var diskon	= (parseInt(jumlah)<=parseInt(minorder)) ? diskon1 : diskon2;
	var diskon	= $("#pdiskon"+nomor).val();
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
	var ppn		= Math.round(((stotal * 11) / 100), 0);
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
				swal("Error", "Jumlah Donasi tidak boleh kosong...", "error");
				$("#pjumlah"+nomor).val(1);
			} else {
				if(parseInt(pjumlah)>parseInt(stok)){
					swal("Error", "Jumlah Donasi melebihi jumlah stok...", "error");
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

function addsalesp(nomor, outlet){
	var cart	= $("#cartaddsales").val();
	var mitra	= $("#"+outlet).val();
	$.ajax({
		url		: usuper+"/modal/addsalesp/addsalesp.php",
		type	: "POST",
		async	: true,
		dataType: "text",
		cache	: false,
		data	: { "m" : mitra, "x" : nomor, "y" : cart },
		success	: function(data){ $(".modal-content").html(data); }
	});
}

function ceksalesp(){
	var jumlah	= $("#jumaddsales").val();
	var kode	= $("#outlet").val();
	$("#imgloading1").html('<img src="'+usuper+'/berkas/gif/tunggu.gif" style="width:15%;"  />');
	$.ajax({
		url			: usuper+"/ajax/ceksalesp/ceksalesp.php",
		type		: "POST",
		async		: true,
		dataType	: "text",
		cache		: false,
		data		: { "x" : kode },
		success		: function(data){
			var json	= JSON.parse(data);
			$("#koout").val(json.koout);
			$("#fkout").val(json.fkout);
			$("#minorder").val(json.minorder);
			$("#diskon1").val(json.diskon1);
			$("#diskon2").val(json.diskon2);
			// $("#jatuhtempo").val(json.jatuhtempo);
			$("#dataaddsales").html('<tr id="pilihoutlet"><td colspan="10">Tambah produk...</td></tr>');
		},
		error: function(data){ swal("Error", "Proses data error...", "error"); },
		complete: function(data){
			$("#imgloading1").html('');
		}
	});
}
function detailsalesp(){
	var kode	= $("#kodesales").val();
	$.ajax({
		url			: usuper+"/ajax/detailsalesp/detailsalesp.php",
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

function delsalesp(nomor){
	var stokid	= $("#kodestok"+nomor).val();
	var cart	= $("#cartaddsales").val();
	var jumlah	= $("#jumaddsales").val();
	var total	= $("#ptotal"+nomor).val();
	var pstotal	= $("#pstotal").val();
	var stotal	= parseInt(bersih(pstotal)) - parseInt(bersih(total));
	var ppn		= (stotal * 11) / 100;
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

$("#formsalespnpp").submit(function(e){
	e.preventDefault();
	var modal	= $("#namamodal").val();
	$("#btnsave").prop("disabled", true);
	$("#imgloading").html('<img src="'+usuper+'/berkas/gif/tunggu.gif" style="width:15%;"  />');
	$.ajax({
		url			: usuper+"/modal/"+modal+"/action.php",
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
			if (json.status === "Success") {
				window.location.href = usuper+"/"+json.url;	
			} else {
				$("#btnsave").prop("disabled", false);
				$("#imgloading").html('');
				swal("Maaf!", "Pesan error: " + json.message, "warning");
			}
		},
		error: function(data) { 
			swal("Maaf!", "Proses data error...", "error"); 
		},
		complete: function(data){
			$("#btnsave").prop("disabled", false);
			$("#formmenu").get(0).reset();
			$("#imgloading").html('');
		}
	});
});

// FAKTUR PINJAMAN END

// FAKTUR KONSINYASI
function getproductsalesk(nomor, kode, produk, nama, code, harga, berat, kategori, satuanqty, satuan, bcode, tgled, gudang, stok, diskon){
	var jumlah	= $("#pjumlah"+nomor).val(),
		jumlah	= jumlah=='' ? 1 : jumlah;
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
	var ppn		= Math.round(((stotal * 11) / 100), 0);
	var gtotal	= parseInt(stotal) + parseInt(ppn);
	$("#noproduct"+nomor).html('('+code+') '+nama);
	$("#satuanqty"+nomor).html(satuanqty);
	$("#nobcode"+nomor).html(bcode);
	$("#tgled"+nomor).html(tgled);
	$("#gudang"+nomor).html(gudang);
	$("#pdiskon"+nomor).val(diskon);
	$("#prostok"+nomor).val(stok);
	$("#prodetail"+nomor).html(kategori+' ('+berat+' '+satuan+')');
	$("#product"+nomor).val(produk);
	$("#pharga"+nomor).val(titik(harga));
	$("#ptotal"+nomor).val(titik(total));
	$("#pstotal").val(titik(stotal));
	$("#pppn").val(titik(ppn));
	$("#pgtotal").val(titik(gtotal));
	$("#noproduct"+nomor).html('('+code+') '+nama);
	cart(nomor, kode, 'addsales');
	$("#kodestok"+nomor).val(kode);
	$('#modal1').modal('hide');
}

function hitungsalesk(nomor){
	var pjumlah	= bersih($("#pjumlah"+nomor).val());
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
	var ppn		= Math.round(((stotal * 11) / 100), 0);
	var gtotal	= parseInt(stotal) + parseInt(ppn);
	if(pjumlah==0 || pjumlah==''){
		swal("Error", "Jumlah Konsinyasi tidak boleh kosong...", "error");
		$("#pjumlah"+nomor).val(1);
	} else {
		if(parseInt(pjumlah)>parseInt(stok)){
			swal("Error", "Jumlah order melebihi jumlah stok...", "error");
			$("#pjumlah"+nomor).val(1);
		}
	}
	$("#pharga"+nomor).val(titik(harga));
	$("#ptotal"+nomor).val(titik(total));
	$("#pstotal").val(titik(stotal));
	$("#pppn").val(titik(ppn));
	$("#pgtotal").val(titik(gtotal));
}

function jumlahsalesk(nomor){
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
	var diskon	= $("#pdiskon"+nomor).val();
	var ptotal	= $("#ptotal"+nomor).val(),
		ptotal	= ptotal=='' ? 0 : bersih(ptotal);
	var pstotal	= $("#pstotal").val(),
		pstotal	= pstotal=='' ? 0 : bersih(pstotal);
	var pgtotal	= $("#pgtotal").val(),
		pgtotal	= pgtotal=='' ? 0 : bersih(pgtotal);
	var subtot	= (jumlah * harga);
	var total	= Math.round((parseInt(subtot) - ((subtot * diskon) / 100)), 0);
	var stotal	= parseInt(total) + parseInt(pstotal) - parseInt(ptotal);
	var ppn		= Math.round(((stotal * 11) / 100), 0);
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
				swal("Error", "Jumlah Konsinyasi tidak boleh kosong...", "error");
				$("#pjumlah"+nomor).val(1);
			} else {
				if(parseInt(pjumlah)>parseInt(stok)){
					swal("Error", "Jumlah Konsinyasi melebihi jumlah stok...", "error");
					$("#pjumlah"+nomor).val(1);
				}
			}
			$("#pdiskon"+nomor).val(diskon);
			$("#pharga"+nomor).val(titik(harga));
			$("#ptotal"+nomor).val(titik(total));
			$("#pstotal").val(titik(stotal));
			$("#pppn").val(titik(ppn));
			$("#pgtotal").val(titik(gtotal));
		}
	}
}

function addsalesk(nomor, outlet){
	var cart	= $("#cartaddsales").val();
	var mitra	= $("#"+outlet).val();
	$.ajax({
		url		: usuper+"/modal/addsalesk/addsalesk.php",
		type	: "POST",
		async	: true,
		dataType: "text",
		cache	: false,
		data	: { "m" : mitra, "x" : nomor, "y" : cart },
		success	: function(data){ $(".modal-content").html(data); }
	});
}

function ceksalesk(){
	var jumlah	= $("#jumaddsales").val();
	var kode	= $("#outlet").val();
	$("#imgloading1").html('<img src="'+usuper+'/berkas/gif/tunggu.gif" style="width:15%;"  />');
	$.ajax({
		url			: usuper+"/ajax/ceksalesk/ceksalesk.php",
		type		: "POST",
		async		: true,
		dataType	: "text",
		cache		: false,
		data		: { "x" : kode },
		success		: function(data){
			var json	= JSON.parse(data);
			$("#koout").val(json.koout);
			$("#fkout").val(json.fkout);
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

function detailsalesk(){
	var kode	= $("#kodesales").val();
	$.ajax({
		url			: usuper+"/ajax/detailsalesk/detailsalesk.php",
		type		: "POST",
		async		: true,
		dataType	: "text",
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

function delsalesk(nomor){
	var stokid	= $("#kodestok"+nomor).val();
	var cart	= $("#cartaddsales").val();
	var jumlah	= $("#jumaddsales").val();
	var total	= $("#ptotal"+nomor).val();
	var pstotal	= $("#pstotal").val();
	var stotal	= parseInt(bersih(pstotal)) - parseInt(bersih(total));
	var ppn		= (stotal * 11) / 100;
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

$("#formsalespnpk").submit(function(e){
	e.preventDefault();
	var modal	= $("#namamodal").val();
	$("#btnsave").prop("disabled", true);
	$("#imgloading").html('<img src="'+usuper+'/berkas/gif/tunggu.gif" style="width:15%;"  />');
	$.ajax({
		url			: usuper+"/modal/"+modal+"/action.php",
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
			if (json.status === "Success") {
				window.location.href = usuper+"/"+json.url;	
			} else {
				$("#btnsave").prop("disabled", false);
				$("#imgloading").html('');
				swal("Maaf!", "Pesan error: " + json.message, "warning");
			}
		},
		error: function(data) { 
			swal("Maaf!", "Proses data error...", "error"); 
		},
		complete: function(data){
			$("#btnsave").prop("disabled", false);
			$("#formmenu").get(0).reset();
			$("#imgloading").html('');
		}
	});
});

// FAKTUR KONSINYASI END

// FAKTUR RETUR

function getproductsalesr(nomor, kode, produk, nama, code, harga, berat, kategori, satuanqty, satuan, bcode, tgled, gudang, stok, diskon){
	var jumlah	= $("#pjumlah"+nomor).val(),
		jumlah	= jumlah=='' ? 1 : jumlah;
	//var diskon	= $("#pdiskon"+nomor).val(),
		//diskon	= diskon=='' ? 0 : diskon;
	//var diskon	= $("#diskon1").val();
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
	var ppn		= Math.round(((stotal * 11) / 100), 0);
	var gtotal	= parseInt(stotal) + parseInt(ppn);
	$("#noproduct"+nomor).html('('+code+') '+nama);
	$("#satuanqty"+nomor).html(satuanqty);
	$("#nobcode"+nomor).html(bcode);
	$("#tgled"+nomor).html(tgled);
	$("#gudang"+nomor).html(gudang);
	$("#pdiskon"+nomor).val(diskon);
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

function hitungsalesr(nomor){
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
	var ppn		= Math.round(((stotal * 11) / 100), 0);
	var gtotal	= parseInt(stotal) + parseInt(ppn);
	if(pjumlah==0 || pjumlah==''){
		swal("Error", "Jumlah Donasi tidak boleh kosong...", "error");
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

function jumlahsalesr(nomor){
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
	//var	diskon1	= $("#diskon1").val();
	//var	diskon2	= $("#diskon2").val();
	//var diskon	= (parseInt(jumlah)<=parseInt(minorder)) ? diskon1 : diskon2;
	var diskon	= $("#pdiskon"+nomor).val();
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
	var ppn		= Math.round(((stotal * 11) / 100), 0);
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
				swal("Error", "Jumlah Donasi tidak boleh kosong...", "error");
				$("#pjumlah"+nomor).val(1);
			} else {
				if(parseInt(pjumlah)>parseInt(stok)){
					swal("Error", "Jumlah Donasi melebihi jumlah stok...", "error");
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

function addsalesr(nomor, outlet){
	var cart	= $("#cartaddsales").val();
	var mitra	= $("#"+outlet).val();
	$.ajax({
		url		: usuper+"/modal/addsalesr/addsalesr.php",
		type	: "POST",
		async	: true,
		dataType: "text",
		cache	: false,
		data	: { "m" : mitra, "x" : nomor, "y" : cart },
		success	: function(data){ $(".modal-content").html(data); }
	});
}

function ceksalesr(){
	var jumlah	= $("#jumaddsales").val();
	var kode	= $("#outlet").val();
	$("#imgloading1").html('<img src="'+usuper+'/berkas/gif/tunggu.gif" style="width:15%;"  />');
	$.ajax({
		url			: usuper+"/ajax/ceksalesr/ceksalesr.php",
		type		: "POST",
		async		: true,
		dataType	: "text",
		cache		: false,
		data		: { "x" : kode },
		success		: function(data){
			var json	= JSON.parse(data);
			$("#koout").val(json.koout);
			$("#fkout").val(json.fkout);
			$("#minorder").val(json.minorder);
			$("#diskon1").val(json.diskon1);
			$("#diskon2").val(json.diskon2);
			// $("#jatuhtempo").val(json.jatuhtempo);
			$("#dataaddsales").html('<tr id="pilihoutlet"><td colspan="10">Tambah produk...</td></tr>');
		},
		error: function(data){ swal("Error", "Proses data error...", "error"); },
		complete: function(data){
			$("#imgloading1").html('');
		}
	});
}
function detailsalesr(){
	var kode	= $("#kodesales").val();
	$.ajax({
		url			: usuper+"/ajax/detailsalesr/detailsalesr.php",
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

function delsalesr(nomor){
	var stokid	= $("#kodestok"+nomor).val();
	var cart	= $("#cartaddsales").val();
	var jumlah	= $("#jumaddsales").val();
	var total	= $("#ptotal"+nomor).val();
	var pstotal	= $("#pstotal").val();
	var stotal	= parseInt(bersih(pstotal)) - parseInt(bersih(total));
	var ppn		= (stotal * 11) / 100;
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

$("#formsalespnpr").submit(function(e){
	e.preventDefault();
	var modal	= $("#namamodal").val();
	$("#btnsave").prop("disabled", true);
	$("#imgloading").html('<img src="'+usuper+'/berkas/gif/tunggu.gif" style="width:15%;"  />');
	$.ajax({
		url			: usuper+"/modal/"+modal+"/action.php",
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
			if (json.status === "Success") {
				window.location.href = usuper+"/"+json.url;	
			} else {
				$("#btnsave").prop("disabled", false);
				$("#imgloading").html('');
				swal("Maaf!", "Pesan error: " + json.message, "warning");
			}
		},
		error: function(data) { 
			swal("Maaf!", "Proses data error...", "error"); 
		},
		complete: function(data){
			$("#btnsave").prop("disabled", false);
			$("#formmenu").get(0).reset();
			$("#imgloading").html('');
		}
	});
});

// END FAKTUR RETUR

function getproductsalesl(nomor, kode, produk, nama, code, harga, berat, kategori, satuanqty, satuan, bcode, tgled, gudang, stok, diskon){
	var jumlah	= $("#pjumlah"+nomor).val(),
		jumlah	= jumlah=='' ? 1 : jumlah;
	//var diskon	= $("#pdiskon"+nomor).val(),
		//diskon	= diskon=='' ? 0 : diskon;
	//var diskon	= $("#diskon1").val();
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
	var ppn		= Math.round(((stotal * 11) / 100), 0);
	var gtotal	= parseInt(stotal) + parseInt(ppn);
	$("#noproduct"+nomor).html('('+code+') '+nama);
	$("#satuanqty"+nomor).html(satuanqty);
	$("#nobcode"+nomor).html(bcode);
	$("#tgled"+nomor).html(tgled);
	$("#gudang"+nomor).html(gudang);
	$("#pdiskon"+nomor).val(diskon);
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

function hitungsalesl(nomor){
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
	var ppn		= Math.round(((stotal * 11) / 100), 0);
	var gtotal	= parseInt(stotal) + parseInt(ppn);
	if(pjumlah==0 || pjumlah==''){
		swal("Error", "Jumlah Donasi tidak boleh kosong...", "error");
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

function jumlahsalesl(nomor){
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
	//var	diskon1	= $("#diskon1").val();
	//var	diskon2	= $("#diskon2").val();
	//var diskon	= (parseInt(jumlah)<=parseInt(minorder)) ? diskon1 : diskon2;
	var diskon	= $("#pdiskon"+nomor).val();
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
	var ppn		= Math.round(((stotal * 11) / 100), 0);
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
				swal("Error", "Jumlah Donasi tidak boleh kosong...", "error");
				$("#pjumlah"+nomor).val(1);
			} else {
				if(parseInt(pjumlah)>parseInt(stok)){
					swal("Error", "Jumlah Donasi melebihi jumlah stok...", "error");
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

function addsalesl(nomor, outlet){
	var cart	= $("#cartaddsales").val();
	var mitra	= $("#"+outlet).val();
	$.ajax({
		url		: usuper+"/modal/addsalesl/addsalesl.php",
		type	: "POST",
		async	: true,
		dataType: "text",
		cache	: false,
		data	: { "m" : mitra, "x" : nomor, "y" : cart },
		success	: function(data){ $(".modal-content").html(data); }
	});
}

function ceksalesl(){
	var jumlah	= $("#jumaddsales").val();
	var kode	= $("#outlet").val();
	$("#imgloading1").html('<img src="'+usuper+'/berkas/gif/tunggu.gif" style="width:15%;"  />');
	$.ajax({
		url			: usuper+"/ajax/ceksalesl/ceksalesl.php",
		type		: "POST",
		async		: true,
		dataType	: "text",
		cache		: false,
		data		: { "x" : kode },
		success		: function(data){
			var json	= JSON.parse(data);
			$("#koout").val(json.koout);
			$("#fkout").val(json.fkout);
			$("#minorder").val(json.minorder);
			$("#diskon1").val(json.diskon1);
			$("#diskon2").val(json.diskon2);
			// $("#jatuhtempo").val(json.jatuhtempo);
			$("#dataaddsales").html('<tr id="pilihoutlet"><td colspan="10">Tambah produk...</td></tr>');
		},
		error: function(data){ swal("Error", "Proses data error...", "error"); },
		complete: function(data){
			$("#imgloading1").html('');
		}
	});
}
function detailsalesl(){
	var kode	= $("#kodesales").val();
	$.ajax({
		url			: usuper+"/ajax/detailsalesl/detailsalesl.php",
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

function delsalesl(nomor){
	var stokid	= $("#kodestok"+nomor).val();
	var cart	= $("#cartaddsales").val();
	var jumlah	= $("#jumaddsales").val();
	var total	= $("#ptotal"+nomor).val();
	var pstotal	= $("#pstotal").val();
	var stotal	= parseInt(bersih(pstotal)) - parseInt(bersih(total));
	var ppn		= (stotal * 11) / 100;
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

$("#formsalespnpl").submit(function(e){
	e.preventDefault();
	var modal	= $("#namamodal").val();
	$("#btnsave").prop("disabled", true);
	$("#imgloading").html('<img src="'+usuper+'/berkas/gif/tunggu.gif" style="width:15%;"  />');
	$.ajax({
		url			: usuper+"/modal/"+modal+"/action.php",
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
			if (json.status === "Success") {
				window.location.href = usuper+"/"+json.url;	
			} else {
				$("#btnsave").prop("disabled", false);
				$("#imgloading").html('');
				swal("Maaf!", "Pesan error: " + json.message, "warning");
			}
		},
		error: function(data) { 
			swal("Maaf!", "Proses data error...", "error"); 
		},
		complete: function(data){
			$("#btnsave").prop("disabled", false);
			$("#formmenu").get(0).reset();
			$("#imgloading").html('');
		}
	});
});

// FAKTUR LAIN-LAIN
// END FAKTUR LAIN-LAIN

// PENGIRIMAN KURIR

function cekFaktur(){
	var id_tfk	= $("#id_tfk").val();
	var modal	= $("#nmenu").val();
	$("#nama_out").val = "";
	$("#id_out").val = "";
	$("#tgl_faktur").val = "";
	$("#tf_created").val = "";
	$("#imgloading1").html('<img src="'+usuper+'/berkas/gif/tunggu.gif" style="width:15%;"  />');
	$.ajax({
		url			: usuper+"/ajax/cekfaktur/cekfaktur.php",
		type		: "POST",
		async		: true,
		dataType	: "text",
		cache		: false,
		data		: { "x" : id_tfk },
		success		: function(data){
			var json	= JSON.parse(data);
			$("#nama_out").val(json.nama_out);
			$("#id_out").val(json.id_out);
			// if (modal=="pengiriman") {
				$("#tgl_faktur").val(json.tf_created);
				$("#tgl_tfk").val(json.tf_created);	
			// }
		},
		error: function(data){ swal("Error", "Proses data error...", "error"); },
		complete: function(data){
			$("#imgloading1").html('');
		}
	});
}

$("#formpengiriman").submit(function(e){
	e.preventDefault();
	var modal	= $("#nmenu").val();
	$("#bsave").prop("disabled", true);
	$("#imgloading").html('<img src="'+usuper+'/berkas/gif/tunggu.gif" style="width:15%;"  />');
	$.ajax({
		url			: usuper+"/modal/"+modal+"/action.php",
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
					text	: "Data berhasil di input...",
					type	: "success",
					timer	: 2000,
					showCancelButton: false,
					showConfirmButton: false
				}, function(){
					window.location.href = usuper+"/"+modal;
				// 	window.location.href = usuper+"/signout";
				});
			} else { swal("Maaf!", "Data gagal di input...", "error"); }
		},
		error: function(data){ swal("Maaf!", "Proses data error...", "error"); },
		complete: function(data){
			$("#bsave").prop("disabled", false);
			$("#form".modal).get(0).reset();
			$("#imgloading").html('');
		}
	});
});

// END PENGIRIMAN

// START OUTLET BARU

$("#formoutlet").submit(function(e){
	e.preventDefault();
	var modal	= $("#nmenu").val();
	var nact	= $("#nact").val();
	$("#bsave").prop("disabled", true);
	$("#imgloading").html('<img src="'+usuper+'/berkas/gif/tunggu.gif" style="width:15%;"  />');
	$.ajax({
		url			: usuper+"/modal/"+modal+"/action.php?act="+nact,
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
					text	: "Data berhasil di input...",
					type	: "success",
					timer	: 2000,
					showCancelButton: false,
					showConfirmButton: false
				}, function(){
					window.location.href = usuper+"/signout";
				});
			} else { swal("Maaf!", "Data gagal di input...", "error"); }
		},
		error: function(data){ swal("Maaf!", "Proses data error...", "error"); },
		complete: function(data){
			$("#bsave").prop("disabled", false);
			$("#form".modal).get(0).reset();
			$("#imgloading").html('');
		}
	});
});


$("#formtransaksis").submit(function(e){
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
					window.location.href = usuper+"/stockopnameap/v";
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

$("#formdokumen").submit(function(e){
	e.preventDefault();
	var modal	= $("#nmenu").val();
	var nact	= $("#nact").val();
	$("#bsave").prop("disabled", true);
	$("#imgloading").html('<img src="'+usuper+'/berkas/gif/tunggu.gif" style="width:15%;"  />');
	$.ajax({
		url			: usuper+"/modal/"+modal+"/action.php?act="+nact,
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
					text	: "Data berhasil di input...",
					type	: "success",
					timer	: 2000,
					showCancelButton: false,
					showConfirmButton: false
				}, function(){
					window.location.href = usuper+"/dokumenbalik";
				});
			} else { swal("Maaf!", "Data gagal di input...", "error"); }
		},
		error: function(data){ swal("Maaf!", "Proses data error...", "error"); },
		complete: function(data){
			$("#bsave").prop("disabled", false);
			$("#form".modal).get(0).reset();
			$("#imgloading").html('');
		}
	});
});


$("#formproduk").submit(function(e){
	e.preventDefault();
	var modal	= $("#nmenu").val();
	var nact	= $("#nact").val();
	$("#bsave").prop("disabled", true);
	$("#imgloading").html('<img src="'+usuper+'/berkas/gif/tunggu.gif" style="width:15%;"  />');
	$.ajax({
		url			: usuper+"/modal/"+modal+"/action.php?act="+nact,
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
					text	: "Data berhasil di input...",
					type	: "success",
					timer	: 2000,
					showCancelButton: false,
					showConfirmButton: false
				}, function(){
					window.location.href = usuper+"/produk";
				});
			} else { swal("Maaf!", "Data gagal di input...", "error"); }
		},
		error: function(data){ swal("Maaf!", "Proses data error...", "error"); },
		complete: function(data){
			$("#bsave").prop("disabled", false);
			$("#form".modal).get(0).reset();
			$("#imgloading").html('');
		}
	});
});

function ceksalespenggantianbarang() {
    var kode = $("#outlet").val();
    $("#imgloading1").html('<img src="'+usuper+'/berkas/gif/tunggu.gif" style="width:15%;"  />');
    
    $.ajax({
        url: usuper+"/ajax/ceksalespenggantianbarang/ceksalespenggantianbarang.php",
        type: "POST",
        async: true,
        dataType: "json",  // Ensure the server responds with JSON
        cache: false,
        data: { "x": kode },
        success: function(data) {
            $("#koout").val(data.koout);
            $("#fkout").val(data.fkout);
            $("#minorder").val(data.minorder);
            $("#diskon1").val(data.diskon1);
            $("#diskon2").val(data.diskon2);
            
            // Update the no_tfk_penjualan dropdown
            var $noTfkPenjualan = $("#no_tfk_penjualan");
            $noTfkPenjualan.empty(); // Clear existing options
            $noTfkPenjualan.append('<option value="">-- Pilih --</option>'); // Add default option

            $.each(data.faktur, function(index, item) {
                $noTfkPenjualan.append('<option value="' + item.id_tfk + '">' + item.kode_tfk + ' (' + item.nama_out + ')</option>');
            });

            $("#dataaddsales").html('<tr id="pilihoutlet"><td colspan="10">Tambah produk...</td></tr>');
        },
        error: function() { 
            swal("Error", "Proses data error...", "error"); 
        },
        complete: function() {
            $("#imgloading1").html('');
        }
    });
}

function cekFaktur(){
	var id_tfk	= $("#id_tfk").val();
	var modal	= $("#nmenu").val();
	$("#nama_out").val = "";
	$("#id_out").val = "";
	$("#tgl_faktur").val = "";
	$("#tf_created").val = "";
	$("#imgloading1").html('<img src="'+usuper+'/berkas/gif/tunggu.gif" style="width:15%;"  />');
	$.ajax({
		url			: usuper+"/ajax/cekfaktur/cekfaktur.php",
		type		: "POST",
		async		: true,
		dataType	: "text",
		cache		: false,
		data		: { "x" : id_tfk },
		success		: function(data){
			var json	= JSON.parse(data);
			$("#nama_out").val(json.nama_out);
			$("#id_out").val(json.id_out);
		},
		error: function(data){ swal("Error", "Proses data error...", "error"); },
		complete: function(data){
			$("#imgloading1").html('');
		}
	});
}

$("#formpengiriman").submit(function(e){
	e.preventDefault();
	var modal	= $("#nmenu").val();
	$("#bsave").prop("disabled", true);
	$("#imgloading").html('<img src="'+usuper+'/berkas/gif/tunggu.gif" style="width:15%;"  />');
	$.ajax({
		url			: usuper+"/modal/"+modal+"/action.php",
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
					text	: "Data berhasil di input...",
					type	: "success",
					timer	: 2000,
					showCancelButton: false,
					showConfirmButton: false
				}, function(){
					let cookie = {};
					document.cookie.split(';').forEach(function(el) {
						let [k,v] = el.split('=');
						cookie[k.trim()] = v;
					});
					if (cookie["jeniskuy"].toLowerCase() == 'kurir')  {
						window.location.href = usuper+"/home";
					} else {
						window.location.href = usuper+"/"+modal;
					}
					// window.location.href = usuper+"/signout";
				});
			} else { swal("Maaf!", "Data gagal di input...", "error"); }
		},
		error: function(data){ swal("Maaf!", "Proses data error...", "error"); },
		complete: function(data){
			$("#bsave").prop("disabled", false);
			$("#form".modal).get(0).reset();
			$("#imgloading").html('');
		}
	});
});



function ceksalespim(){
	var jumlah	= $("#jumaddsalespim").val();
	var kode	= $("#outlet").val();
	$("#imgloading1").html('<img src="'+usuper+'/berkas/gif/tunggu.gif" style="width:15%;"  />');
	$.ajax({
		url			: usuper+"/ajax/ceksalespim/ceksalespim.php",
		type		: "POST",
		async		: true,
		dataType	: "text",
		cache		: false,
		data		: { "x" : kode },
		success		: function(data){
			var json	= JSON.parse(data);
			$("#koout").val(json.koout);
			$("#fkout").val(json.fkout);
			$("#minorder").val(json.minorder);
			$("#diskon1").val(json.diskon1);
			$("#diskon2").val(json.diskon2);
			$("#jatuhtempo").val(json.jatuhtempo);
			$("#dataaddsalespim").html('<tr id="pilihoutlet"><td colspan="10">Tambah produk...</td></tr>');
		},
		error: function(data){ swal("Error", "Proses data error...", "error"); },
		complete: function(data){
			$("#imgloading1").html('');
		}
	});
}

function addsalespim(nomor, outlet) {
    var cart = $("#cartaddsalespim").val();  // Ambil nilai dari input dengan id cartaddsales
    var mitra = $("#" + outlet).val();  // Ambil nilai dari elemen outlet

    $.ajax({
        url: usuper + "/modal/addsalespim/addsalespim.php",  // URL untuk AJAX
        type: "POST",
        async: true,
        dataType: "text",
        cache: false,
        data: {
            "m": mitra,
            "x": nomor,
            "y": cart,
        },
        success: function(data) {
            $(".modal-content").html(data);  // Tampilkan hasil di dalam modal
        }
    });
}
function getproductsalespimtest(nomor, kode, produk, nama, code, harga, berat, kategori, satuanqty, satuan, bcode, tgled, gudang, stok, diskon){
	var jumlah	= $("#pjumlah"+nomor).val(),
		jumlah	= jumlah=='' ? 1 : jumlah;
	//var diskon	= $("#pdiskon"+nomor).val(),
		//diskon	= diskon=='' ? 0 : diskon;
	//var diskon	= $("#diskon1").val();
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
	var ppn		= Math.round(((stotal * 11) / 100), 0);
	var gtotal	= parseInt(stotal) + parseInt(ppn);
	$("#noproduct"+nomor).html('('+code+') '+nama);
	$("#satuanqty"+nomor).html(satuanqty);
	$("#nobcode"+nomor).html(bcode);
	$("#tgled"+nomor).html(tgled);
	$("#gudang"+nomor).html(gudang);
	$("#pdiskon"+nomor).val(diskon);
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
	cart(nomor, kode, 'addsalespim');
	$("#kodestok"+nomor).val(kode);
	$('#modal1').modal('hide');
}
function getproductsalespim(nomor, kode, produk, nama, code, harga, berat, kategori, satuanqty, satuan, bcode, tgled, gudang, stok, diskon) {
    var jumlah = $("#pjumlah" + nomor).val();
    jumlah = jumlah == '' ? 1 : jumlah;

    var ptotal = $("#ptotal" + nomor).val();
    ptotal = ptotal == '' ? 0 : bersih(ptotal);
    var pstotal = $("#pstotal").val();
    pstotal = pstotal == '' ? 0 : bersih(pstotal);
    var pgtotal = $("#pgtotal").val();
    pgtotal = pgtotal == '' ? 0 : bersih(pgtotal);
    var pppn = $("#pppn").val();
    pppn = pppn == '' ? 0 : bersih(pppn);

    var hargaNumeric = parseFloat(harga);  // Pastikan harga adalah angka
    if (isNaN(hargaNumeric)) {
        hargaNumeric = 0;  // Jika harga tidak valid, set ke 0
    }

    var subtot = (jumlah * hargaNumeric);  // Perhitungan subtotal
    var total = Math.round((parseInt(subtot) - ((subtot * diskon) / 100)), 0);
    var stotal = parseInt(total) + parseInt(pstotal) - parseInt(ptotal);
    var ppn = Math.round(((stotal * 11) / 100), 0);
    var gtotal = parseInt(stotal) + parseInt(ppn);

    // Update UI with new values
    $("#noproduct" + nomor).html('(' + code + ') ' + nama);
    $("#satuanqty" + nomor).html(satuanqty);
    $("#nobcode" + nomor).html(bcode);
    $("#tgled" + nomor).html(tgled);
    $("#gudang" + nomor).html(gudang);
    $("#pdiskon" + nomor).val(diskon);
    $("#prostok" + nomor).val(stok);
    $("#prodetail" + nomor).html(kategori + ' (' + berat + ' ' + satuan + ')');
    $("#product" + nomor).val(produk);
    $("#pharga" + nomor).val(titik(hargaNumeric));  // Menggunakan harga dalam format numerik
    $("#ptotal" + nomor).val(titik(total));
    $("#pstotal").val(titik(stotal));
    $("#pppn").val(titik(ppn));
    $("#pgtotal").val(titik(gtotal));

    cart(nomor, kode, 'addsalespim');
    $("#kodestok" + nomor).val(kode);
    $('#modal1').modal('hide');
}
function jumlahsalespim(nomor){
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
	//var	diskon1	= $("#diskon1").val();
	//var	diskon2	= $("#diskon2").val();
	//var diskon	= (parseInt(jumlah)<=parseInt(minorder)) ? diskon1 : diskon2;
	var diskon	= $("#pdiskon"+nomor).val();
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
	var ppn		= Math.round(((stotal * 11) / 100), 0);
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
function hitungsalespim(nomor){
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
	var ppn		= Math.round(((stotal * 11) / 100), 0);
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

function delsalespim(nomor){
	var stokid	= $("#kodestok"+nomor).val();
	var cart	= $("#cartaddsalespim").val();
	var jumlah	= $("#jumaddsalespim").val();
	var total	= $("#ptotal"+nomor).val();
	var pstotal	= $("#pstotal").val();
	var stotal	= parseInt(bersih(pstotal)) - parseInt(bersih(total));
	var ppn		= (stotal * 11) / 100;
	var gtotal	= parseInt(stotal) + parseInt(ppn);
	if(parseInt(jumlah)<=1){
		swal("Maaf!", "Tidak boleh dikosongkan...", "error");
	} else {
		var hasil	= cart.replace("-"+stokid, "").replace(stokid, ""),
			hasil	= (hasil.substr(0, 1)=="-") ? hasil.substr(1) : hasil;
		var jitem	= parseInt(jumlah) - 1;
		$("#cartaddsalespim").val(hasil);
		$("#traddsales"+nomor).remove();
		$("#jumaddsalespim").val(jitem);
		$("#pstotal").val(titik(stotal));
		$("#pppn").val(titik(ppn));
		$("#pgtotal").val(titik(gtotal));
	}
}

function addmaximalpim(menu, outlet, maksimal){
	var total	= $('.itemproduct').length;
	var jumlah	= $("#jum"+menu).val();
	var diskon	= $("#diskon1").val();
	var mitra	= $("#"+outlet).val();
	if(mitra==''){
		swal("Maaf!", "Pilih "+outlet+" dulu...", "error");
	} else {
		if(parseInt(total)>=parseInt(maksimal)){
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

function detailsalespim(){
	var kode	= $("#kodesales").val();
	$.ajax({
		url			: usuper+"/ajax/detailsalespim/detailsalespim.php",
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


function cekfretur(){
	var jumlah	= $("#jumaddsalesretur").val();
	var kode	= $("#supplier").val();
	$("#imgloading1").html('<img src="'+usuper+'/berkas/gif/tunggu.gif" style="width:15%;"  />');
	$.ajax({
		url			: usuper+"/ajax/cekfretur/cekfretur.php",
		type		: "POST",
		async		: true,
		dataType	: "text",
		cache		: false,
		data		: { "x" : kode },
		success		: function(data){
			var json	= JSON.parse(data);
			$("#kosup").val(json.kosup);
			$("#fksup").val(json.fksup);
			$("#minorder").val(json.minorder);
			$("#diskon1").val(json.diskon1);
			$("#diskon2").val(json.diskon2);
			$("#jatuhtempo").val(json.jatuhtempo);
			$("#dataaddsalesretur").html('<tr id="pilihoutlet"><td colspan="10">Tambah produk...</td></tr>');
		},
		error: function(data){ swal("Error", "Proses data error...", "error"); },
		complete: function(data){
			$("#imgloading1").html('');
		}
	});
}

function addsalesretur(nomor, supplier){
	var cart	= $("#cartaddsalesretur").val();
	var mitra	= $("#"+supplier).val();
	$.ajax({
		url		: usuper+"/modal/addsalesretur/addsalesretur.php",
		type	: "POST",
		async	: true,
		dataType: "text",
		cache	: false,
		data	: { "m" : mitra, "x" : nomor, "y" : cart },
		success	: function(data){ $(".modal-content").html(data); }
	});
}
function detailsalesretur(){
	var kode	= $("#kodesales").val();
	$.ajax({
		url			: usuper+"/ajax/detailsalesretur/detailsalesretur.php",
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
function getproductsalesretur(nomor, kode, produk, nama, code, harga, berat, kategori, satuanqty, satuan, bcode, tgled, gudang, stok, diskon){
	var jumlah	= $("#pjumlah"+nomor).val(),
		jumlah	= jumlah=='' ? 1 : jumlah;
	//var diskon	= $("#pdiskon"+nomor).val(),
		//diskon	= diskon=='' ? 0 : diskon;
	//var diskon	= $("#diskon1").val();
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
	var ppn		= Math.round(((stotal * 11) / 100), 0);
	var gtotal	= parseInt(stotal) + parseInt(ppn);
	$("#noproduct"+nomor).html('('+code+') '+nama);
	$("#satuanqty"+nomor).html(satuanqty);
	$("#nobcode"+nomor).html(bcode);
	$("#tgled"+nomor).html(tgled);
	$("#gudang"+nomor).html(gudang);
	$("#pdiskon"+nomor).val(diskon);
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
	cart(nomor, kode, 'addsalesretur');
	$("#kodestok"+nomor).val(kode);
	$('#modal1').modal('hide');
}

function hitungsalesretur(nomor){
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
	var ppn		= Math.round(((stotal * 11) / 100), 0);
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
function jumlahsalesretur(nomor){
	var supplier	= $("#supplier").val();
	var product	= $("#product"+nomor).val();
	var pjumlah	= bersih($("#pjumlah"+nomor).val());
	var harga	= $("#pharga"+nomor).val(),
		harga	= harga=='' ? 0 : bersih(harga);
	var stok	= $("#prostok"+nomor).val(),
		stok	= stok=='' ? 0 : bersih(stok);
	var jumlah	= bersih($("#pjumlah"+nomor).val()),
		jumlah 	= (parseInt(jumlah)==0 || jumlah=='' || parseInt(jumlah)>parseInt(stok)) ? 1 : bersih(jumlah);
	var	minorder= $("#minorder").val();
	//var	diskon1	= $("#diskon1").val();
	//var	diskon2	= $("#diskon2").val();
	//var diskon	= (parseInt(jumlah)<=parseInt(minorder)) ? diskon1 : diskon2;
	var diskon	= $("#pdiskon"+nomor).val();
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
	var ppn		= Math.round(((stotal * 11) / 100), 0);
	var gtotal	= parseInt(stotal) + parseInt(ppn);
	if(supplier==''){
		swal("Error", "Pilih supplier dulu...", "error");
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

function hitungsalesretur(nomor){
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
	var ppn		= Math.round(((stotal * 11) / 100), 0);
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

function disprosupplier(){
	var jumlah	= $("#jumitem").val();
	var nomor	= parseInt(jumlah) + 1;
	$("#jumitem").val(nomor);
	$.ajax({
		url			: usuper+"/ajax/disprosupplier/disprosupplier.php",
		type		: "POST",
		//dataType: "json",
		async		: true,
		dataType	: "text",
		//cache	: false,
		data		: { "n" : nomor },
		success		: function(data){
			//var json	= JSON.parse(data);
			$("#tbldiskon").append(data);
		}
	});
}
function addmaximalpim(menu, outlet, maksimal){
	var total	= $('.itemproduct').length;
	var jumlah	= $("#jum"+menu).val();
	var diskon	= $("#diskon1").val();
	var mitra	= $("#"+outlet).val();
	if(mitra==''){
		swal("Maaf!", "Pilih "+outlet+" dulu...", "error");
	} else {
		if(parseInt(total)>=parseInt(maksimal)){
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

function delprosup(nomor){
	var jumlah	= $("#jumitem").val();
	$("#item"+nomor).remove();
	var total	= parseInt(jumlah) - 1;
	$("#jumitem").val(total);
}
function hpimoutlet() {
    var jumlah = $("#tbldiskon tr").length; // Menghitung jumlah baris yang ada di tabel
    var nomor = jumlah + 1; // Menambahkan nomor unik

    $("#jumitem").val(nomor); // Update input hidden agar tetap sesuai jumlah baris

    $.ajax({
        url: usuper + "/ajax/hpimoutlet/hpimoutlet.php", // Pastikan URL ini benar
        type: "POST",
        dataType: "text",
        async: true,
        data: { "n": nomor },
        success: function (data) {
            $("#tbldiskon").append(data); // Menambahkan data baru tanpa mengganti yang lama
        },
        error: function (xhr, status, error) {
            console.error("Error: " + error);
        }
    });
}

function delprohpim(nomor){
	var jumlah	= $("#jumitem").val();
	$("#item"+nomor).remove();
	var total	= parseInt(jumlah) - 1;
	$("#jumitem").val(total);
}
function additemplk(tabel, hitung, lokasi){
	var jumlah	= $("#"+hitung).val();
	var nomor	= parseInt(jumlah) + 1;
	$("#"+hitung).val(nomor);
	$.ajax({
		url			: usuper+"/ajax/"+lokasi+"/"+lokasi+".php",
		type		: "POST",
		//dataType: "json",
		async		: true,
		dataType	: "text",
		//cache	: false,
		data		: { "n" : nomor },
		success		: function(data){
			//var json	= JSON.parse(data);
			$("#"+tabel).append(data);
		}
	});
}
function additemplk(tabel, hitung, lokasi){
	var jumlah	= $("#"+hitung).val();
	var nomor	= parseInt(jumlah) + 1;
	$("#"+hitung).val(nomor);
	$.ajax({
		url			: usuper+"/ajax/"+lokasi+"/"+lokasi+".php",
		type		: "POST",
		//dataType: "json",
		async		: true,
		dataType	: "text",
		//cache	: false,
		data		: { "n" : nomor },
		success		: function(data){
			//var json	= JSON.parse(data);
			$("#"+tabel).append(data);
		}
	});
}


// PIM
function cariorderpim(){
	var kode	= $("#invoice").val();
	$("#imgloading1").html('<img src="'+usuper+'/berkas/gif/tunggu.gif" style="width:15%;"  />');
	$.ajax({
		url			: usuper+"/ajax/cariorderpim/cariorderpim.php",
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

function jumlahorderpim(nomor){
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
	var ppn		= Math.round(((stotal * 11) / 100), 0);
	var pph		= Math.round(((stotal * 0.3) / 100), 0);
	var gtotal	= parseInt(stotal) + parseInt(ppn) + parseInt(pph);
	$("#pdiskon"+nomor).val(diskon);
	$("#pjumlah"+nomor).val(titik(jumlah));
	$("#pharga"+nomor).val(titik(harga));
	$("#ptotal"+nomor).val(titik(total));
	$("#pstotal").val(titik(stotal));
	$("#pppn").val(titik(ppn));
	$("#pph").val(titik(pph));
	$("#pgtotal").val(titik(gtotal));
}

function hitungorderpim(nomor){
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
	var total	= Math.round((parseInt(subtot) - ((subtot * diskon) / 100)));
	var stotal	= parseInt(total) + parseInt(pstotal) - parseInt(ptotal);
	var ppn		= Math.round(((stotal * 11) / 100));
	var pph		= Math.round(((stotal * 0.3) / 100));
	var gtotal	= parseInt(stotal) + parseInt(ppn) + parseInt(pph);
	$("#pjumlah"+nomor).val(titik(jumlah));
	$("#pharga"+nomor).val(titik(harga));
	$("#ptotal"+nomor).val(titik(total));
	$("#pstotal").val(titik(stotal));
	$("#pppn").val(titik(ppn));
	$("#pph").val(titik(pph));
	$("#pgtotal").val(titik(gtotal));
}

function addrorderpim(nomor, kode){
	var jumlah	= $("#jumaddorder").val();
	var diskon1	= $("#diskon1").val();
	var pstotal	= $("#pstotal").val(),
		pstotal	= pstotal=='' ? 0 : bersih(pstotal);
	$.ajax({
		url		: usuper+"/ajax/addrorderpim/addrorderpim.php",
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
			var ppn		= (stotal * 11) / 100;
			var pph 	= (stotal * 0.3) / 100;
			var gtotal	= parseInt(stotal) + parseInt(ppn) + parseInt(pph);
			$("#jumaddorder").val(total);
			$("#pstotal").val(titik(stotal));
			$("#pppn").val(titik(ppn));
			$("#pph").val(titik(pph));
			$("#pgtotal").val(titik(gtotal));
		}
	});
}

function remmaximalpim(menu, nomor){
	var jumlah	= $("#jum"+menu).val();
	var total	= $("#ptotal"+nomor).val();
	var pstotal	= $("#pstotal").val();
	//var pgtotal	= $("#pgtotal").val();
	var stotal	= parseInt(bersih(pstotal)) - parseInt(bersih(total));
	var ppn		= (stotal * 11) / 100;
	var pph 	= (stotal * 0.3) / 100;

	var gtotal	= parseInt(stotal) + parseInt(ppn) + parseInt(pph);
	if(parseInt(jumlah)<=1){
		swal("Maaf!", "Tidak boleh dikosongkan...", "error");
	} else {
		$("#tr"+menu+""+nomor).remove();
		var jitem	= parseInt(jumlah) - 1;
		$("#jum"+menu).val(jitem);
		$("#pstotal").val(titik(stotal));
		$("#pppn").val(titik(ppn));
		$("#pph").val(titik(pph));
		$("#pgtotal").val(titik(gtotal));
	}
}

// DPE
function cariorderdpe(){
	var kode	= $("#invoice").val();
	$("#imgloading1").html('<img src="'+usuper+'/berkas/gif/tunggu.gif" style="width:15%;"  />');
	$.ajax({
		url			: usuper+"/ajax/cariorderdpe/cariorderdpe.php",
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

function addrorderdpe(nomor, kode){
	var jumlah	= $("#jumaddorder").val();
	var diskon1	= $("#diskon1").val();
	var pstotal	= $("#pstotal").val(),
		pstotal	= pstotal=='' ? 0 : bersih(pstotal);
	$.ajax({
		url		: usuper+"/ajax/addrorderdpe/addrorderdpe.php",
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
			var gtotal	= parseInt(stotal);
			$("#jumaddorder").val(total);
			$("#pstotal").val(titik(stotal));
			$("#pgtotal").val(titik(gtotal));
		}
	});
}

function jumlahorderdpe(nomor){
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
	var gtotal	= parseInt(stotal);
	$("#pdiskon"+nomor).val(diskon);
	$("#pjumlah"+nomor).val(titik(jumlah));
	$("#pharga"+nomor).val(titik(harga));
	$("#ptotal"+nomor).val(titik(total));
	$("#pstotal").val(titik(stotal));
	$("#pgtotal").val(titik(gtotal));
}

function hitungorderdpe(nomor){
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
	var total	= Math.round((parseInt(subtot) - ((subtot * diskon) / 100)));
	var stotal	= parseInt(total) + parseInt(pstotal) - parseInt(ptotal);
	var gtotal	= parseInt(stotal);
	$("#pjumlah"+nomor).val(titik(jumlah));
	$("#pharga"+nomor).val(titik(harga));
	$("#ptotal"+nomor).val(titik(total));
	$("#pstotal").val(titik(stotal));
	$("#pgtotal").val(titik(gtotal));
}

function remmaximaldpe(menu, nomor){
	var jumlah	= $("#jum"+menu).val();
	var total	= $("#ptotal"+nomor).val();
	var pstotal	= $("#pstotal").val();
	//var pgtotal	= $("#pgtotal").val();
	var stotal	= parseInt(bersih(pstotal)) - parseInt(bersih(total));
	var gtotal	= parseInt(stotal);
	if(parseInt(jumlah)<=1){
		swal("Maaf!", "Tidak boleh dikosongkan...", "error");
	} else {
		$("#tr"+menu+""+nomor).remove();
		var jitem	= parseInt(jumlah) - 1;
		$("#jum"+menu).val(jitem);
		$("#pstotal").val(titik(stotal));
		$("#pgtotal").val(titik(gtotal));
	}
}




// END OUTLET BARU
// end