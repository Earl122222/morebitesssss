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

// Get monthly revenue data for the last 7 months
$sql = "SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            SUM(amount) as total_sales
        FROM transactions
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute();

// Initialize arrays for chart data
$labels = array();
$data = array();

// Fill in data from query results
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $month = date('M', strtotime($row['month']));
    $labels[] = $month;
    $data[] = floatval($row['total_sales']);
}

// Prepare the response
$response = array(
    'labels' => $labels,
    'data' => $data
);

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response); 