<div class="container-fluid">
    <div class="row">
        <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="<?php echo url('dashboard.php'); ?>">
                            <i class="bi bi-speedometer2 me-2"></i>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('items.php'); ?>">
                            <i class="bi bi-pc-display me-2"></i>
                            Equipment Inventory
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('assignments.php'); ?>">
                            <i class="bi bi-people me-2"></i>
                            Assignments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('locations.php'); ?>">
                            <i class="bi bi-geo-alt me-2"></i>
                            Locations
                        </a>
                    </li>
                    <?php if (is_admin()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('employees.php'); ?>">
                            <i class="bi bi-person-lines-fill me-2"></i>
                            Employee Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('reports.php'); ?>">
                            <i class="bi bi-graph-up me-2"></i>
                            Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('printer_maintenance.php'); ?>">
                            <i class="bi bi-journal-text me-2"></i>
                            Printer Maintenance
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>