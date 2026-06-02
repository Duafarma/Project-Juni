<?php
		

		
		function stokin($kode) {
			$conn	= $this->open();
			$rin	= $conn->prepare("SELECT IFNULL(SUM(A.jumlah_ttd), 0) AS total FROM transaksi_transferstockdetail AS A INNER JOIN transaksi_transferstock AS B ON A.id_ttr=B.id_ttr WHERE A.id_pro=:kode AND MONTH(B.tgl_ttr) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) AND YEAR(B.tgl_ttr) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) AND B.tipe_ttr='IN' ");
			$rin->bindParam(':kode', $kode, PDO::PARAM_STR);
			$rin->execute();
			$vin	= $rin->fetch(PDO::FETCH_ASSOC);
			return $vin['total'];
		}
		
		function stokoutr($kode) {
			$conn	= $this->open();
			$read	= $conn->prepare("SELECT IFNULL(SUM(A.jumlah_ttd), 0) AS total FROM transaksi_transferstockdetail AS A INNER JOIN transaksi_transferstock AS B ON A.id_ttr=B.id_ttr WHERE A.id_pro=:kode AND MONTH(B.tgl_ttr) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) AND YEAR(B.tgl_ttr) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) AND B.tipe_ttr='OUT' ");
			$read->bindParam(':kode', $kode, PDO::PARAM_STR);
			$read->execute();
			$conn	= $this->close();
			$view	= $read->fetch(PDO::FETCH_ASSOC);
			return $view['total'];
		}

		function stokout($kode) {
			$conn	= $this->open();
			$read	= $conn->prepare("SELECT IFNULL(SUM(A.jumlah_tfd), 0) AS total FROM transaksi_fakturdetail AS A INNER JOIN transaksi_faktur AS B ON A.id_tfk=B.id_tfk WHERE A.id_pro=:kode AND MONTH(B.tgl_tfk) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) AND YEAR(B.tgl_tfk) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) ");
			$read->bindParam(':kode', $kode, PDO::PARAM_STR);
			$read->execute();
			$conn	= $this->close();
			$view	= $read->fetch(PDO::FETCH_ASSOC);
			return $view['total'];
		}
		
		function stokour($kode) {
			$conn	= $this->open();
			$read	= $conn->prepare("SELECT IFNULL(SUM(A.jumlah_tfd), 0) AS total FROM transaksi_fakturdetail_r AS A INNER JOIN transaksi_faktur_r AS B ON A.id_tfk=B.id_tfk WHERE A.id_pro=:kode AND MONTH(B.tgl_tfk) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) AND YEAR(B.tgl_tfk) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) ");
			$read->bindParam(':kode', $kode, PDO::PARAM_STR);
			$read->execute();
			$conn	= $this->close();
			$view	= $read->fetch(PDO::FETCH_ASSOC);
			return $view['total'];
		}
        	
		function penjualanout($kode) {
			$conn	= $this->open();
			$read	= $conn->prepare("SELECT IFNULL(SUM(A.jumlah_tfd),0) AS total FROM transaksi_fakturdetail AS A INNER JOIN transaksi_faktur AS B ON A.id_tfk=B.id_tfk WHERE A.id_pro IN (:kode) AND MONTH(B.tgl_tfk) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) AND YEAR(B.tgl_tfk) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))");
			$read->bindParam(':kode', $kode, PDO::PARAM_STR);
			$read->execute();
			$view	= $read->fetch(PDO::FETCH_ASSOC);
			
			$read2	= $conn->prepare("SELECT IFNULL(SUM(A.jumlah_tfd),0) AS total FROM transaksi_fakturdetail_pim AS A INNER JOIN transaksi_faktur_pim AS B ON A.id_tfk=B.id_tfk WHERE A.id_pro IN (:kode) AND MONTH(B.tgl_tfk) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) AND YEAR(B.tgl_tfk) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))");
			$read2->bindParam(':kode', $kode, PDO::PARAM_STR);
			$read2->execute();
			$view2	= $read2->fetch(PDO::FETCH_ASSOC);
			
			$conn	= $this->close();
			$total = $view['total'] + $view2['total'];
			return $total;
		}
        	
?>