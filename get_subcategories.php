<?php
require_once __DIR__ . '/includes/config.php';

header('Content-Type: application/json');

if (!isset($_GET['category_id'])) {
    echo json_encode([]);
    exit;
}

$category_id = (int)$_GET['category_id'];
$subcategories = [];

$query = "SELECT sub_category_id, name FROM sub_category 
          WHERE category_id = $category_id 
          ORDER BY name";
$result = $conn->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $subcategories[] = [
            'sub_category_id' => $row['sub_category_id'],
            'name' => htmlspecialchars($row['name'])
        ];
    }
}

echo json_encode($subcategories);