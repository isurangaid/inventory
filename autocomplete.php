<?php
require_once __DIR__ . '/includes/config.php';

if((isset($_GET['type'])) && ($_GET['type'] =="items")){
    $term = isset($_GET['term']) ? $conn->real_escape_string($_GET['term']) : '';
    $suggestions = [];

    if ($term !== '') {
        $query = "SELECT DISTINCT name FROM items WHERE name LIKE '%$term%' ORDER BY name LIMIT 10";
        $result = $conn->query($query);

        while ($row = $result->fetch_assoc()) {
            $suggestions[] = $row['name'];
        }
    }
    echo json_encode($suggestions);
}

if((isset($_GET['type'])) && ($_GET['type'] =="astid")){
    $term = isset($_GET['term']) ? $conn->real_escape_string($_GET['term']) : '';
    $suggestions = [];

    if ($term !== '') {
        $query = "SELECT DISTINCT asset_id FROM items WHERE asset_id LIKE '%$term%' ORDER BY asset_id DESC LIMIT 5";
        $result = $conn->query($query);

        while ($row = $result->fetch_assoc()) {
            $suggestions[] = $row['asset_id'];
        }
    }
    echo json_encode($suggestions);
}

if((isset($_GET['type'])) && ($_GET['type'] =="brand")){
    $term = isset($_GET['term']) ? $conn->real_escape_string($_GET['term']) : '';
    $suggestions = [];

    if ($term !== '') {
        $query = "SELECT DISTINCT brand FROM items WHERE brand LIKE '%$term%' ORDER BY brand LIMIT 10";
        $result = $conn->query($query);

        while ($row = $result->fetch_assoc()) {
            $suggestions[] = $row['brand'];
        }
    }
    echo json_encode($suggestions);
}

if((isset($_GET['type'])) && ($_GET['type'] =="model")){
    $term = isset($_GET['term']) ? $conn->real_escape_string($_GET['term']) : '';
    $suggestions = [];

    if ($term !== '') {
        $query = "SELECT DISTINCT model FROM items WHERE model LIKE '%$term%' ORDER BY model LIMIT 10";
        $result = $conn->query($query);

        while ($row = $result->fetch_assoc()) {
            $suggestions[] = $row['model'];
        }
    }
    echo json_encode($suggestions);
}

if((isset($_GET['type'])) && ($_GET['type'] =="supplier")){
    $term = isset($_GET['term']) ? $conn->real_escape_string($_GET['term']) : '';
    $suggestions = [];
    
    if ($term !== '') {
        $query = "SELECT DISTINCT supplier_id,name FROM suppliers WHERE name LIKE '%$term%' ORDER BY name LIMIT 10";
        $result = $conn->query($query);
    
        while ($row = $result->fetch_assoc()) {
            $suggestions[] = [
                'value' => $row['name'], // what goes into the input
                'id'    => $row['supplier_id'] // the actual ID
            ];
        }
    }
    echo json_encode($suggestions);
}
    