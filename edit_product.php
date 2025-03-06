<?php

require_once 'db_connect.php';
require_once 'auth_function.php';

checkAdminLogin();

// Fetch category for the dropdown
$sql = "SELECT id, category_name FROM product_categories ORDER BY category_name";
$categories = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$message = '';
$errors = [];
$product_id = (isset($_GET['id'])) ? $_GET['id'] :'';
$category_id = '';
$product_name = '';
$product_image = '';
$product_price = '';
$product_status = 'Available';

if($product_id){
    $stmt = $pdo->prepare("SELECT * FROM pos_product WHERE product_id = :product_id");
    $stmt->execute(['product_id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        $category_id = $product['category_id'];
        $product_name = $product['product_name'];
        $product_image = $product['product_image'];
        $product_price = $product['product_price'];
        $product_status = $product['product_status'];
    } else {
        $message = 'Product not found.';
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $category_id = $_POST['category_id'];
    $product_name = trim($_POST['product_name']);
    $new_product_image = $_FILES['product_image'];
    $product_price = trim($_POST['product_price']);
    $product_status = $_POST['product_status'];
    $destPath = '';

    // Validate fields
    if (empty($category_id)) {
        $errors[] = 'Category is required.';
    }
    if (empty($product_name)) {
        $errors[] = 'Product Name is required.';
    }
    if (empty($product_price)) {
        $errors[] = 'Product Price is required.';
    }
    echo $product_id;
    // Check if Product already exists for another user
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pos_product WHERE product_name = :product_name AND product_id != :product_id");
    $stmt->execute(['product_name' => $product_name, 'product_id' => $product_id]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        $errors[] = 'Product already exists.';
    } else {
        // Handle image upload
        if ($new_product_image['error'] === UPLOAD_ERR_OK) {

            // Define the allowed file types
            $allowedTypes = ['image/jpeg', 'image/png'];

            // Get the uploaded file information
            $fileTmpPath = $new_product_image['tmp_name'];
            $fileName = $new_product_image['name'];
            $fileSize = $new_product_image['size'];
            $fileType = $new_product_image['type'];

            // Validate the file type
            if (in_array($fileType, $allowedTypes)) {
                // Define the upload directory
                $uploadDir = 'uploads/';

                // Generate a unique file name to avoid overwriting
                $uniqueFileName = uniqid('', true) . '-' . basename($fileName);

                // Define the destination path
                $product_image = $uploadDir . $uniqueFileName;

                // Move the uploaded file to the destination directory
                move_uploaded_file($fileTmpPath, $product_image);
            } else {
                $errors[] = "Invalid file type. Only JPG and PNG files are allowed.";
            }
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE pos_product SET category_id = ?, product_name = ?, product_image = ?, product_price = ?, product_status = ? WHERE product_id = ?");
        $stmt->execute([$category_id, $product_name, $product_image, $product_price, $product_status, $product_id]);
        header("Location: product.php");
        exit;
    } else {
        $message = '<ul class="list-unstyled">';
        foreach ($errors as $error) {
            $message .= '<li>' . $error . '</li>';
        }
        $message .= '</ul>';
    }
}

include('header.php');

?>

<h1 class="mt-4">Edit Product</h1>
<ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="product.php">Product Management</a></li>
    <li class="breadcrumb-item active">Edit Product</li>
</ol>
<?php
if($message !== ''){
    echo '<div class="alert alert-danger">'.$message.'</div>';
}
?>
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Edit Product</div>
                <div class="card-body">
                    <form method="POST" action="edit_product.php?id=<?php echo htmlspecialchars($product_id); ?>" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="category_id">Category</label>
                            <select name="category_id" id="category_id" class="form-select">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php if (isset($category_id) && $category_id == $category['id']) echo 'selected'; ?>><?php echo $category['category_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="product_name">Product Name</label>
                            <input type="text" name="product_name" id="product_name" class="form-control" value="<?php echo $product_name; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="product_price">Product Price</label>
                            <input type="number" name="product_price" id="product_price" class="form-control" value="<?php echo $product_price; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="product_status">Status</label>
                            <select name="product_status" id="product_status" class="form-select">
                                <option value="Available" <?php if (isset($product_status) && $product_status == 'Available') echo 'selected'; ?>>Available</option>
                                <option value="Out of Stock" <?php if (isset($product_status) && $product_status == 'Out of Stock') echo 'selected'; ?>>Out of Stock</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="product_image">Image</label>
                            <input type="file" name="product_image" accept="image/*" />
                            <?php if ($product_image) { ?>
                            <div class="mt-2">
                                <img src="<?php echo htmlspecialchars($product_image); ?>" class="img-thumbnail" alt="User Image" width="100">
                            </div>
                        <?php } ?>
                        </div>
                        <div class="mt-4 text-center">
                        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product_id); ?>">
                            <button type="submit" class="btn btn-primary">Edit Product</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include('footer.php');
?>
