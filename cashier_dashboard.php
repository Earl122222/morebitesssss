<?php
session_start();

// Define constant to allow database.php access
define('ALLOW_ACCESS', true);

require_once 'config/database.php';
require_once 'auth_function.php';

// Check if user is logged in and is a Cashier
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Cashier') {
    header('Location: login.php');
    exit;
}

// Establish database connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

include 'header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Cashier Dashboard</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">Dashboard</li>
    </ol>

    <!-- Quick Action Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">New Transaction</div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="pos.php">Start POS</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">Today's Sales</div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="daily_sales.php">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">Recent Transactions</div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="transactions.php">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white mb-4">
                <div class="card-body">Products</div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="products.php">View Products</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Tables Row -->
    <div class="row">
        <!-- Sales Chart -->
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-1"></i>
                    Today's Sales Chart
                </div>
                <div class="card-body">
                    <canvas id="salesChart" width="100%" height="40"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Transactions Table -->
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-table me-1"></i>
                    Recent Transactions
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="recentTransactionsTable" width="100%">
                            <thead>
                                <tr>
                                    <th>Transaction ID</th>
                                    <th>Amount</th>
                                    <th>Time</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    // Enable error reporting for debugging
                                    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
                                    
                                    $sql = "SELECT order_id as transaction_id, total_amount as total, order_date as created_at 
                                           FROM pos_order 
                                           WHERE user_id = ? 
                                           ORDER BY order_date DESC 
                                           LIMIT 10";
                                    
                                    // Prepare statement with error checking
                                    if (!($stmt = $conn->prepare($sql))) {
                                        throw new Exception("Prepare failed: " . $conn->error);
                                    }
                                    
                                    // Bind parameter with error checking
                                    if (!$stmt->bind_param("i", $_SESSION['user_id'])) {
                                        throw new Exception("Binding parameters failed: " . $stmt->error);
                                    }
                                    
                                    // Execute with error checking
                                    if (!$stmt->execute()) {
                                        throw new Exception("Execute failed: " . $stmt->error);
                                    }
                                    
                                    $result = $stmt->get_result();

                                    if ($result->num_rows === 0) {
                                        echo "<tr><td colspan='4' class='text-center'>No transactions found</td></tr>";
                                    } else {
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td>" . htmlspecialchars($row['transaction_id']) . "</td>";
                                            echo "<td>₱" . htmlspecialchars(number_format($row['total'], 2)) . "</td>";
                                            echo "<td>" . htmlspecialchars(date('Y-m-d H:i:s', strtotime($row['created_at']))) . "</td>";
                                            echo "<td><button class='btn btn-info btn-sm view-transaction' data-id='" . htmlspecialchars($row['transaction_id']) . "'>View</button></td>";
                                            echo "</tr>";
                                        }
                                    }
                                    
                                    $stmt->close();
                                } catch (Exception $e) {
                                    error_log("Error loading transactions: " . $e->getMessage());
                                    echo "<tr><td colspan='4' class='text-center text-danger'>Error loading transactions. Please try again later.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Receipt Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Receipt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="receiptContent">
                <!-- Receipt content will be added here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="window.print()">Print Receipt</button>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#recentTransactionsTable').DataTable({
        pageLength: 10,
        order: [[2, 'desc']],
        language: {
            emptyTable: "No transactions found"
        },
        dom: '<"top"f>rt<"bottom"lip><"clear">',
        responsive: true
    });

    // Handle view transaction button click
    $('.view-transaction').click(function() {
        var transactionId = $(this).data('id');
        // Load and show receipt
        $.ajax({
            url: 'get_receipt.php',
            method: 'GET',
            data: { transaction_id: transactionId },
            success: function(response) {
                $('#receiptContent').html(response);
                $('#receiptModal').modal('show');
            },
            error: function(xhr, status, error) {
                console.error('Error loading receipt:', error);
                alert('Error loading receipt. Please try again.');
            }
        });
    });

    // Sales Chart
    var ctx = document.getElementById("salesChart");
    var salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [], // Will be populated by AJAX
            datasets: [{
                label: "Sales",
                backgroundColor: "rgba(2,117,216,0.2)",
                borderColor: "rgba(2,117,216,1)",
                data: [] // Will be populated by AJAX
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

    // Load chart data
    $.ajax({
        url: 'daily_sales_chart_ajax.php',
        type: 'GET',
        success: function(response) {
            var chartData = JSON.parse(response);
            salesChart.data.labels = chartData.labels;
            salesChart.data.datasets[0].data = chartData.data;
            salesChart.update();
        },
        error: function(xhr, status, error) {
            console.error('Error loading chart data:', error);
        }
    });
});
</script> 