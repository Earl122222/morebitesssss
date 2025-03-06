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

// Get product distribution by category
$sql = "SELECT 
            c.category_name,
            COUNT(p.product_id) as product_count,
            (COUNT(p.product_id) * 100.0 / (SELECT COUNT(*) FROM products)) as percentage
        FROM categories c
        LEFT JOIN products p ON c.category_id = p.category_id
        GROUP BY c.category_id, c.category_name
        ORDER BY product_count DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute();

// Initialize arrays for chart data
$labels = array();
$data = array();
$colors = array('#dc3545', '#0d6efd', '#198754', '#ffc107', '#6f42c1');
$colorIndex = 0;

// Fill in data from query results
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $labels[] = $row['category_name'];
    $data[] = round($row['percentage'], 1);
    $colorIndex = ($colorIndex + 1) % count($colors);
}

// Prepare the response
$response = array(
    'labels' => $labels,
    'data' => $data,
    'colors' => array_slice($colors, 0, count($labels))
);

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response); 