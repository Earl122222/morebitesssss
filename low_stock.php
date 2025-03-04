<?php
require_once 'db_connect.php';
require_once 'auth_function.php';

checkAdminLogin();

// Fetch low stock ingredients (where ingredient_quantity <= threshold)
$query = "SELECT ingredient_id, ingredient_name, ingredient_quantity, 
          ingredient_unit, threshold, 
          CASE 
            WHEN ingredient_quantity = 0 THEN 'Out of Stock'
            WHEN ingredient_quantity <= threshold * 0.5 THEN 'Critical'
            ELSE 'Low'
          END as status
          FROM ingredients 
          WHERE ingredient_quantity <= threshold 
          ORDER BY 
            CASE 
              WHEN ingredient_quantity = 0 THEN 1
              WHEN ingredient_quantity <= threshold * 0.5 THEN 2
              ELSE 3
            END,
            ingredient_quantity/threshold ASC";
$result = $pdo->query($query);

include('header.php');
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2 class="mb-4">Low Stock Ingredients</h2>
            
            <!-- Status Legend -->
            <div class="mb-4">
                <span class="badge bg-danger me-2">Out of Stock</span>
                <span class="badge bg-warning text-dark me-2">Critical</span>
                <span class="badge bg-info me-2">Low</span>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="lowStockTable" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Current Stock</th>
                                    <th>Threshold</th>
                                    <th>Unit</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['ingredient_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['ingredient_quantity']); ?></td>
                                        <td><?php echo htmlspecialchars($row['threshold']); ?></td>
                                        <td><?php echo htmlspecialchars($row['ingredient_unit']); ?></td>
                                        <td>
                                            <?php
                                            $statusClass = 'bg-info';
                                            if ($row['status'] === 'Out of Stock') {
                                                $statusClass = 'bg-danger';
                                            } elseif ($row['status'] === 'Critical') {
                                                $statusClass = 'bg-warning text-dark';
                                            }
                                            ?>
                                            <span class="badge <?php echo $statusClass; ?>">
                                                <?php echo htmlspecialchars($row['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-primary btn-sm restock-btn" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#restockModal"
                                                    data-id="<?php echo $row['ingredient_id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($row['ingredient_name']); ?>"
                                                    data-unit="<?php echo htmlspecialchars($row['ingredient_unit']); ?>">
                                                <i class="fas fa-plus"></i> Restock
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Restock Modal -->
<div class="modal fade" id="restockModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Restock Ingredient</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="restockForm">
                <div class="modal-body">
                    <input type="hidden" id="ingredientId" name="ingredientId">
                    <div class="mb-3">
                        <label for="ingredientName" class="form-label">Ingredient Name</label>
                        <input type="text" class="form-control" id="ingredientName" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="restockAmount" class="form-label">Amount to Add</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="restockAmount" name="amount" required min="0" step="0.01">
                            <span class="input-group-text" id="unitLabel"></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Restock</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable
    $('#lowStockTable').DataTable({
        order: [[4, 'desc']], // Sort by status by default
        pageLength: 25
    });

    // Handle Restock Modal
    const restockModal = document.getElementById('restockModal');
    restockModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const id = button.getAttribute('data-id');
        const name = button.getAttribute('data-name');
        const unit = button.getAttribute('data-unit');

        restockModal.querySelector('#ingredientId').value = id;
        restockModal.querySelector('#ingredientName').value = name;
        restockModal.querySelector('#unitLabel').textContent = unit;
    });

    // Handle Restock Form Submission
    document.getElementById('restockForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('includes/restock_ingredient.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Ingredient restocked successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while restocking the ingredient.');
        });
    });
});
</script>

<?php
include('footer.php');
?> 