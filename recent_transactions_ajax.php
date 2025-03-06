<?php
require_once 'db_connect.php';
require_once 'auth_function.php';

// Check if user is logged in and is a Cashier
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Cashier') {
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
$order_column = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 2;
$order_dir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc';

// Column names for ordering
$columns = array(
    0 => 'transaction_id',
    1 => 'amount',
    2 => 'created_at'
);

// Base query
$sql = "SELECT SQL_CALC_FOUND_ROWS 
        transaction_id, 
        amount, 
        created_at 
        FROM transactions";

// Search condition
if (!empty($search)) {
    $sql .= " WHERE transaction_id LIKE :search 
              OR amount LIKE :search 
              OR created_at LIKE :search";
}

// Ordering
$sql .= " ORDER BY " . $columns[$order_column] . " " . $order_dir;

// Limit
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
        "transaction_id" => $row['transaction_id'],
        "amount" => $row['amount'],
        "created_at" => $row['created_at']
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