<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and has appropriate access
if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['Admin', 'Cashier'])) {
    header('Location: login.php');
    exit;
}

require_once('navigation.php');
?>

<!-- DataTables CSS -->
<link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

<style>
    .order-items {
        max-width: 300px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .btn-group {
        display: flex;
        gap: 5px;
    }
    .btn-pdf {
        background-color: #28a745;
        color: white;
        border: none;
        padding: 4px 8px;
        border-radius: 4px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .btn-delete {
        background-color: #dc3545;
        color: white;
        border: none;
        padding: 4px 8px;
        border-radius: 4px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .btn-pdf:hover {
        background-color: #218838;
        color: white;
        text-decoration: none;
    }
    .btn-delete:hover {
        background-color: #c82333;
        color: white;
    }
    .card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    .card-header {
        background: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .card-body {
        padding: 20px;
    }
    .table thead th {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
    }
    .table td {
        vertical-align: middle;
    }
</style>

<div class="container-fluid px-4">
    <h1 class="mt-4">Order History</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Order History</li>
    </ol>
    
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Order List</h5>
            <a href="pos.php" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> New Order
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="orderTable" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Cashier</th>
                            <th>Total</th>
                            <th>Date/Time</th>
                            <th>Items</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- DataTables & Plugins -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        var table = $('#orderTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: 'order_fetch.php',
                type: 'POST',
                data: function(d) {
                    d.user_type = '<?php echo $_SESSION['user_type']; ?>';
                    d.user_id = '<?php echo $_SESSION['user_id']; ?>';
                }
            },
            columns: [
                { 
                    data: 'transaction_id',
                    render: function(data) {
                        return String(data).padStart(6, '0');
                    }
                },
                { data: 'user_name' },
                { 
                    data: 'total',
                    render: function(data) {
                        return '₱' + parseFloat(data).toFixed(2);
                    }
                },
                { 
                    data: 'created_at',
                    render: function(data) {
                        return new Date(data).toLocaleString();
                    }
                },
                { 
                    data: 'items',
                    render: function(data) {
                        return '<div class="order-items">' + (data || 'No items') + '</div>';
                    }
                },
                {
                    data: null,
                    render: function(data) {
                        var buttons = '<div class="btn-group">';
                        buttons += '<a href="print_receipt.php?id=' + data.transaction_id + '" class="btn-pdf" target="_blank"><i class="fas fa-print"></i> Print</a>';
                        <?php if ($_SESSION['user_type'] === 'Admin'): ?>
                        buttons += '<button class="btn-delete" onclick="deleteOrder(' + data.transaction_id + ')"><i class="fas fa-trash"></i> Delete</button>';
                        <?php endif; ?>
                        buttons += '</div>';
                        return buttons;
                    }
                }
            ],
            order: [[3, 'desc']],
            pageLength: 10,
            language: {
                processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>'
            }
        });
    });

    function deleteOrder(orderId) {
        Swal.fire({
            title: 'Delete Order',
            text: "Are you sure you want to delete this order? This action cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'order_fetch.php',
                    type: 'POST',
                    data: {
                        action: 'delete',
                        id: orderId
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: 'Order has been deleted successfully.',
                                timer: 1500
                            });
                            $('#orderTable').DataTable().ajax.reload();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.error || 'Failed to delete order.'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred while deleting the order.'
                        });
                    }
                });
            }
        });
    }
</script>