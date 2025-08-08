<?php
require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $printer_id = (int) $_POST['printer_id'];
    $service_type = $conn->real_escape_string($_POST['service_type']);
    $service_date = $_POST['service_date'];
    $serviced_by = $conn->real_escape_string($_POST['serviced_by']);
    $print_count = !empty($_POST['print_count']) ? (int) $_POST['print_count'] : NULL;
    $notes = $conn->real_escape_string($_POST['notes']);

    // Insert into printer_maintenance
    $stmt = $conn->prepare("INSERT INTO printer_maintenance (printer_id, service_type, service_date, serviced_by, print_count, notes) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssis", $printer_id, $service_type, $service_date, $serviced_by, $print_count, $notes);
    $stmt->execute();
    $maintenance_id = $stmt->insert_id;
    $stmt->close();

    // Loop through parts
    if (!empty($_POST['part_type'])) {
        foreach ($_POST['part_type'] as $index => $part_type) {
            $type = $conn->real_escape_string($part_type);
            $color = $conn->real_escape_string($_POST['part_color'][$index]);
            $status = $conn->real_escape_string($_POST['status'][$index]);
            $installed_date = $_POST['installed_date'][$index];
            $part_notes = $conn->real_escape_string($_POST['part_notes'][$index]);

            $stmt_part = $conn->prepare("INSERT INTO printer_maintenance_parts (maintenance_id, part_type, part_color, status, installed_date, notes) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_part->bind_param("isssss", $maintenance_id, $type, $color, $status, $installed_date, $part_notes);
            $stmt_part->execute();
            $stmt_part->close();
        }
    }

    header("Location: printer_maintenance.php");
    exit;
} else {
    echo "Invalid request method.";
}
?>
