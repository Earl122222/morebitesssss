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

// Fetch categories for dropdown
$sql = "SELECT id, category_name FROM product_categories ORDER BY category_name";
$categories = $conn->query($sql);

if (!$categories) {
    die("Error fetching categories: " . $conn->error);
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Add Product</h1>
    
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="products.php">Product Management</a></li>
            <li class="breadcrumb-item active" aria-current="page">Add Product</li>
        </ol>
    </nav>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-plus me-1"></i>
            Add New Product
        </div>
        <div class="card-body">
            <form id="addProductForm" method="POST" action="process_add_product.php" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-select" id="category" name="category" required>
                        <option value="">Select Category</option>
                        <?php
                        if ($categories && $categories->num_rows > 0) {
                            while ($category = $categories->fetch_assoc()) {
                                echo '<option value="' . $category['id'] . '">' . 
                                     htmlspecialchars($category['category_name']) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="productName" class="form-label">Product Name</label>
                    <input type="text" class="form-control" id="productName" name="productName" required>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                </div>

                <div class="mb-3">
                    <label for="price" class="form-label">Price</label>
                    <input type="number" class="form-control" id="price" name="price" step="0.01" required>
                </div>

                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="Available">Available</option>
                        <option value="Not Available">Not Available</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="image" class="form-label">Product Image</label>
                    <input type="file" class="form-control" id="image" name="image" accept="image/*">
                </div>

                <button type="submit" class="btn btn-primary">Add Product</button>
                <a href="products.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize form validation
    const form = document.getElementById('addProductForm');
    
    form.addEventListener('submit', function(event) {
        event.preventDefault();
        
        // Basic validation
        const category = document.getElementById('category').value;
        const productName = document.getElementById('productName').value;
        const price = document.getElementById('price').value;
        
        if (!category || !productName || !price) {
            alert('Please fill in all required fields');
            return;
        }

        if (parseFloat(price) <= 0) {
            alert('Price must be greater than 0');
            return;
        }
        
        // Create FormData object
        const formData = new FormData(this);
        
        // Disable submit button and show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Adding...';
        
        // Submit form using fetch
        fetch('process_add_product.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                window.location.href = 'product.php';
            } else {
                alert(data.message || 'Error adding product');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while adding the product. Please try again.');
        })
        .finally(() => {
            // Re-enable submit button and restore original text
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });
});
</script>

<?php include 'footer.php'; ?>
