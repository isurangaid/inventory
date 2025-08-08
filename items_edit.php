<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

// Get item ID from URL
$item_id = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;
if (!$item_id) {
    header("Location: items.php");
    exit();
}

// Fetch item details
$item = [];
$query = "SELECT * FROM items WHERE item_id = $item_id";
$result = $conn->query($query);
if ($result->num_rows === 0) {
    header("Location: items.php");
    exit();
}
$item = $result->fetch_assoc();

// Get dropdown options
$categories = $locations = $suppliers = $subcategories = [];

$result = $conn->query("SELECT * FROM category ORDER BY name");
if ($result) while ($row = $result->fetch_assoc()) $categories[] = $row;

$result = $conn->query("SELECT * FROM locations ORDER BY name");
if ($result) while ($row = $result->fetch_assoc()) $locations[] = $row;

if($item['supplier_id']!=0){
    //echo "SELECT * FROM suppliers WHERE supplier_id = '".$item['supplier_id']."'";
    $result = $conn->query("SELECT * FROM suppliers WHERE supplier_id = '".$item['supplier_id']."'");
    
    //if ($result) $row = $result->fetch_assoc() $suppliers[] = $row;
    $suppliers = mysqli_fetch_assoc($result);
} else {
    $suppliers["name"] = "";
}

