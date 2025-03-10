<?php
session_start();

// Define constant to allow database.php access
define('ALLOW_ACCESS', true);

require_once 'config/database.php';
require_once 'auth_function.php';

// Check if user is logged in and is a Cashier
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Cashier') {
    echo 'Unauthorized access';
    exit;
}

// Get transaction ID
$transaction_id = isset($_GET['transaction_id']) ? intval($_GET['transaction_id']) : 0;
if ($transaction_id <= 0) {
    echo 'Invalid transaction ID';
    exit;
}

try {
    // Database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Get transaction details
    $sql = "SELECT t.*, u.user_name 
            FROM transactions t 
            LEFT JOIN pos_user u ON t.user_id = u.user_id 
            WHERE t.transaction_id = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $transaction_id);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $transaction = $result->fetch_assoc();
    $stmt->close();

    if (!$transaction) {
        throw new Exception("Transaction not found");
    }

    // Get transaction items
    $sql = "SELECT ti.*, p.product_name 
            FROM transaction_items ti 
            LEFT JOIN pos_product p ON ti.product_id = p.id 
            WHERE ti.transaction_id = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $transaction_id);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $items = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Generate receipt HTML
    ?>
    <div class="receipt" style="font-family: monospace; width: 100%; max-width: 400px; margin: 0 auto;">
        <div style="text-align: center; margin-bottom: 20px;">
            <h2 style="margin: 0;">MoreBites</h2>
            <p style="margin: 5px 0;">Receipt</p>
            <p style="margin: 5px 0;">Transaction #<?php echo $transaction_id; ?></p>
            <p style="margin: 5px 0;"><?php echo date('Y-m-d H:i:s', strtotime($transaction['created_at'])); ?></p>
            <p style="margin: 5px 0;">Cashier: <?php echo htmlspecialchars($transaction['user_name']); ?></p>
        </div>

        <div style="border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 10px 0; margin-bottom: 10px;">
            <table style="width: 100%;">
                <thead>
                    <tr>
                        <th style="text-align: left;">Item</th>
                        <th style="text-align: right;">Qty</th>
                        <th style="text-align: right;">Price</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td style="text-align: left;"><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td style="text-align: right;"><?php echo $item['quantity']; ?></td>
                        <td style="text-align: right;">₱<?php echo number_format($item['price'], 2); ?></td>
                        <td style="text-align: right;">₱<?php echo number_format($item['subtotal'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="margin-bottom: 20px;">
            <table style="width: 100%;">
                <tr>
                    <td style="text-align: right;">Subtotal:</td>
                    <td style="text-align: right; padding-left: 10px;">₱<?php echo number_format($transaction['subtotal'], 2); ?></td>
                </tr>
                <tr>
                    <td style="text-align: right;">Tax (12%):</td>
                    <td style="text-align: right; padding-left: 10px;">₱<?php echo number_format($transaction['tax'], 2); ?></td>
                </tr>
                <tr>
                    <td style="text-align: right;"><strong>Total:</strong></td>
                    <td style="text-align: right; padding-left: 10px;"><strong>₱<?php echo number_format($transaction['total'], 2); ?></strong></td>
                </tr>
                <tr>
                    <td style="text-align: right;">Amount Paid:</td>
                    <td style="text-align: right; padding-left: 10px;">₱<?php echo number_format($transaction['amount_paid'], 2); ?></td>
                </tr>
                <tr>
                    <td style="text-align: right;">Change:</td>
                    <td style="text-align: right; padding-left: 10px;">₱<?php echo number_format($transaction['change_amount'], 2); ?></td>
                </tr>
            </table>
        </div>

        <div style="text-align: center; margin-top: 20px;">
            <p style="margin: 5px 0;">Thank you for your purchase!</p>
            <p style="margin: 5px 0;">Please come again</p>
        </div>
    </div>
    <?php

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
} finally {
    if (isset($conn)) {
        $conn->close();
    }
} 