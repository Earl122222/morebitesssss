<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('ALLOW_ACCESS', true);
require_once('config/database.php');

// Check if user is logged in and has Admin access
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    die('Access Denied. Only administrators can run this script.');
}

// Initialize connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Enable error reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Start transaction
    $conn->begin_transaction();

    // Create transactions table
    $sql = "CREATE TABLE IF NOT EXISTS transactions (
        transaction_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        total DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES pos_user(user_id)
    )";
    $conn->query($sql);
    echo "Transactions table created successfully<br>";

    // Create transaction_items table
    $sql = "CREATE TABLE IF NOT EXISTS transaction_items (
        item_id INT AUTO_INCREMENT PRIMARY KEY,
        transaction_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id),
        FOREIGN KEY (product_id) REFERENCES pos_product(product_id)
    )";
    $conn->query($sql);
    echo "Transaction items table created successfully<br>";

    // Check if tables are empty and insert sample data
    $result = $conn->query("SELECT COUNT(*) as count FROM transactions");
    $count = $result->fetch_assoc()['count'];

    if ($count == 0) {
        // Get the current user's ID
        $user_id = $_SESSION['user_id'];
        
        // Get some products from pos_product table
        $products = $conn->query("SELECT product_id, product_price FROM pos_product LIMIT 2");
        if ($products->num_rows > 0) {
            // Create a sample transaction
            $total = 0;
            $product_data = [];
            
            while ($product = $products->fetch_assoc()) {
                $quantity = rand(1, 3);
                $price = $product['product_price'];
                $total += $price * $quantity;
                $product_data[] = [
                    'product_id' => $product['product_id'],
                    'quantity' => $quantity,
                    'price' => $price
                ];
            }

            // Insert transaction
            $stmt = $conn->prepare("INSERT INTO transactions (user_id, total) VALUES (?, ?)");
            $stmt->bind_param("id", $user_id, $total);
            $stmt->execute();
            $transaction_id = $conn->insert_id;
            echo "Sample transaction created with ID: $transaction_id<br>";

            // Insert transaction items
            $stmt = $conn->prepare("INSERT INTO transaction_items (transaction_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            foreach ($product_data as $item) {
                $stmt->bind_param("iiid", $transaction_id, $item['product_id'], $item['quantity'], $item['price']);
                $stmt->execute();
            }
            echo "Sample transaction items created<br>";
        } else {
            echo "Warning: No products found in pos_product table. Please add some products first.<br>";
        }
    }

    // Commit transaction
    $conn->commit();
    echo "<br>Database setup completed successfully!<br>";

    // Show current data
    echo "<h3>Current Data in Tables:</h3>";

    echo "<h4>Transactions:</h4>";
    $result = $conn->query("SELECT t.*, u.user_name FROM transactions t JOIN pos_user u ON t.user_id = u.user_id");
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo "ID: " . $row["transaction_id"]. 
                 " - User: " . $row["user_name"]. 
                 " - Total: ₱" . number_format($row["total"], 2). 
                 " - Created: " . $row["created_at"]. "<br>";
        }
    } else {
        echo "No transactions found<br>";
    }

    echo "<h4>Transaction Items:</h4>";
    $result = $conn->query("SELECT ti.*, p.product_name FROM transaction_items ti JOIN pos_product p ON ti.product_id = p.product_id");
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo "Transaction ID: " . $row["transaction_id"]. 
                 " - Product: " . $row["product_name"]. 
                 " - Quantity: " . $row["quantity"].
                 " - Price: ₱" . number_format($row["price"], 2). "<br>";
        }
    } else {
        echo "No transaction items found<br>";
    }

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
} finally {
    $conn->close();
}
?> 