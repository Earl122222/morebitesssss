<?php
require_once 'config.php';

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Create activity_log table
    $sql = "CREATE TABLE IF NOT EXISTS activity_log (
        log_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        activity_type VARCHAR(50) NOT NULL,
        item_id INT NOT NULL,
        item_name VARCHAR(100) NOT NULL,
        quantity DECIMAL(10,2) NOT NULL,
        unit VARCHAR(20) NOT NULL,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id),
        FOREIGN KEY (item_id) REFERENCES ingredients(ingredient_id)
    )";

    if ($conn->query($sql)) {
        echo "Activity log table created successfully!";
    } else {
        throw new Exception("Error creating table: " . $conn->error);
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

if (isset($conn)) {
    $conn->close();
}
?> 