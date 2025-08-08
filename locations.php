<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

// Handle insert or update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $location_id = isset($_POST['location_id']) ? (int)$_POST['location_id'] : 0;
    $name = $conn->real_escape_string($_POST['name']);
    $description = $conn->real_escape_string($_POST['description']);
    $is_project_office = isset($_POST['is_project_office']) ? 1 : 0;

    if ($location_id > 0) {
        $sql = "UPDATE locations SET name='$name', description='$description', is_project_office=$is_project_office WHERE location_id=$location_id";
        $action = 'Update';
        $message = 'Location updated successfully';
    } else {
        $sql = "INSERT INTO locations (name, description, is_project_office, created_at) VALUES ('$name', '$description', $is_project_office, NOW())";
        $action = 'Create';
        $message = 'Location added successfully';
    }

    if ($conn->query($sql)) {
        $loc_id = ($location_id > 0) ? $location_id : $conn->insert_id;
        $conn->query("INSERT INTO audit_log (user_id, action, table_affected, record_id, action_details, ip_address, created_at)
                      VALUES ('{$_SESSION['user_id']}', '$action', 'locations', '$loc_id', 'Location $action: $name', '{$_SERVER['REMOTE_ADDR']}', NOW())");
        $_SESSION['message'] = $message;
        header("Location: locations.php");
        exit();
    } else {
        $error = "Database error: " . $conn->error;
    }
}

// Handle delete
if (isset($_GET['delete']) && is_admin()) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM locations WHERE location_id = $id");
    $_SESSION['message'] = "Location deleted successfully";
    header("Location: locations.php");
    exit();
}

// Fetch all locations
$locations = [];
$result = $conn->query("SELECT * FROM locations ORDER BY name");
if ($result) while ($row = $result->fetch_assoc()) $locations[] = $row;

// Edit mode setup
$edit_location = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $res = $conn->query("SELECT * FROM locations WHERE location_id = $id");
    if ($res && $res->num_rows > 0) {
        $edit_location = $res->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Locations | IT Tracker</title>
    <?php include __DIR__ . '/includes/header_scripts.php'; ?>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<div class="container-fluid flex-grow-1">
    <div class="row">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                <h2>Manage Locations</h2>
            </div>

            <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

            <div class="card mb-4">
                <div class="card-header">
                    <?php echo $edit_location ? 'Edit Location' : 'Add New Location'; ?>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="location_id" value="<?php echo $edit_location['location_id'] ?? ''; ?>">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Location Name*</label>
                                <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($edit_location['name'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Project Office?</label>
                                <div class="form-check">
                                    <input type="checkbox" name="is_project_office" class="form-check-input" value="1" <?php echo (isset($edit_location['is_project_office']) && $edit_location['is_project_office']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label">Yes</label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"><?php echo htmlspecialchars($edit_location['description'] ?? ''); ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Save</button>
                        <?php if ($edit_location): ?>
                        <a href="locations.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Project Office</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($locations as $loc): ?>
                            <tr>
                                <td><?php echo $loc['location_id']; ?></td>
                                <td><?php echo htmlspecialchars($loc['name']); ?></td>
                                <td><?php echo htmlspecialchars($loc['description']); ?></td>
                                <td><span class="badge bg-<?php echo $loc['is_project_office'] ? 'info' : 'secondary'; ?>"><?php echo $loc['is_project_office'] ? 'Yes' : 'No'; ?></span></td>
                                <td><?php echo $loc['created_at']; ?></td>
                                <td>
                                    <a href="locations.php?edit=<?php echo $loc['location_id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <?php if (is_admin()): ?>
                                    <a href="locations.php?delete=<?php echo $loc['location_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/includes/footer_scripts.php'; ?>
</body>
</html>
