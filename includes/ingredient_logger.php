<?php
if (!defined('DB_SERVER')) {
    require_once __DIR__ . '/conn.php';
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

function logIngredientAction($pdo, $source, $initial, $adjustment, $remaining, $usage_cost = null, $requested = null, $fulfilled = null) {
    try {
        if (!$pdo) {
            throw new Exception("Database connection not available");
        }

        // Get the current user ID from session
        if (!isset($_SESSION['user_id'])) {
            throw new Exception("User ID not found in session");
        }
        $user_id = $_SESSION['user_id'];

        // Debug log
        error_log("Attempting to log action: " . json_encode([
            'source' => $source,
            'user_id' => $user_id,
            'initial' => $initial,
            'adjustment' => $adjustment,
            'remaining' => $remaining,
            'usage_cost' => $usage_cost,
            'requested' => $requested,
            'fulfilled' => $fulfilled
        ]));
        
        // Prepare the SQL statement
        $sql = "INSERT INTO ingredients_log (source, user_id, initial, adjustment, remaining, usage_cost, requested, fulfilled) 
                VALUES (:source, :user_id, :initial, :adjustment, :remaining, :usage_cost, :requested, :fulfilled)";
        
        // Create a prepared statement
        $stmt = $pdo->prepare($sql);
        
        // Execute the statement with parameters
        $result = $stmt->execute([
            ':source' => $source,
            ':user_id' => $user_id,
            ':initial' => $initial,
            ':adjustment' => $adjustment,
            ':remaining' => $remaining,
            ':usage_cost' => $usage_cost,
            ':requested' => $requested,
            ':fulfilled' => $fulfilled
        ]);
        
        if (!$result) {
            throw new Exception("Failed to execute statement: " . implode(", ", $stmt->errorInfo()));
        }
        
        error_log("Successfully logged ingredient action");
        return true;
    } catch (Exception $e) {
        error_log("Error in logIngredientAction: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        return false;
    }
}
?> 