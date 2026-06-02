<?php
	class Date{
		function pecahTgl($tgl,$cari){
			$pecah	= explode('-',$tgl);
			$date	= ($cari=='Thn') ? $pecah[0] : (($cari=='Bln') ? $pecah[1] : $pecah[2]);
			return $date;
		}
		
		function oprPeriode($format,$kalku,$tgl){
			$hasil	= date($format, strtotime($kalku, strtotime($tgl)));
			return $hasil;
		}

		function forbul($bln){
			$jum	= strlen($bln);
			$bulan	= ($jum==1) ? "0$bln" : $bln;
			return $bulan;
		}

		function olahTgl($tanggal,$kueri){
			$olah	= date("Y-m-d H:i:s", strtotime($kueri, strtotime($tanggal)));
			return $olah;
		}

		function bulanTahun($tgl){
			$date		= new Date;
			$tanggal	= explode('-',$tgl);
			$bulan		= $date->getBulan($tanggal[1]);
			return substr($bulan,0,3).' '.$tanggal[0];		 
		}

		function forTgl($tgl){
			$tahun	= substr($tgl,0,4);
			$bulan	= substr($tgl,4,2);
			$hari	= substr($tgl,6,2);
			$format	= $tahun.'-'.$bulan.'-'.$hari;
			return $format;
		}
		
		function ambil_tgl($data){
			$tgl	= substr($data,0,10);
			return $tgl;
		}
	
		function ambil_jam($data){
			$jam	= substr($data,11,5);
			return $jam;
		}
	
		function garing($data){
			$tgl		= explode("-",$data);
			$tanggal 	= $tgl[2]."/".$tgl[1]."/".$tgl[0];	
			return $tanggal;
		}
	
		function strip($data){
			$tgl		= explode("-",$data);
			$tanggal 	= $tgl[2]."-".$tgl[1]."-".$tgl[0];	
			return $tanggal;
		}
		
		function nyetrip($data){
			$tgl		= explode("-",$data);
			$tanggal 	= $tgl[0]."".$tgl[1]."".$tgl[2];	
			return $tanggal;
		}
	
		function tgl_indo($tgl){
			$date		= new Date;
			$tanggal	= substr($tgl,8,2);
			$bulan		= $date->getBulan(substr($tgl,5,2));
			$tahun		= substr($tgl,0,4);
			return $tanggal.' '.$bulan.' '.$tahun;		 
		}	
	
		function tgl_lokal($tgl){
			$date		= new Date;
			$tanggal	= substr($tgl,8,2);
			$bulan		= $date->getBulan(substr($tgl,5,2));
			$tahun		= substr($tgl,0,4);
			return $tanggal.'-'.substr($bulan,0,3).'-'.$tahun;		 
		}	
	
		function tanggal($tgl){
			$date		= new Date;
			$tanggal	= substr($tgl, 8, 2);
			$bulan		= $date->getBulan(substr($tgl, 5, 2));
			$tahun		= substr($tgl, 0, 4);
			return $tanggal.' '.substr($bulan,0,3).' '.$tahun;
		}
		
		function tglIndo($tgl){
			$date		= new Date;
			$tanggal	= substr($tgl, 8, 2);
			$bulan		= $date->getBulan(substr($tgl, 5, 2));
			$tahun		= substr($tgl, 0, 4);
			return $tanggal.' '.substr($bulan,0,3).' '.$tahun.' ('.substr($tgl, 11, 5).')';
		}

		function tglBlog($tgl){
			$date		= new Date;
			$tanggal	= substr($tgl, 8, 2);
			$bulan		= $date->getBulan(substr($tgl, 5, 2));
			$hari		= $date->getHari(substr($tgl, 1, 10));
			$tahun		= substr($tgl, 0, 4);
			$jam		= substr($tgl, 11, 8);
			return "$hari, $tanggal $bulan $tahun | ".substr($tgl, 11, 5)." WIB";
		}
		
		function tglVideo($tgl){
			$date		= new Date;
			$tanggal	= substr($tgl, 8, 2);
			$bulan		= $date->getBulan(substr($tgl, 5, 2));
			$tahun		= substr($tgl, 0, 4);
			$jam		= substr($tgl, 11, 8);
			return "$tanggal $bulan $tahun (".substr($tgl, 11, 5)." WIB)";
		}
		
		function getHari($tgl){
			$format		= date('D',strtotime($tgl));
			$hari		= array(
				'Sun' 	=> 'Minggu',
				'Mon' 	=> 'Senin',
				'Tue' 	=> 'Selasa',
				'Wed' 	=> 'Rabu',
				'Thu' 	=> 'Kamis',
				'Fri' 	=> 'Jumat',
				'Sat' 	=> 'Sabtu'
			);
			return $hari[$format];
		}
	
		function getBulan($bln){
			switch ($bln){
				case 1: 
					return "Januari";
					break;
				case 2:
					return "Februari";
					break;
				case 3:
					return "Maret";
					break;
				case 4:
					return "April";
					break;
				case 5:
					return "Mei";
					break;
				case 6:
					return "Juni";
					break;
				case 7:
					return "Juli";
					break;
				case 8:
					return "Agustus";
					break;
				case 9:
					return "September";
					break;
				case 10:
					return "Oktober";
					break;
				case 11:
					return "November";
					break;
				case 12:
					return "Desember";
					break;
			}
		}
	
		function tgl_eng($tgl){
			$date		= new Date;
			$tanggal 	= substr($tgl,8,2);
			$bulan 		= $date->getMonth(substr($tgl,5,2));
			$tahun		= substr($tgl,0,4);
			return $tanggal.' '.$bulan.' '.$tahun;		 
		}	
	
		function getMonth($bln){
			switch ($bln){
				case 1: 
					return "January";
					break;
				case 2:
					return "February";
					break;
				case 3:
					return "March";
					break;
				case 4:
					return "April";
					break;
				case 5:
					return "May";
					break;
				case 6:
					return "June";
					break;
				case 7:
					return "July";
					break;
				case 8:
					return "August";
					break;
				case 9:
					return "September";
					break;
				case 10:
					return "October";
					break;
				case 11:
					return "November";
					break;
				case 12:
					return "December";
					break;
			}
		}
	}
?>