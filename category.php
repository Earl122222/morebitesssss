<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header('Location: login.php');
    exit;
}

require_once 'config.php';
include 'header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Category Management</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="admin_dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Category Management</li>
    </ol>

    <!-- Add Category Button -->
    <div class="mb-4">
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="fas fa-plus"></i> Add Category
        </button>
    </div>

    <!-- Categories Table -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Categories
        </div>
        <div class="card-body">
            <table id="categoryTable" class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
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
            <form id="addCategoryForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="categoryName" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="categoryName" name="category_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editCategoryForm">
                <div class="modal-body">
                    <input type="hidden" id="editCategoryId" name="category_id">
                    <div class="mb-3">
                        <label for="editCategoryName" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="editCategoryName" name="category_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="editDescription" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#categoryTable').DataTable({
        ajax: {
            url: 'get_categories.php',
            type: 'GET'
        },
        columns: [
            { data: 'category_id' },
            { data: 'category_name' },
            { data: 'description' },
            { 
                data: 'status',
                render: function(data, type, row) {
                    return '<span class="badge bg-success">Active</span>';
                }
            },
            {
                data: null,
                render: function(data, type, row) {
                    return `
                        <button class="btn btn-sm btn-warning edit-btn" data-id="${row.category_id}">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-danger delete-btn" data-id="${row.category_id}">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    `;
                }
            }
        ],
        order: [[1, 'asc']], // Order by name
        responsive: true
    });

    // Add Category Form Submit
    $('#addCategoryForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'add_category.php',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    $('#addCategoryModal').modal('hide');
                    table.ajax.reload();
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Category added successfully!'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Failed to add category'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while adding the category'
                });
            }
        });
    });

    // Edit Category Button Click
    $('#categoryTable').on('click', '.edit-btn', function() {
        var data = table.row($(this).closest('tr')).data();
        $('#editCategoryId').val(data.category_id);
        $('#editCategoryName').val(data.category_name);
        $('#editDescription').val(data.description);
        $('#editCategoryModal').modal('show');
    });

    // Edit Category Form Submit
    $('#editCategoryForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'edit_category.php',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    $('#editCategoryModal').modal('hide');
                    table.ajax.reload();
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Category updated successfully!'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Failed to update category'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while updating the category'
                });
            }
        });
    });

    // Delete Category Button Click
    $('#categoryTable').on('click', '.delete-btn', function() {
        var categoryId = $(this).data('id');
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
                    url: 'delete_category.php',
                    type: 'POST',
                    data: { category_id: categoryId },
                    success: function(response) {
                        if (response.success) {
                            table.ajax.reload();
                            Swal.fire(
                                'Deleted!',
                                'Category has been deleted.',
                                'success'
                            );
                        } else {
                            Swal.fire(
                                'Error!',
                                response.message || 'Failed to delete category',
                                'error'
                            );
                        }
                    },
                    error: function() {
                        Swal.fire(
                            'Error!',
                            'An error occurred while deleting the category',
                            'error'
                        );
                    }
                });
            }
        });
    });

    // Clear forms when modals are hidden
    $('#addCategoryModal').on('hidden.bs.modal', function() {
        $('#addCategoryForm')[0].reset();
    });

    $('#editCategoryModal').on('hidden.bs.modal', function() {
        $('#editCategoryForm')[0].reset();
    });
});
</script>

<?php include 'footer.php'; ?>
