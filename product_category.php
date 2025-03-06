<?php
session_start();

// Define constant to allow database.php access
define('ALLOW_ACCESS', true);

// Include database configuration
require_once 'config/database.php';

// Establish database connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

include 'header.php';

// Check if user is logged in and is an Admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header('Location: login.php');
    exit;
}

// Initialize response message variables
$response = '';
$response_class = '';

// Handle category operations (Add, Edit, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                if (isset($_POST['category_name']) && !empty($_POST['category_name'])) {
                    $category_name = mysqli_real_escape_string($conn, $_POST['category_name']);
                    $description = isset($_POST['description']) ? mysqli_real_escape_string($conn, $_POST['description']) : '';
                    
                    // Check if category already exists
                    $check_sql = "SELECT * FROM product_categories WHERE category_name = '$category_name'";
                    $check_result = $conn->query($check_sql);
                    
                    if ($check_result->num_rows > 0) {
                        $response = "Category already exists!";
                        $response_class = "alert-danger";
                    } else {
                        $sql = "INSERT INTO product_categories (category_name, description) VALUES ('$category_name', '$description')";
                        if ($conn->query($sql)) {
                            $response = "Category added successfully!";
                            $response_class = "alert-success";
                        } else {
                            $response = "Error adding category: " . $conn->error;
                            $response_class = "alert-danger";
                        }
                    }
                }
                break;

            case 'edit':
                if (isset($_POST['category_id']) && isset($_POST['category_name'])) {
                    $category_id = mysqli_real_escape_string($conn, $_POST['category_id']);
                    $category_name = mysqli_real_escape_string($conn, $_POST['category_name']);
                    $description = isset($_POST['description']) ? mysqli_real_escape_string($conn, $_POST['description']) : '';
                    
                    // Check if the new name already exists for other categories
                    $check_sql = "SELECT * FROM product_categories WHERE category_name = '$category_name' AND id != $category_id";
                    $check_result = $conn->query($check_sql);
                    
                    if ($check_result->num_rows > 0) {
                        $response = "Category name already exists!";
                        $response_class = "alert-danger";
                    } else {
                        $sql = "UPDATE product_categories SET category_name = '$category_name', description = '$description' WHERE id = $category_id";
                        if ($conn->query($sql)) {
                            $response = "Category updated successfully!";
                            $response_class = "alert-success";
                        } else {
                            $response = "Error updating category: " . $conn->error;
                            $response_class = "alert-danger";
                        }
                    }
                }
                break;

            case 'delete':
                if (isset($_POST['category_id'])) {
                    $category_id = mysqli_real_escape_string($conn, $_POST['category_id']);
                    
                    // Check if category is being used by any products
                    $check_sql = "SELECT * FROM products WHERE category_id = $category_id";
                    $check_result = $conn->query($check_sql);
                    
                    if ($check_result->num_rows > 0) {
                        $response = "Cannot delete category. It is being used by products!";
                        $response_class = "alert-danger";
                    } else {
                        $sql = "DELETE FROM product_categories WHERE id = $category_id";
                        if ($conn->query($sql)) {
                            $response = "Category deleted successfully!";
                            $response_class = "alert-success";
                        } else {
                            $response = "Error deleting category: " . $conn->error;
                            $response_class = "alert-danger";
                        }
                    }
                }
                break;
        }
    }
}

// Fetch all categories
$sql = "SELECT * FROM product_categories ORDER BY category_name";
$result = $conn->query($sql);

// Check if table exists
if ($result === false) {
    // Create the table if it doesn't exist
    $create_table_sql = "CREATE TABLE IF NOT EXISTS product_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_name VARCHAR(255) NOT NULL UNIQUE,
        description TEXT
    )";
    
    if ($conn->query($create_table_sql)) {
        // Retry the select query
        $result = $conn->query($sql);
    } else {
        die("Error creating table: " . $conn->error);
    }
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Product Categories</h1>
    
    <!-- Add Category Button -->
    <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
        + Add New Category
    </button>

    <?php if (!empty($response)): ?>
        <div class="alert <?php echo $response_class; ?> alert-dismissible fade show" role="alert">
            <?php echo $response; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Categories Table -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Product Categories
        </div>
        <div class="card-body">
            <table id="categoriesTable" class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Category Name</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['category_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                            echo "<td>
                                    <button class='btn btn-sm btn-primary edit-btn' 
                                            data-id='" . $row['id'] . "'
                                            data-name='" . htmlspecialchars($row['category_name'], ENT_QUOTES) . "'
                                            data-description='" . htmlspecialchars($row['description'], ENT_QUOTES) . "'
                                            data-bs-toggle='modal' 
                                            data-bs-target='#editCategoryModal'>
                                        <i class='fas fa-edit'></i> Edit
                                    </button>
                                    <button class='btn btn-sm btn-danger delete-btn'
                                            data-id='" . $row['id'] . "'
                                            data-name='" . htmlspecialchars($row['category_name'], ENT_QUOTES) . "'
                                            data-bs-toggle='modal'
                                            data-bs-target='#deleteCategoryModal'>
                                        <i class='fas fa-trash'></i> Delete
                                    </button>
                                </td>";
                            echo "</tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCategoryModalLabel">Add New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="category_name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="category_name" name="category_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCategoryModalLabel">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="category_id" id="edit_category_id">
                    <div class="mb-3">
                        <label for="edit_category_name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="edit_category_name" name="category_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Category Modal -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-labelledby="deleteCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteCategoryModalLabel">Delete Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="category_id" id="delete_category_id">
                    <p>Are you sure you want to delete the category "<span id="delete_category_name"></span>"?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable
    $('#categoriesTable').DataTable();

    // Edit Category Modal
    $('.edit-btn').click(function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        var description = $(this).data('description');
        
        $('#edit_category_id').val(id);
        $('#edit_category_name').val(name);
        $('#edit_description').val(description);
    });

    // Delete Category Modal
    $('.delete-btn').click(function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        
        $('#delete_category_id').val(id);
        $('#delete_category_name').text(name);
    });
});
</script>

<?php include 'footer.php'; ?> 