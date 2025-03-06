<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');

// Connect without database selected
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS pos_system";
if ($conn->query($sql)) {
    echo "Database 'pos_system' created successfully or already exists.<br>";
} else {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db('pos_system');

// Create product categories table
$sql = "CREATE TABLE IF NOT EXISTS pos_category (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    category_status VARCHAR(50) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql)) {
    echo "Table 'pos_category' created successfully!<br>";
} else {
    echo "Error creating pos_category table: " . $conn->error . "<br>";
}

// Create products table
$sql = "CREATE TABLE IF NOT EXISTS pos_product (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    product_description TEXT,
    product_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    product_status VARCHAR(50) NOT NULL DEFAULT 'Available',
    product_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES pos_category(category_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql)) {
    echo "Table 'pos_product' created successfully!<br>";
} else {
    echo "Error creating pos_product table: " . $conn->error . "<br>";
}

$conn->close();

echo "<br>Database setup completed. You can now <a href='product_category.php'>manage product categories</a> or <a href='add_product.php'>add products</a>.";
?> 