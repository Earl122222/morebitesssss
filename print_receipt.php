<?php
session_start();
define('ALLOW_ACCESS', true);
require_once('config/database.php');

// Check if user is logged in and is a cashier
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Cashier') {
    header('HTTP/1.0 403 Forbidden');
    echo 'Access Denied';
    exit;
}

// Get order ID from URL
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
if ($order_id <= 0) {
    die('Invalid order ID');
}

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Get order details
    $sql = "SELECT t.transaction_id as order_id, 
                   t.created_at, 
                   t.subtotal, 
                   t.tax, 
                   t.total, 
                   t.amount_paid, 
                   t.change_amount,
                   u.user_name as cashier_name
            FROM transactions t
            JOIN pos_user u ON t.user_id = u.user_id
            WHERE t.transaction_id = ?";
            
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param('i', $order_id);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    
    if (!$order) {
        throw new Exception("Order not found");
    }
    
    $stmt->close();

    // Get order items
    $sql = "SELECT ti.quantity, 
                   ti.price, 
                   ti.subtotal,
                   p.product_name, 
                   p.product_description
            FROM transaction_items ti
            JOIN pos_product p ON ti.product_id = p.product_id
            WHERE ti.transaction_id = ?";
            
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param('i', $order_id);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    $stmt->close();

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
} finally {
    if (isset($conn)) $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #<?php echo $order_id; ?></title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .receipt {
            max-width: 300px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .store-name {
            font-size: 1.2em;
            font-weight: bold;
        }
        .divider {
            border-top: 1px dashed #000;
            margin: 10px 0;
        }
        .item {
            margin: 5px 0;
        }
        .item-details {
            display: flex;
            justify-content: space-between;
        }
        .totals {
            margin-top: 10px;
        }
        .total-line {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9em;
        }
        .button-group {
            text-align: center;
            margin-top: 20px;
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        .btn-print {
            background-color: #28a745;
            color: white;
        }
        .btn-print:hover {
            background-color: #218838;
        }
        .btn-back {
            background-color: #007bff;
            color: white;
        }
        .btn-back:hover {
            background-color: #0056b3;
        }
        .btn-cancel {
            background-color: #dc3545;
            color: white;
        }
        .btn-cancel:hover {
            background-color: #c82333;
        }
        @media print {
            body {
                background: none;
                padding: 0;
            }
            .receipt {
                box-shadow: none;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="header">
            <div class="store-name">POS SYSTEM</div>
            <div>123 Main Street</div>
            <div>City, State 12345</div>
            <div>Tel: (123) 456-7890</div>
        </div>

        <div class="divider"></div>

        <div>
            <div>Receipt #: <?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></div>
            <div>Date: <?php echo date('Y-m-d H:i:s', strtotime($order['created_at'])); ?></div>
            <div>Cashier: <?php echo htmlspecialchars($order['cashier_name']); ?></div>
        </div>

        <div class="divider"></div>

        <div class="items">
            <?php foreach ($items as $item): ?>
            <div class="item">
                <div><?php echo htmlspecialchars($item['product_name']); ?></div>
                <div class="item-details">
                    <span><?php echo $item['quantity'] . ' x ₱' . number_format($item['price'], 2); ?></span>
                    <span>₱<?php echo number_format($item['subtotal'], 2); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="divider"></div>

        <div class="totals">
            <div class="total-line">
                <span>Subtotal:</span>
                <span>₱<?php echo number_format($order['subtotal'], 2); ?></span>
            </div>
            <div class="total-line">
                <span>Tax:</span>
                <span>₱<?php echo number_format($order['tax'], 2); ?></span>
            </div>
            <div class="total-line">
                <strong>Total:</strong>
                <strong>₱<?php echo number_format($order['total'], 2); ?></strong>
            </div>
            <div class="total-line">
                <span>Amount Paid:</span>
                <span>₱<?php echo number_format($order['amount_paid'], 2); ?></span>
            </div>
            <div class="total-line">
                <span>Change:</span>
                <span>₱<?php echo number_format($order['change_amount'], 2); ?></span>
            </div>
        </div>

        <div class="divider"></div>

        <div class="footer">
            <div>Thank you for your purchase!</div>
            <div>Please come again</div>
        </div>
    </div>

    <div class="no-print button-group">
        <button class="btn btn-print" onclick="printReceipt()">
            <i class="fas fa-print"></i> Print Receipt
        </button>
        <button class="btn btn-back" onclick="backToPos()">
            <i class="fas fa-arrow-left"></i> Back to POS
        </button>
        <button class="btn btn-cancel" onclick="cancelReceipt()">
            <i class="fas fa-times"></i> Cancel
        </button>
    </div>

    <script>
        // Function to print receipt
        function printReceipt() {
            window.print();
        }

        // Function to go back to POS page
        function backToPos() {
            window.location.href = 'pos.php';
        }

        // Function to cancel and go back to POS page
        function cancelReceipt() {
            if (confirm('Are you sure you want to cancel this receipt?')) {
                window.location.href = 'pos.php';
            }
        }

        // Auto-print on page load if not explicitly disabled
        window.onload = function() {
            if (!window.location.search.includes('noprint')) {
                window.print();
            }
        };
    </script>

    <!-- Add Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</body>
</html> 