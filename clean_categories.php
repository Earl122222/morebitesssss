<?php
require_once 'config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Establish database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

try {
    // Start transaction
    $conn->begin_transaction();

    // 1. Create temporary table with the categories we want to keep
    $sql = "CREATE TEMPORARY TABLE keep_categories AS
            SELECT MIN(category_id) as keep_id, category_name
            FROM categories
            GROUP BY category_name";
    $conn->query($sql);

    // 2. Update all ingredients to use the kept category IDs
    $sql = "UPDATE ingredients i
            JOIN categories c ON i.category_id = c.category_id
            JOIN keep_categories k ON c.category_name = k.category_name
            SET i.category_id = k.keep_id
            WHERE i.category_id != k.keep_id";
    $conn->query($sql);

    // 3. Delete all categories except the ones we want to keep
    $sql = "DELETE c FROM categories c
            LEFT JOIN keep_categories k ON c.category_id = k.keep_id
            WHERE k.keep_id IS NULL";
    $conn->query($sql);

    // 4. Drop the temporary table
    $sql = "DROP TEMPORARY TABLE IF EXISTS keep_categories";
    $conn->query($sql);

    // 5. Reset auto_increment to prevent gaps
    $sql = "ALTER TABLE categories AUTO_INCREMENT = 1";
    $conn->query($sql);

    // 6. Ensure we have all our default categories
    $default_categories = [
        ['Dry Goods', 'Non-perishable food items'],
        ['Fresh Produce', 'Fresh fruits and vegetables'],
        ['Meat', 'Fresh and processed meats'],
        ['Dairy', 'Milk and dairy products'],
        ['Beverages', 'Drinks and liquid refreshments'],
        ['Others', 'Miscellaneous items']
    ];

    // Insert any missing categories
    $stmt = $conn->prepare("INSERT IGNORE INTO categories (category_name, description) VALUES (?, ?)");
    foreach ($default_categories as $category) {
        $stmt->bind_param("ss", $category[0], $category[1]);
        $stmt->execute();
    }

    // 7. Update any ingredients without categories to use 'Others' category
    $sql = "UPDATE ingredients SET category_id = (
        SELECT category_id FROM categories WHERE category_name = 'Others'
    ) WHERE category_id IS NULL";
    $conn->query($sql);

    // Commit transaction
    $conn->commit();
    
    echo "Categories cleaned up successfully! Duplicates have been removed and all ingredients have been properly mapped.";

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo "Error: " . $e->getMessage();
}

$conn->close();
?> 