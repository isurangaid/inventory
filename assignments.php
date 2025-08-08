<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

// Handle return action
if (isset($_GET['return'])) {
    $assignment_id = (int)$_GET['return'];
    
    // Get assignment details
    $query = "SELECT * FROM assignments WHERE assignment_id = $assignment_id";
    $result = $conn->query($query);
    
    if ($result->num_rows === 1) {
        $assignment = $result->fetch_assoc();
        
        // Update assignment with return details
        $return_date = date('Y-m-d');
        $conn->query("UPDATE assignments SET 
                     return_date = '$return_date',
                     return_condition = 'Good'
                     WHERE assignment_id = $assignment_id");
        
        // Update item status
        $conn->query("UPDATE items SET 
                     status = 'Available'
                     WHERE item_id = {$assignment['item_id']}");
        
        // Log the action
        $conn->query("INSERT INTO audit_log (
            user_id, action, table_affected, record_id, action_details, ip_address
        ) VALUES (
            '{$_SESSION['user_id']}', 'Return', 'assignments', '$assignment_id', 
            'Returned equipment: {$assignment['item_id']}', '{$_SERVER['REMOTE_ADDR']}'
        )");
        
        $_SESSION['message'] = "Equipment returned successfully";
        header("Location: assignments.php");
        exit();
    }
}

// Get current assignments
$assignments = [];
$query = "SELECT a.*, 
          i.asset_id, i.name as item_name, 
          e.employee_name as employee_name,
          l.name as location_name
          FROM assignments a
          JOIN items i ON a.item_id = i.item_id
          JOIN employee e ON a.user_id = e.id
          JOIN locations l ON a.assigned_location_id = l.location_id
          WHERE a.return_date IS NULL
          ORDER BY a.assigned_date DESC";
$result = $conn->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $assignments[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment Assignments | IT Tracker</title>
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
                    <h1 class="h2">Equipment Assignments</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="assignments_add.php" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-circle"></i> New Assignment
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
                            <table id="assignmentsTable" class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Assignment ID</th>
                                        <th>Equipment</th>
                                        <th>Assigned To</th>
                                        <th>Location</th>
                                        <th>Assigned Date</th>
                                        <th>Expected Return</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assignments as $assignment): ?>
                                    <tr>
                                        <td><?php echo $assignment['assignment_id']; ?></td>
                                        <td>
                                            <?php echo esc($assignment['item_name']); ?>
                                            <small class="text-muted d-block"><?php echo esc($assignment['asset_id']); ?></small>
                                        </td>
                                        <td><?php echo esc($assignment['employee_name']); ?></td>
                                        <td><?php echo esc($assignment['location_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($assignment['assigned_date'])); ?></td>
                                        <td>
                                            <?php if ($assignment['expected_return_date']): ?>
                                                <?php echo date('M d, Y', strtotime($assignment['expected_return_date'])); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Not specified</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="assignments_view.php?assignment_id=<?php echo $assignment['assignment_id']; ?>" 
                                            class="btn btn-sm btn-info" title="View">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <button class="btn btn-sm btn-success return-btn" 
                                                    data-assignment-id="<?php echo $assignment['assignment_id']; ?>"
                                                    title="Return">
                                                <i class="bi bi-arrow-return-left"></i>
                                            </button>
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
    <div class="modal fade" id="returnModal" tabindex="-1" aria-labelledby="returnModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
            <form id="returnForm" method="POST" action="assignments_return.php">
                <input type="hidden" name="assignment_id" id="modal_assignment_id">
                <div class="modal-header">
                <h5 class="modal-title" id="returnModalLabel">Return Equipment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                <div class="mb-3">
                    <label for="return_condition" class="form-label">Condition*</label>
                    <select class="form-select" id="return_condition" name="return_condition" required>
                    <option value="1">Good (Working Condition)</option>
                    <option value="5">Damaged (Needs Repair)</option>
                    <option value="6">Lost/Missing</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="return_location_id" class="form-label">Return To Location*</label>
                    <select class="form-select" id="return_location_id" name="return_location_id" required>
                    <?php 
                    $locations = [];
                    $result = $conn->query("SELECT location_id, name FROM locations ORDER BY name");
                    if ($result) while ($row = $result->fetch_assoc()) {
                        echo '<option value="'.$row['location_id'].'">'.htmlspecialchars($row['name']).'</option>';
                    }
                    ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="return_notes" class="form-label">Return Notes</label>
                    <textarea class="form-control" id="return_notes" name="return_notes" rows="3" 
                            placeholder="Describe any issues or special instructions"></textarea>
                </div>
                </div>
                <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Confirm Return</button>
                </div>
            </form>
            </div>
        </div>
    </div>
    <?php include __DIR__ . '/includes/footer.php'; ?>
    <?php include __DIR__ . '/includes/footer_scripts.php'; ?>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        // Add this script to handle modal opening
        $(document).ready(function() {
            $('.return-btn').click(function() {
                var assignmentId = $(this).data('assignment-id');
                $('#modal_assignment_id').val(assignmentId);
                $('#returnModal').modal('show');
            });
        });
        
        $(document).ready(function() {
            $('#assignmentsTable').DataTable({
                responsive: true,
                order: [[4, 'desc']], // Sort by assigned date by default
                columnDefs: [
                    { orderable: false, targets: [6] } // Disable sorting on actions column
                ]
            });
        });
    </script>
</body>
</html>