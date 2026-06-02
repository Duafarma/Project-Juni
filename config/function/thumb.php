<?php
	class File{
		function klaimBonus($fupload_name){
			$vdir_upload	= "../../berkas/klaimBonus/";
			$vfile_upload	= $vdir_upload.$fupload_name;
			$imageType		= $_FILES["bukti"]["type"];			
			move_uploaded_file($_FILES["bukti"]["tmp_name"], $vfile_upload);
		}

		function fotoMember($fupload_name,$nama_file){
			$vdir_upload	= "../../berkas/member/";
			$vfile_upload	= $vdir_upload.$fupload_name;
				$imageType		= $_FILES["$nama_file"]["type"];			
				move_uploaded_file($_FILES["$nama_file"]["tmp_name"], $vfile_upload);
			switch($imageType){
				case "image/gif":
					$im_src	= imagecreatefromgif($vfile_upload); 
				break;
				case "image/pjpeg":
				case "image/jpeg":
				case "image/jpg":
					$im_src	= imagecreatefromjpeg($vfile_upload); 
				break;
				case "image/png":
				case "image/x-png":
					$im_src	= imagecreatefrompng($vfile_upload); 
				break;
			}
			$src_width	= imageSX($im_src);
			$src_height	= imageSY($im_src);
			$dst_width	= 150;
			$dst_height = 165;
			$im 		= imagecreatetruecolor($dst_width,$dst_height);
			imagecopyresampled($im, $im_src, 0, 0, 0, 0, $dst_width, $dst_height, $src_width, $src_height);
			switch($imageType) {
				case "image/gif":
					imagegif($im,$vdir_upload.$fupload_name);
				break;
				case "image/pjpeg":
				case "image/jpeg":
				case "image/jpg":
					imagejpeg($im,$vdir_upload.$fupload_name);
				break;
				case "image/png":
				case "image/x-png":
					imagepng($im,$vdir_upload.$fupload_name);
				break;
			}
		}

		function ktpMember($fupload_name,$nama_file){
			$vdir_upload	= "../../berkas/ktp/";
			$vfile_upload	= $vdir_upload.$fupload_name;
				$imageType		= $_FILES["$nama_file"]["type"];			
				move_uploaded_file($_FILES["$nama_file"]["tmp_name"], $vfile_upload);
			switch($imageType){
				case "image/gif":
					$im_src	= imagecreatefromgif($vfile_upload); 
				break;
				case "image/pjpeg":
				case "image/jpeg":
				case "image/jpg":
					$im_src	= imagecreatefromjpeg($vfile_upload); 
				break;
				case "image/png":
				case "image/x-png":
					$im_src	= imagecreatefrompng($vfile_upload); 
				break;
			}
			$src_width	= imageSX($im_src);
			$src_height	= imageSY($im_src);
			$dst_width	= 550;
			$dst_height = 300;
			$im 		= imagecreatetruecolor($dst_width,$dst_height);
			imagecopyresampled($im, $im_src, 0, 0, 0, 0, $dst_width, $dst_height, $src_width, $src_height);
			switch($imageType) {
				case "image/gif":
					imagegif($im,$vdir_upload.$fupload_name);
				break;
				case "image/pjpeg":
				case "image/jpeg":
				case "image/jpg":
					imagejpeg($im,$vdir_upload.$fupload_name);
				break;
				case "image/png":
				case "image/x-png":
					imagepng($im,$vdir_upload.$fupload_name);
				break;
			}
		}

		function uploadfoto($folder, $asli, $nama, $lebar, $tinggi){
			$vdir_upload	= "../../berkas/$folder/";
			$vfile_upload	= $vdir_upload.$nama;
				$imageType		= $_FILES["$asli"]["type"];			
				move_uploaded_file($_FILES["$asli"]["tmp_name"], $vfile_upload);
			switch($imageType){
				case "image/gif":
					$im_src	= imagecreatefromgif($vfile_upload); 
				break;
				case "image/pjpeg":
				case "image/jpeg":
				case "image/jpg":
					$im_src	= imagecreatefromjpeg($vfile_upload); 
				break;
				case "image/png":
				case "image/x-png":
					$im_src	= imagecreatefrompng($vfile_upload); 
				break;
			}
			$src_width	= imageSX($im_src);
			$src_height	= imageSY($im_src);
			$dst_width	= $lebar;
			$dst_height = $tinggi;
			$im 		= imagecreatetruecolor($dst_width,$dst_height);
			imagecopyresampled($im, $im_src, 0, 0, 0, 0, $dst_width, $dst_height, $src_width, $src_height);
			switch($imageType) {
				case "image/gif":
					imagegif($im,$vdir_upload.$nama);
				break;
				case "image/pjpeg":
				case "image/jpeg":
				case "image/jpg":
					imagejpeg($im,$vdir_upload.$nama);
				break;
				case "image/png":
				case "image/x-png":
					imagepng($im,$vdir_upload.$nama);
				break;
			}
		}
	}
?>