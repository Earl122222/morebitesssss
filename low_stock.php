<?php
session_start();
if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'Admin' && $_SESSION['user_type'] !== 'Stockman')) {
    header('Location: login.php');
    exit;
}

require_once 'config.php';
include 'header.php';
?>

<div class="container-fluid">
    <h1 class="mt-4">Low Stock Ingredients</h1>
    <div class="mb-4">
        <span class="badge bg-danger">Out of Stock</span>
        <span class="badge bg-warning">Critical</span>
        <span class="badge bg-info">Low</span>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table id="lowStockTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Current Stock</th>
                            <th>Threshold</th>
                            <th>Unit</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var table = $('#lowStockTable').DataTable({
        ajax: {
            url: 'get_low_stock.php',
            type: 'GET'
        },
        columns: [
            { data: 'name' },
            { data: 'quantity' },
            { data: 'min_stock' },
            { data: 'unit' },
            { 
                data: 'status',
                render: function(data, type, row) {
                    if (row.quantity <= 0) {
                        return '<span class="badge bg-danger">Out of Stock</span>';
                    } else if (row.quantity <= row.min_stock * 0.5) {
                        return '<span class="badge bg-warning">Critical</span>';
                    } else {
                        return '<span class="badge bg-info">Low</span>';
                    }
                }
            }
        ],
        order: [[1, 'asc']],
        pageLength: 25
    });
});
</script>

<?php include 'footer.php'; ?> 