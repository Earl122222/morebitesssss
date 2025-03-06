<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Stockman') {
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

<div class="container-fluid">
    <h1 class="mt-4">Stock Value</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="stockman_dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Stock Value</li>
    </ol>

    <!-- Value Summary Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <h4 class="mb-0">₱<span id="totalStockValue">0.00</span></h4>
                    <div>Total Stock Value</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <h4 class="mb-0">₱<span id="avgItemValue">0.00</span></h4>
                    <div>Average Item Value</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white mb-4">
                <div class="card-body">
                    <h4 class="mb-0" id="totalItems">0</h4>
                    <div>Total Items</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <h4 class="mb-0" id="lowValueItems">0</h4>
                    <div>Low Value Items</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Value Chart -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-chart-bar me-1"></i>
            Stock Value by Category
        </div>
        <div class="card-body">
            <canvas id="stockValueChart" width="100%" height="40"></canvas>
        </div>
    </div>

    <!-- Stock Value Table -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Stock Value Details
        </div>
        <div class="card-body">
            <table id="stockValueTable" class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>Category</th>
                        <th>Quantity</th>
                        <th>Unit</th>
                        <th>Unit Cost</th>
                        <th>Total Value</th>
                        <th>Last Updated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT i.*, c.category_name, (i.quantity * i.unit_cost) as total_value 
                            FROM ingredients i 
                            LEFT JOIN categories c ON i.category_id = c.category_id 
                            ORDER BY total_value DESC";
                    $result = $conn->query($sql);

                    while ($row = $result->fetch_assoc()) {
                        $total_value = number_format($row['total_value'], 2);
                        $unit_cost = number_format($row['unit_cost'], 2);
                        
                        echo "<tr>
                                <td>{$row['ingredient_name']}</td>
                                <td>{$row['category_name']}</td>
                                <td>{$row['quantity']}</td>
                                <td>{$row['unit']}</td>
                                <td>₱{$unit_cost}</td>
                                <td>₱{$total_value}</td>
                                <td>{$row['last_updated']}</td>
                            </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Value Trends -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-chart-line me-1"></i>
            Value Trends
        </div>
        <div class="card-body">
            <canvas id="valueTrendsChart" width="100%" height="40"></canvas>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable
    const stockValueTable = $('#stockValueTable').DataTable({
        responsive: true,
        order: [[5, 'desc']], // Sort by total value by default
        dom: 'Bfrtip',
        buttons: [
            'excel', 'pdf', 'print'
        ]
    });

    // Update value statistics
    function updateValueStats() {
        $.ajax({
            url: 'get_stock_value_stats.php',
            method: 'GET',
            success: function(response) {
                $('#totalStockValue').text(response.total.toFixed(2));
                $('#avgItemValue').text(response.average.toFixed(2));
                $('#totalItems').text(response.items);
                $('#lowValueItems').text(response.lowValue);
            }
        });
    }

    // Initial statistics update
    updateValueStats();

    // Initialize Stock Value Chart
    const stockValueCtx = document.getElementById('stockValueChart');
    $.ajax({
        url: 'get_category_values.php',
        method: 'GET',
        success: function(response) {
            new Chart(stockValueCtx, {
                type: 'bar',
                data: {
                    labels: response.categories,
                    datasets: [{
                        label: 'Stock Value by Category',
                        data: response.values,
                        backgroundColor: 'rgba(0, 123, 255, 0.5)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₱' + value.toFixed(2);
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return '₱' + context.parsed.y.toFixed(2);
                                }
                            }
                        }
                    }
                }
            });
        }
    });

    // Initialize Value Trends Chart
    const trendsCtx = document.getElementById('valueTrendsChart');
    $.ajax({
        url: 'get_value_trends.php',
        method: 'GET',
        success: function(response) {
            new Chart(trendsCtx, {
                type: 'line',
                data: {
                    labels: response.dates,
                    datasets: [{
                        label: 'Total Stock Value',
                        data: response.values,
                        borderColor: 'rgba(40, 167, 69, 1)',
                        tension: 0.1,
                        fill: false
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₱' + value.toFixed(2);
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return '₱' + context.parsed.y.toFixed(2);
                                }
                            }
                        }
                    }
                }
            });
        }
    });

    // Auto-refresh data every 5 minutes
    setInterval(function() {
        stockValueTable.ajax.reload(null, false);
        updateValueStats();
    }, 300000);
});
</script>

<?php include 'footer.php'; ?> 