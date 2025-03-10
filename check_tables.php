<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('ALLOW_ACCESS', true);
require_once('config/database.php');

// Check if user is logged in and has appropriate access
if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['Admin', 'Cashier'])) {
    die('Access Denied');
}

header('Content-Type: text/plain');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Database Connection: OK\n\n";

// Check tables
$tables = ['transactions', 'transaction_items', 'pos_product'];
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
            echo json_encode($row, JSON_PRETTY_PRINT) . "\n";
        }
    }
    echo "\n----------------------------------------\n\n";
}

$conn->close();
?> 