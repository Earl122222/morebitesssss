<?php
require_once 'db_connect.php';

function getLowStockNotifications($pdo) {
    $query = "SELECT ingredient_id, ingredient_name, ingredient_quantity, threshold 
              FROM pos_ingredients 
              WHERE ingredient_quantity <= threshold 
              ORDER BY 
                CASE 
                    WHEN ingredient_quantity = 0 THEN 1
                    WHEN ingredient_quantity <= threshold * 0.5 THEN 2
                    ELSE 3
                END,
                ingredient_quantity/threshold ASC
              LIMIT 5";
    
    try {
        $stmt = $pdo->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching notifications: " . $e->getMessage());
        return [];
    }
}

if (isset($_GET['fetch']) && $_GET['fetch'] === 'notifications') {
    // Set headers to prevent caching
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    
    try {
        $notifications = getLowStockNotifications($pdo);
        $count = count($notifications);
        
        $response = [
            'count' => $count,
            'notifications' => array_map(function($item) {
                $status = '';
                $badge = '';
                $message = '';
                
                if ($item['ingredient_quantity'] == 0) {
                    $status = 'Out of Stock';
                    $badge = 'danger';
                    $message = "The item {$item['ingredient_name']} is out of stock!";
                } elseif ($item['ingredient_quantity'] <= $item['threshold'] * 0.5) {
                    $status = 'Critical';
                    $badge = 'danger';
                    $message = "The item {$item['ingredient_name']} is critically low on stocks!";
                } else {
                    $status = 'Low Stock';
                    $badge = 'warning';
                    $message = "The item {$item['ingredient_name']} is low on stocks";
                }
                
                return [
                    'ingredient_id' => $item['ingredient_id'],
                    'ingredient_name' => $item['ingredient_name'],
                    'quantity' => $item['ingredient_quantity'],
                    'threshold' => $item['threshold'],
                    'status' => $status,
                    'badge' => $badge,
                    'message' => $message,
                    'time' => 'Just now',
                    'url' => 'low_stock.php'  // Add URL for frontend to use
                ];
            }, $notifications),
            'success' => true
        ];
    } catch (Exception $e) {
        error_log("Error processing notifications: " . $e->getMessage());
        $response = [
            'success' => false,
            'count' => 0,
            'notifications' => [],
            'error' => 'Error fetching notifications'
        ];
    }
    
    echo json_encode($response);
    exit;
}
?> 