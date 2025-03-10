<?php
session_start();
define('ALLOW_ACCESS', true);
require_once 'config/database.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'php_error.log');

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Cashier') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    // Get JSON input
    $input = file_get_contents('php://input');
    error_log("Received input: " . $input);
    
    $postData = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data: ' . json_last_error_msg());
    }

    // Connect to database
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    if (empty($postData['order']) || !is_array($postData['order'])) {
        throw new Exception('Invalid order data');
    }

    $order = $postData['order'];
    $amountPaid = floatval($postData['amount_paid']);
    $userId = $_SESSION['user_id'];

    error_log("Processing order for user: " . $userId);
    error_log("Order data: " . print_r($order, true));

    // Start transaction
    $conn->begin_transaction();

    try {
        // Calculate order totals
        $subtotal = 0;
        foreach ($order as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        $tax = $subtotal * 0.12;
        $total = $subtotal + $tax;
        $change = $amountPaid - $total;

        error_log("Order totals - Subtotal: $subtotal, Tax: $tax, Total: $total, Change: $change");

        // Insert into transactions table
        $stmt = $conn->prepare("
            INSERT INTO transactions (
                user_id, 
                subtotal,
                tax,
                total,
                amount_paid,
                change_amount,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("iddddd", $userId, $subtotal, $tax, $total, $amountPaid, $change);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $transactionId = $conn->insert_id;
        error_log("Created transaction with ID: " . $transactionId);

        // Insert transaction items
        $stmt = $conn->prepare("
            INSERT INTO transaction_items (
                transaction_id,
                product_id,
                quantity,
                price,
                subtotal
            ) VALUES (?, ?, ?, ?, ?)
        ");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        foreach ($order as $item) {
            $itemSubtotal = $item['price'] * $item['quantity'];
            $productId = intval($item['id']);
            $quantity = intval($item['quantity']);
            $price = floatval($item['price']);
            
            error_log("Adding item - Product ID: $productId, Quantity: $quantity, Price: $price, Subtotal: $itemSubtotal");
            
            $stmt->bind_param("iiidd", $transactionId, $productId, $quantity, $price, $itemSubtotal);
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
        }

        // Commit transaction
        $conn->commit();
        error_log("Transaction committed successfully");

        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Order processed successfully',
            'order_id' => $transactionId,
            'total' => $total,
            'change' => $change
        ]);

    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        error_log("Transaction rolled back: " . $e->getMessage());
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error processing order: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => 'Error processing order: ' . $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
} 