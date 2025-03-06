<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pos_system');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Database Check Results:</h2>";

// Check if tables exist
$tables = ['categories', 'ingredients', 'pos_user'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    echo "<p>Table '$table': " . ($result->num_rows > 0 ? "EXISTS" : "MISSING") . "</p>";
    
    if ($result->num_rows > 0) {
        // Show table structure
        $structure = $conn->query("DESCRIBE $table");
        echo "<pre>Structure of $table:\n";
        while ($row = $structure->fetch_assoc()) {
            echo json_encode($row, JSON_PRETTY_PRINT) . "\n";
        }
        echo "</pre>";
        
        // Show row count
        $count = $conn->query("SELECT COUNT(*) as count FROM $table");
        $count = $count->fetch_assoc()['count'];
        echo "<p>Number of rows in $table: $count</p>";
    }
}

// Close connection
$conn->close();
?> 