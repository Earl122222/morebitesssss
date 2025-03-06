<?php
require_once 'db_connect.php';
require_once 'auth_function.php';

// Check if user is logged in and is a Stockman
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Stockman') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Parameters from DataTables
$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';

// Base query for low stock items
$sql = "SELECT SQL_CALC_FOUND_ROWS 
        p.product_id,
        p.product_name,
        p.quantity as current_stock,
        p.min_stock
        FROM products p
        WHERE p.quantity <= p.min_stock";

// Add search condition if search term exists
if (!empty($search)) {
    $sql .= " AND (p.product_name LIKE :search 
              OR p.quantity LIKE :search 
              OR p.min_stock LIKE :search)";
}

// Add ordering
$sql .= " ORDER BY p.quantity ASC";

// Add limit
$sql .= " LIMIT :start, :length";

// Prepare and execute the query
$stmt = $pdo->prepare($sql);

if (!empty($search)) {
    $searchParam = "%{$search}%";
    $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
}

$stmt->bindParam(':start', $start, PDO::PARAM_INT);
$stmt->bindParam(':length', $length, PDO::PARAM_INT);
$stmt->execute();

// Get total records count
$total = $pdo->query("SELECT FOUND_ROWS()")->fetchColumn();

// Fetch the data
$data = array();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $data[] = array(
        "product_id" => $row['product_id'],
        "product_name" => $row['product_name'],
        "current_stock" => $row['current_stock'],
        "min_stock" => $row['min_stock']
    );
}

// Prepare the response
$response = array(
    "draw" => $draw,
    "recordsTotal" => $total,
    "recordsFiltered" => $total,
    "data" => $data
);

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response); 