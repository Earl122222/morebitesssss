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
?>

<div class="container-fluid px-4">
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
            <table id="ingredientTable" class="table table-striped table-bordered">
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
            </table>
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
                    <input type="hidden" id="itemId" name="id">
                    <div class="mb-3">
                        <label for="itemName" class="form-label">Item Name</label>
                        <input type="text" class="form-control" id="itemName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="itemCategory" class="form-label">Category</label>
                        <select class="form-control" id="itemCategory" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php
                            $sql = "SELECT category_id, category_name FROM categories ORDER BY category_name";
                            $result = $conn->query($sql);
                            while ($row = $result->fetch_assoc()) {
                                echo "<option value='{$row['category_id']}'>{$row['category_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="itemQuantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="itemQuantity" name="quantity" min="0" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="itemUnit" class="form-label">Unit</label>
                        <input type="text" class="form-control" id="itemUnit" name="unit" required>
                    </div>
                    <div class="mb-3">
                        <label for="itemMinStock" class="form-label">Minimum Stock Level</label>
                        <input type="number" class="form-control" id="itemMinStock" name="min_stock" min="0" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="itemUnitCost" class="form-label">Unit Cost</label>
                        <input type="number" class="form-control" id="itemUnitCost" name="unit_cost" min="0" step="0.01" required>
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

<!-- Update Stock Modal -->
<div class="modal fade" id="updateStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="updateStockForm">
                    <input type="hidden" id="itemId">
                    <div class="mb-3">
                        <label for="currentStock" class="form-label">Current Stock</label>
                        <input type="text" class="form-control" id="currentStock" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="stockChange" class="form-label">Quantity to Add/Remove</label>
                        <input type="number" class="form-control" id="stockChange" min="0" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Action</label>
                        <div class="btn-group w-100">
                            <button type="button" class="btn btn-success active" id="addStockBtn">Add Stock</button>
                            <button type="button" class="btn btn-danger" id="removeStockBtn">Remove Stock</button>
                        </div>
                        <input type="hidden" id="stockAction" value="add">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveStockBtn">Update Stock</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#ingredientTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'get_ingredients.php',
            type: 'GET',
            error: function(xhr, error, thrown) {
                console.error('Ajax error:', error);
                console.error('Server response:', xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load data. Please try refreshing the page.'
                });
            }
        },
        columns: [
            { data: 'id' },
            { data: 'name' },
            { 
                data: 'category',
                render: function(data, type, row) {
                    return data || 'Uncategorized';
                }
            },
            { data: 'quantity' },
            { data: 'unit' },
            { data: 'min_stock' },
            { data: 'unit_cost' },
            { data: 'last_updated' },
            { data: 'status' },
            { data: 'action' }
        ],
        order: [[1, 'asc']], // Order by name
        pageLength: 10,
        responsive: true,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excel',
                text: '<i class="fas fa-file-excel"></i> Excel',
                className: 'btn btn-success',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7]
                }
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf"></i> PDF',
                className: 'btn btn-danger',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7]
                }
            }
        ]
    });

    // Edit button click handler
    $(document).on('click', '.edit-btn', function() {
        const row = table.row($(this).closest('tr')).data();
        
        // Populate the edit form with current values
        $('#itemId').val(row.id);
        $('#itemName').val(row.name);
        $('#itemCategory').val(row.category_id);
        $('#itemQuantity').val(row.quantity);
        $('#itemUnit').val(row.unit);
        $('#itemMinStock').val(row.min_stock);
        $('#itemUnitCost').val(row.unit_cost);
        
        // Change modal title and button text
        $('.modal-title').text('Edit Item');
        $('#saveItemBtn').text('Update Item');
        
        // Show the modal
        $('#addItemModal').modal('show');
    });

    // Save/Update button click handler
    $('#saveItemBtn').click(function() {
        if (!$('#addItemForm')[0].checkValidity()) {
            $('#addItemForm')[0].reportValidity();
            return;
        }

        const itemId = $('#itemId').val();
        const url = itemId ? 'update_ingredient.php' : 'add_ingredient.php';

        $.ajax({
            url: url,
            method: 'POST',
            data: {
                id: itemId,
                name: $('#itemName').val(),
                category_id: $('#itemCategory').val(),
                quantity: $('#itemQuantity').val(),
                unit: $('#itemUnit').val(),
                min_stock: $('#itemMinStock').val(),
                unit_cost: $('#itemUnitCost').val()
            },
            success: function(response) {
                if (response.success) {
                    $('#addItemModal').modal('hide');
                    $('#addItemForm')[0].reset();
                    table.ajax.reload();
                    
                    Swal.fire({
                        title: 'Success',
                        text: itemId ? 'Item updated successfully' : 'Item added successfully',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: response.message || 'Failed to save item',
                        icon: 'error'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax error:', error);
                console.error('Server response:', xhr.responseText);
                Swal.fire({
                    title: 'Error',
                    text: 'An error occurred while saving the item',
                    icon: 'error'
                });
            }
        });
    });

    // Reset form when modal is closed
    $('#addItemModal').on('hidden.bs.modal', function() {
        $('#addItemForm')[0].reset();
        $('#itemId').val('');
        $('.modal-title').text('Add New Item');
        $('#saveItemBtn').text('Save Item');
    });

    // Update Stock Button Click
    $(document).on('click', '.update-stock-btn', function() {
        const row = table.row($(this).closest('tr')).data();
        $('#itemId').val(row.id);
        $('#currentStock').val(row.quantity);
        $('#stockChange').val('');
        $('#updateStockModal').modal('show');
    });

    // Stock Action Buttons
    $('#addStockBtn, #removeStockBtn').click(function() {
        $('#addStockBtn, #removeStockBtn').removeClass('active');
        $(this).addClass('active');
        $('#stockAction').val($(this).attr('id') === 'addStockBtn' ? 'add' : 'remove');
    });

    // Save Stock Update
    $('#saveStockBtn').click(function() {
        if (!$('#updateStockForm')[0].checkValidity()) {
            $('#updateStockForm')[0].reportValidity();
            return;
        }

        $.ajax({
            url: 'update_stock.php',
            method: 'POST',
            data: {
                ingredient_id: $('#itemId').val(),
                quantity: $('#stockChange').val(),
                action: $('#stockAction').val()
            },
            success: function(response) {
                if (response.success) {
                    $('#updateStockModal').modal('hide');
                    table.ajax.reload();
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Stock updated successfully'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Failed to update stock'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while updating stock'
                });
            }
        });
    });

    // Delete Item
    $(document).on('click', '.delete-btn', function() {
        const itemId = $(this).data('id');
        
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
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
                    data: { ingredient_id: itemId },
                    success: function(response) {
                        if (response.success) {
                            table.ajax.reload();
                            Swal.fire(
                                'Deleted!',
                                'Item has been deleted.',
                                'success'
                            );
                        } else {
                            Swal.fire(
                                'Error!',
                                response.message || 'Failed to delete item',
                                'error'
                            );
                        }
                    },
                    error: function() {
                        Swal.fire(
                            'Error!',
                            'An error occurred while deleting the item',
                            'error'
                        );
                    }
                });
            }
        });
    });
});
</script>

<?php include 'footer.php'; ?> 