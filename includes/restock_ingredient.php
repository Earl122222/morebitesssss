<?php
require_once '../db_connect.php';
require_once '../auth_function.php';
require_once 'ingredient_logger.php';

checkAdminLogin();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Validate input
if (!isset($_POST['ingredientId']) || !isset($_POST['amount'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$ingredientId = $_POST['ingredientId'];
$amount = floatval($_POST['amount']);

if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Amount must be greater than 0']);
    exit();
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Get current quantity and ingredient name
    $stmt = $pdo->prepare("SELECT name, quantity FROM ingredients WHERE id = ?");
    $stmt->execute([$ingredientId]);
    $ingredient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ingredient) {
        throw new Exception("Ingredient not found with ID: " . $ingredientId);
    }

    $currentQuantity = $ingredient['quantity'];
    $newQuantity = $currentQuantity + $amount;

    // Update the stock
    $query = "UPDATE ingredients SET quantity = :new_quantity WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $result = $stmt->execute([
        ':new_quantity' => $newQuantity,
        ':id' => $ingredientId
    ]);

    if (!$result) {
        throw new Exception("Failed to update ingredient quantity");
    }

    // Log the restock action
    $success = logIngredientAction(
        $pdo,
        $ingredient['name'], // source
        $currentQuantity,    // initial
        $amount,            // adjustment
        $newQuantity,       // remaining
        null,              // usage_cost
        null,              // requested
        null               // fulfilled
    );
    
    if (!$success) {
        throw new Exception("Failed to log ingredient action");
    }

    // Commit transaction
    $pdo->commit();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    error_log("Restock error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to update stock: ' . $e->getMessage()]);
} 