<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

// Handle delete action
if (isset($_GET['delete']) && is_admin()) {
    $item_id = (int)$_GET['delete'];
    
    // Log before deleting
    $conn->query("INSERT INTO audit_log (user_id, action, table_affected, record_id, action_details, ip_address) 
                 VALUES ('{$_SESSION['user_id']}', 'Delete', 'items', '$item_id', 'Deleted equipment item', '{$_SERVER['REMOTE_ADDR']}')");
    
    $conn->query("DELETE FROM items WHERE item_id = $item_id");
    $_SESSION['message'] = "Equipment deleted successfully";
    header("Location: items.php");
    exit();
}

// Get all equipment with category and location info
$items = [];
$query = "SELECT i.*, c.name as category_name, l.name as location_name, s.name as supplier_name, serial_number 
          FROM items i
          LEFT JOIN category c ON i.category_id = c.category_id
          LEFT JOIN locations l ON i.location_id = l.location_id
          LEFT JOIN suppliers s ON i.supplier_id = s.supplier_id
          ORDER BY i.item_id";
$result = $conn->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Printer Maintenance Records | IT Tracker</title>
    <?php include __DIR__ . '/includes/header_scripts.php'; ?>
    <!-- Then load DataTables CSS and JS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    
    <div class="container-fluid flex-grow-1">
        <div class="row">
            <?php include __DIR__ . '/includes/sidebar.php'; ?>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Printer Maintenance Records</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="printer_maintenance_add.php" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-circle"></i> Add Maintenance
                        </a>
                    </div>
                </div>

                <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="itemsTable">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Printer</th>
                                    <th>Service Type</th>
                                    <th>Last Service Date</th>
                                    <th>Serviced By</th>
                                    <th>Print Count</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                                $result = $conn->query("
                                SELECT
                                    pm.*, 
                                    i.asset_id, 
                                    i.name,
                                    i.status,
                                    IFNULL(bill_counts.bill_count, 0) AS bill_count
                                FROM printer_maintenance pm
                                JOIN (
                                    SELECT printer_id, MAX(service_date) AS latest_service_date
                                    FROM printer_maintenance
                                    GROUP BY printer_id
                                ) sub ON pm.printer_id = sub.printer_id AND pm.service_date = sub.latest_service_date
                                JOIN items i ON pm.printer_id = i.item_id
                                LEFT JOIN (
                                    SELECT maintenance_id, COUNT(*) AS bill_count
                                    FROM printer_maintenance_bills
                                    GROUP BY maintenance_id
                                ) bill_counts ON pm.id = bill_counts.maintenance_id
                                ORDER BY pm.service_date DESC;  
                                ");

                                if ($result && $result->num_rows > 0):
                                    while ($row = $result->fetch_assoc()):
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['asset_id'] . ' - ' . $row['name']) ?></td>
                                    <td><?= htmlspecialchars($row['service_type']) ?></td>
                                    <td><?= htmlspecialchars($row['service_date']) ?></td>
                                    <td><?= htmlspecialchars($row['serviced_by']) ?></td>
                                    <td><?= htmlspecialchars($row['print_count']) ?></td>
                                    <td>
                                        <?php 
                                            switch($row['status']) {
                                                case '1': echo '<span class="badge bg-success">Working'; break;
                                                case '2': echo '<span class="badge bg-warning">Under Repair'; break;
                                                case '3': echo '<span class="badge bg-danger">Out of Service'; break;
                                                case 'Under Repair': echo 'warning'; break;
                                                case 'Out of Service': echo 'danger'; break;
                                                default: echo '<span class="badge bg-secondary">'.$item['status'].'';
                                            }
                                        ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="printer_maintenance_view.php?printer_id=<?= $row['printer_id'] ?>&selected=all" class="btn btn-sm btn-info">View</a>
                                        <a href="add_bill.php?printer_id=<?= $row['printer_id'] ?>&selected=all" class="btn btn-sm btn-default">Add Bill</a> 
                                    </td>
                                </tr>
                            <?php
                                    endwhile;
                                else:
                            ?>
                                <tr>
                                    <td colspan="7" class="text-center">No maintenance records found.</td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>
    <?php include __DIR__ . '/includes/footer_scripts.php'; ?>
    
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#itemsTable').DataTable({
            responsive: true,
            columnDefs: [
                { orderable: false, targets: [6] } // Disable sorting on actions column
            ],
            order: [[2, 'desc']] // Sort by item_id by default
        });
    });
    </script>
</body>
</html>