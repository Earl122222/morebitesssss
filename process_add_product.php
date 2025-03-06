<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define constant to allow database.php access
define('ALLOW_ACCESS', true);

// Include database configuration
require_once 'config/database.php';

// Establish database connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) {
    $response = array(
        'success' => false,
        'message' => "Database connection failed: " . mysqli_connect_error()
    );
    echo json_encode($response);
    exit;
}

// Check if user is logged in and is an Admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    $response = array(
        'success' => false,
        'message' => "Unauthorized access"
    );
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = array('success' => false, 'message' => '');
    
    // Validate required fields
    if (empty($_POST['category']) || empty($_POST['productName']) || empty($_POST['price'])) {
        $response['message'] = "Please fill in all required fields";
        echo json_encode($response);
        exit;
    }
    
    // Get form data
    $category_id = mysqli_real_escape_string($conn, $_POST['category']);
    $product_name = mysqli_real_escape_string($conn, $_POST['productName']);
    $description = isset($_POST['description']) ? mysqli_real_escape_string($conn, $_POST['description']) : '';
    $price = floatval($_POST['price']);
    $status = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : 'Available';
    
    // Validate price
    if ($price <= 0) {
        $response['message'] = "Price must be greater than 0";
        echo json_encode($response);
        exit;
    }
    
    // Handle image upload
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $base_upload_dir = 'uploads/products/';  // Base upload directory
        
        // Create directory if it doesn't exist
        if (!file_exists($base_upload_dir)) {
            if (!mkdir($base_upload_dir, 0777, true)) {
                $response['message'] = "Failed to create upload directory. Error: " . error_get_last()['message'];
                echo json_encode($response);
                exit;
            }
            // Ensure proper permissions
            chmod($base_upload_dir, 0777);
        }
        
        // Get file info
        $file_name = $_FILES['image']['name'];
        $file_tmp = $_FILES['image']['tmp_name'];
        $file_type = $_FILES['image']['type'];
        
        // Clean filename and ensure proper extension
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        if (!in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            $response['message'] = "Invalid file type. Only JPG, PNG and GIF files are allowed.";
            echo json_encode($response);
            exit;
        }
        
        // Generate unique filename
        $unique_name = uniqid() . '.' . $file_extension;
        $upload_path = $base_upload_dir . $unique_name;
        $relative_path = $upload_path; // Store the relative path in database
        
        // Check if it's an image
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif');
        if (!in_array($file_type, $allowed_types)) {
            $response['message'] = "Invalid file type. Only JPG, PNG and GIF files are allowed.";
            echo json_encode($response);
            exit;
        }
        
        // Move uploaded file
        if (move_uploaded_file($file_tmp, $upload_path)) {
            $image_path = $relative_path;
            // Ensure proper permissions for the uploaded file
            chmod($upload_path, 0644);
        } else {
            $response['message'] = "Failed to upload image. Error: " . error_get_last()['message'];
            echo json_encode($response);
            exit;
        }
    }
    
    try {
        // Begin transaction
        $conn->begin_transaction();
        
        // Check if category exists
        $check_category = $conn->query("SELECT id FROM product_categories WHERE id = " . intval($category_id));
        if (!$check_category || $check_category->num_rows === 0) {
            throw new Exception("Selected category does not exist");
        }
        
        // Insert into database
        $sql = "INSERT INTO pos_product (category_id, product_name, product_description, product_price, product_status, product_image) 
                VALUES (?, ?, ?, ?, ?, ?)";
                
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("issdss", $category_id, $product_name, $description, $price, $status, $image_path);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        // Commit transaction
        $conn->commit();
        
        $_SESSION['message'] = "Product added successfully!";
        $_SESSION['message_type'] = "success";
        $response['success'] = true;
        $response['message'] = "Product added successfully!";
        
        $stmt->close();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        $_SESSION['message'] = "Error adding product: " . $e->getMessage();
        $_SESSION['message_type'] = "danger";
        $response['message'] = "Error adding product: " . $e->getMessage();
        
        // Delete uploaded image if exists
        if (!empty($image_path) && file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    echo json_encode($response);
    exit;
}

// If not POST request, redirect to add product page
header('Location: add_product.php');
exit;
?> 