<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Check authorization
if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'Admin' && $_SESSION['user_type'] !== 'Stockman')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log the received data
error_log("Received POST data: " . print_r($_POST, true));

// Validate input
if (!isset($_POST['id']) || !isset($_POST['name']) || !isset($_POST['category_id']) || 
    !isset($_POST['quantity']) || !isset($_POST['unit']) || !isset($_POST['min_stock']) || 
    !isset($_POST['unit_cost'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Prepare the update statement
    $sql = "UPDATE ingredients SET 
        ingredient_name = ?,
        category_id = ?,
        quantity = ?,
        unit = ?,
        min_stock = ?,
        unit_cost = ?,
        last_updated = CURRENT_TIMESTAMP
        WHERE ingredient_id = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    // Bind parameters
    $stmt->bind_param("sidsidi",
        $_POST['name'],
        $_POST['category_id'],
        $_POST['quantity'],
        $_POST['unit'],
        $_POST['min_stock'],
        $_POST['unit_cost'],
        $_POST['id']
    );

    // Log the SQL and parameters
    error_log("SQL: " . $sql);
    error_log("Parameters: " . print_r([
        $_POST['name'],
        $_POST['category_id'],
        $_POST['quantity'],
        $_POST['unit'],
        $_POST['min_stock'],
        $_POST['unit_cost'],
        $_POST['id']
    ], true));

    // Execute the statement
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    if ($stmt->affected_rows > 0 || $stmt->affected_rows === 0) {
        // Log the update in activity_log
        $log_sql = "INSERT INTO activity_log (user_id, activity_type, item_id, item_name, quantity, unit, timestamp) 
            VALUES (?, 'update', ?, ?, ?, ?, CURRENT_TIMESTAMP)";
        
        $log_stmt = $conn->prepare($log_sql);
        if ($log_stmt) {
            $log_stmt->bind_param("iisds",
                $_SESSION['user_id'],
                $_POST['id'],
                $_POST['name'],
                $_POST['quantity'],
                $_POST['unit']
            );
            $log_stmt->execute();
            $log_stmt->close();
        }

        echo json_encode(['success' => true, 'message' => 'Item updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No changes made or item not found']);
    }

    $stmt->close();

} catch (Exception $e) {
    error_log("Error in update_ingredient.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error updating item: ' . $e->getMessage()]);
}

$conn->close();
?> 