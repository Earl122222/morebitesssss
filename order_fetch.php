<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ALLOW_ACCESS', true);
require_once('config/database.php');

// Check if user is logged in and has appropriate access
if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['Admin', 'Cashier'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Access denied']);
    exit;
}

// Initialize connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Connection failed: ' . $conn->connect_error]);
    exit;
}

try {
    // Handle delete action for Admin
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
        if ($_SESSION['user_type'] !== 'Admin') {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        $order_id = intval($_POST['id']);
        
        $conn->begin_transaction();

        // Delete order items first
        $stmt = $conn->prepare("DELETE FROM transaction_items WHERE transaction_id = ?");
        $stmt->bind_param('i', $order_id);
        $stmt->execute();
        $stmt->close();

        // Delete the order
        $stmt = $conn->prepare("DELETE FROM transactions WHERE transaction_id = ?");
        $stmt->bind_param('i', $order_id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        echo json_encode(['success' => true]);
        exit;
    }

    // Get parameters from DataTables
    $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $user_type = isset($_POST['user_type']) ? $_POST['user_type'] : '';

    // Build where clause
    $where = [];
    $params = [];
    $types = '';

    if ($user_type === 'Cashier') {
        $where[] = "t.user_id = ?";
        $params[] = $user_id;
        $types .= 'i';
    }

    if (!empty($search)) {
        $where[] = "(t.transaction_id LIKE ? OR u.user_name LIKE ? OR t.total LIKE ?)";
        $search_param = "%$search%";
        $params = array_merge($params, [$search_param, $search_param, $search_param]);
        $types .= 'sss';
    }

    $where_clause = !empty($where) ? " WHERE " . implode(' AND ', $where) : "";

    // Get total records count
    $sql = "SELECT COUNT(*) as total FROM transactions t";
    if ($user_type === 'Cashier') {
        $sql .= " WHERE t.user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
    } else {
        $stmt = $conn->prepare($sql);
    }
    $stmt->execute();
    $total_records = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    // Get filtered records count
    $sql = "SELECT COUNT(*) as total 
            FROM transactions t 
            JOIN pos_user u ON t.user_id = u.user_id 
            " . $where_clause;
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $filtered_records = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    // Main query for data
    $sql = "SELECT 
                t.transaction_id,
                t.total,
                t.created_at,
                u.user_name,
                GROUP_CONCAT(CONCAT(ti.quantity, 'x ', p.product_name) SEPARATOR ', ') as items
            FROM transactions t
            JOIN pos_user u ON t.user_id = u.user_id
            LEFT JOIN transaction_items ti ON t.transaction_id = ti.transaction_id
            LEFT JOIN pos_product p ON ti.product_id = p.product_id
            " . $where_clause . "
            GROUP BY t.transaction_id, t.total, t.created_at, u.user_name
            ORDER BY t.created_at DESC
            LIMIT ?, ?";

    // Add pagination parameters
    $params[] = $start;
    $params[] = $length;
    $types .= 'ii';

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    $response = [
        "draw" => $draw,
        "recordsTotal" => $total_records,
        "recordsFiltered" => $filtered_records,
        "data" => $data
    ];

    header('Content-Type: application/json');
    echo json_encode($response);

} catch (Exception $e) {
    error_log("Error in order_fetch.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    header('Content-Type: application/json');
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}
?> 