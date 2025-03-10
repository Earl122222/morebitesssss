<?php
session_start();
define('ALLOW_ACCESS', true);
require_once('config/database.php');

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Check if order_id is provided
if (!isset($_POST['order_id']) || !is_numeric($_POST['order_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

$order_id = intval($_POST['order_id']);

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Delete order items first (due to foreign key constraint)
        $stmt = $conn->prepare("DELETE FROM transaction_items WHERE transaction_id = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param('i', $order_id);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $stmt->close();

        // Delete the order
        $stmt = $conn->prepare("DELETE FROM transactions WHERE transaction_id = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param('i', $order_id);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $stmt->close();

        // Commit transaction
        $conn->commit();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?> 