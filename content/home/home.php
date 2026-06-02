<?php
    $role = $secu->injection(@$_COOKIE['jeniskuy']);
    if(strtolower($role) == 'finance') {
        include 'home_finance.php';
    }else {
        include 'home_admin.php';
    }
?>
