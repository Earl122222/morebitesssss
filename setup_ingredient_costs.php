<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header('Location: login.php');
    exit;
}

require_once('config/database.php');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create ingredient_costs table
$sql = "CREATE TABLE IF NOT EXISTS ingredient_costs (
    cost_id INT AUTO_INCREMENT PRIMARY KEY,
    ingredient_id INT NOT NULL,
    cost_price DECIMAL(10,2) NOT NULL,
    effective_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT NOT NULL,
    FOREIGN KEY (ingredient_id) REFERENCES ingredients(ingredient_id),
    FOREIGN KEY (created_by) REFERENCES pos_user(user_id)
)";

if ($conn->query($sql) === FALSE) {
    die("Error creating ingredient_costs table: " . $conn->error);
}

// Create ingredients table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS ingredients (
    ingredient_id INT AUTO_INCREMENT PRIMARY KEY,
    ingredient_name VARCHAR(100) NOT NULL,
    current_stock DECIMAL(10,2) DEFAULT 0,
    unit_measure VARCHAR(20) NOT NULL,
    min_stock_level DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === FALSE) {
    die("Error creating ingredients table: " . $conn->error);
}

echo "Tables created successfully!";
$conn->close();
?> 