<?php
require_once 'db_connect.php';
require_once 'auth_function.php';

// Check if user is logged in and is a Cashier
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Cashier') {
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

// Get today's sales data by hour
$sql = "SELECT 
            HOUR(created_at) as hour,
            SUM(amount) as total_sales
        FROM transactions
        WHERE DATE(created_at) = CURDATE()
        GROUP BY HOUR(created_at)
        ORDER BY hour";

$stmt = $pdo->prepare($sql);
$stmt->execute();

// Initialize arrays for chart data
$labels = array();
$data = array();

// Fill in missing hours with zero sales
for ($i = 0; $i < 24; $i++) {
    $labels[] = sprintf("%02d:00", $i);
    $data[$i] = 0;
}

// Fill in actual sales data
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $hour = intval($row['hour']);
    $data[$hour] = floatval($row['total_sales']);
}

// Prepare the response
$response = array(
    'labels' => $labels,
    'data' => array_values($data)
);

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response); 