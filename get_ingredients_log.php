<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Check authorization
if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'Admin' && $_SESSION['user_type'] !== 'Stockman')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // First, check if the table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'activity_log'");
    if ($table_check->num_rows == 0) {
        // Table doesn't exist, create it
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
        
        if (!$conn->query($create_sql)) {
            throw new Exception("Failed to create activity_log table: " . $conn->error);
        }
    }

    // Get activity log data
    $sql = "SELECT 
        log_id,
        timestamp,
        activity_type,
        item_name,
        quantity,
        unit,
        user_id
    FROM activity_log 
    ORDER BY timestamp DESC";

    $result = $conn->query($sql);
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }

    $data = array();
    while ($row = $result->fetch_assoc()) {
        $data[] = array(
            'timestamp' => $row['timestamp'],
            'item_name' => $row['item_name'],
            'activity_type' => $row['activity_type'],
            'quantity' => floatval($row['quantity']),
            'unit' => $row['unit'],
            'user_id' => 'User ' . $row['user_id']
        );
    }

    echo json_encode([
        'data' => $data,
        'recordsTotal' => count($data),
        'recordsFiltered' => count($data)
    ]);

} catch (Exception $e) {
    error_log("Error in get_ingredients_log.php: " . $e->getMessage());
    echo json_encode([
        'error' => $e->getMessage(),
        'data' => [],
        'recordsTotal' => 0,
        'recordsFiltered' => 0
    ]);
}

if (isset($conn)) {
    $conn->close();
}
?> 