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

// Get all categories
$sql = "SELECT * FROM categories ORDER BY category_id";
$result = $conn->query($sql);

if ($result) {
    echo "Current Categories:\n";
    echo str_repeat('-', 80) . "\n";
    echo sprintf("%-5s %-20s %-40s %-15s\n", "ID", "Name", "Description", "Created At");
    echo str_repeat('-', 80) . "\n";
    
    while ($row = $result->fetch_assoc()) {
        echo sprintf("%-5s %-20s %-40s %-15s\n",
            $row['category_id'],
            $row['category_name'],
            $row['description'],
            $row['created_at']
        );
    }
} else {
    echo "Error fetching categories: " . $conn->error;
}

$conn->close();
?> 