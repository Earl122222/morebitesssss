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

    // 1. Create a temporary table to store current category mappings
    $sql = "CREATE TEMPORARY TABLE temp_mappings AS
            SELECT i.ingredient_id, c.category_name
            FROM ingredients i
            LEFT JOIN categories c ON i.category_id = c.category_id";
    $conn->query($sql);

    // 2. Drop all foreign key constraints
    $sql = "ALTER TABLE ingredients DROP FOREIGN KEY ingredients_ibfk_1";
    $conn->query($sql);

    // 3. Truncate the categories table
    $sql = "TRUNCATE TABLE categories";
    $conn->query($sql);

    // 4. Insert our clean set of categories
    $default_categories = [
        ['Dry Goods', 'Non-perishable food items'],
        ['Fresh Produce', 'Fresh fruits and vegetables'],
        ['Meat', 'Fresh and processed meats'],
        ['Dairy', 'Milk and dairy products'],
        ['Beverages', 'Drinks and liquid refreshments'],
        ['Others', 'Miscellaneous items']
    ];

    $stmt = $conn->prepare("INSERT INTO categories (category_name, description) VALUES (?, ?)");
    foreach ($default_categories as $category) {
        $stmt->bind_param("ss", $category[0], $category[1]);
        $stmt->execute();
    }

    // 5. Update ingredients with new category IDs
    $sql = "UPDATE ingredients i
            LEFT JOIN temp_mappings tm ON i.ingredient_id = tm.ingredient_id
            LEFT JOIN categories c ON tm.category_name = c.category_name
            SET i.category_id = COALESCE(c.category_id, (SELECT category_id FROM categories WHERE category_name = 'Others'))";
    $conn->query($sql);

    // 6. Recreate foreign key constraint
    $sql = "ALTER TABLE ingredients
            ADD CONSTRAINT ingredients_ibfk_1 
            FOREIGN KEY (category_id) 
            REFERENCES categories(category_id)";
    $conn->query($sql);

    // 7. Drop temporary table
    $sql = "DROP TEMPORARY TABLE IF EXISTS temp_mappings";
    $conn->query($sql);

    // Commit transaction
    $conn->commit();
    
    echo "Categories have been reset successfully! All ingredients have been properly mapped to the new categories.";

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo "Error: " . $e->getMessage();
}

$conn->close();
?> 