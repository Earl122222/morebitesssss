<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Check authorization
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log received data
error_log("POST data received: " . print_r($_POST, true));

// Validate input
if (!isset($_POST['ingredient_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing ingredient ID']);
    exit;
}

$ingredient_id = intval($_POST['ingredient_id']);

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Start transaction
    $conn->begin_transaction();

    // First, get the ingredient details for logging
    $stmt = $conn->prepare("SELECT ingredient_name, quantity, unit FROM ingredients WHERE ingredient_id = ?");
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

    $ingredient = $result->fetch_assoc();
    $ingredient_name = $ingredient['ingredient_name'];
    $quantity = $ingredient['quantity'];
    $unit = $ingredient['unit'];

    // Delete the ingredient
    $delete_stmt = $conn->prepare("DELETE FROM ingredients WHERE ingredient_id = ?");
    if (!$delete_stmt) {
        throw new Exception("Prepare delete failed: " . $conn->error);
    }
    
    $delete_stmt->bind_param("i", $ingredient_id);
    if (!$delete_stmt->execute()) {
        throw new Exception("Execute delete failed: " . $delete_stmt->error);
    }

    if ($delete_stmt->affected_rows === 0) {
        throw new Exception("No ingredient was deleted");
    }

    // Log the deletion in activity_log
    $log_sql = "INSERT INTO activity_log (user_id, activity_type, item_id, item_name, quantity, unit) VALUES (?, ?, ?, ?, ?, ?)";
    $log_stmt = $conn->prepare($log_sql);
    if (!$log_stmt) {
        throw new Exception("Prepare log failed: " . $conn->error);
    }

    $user_id = $_SESSION['user_id'];
    $activity_type = 'Deleted';
    
    $log_stmt->bind_param("isisds", 
        $user_id,
        $activity_type,
        $ingredient_id,
        $ingredient_name,
        $quantity,
        $unit
    );

    if (!$log_stmt->execute()) {
        throw new Exception("Failed to log deletion: " . $log_stmt->error);
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => "Ingredient '{$ingredient_name}' deleted successfully"
    ]);

} catch (Exception $e) {
    error_log("Error in delete_ingredient.php: " . $e->getMessage());
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
