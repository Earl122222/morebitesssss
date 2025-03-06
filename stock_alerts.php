<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Stockman') {
    header('Location: login.php');
    exit;
}

require_once 'config.php';

// Establish database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

include 'header.php';
?>

<div class="container-fluid">
    <h1 class="mt-4">Stock Alerts</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Stock Alerts</li>
    </ol>

    <!-- Out of Stock Items -->
    <div class="card mb-4">
        <div class="card-header bg-danger text-white">
            <i class="fas fa-exclamation-circle"></i>
            Out of Stock Items
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="outOfStockTable">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>Current Stock</th>
                            <th>Min Stock</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Get out of stock items
                        $sql = "SELECT i.ingredient_id, i.ingredient_name, i.quantity, i.unit, i.min_stock, 
                                      i.last_updated, c.category_name 
                               FROM ingredients i 
                               LEFT JOIN categories c ON i.category_id = c.category_id 
                               WHERE i.quantity <= i.min_stock 
                               ORDER BY i.quantity ASC";
                        
                        $result = $conn->query($sql);
                        
                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $stockStatus = $row['quantity'] <= 0 ? 'Out of Stock' : 'Low Stock';
                                $statusClass = $row['quantity'] <= 0 ? 'danger' : 'warning';
                                
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['ingredient_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['category_name']) . "</td>";
                                echo "<td><span class='badge bg-{$statusClass}'>" . $row['quantity'] . " " . $row['unit'] . "</span></td>";
                                echo "<td>" . $row['min_stock'] . " " . $row['unit'] . "</td>";
                                echo "<td>" . $row['last_updated'] . "</td>";
                                echo "<td>
                                        <button class='btn btn-sm btn-success update-stock' data-id='" . $row['ingredient_id'] . "'>
                                            <i class='fas fa-plus-minus'></i> Update Stock
                                        </button>
                                    </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' class='text-center'>No items are out of stock</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Update Stock Modal -->
<div class="modal fade" id="updateStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="updateStockForm">
                    <input type="hidden" id="ingredient_id">
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantity to Add</label>
                        <input type="number" class="form-control" id="quantity" min="0" step="0.01" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveStockUpdate">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#outOfStockTable').DataTable({
        responsive: true,
        order: [[2, 'asc']], // Order by current stock ascending
        pageLength: 25 // Show more items per page
    });

    // Handle Update Stock button click
    $('.update-stock').click(function() {
        const id = $(this).data('id');
        $('#ingredient_id').val(id);
        $('#quantity').val(''); // Clear previous value
        $('#updateStockModal').modal('show');
    });

    // Handle stock update submission
    $('#saveStockUpdate').click(function() {
        const id = $('#ingredient_id').val();
        const quantity = $('#quantity').val();

        if (!quantity || quantity <= 0) {
            alert('Please enter a valid quantity');
            return;
        }

        $.ajax({
            url: 'update_stock.php',
            method: 'POST',
            data: {
                ingredient_id: id,
                quantity: quantity,
                action: 'add'
            },
            success: function(response) {
                if (response.success) {
                    $('#updateStockModal').modal('hide');
                    location.reload(); // Refresh to show updated stock levels
                } else {
                    alert(response.message || 'Failed to update stock');
                }
            },
            error: function() {
                alert('An error occurred while updating stock');
            }
        });
    });

    // Add keyboard shortcut for the modal
    $('#quantity').keypress(function(e) {
        if (e.which === 13) { // Enter key
            e.preventDefault();
            $('#saveStockUpdate').click();
        }
    });
});
</script>

<?php include 'footer.php'; ?> 