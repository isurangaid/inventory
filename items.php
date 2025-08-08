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
    <title>Equipment Inventory | IT Tracker</title>
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
                    <h1 class="h2">Equipment Inventory</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="items_add.php" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-circle"></i> Add New Equipment
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
                            <table id="itemsTable" class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Asset ID</th>
                                        <th>Name</th>
                                        
                                        <th>Category</th>
                                        <th>Location</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?php echo $item['item_id']; ?></td>
                                        <td><?php echo esc($item['asset_id']); ?></td>
                                        <td><?php echo esc(substr($item['name'], 0, 40) . '...')."<br><small>".esc($item['serial_number'])."</small>";?></td>
                                        
                                        <td><?php echo esc($item['category_name']); ?></td>
                                        <td><?php echo esc($item['location_name']); ?></td>
                                        <td>
                                            <?php 
                                                switch($item['status']) {
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
                                            <!-- <a href="items_view.php?item_id=<?php //echo $item['item_id']; ?>" class="btn btn-sm btn-info" title="View">
                                                <i class="bi bi-eye"></i>
                                            </a> -->
                                            <a href="items_edit.php?item_id=<?php echo $item['item_id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="assignments_add.php?item_id=<?php echo $item['item_id']; ?>" class="btn btn-sm btn-primary" title="Assign">
                                                <i class="bi-plus-square"></i>
                                            </a>
                                            <?php if (is_admin()): ?>
                                            <a href="items.php?delete=<?php echo $item['item_id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
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
                { orderable: false, targets: [3] } // Disable sorting on actions column
            ],
            order: [[0, 'asc']] // Sort by item_id by default
        });
    });
    </script>
</body>
</html>