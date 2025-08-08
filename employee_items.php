<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

$employee_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$employee_id) {
    $_SESSION['message'] = "Invalid employee ID.";
    header("Location: employees.php");
    exit();
}

// Fetch employee
$employee = null;
$result = $conn->query("SELECT * FROM employee WHERE id = $employee_id");
if ($result && $result->num_rows > 0) {
    $employee = $result->fetch_assoc();
} else {
    $_SESSION['message'] = "Employee not found.";
    header("Location: employees.php");
    exit();
}

// Fetch assigned items
$assigned_items = [];
$sql = "SELECT i.asset_id, i.name, i.serial_number
        FROM assignments a
        INNER JOIN items i ON a.item_id = i.item_id
        WHERE a.user_id = $employee_id AND a.return_date IS NULL";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $assigned_items[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assigned Items - <?php echo htmlspecialchars($employee['employee_name']); ?></title>
    <?php include __DIR__ . '/includes/header_scripts.php'; ?>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
    <div class="container-fluid flex-grow-1">
        <div class="row">
            <?php include __DIR__ . '/includes/sidebar.php'; ?>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                    <h2>Assigned Items for <?php echo htmlspecialchars($employee['employee_name']); ?></h2>
                    <div>
                        <a href="generate_employee_pdf.php?id=<?php echo $employee_id; ?>" class="btn btn-outline-secondary" target="_blank">
                            <i class="bi bi-download"></i> Download PDF
                        </a>
                        <button class="btn btn-primary" onclick="confirmEmailSend(<?php echo $employee_id; ?>)">
                            <i class="bi bi-envelope"></i> Send Email with PDF
                        </button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <?php if (empty($assigned_items)): ?>
                            <div class="alert alert-warning">No items currently assigned to this employee.</div>
                        <?php else: ?>
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Asset ID</th>
                                        <th>Serial Number</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assigned_items as $index => $item): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($item['asset_id']); ?></td>
                                            <td><?php echo strtoupper(htmlspecialchars($item['serial_number'])); ?></td>
                                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/includes/footer_scripts.php'; ?>
<script>
function confirmEmailSend(empId) {
    if (confirm('Are you sure you want to send the PDF report to this employee via email?')) {
        window.location.href = 'send_employee_email.php?id=' + empId;
    }
}
</script>
</body>
</html>