// Get subcategories for the item's category
$result = $conn->query("SELECT * FROM sub_category WHERE category_id = {$item['category_id']} ORDER BY name");
if ($result) while ($row = $result->fetch_assoc()) $subcategories[] = $row;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $asset_id = $conn->real_escape_string(trim($_POST['asset_id']));
    $name = $conn->real_escape_string(trim($_POST['name']));
    $serial_number = strtoupper($conn->real_escape_string(trim($_POST['serial_number'])));
    $model = $conn->real_escape_string(trim($_POST['model']));
    $brand = $conn->real_escape_string(trim($_POST['brand']));
    $category_id = (int)$_POST['category_id'];
    $sub_category_id = (int)$_POST['sub_category_id'];
    $supplier_id = (int)$_POST['supplier_id'];
    $location_id = (int)$_POST['location_id'];
    $status = $conn->real_escape_string($_POST['status']);
    $purchase_date = $conn->real_escape_string($_POST['purchase_date']);
    $warranty_months = (int)$_POST['warranty_months'];
    $notes = $conn->real_escape_string(trim($_POST['notes']));

    // Validate asset_id uniqueness (except for current item)
    $check_query = "SELECT COUNT(*) as count FROM items WHERE asset_id = '$asset_id' AND item_id != $item_id";
    $result = $conn->query($check_query);
    if ($result && $result->fetch_assoc()['count'] > 0) {
        $errors['asset_id'] = "This Asset ID is already in use";
    }

    // Validate serial_number uniqueness (if provided)
    if (!empty($serial_number)) {
        $check_query = "SELECT COUNT(*) as count FROM items WHERE serial_number = '$serial_number' AND item_id != $item_id";
        $result = $conn->query($check_query);
        if ($result && $result->fetch_assoc()['count'] > 0) {
            $errors['serial_number'] = "This Serial Number is already in use";
        }
    }

    // Calculate warranty end date
    $warranty_end = '';
    if ($purchase_date && $warranty_months > 0) {
        $warranty_end = date('Y-m-d', strtotime($purchase_date . " + $warranty_months months"));
    }

    // If no errors, update the item
    if (empty($errors)) {
        if ($supplier_id > 0) {
            // Existing item selected
        } else {
            // New supplier – insert to DB and get new ID
            $supplier_name = $conn->real_escape_string($_POST['supplier_name']);
            $conn->query("INSERT INTO suppliers (`name`, `contact_person`, `email`, `phone`, `address`, `created_at`) VALUES 
            ('$supplier_name','','','','','".date('Y-m-d H:i:s')."')");
            $supplier_id = $conn->insert_id;
        }
        $sql = "UPDATE items SET 
                asset_id = '$asset_id',
                name = '$name',
                serial_number = " . ($serial_number ? "'$serial_number'" : "NULL") . ",
                model = " . ($model ? "'$model'" : "NULL") . ",
                brand = " . ($brand ? "'$brand'" : "NULL") . ",
                category_id = $category_id,
                sub_category_id = " . ($sub_category_id ? $sub_category_id : "NULL") . ",
                supplier_id = $supplier_id,
                location_id = $location_id,
                status = '$status',
                purchase_date = " . ($purchase_date ? "'$purchase_date'" : "NULL") . ",
                warranty_months = " . ($warranty_months ? $warranty_months : "NULL") . ",
                warranty_end = " . ($warranty_end ? "'$warranty_end'" : "NULL") . ",
                notes = " . ($notes ? "'$notes'" : "NULL") . ",
                updated_at = CURRENT_TIMESTAMP
                WHERE item_id = $item_id";

        if ($conn->query($sql)) {
            // Log the action
            $sql = "INSERT INTO audit_log (
                user_id, action, table_affected, record_id, action_details, ip_address,created_at
            ) VALUES (
                '{$_SESSION['user_id']}', 'Update', 'items', '$item_id', 
                'Updated equipment: $asset_id', '{$_SERVER['REMOTE_ADDR']}','".date('Y-m-d H:i:s')."'
            )";
            //echo $sql;
            $conn->query($sql);
            $audit_log_id = $conn->insert_id;
            // Item history tracking
            $description = "Asset ID - ".$asset_id." (".$name.") has been updated. | by ".$_SESSION['full_name'];
            $conn->query("INSERT INTO item_history (`item_id`, `user_id`, `action`, `description`,`audit_log_id`) VALUES (
                '$item_id', '{$_SESSION['user_id']}', 'Asset Updated', '$description','$audit_log_id'
            )");
            
            $_SESSION['message'] = "Equipment updated successfully";
            header("Location: items.php");
            exit();
        } else {
            $error = "Error updating equipment: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Equipment | IT Tracker</title>
    <?php include __DIR__ . '/includes/header_scripts.php'; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .subcategory-loading {
            display: none;
            color: #6c757d;
            font-style: italic;
        }
        .is-invalid {
            border-color: #dc3545;
        }
        .invalid-feedback {
            color: #dc3545;
            display: block;
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
                    <h1 class="h2">Edit Equipment</h1>
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
                                    <input type="text" class="form-control <?php echo isset($errors['asset_id']) ? 'is-invalid' : ''; ?>" 
                                        id="asset_id" name="asset_id" 
                                        value="<?php echo htmlspecialchars($item['asset_id']); ?>" >
                                    <?php if (isset($errors['asset_id'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['asset_id']; ?></div>
                                    <?php endif; ?>
                                    <small class="text-muted">Unique identifier for this equipment</small>
                                    <div id="asset_id_feedback" class="form-text text-danger" style="display:none;"></div>
                                </div>
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Equipment Name*</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                        value="<?php echo htmlspecialchars($item['name']); ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="serial_number" class="form-label">Serial Number</label>
                                    <input type="text" class="form-control <?php echo isset($errors['serial_number']) ? 'is-invalid' : ''; ?>" 
                                        id="serial_number" name="serial_number" 
                                        value="<?php echo htmlspecialchars($item['serial_number']); ?>">
                                    <?php if (isset($errors['serial_number'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['serial_number']; ?></div>
                                    <?php endif; ?>
                                    <div id="serial_number_feedback" class="form-text text-danger" style="display:none;"></div>
                                </div>
                                <div class="col-md-4">
                                    <label for="brand" class="form-label">Brand</label>
                                    <input type="text" class="form-control" id="brand" name="brand" value="<?php echo htmlspecialchars($item['brand']); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="model" class="form-label">Model</label>
                                    <input type="text" class="form-control" id="model" name="model" value="<?php echo htmlspecialchars($item['model']); ?>">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="category_id" class="form-label">Category*</label>
                                    <select class="form-select" id="category_id" name="category_id" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['category_id']; ?>" 
                                            <?php echo $category['category_id'] == $item['category_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="sub_category_id" class="form-label">Sub-Category</label>
                                    <select class="form-select" id="sub_category_id" name="sub_category_id">
                                        <option value="0">Select Sub-Category</option>
                                        <?php foreach ($subcategories as $subcat): ?>
                                        <option value="<?php echo $subcat['sub_category_id']; ?>"
                                            <?php echo $subcat['sub_category_id'] == $item['sub_category_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($subcat['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div id="subcategoryLoading" class="subcategory-loading">Loading subcategories...</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="supplier_id" class="form-label">Supplier</label>
                                    <input type="text" class="form-control" id="supplier_name" name="supplier_name" value="<?php echo $suppliers['name']; ?>">
                                    <input type="hidden" id="supplier_id" name="supplier_id" value="<?php echo $item['supplier_id']; ?>">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="location_id" class="form-label">Location*</label>
                                    <select class="form-select" id="location_id" name="location_id" required>
                                        <option value="">Select Location</option>
                                        <?php foreach ($locations as $location): ?>
                                        <option value="<?php echo $location['location_id']; ?>"
                                            <?php echo $location['location_id'] == $item['location_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($location['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="status" class="form-label">Status*</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="1"<?php echo $item['status'] == '1' ? 'selected' : ''; ?>>Working</option>
                                        <option value="2" <?php echo $item['status'] == '2' ? 'selected' : ''; ?>>Under Repair</option>
                                        <option value="3" <?php echo $item['status'] == '3' ? 'selected' : ''; ?>>Out of Service</option>
                                        <option value="3" <?php echo $item['status'] == '5' ? 'selected' : ''; ?>>Damaged</option>
                                        <option value="3" <?php echo $item['status'] == '6' ? 'selected' : ''; ?>>Lost</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="purchase_date" class="form-label">Purchase Date</label>
                                    <input type="date" class="form-control" id="purchase_date" name="purchase_date" 
                                        value="<?php echo htmlspecialchars($item['purchase_date']); ?>">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="warranty_months" class="form-label">Warranty (months)</label>
                                    <input type="number" class="form-control" id="warranty_months" name="warranty_months" 
                                        value="<?php echo htmlspecialchars($item['warranty_months']); ?>" min="0">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($item['notes']); ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">Update Equipment</button>
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
    });
    // form validation
    $(document).ready(function () {
        function validateField(fieldId, fieldName) {
            const value = $(`#${fieldId}`).val();
            if (!value) return;

            $.getJSON('validate_item.php', { field: fieldName, value: value,type: 'edit',itemid: '<?php echo $item_id; ?>' }, function (response) {
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