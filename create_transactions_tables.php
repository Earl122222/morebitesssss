<?php
require_once('config/database.php');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create transactions table
$sql = "CREATE TABLE IF NOT EXISTS transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Transactions table created successfully<br>";
} else {
    echo "Error creating transactions table: " . $conn->error . "<br>";
}

// Create transaction_items table
$sql = "CREATE TABLE IF NOT EXISTS transaction_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Transaction items table created successfully<br>";
} else {
    echo "Error creating transaction items table: " . $conn->error . "<br>";
}

// Check if tables are empty and insert sample data if they are
$result = $conn->query("SELECT COUNT(*) as count FROM transactions");
$count = $result->fetch_assoc()['count'];

if ($count == 0) {
    // Insert sample transaction
    $sql = "INSERT INTO transactions (user_id, total) VALUES (1, 299.99)";
    if ($conn->query($sql) === TRUE) {
        $transaction_id = $conn->insert_id;
        echo "Sample transaction created<br>";
        
        // Insert sample transaction items
        $sql = "INSERT INTO transaction_items (transaction_id, product_id, quantity, price) 
                VALUES ($transaction_id, 1, 2, 149.99)";
        if ($conn->query($sql) === TRUE) {
            echo "Sample transaction items created<br>";
        } else {
            echo "Error creating sample transaction items: " . $conn->error . "<br>";
        }
    } else {
        echo "Error creating sample transaction: " . $conn->error . "<br>";
    }
}

// Show current data
echo "<h3>Current Data in Tables:</h3>";

echo "<h4>Transactions:</h4>";
$result = $conn->query("SELECT * FROM transactions");
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "ID: " . $row["transaction_id"]. " - Total: " . $row["total"]. " - Created: " . $row["created_at"]. "<br>";
    }
} else {
    echo "No transactions found<br>";
}

echo "<h4>Transaction Items:</h4>";
$result = $conn->query("SELECT * FROM transaction_items");
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "Transaction ID: " . $row["transaction_id"]. " - Product ID: " . $row["product_id"]. " - Quantity: " . $row["quantity"]. "<br>";
    }
} else {
    echo "No transaction items found<br>";
}

$conn->close();
?> 