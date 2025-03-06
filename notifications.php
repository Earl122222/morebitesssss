<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'Admin' && $_SESSION['user_type'] !== 'Stockman')) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Get low stock items
    $sql = "SELECT 
        i.ingredient_id,
        i.ingredient_name,
        i.quantity,
        i.unit,
        i.min_stock as threshold
    FROM ingredients i
    WHERE i.quantity <= i.min_stock
    ORDER BY 
        CASE 
            WHEN i.quantity = 0 THEN 1
            WHEN i.quantity <= i.min_stock * 0.5 THEN 2
            ELSE 3
        END,
        i.quantity/i.min_stock ASC";

    $result = $conn->query($sql);
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }

    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $status = '';
        $badge = '';
        $message = '';

        if ($row['quantity'] <= 0) {
            $status = 'Out of Stock';
            $badge = 'danger';
            $message = "The item {$row['ingredient_name']} is out of stock!";
        } elseif ($row['quantity'] <= $row['threshold'] * 0.5) {
            $status = 'Critical';
            $badge = 'danger';
            $message = "The item {$row['ingredient_name']} is critically low!";
        } else {
            $status = 'Low Stock';
            $badge = 'warning';
            $message = "The item {$row['ingredient_name']} is low on stocks";
        }

        $notifications[] = [
            'ingredient_id' => $row['ingredient_id'],
            'ingredient_name' => $row['ingredient_name'],
            'quantity' => $row['quantity'],
            'unit' => $row['unit'],
            'threshold' => $row['threshold'],
            'status' => $status,
            'badge' => $badge,
            'message' => $message,
            'time' => 'Just now'
        ];
    }

    echo json_encode([
        'success' => true,
        'count' => count($notifications),
        'notifications' => $notifications
    ]);

} catch (Exception $e) {
    error_log("Error in notifications.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error fetching notifications',
        'count' => 0,
        'notifications' => []
    ]);
}

$conn->close();
?> 