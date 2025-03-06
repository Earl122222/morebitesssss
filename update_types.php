<?php
require_once 'db_connect.php';

try {
    // Update Stockman
    $stmt = $pdo->prepare("UPDATE pos_user SET user_type = 'Stockman' WHERE user_id = 10");
    $stmt->execute();
    
    // Update Cashiers
    $stmt = $pdo->prepare("UPDATE pos_user SET user_type = 'Cashier' WHERE user_id IN (11, 12)");
    $stmt->execute();
    
    echo "User types updated successfully!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 