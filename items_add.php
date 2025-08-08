<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

// Get dropdown options
$categories = $locations = $suppliers = [];

$result = $conn->query("SELECT * FROM category ORDER BY name");
if ($result) while ($row = $result->fetch_assoc()) $categories[] = $row;

$result = $conn->query("SELECT * FROM locations ORDER BY name");
if ($result) while ($row = $result->fetch_assoc()) $locations[] = $row;

$result = $conn->query("SELECT * FROM suppliers ORDER BY name");
if ($result) while ($row = $result->fetch_assoc()) $suppliers[] = $row;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $asset_id = $conn->real_escape_string($_POST['asset_id']);
    $name = $conn->real_escape_string($_POST['name']);
    $serial_number = strtoupper($conn->real_escape_string($_POST['serial_number']));
    $model = $conn->real_escape_string($_POST['model']);
    $brand = $conn->real_escape_string($_POST['brand']);
    $category_id = (int)$_POST['category_id'];
    $sub_category_id = (int)$_POST['sub_category_id'];
    $supplier_id = (int)$_POST['supplier_id'];
    $location_id = (int)$_POST['location_id'];
    $status = $conn->real_escape_string($_POST['status']);
    $purchase_date = $conn->real_escape_string($_POST['purchase_date']);
    $warranty_months = (int)$_POST['warranty_months'];
    $notes = $conn->real_escape_string($_POST['notes']);
    
    // Calculate warranty end date
    $warranty_end = '';
    if ($purchase_date && $warranty_months > 0) {
        $warranty_end = date('Y-m-d', strtotime($purchase_date . " + $warranty_months months"));
    }
    if ($supplier_id > 0) {
        // Existing item selected
    } else {
        // New supplier – insert to DB and get new ID
        $supplier_name = $conn->real_escape_string($_POST['supplier_name']);
        $conn->query("INSERT INTO suppliers (`name`, `contact_person`, `email`, `phone`, `address`, `created_at`) VALUES 
        ('$supplier_name','','','','','".date('Y-m-d H:i:s')."')");
        $supplier_id = $conn->insert_id;
    }
    $sql = "INSERT INTO items (
                asset_id, name, serial_number, brand,model, 
                category_id, sub_category_id, supplier_id, location_id,
                status, purchase_date, warranty_months, warranty_end, notes,created_at
            ) VALUES (
                '$asset_id', '$name', '$serial_number', '$brand','$model',
                $category_id, $sub_category_id, $supplier_id, $location_id,
                '$status', '$purchase_date', $warranty_months, " . ($warranty_end ? "'$warranty_end'" : "NULL") . ", '$notes','".date('Y-m-d H:i:s')."'
            )";
    
    if ($conn->query($sql)) {
        $item_id = $conn->insert_id;
        
        // Log the action
        $conn->query("INSERT INTO audit_log (
            user_id, action, table_affected, record_id, action_details, ip_address, created_at
        ) VALUES (
            '{$_SESSION['user_id']}', 'Create', 'items', '$item_id', 
            'Added new equipment: $asset_id', '{$_SERVER['REMOTE_ADDR']}','".date('Y-m-d H:i:s')."'
        )");

        $audit_log_id = $conn->insert_id;
        // Item history tracking
        $description = "Asset ID - ".$asset_id." (".$name.") has been added to the system. | by ".$_SESSION['full_name'];
        $conn->query("INSERT INTO item_history (`item_id`, `user_id`, `action`, `description`,`audit_log_id`) VALUES (
            '$item_id', '{$_SESSION['user_id']}', 'New asset add', '$description','$audit_log_id'
        )");
        
        $_SESSION['message'] = "Equipment added successfully";
        header("Location: items.php");
        exit();
    } else {
        $error = "Error adding equipment: " . $conn->error;
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
    <script src="assets/js/html5-qrcode.min.js"></script>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <div class="container-fluid flex-grow-1">
        <div class="row">
            <?php include __DIR__ . '/includes/sidebar.php'; ?>
    
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Add New Equipment</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="items.php" class="btn btn-sm btn-outline-secondary">
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
                                    <label for="asset_id" class="form-label">Asset ID*</label>
                                    <input type="text" class="form-control" id="asset_id" name="asset_id" required>
                                    <div id="asset_id_feedback" class="form-text text-danger" style="display:none;"></div>
                                    <small class="text-muted">Unique identifier for this equipment</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Equipment Name*</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="serial_number" class="form-label">Serial Number</label>
                                    <!-- <input type="text" class="form-control" id="serial_number" name="serial_number"> -->
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="serial_number" name="serial_number" placeholder="Scan or type serial number">
                                        <button type="button" class="btn btn-outline-secondary" id="scanSerialBtn" title="Scan Barcode">
                                            <i class="bi bi-upc-scan"></i>
                                        </button>
                                    </div>
                                    <div id="scanner" class="mt-2" style="display: none;"></div>
                                    <div id="serial_number_feedback" class="form-text text-danger" style="display:none;"></div>
                                </div>
                                <div class="col-md-4">
                                    <label for="brand" class="form-label">Brand</label>
                                    <input type="text" class="form-control" id="brand" name="brand">
                                </div>
                                <div class="col-md-4">
                                    <label for="model" class="form-label">Model</label>
                                    <input type="text" class="form-control" id="model" name="model">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="category_id" class="form-label">Category*</label>
                                    <select class="form-select" id="category_id" name="category_id" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['category_id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="sub_category_id" class="form-label">Sub-Category</label>
                                    <select class="form-select" id="sub_category_id" name="sub_category_id">
                                        <option value="0">Select Category First</option>
                                    </select>
                                    <div id="subcategoryLoading" class="subcategory-loading">Loading subcategories...</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="supplier_name" class="form-label">Supplier</label>
                                    <input type="text" class="form-control" id="supplier_name" name="supplier_name">
                                    <input type="hidden" id="supplier_id" name="supplier_id">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="location_id" class="form-label">Location*</label>
                                    <select class="form-select" id="location_id" name="location_id" required>
                                        <option value="">Select Location</option>
                                        <?php foreach ($locations as $location): ?>
                                        <option value="<?php echo $location['location_id']; ?>">
                                            <?php echo htmlspecialchars($location['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="status" class="form-label">Status*</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="1">Working</option>
                                        <option value="2">Under Repair</option>
                                        <option value="3">Out of Service</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="purchase_date" class="form-label">Purchase Date</label>
                                    <input type="date" class="form-control" id="purchase_date" name="purchase_date">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="warranty_months" class="form-label">Warranty (months)</label>
                                    <input type="number" class="form-control" id="warranty_months" name="warranty_months" min="0">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">Save Equipment</button>
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
        $('#asset_id').autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: 'autocomplete.php',
                    dataType: 'json',
                    data: { term: request.term,type: 'astid' },
                    success: function(data) {
                        response(data);
                    }
                });
            },
            minLength: 2,
            select: function(event, ui) {
                $('#asset_id').val(ui.item.value);
                return false;
            }
        });
        $('#name').autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: 'autocomplete.php',
                    dataType: 'json',
                    data: { term: request.term,type: 'items' },
                    success: function(data) {
                        response(data);
                    }
                });
            },
            minLength: 2,
            select: function(event, ui) {
                $('#name').val(ui.item.value);
                return false;
            }
        });
        $('#brand').autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: 'autocomplete.php',
                    dataType: 'json',
                    data: { term: request.term,type: 'brand' },
                    success: function(data) {
                        response(data);
                    }
                });
            },
            minLength: 2,
            select: function(event, ui) {
                $('#brand').val(ui.item.value);
                return false;
            }
        });
        $('#model').autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: 'autocomplete.php',
                    dataType: 'json',
                    data: { term: request.term,type: 'model' },
                    success: function(data) {
                        response(data);
                    }
                });
            },
            minLength: 2,
            select: function(event, ui) {
                $('#model').val(ui.item.value);
                return false;
            }
        });
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
    // Initialize date picker
    flatpickr("#purchase_date", {
        dateFormat: "Y-m-d",
        allowInput: true
    });

    // AJAX-based sub-category loading
    document.addEventListener('DOMContentLoaded', function() {
        const categorySelect = document.getElementById('category_id');
        const subcategorySelect = document.getElementById('sub_category_id');
        const loadingIndicator = document.getElementById('subcategoryLoading');
        
        categorySelect.addEventListener('change', function() {
            const categoryId = this.value;
            
            // Reset subcategory dropdown
            subcategorySelect.innerHTML = '<option value="0">Loading sub-categories...</option>';
            subcategorySelect.disabled = true;
            loadingIndicator.style.display = 'block';
            
            if (!categoryId) {
                subcategorySelect.innerHTML = '<option value="0">Select a category first</option>';
                subcategorySelect.disabled = false;
                loadingIndicator.style.display = 'none';
                return;
            }
            
            // Make AJAX request to get sub-categories
            fetch('get_subcategories.php?category_id=' + categoryId)
                .then(response => response.json())
                .then(data => {
                    subcategorySelect.innerHTML = '<option value="0">Select Sub-Category</option>';
                    
                    if (data.length > 0) {
                        data.forEach(subcat => {
                            const option = new Option(subcat.name, subcat.sub_category_id);
                            subcategorySelect.add(option);
                        });
                    } else {
                        subcategorySelect.innerHTML = '<option value="0">No sub-categories found</option>';
                    }
                    
                    subcategorySelect.disabled = false;
                    loadingIndicator.style.display = 'none';
                })
                .catch(error => {
                    console.error('Error loading sub-categories:', error);
                    subcategorySelect.innerHTML = '<option value="0">Error loading sub-categories</option>';
                    subcategorySelect.disabled = false;
                    loadingIndicator.style.display = 'none';
                });
        });
        
        // Trigger change if category already selected (after form validation error)
        if (categorySelect.value) {
            categorySelect.dispatchEvent(new Event('change'));
        }
    });

    // scanner 
    let html5QrScannerInstance = null;

    document.getElementById('scanSerialBtn').addEventListener('click', function () {
        const scannerDiv = document.getElementById("scanner");

        if (scannerDiv.style.display === "none") {
            scannerDiv.style.display = "block";

            html5QrScannerInstance = new Html5Qrcode("scanner");
            html5QrScannerInstance.start(
                { facingMode: "environment" },
                { fps: 10, qrbox: 250 },
                (decodedText) => {
                    document.getElementById("serial_number").value = decodedText;
                    html5QrScannerInstance.stop().then(() => {
                        scannerDiv.innerHTML = ""; // clear camera view
                        scannerDiv.style.display = "none";
                    });
                },
                (error) => {
                    // optional: console.log(`Scan error: ${error}`);
                }
            ).catch(err => {
                console.error("Camera start error:", err);
                scannerDiv.style.display = "none";
            });
        } else {
            html5QrScannerInstance.stop().then(() => {
                scannerDiv.innerHTML = "";
                scannerDiv.style.display = "none";
            });
        }
    });
    
    // form validation
    $(document).ready(function () {
        function validateField(fieldId, fieldName) {
            const value = $(`#${fieldId}`).val();
            if (!value) return;

            $.getJSON('validate_item.php', { field: fieldName, value: value, type: 'add' }, function (response) {
                const feedback = $(`#${fieldId}_feedback`);
                if (response.status === 'exists') {
                    feedback.text(`${fieldName.replace('_', ' ')} already exists`).addClass('text-danger').show();
                    $(`#${fieldId}`).addClass('is-invalid');
                } else {
                    feedback.text('').removeClass('text-danger').hide();
                    $(`#${fieldId}`).removeClass('is-invalid');
                }
            });
        }

        $('#asset_id').on('blur', function () {
            validateField('asset_id', 'asset_id');
        });

        $('#serial_number').on('blur', function () {
            validateField('serial_number', 'serial_number');
        });

        // Optional: prevent form submission if any fields are invalid
        /*$('#equipmentForm').on('submit', function (e) {
            if ($('.is-invalid').length > 0) {
                alert("Please fix validation errors before submitting.");
                e.preventDefault();
            }
        });*/
    });

    </script>
</body>
</html>