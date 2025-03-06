<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'Admin' && $_SESSION['user_type'] !== 'Stockman')) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

require_once 'config.php';

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Get items where quantity is at or below minimum stock level
    $sql = "SELECT 
                i.ingredient_id,
                i.ingredient_name,
                i.quantity,
                i.unit,
                i.min_stock,
                c.category_name
            FROM ingredients i
            LEFT JOIN categories c ON i.category_id = c.category_id
            WHERE i.quantity <= i.min_stock AND i.quantity > 0
            ORDER BY i.ingredient_name";

    $result = $conn->query($sql);
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }

    $low_stock = array();
    while ($row = $result->fetch_assoc()) {
        $low_stock[] = array(
            'ingredient_id' => $row['ingredient_id'],
            'ingredient_name' => $row['ingredient_name'],
            'quantity' => $row['quantity'],
            'unit' => $row['unit'],
            'min_stock' => $row['min_stock'],
            'category_name' => $row['category_name']
        );
    }

    echo json_encode([
        'success' => true,
        'low_stock' => $low_stock
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?> 