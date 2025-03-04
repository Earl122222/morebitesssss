<?php 

require_once 'db_connect.php';
require_once 'auth_function.php';
require_once 'db_functions.php';

checkAdminLogin();

$message = '';
$success_message = '';

$user_name = '';
$user_email = '';
$user_password = '';
$user_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_name = trim($_POST['user_name']);
    $user_email = trim($_POST['user_email']);
    $user_password = trim($_POST['user_password']);
    $user_type = trim($_POST['user_type']);
    
    // Validate inputs
    if (empty($user_name) || empty($user_email) || empty($user_password) || empty($user_type)) {
        $message = 'All fields are required.';
    } elseif (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Invalid email format.';
    } else {
        // Sanitize password input
        $user_password = sanitizePassword($user_password);
        
        // Validate password strength
        $password_validation = validatePasswordStrength($user_password);
        if (!$password_validation['valid']) {
            $message = $password_validation['message'];
        } else {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM pos_user WHERE user_email = :user_email");
            $stmt->execute(['user_email' => $user_email]);
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                $message = 'Email already exists.';
            } else {
                // Hash the password using secure function
                $hashed_password = hashPassword($user_password);
                
                try {
                    // Create active user directly
                    $stmt = $pdo->prepare("INSERT INTO pos_user (user_name, user_email, user_password, user_type, user_status, created_at) 
                                         VALUES (:user_name, :user_email, :user_password, :user_type, 'Active', NOW())");
                    
                    $stmt->execute([
                        'user_name' => $user_name,
                        'user_email' => $user_email,
                        'user_password' => $hashed_password,
                        'user_type' => $user_type
                    ]);
                    
                    $success_message = "User account created successfully!";
                    
                    // Redirect after short delay
                    header("refresh:2;url=user.php");
                    
                } catch (PDOException $e) {
                    error_log('Error in creating user: ' . $e->getMessage());
                    $message = 'Error creating user account. Please try again.';
                }
            }
        }
    }
}

include('header.php');
?>

<h1 class="mt-4">Add User</h1>
<ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="user.php">User Management</a></li>
    <li class="breadcrumb-item active">Add User</li>
</ol>
    <?php
    if(isset($message) && $message !== ''){
        echo '
        <div class="alert alert-danger">
        '.$message.'
        </div>
        ';
    }
    if(isset($success_message) && $success_message !== ''){
        echo '
        <div class="alert alert-success">
        '.$success_message.'
        </div>
        ';
    }
    ?>
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">Add User</div>
                <div class="card-body">
                    <form method="post" action="add_user.php" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="user_name">Name:</label>
                            <input type="text" id="user_name" name="user_name" class="form-control" value="<?php echo htmlspecialchars($user_name); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="user_email">Email:</label>
                            <input type="email" id="user_email" name="user_email" class="form-control" value="<?php echo htmlspecialchars($user_email); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="user_password">Password:</label>
                            <div class="password-container position-relative">
                                <input type="password" id="user_password" name="user_password" class="form-control" value="<?php echo htmlspecialchars($user_password); ?>">
                                <span class="password-toggle" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                            <small class="form-text text-muted">
                                Password must be at least 8 characters long and contain:
                                <ul>
                                    <li>At least one uppercase letter</li>
                                    <li>At least one lowercase letter</li>
                                    <li>At least one number</li>
                                    <li>At least one special character</li>
                                    <li>No repeated characters</li>
                                </ul>
                            </small>
                        </div>
                        <div class="mb-3">
                            <label for="user_type">User Type:</label>
                            <select id="user_type" name="user_type" class="form-control">
                                <option value="Admin" <?php echo ($user_type == 'Admin') ? 'selected' : ''; ?>>Admin</option>
                                <option value="Cashier" <?php echo ($user_type == 'Cashier') ? 'selected' : ''; ?>>Cashier</option>
                                <option value="Stockman" <?php echo ($user_type == 'Stockman') ? 'selected' : ''; ?>>Stockman</option>
                            </select>
                        </div>
                        <div class="mt-2 text-center">
                            <input type="submit" value="Add User" class="btn btn-primary" />
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<style>
.password-container {
    position: relative;
}

.password-toggle {
    color: #6c757d;
    transition: color 0.3s ease;
}

.password-toggle:hover {
    color: #343a40;
}

.password-toggle.active {
    color: #007bff;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const passwordToggle = document.querySelector('.password-toggle');
    const passwordInput = document.getElementById('user_password');
    
    if (passwordToggle && passwordInput) {
        passwordToggle.addEventListener('click', function() {
            // Toggle password visibility
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.classList.add('active');
                this.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                passwordInput.type = 'password';
                this.classList.remove('active');
                this.innerHTML = '<i class="fas fa-eye"></i>';
            }
        });
    }
});
</script>

<?php
include('footer.php');
?>