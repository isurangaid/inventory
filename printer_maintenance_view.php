<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

$printer_id = isset($_GET['printer_id']) ? (int)$_GET['printer_id'] : 0;
$part_type = $conn->real_escape_string($_GET['selected']);
if (!$printer_id) {
    header("Location: printer_maintenance.php");
    exit();
}

// Get assignment details
$query = "SELECT * FROM items WHERE item_id = $printer_id";
$result = $conn->query($query);

if ($result->num_rows === 0) {
    header("Location: printer_maintenance.php");
    exit();
}

$printer = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Printer Maintenance Details | IT Tracker</title>
    <?php include __DIR__ . '/includes/header_scripts.php'; ?>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <div class="container-fluid flex-grow-1">
        <div class="row">
            <?php include __DIR__ . '/includes/sidebar.php'; ?>
    
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Printer Maintenance Details</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="printer_maintenance.php" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Printer Maintenance List
                        </a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <h5 class="mb-3">Printer Information</h5>
                                <div class="mb-2">
                                    <strong>Name:</strong> <?php echo esc($printer['name']); ?>
                                </div>
                                <div class="mb-2">
                                    <strong>Asset ID:</strong> <?php echo esc($printer['asset_id']); ?>
                                </div>
                                <div class="mb-2">
                                    <strong>Serial Number:</strong> <?php echo esc($printer['serial_number']); ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5 class="mb-3">Maintenance History</h5>
                                <div class="row">
                                    <div class="col-md-12">
                                    <label for="myDropdown" class="form-label">Equipment Selection</label>
                                    <select id="myDropdown" onchange="loadPageWithSelection()" class="form-select">
                                        <option value="all">All</option>
                                        <option value="Toner">Toner</option>
                                        <option value="Drum">Drum</option>
                                    </select>
                                    </div>
                                </div>
                                <?php 
                                    $maintenance_query = "SELECT * FROM printer_maintenance WHERE printer_id = '".$printer['item_id']."' ORDER BY service_date DESC";
                                    $maintenance_result = $conn->query($maintenance_query);
                                    //echo mysqli_num_rows($maintenance_result);
                                ?>
                                <section class="py-5">
                                    <?php 
                                        if ($maintenance_result->num_rows === 0) {
                                            "Data not available";
                                        } else {
                                            
                                    ?>
                                    <ul class="timeline">
                                        <?php while($maintenance_result_line = mysqli_fetch_assoc($maintenance_result)){ ?>
                                        <li class="timeline-item mb-5">
                                        <h6 class="fw-bold"><span class='text-muted'><?php echo $maintenance_result_line["service_date"]; ?> | </span>
                                            Service Type - 
                                            <?php 
                                                switch($maintenance_result_line["service_type"]) {
                                                    case 'Repair': echo '<span class="text-danger">Repair</span>'; break;
                                                    case 'Service': echo '<span class="text-warning">Service</span>'; break;
                                                    default: echo '<span class="text-primary">'.$maintenance_result_line["service_type"].'</span>';
                                                }
                                                $get_bills_q = $conn->query("SELECT * FROM printer_maintenance_bills WHERE maintenance_id = '".$maintenance_result_line["id"]."'");
                                                $get_bills_rows = mysqli_num_rows($get_bills_q);
                                                $counter = 1;
                                                if ($get_bills_rows > 0){
                                                    echo "<small>- Bills: ";
                                                    while($get_bills_lines = mysqli_fetch_assoc($get_bills_q)){
                                                        echo "<a href='uploads/".$get_bills_lines["file_name"]."' target='_blank'>Bill-".$counter."</a> ";
                                                        $counter++;
                                                    }
                                                } echo "</small>";
                                            ?>
                                        </h6>
                                        <small>(Count - <?php echo $maintenance_result_line["print_count"]; ?>)</small>
                                        <p class="text-muted mb-1"> <?php echo $maintenance_result_line["notes"]; ?></p>
                                            <ul>
                                                <?php 
                                                    if($part_type =="all"){
                                                        $maintenance_lines_query = "SELECT * FROM printer_maintenance_parts WHERE maintenance_id = '".$maintenance_result_line['id']."'";
                                                    } else {
                                                        $maintenance_lines_query = "SELECT * FROM printer_maintenance_parts WHERE maintenance_id = '".$maintenance_result_line['id']."' AND part_type = '".$part_type."'";
                                                    }
                                                    $maintenance_lines_result = $conn->query($maintenance_lines_query);
                                                    while($maintenance_lines = mysqli_fetch_assoc($maintenance_lines_result)){
                                                        $part_color = "";
                                                        if($maintenance_lines["part_color"] !="") {$part_color = " - ".$maintenance_lines["part_color"];} else {$part_color = "";}
                                                        echo "<li>".$maintenance_lines["part_type"].$part_color." (".$maintenance_lines["status"].")"."</li>";
                                                    }
                                                    
                                                ?>
                                            </ul>
                                        </li>
                                        <?php }} ?>
                                    </ul>
                                </section>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <?php include __DIR__ . '/includes/footer.php'; ?>
    <?php include __DIR__ . '/includes/footer_scripts.php'; ?>
    <script>
        function loadPageWithSelection() {
            var dropdown = document.getElementById("myDropdown");
            var selectedValue = dropdown.value;
            window.location.href = window.location.pathname + "?printer_id=<?php echo $printer_id?>&selected=" + selectedValue;
        }
            // Example client-side JavaScript to set selected option on load
        window.onload = function() {
            var urlParams = new URLSearchParams(window.location.search);
            var selectedValue = urlParams.get('selected');
            if (selectedValue) {
                var dropdown = document.getElementById("myDropdown");
                dropdown.value = selectedValue;
            }
        };
    </script>
</body>
</html>