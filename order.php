<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and has appropriate access
if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['Admin', 'Cashier'])) {
    header('Location: login.php');
    exit;
}

require_once('header.php');
?>

<div class="container-fluid px-4">
    <div class="row">
        <div class="col-12">
            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Order History</h5>
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
    </div>
</div>

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
                        buttons += '<a href="print_receipt.php?id=' + data.transaction_id + '" class="btn btn-info btn-sm" target="_blank"><i class="fas fa-print"></i></a>';
                        <?php if ($_SESSION['user_type'] === 'Admin'): ?>
                        buttons += '<button class="btn btn-danger btn-sm" onclick="deleteOrder(' + data.transaction_id + ')"><i class="fas fa-trash"></i></button>';
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