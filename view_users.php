<?php
// Start session at the beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!file_exists('db_connect.php')) {
    die('Database connection file not found.');
}

require_once 'db_connect.php';
require_once 'db_functions.php'; // Include the db_functions.php file

try {
    // Ensure connection is active
    $pdo = checkConnection($pdo);

    // Fetch all users from the pos_user table
    $stmt = $pdo->prepare("SELECT * FROM pos_user");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Users</title>
    <link href="asset/vendor/bootstrap/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">User List</h1>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                    <td><?php echo htmlspecialchars($user['user_email']); ?></td>
                    <td><?php echo htmlspecialchars($user['user_name']); ?></td>
                    <td><?php echo htmlspecialchars($user['user_type']); ?></td>
                    <td><?php echo htmlspecialchars($user['user_status']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
