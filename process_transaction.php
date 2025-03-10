<?php
session_start();

// Define constant to allow database.php access
define('ALLOW_ACCESS', true);

require_once 'config/database.php';
require_once 'auth_function.php';

// Check if user is logged in and is a Cashier
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Cashier') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

try {
    // Get POST data
    $cart = json_decode($_POST['cart'], true);
    $subtotal = floatval($_POST['subtotal']);
    $tax = floatval($_POST['tax']);
    $total = floatval($_POST['total']);
    $amount_paid = floatval($_POST['amount_paid']);
    $change = floatval($_POST['change']);

    if (empty($cart)) {
        throw new Exception('Cart is empty');
    }

    // Database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert transaction record
        $sql = "INSERT INTO transactions (user_id, subtotal, tax, total, amount_paid, change_amount, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $user_id = $_SESSION['user_id'];
        $stmt->bind_param("iddddd", $user_id, $subtotal, $tax, $total, $amount_paid, $change);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $transaction_id = $conn->insert_id;
        $stmt->close();

        // Insert transaction items
        $sql = "INSERT INTO transaction_items (transaction_id, product_id, quantity, price, subtotal) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        foreach ($cart as $item) {
            $item_subtotal = $item['price'] * $item['quantity'];
            $stmt->bind_param("iidd", $transaction_id, $item['productId'], $item['quantity'], $item['price'], $item_subtotal);
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
        }

        $stmt->close();

        // Commit transaction
        $conn->commit();

        // Return success response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Transaction processed successfully',
            'transaction_id' => $transaction_id
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
} 