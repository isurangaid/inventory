


    <header class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow px-2">
    <div class="d-flex align-items-center w-100 justify-content-between">
        <a class="navbar-brand me-0 px-2 fw-bold text-truncate" href="<?php echo url('dashboard.php'); ?>" style="max-width: 80%;">
            <i class="bi bi-pc-display me-2"></i>
            <span class="d-inline-block text-truncate" style="max-width: 100%;">IT Equipment Tracker</span>
        </a>

        <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
    </div>

    <div class="navbar-nav ms-auto">
        <div class="nav-item text-nowrap d-flex align-items-center">
            <span class="text-white me-3 d-none d-sm-inline">Welcome, <?php echo $_SESSION['full_name']; ?></span>
            <a class="nav-link px-3" href="<?php echo url('auth/logout.php'); ?>">Sign out</a>
        </div>
    </div>
</header>
