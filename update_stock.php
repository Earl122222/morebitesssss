<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log received data
error_log("POST data received in update_stock.php: " . print_r($_POST, true));
error_log("Session data: " . print_r($_SESSION, true));

// Check authorization
if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'Admin' && $_SESSION['user_type'] !== 'Stockman')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Validate input
if (!isset($_POST['ingredient_id']) || !isset($_POST['quantity']) || !isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$ingredient_id = intval($_POST['ingredient_id']);
$quantity = floatval($_POST['quantity']);
$action = $_POST['action'];

if ($quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Quantity must be greater than 0']);
    exit;
}

if (!in_array($action, ['add', 'remove'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Start transaction
    $conn->begin_transaction();

    // First, ensure activity_log table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'activity_log'");
    if ($table_check->num_rows == 0) {
        $create_sql = "CREATE TABLE IF NOT EXISTS activity_log (
            log_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            activity_type VARCHAR(50) NOT NULL,
            item_id INT NOT NULL,
            item_name VARCHAR(100) NOT NULL,
            quantity DECIMAL(10,2) NOT NULL,
            unit VARCHAR(20) NOT NULL,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if (!$conn->query($create_sql)) {
            throw new Exception("Failed to create activity_log table: " . $conn->error);
        }
    }

    // Get current quantity and ingredient details
    $stmt = $conn->prepare("SELECT quantity, ingredient_name, unit FROM ingredients WHERE ingredient_id = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $ingredient_id);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception("Ingredient not found");
    }

    $row = $result->fetch_assoc();
    $current_quantity = floatval($row['quantity']);
    $ingredient_name = $row['ingredient_name'];
    $unit = $row['unit'];

    // Calculate new quantity
    $new_quantity = $action === 'add' ? 
        $current_quantity + $quantity : 
        $current_quantity - $quantity;

    // Check if removing would result in negative quantity
    if ($action === 'remove' && $new_quantity < 0) {
        throw new Exception("Cannot remove more than current stock");
    }

    // Update quantity
    $update_stmt = $conn->prepare("UPDATE ingredients SET quantity = ?, last_updated = CURRENT_TIMESTAMP WHERE ingredient_id = ?");
    if (!$update_stmt) {
        throw new Exception("Prepare update failed: " . $conn->error);
    }
    
    $update_stmt->bind_param("di", $new_quantity, $ingredient_id);
    if (!$update_stmt->execute()) {
        throw new Exception("Execute update failed: " . $update_stmt->error);
    }

    // Log the activity
    $log_sql = "INSERT INTO activity_log (user_id, activity_type, item_id, item_name, quantity, unit) VALUES (?, ?, ?, ?, ?, ?)";
    $log_stmt = $conn->prepare($log_sql);
    if (!$log_stmt) {
        error_log("Failed to prepare log statement: " . $conn->error);
        error_log("SQL: " . $log_sql);
        throw new Exception("Prepare log failed: " . $conn->error);
    }
    
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    $activity_type = $action === 'add' ? 'Stock Added' : 'Stock Removed';
    
    error_log("Logging activity with data: " . json_encode([
        'user_id' => $user_id,
        'activity_type' => $activity_type,
        'item_id' => $ingredient_id,
        'item_name' => $ingredient_name,
        'quantity' => $quantity,
        'unit' => $unit
    ]));
    
    $log_stmt->bind_param("isisds", 
        $user_id,
        $activity_type,
        $ingredient_id,
        $ingredient_name,
        $quantity,
        $unit
    );
    
    if (!$log_stmt->execute()) {
        error_log("Failed to execute log statement: " . $log_stmt->error);
        throw new Exception("Execute log failed: " . $log_stmt->error);
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Stock updated successfully',
        'new_quantity' => $new_quantity
    ]);

} catch (Exception $e) {
    error_log("Error in update_stock.php: " . $e->getMessage());
    error_log("Error trace: " . $e->getTraceAsString());
    
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