<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

// Initialize stats array
$stats = [
    'total_assets' => 0,
    'working_assets' => 0,
    'assigned_assets' => 0,
    'repair_assets' => 0
];

// Get total assets count
$query = "SELECT COUNT(*) as total FROM items";
$result = $conn->query($query);
if ($result) {
    $stats['total_assets'] = $result->fetch_assoc()['total'];
} else {
    // Log error if query fails
    error_log("Database error: " . $conn->error);
}

// Get working assets count
$query = "SELECT COUNT(*) as total FROM items WHERE status = '1'";
$result = $conn->query($query);
if ($result) {
    $stats['working_assets'] = $result->fetch_assoc()['total'];
}

// Get assigned assets count
$query = "SELECT COUNT(*) as total FROM assignments WHERE return_date IS NULL";
$result = $conn->query($query);
if ($result) {
    $stats['assigned_assets'] = $result->fetch_assoc()['total'];
}

// Get assets under repair count
$query = "SELECT COUNT(*) as total FROM items WHERE status = 'Under Repair'";
$result = $conn->query($query);
if ($result) {
    $stats['repair_assets'] = $result->fetch_assoc()['total'];
}

// Get recent assignments
$assignments = [];
$query = "SELECT a.*, i.asset_id as asset_id, e.employee_name as employee_name, l.name as location_name 
          FROM assignments a
          JOIN items i ON a.item_id = i.item_id
          JOIN employee e ON a.user_id = e.id
          JOIN locations l ON a.assigned_location_id = l.location_id
          WHERE a.return_date IS NULL
          ORDER BY a.created_at DESC 
          LIMIT 5
          ";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $assignments[] = $row;
    }
}

// Get recent audit logs
$logs = [];
$query = "SELECT l.*, u.username 
          FROM audit_log l
          LEFT JOIN users u ON l.user_id = u.user_id
          ORDER BY l.created_at DESC
          LIMIT 10";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
}

// Rest of your dashboard HTML code...
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | IT Equipment Tracker</title>
    <?php include __DIR__ . '/includes/header_scripts.php'; ?>
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <div class="container-fluid flex-grow-1">
        <div class="row">
            <?php include __DIR__ . '/includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Total Assets</h6>
                                        <h2 class="mb-0"><?php echo $stats['total_assets']; ?></h2>
                                    </div>
                                    <i class="bi bi-pc-display-horizontal fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card text-white bg-success mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Working Assets</h6>
                                        <h2 class="mb-0"><?php echo $stats['working_assets']; ?></h2>
                                    </div>
                                    <i class="bi bi-check-circle fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card text-white bg-info mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Assigned Assets</h6>
                                        <h2 class="mb-0"><?php echo $stats['assigned_assets']; ?></h2>
                                    </div>
                                    <i class="bi bi-people fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card text-white bg-warning mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Under Repair</h6>
                                        <h2 class="mb-0"><?php echo $stats['repair_assets']; ?></h2>
                                    </div>
                                    <i class="bi bi-tools fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Recent Assignments -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Recent Assignments</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Asset</th>
                                                <th>Assigned To</th>
                                                <th>Location</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($assignments as $assignment): ?>
                                            <tr>
                                                <td><?php echo esc($assignment['asset_id']); ?></td>
                                                <td><?php echo esc($assignment['employee_name']); ?></td>
                                                <td><?php echo esc($assignment['location_name']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($assignment['assigned_date'])); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <a href="assignments.php" class="btn btn-sm btn-outline-primary mt-2">View All Assignments</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Recent Activity</h5>
                            </div>
                            <div class="card-body">
                                <div class="activity-feed">
                                    <?php foreach ($logs as $log): ?>
                                    <div class="activity-item mb-3">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <strong><?php echo esc($log['username'] ?? 'System'); ?></strong>
                                                <span class="text-muted"><?php echo esc($log['action']); ?></span>
                                            </div>
                                            <small class="text-muted"><?php echo date('M d H:i', strtotime($log['created_at'])); ?></small>
                                        </div>
                                        <small class="text-muted"><?php echo esc($log['action_details']); ?></small>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <a href="audit_log.php" class="btn btn-sm btn-outline-primary mt-2">View Full Log</a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>
    <?php include __DIR__ . '/includes/footer_scripts.php'; ?>
    <script src="<?php //echo url('assets/js/dashboard.js'); ?>"></script>
</body>
</html>

<div class="container mt-5">
    <h5>🧮 Toner/Drum Replacement Predictions</h5>
    <div class="card p-3">
        <?php
        $sql = "
            SELECT 
                i.asset_id, i.item_name, 
                pmp.part_type, pmp.part_color, MAX(pmp.installed_date) as last_installed,
                DATEDIFF(CURDATE(), MAX(pmp.installed_date)) as days_since_last
            FROM printer_maintenance_parts pmp
            JOIN printer_maintenance pm ON pm.id = pmp.maintenance_id
            JOIN items i ON i.id = pm.printer_id
            WHERE pmp.part_type IN ('Toner', 'Drum')
            GROUP BY pm.printer_id, pmp.part_type, pmp.part_color
            ORDER BY days_since_last DESC
        ";

        $results = $conn->query($sql);
        if ($results && $results->num_rows > 0):
        ?>
        <table class="table table-sm table-bordered">
            <thead class="thead-light">
                <tr>
                    <th>Printer</th>
                    <th>Part</th>
                    <th>Color</th>
                    <th>Last Installed</th>
                    <th>Days Since</th>
                    <th>Estimated Next Replacement</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($r = $results->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($r['asset_id'] . ' - ' . $r['item_name']) ?></td>
                    <td><?= htmlspecialchars($r['part_type']) ?></td>
                    <td><?= htmlspecialchars($r['part_color']) ?></td>
                    <td><?= htmlspecialchars($r['last_installed']) ?></td>
                    <td><?= htmlspecialchars($r['days_since_last']) ?> days</td>
                    <td>
                        <?php
                        $avg_days = 60; // Default average interval
                        $est = date('Y-m-d', strtotime($r['last_installed'] . " +{$avg_days} days"));
                        echo $est;
                        ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p>No prediction data available yet.</p>
        <?php endif; ?>
    </div>
</div>
