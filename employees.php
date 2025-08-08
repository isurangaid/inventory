<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

// Handle delete action (if needed in future)
if (isset($_GET['delete']) && is_admin()) {
    $emp_id = (int)$_GET['delete'];
    $conn->query("INSERT INTO audit_log (user_id, action, table_affected, record_id, action_details, ip_address, created_at)
                  VALUES ('{$_SESSION['user_id']}', 'Delete', 'employee', '$emp_id', 'Deleted employee record', '{$_SERVER['REMOTE_ADDR']}', '".date('Y-m-d H:i:s')."')");
    $conn->query("DELETE FROM employee WHERE id = $emp_id");
    $_SESSION['message'] = "Employee deleted successfully";
    header("Location: employees.php");
    exit();
}

// Fetch employees
$employees = [];
$result = $conn->query("SELECT * FROM employee ORDER BY id");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
}
$status_labels = [1 => 'Active', 2 => 'Inactive'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employees | IT Tracker</title>
    <?php include __DIR__ . '/includes/header_scripts.php'; ?>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<div class="container-fluid flex-grow-1">
    <div class="row">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Employees</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="add_employee.php" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-circle"></i> Add New Employee
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
                        <table id="employeesTable" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Employee ID</th>
                                    <th>Name</th>
                                    <th>Designation</th>
                                    <th>Email</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($employees as $emp): ?>
                                <tr>
                                    <td><?php echo $emp['id']; ?></td>
                                    <td><?php echo esc($emp['employee_id']); ?></td>
                                    <td><?php echo esc($emp['employee_name']); ?></td>
                                    <td><?php echo esc($emp['designation']); ?></td>
                                    <td><?php echo esc($emp['email']); ?></td>
                                    <td><?php echo esc($emp['working_location']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $emp['employment_status'] == 1 ? 'success' : 'secondary'; ?>">
                                            <?php echo $status_labels[$emp['employment_status']]; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="add_employee.php?id=<?php echo $emp['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if (is_admin()): ?>
                                        <a href="employees.php?delete=<?php echo $emp['id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                        <a href="employee_items.php?id=<?php echo $emp['id']; ?>" class="btn btn-sm btn-info" title="View Assigned Items">
                                            <i class="bi bi-list-check"></i>
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
        $('#employeesTable').DataTable({
            responsive: true,
            order: [[0, 'asc']]
        });
    });
</script>
</body>
</html>
