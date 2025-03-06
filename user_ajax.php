<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_connect.php';

$columns = [
    0 => 'user_id',
    1 => 'user_name',
    2 => 'user_email',
    3 => 'user_type',
    4 => 'user_status',
    5 => null
];

$limit = $_GET['length'];
$start = $_GET['start'];
$order = $columns[$_GET['order'][0]['column']];
$dir = $_GET['order'][0]['dir'];

$searchValue = $_GET['search']['value'];

// Get total records
$totalRecordsStmt = $pdo->query("SELECT COUNT(*) FROM pos_user");
$totalRecords = $totalRecordsStmt->fetchColumn();

// Get total filtered records
$filterQuery = "SELECT COUNT(*) FROM pos_user WHERE 1=1";
if (!empty($searchValue)) {
    $filterQuery .= " AND (user_name LIKE :search OR user_email LIKE :search OR user_type LIKE :search OR user_status LIKE :search)";
}
$totalFilteredRecordsStmt = $pdo->prepare($filterQuery);
if (!empty($searchValue)) {
    $searchParam = "%$searchValue%";
    $totalFilteredRecordsStmt->bindParam(':search', $searchParam);
}
$totalFilteredRecordsStmt->execute();
$totalFilteredRecords = $totalFilteredRecordsStmt->fetchColumn();

// Fetch data with prepared statement
$dataQuery = "SELECT user_id, user_name, user_email, user_type, user_status FROM pos_user WHERE 1=1";
if (!empty($searchValue)) {
    $dataQuery .= " AND (user_name LIKE :search OR user_email LIKE :search OR user_type LIKE :search OR user_status LIKE :search)";
}
$dataQuery .= " ORDER BY $order $dir LIMIT :start, :limit";

$dataStmt = $pdo->prepare($dataQuery);
$dataStmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
$dataStmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
if (!empty($searchValue)) {
    $dataStmt->bindParam(':search', $searchParam);
}
$dataStmt->execute();
$data = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

// Debug: Log the actual SQL query and results
error_log("SQL Query: " . $dataQuery);
error_log("Data returned: " . print_r($data, true));

$response = [
    "draw" => intval($_GET['draw']),
    "recordsTotal" => intval($totalRecords),
    "recordsFiltered" => intval($totalFilteredRecords),
    "data" => $data,
    "debug_query" => $dataQuery  // Adding the query to debug output
];

header('Content-Type: application/json');
echo json_encode($response);
?>