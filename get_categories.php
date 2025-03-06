<?php
require_once 'config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    $sql = "SELECT category_id, category_name, description, created_at, updated_at 
            FROM categories 
            ORDER BY category_name";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }

    $data = array();
    while ($row = $result->fetch_assoc()) {
        $data[] = array(
            'category_id' => $row['category_id'],
            'category_name' => $row['category_name'],
            'description' => $row['description'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        );
    }

    echo json_encode(array(
        'data' => $data
    ));

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'error' => $e->getMessage()
    ));
}

$conn->close();
?> 