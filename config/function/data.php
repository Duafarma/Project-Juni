<?php
	class Data extends DB {
		function acak($length){
			$data	= '0A1B2C3D4E5F6G7H8I9J0K1L2M3N4O5P6Q7R8S9T0U2V3W4X5Y6Z7';
			$string = '';
			for($i=0; $i<$length; $i++) {
				$pos	= rand(0,strlen($data)-1);
				$string .= $data[$pos];
			}
			return $string;
		}
		
		function cariarray($string, $pisah, $cari){
			$array	= explode("$pisah", rtrim($string, "$pisah"));
			$hasil	= in_array($cari, $array) ? true : false;
			return $hasil;
		}

		function getIP() {
			$mycom	= file_get_contents('https://api.ipify.org');
			return $mycom;
		}

		function myinfo($cari) {
			$data	= new selData;
			$mine	= $data->getIP();
			$info	= json_decode(file_get_contents('http://getcitydetails.geobytes.com/GetCityDetails?fqcn='.$mine),true);
			return $info[$cari];
		}

		function sistem($cari) {
			$conn	= $this->open();
			$kode	= 1;
			$read	= $conn->prepare("SELECT $cari AS cari FROM sistem WHERE id_sis=:kode");
			$read->bindParam(':kode', $kode, PDO::PARAM_STR);
			$read->execute();
			$conn	= $this->close();
			$view	= $read->fetch(PDO::FETCH_ASSOC);
			return $view['cari'];
		}
		
		function transcodeorder($kunci, $kode, $tabel) {
            // Buka koneksi ke database
            $conn = $this->open();
        
            // Query untuk mendapatkan nilai numerik terbesar dari kode
            // Menggunakan SUBSTRING dan CAST untuk mendapatkan bagian nomor sebagai integer
            $query = "SELECT MAX(CAST(SUBSTRING($kode, 1, LENGTH($kode) - LENGTH('$kunci')) AS UNSIGNED)) AS kode 
                      FROM $tabel 
                      WHERE $kode LIKE '%$kunci'";
            $select = $conn->query($query)->fetch(PDO::FETCH_ASSOC);
            $conn = $this->close();
        
            // Jika tidak ada hasil, set nilai default
            if (!$select['kode']) {
                $select['kode'] = 0;
            }
        
            // Ambil nomor urut dan tambahkan 1
            $nourut = (int) $select['kode'];
            $nourut++;
        
            // Buat nomor urut dengan minimal 4 digit diikuti oleh kunci
            $unik = sprintf("%04d", $nourut) . $kunci;
        
            return $unik;
        }

		function myadmin($kode, $cari) {
			$conn	= $this->open();
			$read	= $conn->prepare("SELECT $cari AS cari FROM adminz WHERE id_adm=:kode");
			$read->bindParam(':kode', $kode, PDO::PARAM_STR);
			$read->execute();
			$conn	= $this->close();
			$view	= $read->fetch(PDO::FETCH_ASSOC);
			$hasil	= empty($kode) ? '' : $view['cari'];
			return $hasil;
		}

		function akses($kode, $url, $cari) {
			$conn	= $this->open();
			$read	= $conn->prepare("SELECT $cari AS cari FROM role_menu AS A LEFT JOIN sub_menu AS B ON A.id_smu=B.id_smu WHERE A.id_adm=:kode AND B.url_smu=:url");
			$read->bindParam(':kode', $kode, PDO::PARAM_STR);
			$read->bindParam(':url', $url, PDO::PARAM_STR);
			$read->execute();
			$conn	= $this->close();
			$view	= $read->fetch(PDO::FETCH_ASSOC);
			$hasil	= (empty($kode) || !is_array($view)) ? '' : $view['cari'];
			return $hasil;
		}

		function koutlet($kode, $cari) {
			$conn	= $this->open();
			$read	= $conn->prepare("SELECT $cari AS cari FROM kategori_outlet WHERE id_kot=:kode");
			$read->bindParam(':kode', $kode, PDO::PARAM_STR);
			$read->execute();
			$conn	= $this->close();
			$view	= $read->fetch(PDO::FETCH_ASSOC);
			$hasil	= empty($kode) ? '' : $view['cari'];
			return $hasil;
		}

		function outlet($kode, $cari) {
			$conn	= $this->open();
			$read	= $conn->prepare("SELECT $cari AS cari FROM outlet WHERE id_out=:kode");
			$read->bindParam(':kode', $kode, PDO::PARAM_STR);
			$read->execute();
			$conn	= $this->close();
			$view	= $read->fetch(PDO::FETCH_ASSOC);
			$hasil	= empty($kode) ? '' : $view['cari'];
			return $hasil;
		}

		function supplier($kode, $cari) {
			$conn	= $this->open();
			$read	= $conn->prepare("SELECT $cari AS cari FROM supplier WHERE id_sup=:kode");
			$read->bindParam(':kode', $kode, PDO::PARAM_STR);
			$read->execute();
			$conn	= $this->close();
			$view	= $read->fetch(PDO::FETCH_ASSOC);
			$hasil	= empty($kode) ? '' : $view['cari'];
			return $hasil;
		}
		
		function produk($kode, $cari) {
			$conn	= $this->open();
			$read	= $conn->prepare("SELECT $cari AS cari FROM produk WHERE id_pro=:kode");
			$read->bindParam(':kode', $kode, PDO::PARAM_STR);
			$read->execute();
			$conn	= $this->close();
			$view	= $read->fetch(PDO::FETCH_ASSOC);
			$hasil	= empty($kode) ? '' : $view['cari'];
			return $hasil;
		}

		function stokawal($kode, $tgl) {
			$conn	= $this->open();
			$rin	= $conn->prepare("SELECT IFNULL(SUM(A.jumlah_trd), 0) AS total FROM transaksi_receivedetail AS A INNER JOIN transaksi_receive AS B ON A.id_tre=B.id_tre WHERE A.id_pro=:kode AND B.tgl_tre<:tgl");
			$rin->bindParam(':kode', $kode, PDO::PARAM_STR);
			$rin->bindParam(':tgl', $tgl, PDO::PARAM_STR);
			$rin->execute();
			$vin	= $rin->fetch(PDO::FETCH_ASSOC);

			$rout	= $conn->prepare("SELECT IFNULL(SUM(A.jumlah_tfd), 0) AS total FROM transaksi_fakturdetail AS A INNER JOIN transaksi_faktur AS B ON A.id_tfk=B.id_tfk WHERE A.id_pro=:kode AND B.tgl_tfk<:tgl");
			$rout->bindParam(':kode', $kode, PDO::PARAM_STR);
			$rout->bindParam(':tgl', $tgl, PDO::PARAM_STR);
			$rout->execute();
			$vout	= $rout->fetch(PDO::FETCH_ASSOC);
			$conn	= $this->close();

			$hasil	= $vin['total'] - $vout['total'];
			return $hasil;
		}
		
		 function so($kode) {
			$conn	= $this->open();
			$rin	= $conn->prepare("SELECT IFNULL(SUM(qty_so), 0) AS total FROM produk_stokdetail WHERE  id_pro IN (:kode) ");
			$rin->bindParam(':kode', $kode, PDO::PARAM_STR);
			$rin->execute();
			$vin	= $rin->fetch(PDO::FETCH_ASSOC);
			return $vin['total'];
		}

		function sisapsd($kode) {
			$conn	= $this->open();
			$rin	= $conn->prepare("SELECT IFNULL(SUM(sisa_psd), 0) AS total FROM produk_stokdetail WHERE id_pro IN (:kode) ");
			$rin->bindParam(':kode', $kode, PDO::PARAM_STR);
			$rin->execute();
			$vin	= $rin->fetch(PDO::FETCH_ASSOC);
			return $vin['total'];
		}

		function selisih($kode) {
			$conn	= $this->open();
			$rin	= $conn->prepare("SELECT IFNULL(SUM(selisih), 0) AS total FROM total_inventory WHERE  id_pro IN (:kode) AND selisih<0 AND created_at >= DATE_SUB(NOW(), INTERVAL 2 DAY) ");
			$rin->bindParam(':kode', $kode, PDO::PARAM_STR);
			$rin->execute();
			$vin	= $rin->fetch(PDO::FETCH_ASSOC);
			return $vin['total'];
		}
		
		function selisihplus($kode) {
			$conn	= $this->open();
			$rin	= $conn->prepare("SELECT IFNULL(SUM(selisih), 0) AS total FROM total_inventory WHERE  id_pro IN (:kode) AND selisih>0 AND created_at >= DATE_SUB(NOW(), INTERVAL 2 DAY) ");
			$rin->bindParam(':kode', $kode, PDO::PARAM_STR);
			$rin->execute();
			$vin	= $rin->fetch(PDO::FETCH_ASSOC);
			return $vin['total'];
		}
		function stokin($kode, $tgl1, $tgl2) {
			$conn	= $this->open();
			$rin	= $conn->prepare("SELECT IFNULL(SUM(A.jumlah_trd), 0) AS total FROM transaksi_receivedetail AS A INNER JOIN transaksi_receive AS B ON A.id_tre=B.id_tre WHERE A.id_pro IN (:kode) AND B.tgl_tre>=:tgl1 AND B.tgl_tre<=:tgl2");
			$rin->bindParam(':kode', $kode, PDO::PARAM_STR);
			$rin->bindParam(':tgl1', $tgl1, PDO::PARAM_STR);
			$rin->bindParam(':tgl2', $tgl2, PDO::PARAM_STR);
			$rin->execute();
			$conn	= $this->close();
			$vin	= $rin->fetch(PDO::FETCH_ASSOC);
			return $vin['total'];
		}

		function stokout($kode, $tgl1, $tgl2) {
			$conn	= $this->open();
			$read	= $conn->prepare("SELECT IFNULL(SUM(A.jumlah_tfd), 0) AS total FROM transaksi_fakturdetail AS A INNER JOIN transaksi_faktur AS B ON A.id_tfk=B.id_tfk WHERE A.id_pro IN (:kode) AND B.tgl_tfk>=:tgl1 AND B.tgl_tfk<=:tgl2");
			$read->bindParam(':kode', $kode, PDO::PARAM_STR);
			$read->bindParam(':tgl1', $tgl1, PDO::PARAM_STR);
			$read->bindParam(':tgl2', $tgl2, PDO::PARAM_STR);
			$read->execute();
			$conn	= $this->close();
			$view	= $read->fetch(PDO::FETCH_ASSOC);
			return $view['total'];
		}

		function jumlahjual($tahun, $bulan) {
			$conn	= $this->open();
			$read	= $conn->prepare("SELECT SUM(total_tfk) AS total FROM transaksi_faktur WHERE YEAR(tgl_tfk)=:tahun AND MONTH(tgl_tfk)=:bulan");
			$read->bindParam(':tahun', $tahun, PDO::PARAM_STR);
			$read->bindParam(':bulan', $bulan, PDO::PARAM_STR);
			$read->execute();
			$conn	= $this->close();
			$view	= $read->fetch(PDO::FETCH_ASSOC);
			return $view['total'];
		}

    	function jumlahjualpim($tahun, $bulan) {
			$conn	= $this->open();
			$read	= $conn->prepare("SELECT SUM(total_tfk) AS total FROM transaksi_faktur_pim WHERE YEAR(tgl_tfk)=:tahun AND MONTH(tgl_tfk)=:bulan");
			$read->bindParam(':tahun', $tahun, PDO::PARAM_STR);
			$read->bindParam(':bulan', $bulan, PDO::PARAM_STR);
			$read->execute();
			$conn	= $this->close();
			$view	= $read->fetch(PDO::FETCH_ASSOC);
			return $view['total'];
		}
		function jumlahbelidpe($tahun, $bulan) {
           $conn = $this->open();
            $read = $conn->prepare("SELECT SUM(A.total_tre) AS total 
                                    FROM transaksi_receive AS A 
                                    LEFT JOIN supplier AS B ON A.id_sup = B.id_sup
                                    WHERE YEAR(A.tgl_tre) = :tahun 
                                    AND MONTH(A.tgl_tre) = :bulan
                                    AND B.id_sup = 'SUP1725675322'"); // Menambahkan kondisi id_mp langsung
            $read->bindParam(':tahun', $tahun, PDO::PARAM_STR);
            $read->bindParam(':bulan', $bulan, PDO::PARAM_STR);
            $read->execute();
            $conn = $this->close();
            $view = $read->fetch(PDO::FETCH_ASSOC);
            return $view['total'];
        }
        
        function jumlahbelipim($tahun, $bulan) {
             $conn = $this->open();
            $read = $conn->prepare("SELECT SUM(A.total_tre) AS total 
                                    FROM transaksi_receive AS A 
                                    LEFT JOIN supplier AS B ON A.id_sup = B.id_sup
                                    WHERE YEAR(A.tgl_tre) = :tahun 
                                    AND MONTH(A.tgl_tre) = :bulan
                                    AND B.id_sup = 'SUP1738590257'"); // Menambahkan kondisi id_mp langsung
            $read->bindParam(':tahun', $tahun, PDO::PARAM_STR);
            $read->bindParam(':bulan', $bulan, PDO::PARAM_STR);
            $read->execute();
            $conn = $this->close();
            $view = $read->fetch(PDO::FETCH_ASSOC);
            return $view['total'];
        }
		
		function jumlahbeli($tahun, $bulan) {
            $conn = $this->open();
            $read = $conn->prepare("SELECT COALESCE(SUM(A.total_tre), 0) AS total 
                                    FROM transaksi_receive AS A 
                                    LEFT JOIN supplier AS B ON A.id_sup = B.id_sup
                                    WHERE YEAR(A.tgl_tre) = :tahun 
                                    AND MONTH(A.tgl_tre) = :bulan
                                    AND MONTH(A.tglfak_tre) = :bulan
                                    AND B.id_sup = 'SUP1594639748'"); // Menambahkan kondisi id_mp langsung
            $read->bindParam(':tahun', $tahun, PDO::PARAM_STR);
            $read->bindParam(':bulan', $bulan, PDO::PARAM_STR);
            $read->execute();
            $conn = $this->close();
            $view = $read->fetch(PDO::FETCH_ASSOC);
            return $view['total'] ? $view['total'] : 0; // Jika total NULL, kembalikan 0
        }
        
        // function jumlahbeli($tahun, $bulan) {
        //     $conn = $this->open();
        //     $read = $conn->prepare("SELECT COALESCE(SUM(A.total_trd), 0) AS total 
        //                             FROM transaksi_receivedetail AS A 
        //                             INNER JOIN produk AS B ON A.id_pro = B.id_pro
        //                             INNER JOIN master_principle AS C ON B.nama_p = C.id_mp
        //                             INNER JOIN transaksi_receive AS D ON A.id_tre = D.id_tre
        //                             WHERE YEAR(D.tgl_tre) = :tahun 
        //                             AND MONTH(D.tgl_tre) = :bulan
        //                             AND B.nama_p = 'MP0000000001'"); 
        //     $read->bindParam(':tahun', $tahun, PDO::PARAM_STR);
        //     $read->bindParam(':bulan', $bulan, PDO::PARAM_STR);
        //     $read->execute();
        //     $conn = $this->close();
        //     $view = $read->fetch(PDO::FETCH_ASSOC);
        //     return $view['total'] ? $view['total'] : 0; // Jika total NULL, kembalikan 0
        // }


		function outletjual($kode, $periode) {
			$conn	= $this->open();
			$read	= $conn->prepare("SELECT SUM(total_tfk) AS total FROM transaksi_faktur WHERE id_out=:kode AND LEFT(tgl_tfk, 7)=:periode");
			$read->bindParam(':kode', $kode, PDO::PARAM_STR);
			$read->bindParam(':periode', $periode, PDO::PARAM_STR);
			$read->execute();
			$conn	= $this->close();
			$view	= $read->fetch(PDO::FETCH_ASSOC);
			return $view['total'];
		}
		
		function angka($angka){
			$hasil 	= number_format($angka, 0, ',', '.');
			return $hasil;
		}
		
		function stokawall($kode) {
			$conn	= $this->open();
			$rin	= $conn->prepare("SELECT IFNULL(SUM(qty_so), 0) AS total FROM stock_awal WHERE  id_pro IN (:kode) ");
			$rin->bindParam(':kode', $kode, PDO::PARAM_STR);
			$rin->execute();
			$conn	= $this->close();
			$vin	= $rin->fetch(PDO::FETCH_ASSOC);
			return $vin['total'];
		}
		
		
		
		function stokinn($kode) {
			$conn	= $this->open();
			$rin	= $conn->prepare("SELECT IFNULL(SUM(A.jumlah_trd),0) AS total FROM transaksi_receivedetail AS A INNER JOIN transaksi_receive AS B ON A.id_tre=B.id_tre WHERE A.id_pro IN (:kode) AND MONTH(B.tgl_tre) = MONTH(CURRENT_DATE()) AND YEAR(B.tgl_tre) = YEAR(CURRENT_DATE()) ");
			$rin->bindParam(':kode', $kode, PDO::PARAM_STR);
			$rin->execute();
			$conn	= $this->close();
			$vin	= $rin->fetch(PDO::FETCH_ASSOC);
			return $vin['total'];
		}

     
		function stokoutt($kode) {
			$conn	= $this->open();
			$read	= $conn->prepare("SELECT IFNULL(SUM(A.jumlah_tfd),0) AS total FROM transaksi_fakturdetail AS A INNER JOIN transaksi_faktur AS B ON A.id_tfk=B.id_tfk WHERE  A.id_pro IN (:kode) AND MONTH(B.tgl_tfk) = MONTH(CURRENT_DATE()) AND YEAR(B.tgl_tfk) = YEAR(CURRENT_DATE()) ");
			$read->bindParam(':kode', $kode, PDO::PARAM_STR);
			$read->execute();
			$conn	= $this->close();
			$view	= $read->fetch(PDO::FETCH_ASSOC);
			return $view['total'];
		}
		function test($kode) {
			$conn	= $this->open();
			$read	= $conn->prepare("SELECT IFNULL(SUM(A.jumlah_tfd),0) AS total FROM transaksi_faktur_penggantian_barang_detail AS A INNER JOIN transaksi_faktur_penggantian_barang AS B ON A.id_tfk=B.id_tfk WHERE  A.id_pro IN (:kode) AND MONTH(B.tgl_tfk) = MONTH(CURRENT_DATE()) AND YEAR(B.tgl_tfk) = YEAR(CURRENT_DATE()) ");
			$read->bindParam(':kode', $kode, PDO::PARAM_STR);
			$read->execute();
			$conn	= $this->close();
			$view	= $read->fetch(PDO::FETCH_ASSOC);
			return $view['total'];
		}
		
		function stokoutrr($kode) {
			$conn	= $this->open();
			$read	= $conn->prepare("SELECT IFNULL(SUM(A.jumlah_tfd),0) AS total FROM transaksi_fakturdetail_r AS A INNER JOIN transaksi_faktur_r AS B ON A.id_tfk=B.id_tfk WHERE  A.id_pro IN (:kode) AND MONTH(B.tgl_tfk) = MONTH(CURRENT_DATE()) AND YEAR(B.tgl_tfk) = YEAR(CURRENT_DATE())  ");
			$read->bindParam(':kode', $kode, PDO::PARAM_STR);
			$read->execute();
			$conn	= $this->close();
			$view	= $read->fetch(PDO::FETCH_ASSOC);
			return $view['total'];
		}
		
		
		function stokoutpim($kode) {
			$conn	= $this->open();
			$read	= $conn->prepare("SELECT IFNULL(SUM(A.jumlah_tfd),0) AS total FROM transaksi_fakturdetail_pim AS A INNER JOIN transaksi_faktur_pim AS B ON A.id_tfk=B.id_tfk WHERE  A.id_pro IN (:kode) AND MONTH(B.tgl_tfk) = MONTH(CURRENT_DATE()) AND YEAR(B.tgl_tfk) = YEAR(CURRENT_DATE())  ");
			$read->bindParam(':kode', $kode, PDO::PARAM_STR);
			$read->execute();
			$conn	= $this->close();
			$view	= $read->fetch(PDO::FETCH_ASSOC);
			return $view['total'];
		}
		
		

		function stokoutr($kode) {
			$conn	= $this->open();
			$read	= $conn->prepare("SELECT IFNULL(SUM(A.jumlah_ttd), 0) AS total FROM transaksi_transferstockdetail AS A INNER JOIN transaksi_transferstock AS B ON A.id_ttr=B.id_ttr WHERE  A.id_pro IN (:kode) AND MONTH(B.tgl_ttr) = MONTH(CURRENT_DATE()) AND YEAR(B.tgl_ttr) = YEAR(CURRENT_DATE()) AND B.tipe_ttr='OUT'  ");
			$read->bindParam(':kode', $kode, PDO::PARAM_STR);
			$read->execute();
			$conn	= $this->close();
			$view	= $read->fetch(PDO::FETCH_ASSOC);
			return $view['total'];
		}
		
		
		function stokintf($kode) {
			$conn	= $this->open();
			$read	= $conn->prepare("SELECT IFNULL(SUM(A.jumlah_ttd), 0) AS total FROM transaksi_transferstockdetail AS A INNER JOIN transaksi_transferstock AS B ON A.id_ttr=B.id_ttr WHERE  A.id_pro IN (:kode) AND MONTH(B.tgl_ttr) = MONTH(CURRENT_DATE()) AND YEAR(B.tgl_ttr) = YEAR(CURRENT_DATE()) AND B.tipe_ttr='IN' ");
			$read->bindParam(':kode', $kode, PDO::PARAM_STR);
			$read->execute();
			$conn	= $this->close();
			$view	= $read->fetch(PDO::FETCH_ASSOC);
			return $view['total'];
		}
		
		

		function romawi($angka) {
			$hsl = "";
			if ($angka < 1 || $angka > 5000) { 
				// Statement di atas buat nentuin angka ngga boleh dibawah 1 atau di atas 5000
				$hsl = "Batas Angka 1 s/d 5000";
			} else {
				while ($angka >= 1000) {
					// While itu termasuk kedalam statement perulangan
					// Jadi misal variable angka lebih dari sama dengan 1000
					// Kondisi ini akan di jalankan
					$hsl .= "M"; 
					// jadi pas di jalanin , kondisi ini akan menambahkan M ke dalam
					// Varible hsl
					$angka -= 1000;
					// Lalu setelah itu varible angka di kurangi 1000 ,
					// Kenapa di kurangi
					// Karena statment ini mengambil 1000 untuk di konversi menjadi M
				}
			}
		
		
			if ($angka >= 500) {
				// statement di atas akan bernilai true / benar
				// Jika var angka lebih dari sama dengan 500
				if ($angka > 500) {
					if ($angka >= 900) {
						$hsl .= "CM";
						$angka -= 900;
					} else {
						$hsl .= "D";
						$angka-=500;
					}
				}
			}
			while ($angka>=100) {
				if ($angka>=400) {
					$hsl .= "CD";
					$angka -= 400;
				} else {
					$angka -= 100;
				}
			}
			if ($angka>=50) {
				if ($angka>=90) {
					$hsl .= "XC";
					$angka -= 90;
				} else {
					$hsl .= "L";
					$angka-=50;
				}
			}
			while ($angka >= 10) {
				if ($angka >= 40) {
					$hsl .= "XL";
					$angka -= 40;
				} else {
					$hsl .= "X";
					$angka -= 10;
				}
			}
			if ($angka >= 5) {
				if ($angka == 9) {
					$hsl .= "IX";
					$angka-=9;
				} else {
					$hsl .= "V";
					$angka -= 5;
				}
			}
			while ($angka >= 1) {
				if ($angka == 4) {
					$hsl .= "IV"; 
					$angka -= 4;
				} else {
					$hsl .= "I";
					$angka -= 1;
				}
			}
			return ($hsl);
		}
		
		function basecode($kunci, $long, $kode, $tabel) {
			$conn	= $this->open();
			$select	= $conn->query("SELECT MAX($kode) AS kode FROM $tabel WHERE $kode LIKE '%$kunci%'")->fetch(PDO::FETCH_ASSOC);
			$panjang= $conn->query("SELECT CHARACTER_MAXIMUM_LENGTH AS total FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name='$tabel' AND COLUMN_NAME='$kode'")->fetch(PDO::FETCH_ASSOC);
			$conn	= $this->close();
			$jumlah	= strlen($kunci);
			//$sisa	= $panjang['total'] - $jumlah;
			$nourut = (int) str_replace($kunci, "", $select['kode']);
			$nourut++;
			$unik	= $kunci.sprintf("%0".$long."s", $nourut);
			return $unik;
		}
		
		function bcode($kunci, $kode, $tabel) {
			$conn	= $this->open();
			$select	= $conn->query("SELECT MAX($kode) AS kode FROM $tabel WHERE $kode LIKE '%$kunci%'")->fetch(PDO::FETCH_ASSOC);
			$panjang= $conn->query("SELECT CHARACTER_MAXIMUM_LENGTH AS total FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name='$tabel' AND COLUMN_NAME='$kode'")->fetch(PDO::FETCH_ASSOC);
			$conn	= $this->close();
			$jumlah	= strlen($kunci);
			$sisa	= $panjang['total'] - $jumlah;
			$nourut = (int) str_replace($kunci, "", $select['kode']);
			$nourut++;
			$unik	= $kunci.sprintf("%0".$sisa."s", $nourut);
			return $unik;
		}
		
    function transcode($kunci, $kode, $tabel) {
    // Pastikan nama tabel aman
    $allowedTables = ['transaksi_faktur', 'transaksi_faktur_konsinyasi', 'some_other_table'];
    if (!in_array($tabel, $allowedTables)) {
        throw new Exception("Invalid table name");
    }

    // Dapatkan bulan dalam format Romawi
    $bulanRomawi = $this->romawi(date('m'));
    $tahun = date('y');

    // Format bagian yang membedakan transaksi (bulan/tahun)
    $filterBulan = "%/$bulanRomawi/$tahun";

    // Buka koneksi database
    $conn = $this->open();

    // Query untuk mencari nomor urut tertinggi dengan bulan yang sama
    $query = "SELECT MAX(CAST(SUBSTRING_INDEX($kode, '.', 1) AS UNSIGNED)) AS kode 
              FROM $tabel 
              WHERE $kode LIKE :filterBulan";

    $stmt = $conn->prepare($query);
    $stmt->execute([':filterBulan' => $filterBulan]);
    $select = $stmt->fetch(PDO::FETCH_ASSOC);

    // Tutup koneksi database
    $conn = null;

    // Jika tidak ada data, mulai dari 0001
    $nourut = $select['kode'] ? (int) $select['kode'] : 0;
    $nourut++;

    // Format nomor urut agar selalu 4 digit
    $nomorBaru = sprintf("%04d", $nourut) . $kunci;

    return $nomorBaru;
}
function transcodepim($kunci, $kode, $tabel) {
	// Buka koneksi ke database
	$conn = $this->open();

	// Query untuk mendapatkan nilai numerik terbesar dari kode
	// Menggunakan SUBSTRING dan CAST untuk mendapatkan bagian nomor sebagai integer
	$query = "SELECT MAX(CAST(SUBSTRING($kode, 1, LENGTH($kode) - LENGTH('$kunci')) AS UNSIGNED)) AS kode 
			  FROM $tabel 
			  WHERE $kode LIKE '%$kunci'";
	$select = $conn->query($query)->fetch(PDO::FETCH_ASSOC);
	$conn = $this->close();

	// Jika tidak ada hasil, set nilai default
	if (!$select['kode']) {
		$select['kode'] = 0;
	}

	// Ambil nomor urut dan tambahkan 1
	$nourut = (int) $select['kode'];
	$nourut++;

	// Buat nomor urut dengan minimal 4 digit diikuti oleh kunci
	$unik = sprintf("%04d", $nourut) . $kunci;

	return $unik;
}
function transcodetfw($kunci, $kode, $tabel) {
    // Buka koneksi ke database
    $conn = $this->open();

    // Query untuk mendapatkan nilai numerik terbesar dari kode
    // Menggunakan SUBSTRING dan CAST untuk mendapatkan bagian nomor sebagai integer
    $query = "SELECT MAX(CAST(SUBSTRING($kode, 1, LENGTH($kode) - LENGTH('$kunci')) AS UNSIGNED)) AS kode 
              FROM $tabel 
              WHERE $kode LIKE '%$kunci'";
    $select = $conn->query($query)->fetch(PDO::FETCH_ASSOC);
    $conn = $this->close();

    // Jika tidak ada hasil, set nilai default
    if (!$select['kode']) {
        $select['kode'] = 0;
    }

    // Ambil nomor urut dan tambahkan 1
    $nourut = (int) $select['kode'];
    $nourut++;

    // Buat nomor urut dengan minimal 4 digit diikuti oleh kunci
    $unik = sprintf("%04d", $nourut) . $kunci;

    return $unik;
}


function transcoderetur($kunci, $kode, $tabel) {
    // Buka koneksi ke database
    $conn = $this->open();

    // Query untuk mendapatkan nilai numerik terbesar dari kode
    // Menggunakan SUBSTRING dan CAST untuk mendapatkan bagian nomor sebagai integer
    $query = "SELECT MAX(CAST(SUBSTRING($kode, 1, LENGTH($kode) - LENGTH('$kunci')) AS UNSIGNED)) AS kode 
              FROM $tabel 
              WHERE $kode LIKE '%$kunci'";
    $select = $conn->query($query)->fetch(PDO::FETCH_ASSOC);
    $conn = $this->close();

    // Jika tidak ada hasil, set nilai default
    if (!$select['kode']) {
        $select['kode'] = 0;
    }

    // Ambil nomor urut dan tambahkan 1
    $nourut = (int) $select['kode'];
    $nourut++;

    // Buat nomor urut dengan minimal 4 digit diikuti oleh kunci
    $unik = sprintf("%04d", $nourut) . $kunci;

    return $unik;
}


		function transcodetf($kunci, $kode, $tabel) {
			$prefix = "TRF/";
			$conn	= $this->open();
			$select	= $conn->query("SELECT MAX($kode) AS kode FROM $tabel WHERE $kode LIKE '%$kunci%'")->fetch(PDO::FETCH_ASSOC);
			$conn	= $this->close();
			$jumlah	= strlen($kunci);
			$nourut = str_replace($prefix, "", $select['kode']);
			$nourut = (int) str_replace($kunci, "", $select['kode']);
			$nourut++;
			$unik	= $prefix.sprintf("%03s", $nourut).$kunci;
			return $unik;
		}

		function transcodedn($kunci, $kode, $tabel) {
			$prefix = " DON/";
			$conn	= $this->open();
			$select	= $conn->query("SELECT MAX($kode) AS kode FROM $tabel WHERE $kode LIKE '%$kunci%'")->fetch(PDO::FETCH_ASSOC);
			$conn	= $this->close();
			$jumlah	= strlen($kunci);
			$nourut = str_replace($prefix, "", $select['kode']);
			$nourut = (int) str_replace($kunci, "", $select['kode']);
			$nourut++;
			$unik	= $prefix.sprintf("%03s", $nourut).$kunci;
			return $unik;
		}

		function transcodepm($kunci, $kode, $tabel) {
			$prefix = " PIN/";
			$conn	= $this->open();
			$select	= $conn->query("SELECT MAX($kode) AS kode FROM $tabel WHERE $kode LIKE '%$kunci%'")->fetch(PDO::FETCH_ASSOC);
			$conn	= $this->close();
			$jumlah	= strlen($kunci);
			$nourut = str_replace($prefix, "", $select['kode']);
			$nourut = (int) str_replace($kunci, "", $select['kode']);
			$nourut++;
			$unik	= $prefix.sprintf("%03s", $nourut).$kunci;
			return $unik;
		}

		function transcodert($kunci, $kode, $tabel) {
			$prefix = " RET/";
			$conn	= $this->open();
			$select	= $conn->query("SELECT MAX($kode) AS kode FROM $tabel WHERE $kode LIKE '%$kunci%'")->fetch(PDO::FETCH_ASSOC);
			$conn	= $this->close();
			$jumlah	= strlen($kunci);
			$nourut = str_replace($prefix, "", $select['kode']);
			$nourut = (int) str_replace($kunci, "", $select['kode']);
			$nourut++;
			$unik	= $prefix.sprintf("%03s", $nourut).$kunci;
			return $unik;
		}

		function transcoderl($kunci, $kode, $tabel) {
			$prefix = " LAIN/";
			$conn	= $this->open();
			$select	= $conn->query("SELECT MAX($kode) AS kode FROM $tabel WHERE $kode LIKE '%$kunci%'")->fetch(PDO::FETCH_ASSOC);
			$conn	= $this->close();
			$jumlah	= strlen($kunci);
			$nourut = str_replace($prefix, "", $select['kode']);
			$nourut = (int) str_replace($kunci, "", $select['kode']);
			$nourut++;
			$unik	= $prefix.sprintf("%03s", $nourut).$kunci;
			return $unik;
		}

	function terbilang($kata) {
    $data = new Data; // Pastikan kelas Data sudah ada dan berfungsi dengan baik.
    $ambil = array("", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas");
            
            if ($kata < 12) {
                return $ambil[$kata];
            } elseif ($kata < 20) {
                return $data->terbilang($kata - 10) . " Belas";
            } elseif ($kata < 100) {
                return $data->terbilang(floor($kata / 10)) . " Puluh " . $data->terbilang($kata % 10);
            } elseif ($kata < 200) {
                return "Seratus " . $data->terbilang($kata - 100);
            } elseif ($kata < 1000) {
                return $data->terbilang(floor($kata / 100)) . " Ratus " . $data->terbilang($kata % 100);
            } elseif ($kata < 2000) {
                return "Seribu " . $data->terbilang($kata - 1000);
            } elseif ($kata < 1000000) {
                return $data->terbilang(floor($kata / 1000)) . " Ribu " . $data->terbilang($kata % 1000);
            } elseif ($kata < 1000000000) {
                return $data->terbilang(floor($kata / 1000000)) . " Juta " . $data->terbilang($kata % 1000000);
            } elseif ($kata < 1000000000000) {
                return $data->terbilang(floor($kata / 1000000000)) . " Miliar " . $data->terbilang($kata % 1000000000);
            }
        }

		
		function cekcari($kata, $kunci, $timpa){
			$hasil	= empty($kata) ? '' : str_replace("$kunci", "$timpa", $kata);
			return $hasil;
		}

		// add by suryo
		function self_apl() {
			$conn	= $this->open();
			$self_apl	= 1;
			$read	= $conn->prepare("SELECT * FROM aplikasi WHERE self_apl=:self_apl AND active_apl = 1 LIMIT 1");
			$read->bindParam(':self_apl', $self_apl, PDO::PARAM_STR);
			$read->execute();
			$conn	= $this->close();
			$data	= $read->fetch(PDO::FETCH_ASSOC);
			return $data;
		}

		function get_apl($id_apl = null) {
			$id_apl = is_null($id_apl) ? "id_apl != ''" : "id_apl = '$id_apl'";
			$conn	= $this->open();
			$read	= $conn->prepare("SELECT * FROM aplikasi WHERE $id_apl AND active_apl = 1");
			$read->execute();
			$conn	= $this->close();
			$data	= $read->fetchAll(PDO::FETCH_ASSOC);
			return $data;
		}
		
		
		
		function belumbalik($kode) {
				$conn	= $this->open();
				$qMaster = "SELECT
									COUNT(*) AS jumlah
								FROM
									transaksi_faktur
								WHERE
							     	tgl_tfk IN (:kode) AND
								    status_dokumen='belum balik'
								GROUP BY
								    tgl_tfk DESC";
				$master	= $conn->prepare($qMaster);
				$master->bindParam(':kode', $kode, PDO::PARAM_STR);
				$master->execute();
				$view	= $master->fetch(PDO::FETCH_ASSOC);
				$conn	= $this->close();
		    	return $view['jumlah'];
				
		}

		function sudahbalik($kode) {
			$conn	= $this->open();
			$qMaster = "SELECT
								COUNT(*) AS jumlah
							FROM
								transaksi_faktur
							WHERE
								tgl_tfk = :kode AND
								status_dokumen='sudah balik'
							GROUP BY
								tgl_tfk DESC";
			$master	= $conn->prepare($qMaster);
			$master->bindParam(':kode', $kode, PDO::PARAM_STR);
			$master->execute();
			$view	= $master->fetch(PDO::FETCH_ASSOC);
			$conn	= $this->close();
			return $view['jumlah'];
			
	}
			function belumfiling($kode) {
				$conn	= $this->open();
				$qMaster = "SELECT
									COUNT(*) AS jumlah
								FROM
									transaksi_faktur
								WHERE
							     tgl_tfk = :kode AND
								    status_failing='belum failing'
								GROUP BY
								        tgl_tfk DESC";
				$master	= $conn->prepare($qMaster);
				$master->bindParam(':kode', $kode, PDO::PARAM_STR);
				$master->execute();
				$view	= $master->fetch(PDO::FETCH_ASSOC);
				$conn	= $this->close();
		    	return $view['jumlah'];
				
		}

		function sudahfiling($kode) {
			$conn	= $this->open();
			$qMaster = "SELECT
								COUNT(*) AS jumlah
							FROM
								transaksi_faktur
							WHERE
								tgl_tfk = :kode AND
								status_failing='sudah failing'
							GROUP BY
									tgl_tfk DESC";
			$master	= $conn->prepare($qMaster);
			$master->bindParam(':kode', $kode, PDO::PARAM_STR);
			$master->execute();
			$view	= $master->fetch(PDO::FETCH_ASSOC);
			$conn	= $this->close();
			return $view['jumlah'];
			
	}
		
		function beluminputpajak($kode) {
				$conn	= $this->open();
				$qMaster = "SELECT
									COUNT(*) AS jumlah
								FROM
									transaksi_faktur
								WHERE
							     tgl_tfk = :kode AND
								    status_f_pajak='belum terbit'
								GROUP BY
								        tgl_tfk DESC";
				$master	= $conn->prepare($qMaster);
				$master->bindParam(':kode', $kode, PDO::PARAM_STR);
				$master->execute();
				$conn	= $this->close();
				$view	= $master->fetch(PDO::FETCH_ASSOC);
		    	return $view['jumlah'];
				
		}

		function sudahinputpajak($kode) {
			$conn	= $this->open();
			$qMaster = "SELECT
								COUNT(*) AS jumlah
							FROM
								transaksi_faktur
							WHERE
							    tgl_tfk = :kode AND
								status_f_pajak='sudah terbit'
							GROUP BY
									tgl_tfk DESC";
			$master	= $conn->prepare($qMaster);
			$master->bindParam(':kode', $kode, PDO::PARAM_STR);
			$master->execute();
			$view	= $master->fetch(PDO::FETCH_ASSOC);
			$conn	= $this->close();
			return $view['jumlah'];
			
	}

		function belumpemberkasan($kode) {
			$conn	= $this->open();
			$qMaster = "SELECT
								COUNT(*) AS jumlah
							FROM
								transaksi_faktur
							WHERE
								tgl_tfk = :kode AND
								status_dokumentasi='belum siap'
							GROUP BY
									tgl_tfk DESC";
			$master	= $conn->prepare($qMaster);
			$master->bindParam(':kode', $kode, PDO::PARAM_STR);
			$master->execute();
			$view	= $master->fetch(PDO::FETCH_ASSOC);
			$conn	= $this->close();
			return $view['jumlah'];
			
	}

	function siappemberkasan($kode) {
		$conn	= $this->open();
		$qMaster = "SELECT
							COUNT(*) AS jumlah
						FROM
							transaksi_faktur
						WHERE
							tgl_tfk = :kode AND
							status_dokumentasi='sudah siap'
						GROUP BY
								tgl_tfk DESC";
		$master	= $conn->prepare($qMaster);
		$master->bindParam(':kode', $kode, PDO::PARAM_STR);
		$master->execute();
		$view	= $master->fetch(PDO::FETCH_ASSOC);
		$conn	= $this->close();
		return $view['jumlah'];
		
	}

	function selesaitf($kode) {
		$conn	= $this->open();
		$qMaster = "SELECT
							COUNT(*) AS jumlah
						FROM
							transaksi_faktur
						WHERE
							tgl_tfk = :kode AND
							status_tfkkf='Sudah Dikirim'
						GROUP BY
								tgl_tfk DESC";
		$master	= $conn->prepare($qMaster);
		$master->bindParam(':kode', $kode, PDO::PARAM_STR);
		$master->execute();
		$view	= $master->fetch(PDO::FETCH_ASSOC);
		$conn	= $this->close();
		return $view['jumlah'];
		
	}

	function belumtf($kode) {
		$conn	= $this->open();
		$qMaster = "SELECT
							COUNT(*) AS jumlah
						FROM
							transaksi_faktur
						WHERE
							tgl_tfk = :kode AND
							status_tfkkf='Belum Dikirim'
						GROUP BY
								tgl_tfk DESC";
		$master	= $conn->prepare($qMaster);
		$master->bindParam(':kode', $kode, PDO::PARAM_STR);
		$master->execute();
		$conn	= $this->close();
		$view	= $master->fetch(PDO::FETCH_ASSOC);
		return $view['jumlah'];
		
	}

	function uploadpajak($kode) {
		$conn	= $this->open();
		$qMaster = "SELECT
							COUNT(*) AS jumlah
						FROM
							transaksi_faktur
						WHERE
							tgl_tfk = :kode AND
							upload_f_pajak='belum'
						GROUP BY
								tgl_tfk DESC";
		$master	= $conn->prepare($qMaster);
		$master->bindParam(':kode', $kode, PDO::PARAM_STR);
		$master->execute();
		$view	= $master->fetch(PDO::FETCH_ASSOC);
		$conn	= $this->close();
		return $view['jumlah'];
		
	}

	function sudahuploadpajak($kode) {
		$conn	= $this->open();
		$qMaster = "SELECT
							COUNT(*) AS jumlah
						FROM
							transaksi_faktur
						WHERE
							tgl_tfk = :kode AND
							upload_f_pajak='sudah'
						GROUP BY
								tgl_tfk DESC";
		$master	= $conn->prepare($qMaster);
		$master->bindParam(':kode', $kode, PDO::PARAM_STR);
		$master->execute();
		$view	= $master->fetch(PDO::FETCH_ASSOC);
		$conn	= $this->close();
		return $view['jumlah'];
		
	}

	function sudahpembayaran($kode) {
		$conn	= $this->open();
		$qMaster = "SELECT
							COUNT(*) AS jumlah
						FROM
							transaksi_faktur
						WHERE
							 tgl_tfk = :kode AND
							 status_tfk='lunas'
						GROUP BY
								tgl_tfk DESC";
		$master	= $conn->prepare($qMaster);
		$master->bindParam(':kode', $kode, PDO::PARAM_STR);
		$master->execute();
		$view	= $master->fetch(PDO::FETCH_ASSOC);
		$conn	= $this->close();
		return $view['jumlah'];
		
	}

	function belumpembayaran($kode) {
		$conn	= $this->open();
		$qMaster = "SELECT
							COUNT(*) AS jumlah
						FROM
							transaksi_faktur
						WHERE
							 tgl_tfk = :kode AND
							 status_tfk='tagihan'
						GROUP BY
								tgl_tfk DESC";
		$master	= $conn->prepare($qMaster);
		$master->bindParam(':kode', $kode, PDO::PARAM_STR);
		$master->execute();
		$conn	= $this->close();
		$view	= $master->fetch(PDO::FETCH_ASSOC);
		return $view['jumlah'];
		
	}

	function bayarsebagian($kode) {
		$conn	= $this->open();
		$qMaster = "SELECT
							COUNT(*) AS jumlah
						FROM
							transaksi_faktur
						WHERE
							 tgl_tfk = :kode AND
							 status_tfk='bayar'
						GROUP BY
								tgl_tfk DESC";
		$master	= $conn->prepare($qMaster);
		$master->bindParam(':kode', $kode, PDO::PARAM_STR);
		$master->execute();
		$view	= $master->fetch(PDO::FETCH_ASSOC);
		$conn	= $this->close();
		return $view['jumlah'];
		
	}


	function selesaipengirimanbarang($kode) {
		$conn	= $this->open();
		$qMaster = "SELECT
							COUNT(*) AS jumlah
						FROM
							transaksi_faktur
						WHERE
							 tgl_tfk = :kode AND
							 status_tfkkb='Sudah Dikirim'
						GROUP BY
								tgl_tfk DESC";
		$master	= $conn->prepare($qMaster);
		$master->bindParam(':kode', $kode, PDO::PARAM_STR);
		$master->execute();
		$view	= $master->fetch(PDO::FETCH_ASSOC);
		$conn	= $this->close();
		return $view['jumlah'];
		
	}

	function belumpengirimanbarang($kode) {
		$conn	= $this->open();
		$qMaster = "SELECT
							COUNT(*) AS jumlah
						FROM
							transaksi_faktur
						WHERE
							 tgl_tfk = :kode AND
							 status_tfkkb='Belum Dikirim'
						GROUP BY
								tgl_tfk DESC";
		$master	= $conn->prepare($qMaster);
		$master->bindParam(':kode', $kode, PDO::PARAM_STR);
		$master->execute();
		$view	= $master->fetch(PDO::FETCH_ASSOC);
		$conn	= $this->close();
		return $view['jumlah'];
		
	}

	function totaloutlet($kode) {
		$conn	= $this->open();
		$qMaster = "SELECT
							COUNT(*) AS jumlah
						FROM
							transaksi_faktur_kirim_f
						WHERE
							 id_out IN (:kode) AND created_at
						GROUP BY
								created_at DESC";
		$master	= $conn->prepare($qMaster);
		$master->bindParam(':kode', $kode, PDO::PARAM_STR);
		$master->execute();
		$view	= $master->fetch(PDO::FETCH_ASSOC);
		$conn	= $this->close();
		return $view['jumlah'];
		
	}

	function totalfaktur($kode) {
		$conn	= $this->open();
		$qMaster = "SELECT
							COUNT(id_tfkkf) AS jumlah
						FROM
							transaksi_faktur_kirim_f
						WHERE
							 created_at IN (:kode) 
						GROUP BY
								created_at DESC";
		$master	= $conn->prepare($qMaster);
		$master->bindParam(':kode', $kode, PDO::PARAM_STR);
		$master->execute();
		$view	= $master->fetch(PDO::FETCH_ASSOC);
		$conn	= $this->close();
		return $view['jumlah'];
		
	}

	function selesaikirim($kode) {
		$conn	= $this->open();
		$qMaster = "SELECT
							COUNT(*) AS jumlah
						FROM
							transaksi_faktur_kirim_f
						WHERE
							 created_at IN (:kode) AND
							 status_tfkkf='sudah dikirim'
						GROUP BY
								created_at DESC";
		$master	= $conn->prepare($qMaster);
		$master->bindParam(':kode', $kode, PDO::PARAM_STR);
		$master->execute();
		$view	= $master->fetch(PDO::FETCH_ASSOC);
		$conn	= $this->close();
		return $view['jumlah'];
		
	}

	function belumdikirim($kode) {
		$conn	= $this->open();
		$qMaster = "SELECT
							COUNT(*) AS jumlah
						FROM
							transaksi_faktur_kirim_f
						WHERE
							 created_at IN (:kode) AND
							 status_tfkkf='belum dikirim'
						GROUP BY
								created_at DESC";
		$master	= $conn->prepare($qMaster);
		$master->bindParam(':kode', $kode, PDO::PARAM_STR);
		$master->execute();
		$view	= $master->fetch(PDO::FETCH_ASSOC);
		$conn	= $this->close();
		return $view['jumlah'];
		
	}
		
	
	function tgldd($tgl){
			// $tgl 		 ='00-00-00';
			$tanggalBaru = date('y-m-d', strtotime($tgl));
			return $tanggalBaru;
		}
		// end
		
	function januari($kode) {
				$conn	= $this->open();
				$qRead = "SELECT IFNULL(SUM(A.subtot_tfk), 0) AS total, B.profit,C.diskon_odi FROM transaksi_faktur AS A LEFT JOIN outlet AS B ON A.id_out=B.id_out LEFT JOIN outlet_diskon AS C ON B.id_out=C.id_out WHERE B.id_out IN (:kode) AND MONTH(A.tgl_tfk) = 01 AND YEAR(A.tgl_tfk) = YEAR(CURRENT_DATE())" ;
				$read	= $conn->prepare($qRead);
				$read->bindParam(':kode', $kode, PDO::PARAM_STR);
				$read->execute();
				$conn	= $this->close();
				$view	= $read->fetch(PDO::FETCH_ASSOC);
				$januari		= $view['total'];
				$a      		= $view['profit'];
				$b      		= $view['diskon_odi'];
				$float_value 	= floatval($b);
				$c				= ($a - $float_value);
				$jumlahjanuari	= ($c * $januari)/100;
				return $jumlahjanuari;

			}

			function februari($kode) {
				$conn	= $this->open();
				$qRead = "SELECT IFNULL(SUM(A.subtot_tfk), 0) AS total, B.profit,C.diskon_odi FROM transaksi_faktur AS A LEFT JOIN outlet AS B ON A.id_out=B.id_out LEFT JOIN outlet_diskon AS C ON B.id_out=C.id_out WHERE B.id_out IN (:kode) AND MONTH(A.tgl_tfk) = 02 AND YEAR(A.tgl_tfk) = YEAR(CURRENT_DATE())";
				$read	= $conn->prepare($qRead);
				$read->bindParam(':kode', $kode, PDO::PARAM_STR);
				$read->execute();
				$conn	= $this->close();
				$view	= $read->fetch(PDO::FETCH_ASSOC);
				$februari		= $view['total'];
				$a      		= $view['profit'];
				$b      		= $view['diskon_odi'];
				$float_value 	= floatval($b);
				$c				= ($a - $float_value);
				$jumlahfebruari	= ($c * $februari)/100;
				return $jumlahfebruari;
			}
			// $read	= $conn->prepare("SELECT IFNULL(SUM(A.jumlah_ttd), 0) AS total FROM transaksi_transferstockdetail AS A INNER JOIN transaksi_transferstock AS B ON A.id_ttr=B.id_ttr WHERE  A.id_pro IN (:kode) AND MONTH(B.tgl_ttr) = MONTH(CURRENT_DATE()) AND YEAR(B.tgl_ttr) = YEAR(CURRENT_DATE()) AND B.tipe_ttr='IN' ");
			function maret($kode) {
				$conn	= $this->open();
				$qRead  = "SELECT IFNULL(SUM(A.subtot_tfk), 0) AS total, B.profit,C.diskon_odi FROM transaksi_faktur AS A LEFT JOIN outlet AS B ON A.id_out=B.id_out LEFT JOIN outlet_diskon AS C ON B.id_out=C.id_out WHERE B.id_out IN (:kode) AND MONTH(A.tgl_tfk) = 03 AND YEAR(A.tgl_tfk) = YEAR(CURRENT_DATE())";
				$read	= $conn->prepare($qRead);
				$read->bindParam(':kode', $kode, PDO::PARAM_STR);
				$read->execute();
				$conn	= $this->close();
				$view	= $read->fetch(PDO::FETCH_ASSOC);
				$maret			= $view['total'];
				$a      		= $view['profit'];
				$b      		= $view['diskon_odi'];
				$float_value 	= floatval($b);
				$c				= ($a - $float_value);
				$jumlahmaret	= ($c * $maret)/100;
				return $jumlahmaret;
			}
			function april($kode) {
				$conn	= $this->open();
				$qRead  = "SELECT IFNULL(SUM(A.subtot_tfk), 0) AS total, B.profit,C.diskon_odi FROM transaksi_faktur AS A LEFT JOIN outlet AS B ON A.id_out=B.id_out LEFT JOIN outlet_diskon AS C ON B.id_out=C.id_out WHERE B.id_out IN (:kode) AND MONTH(A.tgl_tfk) = 04 AND YEAR(A.tgl_tfk) = YEAR(CURRENT_DATE())"; 
				$read	= $conn->prepare($qRead);
				$read->bindParam(':kode', $kode, PDO::PARAM_STR);
				$read->execute();
				$conn	= $this->close();
				$view	= $read->fetch(PDO::FETCH_ASSOC);
				// $view	= $read->fetch(PDO::FETCH_ASSOC);
				$april			= $view['total'];
				$a      		= $view['profit'];
				$b      		= $view['diskon_odi'];
				$float_value 	= floatval($b);
				$c				= ($a - $float_value);
				$jumlahapril		= ($c * $april)/100;
				return $jumlahapril;
			}
			function mei($kode) {
				$conn	= $this->open();
				$qRead  = "SELECT IFNULL(SUM(A.subtot_tfk), 0) AS total, B.profit,C.diskon_odi FROM transaksi_faktur AS A LEFT JOIN outlet AS B ON A.id_out=B.id_out LEFT JOIN outlet_diskon AS C ON B.id_out=C.id_out WHERE B.id_out IN (:kode) AND MONTH(A.tgl_tfk) = 05 AND YEAR(A.tgl_tfk) = YEAR(CURRENT_DATE())"; 
				$read	= $conn->prepare($qRead);
				$read->bindParam(':kode', $kode, PDO::PARAM_STR);
				$read->execute();
				$conn	= $this->close();
				$view	= $read->fetch(PDO::FETCH_ASSOC);
				$mei			= $view['total'];
				$a      		= $view['profit'];
				$b      		= $view['diskon_odi'];
				$float_value 	= floatval($b);
				$c				= ($a - $float_value);
				$jumlahmei		= ($c * $mei)/100;
				return $jumlahmei;	
			}
			function juni($kode) {
				$conn	= $this->open();
				$qRead  = "SELECT IFNULL(SUM(A.subtot_tfk), 0) AS total, B.profit,C.diskon_odi FROM transaksi_faktur AS A LEFT JOIN outlet AS B ON A.id_out=B.id_out LEFT JOIN outlet_diskon AS C ON B.id_out=C.id_out WHERE B.id_out IN (:kode) AND MONTH(A.tgl_tfk) = 06 AND YEAR(A.tgl_tfk) = YEAR(CURRENT_DATE())";
				$read	= $conn->prepare($qRead);
				$read->bindParam(':kode', $kode, PDO::PARAM_STR);
				$read->execute();
				$conn	= $this->close();
				$view	= $read->fetch(PDO::FETCH_ASSOC);
				$juni			= $view['total'];
				$a      		= $view['profit'];
				$b      		= $view['diskon_odi'];
				$float_value 	= floatval($b);
				$c				= ($a - $float_value);
				$jumlahjuni		= ($c * $juni)/100;
				return $jumlahjuni;		
			
			}
			function juli($kode) {
				$conn	= $this->open();
				$qRead  = "SELECT IFNULL(SUM(A.subtot_tfk), 0) AS total, B.profit,C.diskon_odi FROM transaksi_faktur AS A LEFT JOIN outlet AS B ON A.id_out=B.id_out LEFT JOIN outlet_diskon AS C ON B.id_out=C.id_out WHERE B.id_out IN (:kode) AND MONTH(A.tgl_tfk) = 07 AND YEAR(A.tgl_tfk) = YEAR(CURRENT_DATE())";
				$read	= $conn->prepare($qRead);
				$read->bindParam(':kode', $kode, PDO::PARAM_STR);
				$read->execute();
				$conn	= $this->close();
				$view	= $read->fetch(PDO::FETCH_ASSOC);
				$juli			= $view['total'];
				$a      		= $view['profit'];
				$b      		= $view['diskon_odi'];
				$float_value 	= floatval($b);
				$c				= ($a - $float_value);
				$jumlahjuli		= ($c * $juli)/100;
				return $jumlahjuli;		
			
			}
			function agustus($kode) {
				$conn	= $this->open();
				$qRead  = "SELECT IFNULL(SUM(A.subtot_tfk), 0) AS total, B.profit,C.diskon_odi FROM transaksi_faktur AS A LEFT JOIN outlet AS B ON A.id_out=B.id_out LEFT JOIN outlet_diskon AS C ON B.id_out=C.id_out WHERE B.id_out IN (:kode) AND MONTH(A.tgl_tfk) = 08 AND YEAR(A.tgl_tfk) = YEAR(CURRENT_DATE())";
				$read	= $conn->prepare($qRead);
				$read->bindParam(':kode', $kode, PDO::PARAM_STR);
				$read->execute();
				$conn	= $this->close();
				$view	= $read->fetch(PDO::FETCH_ASSOC);
				$agustus		= $view['total'];
				$a      		= $view['profit'];
				$b      		= $view['diskon_odi'];
				$float_value 	= floatval($b);
				$c				= ($a - $float_value);
				$jumlahagustus		= ($c * $agustus)/100;
				return $jumlahagustus;		
			}
			function september($kode) {
				$conn	= $this->open();
				$read	= $conn->prepare("SELECT IFNULL(SUM(A.subtot_tfk), 0) AS total, B.profit,C.diskon_odi FROM transaksi_faktur AS A LEFT JOIN outlet AS B ON A.id_out=B.id_out LEFT JOIN outlet_diskon AS C ON B.id_out=C.id_out WHERE B.id_out IN (:kode) AND MONTH(A.tgl_tfk) = 09 AND YEAR(A.tgl_tfk) = YEAR(CURRENT_DATE())");
				$read->bindParam(':kode', $kode, PDO::PARAM_STR);
				$read->execute();
				$conn	= $this->close();
				$view	= $read->fetch(PDO::FETCH_ASSOC);
				$september		= $view['total'];
				$a      		= $view['profit'];
				$b      		= $view['diskon_odi'];
				$float_value 	= floatval($b);
				$c				= ($a - $float_value);
				$jumlahseptember		= ($c * $september)/100;
				return $jumlahseptember;	
			}
			function oktober($kode) {
				$conn	= $this->open();
				$read	= $conn->prepare("SELECT IFNULL(SUM(A.subtot_tfk), 0) AS total, B.profit,C.diskon_odi FROM transaksi_faktur AS A LEFT JOIN outlet AS B ON A.id_out=B.id_out LEFT JOIN outlet_diskon AS C ON B.id_out=C.id_out WHERE B.id_out IN (:kode) AND MONTH(A.tgl_tfk) = 10 AND YEAR(A.tgl_tfk) = YEAR(CURRENT_DATE())");
				$read->bindParam(':kode', $kode, PDO::PARAM_STR);
				$read->execute();
				$conn	= $this->close();
				$view	= $read->fetch(PDO::FETCH_ASSOC);
				$oktober		= $view['total'];
				$a      		= $view['profit'];
				$b      		= $view['diskon_odi'];
				$float_value 	= floatval($b);
				$c				= ($a - $float_value);
				$jumlahoktober		= ($c * $oktober)/100;
				return $jumlahoktober;	
			}
			function november($kode) {
				$conn	= $this->open();
				$read	= $conn->prepare("SELECT IFNULL(SUM(A.subtot_tfk), 0) AS total, B.profit,C.diskon_odi FROM transaksi_faktur AS A LEFT JOIN outlet AS B ON A.id_out=B.id_out LEFT JOIN outlet_diskon AS C ON B.id_out=C.id_out WHERE B.id_out IN (:kode) AND MONTH(A.tgl_tfk) = 11 AND YEAR(A.tgl_tfk) = YEAR(CURRENT_DATE())");
				$read->bindParam(':kode', $kode, PDO::PARAM_STR);
				$read->execute();
				$conn	= $this->close();
				$view	= $read->fetch(PDO::FETCH_ASSOC);
				$november		= $view['total'];
				$a      		= $view['profit'];
				$b      		= $view['diskon_odi'];
				$float_value 	= floatval($b);
				$c				= ($a - $float_value);
				$jumlahnovember		= ($c * $november)/100;
				return $jumlahnovember;	
			}
			function desember($kode) {
				$conn	= $this->open();
				$read	= $conn->prepare("SELECT IFNULL(SUM(A.subtot_tfk), 0) AS total, B.profit,C.diskon_odi FROM transaksi_faktur AS A LEFT JOIN outlet AS B ON A.id_out=B.id_out LEFT JOIN outlet_diskon AS C ON B.id_out=C.id_out WHERE B.id_out IN (:kode) AND MONTH(A.tgl_tfk) = 12 AND YEAR(A.tgl_tfk) = YEAR(CURRENT_DATE())");
				$read->bindParam(':kode', $kode, PDO::PARAM_STR);
				$read->execute();
				$conn	= $this->close();
				$view	= $read->fetch(PDO::FETCH_ASSOC);
				$desember		= $view['total'];
				$a      		= $view['profit'];
				$b      		= $view['diskon_odi'];
				$float_value 	= floatval($b);
				$c				= ($a - $float_value);
				$jumlahdesember		= ($c * $desember)/100;
				return $jumlahdesember;	
			}
			function penggantianbarang($kunci, $kode, $tabel) {
    			$prefix = "";
    			$conn	= $this->open();
    			$select	= $conn->query("SELECT MAX($kode) AS kode FROM $tabel WHERE $kode LIKE '%$kunci%'")->fetch(PDO::FETCH_ASSOC);
    			$conn	= $this->close();
    			$jumlah	= strlen($kunci);
    			$nourut = str_replace($prefix, "", $select['kode']);
    			$nourut = (int) str_replace($kunci, "", $select['kode']);
    			$nourut++;
    			$unik	= $prefix.sprintf("%03s", $nourut).$kunci;
    			return $unik;
		}
		
	function stokawalltahun($kode) {
            $conn = $this->open();
            $rin = $conn->prepare("
                SELECT IFNULL(SUM(qty_so), 0) AS total 
                FROM so 
                WHERE id_pro = :kode 
                AND YEAR(created_at) = 2024
                AND MONTH(created_at) = 12
            ");
            $rin->bindParam(':kode', $kode, PDO::PARAM_STR);
            $rin->execute();
            $conn = $this->close();
            $vin = $rin->fetch(PDO::FETCH_ASSOC);
            return $vin['total'];
        }
  	function stokitahun($kode) {
			$conn	= $this->open();
			$rin	= $conn->prepare("SELECT IFNULL(SUM(A.jumlah_trd), 0) AS total FROM transaksi_receivedetail AS A INNER JOIN transaksi_receive AS B ON A.id_tre=B.id_tre WHERE A.id_pro IN (:kode) AND YEAR(B.tgl_tre) = 2025");
			$rin->bindParam(':kode', $kode, PDO::PARAM_STR);
			$rin->execute();
			$conn	= $this->close();
			$vin	= $rin->fetch(PDO::FETCH_ASSOC);
			return $vin['total'];
		}
		
		function stokintftahunan($kode) {
			$conn	= $this->open();
			$read	= $conn->prepare("SELECT IFNULL(SUM(A.jumlah_ttd), 0) AS total FROM transaksi_transferstockdetail AS A INNER JOIN transaksi_transferstock AS B ON A.id_ttr=B.id_ttr WHERE A.id_pro IN (:kode) AND YEAR(B.tgl_ttr) = 2025 AND B.tipe_ttr='IN'");
			$read->bindParam(':kode', $kode, PDO::PARAM_STR);
			$read->execute();
			$conn	= $this->close();
			$view	= $read->fetch(PDO::FETCH_ASSOC);
			return $view['total'];
		}
		
	function stokouttahunan($kode) {
			$conn	= $this->open();
			$read	= $conn->prepare("SELECT IFNULL(SUM(A.jumlah_tfd),0) AS total FROM transaksi_fakturdetail AS A INNER JOIN transaksi_faktur AS B ON A.id_tfk=B.id_tfk WHERE A.id_pro IN (:kode) AND YEAR(B.tgl_tfk) = 2025");
			$read->bindParam(':kode', $kode, PDO::PARAM_STR);
			$read->execute();
			$view	= $read->fetch(PDO::FETCH_ASSOC);
			
			$read2	= $conn->prepare("SELECT IFNULL(SUM(A.jumlah_tfd),0) AS total FROM transaksi_fakturdetail_pim AS A INNER JOIN transaksi_faktur_pim AS B ON A.id_tfk=B.id_tfk WHERE A.id_pro IN (:kode) AND YEAR(B.tgl_tfk) = 2025");
			$read2->bindParam(':kode', $kode, PDO::PARAM_STR);
			$read2->execute();
			$view2	= $read2->fetch(PDO::FETCH_ASSOC);
			
			$conn	= $this->close();
			$total = $view['total'] + $view2['total'];
			return $total;
		}
		
		function testtahunan($kode) {
			$conn	= $this->open();
			$read	= $conn->prepare("SELECT IFNULL(SUM(A.jumlah_tfd),0) AS total FROM transaksi_faktur_penggantian_barang_detail AS A INNER JOIN transaksi_faktur_penggantian_barang AS B ON A.id_tfk=B.id_tfk WHERE A.id_pro IN (:kode) AND YEAR(B.tgl_tfk) = 2025");
			$read->bindParam(':kode', $kode, PDO::PARAM_STR);
			$read->execute();
			$conn	= $this->close();
			$view	= $read->fetch(PDO::FETCH_ASSOC);
			return $view['total'];
		}
		
		function stokoutrtahunan($kode) {
			$conn	= $this->open();
			$read	= $conn->prepare("SELECT IFNULL(SUM(A.jumlah_ttd), 0) AS total FROM transaksi_transferstockdetail AS A INNER JOIN transaksi_transferstock AS B ON A.id_ttr=B.id_ttr WHERE A.id_pro IN (:kode) AND YEAR(B.tgl_ttr) = 2025 AND B.tipe_ttr='OUT'");
			$read->bindParam(':kode', $kode, PDO::PARAM_STR);
			$read->execute();
			$conn	= $this->close();
			$view	= $read->fetch(PDO::FETCH_ASSOC);
			return $view['total'];
		}
		
		function stokoutrrtahunan($kode) {
			$conn	= $this->open();
			$read	= $conn->prepare("SELECT IFNULL(SUM(A.jumlah_tfd),0) AS total FROM transaksi_fakturdetail_r AS A INNER JOIN transaksi_faktur_r AS B ON A.id_tfk=B.id_tfk WHERE A.id_pro IN (:kode) AND YEAR(B.tgl_tfk) = 2025");
			$read->bindParam(':kode', $kode, PDO::PARAM_STR);
			$read->execute();
			$conn	= $this->close();
			$view	= $read->fetch(PDO::FETCH_ASSOC);
			return $view['total'];
		}
		
    function transcodetfretur($prefix, $cabang, $kode, $tabel)
    	{
    		$base = new DB;
    		$conn = $base->open();
    
    		// Format components
    		$bulan = $this->romawi(date('m'));  // Month in Roman numerals
    		$tahun = date('Y');                // Full year (4 digits)
    		
    		// Construct the pattern to search for existing codes
    		$pattern = "$prefix/%/RET/$cabang/$bulan/$tahun";
    		
    		// Get the highest sequence number
    		$query = "SELECT MAX(SUBSTRING_INDEX(SUBSTRING_INDEX($kode, '/', 2), '/', -1)) AS last_number 
              FROM $tabel 
              WHERE $kode LIKE :pattern";
              
    		$stmt = $conn->prepare($query);
    		$stmt->bindParam(':pattern', $pattern, PDO::PARAM_STR);
    		$stmt->execute();
    		$result = $stmt->fetch(PDO::FETCH_ASSOC);
    		
    		// Generate the new sequence number
    		$nextNumber = ($result['last_number']) ? intval($result['last_number']) + 1 : 1;
    		$formattedNumber = sprintf("%03d", $nextNumber);
    		
    		// Create the complete code with the desired format
    		$kode = "$prefix/$formattedNumber/RET/$cabang/$bulan/$tahun";
    
    		$conn = $base->close();
    		return $kode;
    	}
    function transcodepim2($kunci, $kode, $tabel) {
	// Buka koneksi ke database
	$conn = $this->open();

	// Query untuk mendapatkan nilai numerik terbesar dari kode
	// Menggunakan SUBSTRING dan CAST untuk mendapatkan bagian nomor sebagai integer
	$query = "SELECT MAX(CAST(SUBSTRING($kode, 1, LENGTH($kode) - LENGTH('$kunci')) AS UNSIGNED)) AS kode 
			  FROM $tabel 
			  WHERE $kode LIKE '%$kunci'";
	$select = $conn->query($query)->fetch(PDO::FETCH_ASSOC);
	$conn = $this->close();

	// Jika tidak ada hasil, set nilai default
	if (!$select['kode']) {
		$select['kode'] = 0;
	}

	// Ambil nomor urut dan tambahkan 1
	$nourut = (int) $select['kode'];
	$nourut++;

	// Buat nomor urut dengan minimal 4 digit diikuti oleh kunci
	$unik = sprintf("%04d", $nourut) . $kunci;

	return $unik;
}
    	
function jsonResponse($success, $message, $data = null, $errors = [], $code = 200)
	{
		$response = [
			'success' => $success,
			'message' => $message
		];

		// Add data if exists
		if ($data !== null) {
			$response['data'] = $data;
		}

		// Add errors if any
		if (!empty($errors)) {
			$response['errors'] = $errors;
		}

		// Try to set headers only if they haven't been sent yet
		if (!headers_sent()) {
			header('Content-Type: application/json');
			header('Cache-Control: no-cache, must-revalidate');
		}

		return json_encode($response);
	}
	
	   public function get_all_months_data2($id_out) {
            $base = new DB;
            $conn = $base->open();
            $year = date('Y');
            $data = array(
                'januari' => 0, 'februari' => 0, 'maret' => 0, 'april' => 0,
                'mei' => 0, 'juni' => 0, 'juli' => 0, 'agustus' => 0,
                'september' => 0, 'oktober' => 0, 'november' => 0, 'desember' => 0
            );
        
            // Get outlet profit and discount data
            $outlet_query = "SELECT A.profit, B.diskon_odi 
                             FROM outlet AS A 
                             INNER JOIN outlet_diskon AS B ON A.id_out=B.id_out 
                             WHERE A.id_out=:id_out";
            $outlet_stmt = $conn->prepare($outlet_query);
            $outlet_stmt->bindParam(':id_out', $id_out, PDO::PARAM_STR);
            $outlet_stmt->execute();
            $outlet_data = $outlet_stmt->fetch(PDO::FETCH_ASSOC);
        
            $profit = floatval($outlet_data['profit'] ?? 0);
            $diskon = floatval($outlet_data['diskon_odi'] ?? 0);
            $margin_pct = $diskon - $profit;
        
            // Gabungkan keempat tabel transaksi
            $query = "
                SELECT 
                    SUM(CASE WHEN MONTH(tgl_tfk)=1 AND YEAR(tgl_tfk)=$year THEN subtot_tfk ELSE 0 END) as jan,
                    SUM(CASE WHEN MONTH(tgl_tfk)=2 AND YEAR(tgl_tfk)=$year THEN subtot_tfk ELSE 0 END) as feb,
                    SUM(CASE WHEN MONTH(tgl_tfk)=3 AND YEAR(tgl_tfk)=$year THEN subtot_tfk ELSE 0 END) as mar,
                    SUM(CASE WHEN MONTH(tgl_tfk)=4 AND YEAR(tgl_tfk)=$year THEN subtot_tfk ELSE 0 END) as apr,
                    SUM(CASE WHEN MONTH(tgl_tfk)=5 AND YEAR(tgl_tfk)=$year THEN subtot_tfk ELSE 0 END) as mei,
                    SUM(CASE WHEN MONTH(tgl_tfk)=6 AND YEAR(tgl_tfk)=$year THEN subtot_tfk ELSE 0 END) as jun,
                    SUM(CASE WHEN MONTH(tgl_tfk)=7 AND YEAR(tgl_tfk)=$year THEN subtot_tfk ELSE 0 END) as jul,
                    SUM(CASE WHEN MONTH(tgl_tfk)=8 AND YEAR(tgl_tfk)=$year THEN subtot_tfk ELSE 0 END) as agt,
                    SUM(CASE WHEN MONTH(tgl_tfk)=9 AND YEAR(tgl_tfk)=$year THEN subtot_tfk ELSE 0 END) as sep,
                    SUM(CASE WHEN MONTH(tgl_tfk)=10 AND YEAR(tgl_tfk)=$year THEN subtot_tfk ELSE 0 END) as okt,
                    SUM(CASE WHEN MONTH(tgl_tfk)=11 AND YEAR(tgl_tfk)=$year THEN subtot_tfk ELSE 0 END) as nov,
                    SUM(CASE WHEN MONTH(tgl_tfk)=12 AND YEAR(tgl_tfk)=$year THEN subtot_tfk ELSE 0 END) as des
                FROM (
                    SELECT id_out, tgl_tfk, subtot_tfk FROM transaksi_faktur WHERE id_out=:id_out
                    UNION ALL
                    SELECT id_out, tgl_tfk, subtot_tfk FROM transaksi_faktur_pim WHERE id_out=:id_out
                    UNION ALL
                    SELECT id_out, tgl_tfk, subtot_tfk FROM transaksi_faktur_c WHERE id_out=:id_out
                    UNION ALL
                    SELECT id_out, tgl_tfk, subtot_tfk FROM transaksi_faktur_np_medan WHERE id_out=:id_out
                ) AS all_faktur
            ";
        
            $read = $conn->prepare($query);
            $read->bindParam(':id_out', $id_out, PDO::PARAM_STR);
            $read->execute();
            $res = $read->fetch(PDO::FETCH_ASSOC);
        
            if($res) {
                $data['januari'] = ($res['jan'] ?? 0) * ($margin_pct / 100);
                $data['februari'] = ($res['feb'] ?? 0) * ($margin_pct / 100);
                $data['maret'] = ($res['mar'] ?? 0) * ($margin_pct / 100);
                $data['april'] = ($res['apr'] ?? 0) * ($margin_pct / 100);
                $data['mei'] = ($res['mei'] ?? 0) * ($margin_pct / 100);
                $data['juni'] = ($res['jun'] ?? 0) * ($margin_pct / 100);
                $data['juli'] = ($res['jul'] ?? 0) * ($margin_pct / 100);
                $data['agustus'] = ($res['agt'] ?? 0) * ($margin_pct / 100);
                $data['september'] = ($res['sep'] ?? 0) * ($margin_pct / 100);
                $data['oktober'] = ($res['okt'] ?? 0) * ($margin_pct / 100);
                $data['november'] = ($res['nov'] ?? 0) * ($margin_pct / 100);
                $data['desember'] = ($res['des'] ?? 0) * ($margin_pct / 100);
            }
        
            $base->close();
            return $data;
        }
        		// Add this function to your Data class
        public function get_all_months_data($id_out) {
            $base = new DB;
            $conn = $base->open();
            $year = date('Y');
            $data = array(
                'januari' => 0, 'februari' => 0, 'maret' => 0, 'april' => 0,
                'mei' => 0, 'juni' => 0, 'juli' => 0, 'agustus' => 0,
                'september' => 0, 'oktober' => 0, 'november' => 0, 'desember' => 0
            );
        
            // Get outlet profit and discount data
            $outlet_query = "SELECT A.profit, B.diskon_odi 
                             FROM outlet AS A 
                             INNER JOIN outlet_diskon AS B ON A.id_out=B.id_out 
                             WHERE A.id_out=:id_out";
            $outlet_stmt = $conn->prepare($outlet_query);
            $outlet_stmt->bindParam(':id_out', $id_out, PDO::PARAM_STR);
            $outlet_stmt->execute();
            $outlet_data = $outlet_stmt->fetch(PDO::FETCH_ASSOC);
        
            $profit = floatval($outlet_data['profit'] ?? 0);
            $diskon = floatval($outlet_data['diskon_odi'] ?? 0);
            $margin_pct = $diskon - $profit;
        
            // Gabungkan keempat tabel transaksi
            $query = "
                SELECT 
                    SUM(CASE WHEN MONTH(tgl_tfk)=1 AND YEAR(tgl_tfk)=$year THEN subtot_tfk ELSE 0 END) as jan,
                    SUM(CASE WHEN MONTH(tgl_tfk)=2 AND YEAR(tgl_tfk)=$year THEN subtot_tfk ELSE 0 END) as feb,
                    SUM(CASE WHEN MONTH(tgl_tfk)=3 AND YEAR(tgl_tfk)=$year THEN subtot_tfk ELSE 0 END) as mar,
                    SUM(CASE WHEN MONTH(tgl_tfk)=4 AND YEAR(tgl_tfk)=$year THEN subtot_tfk ELSE 0 END) as apr,
                    SUM(CASE WHEN MONTH(tgl_tfk)=5 AND YEAR(tgl_tfk)=$year THEN subtot_tfk ELSE 0 END) as mei,
                    SUM(CASE WHEN MONTH(tgl_tfk)=6 AND YEAR(tgl_tfk)=$year THEN subtot_tfk ELSE 0 END) as jun,
                    SUM(CASE WHEN MONTH(tgl_tfk)=7 AND YEAR(tgl_tfk)=$year THEN subtot_tfk ELSE 0 END) as jul,
                    SUM(CASE WHEN MONTH(tgl_tfk)=8 AND YEAR(tgl_tfk)=$year THEN subtot_tfk ELSE 0 END) as agt,
                    SUM(CASE WHEN MONTH(tgl_tfk)=9 AND YEAR(tgl_tfk)=$year THEN subtot_tfk ELSE 0 END) as sep,
                    SUM(CASE WHEN MONTH(tgl_tfk)=10 AND YEAR(tgl_tfk)=$year THEN subtot_tfk ELSE 0 END) as okt,
                    SUM(CASE WHEN MONTH(tgl_tfk)=11 AND YEAR(tgl_tfk)=$year THEN subtot_tfk ELSE 0 END) as nov,
                    SUM(CASE WHEN MONTH(tgl_tfk)=12 AND YEAR(tgl_tfk)=$year THEN subtot_tfk ELSE 0 END) as des
                FROM (
                    SELECT id_out, tgl_tfk, subtot_tfk FROM transaksi_faktur WHERE id_out=:id_out
                    UNION ALL
                    SELECT id_out, tgl_tfk, subtot_tfk FROM transaksi_faktur_pim WHERE id_out=:id_out
                    UNION ALL
                    SELECT id_out, tgl_tfk, subtot_tfk FROM transaksi_faktur_c WHERE id_out=:id_out
                    UNION ALL
                    SELECT id_out, tgl_tfk, subtot_tfk FROM transaksi_faktur_np_medan WHERE id_out=:id_out
                ) AS all_faktur
            ";
        
            $read = $conn->prepare($query);
            $read->bindParam(':id_out', $id_out, PDO::PARAM_STR);
            $read->execute();
            $res = $read->fetch(PDO::FETCH_ASSOC);
        
            if($res) {
                $data['januari'] = ($res['jan'] ?? 0) * ($margin_pct / 100);
                $data['februari'] = ($res['feb'] ?? 0) * ($margin_pct / 100);
                $data['maret'] = ($res['mar'] ?? 0) * ($margin_pct / 100);
                $data['april'] = ($res['apr'] ?? 0) * ($margin_pct / 100);
                $data['mei'] = ($res['mei'] ?? 0) * ($margin_pct / 100);
                $data['juni'] = ($res['jun'] ?? 0) * ($margin_pct / 100);
                $data['juli'] = ($res['jul'] ?? 0) * ($margin_pct / 100);
                $data['agustus'] = ($res['agt'] ?? 0) * ($margin_pct / 100);
                $data['september'] = ($res['sep'] ?? 0) * ($margin_pct / 100);
                $data['oktober'] = ($res['okt'] ?? 0) * ($margin_pct / 100);
                $data['november'] = ($res['nov'] ?? 0) * ($margin_pct / 100);
                $data['desember'] = ($res['des'] ?? 0) * ($margin_pct / 100);
            }
        
            $base->close();
            return $data;
        }
		

	}
?>