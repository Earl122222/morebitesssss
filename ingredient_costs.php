<?php
// Start session if not already started
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define constant to allow database access
define('ALLOW_ACCESS', true);

// Check if user is admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header('Location: login.php');
    exit;
}

require_once('header.php');

// Debug session information
error_log("Session Info in ingredient_costs.php: " . print_r($_SESSION, true));
?>

<!-- Add required CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">Ingredient Costs</h1>
                <button type="button" class="btn btn-primary" onclick="showAddIngredientModal()">
                    <i class="fas fa-plus"></i> Add New Ingredient
                </button>
            </div>

            <div class="card shadow">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="ingredientTable" class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Ingredient Name</th>
                                    <th>Current Cost</th>
                                    <th>Previous Cost</th>
                                    <th>Unit</th>
                                    <th>Last Updated</th>
                                    <th>Stock Level</th>
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
    </div>
</div>

<!-- Add/Edit Ingredient Modal -->
<div class="modal fade" id="ingredientModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add New Ingredient</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="ingredientForm">
                    <input type="hidden" id="ingredientId">
                    <div class="mb-3">
                        <label for="ingredientName" class="form-label">Ingredient Name</label>
                        <input type="text" class="form-control" id="ingredientName" required>
                    </div>
                    <div class="mb-3">
                        <label for="costPrice" class="form-label">Cost Price</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" class="form-control" id="costPrice" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="unitMeasure" class="form-label">Unit of Measure</label>
                        <select class="form-control" id="unitMeasure" required>
                            <option value="kg">Kilogram (kg)</option>
                            <option value="g">Gram (g)</option>
                            <option value="l">Liter (L)</option>
                            <option value="ml">Milliliter (ml)</option>
                            <option value="pcs">Pieces (pcs)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="currentStock" class="form-label">Current Stock</label>
                        <input type="number" class="form-control" id="currentStock" step="0.01" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="minStockLevel" class="form-label">Minimum Stock Level</label>
                        <input type="number" class="form-control" id="minStockLevel" step="0.01" min="0" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveIngredient()">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- View Cost History Modal -->
<div class="modal fade" id="costHistoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cost History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="costHistoryContent"></div>
            </div>
        </div>
    </div>
</div>

