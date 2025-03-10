<?php
// Buffer output to prevent any unwanted output before JSON
ob_start();

// Start session if not already started
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define constant to allow database access
define('ALLOW_ACCESS', true);

try {
    // Check if user is logged in and is admin
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
        throw new Exception('Access denied. Please log in as an admin.');
    }

    // Set headers
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');

    // Allow CORS for development
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    // Handle preflight requests
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }

    require_once('config/database.php');

    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Log debugging information
    error_log("Session data: " . print_r($_SESSION, true));
    error_log("Request data: " . print_r($_POST, true));

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'get':
            getIngredient($conn);
            break;
        case 'save':
            saveIngredient($conn);
            break;
        case 'update_cost':
            updateCost($conn);
            break;
        case 'history':
            getCostHistory($conn);
            break;
        default:
            getIngredientsList($conn);
            break;
    }

} catch (Exception $e) {
    // Log the error
    error_log("Error in ingredient_costs_fetch.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Clear any output that might have been sent
    ob_clean();
    
    // Set appropriate status code
    http_response_code($e->getMessage() === 'Access denied. Please log in as an admin.' ? 403 : 500);
    
    // Send JSON error response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'session_status' => session_status(),
            'user_type' => $_SESSION['user_type'] ?? 'not set',
            'is_ajax' => !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
        ]
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
    // Flush output buffer
    ob_end_flush();
}

