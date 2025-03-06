<?php
// Enable error logging to file
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Prevent any output before headers
ob_start();

// Database configuration - update these with your actual values
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pos_system');

// For debugging
error_log("Starting dashboard stats generation");

try {
    // Start session and set headers
    session_start();
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');

    // Check authentication
    if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'Admin' && $_SESSION['user_type'] !== 'Stockman')) {
        throw new Exception('Unauthorized access');
    }

    error_log("Attempting database connection");
    
    // Create connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    error_log("Database connection successful");

    // Get total categories
    $categories_sql = "SELECT COUNT(*) as total FROM categories";
    if (!$categories_result = $conn->query($categories_sql)) {
        throw new Exception("Categories query failed: " . $conn->error);
    }
    $total_categories = $categories_result->fetch_assoc()['total'];

    // Get total products
    $products_sql = "SELECT COUNT(*) as total FROM ingredients";
    if (!$products_result = $conn->query($products_sql)) {
        throw new Exception("Products query failed: " . $conn->error);
    }
    $total_products = $products_result->fetch_assoc()['total'];

    // Get total users
    $users_sql = "SELECT COUNT(*) as total FROM pos_user WHERE user_status = 'Active'";
    if (!$users_result = $conn->query($users_sql)) {
        throw new Exception("Users query failed: " . $conn->error);
    }
    $total_users = $users_result->fetch_assoc()['total'];

    // Get total revenue
    $revenue_sql = "SELECT COALESCE(SUM(unit_cost * quantity), 0) as total FROM ingredients";
    if (!$revenue_result = $conn->query($revenue_sql)) {
        throw new Exception("Revenue query failed: " . $conn->error);
    }
    $total_revenue = $revenue_result->fetch_assoc()['total'];

    error_log("Basic stats queries completed successfully");

    // Get product value by category
    $categoryQuery = "
        SELECT 
            c.category_name,
            COALESCE(SUM(i.quantity * i.unit_cost), 0) as total_value
        FROM categories c
        LEFT JOIN ingredients i ON c.category_id = i.category_id
        GROUP BY c.category_id, c.category_name
        HAVING total_value > 0
        ORDER BY total_value DESC
        LIMIT 10";
    
    if (!$result = $conn->query($categoryQuery)) {
        throw new Exception("Category value query failed: " . $conn->error);
    }
    
    $categoryValue = ['labels' => [], 'data' => []];
    while ($row = $result->fetch_assoc()) {
        $categoryValue['labels'][] = $row['category_name'];
        $categoryValue['data'][] = round(floatval($row['total_value']), 2);
    }

    // Get product count by category
    $categoryCountQuery = "
        SELECT 
            c.category_name,
            COUNT(i.id) as product_count
        FROM categories c
        LEFT JOIN ingredients i ON c.category_id = i.category_id
        GROUP BY c.category_id, c.category_name
        HAVING product_count > 0
        ORDER BY product_count DESC
        LIMIT 10";
    
    if (!$result = $conn->query($categoryCountQuery)) {
        throw new Exception("Category count query failed: " . $conn->error);
    }
    
    $categoryCount = ['labels' => [], 'data' => []];
    while ($row = $result->fetch_assoc()) {
        $categoryCount['labels'][] = $row['category_name'];
        $categoryCount['data'][] = intval($row['product_count']);
    }

    // Get product value by storage location
    $locationQuery = "
        SELECT 
            COALESCE(storage_location, 'Unassigned') as location,
            COALESCE(SUM(quantity * unit_cost), 0) as total_value
        FROM ingredients
        WHERE quantity > 0
        GROUP BY storage_location
        HAVING total_value > 0
        ORDER BY total_value DESC
        LIMIT 10";
    
    if (!$result = $conn->query($locationQuery)) {
        throw new Exception("Location value query failed: " . $conn->error);
    }
    
    $locationValue = ['labels' => [], 'data' => []];
    while ($row = $result->fetch_assoc()) {
        $locationValue['labels'][] = $row['location'];
        $locationValue['data'][] = round(floatval($row['total_value']), 2);
    }

    error_log("All queries completed successfully");

    // Prepare response data
    $response = [
        'success' => true,
        'data' => [
            'total_categories' => intval($total_categories),
            'total_products' => intval($total_products),
            'total_users' => intval($total_users),
            'total_revenue' => round(floatval($total_revenue), 2),
            'categoryValue' => $categoryValue,
            'locationValue' => $locationValue,
            'categoryCount' => $categoryCount
        ]
    ];

    // Clear output buffer and send response
    ob_end_clean();
    echo json_encode($response, JSON_NUMERIC_CHECK);
    error_log("Response sent successfully");

} catch (Exception $e) {
    error_log("Error in dashboard stats: " . $e->getMessage());
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
exit; 