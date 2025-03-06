<?php
require_once 'config.php';

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Check if table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'activity_log'");
    if ($table_check->num_rows == 0) {
        echo "Table 'activity_log' does not exist!<br>";
        
        // Create the table
        $create_sql = "CREATE TABLE IF NOT EXISTS activity_log (
            log_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            activity_type VARCHAR(50) NOT NULL,
            item_id INT NOT NULL,
            item_name VARCHAR(100) NOT NULL,
            quantity DECIMAL(10,2) NOT NULL,
            unit VARCHAR(20) NOT NULL,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if ($conn->query($create_sql)) {
            echo "Table 'activity_log' created successfully!<br>";
        } else {
            throw new Exception("Failed to create table: " . $conn->error);
        }
    } else {
        echo "Table 'activity_log' exists.<br>";
        
        // Show table structure
        $desc_result = $conn->query("DESCRIBE activity_log");
        echo "<br>Table structure:<br>";
        echo "<pre>";
        while ($row = $desc_result->fetch_assoc()) {
            print_r($row);
        }
        echo "</pre>";
        
        // Show table contents
        $content_result = $conn->query("SELECT * FROM activity_log ORDER BY timestamp DESC LIMIT 10");
        echo "<br>Latest 10 entries:<br>";
        echo "<pre>";
        while ($row = $content_result->fetch_assoc()) {
            print_r($row);
        }
        echo "</pre>";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

if (isset($conn)) {
    $conn->close();
}
?> 