<?php
session_start();

// Define constant to allow database.php access
define('ALLOW_ACCESS', true);

// Include database configuration
require_once 'config/database.php';

// Establish database connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Fetch all categories
$sql = "SELECT id, category_name FROM product_categories ORDER BY category_name";
$result = $conn->query($sql);

$categories = array();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = array(
            'id' => $row['id'],
            'name' => $row['category_name']
        );
    }
}

// Return categories as JSON
header('Content-Type: application/json');
echo json_encode($categories);

$conn->close();
?> 