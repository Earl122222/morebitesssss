<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_type'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoreBites</title>
    
    <!-- Include Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Include Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #8B4513;
            --hover-color: rgba(255, 255, 255, 0.1);
        }
        
        body {
            min-height: 100vh;
            margin: 0;
            display: flex;
        }
        
        .sidebar {
            background-color: var(--primary-color);
            width: 250px;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }
        
        .main-content {
            flex-grow: 1;
            padding: 1rem;
            background-color: #f8f9fa;
        }
        
        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
            text-decoration: none;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 2rem;
        }
        
        .brand img {
            width: 40px;
            height: 40px;
        }
        
        .nav-link {
            color: white !important;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .nav-link:hover {
            background-color: var(--hover-color);
        }
        
        .nav-link.active {
            background-color: var(--hover-color);
        }
        
        .nav-link i {
            width: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <a href="dashboard.php" class="brand">
            <img src="assets/images/logo.png" alt="MoreBites Logo">
            MoreBites
        </a>
        
        <nav>
            <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="order.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'order.php' ? 'active' : ''; ?>">
                <i class="fas fa-history"></i> Order History
            </a>
        </nav>
    </div>

    <div class="main-content">
        <!-- Content will be injected here -->
        <?php if(isset($content)) echo $content; ?>
    </div>

    <!-- Include jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Include Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 