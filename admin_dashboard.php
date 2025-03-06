<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header('Location: login.php');
    exit;
}

require_once 'config.php';

// Establish database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

include 'header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Dashboard Overview</h1>
    
    <!-- Statistics Cards -->
    <div class="row mt-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <h4 class="mb-0" id="totalCategories">0</h4>
                    <div>Total Categories</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <h4 class="mb-0" id="totalProducts">0</h4>
                    <div>Total Products</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white mb-4">
                <div class="card-body">
                    <h4 class="mb-0" id="totalUsers">0</h4>
                    <div>Total Users</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <h4 class="mb-0" id="totalRevenue">USD0.00</h4>
                    <div>Total Revenue</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row">
        <!-- Stock Levels Chart -->
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-1"></i>
                    Stock Levels by Category
                </div>
                <div class="card-body">
                    <canvas id="stockLevelsChart" width="100%" height="40"></canvas>
                </div>
            </div>
        </div>

        <!-- Stock Value Chart -->
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-pie me-1"></i>
                    Stock Value Distribution
                </div>
                <div class="card-body">
                    <canvas id="stockValueChart" width="100%" height="40"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity and Low Stock Tables Row -->
    <div class="row">
        <!-- Recent Activity -->
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-history me-1"></i>
                    Recent Activity
                </div>
                <div class="card-body">
                    <table id="activityTable" class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT * FROM activity_log 
                                    ORDER BY timestamp DESC 
                                    LIMIT 5";
                            $result = $conn->query($sql);

                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>
                                        <td>{$row['timestamp']}</td>
                                        <td>{$row['user_name']}</td>
                                        <td>{$row['action']}</td>
                                        <td>{$row['details']}</td>
                                    </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Low Stock Items -->
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    Low Stock Items
                </div>
                <div class="card-body">
                    <table id="lowStockTable" class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Category</th>
                                <th>Current Stock</th>
                                <th>Min Stock</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT i.*, c.category_name 
                                    FROM ingredients i 
                                    LEFT JOIN categories c ON i.category_id = c.category_id 
                                    WHERE i.quantity <= i.min_stock 
                                    ORDER BY i.quantity ASC 
                                    LIMIT 5";
                            $result = $conn->query($sql);

                            while ($row = $result->fetch_assoc()) {
                                $status = $row['quantity'] <= 0 ? 'Out of Stock' : 'Low Stock';
                                $statusClass = $row['quantity'] <= 0 ? 'bg-danger' : 'bg-warning';
                                
                                echo "<tr>
                                        <td>{$row['name']}</td>
                                        <td>{$row['category_name']}</td>
                                        <td>{$row['quantity']} {$row['unit']}</td>
                                        <td>{$row['min_stock']} {$row['unit']}</td>
                                        <td><span class='badge {$statusClass}'>{$status}</span></td>
                                        <td>
                                            <a href='inventory.php' class='btn btn-primary btn-sm'>
                                                <i class='fas fa-edit'></i> Update Stock
                                            </a>
                                        </td>
                                    </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Chart.js code for Stock Levels Chart
<?php
$sql = "SELECT c.category_name, 
               COUNT(i.id) as total_items,
               SUM(CASE WHEN i.quantity <= 0 THEN 1 ELSE 0 END) as out_of_stock,
               SUM(CASE WHEN i.quantity > 0 AND i.quantity <= i.min_stock THEN 1 ELSE 0 END) as low_stock,
               SUM(CASE WHEN i.quantity > i.min_stock THEN 1 ELSE 0 END) as normal_stock
        FROM categories c
        LEFT JOIN ingredients i ON c.category_id = i.category_id
        GROUP BY c.category_id";
$result = $conn->query($sql);

$categories = [];
$outOfStock = [];
$lowStock = [];
$normalStock = [];

while ($row = $result->fetch_assoc()) {
    $categories[] = $row['category_name'];
    $outOfStock[] = $row['out_of_stock'];
    $lowStock[] = $row['low_stock'];
    $normalStock[] = $row['normal_stock'];
}
?>

// Stock Levels Chart
const stockLevelsCtx = document.getElementById('stockLevelsChart');
new Chart(stockLevelsCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($categories); ?>,
        datasets: [
            {
                label: 'Out of Stock',
                backgroundColor: '#dc3545',
                data: <?php echo json_encode($outOfStock); ?>
            },
            {
                label: 'Low Stock',
                backgroundColor: '#ffc107',
                data: <?php echo json_encode($lowStock); ?>
            },
            {
                label: 'Normal Stock',
                backgroundColor: '#28a745',
                data: <?php echo json_encode($normalStock); ?>
            }
        ]
    },
    options: {
        scales: {
            x: { stacked: true },
            y: { stacked: true }
        }
    }
});

// Stock Value Chart
<?php
$sql = "SELECT c.category_name, SUM(i.quantity * i.unit_cost) as total_value
        FROM categories c
        LEFT JOIN ingredients i ON c.category_id = i.category_id
        GROUP BY c.category_id
        HAVING total_value > 0";
$result = $conn->query($sql);

$categoryNames = [];
$values = [];

while ($row = $result->fetch_assoc()) {
    $categoryNames[] = $row['category_name'];
    $values[] = $row['total_value'];
}
?>

// Stock Value Chart
const stockValueCtx = document.getElementById('stockValueChart');
new Chart(stockValueCtx, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode($categoryNames); ?>,
        datasets: [{
            data: <?php echo json_encode($values); ?>,
            backgroundColor: [
                '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
                '#858796', '#5a5c69', '#2e59d9', '#17a673', '#2c9faf'
            ]
        }]
    },
    options: {
        maintainAspectRatio: false,
        tooltips: {
            callbacks: {
                label: function(tooltipItem, data) {
                    return data.labels[tooltipItem.index] + ': ₱' + 
                           Number(data.datasets[0].data[tooltipItem.index]).toLocaleString();
                }
            }
        }
    }
});

// Initialize DataTables
$(document).ready(function() {
    $('#activityTable, #lowStockTable').DataTable({
        pageLength: 5,
        lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]]
    });

    // Function to update dashboard statistics
    function updateDashboardStats() {
        $.ajax({
            url: 'get_dashboard_stats.php',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    $('#totalCategories').text(response.data.total_categories);
                    $('#totalProducts').text(response.data.total_products);
                    $('#totalUsers').text(response.data.total_users);
                    $('#totalRevenue').text('USD' + response.data.total_revenue);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching dashboard stats:', error);
            }
        });
    }

    // Update stats on page load
    updateDashboardStats();

    // Update stats every 5 minutes
    setInterval(updateDashboardStats, 300000);
});
</script>

<?php include 'footer.php'; ?> 