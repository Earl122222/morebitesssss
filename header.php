<!DOCTYPE html>
<html lang="en" data-theme="light">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>MoreBites - <?php echo isset($_SESSION['user_type']) ? $_SESSION['user_type'] . ' Dashboard' : 'Login'; ?></title>
        
        <?php
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        ?>
        
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        
        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        
        <!-- DataTables CSS -->
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
        
        <!-- Inter Font -->
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        
        <!-- Custom dashboard styles -->
        <link rel="stylesheet" href="styles/dashboard.css?v=<?php echo time(); ?>">
        
        <!-- Scripts -->
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
        <script src="js/notifications.js"></script>

        <!-- SweetAlert2 -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        
        <style>
            /* Theme Variables */
            :root[data-theme="light"] {
                --bg-primary: #ffffff;
                --bg-secondary: #f8f9fa;
                --text-primary: #212529;
                --text-secondary: #495057;
                --border-color: #dee2e6;
                --card-bg: #ffffff;
                --nav-bg: #343a40;
                --nav-text: #ffffff;
                --header-bg: #ffffff;
                --dropdown-bg: #ffffff;
                --dropdown-hover: #f8f9fa;
                --table-bg: #ffffff;
                --table-hover: #f8f9fa;
                --table-text: #212529;
                --sidebar-bg: #343a40;
                --sidebar-text: #ffffff;
                --sidebar-hover: #4a5056;
                --sidebar-active: #2196F3;
                --input-bg: #ffffff;
                --input-text: #212529;
                --btn-primary: #0d6efd;
                --btn-text: #ffffff;
                --chart-bg: #ffffff;
                --chart-text: #212529;
            }

            :root[data-theme="dark"] {
                --bg-primary: #121212;
                --bg-secondary: #1e1e1e;
                --text-primary: #ffffff;
                --text-secondary: #cccccc;
                --text-muted: #cccccc;
                --border-color: #2d2d2d;
                --card-bg: #1e1e1e;
                --nav-bg: #121212;
                --nav-text: #ffffff;
                --header-bg: #121212;
                --dropdown-bg: #1e1e1e;
                --dropdown-hover: #2d2d2d;
                --table-bg: #1e1e1e;
                --table-hover: #2d2d2d;
                --table-text: #ffffff;
                --table-header-bg: #2d2d2d;
                --table-header-color: #ffffff;
                --table-cell-color: #ffffff;
                --sidebar-bg: #121212;
                --sidebar-text: #ffffff;
                --sidebar-hover: #2d2d2d;
                --sidebar-active: #2196F3;
                --input-bg: #2d2d2d;
                --input-text: #ffffff;
                --btn-primary: #2196F3;
                --btn-text: #ffffff;
                --chart-bg: #1e1e1e;
                --chart-text: #ffffff;
                --link-color: #64b5f6;
                --heading-color: #ffffff;
                --breadcrumb-color: #cccccc;
                --breadcrumb-active: #ffffff;
                --status-active-bg: #198754;
                --status-active-color: #ffffff;
                --pagination-active-bg: #2196F3;
                --pagination-active-color: #ffffff;
                --btn-edit-bg: #ffc107;
                --btn-edit-color: #000000;
                --btn-delete-bg: #dc3545;
                --btn-delete-color: #ffffff;
            }

            /* Apply theme colors */
            body {
                background-color: var(--bg-primary);
                color: var(--text-primary);
                transition: all 0.3s ease;
                min-height: 100vh;
            }

            .app-container {
                background-color: var(--bg-primary);
                min-height: 100vh;
            }

            .app-content {
                background-color: var(--bg-primary);
                min-height: calc(100vh - 60px);
                padding: 20px;
            }

            /* Main Content Area */
            .container-fluid {
                background-color: var(--bg-primary);
                color: var(--text-primary);
            }

            /* Headings */
            h1, h2, h3, h4, h5, h6 {
                color: var(--heading-color);
            }

            /* Links */
            a {
                color: var(--link-color);
                text-decoration: none;
            }

            a:hover {
                color: var(--link-color);
                filter: brightness(120%);
            }

            /* Breadcrumb */
            .breadcrumb {
                background-color: var(--bg-secondary);
                border-radius: 4px;
                padding: 0.75rem 1rem;
            }

            .breadcrumb-item {
                color: var(--breadcrumb-color);
            }

            .breadcrumb-item.active {
                color: var(--breadcrumb-active);
            }

            /* Tables */
            .table {
                color: #000000;
                background-color: var(--table-bg);
                border-color: var(--border-color);
            }

            .table thead th {
                background-color: var(--table-header-bg);
                color: #000000 !important;
                border-bottom-color: var(--border-color);
                font-weight: 600;
            }

            .table tbody td {
                color: #000000 !important;
                border-color: var(--border-color);
            }

            .table-hover tbody tr:hover {
                background-color: var(--table-hover);
                color: #000000 !important;
            }

            /* DataTables specific styles */
            .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_filter,
            .dataTables_wrapper .dataTables_info,
            .dataTables_wrapper .dataTables_processing,
            .dataTables_wrapper .dataTables_paginate {
                color: var(--text-primary) !important;
            }

            .dataTables_wrapper .dataTables_length label,
            .dataTables_wrapper .dataTables_filter label {
                color: var(--text-primary) !important;
            }

            .dataTables_wrapper .dataTables_length select,
            .dataTables_wrapper .dataTables_filter input {
                background-color: var(--input-bg) !important;
                color: var(--input-text) !important;
                border-color: var(--border-color) !important;
            }

            /* Form Elements */
            select, input[type="search"] {
                background-color: var(--input-bg) !important;
                color: var(--input-text) !important;
                border-color: var(--border-color) !important;
            }

            select option {
                background-color: var(--dropdown-bg);
                color: var(--text-primary);
            }

            /* Buttons */
            .btn {
                color: var(--btn-text);
            }

            .btn-primary {
                background-color: var(--btn-primary);
                border-color: var(--btn-primary);
            }

            .btn-danger {
                background-color: #dc3545;
                border-color: #dc3545;
                color: #ffffff;
            }

            .btn-warning {
                background-color: #ffc107;
                border-color: #ffc107;
                color: #000000;
            }

            /* Pagination */
            .pagination .page-link {
                background-color: var(--bg-secondary);
                border-color: var(--border-color);
                color: var(--text-primary);
            }

            .pagination .page-item.active .page-link {
                background-color: var(--btn-primary);
                border-color: var(--btn-primary);
                color: var(--btn-text);
            }

            .pagination .page-item.disabled .page-link {
                background-color: var(--bg-secondary);
                border-color: var(--border-color);
                color: var(--text-secondary);
            }

            /* Sidebar Styles */
            .app-sidebar {
                background-color: var(--sidebar-bg);
                border-right: 1px solid var(--border-color);
            }

            .sidebar-brand span {
                color: var(--sidebar-text);
            }

            .menu-link {
                color: var(--sidebar-text) !important;
            }

            .menu-link:hover {
                background-color: var(--sidebar-hover);
            }

            .menu-link.active {
                background-color: var(--sidebar-active);
            }

            /* Add submenu styles */
            .menu-link.has-submenu {
                position: relative;
            }

            .submenu-indicator {
                position: absolute;
                right: 15px;
                transition: transform 0.3s ease;
            }

            .menu-link.has-submenu.active .submenu-indicator {
                transform: rotate(-180deg);
            }

            .submenu {
                display: none;
                list-style: none;
                padding-left: 34px;
                margin: 0;
                background-color: rgba(0, 0, 0, 0.1);
            }

            .submenu.show {
                display: block;
            }

            .submenu-item {
                margin: 5px 0;
            }

            .submenu-link {
                display: flex;
                align-items: center;
                padding: 8px 15px;
                color: var(--sidebar-text) !important;
                text-decoration: none;
                font-size: 0.9em;
                border-radius: 4px;
                transition: all 0.3s ease;
            }

            .submenu-link:hover {
                background-color: var(--sidebar-hover);
            }

            .submenu-link i {
                margin-right: 10px;
                font-size: 0.9em;
            }

            /* Header Styles */
            .app-header {
                background-color: var(--header-bg);
                border-bottom: 1px solid var(--border-color);
            }

            .header-actions {
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }

            /* Greeting text */
            .welcome-text {
                color: var(--text-primary) !important;
                font-weight: 500;
            }

            .username {
                color: var(--text-primary) !important;
                font-weight: 600;
            }

            /* Theme Switch Styles */
            .theme-switch-wrapper {
                display: flex;
                align-items: center;
                gap: 8px;
                margin-right: 15px;
                padding: 5px;
                border-radius: 20px;
                background: var(--bg-secondary);
            }

            .theme-switch {
                position: relative;
                display: inline-block;
                width: 50px;
                height: 24px;
                margin: 0;
            }

            .theme-switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }

            .slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #ccc;
                transition: .4s;
                border-radius: 24px;
            }

            .slider:before {
                position: absolute;
                content: "";
                height: 18px;
                width: 18px;
                left: 3px;
                bottom: 3px;
                background-color: white;
                transition: .4s;
                border-radius: 50%;
            }

            input:checked + .slider {
                background-color: #2196F3;
            }

            input:checked + .slider:before {
                transform: translateX(26px);
            }

            .fas.fa-sun,
            .fas.fa-moon {
                font-size: 16px;
                color: var(--text-primary) !important;
            }

            /* Realtime Clock */
            .realtime-clock {
                font-size: 1.1rem;
                font-weight: 500;
                color: var(--text-primary) !important;
                padding: 8px 15px;
                border-radius: 6px;
                border: 1px solid var(--border-color);
                background-color: var(--bg-secondary);
            }

            #current-date {
                margin-right: 10px;
                padding-right: 10px;
                border-right: 1px solid var(--border-color);
                color: var(--text-primary) !important;
            }

            #current-time {
                color: var(--text-primary) !important;
            }

            /* New styles for notification and user icons */
            .header-action-btn {
                background: var(--bg-secondary);
                border: 1px solid var(--border-color);
                padding: 8px;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                transition: all 0.3s ease;
            }

            .header-action-btn i {
                font-size: 18px;
                color: var(--text-primary) !important;
            }

            .header-action-btn:hover {
                background-color: var(--dropdown-hover);
            }

            /* Add these new styles */
            .dropdown-menu {
                background-color: var(--dropdown-bg);
                border-color: var(--border-color);
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
                display: none;
                position: absolute;
                right: 0;
                top: 100%;
                min-width: 200px;
                padding: 0.5rem 0;
                margin: 0.125rem 0 0;
                z-index: 1000;
            }

            .dropdown-menu.show {
                display: block;
            }

            .dropdown-item {
                color: var(--text-primary);
                padding: 0.75rem 1rem;
                display: flex;
                align-items: center;
                text-decoration: none;
                white-space: nowrap;
                transition: all 0.2s ease;
            }

            .dropdown-item:hover {
                background-color: var(--dropdown-hover);
                color: var(--text-primary);
            }

            .dropdown-divider {
                border-top: 1px solid var(--border-color);
                margin: 0.5rem 0;
            }

            .dropdown-item i {
                width: 1.25rem;
                color: var(--text-primary);
                margin-right: 0.5rem;
            }

            .user-icon {
                position: relative;
            }

            .notification-icon {
                position: relative;
            }

            .notification-badge {
                position: absolute;
                top: -5px;
                right: -5px;
                background-color: #dc3545;
                color: white;
                border-radius: 50%;
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
                min-width: 1rem;
                text-align: center;
                font-weight: bold;
            }

            .notification-dropdown {
                width: 300px;
                padding: 0;
                max-height: 400px;
                overflow-y: auto;
            }

            .notification-header {
                padding: 0.75rem 1rem;
                border-bottom: 1px solid var(--border-color);
                background-color: var(--bg-secondary);
            }

            .notification-list {
                max-height: 300px;
                overflow-y: auto;
            }

            .notification-item {
                padding: 0.75rem 1rem;
                border-bottom: 1px solid var(--border-color);
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }

            .notification-item:hover {
                background-color: var(--dropdown-hover);
            }

            .notification-item i {
                color: #ffc107;
            }

            .notification-footer {
                border-top: 1px solid var(--border-color);
                padding: 0.5rem;
            }

            .notification-footer a {
                color: var(--text-primary);
                text-decoration: none;
            }

            .notification-footer a:hover {
                background-color: var(--dropdown-hover);
            }

            /* Add these new styles for dark mode text */
            [data-theme="dark"] .text-muted {
                color: var(--text-muted) !important;
            }

            [data-theme="dark"] .notification-item .small {
                color: var(--text-muted) !important;
            }

            [data-theme="dark"] .notification-message strong {
                color: var(--text-primary) !important;
            }

            [data-theme="dark"] .notification-item a {
                color: var(--text-primary) !important;
            }

            [data-theme="dark"] .notification-header h6 {
                color: var(--text-primary) !important;
            }

            [data-theme="dark"] .dropdown-item {
                color: var(--text-primary) !important;
            }

            /* Add these new styles for dark mode text */
            [data-theme="dark"] .dataTables_wrapper .dataTables_length,
            [data-theme="dark"] .dataTables_wrapper .dataTables_filter,
            [data-theme="dark"] .dataTables_wrapper .dataTables_info,
            [data-theme="dark"] .dataTables_wrapper .dataTables_processing,
            [data-theme="dark"] .dataTables_wrapper .dataTables_paginate {
                color: var(--text-primary) !important;
            }

            [data-theme="dark"] .dataTables_wrapper .dataTables_length label,
            [data-theme="dark"] .dataTables_wrapper .dataTables_filter label {
                color: var(--text-primary) !important;
            }
        </style>
        
        <script>
            // Check if CSS is loaded and initialize sidebar functionality
            window.onload = function() {
                const link = document.querySelector('link[href^="styles/dashboard.css"]');
                if (link) {
                    console.log('Dashboard CSS is linked');
                    // Force reload CSS
                    link.href = link.href.split('?')[0] + '?v=' + new Date().getTime();
                }
                
                // User dropdown functionality
                const userButton = document.getElementById('userButton');
                const userDropdown = userButton.nextElementSibling;
                
                if (userButton && userDropdown) {
                    // Toggle dropdown on button click
                    userButton.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        userDropdown.classList.toggle('show');
                        userButton.setAttribute('aria-expanded', userDropdown.classList.contains('show'));
                    });
                    
                    // Close dropdown when clicking outside
                    document.addEventListener('click', (e) => {
                        if (!userButton.contains(e.target) && !userDropdown.contains(e.target)) {
                            userDropdown.classList.remove('show');
                            userButton.setAttribute('aria-expanded', 'false');
                        }
                    });
                }
                
                // Sidebar toggle functionality
                const sidebarToggle = document.querySelector('.sidebar-toggle');
                const sidebar = document.querySelector('.app-sidebar');
                const appMain = document.querySelector('.app-main');
                
                if (sidebarToggle && sidebar && appMain) {
                    sidebarToggle.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        sidebar.classList.toggle('collapsed');
                    });
                }
                
                // Handle active menu item
                const currentPath = window.location.pathname;
                const menuLinks = document.querySelectorAll('.menu-link');
                menuLinks.forEach(link => {
                    if (link.getAttribute('href') === currentPath.split('/').pop()) {
                        link.classList.add('active');
                        // If this is a submenu item, show its parent submenu
                        const parentSubmenu = link.closest('.submenu');
                        if (parentSubmenu) {
                            parentSubmenu.classList.add('show');
                            const parentMenuLink = parentSubmenu.previousElementSibling;
                            if (parentMenuLink) {
                                parentMenuLink.classList.add('active');
                            }
                        }
                    }
                });

                // Handle submenu toggling
                const submenuLinks = document.querySelectorAll('.menu-link.has-submenu');
                submenuLinks.forEach(link => {
                    link.addEventListener('click', (e) => {
                        e.preventDefault();
                        const submenu = link.nextElementSibling;
                        if (submenu) {
                            submenu.classList.toggle('show');
                            link.classList.toggle('active');
                        }
                    });
                });

                // Add hover effect for dropdown items
                const dropdownItems = document.querySelectorAll('.dropdown-item');
                dropdownItems.forEach(item => {
                    item.addEventListener('mouseenter', () => {
                        item.style.backgroundColor = getComputedStyle(document.documentElement).getPropertyValue('--dropdown-hover');
                    });
                    item.addEventListener('mouseleave', () => {
                        item.style.backgroundColor = '';
                    });
                });
            };
        </script>
    </head>
    <body>
        <div class="app-container">
            <aside class="app-sidebar">
                <div class="sidebar-header">
                    <a href="dashboard.php" class="sidebar-brand">
                        <img src="asset/images/logo.png" alt="MoreBites" class="brand-logo">
                        <span>MoreBites</span>
                    </a>
                    <button class="sidebar-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
                <div class="sidebar-menu">
                    <ul class="menu-list">
                        <?php if(isset($_SESSION['user_type'])): ?>
                            <?php if($_SESSION['user_type'] === 'Stockman'): ?>
                                <li class="menu-item">
                                    <a href="stockman_dashboard.php" class="menu-link">
                                        <i class="fas fa-tachometer-alt"></i>
                                        <span>Dashboard</span>
                                    </a>
                                </li>
                                <li class="menu-item">
                                    <a href="inventory.php" class="menu-link">
                                        <i class="fas fa-boxes"></i>
                                        <span>Inventory Management</span>
                                    </a>
                                </li>
                                <li class="menu-item">
                                    <a href="stock_alerts.php" class="menu-link">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <span>Stock Alerts</span>
                                    </a>
                                </li>
                                <li class="menu-item">
                                    <a href="categories.php" class="menu-link">
                                        <i class="fas fa-tags"></i>
                                        <span>Ingredients Categories</span>
                                    </a>
                                </li>
                                <li class="menu-item">
                                    <a href="stock_value.php" class="menu-link">
                                        <i class="fas fa-dollar-sign"></i>
                                        <span>Stock Value</span>
                                    </a>
                                </li>
                            <?php elseif($_SESSION['user_type'] === 'Cashier'): ?>
                                <li class="menu-item">
                                    <a href="cashier_dashboard.php" class="menu-link">
                                        <i class="fas fa-tachometer-alt"></i>
                                        <span>Dashboard</span>
                                    </a>
                                </li>
                                <li class="menu-item">
                                    <a href="order.php" class="menu-link">
                                        <i class="fas fa-history"></i>
                                        <span>Order History</span>
                                    </a>
                                </li>
                            <?php elseif($_SESSION['user_type'] === 'Admin'): ?>
                                <li class="menu-item">
                                    <a href="dashboard.php" class="menu-link">
                                        <i class="fas fa-tachometer-alt"></i>
                                        <span>Dashboard</span>
                                    </a>
                                </li>
                                <li class="menu-item">
                                    <a href="user.php" class="menu-link">
                                        <i class="fas fa-users"></i>
                                        <span>Users</span>
                                    </a>
                                </li>
                                <li class="menu-item">
                                    <a href="ingredient_costs.php" class="menu-link">
                                        <i class="fas fa-coins"></i>
                                        <span>Cost</span>
                                    </a>
                                </li>
                                <li class="menu-item">
                                    <a href="ingredients.php" class="menu-link has-submenu">
                                        <i class="fas fa-mortar-pestle"></i>
                                        <span>Ingredients</span>
                                        <i class="fas fa-chevron-down submenu-indicator"></i>
                                    </a>
                                    <ul class="submenu">
                                        <li class="submenu-item">
                                            <a href="ingredients.php" class="submenu-link">
                                                <i class="fas fa-list"></i>
                                                <span>All Ingredients</span>
                                            </a>
                                        </li>
                                        <li class="submenu-item">
                                            <a href="category.php" class="submenu-link">
                                                <i class="fas fa-th-list"></i>
                                                <span>Ingredients Category</span>
                                            </a>
                                        </li>
                                        <li class="submenu-item">
                                            <a href="low_stock.php" class="submenu-link">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                <span>Low Stock</span>
                                            </a>
                                        </li>
                                        <li class="submenu-item">
                                            <a href="ingredients_log.php" class="submenu-link">
                                                <i class="fas fa-history"></i>
                                                <span>Ingredients Log</span>
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                                <li class="menu-item">
                                    <a href="product.php" class="menu-link has-submenu">
                                        <i class="fas fa-utensils"></i>
                                        <span>Product</span>
                                        <i class="fas fa-chevron-down submenu-indicator"></i>
                                    </a>
                                    <ul class="submenu">
                                        <li class="submenu-item">
                                            <a href="product.php" class="submenu-link">
                                                <i class="fas fa-list"></i>
                                                <span>All Products</span>
                                            </a>
                                        </li>
                                        <li class="submenu-item">
                                            <a href="product_category.php" class="submenu-link">
                                                <i class="fas fa-th-list"></i>
                                                <span>Product Category</span>
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                                <li class="menu-item">
                                    <a href="order.php" class="menu-link">
                                        <i class="fas fa-history"></i>
                                        <span>Order History</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </aside>

            <div class="app-main">
                <header class="app-header">
                    <div class="greeting">
                        <div class="welcome-text" id="greeting-text">
                        </div>
                    </div>
                    <div class="header-actions">
                        <div class="theme-switch-wrapper">
                            <i class="fas fa-sun"></i>
                            <label class="theme-switch">
                                <input type="checkbox" id="theme-toggle">
                                <span class="slider round"></span>
                            </label>
                            <i class="fas fa-moon"></i>
                        </div>
                        <div id="realtime-clock" class="realtime-clock">
                            <span id="current-date"></span>
                            <span id="current-time"></span>
                        </div>
                        <div class="notification-icon">
                            <button class="header-action-btn" type="button" id="notificationButton" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-bell"></i>
                                <span class="notification-badge" id="notification-badge">0</span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationButton">
                                <div class="notification-header">
                                    <h6 class="m-0">Notifications</h6>
                                </div>
                                <div class="notification-list" id="notification-list">
                                    <!-- Notifications will be dynamically inserted here -->
                                </div>
                            </div>
                        </div>
                        <div class="user-icon">
                            <button class="header-action-btn" type="button" id="userButton" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userButton">
                                <li>
                                    <a class="dropdown-item" href="user_profile.php">
                                        <i class="fas fa-user-circle me-2"></i>User Profile
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </header>

                <main class="app-content">
                    <div class="container-fluid px-4 mb-4">

