<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_connect.php';

try {
    // Update Stockman
    $stmt = $pdo->prepare("UPDATE pos_user SET user_type = ? WHERE user_id = ?");
    $result1 = $stmt->execute(['Stockman', 10]);
    
    // Update Cashiers
    $stmt = $pdo->prepare("UPDATE pos_user SET user_type = ? WHERE user_id IN (?, ?)");
    $result2 = $stmt->execute(['Cashier', 11, 12]);
    
    // Verify the updates
    $stmt = $pdo->query("SELECT user_id, user_name, user_type FROM pos_user WHERE user_id IN (10, 11, 12)");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Update Results:\n";
    echo "Stockman update: " . ($result1 ? "Success" : "Failed") . "\n";
    echo "Cashier update: " . ($result2 ? "Success" : "Failed") . "\n";
    echo "\nCurrent User Data:\n";
    print_r($users);
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString();
}
?> 