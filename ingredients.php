<?php
session_start();
if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'Admin' && $_SESSION['user_type'] !== 'Stockman')) {
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

// Add required CSS
?>
<link href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css" rel="stylesheet">

<div class="container-fluid">
    <h1 class="mt-4">Inventory Management</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $_SESSION['user_type'] === 'Admin' ? 'admin_dashboard.php' : 'stockman_dashboard.php'; ?>">Dashboard</a></li>
        <li class="breadcrumb-item active">Inventory Management</li>
    </ol>

    <!-- Action Buttons -->
    <div class="mb-4">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
            <i class="fas fa-plus"></i> Add New Item
        </button>
        <button type="button" class="btn btn-success" id="exportBtn">
            <i class="fas fa-file-export"></i> Export to Excel
        </button>
    </div>

    <!-- Inventory Table -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Inventory Items
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="ingredientTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>Quantity</th>
                            <th>Unit</th>
                            <th>Min Stock</th>
                            <th>Unit Cost</th>
                            <th>Last Updated</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded dynamically -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addItemForm">
                    <div class="mb-3">
                        <label for="name" class="form-label">Item Name</label>
                        <input type="text" class="form-control" id="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Category</label>
                        <select class="form-control" id="category_id" required>
                            <?php
                            $sql = "SELECT * FROM categories ORDER BY category_name";
                            $result = $conn->query($sql);
                            while ($row = $result->fetch_assoc()) {
                                echo "<option value='{$row['category_id']}'>{$row['category_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="quantity" min="0" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="unit" class="form-label">Unit</label>
                        <input type="text" class="form-control" id="unit" required>
                    </div>
                    <div class="mb-3">
                        <label for="min_stock" class="form-label">Minimum Stock Level</label>
                        <input type="number" class="form-control" id="min_stock" min="0" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="unit_cost" class="form-label">Unit Cost</label>
                        <input type="number" class="form-control" id="unit_cost" min="0" step="0.01" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveItemBtn">Save Item</button>
            </div>
        </div>
    </div>
</div>

<!-- Add required JavaScript before the closing body tag -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>

<script>
$(document).ready(function() {
    var table = $('#ingredientTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'get_ingredients.php',
            type: 'GET'
        },
        columns: [
            { data: 'id' },
            { data: 'name' },
            { data: 'category' },
            { data: 'quantity' },
            { data: 'unit' },
            { data: 'min_stock' },
            { data: 'unit_cost' },
            { data: 'last_updated' },
            { data: 'status' },
            { 
                data: null,
                render: function(data, type, row) {
                    return `
                        <div class="btn-group">
                            <button class="btn btn-sm btn-primary edit-btn" data-id="${row.id}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-success update-stock-btn" data-id="${row.id}">
                                <i class="fas fa-plus-minus"></i>
                            </button>
                            <button class="btn btn-sm btn-danger delete-btn" data-id="${row.id}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>`;
                }
            }
        ],
        order: [[1, 'asc']], // Order by name
        pageLength: 10,
        responsive: true,
        dom: 'Bfrtip',
        buttons: []  // Initialize empty buttons array
    });

    // Handle Export button click
    $('#exportBtn').on('click', function() {
        // Get the current data from the table
        var data = table.rows().data().toArray();
        
        // Create a worksheet
        var ws = XLSX.utils.json_to_sheet(data.map(function(row) {
            return {
                'ID': row.id,
                'Item Name': row.name,
                'Category': row.category,
                'Quantity': row.quantity,
                'Unit': row.unit,
                'Min Stock': row.min_stock,
                'Unit Cost': row.unit_cost,
                'Last Updated': row.last_updated,
                'Status': row.status.replace(/<[^>]*>/g, '') // Remove HTML tags
            };
        }));

        // Create a workbook
        var wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Inventory");

        // Generate Excel file
        XLSX.writeFile(wb, 'Inventory_Items_' + new Date().toISOString().split('T')[0] + '.xlsx');
    });

    // Add Item Form Submission
    $('#saveItemBtn').click(function() {
        if (!$('#addItemForm')[0].checkValidity()) {
            $('#addItemForm')[0].reportValidity();
            return;
        }

        const itemId = $('#addItemForm').data('itemId');
        const isUpdate = !!itemId;
        
        $.ajax({
            url: isUpdate ? 'update_ingredient.php' : 'add_ingredient.php',
            method: 'POST',
            data: {
                id: itemId, // Only included for updates
                name: $('#name').val(),
                category_id: $('#category_id').val(),
                quantity: $('#quantity').val(),
                unit: $('#unit').val(),
                min_stock: $('#min_stock').val(),
                unit_cost: $('#unit_cost').val()
            },
            success: function(response) {
                if (response.success) {
                    $('#addItemModal').modal('hide');
                    $('#addItemForm')[0].reset();
                    table.ajax.reload();
                    showAlert('Success', isUpdate ? 'Item updated successfully' : 'Item added successfully', 'success');
                } else {
                    showAlert('Error', response.message || 'Failed to ' + (isUpdate ? 'update' : 'add') + ' item', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                showAlert('Error', 'An error occurred while ' + (isUpdate ? 'updating' : 'adding') + ' the item', 'error');
            }
        });
    });

    // Edit Item Button Click
    $(document).on('click', '.edit-btn', function() {
        const row = table.row($(this).closest('tr')).data();
        $('#addItemForm').data('itemId', row.id);
        $('#name').val(row.name);
        $('#category_id').val(row.category_id);
        $('#quantity').val(row.quantity);
        $('#unit').val(row.unit);
        $('#min_stock').val(row.min_stock);
        $('#unit_cost').val(row.unit_cost);
        
        // Update modal title
        $('#addItemModalLabel').text('Edit Item');
        $('#saveItemBtn').text('Update Item');
        $('#addItemModal').modal('show');
    });

    // Reset form when modal is closed
    $('#addItemModal').on('hidden.bs.modal', function() {
        $('#addItemForm')[0].reset();
        $('#addItemForm').removeData('itemId');
        $('#addItemModalLabel').text('Add New Item');
        $('#saveItemBtn').text('Add Item');
    });

    // Update Stock button click handler
    $(document).on('click', '.update-stock-btn', function() {
        const row = table.row($(this).closest('tr')).data();
        console.log('Row data:', row); // Debug log
        
        // Show stock update modal
        Swal.fire({
            title: 'Update Stock',
            html: `
                <div class="mb-3">
                    <label class="form-label">Current Stock: ${row.quantity} ${row.unit}</label>
                </div>
                <div class="mb-3">
                    <label for="stockChange" class="form-label">Quantity to Add/Remove</label>
                    <input type="number" id="stockChange" class="form-control" min="0" step="0.01" required>
                </div>
                <div class="btn-group w-100 mb-3">
                    <button type="button" class="btn btn-success active" id="addStockBtn">Add Stock</button>
                    <button type="button" class="btn btn-danger" id="removeStockBtn">Remove Stock</button>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Update',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                const quantity = document.getElementById('stockChange').value;
                if (!quantity || quantity <= 0) {
                    Swal.showValidationMessage('Please enter a valid quantity');
                    return false;
                }
                
                const action = document.getElementById('addStockBtn').classList.contains('active') ? 'add' : 'remove';
                console.log('Sending data:', { // Debug log
                    ingredient_id: row.id,
                    quantity: quantity,
                    action: action
                });
                
                return $.ajax({
                    url: 'update_stock.php',
                    method: 'POST',
                    data: {
                        ingredient_id: row.id,
                        quantity: quantity,
                        action: action
                    }
                }).then(response => {
                    console.log('Response:', response); // Debug log
                    if (!response.success) {
                        throw new Error(response.message || 'Failed to update stock');
                    }
                    return response;
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                table.ajax.reload();
                Swal.fire('Success', 'Stock updated successfully', 'success');
            }
        }).catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', error.message || 'Failed to update stock', 'error');
        });

        // Add click handlers for add/remove buttons
        $(document).on('click', '#addStockBtn, #removeStockBtn', function() {
            $('#addStockBtn, #removeStockBtn').removeClass('active');
            $(this).addClass('active');
        });
    });

    // Delete button click handler
    $(document).on('click', '.delete-btn', function() {
        const row = table.row($(this).closest('tr')).data();
        console.log('Delete row data:', row); // Debug log
        
        Swal.fire({
            title: 'Are you sure?',
            text: `Do you want to delete ${row.name}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'delete_ingredient.php',
                    method: 'POST',
                    data: { 
                        ingredient_id: row.id
                    },
                    success: function(response) {
                        console.log('Delete response:', response); // Debug log
                        if (response.success) {
                            table.ajax.reload();
                            Swal.fire('Deleted!', 'Item has been deleted.', 'success');
                        } else {
                            Swal.fire('Error', response.message || 'Failed to delete item', 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Delete error:', error); // Debug log
                        Swal.fire('Error', 'An error occurred while deleting the item', 'error');
                    }
                });
            }
        });
    });

    // Function to check for low stock items
    function checkLowStock() {
        $.ajax({
            url: 'check_low_stock.php',
            method: 'GET',
            success: function(response) {
                if (response.success && response.low_stock && response.low_stock.length > 0) {
                    // Update notification dropdown
                    let dropdownContent = '';
                    response.low_stock.forEach(item => {
                        dropdownContent += `
                            <a class="dropdown-item" href="#">
                                <i class="fas fa-exclamation-triangle text-warning"></i>
                                Item ${item.ingredient_name} is low
                            </a>`;
                    });
                    
                    // Update the notifications dropdown
                    $('.notifications-dropdown').html(dropdownContent);
                    
                    // Show the notification count
                    const count = response.low_stock.length;
                    $('.notification-badge').text(count).show();

                    // Also show as toast
                    let toastMessage = '<div class="low-stock-alert">';
                    response.low_stock.forEach(item => {
                        toastMessage += `
                            <div class="alert alert-warning mb-2">
                                <h6 class="alert-heading mb-1">${item.ingredient_name}</h6>
                                <div class="small">
                                    <div>Current: ${item.quantity} ${item.unit}</div>
                                    <div>Minimum: ${item.min_stock} ${item.unit}</div>
                                </div>
                            </div>`;
                    });
                    toastMessage += '</div>';

                    Swal.fire({
                        title: '<i class="fas fa-exclamation-triangle text-warning"></i> Low Stock Alert',
                        html: toastMessage,
                        icon: 'warning',
                        confirmButtonText: 'OK',
                        position: 'top-end',
                        width: '400px',
                        showCloseButton: true,
                        toast: true,
                        timer: 10000,
                        timerProgressBar: true,
                        customClass: {
                            popup: 'low-stock-toast',
                            content: 'low-stock-content'
                        }
                    });
                } else {
                    // No low stock items
                    $('.notifications-dropdown').html('<a class="dropdown-item" href="#">No low stock items</a>');
                    $('.notification-badge').hide();
                }
            },
            error: function(xhr, status, error) {
                console.error('Low stock check failed:', error);
                $('.notifications-dropdown').html('<a class="dropdown-item text-danger" href="#">Error checking stock levels</a>');
            }
        });
    }

    // Check low stock on page load
    checkLowStock();

    // Check low stock every 5 minutes
    setInterval(checkLowStock, 300000);

    // Add custom styles
    $('<style>')
        .text(`
            .notification-badge {
                position: absolute;
                top: -5px;
                right: -5px;
                padding: 3px 6px;
                border-radius: 50%;
                background-color: #dc3545;
                color: white;
                font-size: 0.75rem;
            }
            .low-stock-toast {
                background: rgba(255, 255, 255, 0.95);
                box-shadow: 0 0 20px rgba(0,0,0,0.1);
            }
            .low-stock-content {
                max-height: 300px;
                overflow-y: auto;
            }
            .low-stock-alert .alert {
                margin-bottom: 8px;
                padding: 8px 12px;
            }
            .low-stock-alert .alert:last-child {
                margin-bottom: 0;
            }
            .notifications-dropdown .dropdown-item {
                white-space: normal;
                padding: 0.5rem 1rem;
                border-bottom: 1px solid #e9ecef;
            }
            .notifications-dropdown .dropdown-item:last-child {
                border-bottom: none;
            }
        `)
        .appendTo('head');
});
</script>

<?php include 'footer.php'; ?>
