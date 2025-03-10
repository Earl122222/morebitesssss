<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ALLOW_ACCESS', true);
require_once('config/database.php');

// Initialize connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create transactions table
$sql = "CREATE TABLE IF NOT EXISTS transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES pos_user(user_id)
)";

if ($conn->query($sql) === FALSE) {
    die("Error creating transactions table: " . $conn->error);
}

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

if ($conn->query($sql) === FALSE) {
    die("Error creating transaction_items table: " . $conn->error);
}

// Insert sample data
$sql = "INSERT INTO transactions (user_id, total) VALUES 
    ((SELECT user_id FROM pos_user WHERE user_type = 'Cashier' LIMIT 1), 150.00),
    ((SELECT user_id FROM pos_user WHERE user_type = 'Cashier' LIMIT 1), 75.50)";

if ($conn->query($sql) === TRUE) {
    $first_transaction_id = $conn->insert_id;
    
    // Insert sample transaction items
    $sql = "INSERT INTO transaction_items (transaction_id, product_id, quantity, price) VALUES 
        ($first_transaction_id, (SELECT product_id FROM pos_product LIMIT 1), 2, 75.00),
        ($first_transaction_id + 1, (SELECT product_id FROM pos_product LIMIT 1), 1, 75.50)";
    
    if ($conn->query($sql) === FALSE) {
        echo "Error inserting sample transaction items: " . $conn->error;
    }
} else {
    echo "Error inserting sample transactions: " . $conn->error;
}

echo "Tables created successfully and sample data inserted!";
$conn->close();
?> 