function getIngredient($conn) {
    $id = $_POST['id'] ?? 0;
    
    $stmt = $conn->prepare("
        SELECT i.*, 
               COALESCE(ic.cost_price, i.unit_cost) as current_cost,
               i.unit_cost as base_cost
        FROM ingredients i
        LEFT JOIN (
            SELECT ic1.ingredient_id, ic1.cost_price, ic1.effective_date
            FROM ingredient_costs ic1
            INNER JOIN (
                SELECT ingredient_id, MAX(effective_date) as max_date
                FROM ingredient_costs
                GROUP BY ingredient_id
            ) ic2 ON ic1.ingredient_id = ic2.ingredient_id AND ic1.effective_date = ic2.max_date
        ) ic ON i.ingredient_id = ic.ingredient_id
        WHERE i.ingredient_id = ?
    ");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $id);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    if ($data) {
        $data['current_cost_formatted'] = '₱' . number_format(floatval($data['current_cost']), 2);
        $data['base_cost_formatted'] = '₱' . number_format(floatval($data['base_cost']), 2);
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
    $stmt->close();
}

function saveIngredient($conn) {
    $id = $_POST['id'] ?? '';
    $name = $_POST['name'] ?? '';
    $cost = $_POST['cost'] ?? 0;
    $unit = $_POST['unit'] ?? '';
    $stock = $_POST['stock'] ?? 0;
    $minStock = $_POST['minStock'] ?? 0;
    
    $conn->begin_transaction();
    
    try {
        if ($id) {
            $stmt = $conn->prepare("
                UPDATE ingredients 
                SET ingredient_name = ?, 
                    unit = ?,
                    quantity = ?,
                    min_stock = ?,
                    last_updated = CURRENT_TIMESTAMP
                WHERE ingredient_id = ?
            ");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("ssddi", $name, $unit, $stock, $minStock, $id);
        } else {
            $stmt = $conn->prepare("
                INSERT INTO ingredients 
                (ingredient_name, unit, quantity, min_stock) 
                VALUES (?, ?, ?, ?)
            ");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("ssdd", $name, $unit, $stock, $minStock);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        if (!$id) {
            $id = $conn->insert_id;
        }
        
        // Add cost history entry
        $stmt = $conn->prepare("
            INSERT INTO ingredient_costs 
            (ingredient_id, cost_price, created_by) 
            VALUES (?, ?, ?)
        ");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("idi", $id, $cost, $_SESSION['user_id']);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $conn->commit();
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

function updateCost($conn) {
    $id = $_POST['id'] ?? 0;
    $price = $_POST['price'] ?? 0;
    
    $stmt = $conn->prepare("
        INSERT INTO ingredient_costs 
        (ingredient_id, cost_price, created_by) 
        VALUES (?, ?, ?)
    ");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("idi", $id, $price, $_SESSION['user_id']);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    // Update last_updated timestamp in ingredients table
    $stmt = $conn->prepare("
        UPDATE ingredients 
        SET last_updated = CURRENT_TIMESTAMP 
        WHERE ingredient_id = ?
    ");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $id);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    echo json_encode(['success' => true]);
    $stmt->close();
}

function getCostHistory($conn) {
    $id = $_POST['id'] ?? 0;
    
    $stmt = $conn->prepare("
        SELECT ic.*, u.user_name,
               i.unit_cost as base_cost
        FROM ingredient_costs ic
        JOIN pos_user u ON ic.created_by = u.user_id
        JOIN ingredients i ON ic.ingredient_id = i.ingredient_id
        WHERE ic.ingredient_id = ?
        ORDER BY ic.effective_date DESC
    ");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $id);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $data = [];
    
    while ($row = $result->fetch_assoc()) {
        $costPrice = floatval($row['cost_price']);
        $baseCost = floatval($row['base_cost']);
        $diff = $costPrice - $baseCost;
        $changeClass = $diff > 0 ? 'text-success' : ($diff < 0 ? 'text-danger' : '');
        $arrow = $diff > 0 ? '↑' : ($diff < 0 ? '↓' : '');
        
        $data[] = [
            'cost_id' => $row['cost_id'],
            'cost_price' => $costPrice,
            'cost_price_formatted' => '₱' . number_format($costPrice, 2),
            'effective_date' => $row['effective_date'],
            'user_name' => $row['user_name'],
            'change_class' => $changeClass,
            'change_arrow' => $arrow
        ];
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
    $stmt->close();
}

function getIngredientsList($conn) {
    // Handle DataTables parameters
    $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $search = $_POST['search']['value'] ?? '';
    $orderColumn = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 4;
    $orderDir = isset($_POST['order'][0]['dir']) ? strtoupper($_POST['order'][0]['dir']) : 'DESC';
    
    // Validate order direction
    $orderDir = in_array($orderDir, ['ASC', 'DESC']) ? $orderDir : 'DESC';
    
    $columns = ['ingredient_name', 'current_cost', 'unit_cost', 'unit', 'last_updated', 'quantity'];
    $orderBy = isset($columns[$orderColumn]) ? $columns[$orderColumn] : 'last_updated';
    
    // Base query with updated joins for better performance
    $baseQuery = "
        FROM ingredients i
        LEFT JOIN (
            SELECT ic1.ingredient_id, ic1.cost_price, ic1.effective_date
            FROM ingredient_costs ic1
            INNER JOIN (
                SELECT ingredient_id, MAX(effective_date) as max_date
                FROM ingredient_costs
                GROUP BY ingredient_id
            ) ic2 ON ic1.ingredient_id = ic2.ingredient_id AND ic1.effective_date = ic2.max_date
        ) current_cost ON i.ingredient_id = current_cost.ingredient_id
    ";
    
    // Where clause
    $where = "";
    $params = [];
    $types = "";
    
    if ($search) {
        $where = " WHERE i.ingredient_name LIKE ?";
        $params[] = "%$search%";
        $types .= "s";
    }
    
    // Count total records
    $totalRecords = $conn->query("SELECT COUNT(*) as count FROM ingredients")->fetch_assoc()['count'];
    
    // Count filtered records
    $stmt = $conn->prepare("SELECT COUNT(*) as count " . $baseQuery . $where);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $filteredRecords = $stmt->get_result()->fetch_assoc()['count'];
    
    // Get data
    $query = "
        SELECT 
            i.ingredient_id,
            i.ingredient_name,
            i.unit,
            i.quantity,
            i.min_stock,
            i.last_updated,
            i.unit_cost,
            COALESCE(current_cost.cost_price, i.unit_cost) as current_cost
        " . $baseQuery . $where . "
        ORDER BY " . ($orderBy === 'current_cost' ? 'COALESCE(current_cost.cost_price, i.unit_cost)' : $orderBy) . " $orderDir
        LIMIT ?, ?
    ";
    
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        throw new Exception("Prepare failed: " . $conn->error . "\nQuery: " . $query);
    }
    
    // Add limit parameters
    $params[] = $start;
    $params[] = $length;
    $types .= "ii";
    
    $stmt->bind_param($types, ...$params);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $data = [];
    
    while ($row = $result->fetch_assoc()) {
        // Format costs with peso symbol
        $currentCost = floatval($row['current_cost']);
        $unitCost = floatval($row['unit_cost']);
        $diff = $currentCost - $unitCost;
        $changeClass = $diff > 0 ? 'text-success' : ($diff < 0 ? 'text-danger' : '');
        $arrow = $diff > 0 ? '↑' : ($diff < 0 ? '↓' : '');

        $data[] = [
            'ingredient_id' => $row['ingredient_id'],
            'ingredient_name' => $row['ingredient_name'],
            'unit_measure' => $row['unit'],
            'current_stock' => floatval($row['quantity']),
            'min_stock_level' => floatval($row['min_stock']),
            'updated_at' => $row['last_updated'],
            'current_cost' => $currentCost,
            'current_cost_formatted' => '₱' . number_format($currentCost, 2),
            'previous_cost' => $unitCost,
            'previous_cost_formatted' => '₱' . number_format($unitCost, 2),
            'cost_change_class' => $changeClass,
            'cost_change_arrow' => $arrow
        ];
    }
    
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $filteredRecords,
        'data' => $data,
        'error' => null
    ]);
}
?> 