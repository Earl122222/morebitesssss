<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_connect.php';

try {
    // First, let's see what we have
    $stmt = $pdo->query("SELECT user_id, user_name, user_type FROM pos_user");
    echo "Before update:\n";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    
    // Update the types one by one
    $updates = [
        ['Stockman', 10],
        ['Cashier', 11],
        ['Cashier', 12]
    ];
    
    $stmt = $pdo->prepare("UPDATE pos_user SET user_type = ? WHERE user_id = ?");
    foreach ($updates as $update) {
        $result = $stmt->execute($update);
        echo "\nUpdating user {$update[1]} to {$update[0]}: " . ($result ? "Success" : "Failed");
    }
    
    // Verify the updates
    $stmt = $pdo->query("SELECT user_id, user_name, user_type FROM pos_user");
    echo "\n\nAfter update:\n";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString();
}
?> 