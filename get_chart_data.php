<?php
require_once 'config.php';

// Establish database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['error' => "Connection failed: " . $conn->connect_error]));
}

// Fetch category data
$categoryQuery = "SELECT c.category_name, COUNT(i.id) as product_count 
                 FROM categories c 
                 LEFT JOIN ingredients i ON c.category_id = i.category_id 
                 GROUP BY c.category_id";
$categoryResult = $conn->query($categoryQuery);

$categoryData = [
    'labels' => [],
    'data' => []
];

while ($row = $categoryResult->fetch_assoc()) {
    $categoryData['labels'][] = $row['category_name'];
    $categoryData['data'][] = (int)$row['product_count'];
}

// Fetch activity data
$activityQuery = "SELECT DATE(timestamp) as date, COUNT(*) as activity_count 
                 FROM activity_log 
                 WHERE timestamp >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                 GROUP BY DATE(timestamp) 
                 ORDER BY date ASC";
$activityResult = $conn->query($activityQuery);

$activityData = [
    'labels' => [],
    'data' => []
];

while ($row = $activityResult->fetch_assoc()) {
    $activityData['labels'][] = date('M d', strtotime($row['date']));
    $activityData['data'][] = (int)$row['activity_count'];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'category' => $categoryData,
    'activity' => $activityData
]); 