<!-- Add required JavaScript -->
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // Enable debug mode for DataTables
    $.fn.dataTable.ext.errMode = 'throw';
    
    var table = $('#ingredientTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'ingredient_costs_fetch.php',
            type: 'POST',
            error: function(xhr, error, thrown) {
                console.error('DataTables error:', error);
                console.error('Server response:', xhr.responseText);
                console.error('Error details:', thrown);
                
                // Parse the error response
                let errorMessage = 'Error loading data. Please try again.';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.error) {
                        errorMessage = response.error;
                        if (response.debug) {
                            console.log('Debug info:', response.debug);
                        }
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                }
                
                // Clear loading state
                table.processing(false);
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error Loading Data',
                    text: errorMessage,
                    footer: 'Please check if you are logged in as an admin'
                });
                
                // If access denied, redirect to login
                if (xhr.status === 403) {
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 2000);
                }
            }
        },
        columns: [
            { 
                data: 'ingredient_name',
                defaultContent: ''
            },
            { 
                data: 'current_cost_formatted',
                defaultContent: '₱0.00'
            },
            {
                data: 'previous_cost_formatted',
                defaultContent: '₱0.00',
                render: function(data, type, row) {
                    if (!data) return '₱0.00';
                    return `${data} ${row.cost_change_class ? `<span class="${row.cost_change_class}">${row.cost_change_arrow}</span>` : ''}`;
                }
            },
            { 
                data: 'unit_measure',
                defaultContent: ''
            },
            {
                data: 'updated_at',
                defaultContent: '',
                render: function(data) {
                    return data ? new Date(data).toLocaleString() : '-';
                }
            },
            {
                data: null,
                defaultContent: '',
                render: function(data) {
                    if (!data) return '-';
                    const stockLevel = parseFloat(data.current_stock) || 0;
                    const minLevel = parseFloat(data.min_stock_level) || 0;
                    const stockClass = stockLevel <= minLevel ? 'text-danger' : 'text-success';
                    return `<span class="${stockClass}">${stockLevel} ${data.unit_measure || ''}</span>`;
                }
            },
            {
                data: null,
                orderable: false,
                defaultContent: '',
                render: function(data) {
                    if (!data || !data.ingredient_id) return '';
                    return `
                        <div class="btn-group">
                            <button class="btn btn-sm btn-primary" onclick="editIngredient(${data.ingredient_id})" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-info" onclick="viewCostHistory(${data.ingredient_id})" title="History">
                                <i class="fas fa-history"></i>
                            </button>
                            <button class="btn btn-sm btn-warning" onclick="updateCost(${data.ingredient_id})" title="Update Cost">
                                <i class="fas fa-peso-sign"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        order: [[4, 'desc']],
        pageLength: 10,
        language: {
            processing: '<div class="d-flex justify-content-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>',
            emptyTable: 'No ingredients found',
            zeroRecords: 'No matching ingredients found'
        },
        drawCallback: function() {
            $('[title]').tooltip();
        },
        initComplete: function() {
            console.log('DataTable initialization complete');
        }
    });

    // Add error event handler
    table.on('error.dt', function(e, settings, techNote, message) {
        console.error('DataTables error:', message);
        table.processing(false);
    });
});

function showAddIngredientModal() {
    $('#modalTitle').text('Add New Ingredient');
    $('#ingredientId').val('');
    $('#ingredientForm')[0].reset();
    $('#ingredientModal').modal('show');
}

function editIngredient(id) {
    $.ajax({
        url: 'ingredient_costs_fetch.php',
        type: 'POST',
        data: {
            action: 'get',
            id: id
        },
        success: function(response) {
            if (response.success) {
                $('#modalTitle').text('Edit Ingredient');
                $('#ingredientId').val(response.data.ingredient_id);
                $('#ingredientName').val(response.data.ingredient_name);
                $('#costPrice').val(response.data.current_cost);
                $('#unitMeasure').val(response.data.unit_measure);
                $('#currentStock').val(response.data.current_stock);
                $('#minStockLevel').val(response.data.min_stock_level);
                $('#ingredientModal').modal('show');
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.error || 'Failed to load ingredient data'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load ingredient data. Please try again.'
            });
        }
    });
}

function saveIngredient() {
    const formData = {
        id: $('#ingredientId').val(),
        name: $('#ingredientName').val(),
        cost: $('#costPrice').val(),
        unit: $('#unitMeasure').val(),
        stock: $('#currentStock').val(),
        minStock: $('#minStockLevel').val()
    };

    $.ajax({
        url: 'ingredient_costs_fetch.php',
        type: 'POST',
        data: {
            action: 'save',
            ...formData
        },
        success: function(response) {
            if (response.success) {
                $('#ingredientModal').modal('hide');
                $('#ingredientTable').DataTable().ajax.reload();
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: 'Ingredient saved successfully!',
                    timer: 1500
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.error || 'Failed to save ingredient.'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            console.error('Server Response:', xhr.responseText);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to save ingredient. Please try again.'
            });
        }
    });
}

function updateCost(id) {
    Swal.fire({
        title: 'Update Cost Price',
        input: 'number',
        inputLabel: 'New Cost Price (₱)',
        inputAttributes: {
            step: '0.01',
            min: '0'
        },
        showCancelButton: true,
        confirmButtonText: 'Update',
        showLoaderOnConfirm: true,
        preConfirm: (price) => {
            return $.ajax({
                url: 'ingredient_costs_fetch.php',
                type: 'POST',
                data: {
                    action: 'update_cost',
                    id: id,
                    price: price
                }
            }).catch(error => {
                Swal.showValidationMessage(`Request failed: ${error.responseText || error}`);
            });
        }
    }).then((result) => {
        if (result.isConfirmed && result.value.success) {
            $('#ingredientTable').DataTable().ajax.reload();
            Swal.fire({
                icon: 'success',
                title: 'Cost Updated',
                timer: 1500
            });
        }
    });
}

function viewCostHistory(id) {
    $.ajax({
        url: 'ingredient_costs_fetch.php',
        type: 'POST',
        data: {
            action: 'history',
            id: id
        },
        success: function(response) {
            if (response.success) {
                let html = '<div class="list-group">';
                if (response.data.length === 0) {
                    html += '<div class="list-group-item text-center">No cost history available</div>';
                } else {
                    response.data.forEach(item => {
                        html += `
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <span class="fw-bold">${item.cost_price_formatted} ${item.change_class ? `<span class="${item.change_class}">${item.change_arrow}</span>` : ''}</span>
                                    <small>${new Date(item.effective_date).toLocaleString()}</small>
                                </div>
                                <small class="text-muted">Updated by: ${item.user_name}</small>
                            </div>
                        `;
                    });
                }
                html += '</div>';
                $('#costHistoryContent').html(html);
                $('#costHistoryModal').modal('show');
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.error || 'Failed to load cost history'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load cost history. Please try again.'
            });
        }
    });
}
</script>

<?php require_once('footer.php'); ?> 