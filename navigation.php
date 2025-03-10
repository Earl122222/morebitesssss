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
            --text-color: #ffffff;
        }
        
        body {
            min-height: 100vh;
            margin: 0;
            display: flex;
            background-color: #f5f5f5;
        }
        
        .sidebar {
            background-color: #8B4513;
            width: 250px;
            min-height: 100vh;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }
        
        .main-content {
            flex-grow: 1;
            padding: 1rem;
            background-color: #ffffff;
        }
        
        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-color);
            text-decoration: none;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 2rem;
            padding: 0.5rem;
        }
        
        .brand:hover {
            color: var(--text-color);
        }
        
        .brand img {
            width: 40px;
            height: 40px;
        }

        .nav-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-item {
            margin-bottom: 0.5rem;
        }

        .nav-link {
            color: var(--text-color) !important;
            padding: 0.75rem 1rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: background-color 0.3s;
            border-radius: 4px;
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

        .submenu {
            list-style: none;
            padding-left: 2.5rem;
            margin-top: 0.5rem;
            display: none;
        }

        .submenu.show {
            display: block;
        }

        .nav-link[data-bs-toggle="collapse"] {
            position: relative;
        }

        .nav-link[data-bs-toggle="collapse"]::after {
            content: '\f107';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            right: 1rem;
            transition: transform 0.3s;
        }

        .nav-link[data-bs-toggle="collapse"][aria-expanded="true"]::after {
            transform: rotate(180deg);
        }

        .toggle-btn {
            color: var(--text-color);
            background: none;
            border: none;
            padding: 0.5rem;
            font-size: 1.5rem;
            cursor: pointer;
            display: none;
        }

        @media (max-width: 768px) {
            .toggle-btn {
                display: block;
            }
            .sidebar {
                position: fixed;
                left: -250px;
                transition: left 0.3s;
                z-index: 1000;
            }
            .sidebar.show {
                left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="d-flex justify-content-between align-items-center">
            <a href="dashboard.php" class="brand">
                <img src="assets/images/logo.png" alt="MoreBites Logo">
                MoreBites
            </a>
            <button class="toggle-btn">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
        <nav>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <?php if ($_SESSION['user_type'] === 'Admin'): ?>
                <li class="nav-item">
                    <a href="users.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i> Users
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-bs-toggle="collapse" data-bs-target="#ingredientsSubmenu">
                        <i class="fas fa-carrot"></i> Ingredients
                    </a>
                    <ul class="submenu collapse" id="ingredientsSubmenu">
                        <li>
                            <a href="ingredients.php" class="nav-link">
                                <i class="fas fa-list"></i> List
                            </a>
                        </li>
                        <li>
                            <a href="ingredient_costs.php" class="nav-link">
                                <i class="fas fa-coins"></i> Costs
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-bs-toggle="collapse" data-bs-target="#productSubmenu">
                        <i class="fas fa-utensils"></i> Product
                    </a>
                    <ul class="submenu collapse" id="productSubmenu">
                        <li>
                            <a href="products.php" class="nav-link">
                                <i class="fas fa-list"></i> List
                            </a>
                        </li>
                        <li>
                            <a href="product_categories.php" class="nav-link">
                                <i class="fas fa-tags"></i> Categories
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a href="order.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'order.php' ? 'active' : ''; ?>">
                        <i class="fas fa-history"></i> Order History
                    </a>
                </li>
            </ul>
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

    <script>
        // Toggle sidebar on mobile
        document.querySelector('.toggle-btn').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
        });

        // Keep submenu open if child is active
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = '<?php echo basename($_SERVER['PHP_SELF']); ?>';
            const submenus = document.querySelectorAll('.submenu');
            
            submenus.forEach(submenu => {
                const links = submenu.querySelectorAll('.nav-link');
                links.forEach(link => {
                    if (link.getAttribute('href') === currentPage) {
                        submenu.classList.add('show');
                        submenu.previousElementSibling.setAttribute('aria-expanded', 'true');
                    }
                });
            });
        });
    </script>
</body>
</html> 