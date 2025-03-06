<?php 

require_once 'db_connect.php';
require_once 'auth_function.php';

checkAdminLogin();

$message = '';
$user_id = (isset($_GET['id'])) ? $_GET['id'] :'';
$user_name = '';
$user_email = '';
$user_status = 'Active';
$user_type = '';

// Fetch the current user data
if (!empty($user_id)) {
    $stmt = $pdo->prepare("SELECT * FROM pos_user WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $user_name = $user["user_name"];
        $user_email = $user["user_email"];
        $user_status = $user["user_status"];
        $user_type = $user["user_type"];
    } else {
        $message = 'User not found.';
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_name = trim($_POST['user_name']);
    $user_email = trim($_POST['user_email']);
    $user_status = trim($_POST['user_status']);
    $user_type = trim($_POST['user_type']);
    
    // Validate inputs
    if (empty($user_name) || empty($user_email) || empty($user_status) || empty($user_type)) {
        $message = 'All fields are required.';
    } elseif (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Invalid email format.';
    } else {
        // Check if email already exists for another user
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM pos_user WHERE user_email = :user_email AND user_id != :user_id");
        $stmt->execute(['user_email' => $user_email, 'user_id' => $user_id]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            $message = 'Email already exists.';
        } else {
            // Update the database
            if (empty($message)) {
                try {
                    $stmt = $pdo->prepare("UPDATE pos_user SET user_name = :user_name, user_email = :user_email, user_status = :user_status, user_type = :user_type WHERE user_id = :user_id");
                    $stmt->execute([
                        'user_name'       => $user_name,
                        'user_email'      => $user_email,
                        'user_status'     => $user_status,
                        'user_type'       => $user_type,
                        'user_id'         => $user_id
                    ]);
                    header('location:user.php');
                } catch (PDOException $e) {
                    $message = 'Database error: ' . $e->getMessage();
                }
            }
        }
    }
}

include('header.php');
?>

<h1 class="mt-4">Edit User</h1>
<ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="user.php">User Management</a></li>
    <li class="breadcrumb-item active">Edit User</li>
</ol>
    <?php
    if(isset($message) && $message !== ''){
        echo '
        <div class="alert alert-danger">
        '.$message.'
        </div>
        ';
    }
    ?>

    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">Edit User</div>
                <div class="card-body">
                    <form method="post" action="edit_user.php?id=<?php echo htmlspecialchars($user_id); ?>">
                        <div class="mb-3">
                            <label for="user_name">Name:</label>
                            <input type="text" id="user_name" name="user_name" class="form-control" value="<?php echo htmlspecialchars($user_name); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="user_email">Email:</label>
                            <input type="email" id="user_email" name="user_email" class="form-control" value="<?php echo htmlspecialchars($user_email); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="user_type">User Type:</label>
                            <select id="user_type" name="user_type" class="form-select">
                                <option value="Admin" <?php echo ($user_type == 'Admin') ? 'selected' : ''; ?>>Admin</option>
                                <option value="Cashier" <?php echo ($user_type == 'Cashier') ? 'selected' : ''; ?>>Cashier</option>
                                <option value="Stockman" <?php echo ($user_type == 'Stockman') ? 'selected' : ''; ?>>Stockman</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="user_status">Status:</label>
                            <select id="user_status" name="user_status" class="form-select">
                                <option value="Active" <?php if (isset($user_status) && $user_status == 'Active') echo 'selected'; ?>>Active</option>
                                <option value="Inactive" <?php if (isset($user_status) && $user_status == 'Inactive') echo 'selected'; ?>>Inactive</option>
                            </select>    
                        </div>
                        <div class="mt-2 text-center">
                            <input type="submit" value="Update User" class="btn btn-primary" />
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php
include('footer.php');
?>