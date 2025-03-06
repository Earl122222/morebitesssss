<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Check authorization
if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'Admin' && $_SESSION['user_type'] !== 'Stockman')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Get low stock items
    $sql = "SELECT 
        ingredient_id as id,
        ingredient_name as name,
        quantity,
        unit,
        min_stock,
        CASE 
            WHEN quantity <= 0 THEN 'Out of Stock'
            WHEN quantity <= min_stock * 0.5 THEN 'Critical'
            ELSE 'Low'
        END as status
    FROM ingredients 
    WHERE quantity <= min_stock 
    ORDER BY 
        CASE 
            WHEN quantity <= 0 THEN 1
            WHEN quantity <= min_stock * 0.5 THEN 2
            ELSE 3
        END,
        quantity/min_stock ASC";

    $result = $conn->query($sql);
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }

    $data = array();
    while ($row = $result->fetch_assoc()) {
        $data[] = array(
            'id' => $row['id'],
            'name' => $row['name'],
            'quantity' => floatval($row['quantity']),
            'min_stock' => floatval($row['min_stock']),
            'unit' => $row['unit'],
            'status' => $row['status']
        );
    }

    echo json_encode([
        'success' => true,
        'data' => $data
    ]);

} catch (Exception $e) {
    error_log("Error in get_low_stock.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'data' => []
    ]);
}

if (isset($conn)) {
    $conn->close();
}
?> 