<?php
	class Security extends DB {
		function injection($data) {
			//$filter = strip_tags(stripslashes(htmlspecialchars($data)));
			$filter = stripslashes(strip_tags(htmlspecialchars($data)));
			return $filter;
		}

		function enkripsi($str) {
			$kata	= '';
			$kunci 	= '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ@#$%^&*()-+=_}{][|\;.,/?><:';
			for ($i = 0; $i<strlen($str); $i++) {
				$kara	= substr($str, $i, 1);
				$kura	= substr($kunci, ($i % strlen($kunci)) - 1, 1);
				$kira	= chr(ord($kara) + ord($kura));
				$kata	.= $kira;
			}
			$hasil	= urlencode(base64_encode($kata));
			return $hasil;
		}
		
		function dekripsi($str) {
			$str	= base64_decode(urldecode($str));
			$hasil	= '';
			$kunci 	= '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ@#$%^&*()-+=_}{][|\;.,/?><:';
			for ($i = 0; $i < strlen($str); $i++) {
				$kara	= substr($str, $i, 1);
				$kura	= substr($kunci, ($i % strlen($kunci))-1, 1);
				$kira	= chr(ord($kara) - ord($kura));
				$hasil .= $kira;
			}
			return $hasil;
		}

		function encryptsalt($kode, $unik){
			$hasil	= crypt($kode, $unik);
			return $hasil;
		}
		
		function cekencryptsalt($kode, $password){
			$hasil	= hash_equals($password, crypt($kode, $password)) ? true : false ;
			return $hasil;
		}
		
		function hashpass($kode){
			$hasil	= password_hash($kode, PASSWORD_DEFAULT);;
			return $hasil;
		}
		
		function cekhashpass($kode, $password){
			$hasil	= password_verify($kode, $password) ? true : false;
			return $hasil;
		}
		
		function validadmin($kode, $kunci){
			$secu	= new Security;
			$now	= date('Y-m-d H:i:s');
			$conn	= $this->open();
			$user	= $secu->injection($kode);
			$sesi	= $secu->injection($kunci);
			$active	= "Active";
			$mlogin	= $conn->prepare("SELECT A.batas_alo FROM adminz_login AS A INNER JOIN adminz AS B ON A.id_adm=B.id_adm WHERE A.id_adm=:user AND A.kunci_alo=:sesi AND A.status_alo=:active AND B.status_adm=:active");
			$mlogin->bindParam(':user', $user, PDO::PARAM_STR);
			$mlogin->bindParam(':sesi', $sesi, PDO::PARAM_STR);
			$mlogin->bindParam(':active', $active, PDO::PARAM_STR);
			$mlogin->execute();
			$hlogin	= $mlogin->fetch(PDO::FETCH_ASSOC);
			$hasil	= (empty($hlogin['batas_alo']) or $hlogin['batas_alo']<$now) ? false : true;
			return $hasil;
    	}
	}
?>