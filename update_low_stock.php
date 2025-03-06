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

// Log received data
error_log("POST data received: " . print_r($_POST, true));

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

    // Get current quantity
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

    echo json_encode([
        'success' => true,
        'message' => 'Stock updated successfully',
        'new_quantity' => $new_quantity
    ]);

} catch (Exception $e) {
    error_log("Error in update_low_stock.php: " . $e->getMessage());
    error_log("Error trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

if (isset($conn)) {
    $conn->close();
}
?> 