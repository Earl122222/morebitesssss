<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Simple query to test
$sql = "SELECT 
    i.ingredient_id,
    i.ingredient_name,
    c.category_name,
    i.quantity,
    i.unit,
    i.min_stock,
    i.last_updated
FROM ingredients i
LEFT JOIN categories c ON i.category_id = c.category_id
LIMIT 5";

$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error);
}

$data = array();
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data, JSON_PRETTY_PRINT);

$conn->close();
?> 