<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('config/database.php');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Database Check Results:</h2>";

// Check tables
$tables = ['transactions', 'transaction_items', 'pos_product'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    echo "$table exists: " . ($result->num_rows > 0 ? 'Yes' : 'No') . "\n";
    
    if ($result->num_rows > 0) {
        $columns = $conn->query("SHOW COLUMNS FROM $table");
        echo "Columns in $table:\n";
        while ($col = $columns->fetch_assoc()) {
            echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
        }
        
        // Count records
        $count = $conn->query("SELECT COUNT(*) as count FROM $table")->fetch_assoc()['count'];
        echo "Number of records: $count\n";
    }
    echo "\n";
}

// Close connection
$conn->close();
?> 