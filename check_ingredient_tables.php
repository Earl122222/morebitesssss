<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define constant to allow access
define('ALLOW_ACCESS', true);

require_once('config/database.php');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain');

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    echo "Database Connection: OK\n\n";

    // Check tables
    $tables = ['ingredients', 'ingredient_costs'];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        echo "$table table exists: " . ($result->num_rows > 0 ? 'Yes' : 'No') . "\n";
        
        if ($result->num_rows > 0) {
            // Show table structure
            echo "\nStructure of $table:\n";
            $structure = $conn->query("DESCRIBE $table");
            while ($row = $structure->fetch_assoc()) {
                echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
            }
            
            // Count records
            $count = $conn->query("SELECT COUNT(*) as count FROM $table")->fetch_assoc()['count'];
            echo "\nNumber of records in $table: $count\n";
            
            // Show sample data
            echo "\nSample data from $table:\n";
            $data = $conn->query("SELECT * FROM $table LIMIT 3");
            while ($row = $data->fetch_assoc()) {
                print_r($row);
            }
        } else {
            echo "\nCreating $table table...\n";
            
            // Create the missing table
            if ($table === 'ingredients') {
                $sql = "CREATE TABLE IF NOT EXISTS ingredients (
                    ingredient_id INT AUTO_INCREMENT PRIMARY KEY,
                    ingredient_name VARCHAR(100) NOT NULL,
                    current_stock DECIMAL(10,2) DEFAULT 0,
                    unit_measure VARCHAR(20) NOT NULL,
                    min_stock_level DECIMAL(10,2) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )";
            } else if ($table === 'ingredient_costs') {
                $sql = "CREATE TABLE IF NOT EXISTS ingredient_costs (
                    cost_id INT AUTO_INCREMENT PRIMARY KEY,
                    ingredient_id INT NOT NULL,
                    cost_price DECIMAL(10,2) NOT NULL,
                    effective_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    created_by INT NOT NULL,
                    FOREIGN KEY (ingredient_id) REFERENCES ingredients(ingredient_id),
                    FOREIGN KEY (created_by) REFERENCES pos_user(user_id)
                )";
            }
            
            if ($conn->query($sql)) {
                echo "Table $table created successfully!\n";
            } else {
                echo "Error creating table $table: " . $conn->error . "\n";
            }
        }
        echo "\n----------------------------------------\n\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?> 