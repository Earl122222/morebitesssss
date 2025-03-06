<?php
require_once 'db_connect.php';
require_once 'auth_function.php';

// Check if user is logged in and is a Stockman
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Stockman') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Get time period from request
$period = isset($_GET['period']) ? $_GET['period'] : 'week';

// Get top 10 products with lowest stock relative to minimum required
$sql = "SELECT 
            product_name,
            quantity as current_stock,
            min_stock
        FROM products
        WHERE quantity <= min_stock * 2
        ORDER BY (quantity / min_stock) ASC
        LIMIT 10";

$stmt = $pdo->prepare($sql);
$stmt->execute();

// Initialize arrays for chart data
$labels = array();
$current_stock = array();
$min_stock = array();

// Fill in data from query results
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $labels[] = $row['product_name'];
    $current_stock[] = intval($row['current_stock']);
    $min_stock[] = intval($row['min_stock']);
}

// Prepare the response
$response = array(
    'labels' => $labels,
    'current_stock' => $current_stock,
    'min_stock' => $min_stock
);

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response); 