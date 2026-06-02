<?php
	date_default_timezone_set('Asia/Jakarta');
	class DB {
		private $server		= "mysql:host=localhost; dbname=program-q2";
		private $user		= "root";
		private $pass		= "";
		private $options	= array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,);
		protected $con;
		public function open() {
			try {
				$this->con = new PDO($this->server, $this->user,$this->pass,$this->options);
				return $this->con;
			} catch (PDOException $e) {
				return("error");
			}
		}
		public function close() {
			$this->con = null;
		}
	}
?>