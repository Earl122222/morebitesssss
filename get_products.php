<?php
session_start();

// Define constant to allow database.php access
define('ALLOW_ACCESS', true);

require_once 'config/database.php';
require_once 'auth_function.php';

// Check if user is logged in and is a Cashier
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Cashier') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Get parameters
$category_id = isset($_GET['category_id']) ? $_GET['category_id'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

try {
    // Database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Base query
    $sql = "SELECT p.id, p.product_name, p.description, p.price, p.product_image, p.category_id 
            FROM pos_product p 
            WHERE p.product_status = 'Active'";

    // Add category filter if specified
    if (!empty($category_id)) {
        $sql .= " AND p.category_id = " . intval($category_id);
    }

    // Add search filter if specified
    if (!empty($search)) {
        $search = $conn->real_escape_string($search);
        $sql .= " AND (p.product_name LIKE '%$search%' OR p.description LIKE '%$search%')";
    }

    $sql .= " ORDER BY p.product_name";

    // Execute query
    $result = $conn->query($sql);
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }

    // Fetch all products
    $products = [];
    while ($row = $result->fetch_assoc()) {
        // Ensure image path is valid
        $row['product_image'] = !empty($row['product_image']) && file_exists($row['product_image']) 
            ? $row['product_image'] 
            : 'images/no-image.jpg';
        
        $products[] = $row;
    }

    // Return products as JSON
    header('Content-Type: application/json');
    echo json_encode($products);

} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
} 