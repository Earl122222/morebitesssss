<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure clean output buffer
ob_clean();
header('Content-Type: application/json; charset=utf-8');

session_start();
if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'Admin' && $_SESSION['user_type'] !== 'Stockman')) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

require_once 'config.php';

try {
    // Log request parameters
    error_log("DataTables Request: " . json_encode($_GET));
    
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Get total records
    $sql = "SELECT COUNT(*) as total FROM ingredients";
    $result = $conn->query($sql);
    $totalRecords = $result->fetch_assoc()['total'];

    // Search condition
    $search = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';
    $order_column = isset($_GET['order'][0]['column']) ? $_GET['order'][0]['column'] : 1;
    $order_dir = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'asc';
    $start = isset($_GET['start']) ? $_GET['start'] : 0;
    $length = isset($_GET['length']) ? $_GET['length'] : 10;

    // Column names for ordering
    $columns = ['ingredient_id', 'ingredient_name', 'category_name', 'quantity', 'unit', 'min_stock', 'unit_cost', 'last_updated'];
    $order_by = $columns[$order_column];

    // Base query
    $sql = "SELECT 
        i.ingredient_id,
        i.ingredient_name,
        i.category_id,
        c.category_name,
        i.quantity,
        i.unit,
        i.min_stock,
        i.unit_cost,
        i.last_updated
    FROM ingredients i
    LEFT JOIN categories c ON i.category_id = c.category_id";

    // Add search condition if search value exists
    if (!empty($search)) {
        $sql .= " WHERE (i.ingredient_name LIKE '%$search%'
                OR c.category_name LIKE '%$search%'
                OR i.quantity LIKE '%$search%'
                OR i.unit LIKE '%$search%')";
    }

    // Count filtered records
    $filteredQuery = $sql;
    $filteredResult = $conn->query("SELECT COUNT(*) as filtered FROM (" . $filteredQuery . ") as filtered_table");
    $filteredRecords = $filteredResult->fetch_assoc()['filtered'];

    // Add ordering
    $sql .= " ORDER BY " . $order_by . " " . $order_dir;

    // Add limit
    $sql .= " LIMIT " . $start . ", " . $length;

    // Log the final SQL query for debugging
    error_log("Final SQL Query: " . $sql);

    $result = $conn->query($sql);
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }

    $data = array();
    while ($row = $result->fetch_assoc()) {
        // Log each row for debugging
        error_log("Row data: " . json_encode($row));
        
        // Determine status
        if ($row['quantity'] <= 0) {
            $status = '<span class="badge bg-danger">Out of Stock</span>';
        } elseif ($row['quantity'] <= $row['min_stock']) {
            $status = '<span class="badge bg-warning">Low Stock</span>';
        } else {
            $status = '<span class="badge bg-success">In Stock</span>';
        }

        // Prepare actions based on user role
        $actions = '<div class="btn-group">';
        if ($_SESSION['user_type'] === 'Admin') {
            $actions .= '
                <button class="btn btn-sm btn-primary edit-btn" data-id="'.$row['ingredient_id'].'">
                    <i class="fas fa-edit"></i>
                </button>';
        }
        $actions .= '
            <button class="btn btn-sm btn-success update-stock-btn" data-id="'.$row['ingredient_id'].'">
                <i class="fas fa-plus-minus"></i>
            </button>';
        if ($_SESSION['user_type'] === 'Admin') {
            $actions .= '
                <button class="btn btn-sm btn-danger delete-btn" data-id="'.$row['ingredient_id'].'">
                    <i class="fas fa-trash"></i>
                </button>';
        }
        $actions .= '</div>';

        $data[] = array(
            "id" => $row['ingredient_id'],
            "name" => $row['ingredient_name'],
            "category_id" => $row['category_id'],
            "category" => $row['category_name'] ?? 'Uncategorized',
            "quantity" => floatval($row['quantity']),
            "unit" => $row['unit'],
            "min_stock" => floatval($row['min_stock']),
            "unit_cost" => floatval($row['unit_cost']),
            "last_updated" => $row['last_updated'],
            "status" => $status,
            "action" => $actions
        );

        // Log the processed row data
        error_log("Processed row data: " . json_encode($data[count($data)-1]));
    }

    // Log the response data
    error_log("Response data: " . json_encode($data));

    echo json_encode([
        "draw" => isset($_GET['draw']) ? intval($_GET['draw']) : 0,
        "recordsTotal" => $totalRecords,
        "recordsFiltered" => $filteredRecords,
        "data" => $data
    ]);

} catch (Exception $e) {
    error_log("Error in get_ingredients.php: " . $e->getMessage());
    echo json_encode([
        "error" => "Error fetching data: " . $e->getMessage(),
        "data" => []
    ]);
}

$conn->close();
?> 