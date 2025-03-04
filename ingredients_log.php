<?php
require_once 'includes/session.php';
require_once 'includes/conn.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('location: login.php');
    exit();
}

// Check if user is admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'Admin') {
    header('location: dashboard.php');
    exit();
}

$page_title = "Ingredients Activity Log";
include 'header.php';
?>

<!-- DataTables CSS -->
<link href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css" rel="stylesheet">

<div class="container-fluid">
    <h1 class="mt-4">Ingredients Activity Log</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item">Ingredients</li>
        <li class="breadcrumb-item active">Activity Log</li>
    </ol>
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-history me-1"></i>
            Ingredient Actions History
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="ingredientsLogTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Source</th>
                            <th>Initial</th>
                            <th>Adjustment</th>
                            <th>Remaining</th>
                            <th>Usage Cost</th>
                            <th>Requested</th>
                            <th>Fulfilled</th>
                            <th>User</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            // Query to get ingredient logs with user information
                            $sql = "SELECT 
                                    il.*, 
                                    u.username as user_name
                                FROM ingredients_log il
                                LEFT JOIN pos_user u ON il.user_id = u.id
                                ORDER BY il.created_at DESC";
                            
                            $stmt = $pdo->query($sql);
                            
                            while($row = $stmt->fetch()) {
                                echo "
                                <tr>
                                    <td>" . date('M d, Y h:i A', strtotime($row['created_at'])) . "</td>
                                    <td>" . htmlspecialchars($row['source']) . "</td>
                                    <td>" . htmlspecialchars($row['initial']) . "</td>
                                    <td>" . htmlspecialchars($row['adjustment']) . "</td>
                                    <td>" . htmlspecialchars($row['remaining']) . "</td>
                                    <td>" . ($row['usage_cost'] ? htmlspecialchars($row['usage_cost']) : '-') . "</td>
                                    <td>" . ($row['requested'] ? htmlspecialchars($row['requested']) : '-') . "</td>
                                    <td>" . ($row['fulfilled'] ? htmlspecialchars($row['fulfilled']) : '-') . "</td>
                                    <td>" . htmlspecialchars($row['user_name']) . "</td>
                                </tr>";
                            }
                        } catch (PDOException $e) {
                            echo "<tr><td colspan='9'>Error loading log data: " . $e->getMessage() . "</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
$(document).ready(function() {
    $('#ingredientsLogTable').DataTable({
        order: [[0, 'desc']], // Sort by date descending by default
        pageLength: 25, // Show 25 entries per page
        responsive: true,
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    });
});
</script>

<?php include 'includes/footer.php'; ?> 