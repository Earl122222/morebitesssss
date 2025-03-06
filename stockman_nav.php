<?php
// Check if user is logged in and is a Stockman
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Stockman') {
    header('Location: login.php');
    exit;
}

// Get low stock count
$sql = "SELECT COUNT(*) as count FROM ingredients WHERE quantity <= min_stock AND quantity > 0";
$result = $conn->query($sql);
$low_stock_count = $result->fetch_assoc()['count'];
?>
<nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
    <div class="sb-sidenav-menu">
        <div class="nav">
            <div class="sb-sidenav-menu-heading">Core</div>
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'stockman_dashboard.php' ? 'active' : ''; ?>" href="stockman_dashboard.php">
                <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                Dashboard
            </a>
            
            <div class="sb-sidenav-menu-heading">Inventory</div>
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'active' : ''; ?>" href="inventory.php">
                <div class="sb-nav-link-icon"><i class="fas fa-boxes"></i></div>
                Inventory Management
                <?php
                if ($low_stock_count > 0) {
                    echo '<span class="badge bg-warning ms-2">' . $low_stock_count . '</span>';
                }
                ?>
            </a>
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'stock_alerts.php' ? 'active' : ''; ?>" href="stock_alerts.php">
                <div class="sb-nav-link-icon"><i class="fas fa-exclamation-triangle"></i></div>
                Stock Alerts
                <?php
                if ($low_stock_count > 0) {
                    echo '<span class="badge bg-danger ms-2">' . $low_stock_count . '</span>';
                }
                ?>
            </a>
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>" href="categories.php">
                <div class="sb-nav-link-icon"><i class="fas fa-tags"></i></div>
                Categories
            </a>
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'stock_value.php' ? 'active' : ''; ?>" href="stock_value.php">
                <div class="sb-nav-link-icon"><i class="fas fa-dollar-sign"></i></div>
                Stock Value
            </a>
        </div>
    </div>
    <div class="sb-sidenav-footer">
        <div class="small">Logged in as:</div>
        <?php echo htmlspecialchars($_SESSION['user_name']); ?> (Stockman)
    </div>
</nav>

<style>
.nav-link {
    transition: all 0.3s ease;
}
.nav-link:hover {
    transform: translateX(5px);
}
.nav-link.active {
    background-color: rgba(255, 255, 255, 0.1);
}
.badge {
    transition: all 0.3s ease;
}
.nav-link:hover .badge {
    transform: scale(1.1);
}
</style> 