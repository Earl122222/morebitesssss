<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Stockman') {
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

<div class="container-fluid">
    <h1 class="mt-4">Categories</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="stockman_dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Categories</li>
    </ol>

    <!-- Category Statistics -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <h4 class="mb-0" id="totalCategories">0</h4>
                    <div>Total Categories</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <h4 class="mb-0" id="activeCategories">0</h4>
                    <div>Active Categories</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white mb-4">
                <div class="card-body">
                    <h4 class="mb-0" id="totalItems">0</h4>
                    <div>Total Items</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <h4 class="mb-0" id="avgItemsPerCategory">0</h4>
                    <div>Avg. Items per Category</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Categories Table -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-table me-1"></i>
                Categories
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="fas fa-plus"></i> Add Category
            </button>
        </div>
        <div class="card-body">
            <table id="categoriesTable" class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Category ID</th>
                        <th>Category Name</th>
                        <th>Description</th>
                        <th>Items Count</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT c.*, COUNT(i.ingredient_id) as item_count 
                            FROM categories c 
                            LEFT JOIN ingredients i ON c.category_id = i.category_id 
                            GROUP BY c.category_id 
                            ORDER BY c.category_name";
                    $result = $conn->query($sql);

                    while ($row = $result->fetch_assoc()) {
                        $statusClass = $row['status'] == 'Active' ? 'bg-success' : 'bg-secondary';
                        echo "<tr>
                                <td>{$row['category_id']}</td>
                                <td>{$row['category_name']}</td>
                                <td>{$row['description']}</td>
                                <td>{$row['item_count']}</td>
                                <td><span class='badge {$statusClass}'>{$row['status']}</span></td>
                                <td>
                                    <button class='btn btn-sm btn-primary edit-btn' data-id='{$row['category_id']}'
                                            data-bs-toggle='tooltip' title='Edit Category'>
                                        <i class='fas fa-edit'></i>
                                    </button>
                                    <button class='btn btn-sm btn-info view-items-btn' data-id='{$row['category_id']}'
                                            data-bs-toggle='tooltip' title='View Items'>
                                        <i class='fas fa-list'></i>
                                    </button>
                                    <button class='btn btn-sm btn-danger delete-btn' data-id='{$row['category_id']}'
                                            data-bs-toggle='tooltip' title='Delete Category'>
                                        <i class='fas fa-trash'></i>
                                    </button>
                                </td>
                            </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addCategoryForm">
                    <div class="mb-3">
                        <label for="categoryName" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="categoryName" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-control" id="status">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveCategoryBtn">Save Category</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <!-- Similar structure to Add Category Modal with pre-filled values -->
</div>

<!-- View Items Modal -->
<div class="modal fade" id="viewItemsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Category Items</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table id="itemsTable" class="table table-striped">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Quantity</th>
                            <th>Unit</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="itemsTableBody">
                        <!-- Items will be loaded dynamically -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable
    const categoriesTable = $('#categoriesTable').DataTable({
        responsive: true,
        order: [[1, 'asc']]
    });

    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();

    // Update category statistics
    function updateCategoryStats() {
        $.ajax({
            url: 'get_category_stats.php',
            method: 'GET',
            success: function(response) {
                $('#totalCategories').text(response.total);
                $('#activeCategories').text(response.active);
                $('#totalItems').text(response.items);
                $('#avgItemsPerCategory').text(response.average.toFixed(1));
            }
        });
    }

    // Initial statistics update
    updateCategoryStats();

    // Add Category Form Submission
    $('#saveCategoryBtn').click(function() {
        const categoryName = $('#categoryName').val();
        const description = $('#description').val();
        const status = $('#status').val();

        $.ajax({
            url: 'add_category.php',
            method: 'POST',
            data: {
                name: categoryName,
                description: description,
                status: status
            },
            success: function(response) {
                if (response.success) {
                    $('#addCategoryModal').modal('hide');
                    categoriesTable.ajax.reload();
                    updateCategoryStats();
                    showToast('Success', 'Category added successfully', 'success');
                } else {
                    showToast('Error', 'Failed to add category', 'error');
                }
            }
        });
    });

    // Edit Category Button Click
    $('.edit-btn').click(function() {
        const categoryId = $(this).data('id');
        // Load category details and show edit modal
    });

    // View Items Button Click
    $('.view-items-btn').click(function() {
        const categoryId = $(this).data('id');
        $.ajax({
            url: 'get_category_items.php',
            method: 'GET',
            data: { categoryId: categoryId },
            success: function(response) {
                $('#itemsTableBody').html(response);
                $('#viewItemsModal').modal('show');
            }
        });
    });

    // Delete Category Button Click
    $('.delete-btn').click(function() {
        const categoryId = $(this).data('id');
        if (confirm('Are you sure you want to delete this category?')) {
            $.ajax({
                url: 'delete_category.php',
                method: 'POST',
                data: { categoryId: categoryId },
                success: function(response) {
                    if (response.success) {
                        categoriesTable.ajax.reload();
                        updateCategoryStats();
                        showToast('Success', 'Category deleted successfully', 'success');
                    } else {
                        showToast('Error', response.message || 'Failed to delete category', 'error');
                    }
                }
            });
        }
    });

    // Auto-refresh data every 5 minutes
    setInterval(function() {
        categoriesTable.ajax.reload(null, false);
        updateCategoryStats();
    }, 300000);
});

// Toast notification function
function showToast(title, message, type) {
    // Implement toast notification
}
</script>

<?php include 'footer.php'; ?> 