<?php
session_start();

// Define constant to allow database.php access
define('ALLOW_ACCESS', true);

require_once 'config/database.php';
require_once 'auth_function.php';

// Check if user is logged in and is a Cashier
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Cashier') {
    header('Content-Type: application/json');
    echo json_encode([
        'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 0,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => []
    ]);
    exit;
}

try {
    // Database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Parameters from DataTables
    $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
    $orderColumn = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 2;
    $orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc';

    // Column names for ordering
    $columns = [
        0 => 't.transaction_id',
        1 => 't.total',
        2 => 't.created_at'
    ];

    // Base query
    $sql = "FROM transactions t WHERE 1=1";

    // Search condition
    if (!empty($search)) {
        $search = $conn->real_escape_string($search);
        $sql .= " AND (t.transaction_id LIKE '%$search%' 
                 OR t.total LIKE '%$search%' 
                 OR t.created_at LIKE '%$search%')";
    }

    // Count total records
    $countSql = "SELECT COUNT(*) as total " . $sql;
    $countResult = $conn->query($countSql);
    $totalRecords = $countResult->fetch_assoc()['total'];
    $filteredRecords = $totalRecords;

    // Add ordering
    $sql .= " ORDER BY " . $columns[$orderColumn] . " " . $orderDir;

    // Add limit
    $sql .= " LIMIT $start, $length";

    // Final query
    $sql = "SELECT t.transaction_id, t.total, t.created_at " . $sql;

    // Execute query
    $result = $conn->query($sql);
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }

    // Prepare data
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'transaction_id' => $row['transaction_id'],
            'amount' => $row['total'],
            'created_at' => date('Y-m-d H:i:s', strtotime($row['created_at'])),
            'action' => '<button class="btn btn-info btn-sm view-transaction" data-id="' . $row['transaction_id'] . '">View</button>'
        ];
    }

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $filteredRecords,
        'data' => $data
    ]);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 0,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
} 