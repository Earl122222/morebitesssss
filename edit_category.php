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

    if (empty($_POST['category_id']) || empty($_POST['category_name'])) {
        throw new Exception('Category ID and name are required');
    }

    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    $category_id = intval($_POST['category_id']);
    $category_name = trim($_POST['category_name']);
    $description = !empty($_POST['description']) ? trim($_POST['description']) : '';

    // Check if category exists
    $stmt = $conn->prepare("SELECT category_id FROM categories WHERE category_name = ? AND category_id != ?");
    $stmt->bind_param("si", $category_name, $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        throw new Exception('Category name already exists');
    }

    // Update category
    $stmt = $conn->prepare("UPDATE categories SET category_name = ?, description = ? WHERE category_id = ?");
    $stmt->bind_param("ssi", $category_name, $description, $category_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Error updating category: " . $stmt->error);
    }

    if ($stmt->affected_rows === 0) {
        throw new Exception("No category found with ID: " . $category_id);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Category updated successfully'
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