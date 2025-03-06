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

    if (empty($_POST['category_name'])) {
        throw new Exception('Category name is required');
    }

    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    $category_name = trim($_POST['category_name']);
    $description = !empty($_POST['description']) ? trim($_POST['description']) : '';

    // Check if category already exists
    $stmt = $conn->prepare("SELECT category_id FROM categories WHERE category_name = ?");
    $stmt->bind_param("s", $category_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        throw new Exception('Category name already exists');
    }

    // Insert new category
    $stmt = $conn->prepare("INSERT INTO categories (category_name, description) VALUES (?, ?)");
    $stmt->bind_param("ss", $category_name, $description);
    
    if (!$stmt->execute()) {
        throw new Exception("Error adding category: " . $stmt->error);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Category added successfully',
        'category_id' => $conn->insert_id
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