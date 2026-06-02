<!DOCTYPE html>
<html lang="en">
<?php
	require_once('config/connection/connection.php');
	require_once('config/function/data.php');
	$base	= new DB;
	$data	= new Data;
	$sistem	= $data->sistem('url_sis');
?>
    <head>
        <!-- Required meta tags -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <!-- Meta -->
        <meta name="description" content="Inventory Sistem">
        <meta name="author" content="Fazlurr">
        <!-- Favicon -->
        <link rel="shortcut icon" type="image/x-icon" href="<?php echo("$sistem/berkas/sistem/".$data->sistem('favicon_sis')); ?>">
        <title><?php echo($data->sistem('app_sis')); ?></title>
        <!-- vendor css -->
        <link href="<?php echo("$sistem/lib/@fortawesome/fontawesome-free/css/all.min.css"); ?>" rel="stylesheet">
        <link href="<?php echo("$sistem/lib/ionicons/css/ionicons.min.css"); ?>" rel="stylesheet">
        <link href="<?php echo("$sistem/sweetalert/css/sweetalert.css"); ?>" rel="stylesheet">
        <!-- template css -->
        <link rel="stylesheet" href="<?php echo("$sistem/assets/css/cassie.css"); ?>">
    </head>
    <body>
        <div class="signin-panel">
            <img src="<?php echo("$sistem/berkas/sistem/login.jpg"); ?>" class="svg-bg" />
    
            <div class="signin-sidebar">
                <div class="signin-sidebar-body">
                    <a href="<?php echo("$sistem/signin"); ?>" class="sidebar-logo mg-b-0"><span><?php echo($data->sistem('app_sis')); ?></span></a>
                    <small class="sidebar-logo-headline mg-b-10"><?php echo($data->sistem('tagline_sis')); ?></small>
					<?php /*<img src="<?php echo("$sistem/berkas/sistem/".$data->sistem('logo_sis')); ?>" class="mg-b-10" width="330" />*/ ?>
                    <h4 class="signin-title">Welcome back!</h4>
                    <h5 class="signin-subtitle">Please signin to continue.</h5>
                    <form action="#" method="post" id="formlogin" autocomplete="off">
                    <div class="signin-form">
                        <div class="form-group">
                            <label>Email address</label>
                            <input type="email" name="emailz" id="emailz" class="form-control" placeholder="Enter your email address" required="required" />
                        </div>
    
                        <div class="form-group">
                            <label class="d-flex justify-content-between">
                                <span>Password</span>
                                <a href="#" class="tx-13">Forgot password?</a>
                            </label>
                            <input type="password" name="passz" id="passz" class="form-control" placeholder="Enter your password"  required="required" />
                        </div>
    
                        <div class="form-group d-flex mg-b-0">
                            <button type="submit" class="btn btn-brand-01 btn-uppercase flex-fill" id="blogin"><i data-feather="user"></i> Sign In</button>
                            <!-- <a href="pages/page-signup.html" class="btn btn-white btn-uppercase flex-fill mg-l-10">Sign Up</a> -->
                        </div>
						<!--
                        <div class="divider-text mg-y-30">Or</div>    
                        <a href="pages/dashboard-one.html" class="btn btn-facebook btn-uppercase btn-block">Login with Facebook</a>
                    	-->
                    </div>
                    </form>
                    <p class="mg-t-auto mg-b-0 tx-sm tx-color-03">By signing in, you agree to our <a href="">Terms of Use</a> and <a href="">Privacy Policy</a></p>
                </div>
                <!-- signin-sidebar-body -->
            </div>
            <!-- signin-sidebar -->
        </div>
        <!-- signin-panel -->
    
        <script type="text/javascript" src="<?php echo("$sistem/lib/jquery/jquery.min.js"); ?>"></script>
        <script type="text/javascript" src="<?php echo("$sistem/lib/bootstrap/js/bootstrap.bundle.min.js"); ?>"></script>
        <script type="text/javascript" src="<?php echo("$sistem/lib/feather-icons/feather.min.js"); ?>"></script>
        <script type="text/javascript" src="<?php echo("$sistem/lib/perfect-scrollbar/perfect-scrollbar.min.js"); ?>"></script>
        <script type="text/javascript">
		$(function() {
			'use strict'

			feather.replace();

			new PerfectScrollbar('.signin-sidebar', {
				suppressScrollX: true
			});

		});
        </script>
        <script type="text/javascript" src="<?php echo("$sistem/sweetalert/js/sweetalert.min.js"); ?>"></script>
        <script type="text/javascript" src="<?php echo("$sistem/config/js/jquery.maskedinput.js"); ?>"></script>
        <script type="text/javascript" src="<?php echo("$sistem/config/js/fazlurr.js"); ?>?t=<?=time()?>"></script>
    </body>
</html>