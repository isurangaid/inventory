<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

$assignment_id = isset($_GET['assignment_id']) ? (int)$_GET['assignment_id'] : 0;
if (!$assignment_id) {
    header("Location: assignments.php");
    exit();
}

// Get assignment details
$query = "SELECT a.*, 
          i.item_id, i.asset_id, i.name as item_name, i.serial_number,
          u.user_id, u.full_name as user_name, u.email as user_email,
          l.location_id, l.name as location_name,
          ab.full_name as assigned_by_name
          FROM assignments a
          JOIN items i ON a.item_id = i.item_id
          JOIN users u ON a.user_id = u.user_id
          JOIN locations l ON a.assigned_location_id = l.location_id
          JOIN users ab ON a.assigned_by = ab.user_id
          WHERE a.assignment_id = $assignment_id";
$result = $conn->query($query);

if ($result->num_rows === 0) {
    header("Location: assignments.php");
    exit();
}

$assignment = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignment Details | IT Tracker</title>
    <?php include __DIR__ . '/includes/header_scripts.php'; ?>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <div class="container-fluid flex-grow-1">
        <div class="row">
            <?php include __DIR__ . '/includes/sidebar.php'; ?>
    
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Assignment Details</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="assignments.php" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Assignments
                        </a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5 class="mb-3">Equipment Information</h5>
                                <div class="mb-2">
                                    <strong>Name:</strong> <?php echo esc($assignment['item_name']); ?>
                                </div>
                                <div class="mb-2">
                                    <strong>Asset ID:</strong> <?php echo esc($assignment['asset_id']); ?>
                                </div>
                                <div class="mb-2">
                                    <strong>Serial Number:</strong> <?php echo esc($assignment['serial_number']); ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5 class="mb-3">Assignment Information</h5>
                                <div class="mb-2">
                                    <strong>Status:</strong> 
                                    <span class="badge bg-<?php echo $assignment['return_date'] ? 'secondary' : 'success'; ?>">
                                        <?php echo $assignment['return_date'] ? 'Returned' : 'Active'; ?>
                                    </span>
                                </div>
                                <div class="mb-2">
                                    <strong>Assigned Date:</strong> <?php echo date('M d, Y', strtotime($assignment['assigned_date'])); ?>
                                </div>
                                <?php if ($assignment['expected_return_date']): ?>
                                <div class="mb-2">
                                    <strong>Expected Return:</strong> <?php echo date('M d, Y', strtotime($assignment['expected_return_date'])); ?>
                                </div>
                                <?php endif; ?>
                                <?php if ($assignment['return_date']): ?>
                                <div class="mb-2">
                                    <strong>Return Date:</strong> <?php echo date('M d, Y', strtotime($assignment['return_date'])); ?>
                                </div>
                                <div class="mb-2">
                                    <strong>Return Condition:</strong> <?php echo esc($assignment['return_condition']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="mb-3">Assigned To</h5>
                                <div class="mb-2">
                                    <strong>Name:</strong> <?php echo esc($assignment['user_name']); ?>
                                </div>
                                <div class="mb-2">
                                    <strong>Email:</strong> <?php echo esc($assignment['user_email']); ?>
                                </div>
                                <div class="mb-2">
                                    <strong>Location:</strong> <?php echo esc($assignment['location_name']); ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5 class="mb-3">Assignment Details</h5>
                                <div class="mb-2">
                                    <strong>Assigned By:</strong> <?php echo esc($assignment['assigned_by_name']); ?>
                                </div>
                                <?php if ($assignment['assignment_notes']): ?>
                                <div class="mb-2">
                                    <strong>Notes:</strong> <?php echo nl2br(esc($assignment['assignment_notes'])); ?>
                                </div>
                                <?php endif; ?>
                                <?php if ($assignment['return_notes']): ?>
                                <div class="mb-2">
                                    <strong>Return Notes:</strong> <?php echo nl2br(esc($assignment['return_notes'])); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <?php include __DIR__ . '/includes/footer.php'; ?>
    <?php include __DIR__ . '/includes/footer_scripts.php'; ?>
</body>
</html>