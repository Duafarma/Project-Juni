<div class="header">
    <div class="header-left">
        <a href="" class="burger-menu"><i data-feather="menu"></i></a>
		<!--
        <div class="header-search">
            <i data-feather="search"></i>
            <input type="search" class="form-control" placeholder="What are you looking for?">
        </div>
		-->
        <!-- header-search -->
    </div>
    <!-- header-left -->

    <div class="header-right">
        <a href="" class="header-help-link"><i data-feather="calendar"></i> <?php echo($date->getHari(date('Y-m-d')).', '.$date->tgl_indo(date('Y-m-d'))); ?></a>

        <div class="dropdown dropdown-loggeduser">
            <a href="" class="dropdown-link" data-toggle="dropdown">
                <div class="avatar avatar-sm">
                    <img src="<?php echo("$sistem/berkas/icon/admin.png"); ?>" class="rounded-circle" alt="">
                </div>
                <!-- avatar -->
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                <div class="dropdown-menu-header">
                    <div class="media align-items-center">
                        <div class="avatar">
                            <img src="<?php echo("$sistem/berkas/icon/admin.png"); ?>" class="rounded-circle" alt="">
                        </div>
                        <!-- avatar -->
                        <div class="media-body mg-l-10">
                            <h6><?php echo($data->myadmin($admin, 'nama_adm')); ?></h6>
                            <span>Administrator</span>
                        </div>
                    </div>
                    <!-- media -->
                </div>
                <div class="dropdown-menu-body">
                    <a href="" class="dropdown-item"><i data-feather="user"></i> View Profile</a>
                    <a href="" class="dropdown-item"><i data-feather="edit-2"></i> Edit Profile</a>
                    <a href="" class="dropdown-item"><i data-feather="briefcase"></i> Account Settings</a>
                    <a href="" class="dropdown-item"><i data-feather="shield"></i> Privacy Settings</a>
                    <a href="<?php echo("$sistem/signout"); ?>" class="dropdown-item"><i data-feather="log-out"></i> Sign Out</a>
                </div>
            </div>
            <!-- dropdown-menu -->
        </div>
    </div>
    <!-- header-right -->
</div>