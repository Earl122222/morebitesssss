<?php
require_once 'db_connect.php';
require_once 'auth_function.php';

// Check if user is logged in and is a Cashier
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Cashier') {
    header('Location: login.php');
    exit;
}

include('header.php');
?>

<h1 class="mt-4">Cashier Dashboard</h1>
<ol class="breadcrumb mb-4">
    <li class="breadcrumb-item active">Cashier Dashboard</li>
</ol>

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

<div class="row">
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
    <div class="col-xl-6">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-table me-1"></i>
                Recent Transactions
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="recentTransactionsTable">
                        <thead>
                            <tr>
                                <th>Transaction ID</th>
                                <th>Amount</th>
                                <th>Time</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Will be populated by AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>

<script>
// Initialize DataTable for recent transactions
$(document).ready(function() {
    $('#recentTransactionsTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": "recent_transactions_ajax.php",
        "columns": [
            { "data": "transaction_id" },
            { 
                "data": "amount",
                "render": function(data, type, row) {
                    return '₱' + parseFloat(data).toFixed(2);
                }
            },
            { "data": "created_at" },
            {
                "data": null,
                "render": function(data, type, row) {
                    return '<button class="btn btn-info btn-sm view-transaction" data-id="' + row.transaction_id + '">View</button>';
                }
            }
        ],
        "order": [[2, "desc"]]
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
                    beginAtZero: true
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
        }
    });
});
</script> 