<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

require_once 'config.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (empty($_POST['category_id'])) {
        throw new Exception('Category ID is required');
    }

    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    $category_id = intval($_POST['category_id']);

    // Check if category is being used by any ingredients
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM ingredients WHERE category_id = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        throw new Exception('Cannot delete category: It is being used by ingredients. Please reassign ingredients first.');
    }

    // Delete the category
    $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
    $stmt->bind_param("i", $category_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Error deleting category: " . $stmt->error);
    }

    if ($stmt->affected_rows === 0) {
        throw new Exception("No category found with ID: " . $category_id);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Category deleted successfully'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>