<script>
function updateGreeting() {
    const now = new Date();
    const hour = now.getHours();
    let greeting = '';
    
    if (hour >= 5 && hour < 12) {
        greeting = 'Good Morning';
    } else if (hour >= 12 && hour < 17) {
        greeting = 'Good Afternoon';
    } else {
        greeting = 'Good Evening';
    }
    
    const username = '<?php echo isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Admin'; ?>';
    document.getElementById('greeting-text').innerHTML = greeting + ', <span class="username">' + username + '</span>';
}

function updateClock() {
    const timeElement = document.getElementById('current-time');
    const dateElement = document.getElementById('current-date');
    const now = new Date();
    
    // Format time
    const time = now.toLocaleTimeString('en-US', { 
        hour: '2-digit', 
        minute: '2-digit',
        second: '2-digit',
        hour12: true 
    });
    
    // Format date
    const date = now.toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric'
    });
    
    dateElement.textContent = date;
    timeElement.textContent = time;
    updateGreeting(); // Update greeting with current time
}

// Update clock and greeting immediately and then every second
updateClock();
setInterval(updateClock, 1000);
</script>

<script>
    // Theme Switcher Logic
    document.addEventListener('DOMContentLoaded', function() {
        const themeToggle = document.getElementById('theme-toggle');
        const htmlElement = document.documentElement;
        
        // Check for saved theme preference
        const savedTheme = localStorage.getItem('theme') || 'light';
        htmlElement.setAttribute('data-theme', savedTheme);
        themeToggle.checked = savedTheme === 'dark';

        // Theme switch handler
        themeToggle.addEventListener('change', function(e) {
            const newTheme = e.target.checked ? 'dark' : 'light';
            htmlElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            // Force immediate update of all themed elements
            document.body.style.backgroundColor = getComputedStyle(htmlElement).getPropertyValue('--bg-primary');
            document.body.style.color = getComputedStyle(htmlElement).getPropertyValue('--text-primary');
        });

        // Notification System
        function checkLowStockItems() {
            fetch('notifications.php?fetch=notifications')
                .then(response => response.json())
                .then(data => {
                    const badge = document.getElementById('notification-badge');
                    const notificationList = document.getElementById('notification-list');
                    
                    if (data.success && data.notifications) {
                        // Update badge count
                        badge.textContent = data.count;
                        badge.style.display = data.count > 0 ? 'block' : 'none';
                        
                        // Clear existing notifications
                        notificationList.innerHTML = '';
                        
                        if (data.count === 0) {
                            notificationList.innerHTML = '<div class="notification-item">No notifications</div>';
                            return;
                        }
                        
                        // Add new notifications
                        data.notifications.forEach(item => {
                            const notificationItem = document.createElement('div');
                            notificationItem.className = 'notification-item';
                            
                            notificationItem.innerHTML = `
                                <a href="low_stock.php" class="text-decoration-none">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <div class="notification-message">
                                                <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                                ${item.message}
                                            </div>
                                            <div class="small text-muted">
                                                Current: ${item.quantity} ${item.threshold} (Min: ${item.threshold})
                                            </div>
                                        </div>
                                        <span class="badge ${item.badge === 'danger' ? 'bg-danger' : 'bg-warning'} ms-2">
                                            ${item.status}
                                        </span>
                                    </div>
                                </a>
                            `;
                            
                            notificationList.appendChild(notificationItem);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error checking notifications:', error);
                    const notificationList = document.getElementById('notification-list');
                    notificationList.innerHTML = '<div class="notification-item text-danger">Error loading notifications</div>';
                });
        }

        // Check for notifications immediately and then every 30 seconds
        checkLowStockItems();
        setInterval(checkLowStockItems, 30000);

        // Initialize notification dropdown
        const notificationButton = document.getElementById('notificationButton');
        const notificationDropdown = notificationButton.nextElementSibling;
        
        if (notificationButton && notificationDropdown) {
            notificationButton.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                notificationDropdown.classList.toggle('show');
                notificationButton.setAttribute('aria-expanded', notificationDropdown.classList.contains('show'));
            });
            
            document.addEventListener('click', (e) => {
                if (!notificationButton.contains(e.target) && !notificationDropdown.contains(e.target)) {
                    notificationDropdown.classList.remove('show');
                    notificationButton.setAttribute('aria-expanded', 'false');
                }
            });
        }
    });
</script>