<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("SELECT user_id, user_name, user_password, user_type, user_status FROM pos_user WHERE user_name = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['user_password'])) {
            if ($user['user_status'] !== 'Active') {
                $_SESSION['error'] = 'Your account is not active. Please contact the administrator.';
                header('Location: login.php');
                exit;
            }

            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['user_name'];
            $_SESSION['user_type'] = $user['user_type'];

            // Redirect based on user type
            switch ($user['user_type']) {
                case 'Admin':
                    header('Location: dashboard.php');
                    break;
                case 'Cashier':
                    header('Location: cashier_dashboard.php');
                    break;
                case 'Stockman':
                    header('Location: stockman_dashboard.php');
                    break;
                default:
                    $_SESSION['error'] = 'Invalid user type.';
                    header('Location: login.php');
                    break;
            }
            exit;
        } else {
            $_SESSION['error'] = 'Invalid username or password.';
            header('Location: login.php');
            exit;
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = 'Database error: ' . $e->getMessage();
        header('Location: login.php');
        exit;
    }
} else {
    header('Location: login.php');
    exit;
} 