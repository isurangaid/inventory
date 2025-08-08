<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

$printers = $conn->query("SELECT item_id, asset_id, name FROM items ORDER BY name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //echo "yes";
    $printer_id = (int) $_POST['printer_id'];
    $service_type = $conn->real_escape_string($_POST['service_type']);
    $service_date = $_POST['service_date'];
    $supplier_id = $conn->real_escape_string($_POST['supplier_id']);
    $print_count = !empty($_POST['print_count']) ? (int) $_POST['print_count'] : NULL;
    $notes = $conn->real_escape_string($_POST['notes']);
    
    // Calculate warranty end date
    $warranty_end = '';
    if ($purchase_date && $warranty_months > 0) {
        $warranty_end = date('Y-m-d', strtotime($purchase_date . " + $warranty_months months"));
    }
    if ($supplier_id > 0) {
        // Existing item selected
    } else {
        // New item – insert to DB and get new ID
        $supplier_name = $conn->real_escape_string($_POST['supplier_name']);
        $conn->query("INSERT INTO suppliers (`name`, `contact_person`, `email`, `phone`, `address`, `created_at`) VALUES 
        ('$supplier_name','','','','','".date('Y-m-d H:i:s')."')");
        $supplier_id = $conn->insert_id;
    }
    $sql = "INSERT INTO printer_maintenance (
                printer_id, service_type, service_date, serviced_by, print_count, notes
            ) VALUES (
                '$printer_id', '$service_type', '$service_date', '$supplier_id', '$print_count', '$notes'
            )";
    
    if ($conn->query($sql)) {
        $printer_maintenance_id = $conn->insert_id;
        
        // Log the action
        $conn->query("INSERT INTO audit_log (
            user_id, action, table_affected, record_id, action_details, ip_address, created_at
        ) VALUES (
            '{$_SESSION['user_id']}', 'Create', 'printer_maintenance', '$printer_maintenance_id', 
            'Added new printer maintenance', '{$_SERVER['REMOTE_ADDR']}','".date('Y-m-d H:i:s')."'
        )");

        //$audit_log_id = $conn->insert_id;

        // Loop through parts
        if (!empty($_POST['part_type'])) {
            foreach ($_POST['part_type'] as $index => $part_type) {
                $type = $conn->real_escape_string($part_type);
                $color = $conn->real_escape_string($_POST['part_color'][$index]);
                $status = $conn->real_escape_string($_POST['status'][$index]);
                $installed_date = $_POST['installed_date'][$index];
                $part_notes = $conn->real_escape_string($_POST['part_notes'][$index]);
                
                $sql = "INSERT INTO printer_maintenance_parts (maintenance_id, part_type, part_color, status, installed_date, part_notes) VALUES (
                    '$printer_maintenance_id', '$type', '$color', '$status', '$installed_date', '$part_notes'
                )";
                $conn->query($sql);
                $printer_maintenance_parts_id = $conn->insert_id;
                // Log the action
                $conn->query("INSERT INTO audit_log (
                    user_id, action, table_affected, record_id, action_details, ip_address, created_at
                ) VALUES (
                    '{$_SESSION['user_id']}', 'Create', 'printer_maintenance_parts', '$printer_maintenance_parts_id', 
                    'Added new printer maintenance part', '{$_SERVER['REMOTE_ADDR']}','".date('Y-m-d H:i:s')."'
                )");
            }
        }
        
        $_SESSION['message'] = "Printer maintenance added successfully";
        header("Location: printer_maintenance.php");
        exit();
    } else {
        $error = "Error adding printer maintenance: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Equipment | IT Tracker</title>
    <?php include __DIR__ . '/includes/header_scripts.php'; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .subcategory-loading {
            display: none;
            color: #6c757d;
            font-style: italic;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <div class="container-fluid flex-grow-1">
        <div class="row">
            <?php include __DIR__ . '/includes/sidebar.php'; ?>
    
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Add Printer Maintenance</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="printer_maintenance.php" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST" id="equipmentForm">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="category_id" class="form-label">Category*</label>
                                    <select name="printer_id" id="printer_id" class="form-select" required>
                                        <option value="">-- Select Printer --</option>
                                        <?php while($printer = $printers->fetch_assoc()): ?>
                                            <option value="<?= $printer['item_id'] ?>"><?= htmlspecialchars($printer['asset_id'] . ' - ' . $printer['name']) ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="service_type" class="form-label">Service Type</label>
                                    <input type="text" class="form-control" name="service_type" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="service_date" class="form-label">Service Date</label>
                                    <input type="date" class="form-control" name="service_date" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="supplier_name" class="form-label">Serviced By (Supplier/Technician)</label>
                                    <input type="text" class="form-control" id="supplier_name" name="supplier_name">
                                    <input type="hidden" id="supplier_id" name="supplier_id">
                                </div>
                            </div> 
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="print_count" class="form-label">Current Print Count</label>
                                    <input type="number" class="form-control" name="print_count">
                                </div>
                                <div class="col-md-6">
                                    <label for="notes" class="form-label">General Notes</label>
                                    <textarea class="form-control" name="notes" rows="3"></textarea>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <h5>Parts Replaced / Serviced</h5>
                                <div id="parts-container">
                                    <div class="part-row mb-3 border p-3 rounded">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <label class="form-label">Part Type</label>
                                                <select name="part_type[]" class="form-select">
                                                    <option value="Toner">Toner</option>
                                                    <option value="Drum">Drum</option>
                                                    <option value="Tray">Tray</option>
                                                    <option value="Display">Display</option>
                                                    <option value="Fuser">Fuser</option>
                                                    <option value="Rollers">Rollers</option>
                                                    <option value="Other">Other</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Color (if applicable)</label>
                                                <select name="part_color[]" class="form-select">
                                                    <option value="">None</option>
                                                    <option value="Black">Black</option>
                                                    <option value="Cyan">Cyan</option>
                                                    <option value="Magenta">Magenta</option>
                                                    <option value="Yellow">Yellow</option>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label class="form-label">Status</label>
                                                <input type="text" name="status[]" class="form-control" value="Replaced">
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label class="form-label">Installed Date</label>
                                                <input type="date" name="installed_date[]" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Notes</label>
                                            <textarea name="part_notes[]" class="form-control" rows="2"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                                <button type="button" class="btn btn-sm btn-secondary mb-3" onclick="addPartRow()">+ Add Another Part</button>
                                <br>
                                <button type="submit" class="btn btn-primary">Save Maintenance</button>
                                <a href="printer_maintenance.php" class="btn btn-secondary">Cancel</a>
                            
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <?php include __DIR__ . '/includes/footer.php'; ?>
    <?php include __DIR__ . '/includes/footer_scripts.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        $(function() {
            $('#supplier_name').autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: 'autocomplete.php',
                        dataType: 'json',
                        data: { term: request.term,type: 'supplier' },
                        success: function(data) {
                            response(data);
                        }
                    });
                },
                minLength: 2,
                select: function(event, ui) {
                    $('#supplier_name').val(ui.item.value);      // set name
                    $('#supplier_id').val(ui.item.id);      // set item_id
                    return false;
                },
                change: function(event, ui) {
                    if (!ui.item) {
                        // user typed a new value
                        $('#supplier_id').val(''); // clear hidden item_id
                    }
                }
            });
        });
        function addPartRow() {
            const container = document.getElementById('parts-container');
            const firstRow = container.querySelector('.part-row');
            const clone = firstRow.cloneNode(true);
            clone.querySelectorAll('input, select, textarea').forEach(el => el.value = '');
            container.appendChild(clone);
        }
    </script>
</body>
</html>