<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

$selected_item_id = "";
if(isset($_GET["item_id"])) {
    $selected_item_id = $_GET["item_id"];
}
// Get dropdown options
$available_items = $users = $locations = [];

// Get available items (status = 'Available')
$query = "SELECT item_id, asset_id, name FROM items WHERE status = '1' AND is_assigned='0' ORDER BY name";
$result = $conn->query($query);
if ($result) while ($row = $result->fetch_assoc()) $available_items[] = $row;

// Get active users
$query = "SELECT id,employee_id, employee_name FROM employee WHERE employment_status = 1 ORDER BY employee_id";
$result = $conn->query($query);
if ($result) while ($row = $result->fetch_assoc()) $users[] = $row;

// Get locations
$query = "SELECT location_id, name FROM locations ORDER BY name";
$result = $conn->query($query);
if ($result) while ($row = $result->fetch_assoc()) $locations[] = $row;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = (int)$_POST['item_id'];
    $user_id = (int)$_POST['user_id'];
    $assigned_location_id = (int)$_POST['assigned_location_id'];
    $expected_return_date = $conn->real_escape_string($_POST['expected_return_date']);
    $assignment_notes = $conn->real_escape_string($_POST['assignment_notes']);
    
    // Create assignment
    $sql = "INSERT INTO assignments (
                item_id, user_id, assigned_by, assigned_location_id,
                assigned_date, expected_return_date, assignment_notes,created_at
            ) VALUES (
                $item_id, $user_id, {$_SESSION['user_id']}, $assigned_location_id,
                '".date("Y-m-d")."', " . ($expected_return_date ? "'$expected_return_date'" : "NULL") . ", 
                " . ($assignment_notes ? "'$assignment_notes'" : "NULL") . ",'".date('Y-m-d H:i:s')."'
            )";
    
    if ($conn->query($sql)) {
        $assignment_id = $conn->insert_id;
        
        // Update item status
        $conn->query("UPDATE items SET is_assigned = '1' WHERE item_id = $item_id");
        
        // Get item data
        $item_data = "SELECT asset_id,name FROM items WHERE item_id = '$item_id'";
        $item_data_result = $conn->query($item_data);
        $item_data_result_row = mysqli_fetch_array($item_data_result);

        // Get assigned user data
        $user_data = "SELECT employee_id, employee_name FROM employee WHERE id = '$user_id'";
        //echo $user_data;
        $user_data_result = $conn->query($user_data);
        $user_data_result_row = mysqli_fetch_array($user_data_result);
        
        // Log the action
        $conn->query("INSERT INTO audit_log (
            user_id, action, table_affected, record_id, action_details, ip_address,created_at
        ) VALUES (
            '{$_SESSION['user_id']}', 'Assign', 'assignments', '$assignment_id', 
            'Asset Assigned: ".$item_data_result_row["asset_id"]." to user: ".$user_data_result_row["employee_id"]."-".$user_data_result_row["employee_name"]."', '{$_SERVER['REMOTE_ADDR']}','".date('Y-m-d H:i:s')."'
        )");
        $audit_log_id = $conn->insert_id;

        // Item history tracking
        $description = "Asset ID - ".$item_data_result_row["asset_id"]." (".$item_data_result_row["name"].") has been assigned to ".$user_data_result_row["employee_id"]."-".$user_data_result_row["employee_name"].".  | by ".$_SESSION['full_name'];
        $conn->query("INSERT INTO item_history (`item_id`, `user_id`, `action`, `description`,`audit_log_id`,`created_at`) VALUES (
            '$item_id', '{$_SESSION['user_id']}', 'Asset Assigned', '$description','$audit_log_id','".date('Y-m-d H:i:s')."'
        )");
        
        $_SESSION['message'] = "Equipment assigned successfully";
        header("Location: assignments.php");
        exit();
    } else {
        $error = "Error creating assignment: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Equipment | IT Tracker</title>
    <?php include __DIR__ . '/includes/header_scripts.php'; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
    <div class="container-fluid flex-grow-1">
        <div class="row">
            <?php include __DIR__ . '/includes/sidebar.php'; ?>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Assign Equipment</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="assignments.php" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Assignments
                        </a>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="item_id" class="form-label">Equipment*</label>
                                    <select class="form-select select" id="item_id" name="item_id" required>
                                        <option value="">Select Equipment</option>
                                        <?php 
                                            foreach ($available_items as $item): 
                                                $selected = "";
                                                if($item['item_id'] == $selected_item_id) {
                                                    $selected = " selected='selected' ";
                                                }
                                        ?>
                                        <option value="<?php echo $item['item_id']; ?>" <?php echo $selected; ?>>
                                            
                                            <?php echo htmlspecialchars($item['name']); ?> (<?php echo htmlspecialchars($item['asset_id']); $selected = ""; ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="user_id" class="form-label">Assign To*</label>
                                    <select class="form-select select" id="user_id" name="user_id" required>
                                        <option value="">Select User</option>
                                        <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>">
                                            <?php echo htmlspecialchars($user['employee_id'] ." - ".$user['employee_name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="assigned_location_id" class="form-label">Location*</label>
                                    <select class="form-select" id="assigned_location_id" name="assigned_location_id" required>
                                        <option value="">Select Location</option>
                                        <?php foreach ($locations as $location): ?>
                                        <option value="<?php echo $location['location_id']; ?>">
                                            <?php echo htmlspecialchars($location['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="expected_return_date" class="form-label">Expected Return Date</label>
                                    <input type="date" class="form-control" id="expected_return_date" name="expected_return_date">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="assignment_notes" class="form-label">Assignment Notes</label>
                                <textarea class="form-control" id="assignment_notes" name="assignment_notes" rows="3"></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">Create Assignment</button>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <?php include __DIR__ . '/includes/footer.php'; ?>
    <?php include __DIR__ . '/includes/footer_scripts.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select').select2();
        });
    // Initialize date picker
    flatpickr("#expected_return_date", {
        dateFormat: "Y-m-d",
        minDate: "today",
        allowInput: true
    });
    </script>
</body>
</html>