<?php
require_once 'config.php';

// Establish database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create categories table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === FALSE) {
    die("Error creating categories table: " . $conn->error);
}

// Default categories
$default_categories = [
    ['Dry Goods', 'Non-perishable food items and ingredients'],
    ['Fresh Produce', 'Fresh fruits, vegetables, and herbs'],
    ['Meat & Poultry', 'Fresh and frozen meat products'],
    ['Dairy & Eggs', 'Milk, cheese, butter, and egg products'],
    ['Beverages', 'Drinks and drink ingredients'],
    ['Spices & Seasonings', 'Cooking spices and seasoning ingredients'],
    ['Packaging', 'Containers, wraps, and packaging materials'],
    ['Cleaning Supplies', 'Cleaning and sanitation products'],
    ['Others', 'Miscellaneous items']
];

// Insert default categories
$stmt = $conn->prepare("INSERT IGNORE INTO categories (category_name, description) VALUES (?, ?)");

foreach ($default_categories as $category) {
    $stmt->bind_param("ss", $category[0], $category[1]);
    $stmt->execute();
}

// Modify ingredients table to ensure category_id column exists
$sql = "SHOW COLUMNS FROM ingredients LIKE 'category_id'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    // Add category_id column if it doesn't exist
    $sql = "ALTER TABLE ingredients ADD COLUMN category_id INT,
            ADD FOREIGN KEY (category_id) REFERENCES categories(category_id)";
    if ($conn->query($sql) === FALSE) {
        die("Error modifying ingredients table: " . $conn->error);
    }
}

// Update any existing ingredients without categories to use 'Others' category
$sql = "UPDATE ingredients SET category_id = (
    SELECT category_id FROM categories WHERE category_name = 'Others'
) WHERE category_id IS NULL";

if ($conn->query($sql) === FALSE) {
    die("Error updating ingredients: " . $conn->error);
}

echo "Categories have been set up successfully!";
$conn->close();
?> 