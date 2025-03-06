<?php
session_start();
if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'Admin' && $_SESSION['user_type'] !== 'Stockman')) {
    header('Location: login.php');
    exit;
}

require_once 'config.php';
include 'header.php';
?>

<!-- Add required CSS -->
<link href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css" rel="stylesheet">

<div class="container-fluid">
    <h1 class="mt-4">Ingredients Activity Log</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="ingredients.php">Ingredients</a></li>
        <li class="breadcrumb-item active">Activity Log</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-history me-1"></i>
            Ingredient Actions History
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="ingredientLogTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Item Name</th>
                            <th>Action Type</th>
                            <th>Quantity</th>
                            <th>Unit</th>
                            <th>User</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add required JavaScript -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#ingredientLogTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: 'get_ingredients_log.php',
            dataSrc: function(json) {
                if (json.error) {
                    console.error('Server error:', json.error);
                    return [];
                }
                return json.data || [];
            }
        },
        columns: [
            { 
                data: 'timestamp',
                render: function(data) {
                    return data ? moment(data).format('YYYY-MM-DD HH:mm:ss') : '';
                }
            },
            { data: 'item_name' },
            { 
                data: 'activity_type',
                render: function(data) {
                    if (!data) return '';
                    let badge = '';
                    switch(data) {
                        case 'Stock Added':
                            badge = 'bg-success';
                            break;
                        case 'Stock Removed':
                            badge = 'bg-warning';
                            break;
                        default:
                            badge = 'bg-info';
                    }
                    return '<span class="badge ' + badge + '">' + data + '</span>';
                }
            },
            { data: 'quantity' },
            { data: 'unit' },
            { data: 'user_id' }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        dom: 'Bfrtip',
        buttons: [
            'copy', 'excel', 'pdf'
        ],
        language: {
            emptyTable: 'No activity log entries found',
            zeroRecords: 'No matching records found',
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>'
        }
    });

    // Handle AJAX errors
    $(document).ajaxError(function(event, jqxhr, settings, error) {
        console.error('Ajax error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error Loading Data',
            text: 'Failed to load activity log data. Please try refreshing the page.'
        });
    });
});
</script>

<?php include 'footer.php'; ?> 