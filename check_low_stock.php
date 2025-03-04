<?php
require_once 'db_connect.php';

// Set headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Query to get ingredients where quantity is less than or equal to their threshold
    $query = "SELECT 
                ingredient_name,
                ingredient_quantity as quantity,
                threshold,
                ingredient_unit as unit 
              FROM ingredients 
              WHERE ingredient_quantity <= threshold 
              ORDER BY 
                CASE 
                    WHEN ingredient_quantity = 0 THEN 1
                    WHEN ingredient_quantity <= threshold * 0.5 THEN 2
                    ELSE 3
                END,
                ingredient_quantity/threshold ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Ensure proper encoding of special characters
    array_walk_recursive($results, function(&$item) {
        if (is_string($item)) {
            $item = htmlspecialchars_decode($item, ENT_QUOTES);
        }
    });
    
    echo json_encode($results, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

// Close connection
$pdo = null; 