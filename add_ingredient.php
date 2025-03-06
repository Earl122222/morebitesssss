<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Check authorization
if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'Admin' && $_SESSION['user_type'] !== 'Stockman')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Start transaction
    $conn->begin_transaction();

    // Get POST data
    $name = $_POST['name'] ?? '';
    $category_id = $_POST['category_id'] ?? '';
    $quantity = $_POST['quantity'] ?? '';
    $unit = $_POST['unit'] ?? '';
    $min_stock = $_POST['min_stock'] ?? '';
    $unit_cost = $_POST['unit_cost'] ?? '';

    // Validate input
    if (empty($name) || empty($category_id) || !is_numeric($quantity) || 
        empty($unit) || !is_numeric($min_stock) || !is_numeric($unit_cost)) {
        throw new Exception('All fields are required and must be valid');
    }

    // Check if ingredient already exists
    $check_stmt = $conn->prepare("SELECT COUNT(*) FROM ingredients WHERE ingredient_name = ?");
    $check_stmt->bind_param("s", $name);
    $check_stmt->execute();
    $check_stmt->bind_result($count);
    $check_stmt->fetch();
    $check_stmt->close();

    if ($count > 0) {
        throw new Exception('Ingredient already exists');
    }

    // Insert new ingredient
    $stmt = $conn->prepare("INSERT INTO ingredients (ingredient_name, category_id, quantity, unit, min_stock, unit_cost, last_updated) 
                           VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("sidsdd", 
        $name,
        $category_id,
        $quantity,
        $unit,
        $min_stock,
        $unit_cost
    );

    if (!$stmt->execute()) {
        throw new Exception("Failed to add ingredient: " . $stmt->error);
    }

    $new_id = $conn->insert_id;

    // Log the addition in activity_log
    $log_sql = "INSERT INTO activity_log (user_id, activity_type, item_id, item_name, quantity, unit) 
                VALUES (?, ?, ?, ?, ?, ?)";
    $log_stmt = $conn->prepare($log_sql);
    if (!$log_stmt) {
        throw new Exception("Failed to prepare log statement: " . $conn->error);
    }

    $activity_type = 'Added';
    $log_stmt->bind_param("isisds",
        $_SESSION['user_id'],
        $activity_type,
        $new_id,
        $name,
        $quantity,
        $unit
    );

    if (!$log_stmt->execute()) {
        throw new Exception("Failed to log addition: " . $log_stmt->error);
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Item added successfully',
        'id' => $new_id
    ]);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

if (isset($conn)) {
    $conn->close();
}
?> 