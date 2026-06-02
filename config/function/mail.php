<?php
	class KirimEmail extends Data{
		function akunBaru($member, $password){
			$data 				= new Data;
			$unik				= $data->validasiMember($member, 'Baru');
			$mobile				= $data->member($member, 'kontak_member');
			$email				= $data->member($member, 'email_member');
			$name				= $data->member($member, 'nama_member');
			$server				= 'official@mhubi.com';
			$mail 				= new PHPMailer();
			$mail->isSMTP();
			$mail->Host			= "smtp.flockmail.com";
			$mail->Mailer		= "smtp";
			$mail->SMTPAuth		= true;
			$mail->Username 	= $server;
			$mail->Password 	= '@Mf010496';
			$mail->SMTPSecure 	= 'tls';
			//$mail->SMTPDebug 	= 3;
			$mail->From 		= $server;
			$mail->FromName 	= 'Mhubi';
			$mail->AddAddress($email, $name);
			$mail->AddReplyTo($server, 'Mhubi');
			$mail->WordWrap 	= 50;
			$mail->IsHTML(true);
			$mail->Subject		= "Konfirmasi Akun";
			$mail->Body			= "
			<p>Hai sahabat Mhubi terimakasih telah melakukan registrasi melalui website resmi kami, berikut data yang kami terima tentang anda, pastikan data di bawah ini benar dan valid.</p>
			<html>
				<head></head>
				<body>
				<table>
					<tr>
						<th><div align='right'>Nama</div></th>
						<td><center>:</center><td>
						<td>".$name."</td>
					</tr>
					<tr>
						<th><div align='right'>Email</div></th>
						<td><center>:</center><td>
						<td>".$email."</td>
					</tr>
					<tr>
						<th><div align='right'>Kontak</div></th>
						<td><center>:</center><td>
						<td>".$mobile."</td>
					</tr>
					<tr>
						<th><div align='right'>Password</div></th>
						<td><center>:</center><td>
						<td>".$password."</td>
					</tr>
				</table>
				</body>
			</html>
			<p>Telah tercatat melakukan registrasi di Mhubi, segera lengkapi data akun anda dengan terlebih dahulu melakukan aktivasi akun dengan cara melakukan konfirmasi pada link berikut >> <a target='_blank' href='".$data->sistem('url_public')."/confirm/".$unik."'>Link Konfirmasi</a> <<</p>
			";
			$mail->AltBody		= "Konfirmasi Akun";
			if($mail->Send()){
				return("Sukses");
			} else {
				return("Error");
			}
		}

		function gantiEmail($member){
			$data 				= new Data;
			$email				= $data->member($member, 'email_member');
			$name				= $data->member($member, 'nama_member');
			$server				= 'official@mhubi.com';
			$mail 				= new PHPMailer();
			$mail->isSMTP();
			$mail->Host			= "smtp.flockmail.com";
			$mail->Mailer		= "smtp";
			$mail->SMTPAuth		= true;
			$mail->Username 	= $server;
			$mail->Password 	= '@Mf010496';
			$mail->SMTPSecure 	= 'tls';
			//$mail->SMTPDebug 	= 3;
			$mail->From 		= $server;
			$mail->FromName 	= 'Mhubi';
			$mail->AddAddress($email, $name);
			$mail->AddReplyTo($server, 'Mhubi');
			$mail->WordWrap 	= 50;
			$mail->IsHTML(true);
			$mail->Subject		= "Ganti Email";
			$mail->Body			= "<p>Hallo sahabat Mhubi... Kami ingin mengkonfirmasi perubahan email akun anda <b>$name</b> menjadi <b>$email</b>, terima kasih telah menjadi member kami.</p>";
			$mail->AltBody		= "Ganti Email";
			if($mail->Send()){
				return("Sukses");
			} else {
				return("Error");
			}
		}
		
		function gantiPass($member, $pass){
			$data 				= new Data;
			$email				= $data->member($member, 'email_member');
			$name				= $data->member($member, 'nama_member');
			$server				= 'official@mhubi.com';
			$mail 				= new PHPMailer();
			$mail->isSMTP();
			$mail->Host			= "smtp.flockmail.com";
			$mail->Mailer		= "smtp";
			$mail->SMTPAuth		= true;
			$mail->Username 	= $server;
			$mail->Password 	= '@Mf010496';
			$mail->SMTPSecure 	= 'tls';
			//$mail->SMTPDebug 	= 3;
			$mail->From 		= $server;
			$mail->FromName 	= 'Mhubi';
			$mail->AddAddress($email, $name);
			$mail->AddReplyTo($server, 'Mhubi');
			$mail->WordWrap 	= 50;
			$mail->IsHTML(true);
			$mail->Subject		= "Ganti Email";
			$mail->Body			= "<p>Hallo sahabat Mhubi... Kami ingin mengkonfirmasi perubahan password akun anda <b>$name</b> menjadi <b>$pass</b>, terima kasih telah menjadi member kami.</p>";
			$mail->AltBody		= "Ganti Email";
			if($mail->Send()){
				return("Sukses");
			} else {
				return("Error");
			}
		}

		function belanja($kode){
			$data 				= new Data;
			$email				= $data->transaksi($kode, 'member.email_member');
			$name				= $data->transaksi($kode, 'member.nama_member');
			$server				= 'official@mhubi.com';
			$mail 				= new PHPMailer();
			$mail->isSMTP();
			$mail->Host			= "smtp.flockmail.com";
			$mail->Mailer		= "smtp";
			$mail->SMTPAuth		= true;
			$mail->Username 	= $server;
			$mail->Password 	= '@Mf010496';
			$mail->SMTPSecure 	= 'tls';
			//$mail->SMTPDebug 	= 3;
			$mail->From 		= $server;
			$mail->FromName 	= 'Mhubi';
			$mail->AddAddress($email, $name);
			$mail->AddReplyTo($server, 'Mhubi');
			$mail->WordWrap 	= 50;
			$mail->IsHTML(true);
			$mail->Subject		= "Transaksi";
			$mail->Body			= "
			<p>Hai sahabat Mhubi terimakasih telah melakukan transaksi melalui website resmi kami, berikut detail tagihan yang telah dibuat.</p>
			<html>
				<head></head>
				<body>
				<table>
					<tr>
						<th><div align='right'>Nama</div></th>
						<td><center>:</center><td>
						<td>".$name."</td>
					</tr>
					<tr>
						<th><div align='right'>Kode Invoice</div></th>
						<td><center>:</center><td>
						<td>#".$kode."</td>
					</tr>
					<tr>
						<th><div align='right'>Mitra</div></th>
						<td><center>:</center><td>
						<td>".$data->transaksi($kode, 'mitra.nama_mitra')."</td>
					</tr>
					<tr>
						<th><div align='right'>Tanggal</div></th>
						<td><center>:</center><td>
						<td>".$data->transaksi($kode, 'transaksi.created_at')."</td>
					</tr>
					<tr>
						<th><div align='right'>Sub. Total</div></th>
						<td><center>:</center><td>
						<td>IDR. ".$data->angka($data->transaksi($kode, 'transaksi.sub_tagihan')).",-</td>
					</tr>
					<tr>
						<th><div align='right'>Diskon Member</div></th>
						<td><center>:</center><td>
						<td>".$data->transaksi($kode, 'transaksi.diskon_member')."%</td>
					</tr>
					<tr>
						<th><div align='right'>Total</div></th>
						<td><center>:</center><td>
						<td>IDR. ".$data->angka($data->transaksi($kode, 'transaksi.total_tagihan')).",-</td>
					</tr>
				</table>
				</body>
			</html>
			<p>Terimakasih anda telah melakukan transaksi bersama Mhubi, terus lakukan transaksi dan dapatkan diskon-diskon menarik dari kami.</p>
			";
			$mail->AltBody		= "Transaksi";
			if($mail->Send()){
				return("Sukses");
			} else {
				return("Error");
			}
		}
	}
?>