<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

// Fetch locations dynamically
$locations = [];
$result = $conn->query("SELECT name FROM locations ORDER BY name");
if ($result) while ($row = $result->fetch_assoc()) $locations[] = $row['name'];

$statuses = [1 => 'Active', 2 => 'Inactive (Resigned)'];

// For Edit Mode
$editMode = false;
$employee = [
    'employee_id' => '', 'employee_title' => '', 'employee_name' => '', 'designation' => '',
    'email' => '', 'working_location' => '', 'employment_status' => 1,
    'resigned_date' => '', 'resigned_type' => ''
];

if (isset($_GET['id'])) {
    $editMode = true;
    $id = (int)$_GET['id'];
    $result = $conn->query("SELECT * FROM employee WHERE id = $id");
    if ($result && $result->num_rows > 0) {
        $employee = $result->fetch_assoc();
    } else {
        $_SESSION['message'] = "Employee not found.";
        header("Location: employees.php");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = array_map([$conn, 'real_escape_string'], $_POST);
    $employment_status = (int)$data['employment_status'];

    $resigned_date = ($employment_status === 2 && !empty($data['resigned_date'])) ? "'{$data['resigned_date']}'" : "'0000-00-00'";
    $resigned_type = ($employment_status === 2) ? "'{$data['resigned_type']}'" : "''";

    if ($editMode) {
        $sql = "UPDATE employee SET
                    employee_id='{$data['employee_id']}', employee_title='{$data['employee_title']}',
                    employee_name='{$data['employee_name']}', designation='{$data['designation']}',
                    email='{$data['email']}', working_location='{$data['working_location']}',
                    employment_status=$employment_status, resigned_date=$resigned_date,
                    resigned_type=$resigned_type
                WHERE id=$id";
        $action = 'Update';
        $logMsg = "Updated employee: {$data['employee_id']}";
    } else {
        $sql = "INSERT INTO employee (
                    employee_id, employee_title, employee_name, designation, email,
                    working_location, employment_status, resigned_date, resigned_type
                ) VALUES (
                    '{$data['employee_id']}', '{$data['employee_title']}', '{$data['employee_name']}',
                    '{$data['designation']}', '{$data['email']}', '{$data['working_location']}',
                    $employment_status, $resigned_date, $resigned_type
                )";
        $action = 'Create';
        $logMsg = "Added employee: {$data['employee_id']}";
    }

    if ($conn->query($sql)) {
        $emp_id = $editMode ? $id : $conn->insert_id;

        $conn->query("INSERT INTO audit_log (user_id, action, table_affected, record_id, action_details, ip_address, created_at)
                      VALUES ('{$_SESSION['user_id']}', '$action', 'employee', '$emp_id',
                      '$logMsg', '{$_SERVER['REMOTE_ADDR']}', '".date('Y-m-d H:i:s')."')");

        $_SESSION['message'] = "Employee " . ($editMode ? "updated" : "added") . " successfully.";
        header("Location: employees.php");
        exit();
    } else {
        $error = "Error saving employee: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee | IT Tracker</title>
    <?php include __DIR__ . '/includes/header_scripts.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<div class="container-fluid flex-grow-1">
    <div class="row">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                    <h2><?php echo $editMode ? 'Edit' : 'Add'; ?> Employee</h2>
                    <a href="employees.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
                </div>
                <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
                <div class="card">
                    <div class="card-body">
                        <form method="POST">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Employee ID*</label>
                                    <input type="text" name="employee_id" class="form-control" required value="<?php echo htmlspecialchars($employee['employee_id']); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Title</label>
                                    <input type="text" name="employee_title" class="form-control" value="<?php echo htmlspecialchars($employee['employee_title']); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Name*</label>
                                    <input type="text" name="employee_name" class="form-control" required value="<?php echo htmlspecialchars($employee['employee_name']); ?>">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Designation*</label>
                                    <input type="text" name="designation" class="form-control" required value="<?php echo htmlspecialchars($employee['designation']); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($employee['email']); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Working Location*</label>
                                    <select name="working_location" class="form-select" required>
                                        <?php foreach ($locations as $loc): ?>
                                            <option value="<?php echo $loc; ?>" <?php if ($employee['working_location'] === $loc) echo 'selected'; ?>><?php echo $loc; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Employment Status*</label>
                                    <select name="employment_status" class="form-select" id="employment_status" required>
                                        <?php foreach ($statuses as $val => $label): ?>
                                            <option value="<?php echo $val; ?>" <?php if ($employee['employment_status'] == $val) echo 'selected'; ?>><?php echo $label; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 resigned-only">
                                    <label class="form-label">Resigned Date</label>
                                    <input type="date" name="resigned_date" class="form-control" value="<?php echo ($employee['resigned_date'] !== '0000-00-00') ? $employee['resigned_date'] : ''; ?>">
                                </div>
                                <div class="col-md-4 resigned-only">
                                    <label class="form-label">Resigned Type</label>
                                    <input type="text" name="resigned_type" class="form-control" value="<?php echo htmlspecialchars($employee['resigned_type']); ?>">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary"><?php echo $editMode ? 'Update' : 'Add'; ?> Employee</button>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/includes/footer_scripts.php'; ?>
<script>
    function toggleResignedFields() {
        const status = document.getElementById('employment_status').value;
        const resignedFields = document.querySelectorAll('.resigned-only');
        resignedFields.forEach(f => f.style.display = status == 2 ? 'block' : 'none');
    }
    document.getElementById('employment_status').addEventListener('change', toggleResignedFields);
    toggleResignedFields(); // on load
</script>
</body>
</html>
