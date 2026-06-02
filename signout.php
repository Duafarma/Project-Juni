<?php
	session_start();
	session_unset();
	session_destroy();
	setcookie("adminkuy", "", time() - 86400, "/");
	setcookie("kuncikuy", "", time() - 86400, "/");
	//setcookie("siseutemgwanlijakuki", "", time() - 86400, "/");
	header('location:signin');
?>