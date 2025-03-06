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

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fetch all products with their categories
$sql = "SELECT p.*, c.category_name 
        FROM pos_product p 
        LEFT JOIN product_categories c ON p.category_id = c.id";
$result = $conn->query($sql);

// Debug information
if (!$result) {
    die("Query failed: " . $conn->error);
}

// Print the SQL query and results for debugging
echo "<!-- SQL Query: " . htmlspecialchars($sql) . " -->";
echo "<!-- Number of rows: " . $result->num_rows . " -->";

if ($result && $result->num_rows > 0) {
    echo "<!-- First row data: -->";
    $first_row = $result->fetch_assoc();
    echo "<!-- ";
    print_r($first_row);
    echo " -->";
    // Reset the result pointer
    $result->data_seek(0);
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Products</h1>
    
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Product Management</li>
        </ol>
    </nav>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['message'];
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Add Product Button -->
    <div class="mb-4">
        <a href="add_product.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Product
        </a>
    </div>

    <!-- Products Table -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Products List
        </div>
        <div class="card-body">
            <table id="productsTable" class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            // Image column
                            echo "<td>";
                            if (!empty($row['product_image'])) {
                                $image_path = $row['product_image'];
                                // Debug image path
                                echo "<!-- Image path: " . $image_path . " -->";
                                echo "<!-- File exists: " . (file_exists($image_path) ? 'true' : 'false') . " -->";
                                
                                if (file_exists($image_path) && is_readable($image_path)) {
                                    echo "<img src='" . htmlspecialchars($image_path) . "' alt='Product Image' style='max-width: 50px; max-height: 50px; object-fit: cover; border-radius: 5px;'>";
                                } else {
                                    echo "<img src='assets/img/no-image.png' alt='No Image' style='max-width: 50px; max-height: 50px; object-fit: cover; border-radius: 5px;'>";
                                    error_log("Product image not found or not readable: " . $image_path);
                                }
                            } else {
                                echo "<img src='assets/img/no-image.png' alt='No Image' style='max-width: 50px; max-height: 50px; object-fit: cover; border-radius: 5px;'>";
                            }
                            echo "</td>";
                            
                            echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
                            echo "<td>" . (isset($row['category_name']) ? htmlspecialchars($row['category_name']) : 'N/A') . "</td>";
                            echo "<td>₱" . number_format($row['product_price'], 2) . "</td>";
                            echo "<td>" . htmlspecialchars($row['product_status']) . "</td>";
                            echo "<td>
                                    <button class='btn btn-sm btn-primary edit-btn' 
                                            data-id='" . $row['product_id'] . "'
                                            onclick=\"window.location.href='edit_product.php?id=" . $row['product_id'] . "'\">
                                        <i class='fas fa-edit'></i> Edit
                                    </button>
                                    <button class='btn btn-sm btn-danger delete-btn'
                                            data-id='" . $row['product_id'] . "'
                                            data-name='" . htmlspecialchars($row['product_name'], ENT_QUOTES) . "'
                                            data-bs-toggle='modal'
                                            data-bs-target='#deleteProductModal'>
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

<!-- Delete Product Modal -->
<div class="modal fade" id="deleteProductModal" tabindex="-1" aria-labelledby="deleteProductModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteProductModalLabel">Delete Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="delete_product.php">
                <div class="modal-body">
                    <input type="hidden" name="product_id" id="delete_product_id">
                    <p>Are you sure you want to delete the product "<span id="delete_product_name"></span>"?</p>
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
    $('#productsTable').DataTable({
        responsive: true,
        order: [[1, 'asc']] // Sort by name by default
    });

    // Handle Delete Button Click
    $('.delete-btn').click(function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        
        $('#delete_product_id').val(id);
        $('#delete_product_name').text(name);
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
});
</script>

<?php include 'footer.php'; ?>
