<?php
require_once __DIR__ . '/includes/config.php';

$response = ['status' => 'ok'];

if (isset($_GET['field']) && isset($_GET['value']) && isset($_GET['type']) && ($_GET['type']=='edit')) {
    $field = $_GET['field'];
    $itemid = $_GET['itemid'];
    $value = $conn->real_escape_string($_GET['value']);

    if ($field === 'asset_id') {
        $query = "SELECT COUNT(*) as count FROM items WHERE asset_id = '$value' AND item_id != '$itemid'";
    } elseif ($field === 'serial_number') {
        $query = "SELECT COUNT(*) as count FROM items WHERE serial_number = '$value' AND item_id != '$itemid'";
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid field']);
        exit;
    }

    $result = $conn->query($query);
    $row = $result->fetch_assoc();

    if ($row['count'] > 0) {
        $response = ['status' => 'exists'];
    }
}

if (isset($_GET['field']) && isset($_GET['value']) && isset($_GET['type']) && ($_GET['type']=='add')) {
    $field = $_GET['field'];
    $value = $conn->real_escape_string($_GET['value']);

    if ($field === 'asset_id') {
        $query = "SELECT COUNT(*) as count FROM items WHERE asset_id = '$value'";
    } elseif ($field === 'serial_number') {
        $query = "SELECT COUNT(*) as count FROM items WHERE serial_number = '$value'";
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid field']);
        exit;
    }

    $result = $conn->query($query);
    $row = $result->fetch_assoc();

    if ($row['count'] > 0) {
        $response = ['status' => 'exists'];
    }
}

echo json_encode($response);
