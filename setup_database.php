<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

// Establish database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// First, create categories table
$sql = "CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(255) NOT NULL,
    description TEXT
)";

if ($conn->query($sql) === TRUE) {
    echo "Categories table created successfully<br>";
} else {
    die("Error creating categories table: " . $conn->error . "<br>");
}

// Insert sample data into categories
$sql = "INSERT INTO categories (category_name, description) 
        SELECT * FROM (
            SELECT 'Beverages', 'Drinks and liquid refreshments' UNION
            SELECT 'Dairy', 'Milk and dairy products' UNION
            SELECT 'Meat', 'Fresh and processed meats' UNION
            SELECT 'Produce', 'Fresh fruits and vegetables'
        ) AS tmp 
        WHERE NOT EXISTS (SELECT 1 FROM categories LIMIT 1)";

if ($conn->query($sql) === TRUE) {
    echo "Sample categories added successfully<br>";
} else {
    echo "Error adding sample categories: " . $conn->error . "<br>";
}

// Then create ingredients table with foreign key
$sql = "CREATE TABLE IF NOT EXISTS ingredients (
    ingredient_id INT AUTO_INCREMENT PRIMARY KEY,
    ingredient_name VARCHAR(255) NOT NULL,
    category_id INT,
    quantity DECIMAL(10,2) DEFAULT 0,
    unit VARCHAR(50),
    min_stock DECIMAL(10,2) DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
) ENGINE=InnoDB";

if ($conn->query($sql) === TRUE) {
    echo "Ingredients table created successfully<br>";
} else {
    die("Error creating ingredients table: " . $conn->error . "<br>");
}

// Insert sample data into ingredients
$sql = "INSERT INTO ingredients (ingredient_name, category_id, quantity, unit, min_stock) 
        SELECT * FROM (
            SELECT 'Coffee Beans', 1, 100, 'kg', 20 UNION
            SELECT 'Milk', 2, 50, 'liters', 10 UNION
            SELECT 'Chicken', 3, 75, 'kg', 15 UNION
            SELECT 'Tomatoes', 4, 30, 'kg', 5
        ) AS tmp 
        WHERE NOT EXISTS (SELECT 1 FROM ingredients LIMIT 1)";

if ($conn->query($sql) === TRUE) {
    echo "Sample ingredients added successfully<br>";
} else {
    echo "Error adding sample ingredients: " . $conn->error . "<br>";
}

$conn->close();
echo "Database setup completed!";
?> 