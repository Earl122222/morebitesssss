<?php
session_start();
define('ALLOW_ACCESS', true);
require_once('config/database.php');

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header('HTTP/1.0 403 Forbidden');
    echo 'Access Denied';
    exit;
}

// Initialize connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - MoreBites</title>
    
    <!-- Include Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Include DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Include Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <!-- Include SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <style>
        .order-items {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .btn-group {
            gap: 5px;
        }
        .btn-pdf {
            background-color: #ffc107;
            color: #000;
        }
        .btn-delete {
            background-color: #dc3545;
            color: #fff;
        }
        .breadcrumb {
            background-color: transparent;
            padding: 0;
            margin-bottom: 20px;
        }
        .card {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'navigation.php'; ?>

    <div class="container-fluid p-4">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Order Management</li>
            </ol>
        </nav>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Order List</h5>
                <a href="pos.php" class="btn btn-success btn-sm">
                    <i class="fas fa-plus"></i> Add
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="orderTable" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Order Number</th>
                                <th>Order Total</th>
                                <th>Created By</th>
                                <th>Date Time</th>
                                <th>Items</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Include jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Include Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Include DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <!-- Include SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable with server-side processing
            $('#orderTable').DataTable({
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "order_ajax.php?action=get",
                    "type": "GET"
                },
                "columns": [
                    { "data": "order_number" },
                    { 
                        "data": "order_total",
                        "render": function(data, type, row) {
                            return '₱' + data;
                        }
                    },
                    { "data": "user_name" },
                    { "data": "order_datetime" },
                    { 
                        "data": "items",
                        "render": function(data, type, row) {
                            return '<span class="order-items" title="' + data + '">' + data + '</span>';
                        }
                    },
                    {
                        "data": null,
                        "render": function(data, type, row) {
                            return `
                                <div class="btn-group">
                                    <a href="print_receipt.php?order_id=${row.order_id}&noprint=1" 
                                       class="btn btn-pdf btn-sm" target="_blank" title="View Receipt">
                                        <i class="fas fa-file-pdf"></i> PDF
                                    </a>
                                    <button class="btn btn-delete btn-sm" onclick="deleteOrder(${row.order_id})" 
                                            title="Delete Order">
                                        <i class="fas fa-times"></i> X
                                    </button>
                                </div>`;
                        }
                    }
                ],
                "order": [[3, "desc"]],
                "pageLength": 10,
                "drawCallback": function() {
                    // Initialize tooltips after table draw
                    $('[title]').tooltip();
                }
            });
        });

        function deleteOrder(orderId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'order_ajax.php',
                        type: 'POST',
                        data: { id: orderId },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire(
                                    'Deleted!',
                                    'Order has been deleted.',
                                    'success'
                                ).then(() => {
                                    $('#orderTable').DataTable().ajax.reload();
                                });
                            } else {
                                Swal.fire(
                                    'Error!',
                                    'Failed to delete order.',
                                    'error'
                                );
                            }
                        },
                        error: function() {
                            Swal.fire(
                                'Error!',
                                'An error occurred while deleting the order.',
                                'error'
                            );
                        }
                    });
                }
            });
        }
    </script>
</body>
</html>
<?php $conn->close(); ?> 