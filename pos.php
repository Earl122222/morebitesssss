<?php
session_start();

// Define constant to allow database.php access
define('ALLOW_ACCESS', true);

require_once 'config/database.php';
require_once 'auth_function.php';

// Check if user is logged in and is a Cashier
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Cashier') {
    header('Location: login.php');
    exit;
}

// Establish database connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

include 'header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Left Column - Products -->
        <div class="col-lg-8 p-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <!-- Search and Categories Bar -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text bg-primary text-white">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" class="form-control" id="searchProduct" placeholder="Search products...">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <select class="form-select" id="categoryFilter">
                                <option value="">All Categories</option>
                                <?php
                                try {
                                    // Debug the categories first
                                    $debug_sql = "SELECT * FROM product_categories";
                                    $debug_result = $conn->query($debug_sql);
                                    error_log("Available categories:");
                                    while ($row = $debug_result->fetch_assoc()) {
                                        error_log("Category ID: {$row['id']}, Name: {$row['category_name']}");
                                    }

                                    $sql = "SELECT id, category_name FROM product_categories ORDER BY category_name";
                                    $result = $conn->query($sql);
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['category_name']) . "</option>";
                                    }
                                } catch (Exception $e) {
                                    error_log("Error loading categories: " . $e->getMessage());
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <!-- Products Grid -->
                    <div class="row g-3" id="productsGrid">
                        <?php
                        try {
                            // Debug products and their categories
                            $debug_sql = "SELECT p.*, c.category_name 
                                        FROM pos_product p 
                                        LEFT JOIN product_categories c ON p.category_id = c.id";
                            $debug_result = $conn->query($debug_sql);
                            error_log("Products and their categories:");
                            while ($row = $debug_result->fetch_assoc()) {
                                error_log("Product: {$row['product_name']}, Category ID: {$row['category_id']}, Category: {$row['category_name']}");
                            }

                            // Modified query to use correct column names
                            $sql = "SELECT 
                                    p.product_id as id,
                                    p.product_name,
                                    p.product_description as description,
                                    p.product_price as price,
                                    p.product_image,
                                    p.product_status,
                                    p.category_id,
                                    c.category_name
                                   FROM pos_product p 
                                   LEFT JOIN product_categories c ON p.category_id = c.id 
                                   WHERE p.product_status = 'Available'
                                   ORDER BY c.category_name, p.product_name";
                            $result = $conn->query($sql);

                            if ($result->num_rows === 0) {
                                echo '<div class="col-12 text-center">No products found</div>';
                            }

                            while ($product = $result->fetch_assoc()) {
                                // Debug output
                                error_log("Loading product: " . json_encode($product));
                                ?>
                                <div class="col-md-4 col-lg-3 product-item" 
                                     data-category="<?php echo $product['category_id']; ?>"
                                     data-category-name="<?php echo htmlspecialchars($product['category_name']); ?>">
                                    <div class="card h-100 product-card" data-id="<?php echo $product['id']; ?>" 
                                         data-name="<?php echo htmlspecialchars($product['product_name']); ?>"
                                         data-price="<?php echo $product['price']; ?>">
                                        <img src="<?php echo !empty($product['product_image']) ? $product['product_image'] : 'assets/img/no-image.jpg'; ?>" 
                                             class="card-img-top product-img" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                                        <div class="card-body">
                                            <h6 class="card-title mb-1"><?php echo htmlspecialchars($product['product_name']); ?></h6>
                                            <p class="card-text text-muted small mb-1"><?php echo htmlspecialchars($product['category_name']); ?></p>
                                            <p class="card-text text-primary fw-bold">₱<?php echo number_format($product['price'], 2); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                        } catch (Exception $e) {
                            error_log("Error loading products: " . $e->getMessage());
                            echo '<div class="col-12 text-center text-danger">Error loading products. Please try again later.</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Order Summary -->
        <div class="col-lg-4 p-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-shopping-cart me-2"></i>Current Order
                    </h5>
                </div>
                <div class="card-body">
                    <div id="orderItems" class="mb-3" style="max-height: 400px; overflow-y: auto;">
                        <!-- Order items will be added here dynamically -->
                    </div>
                    <hr>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span id="subtotal">₱0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tax (12%):</span>
                            <span id="tax">₱0.00</span>
                        </div>
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Total:</span>
                            <span id="total">₱0.00</span>
                        </div>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <label class="form-label">Amount Paid</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" class="form-control" id="amountPaid" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Change</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="text" class="form-control" id="change" readonly>
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-success" id="processOrder">
                            <i class="fas fa-check-circle me-2"></i>Process Order
                        </button>
                        <button class="btn btn-danger" id="clearOrder">
                            <i class="fas fa-times-circle me-2"></i>Clear Order
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quantity Modal -->
<div class="modal fade" id="quantityModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Enter Quantity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="number" class="form-control" id="productQuantity" min="1" value="1">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="addToOrder">Add to Order</button>
            </div>
        </div>
    </div>
</div>

<!-- Add custom styles -->
<style>
.product-card {
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.product-img {
    height: 150px;
    object-fit: cover;
}

#orderItems {
    scrollbar-width: thin;
    scrollbar-color: rgba(0,0,0,0.2) transparent;
}

#orderItems::-webkit-scrollbar {
    width: 6px;
}

#orderItems::-webkit-scrollbar-track {
    background: transparent;
}

#orderItems::-webkit-scrollbar-thumb {
    background-color: rgba(0,0,0,0.2);
    border-radius: 3px;
}

.order-item {
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 10px;
    margin-bottom: 8px;
}

.order-item-quantity {
    min-width: 60px;
    text-align: center;
}

.quantity-btn {
    padding: 0px 8px;
    font-size: 14px;
}
</style>

<?php include 'footer.php'; ?>

<script>
$(document).ready(function() {
    let currentOrder = [];
    const TAX_RATE = 0.12;

    // Search functionality
    $('#searchProduct').on('input', function() {
        let search = $(this).val().toLowerCase();
        $('.product-item').each(function() {
            let productName = $(this).find('.card-title').text().toLowerCase();
            $(this).toggle(productName.includes(search));
        });
    });

    // Category filter with debug logging
    $('#categoryFilter').change(function() {
        let categoryId = $(this).val();
        let categoryName = $(this).find('option:selected').text();
        console.log('Selected category ID:', categoryId);
        console.log('Selected category name:', categoryName);
        
        if (!categoryId) {
            // Show all products when "All Categories" is selected
            $('.product-item').show();
            return;
        }
        
        // Convert categoryId to number for comparison
        categoryId = parseInt(categoryId);
        
        $('.product-item').each(function() {
            let productCategory = $(this).data('category');
            // Convert product category to number for comparison
            productCategory = parseInt(productCategory) || 0;
            
            console.log('Product:', $(this).find('.card-title').text());
            console.log('Product category:', productCategory);
            console.log('Comparing with:', categoryId);
            
            if (productCategory === categoryId) {
                $(this).show();
                console.log('Showing product');
            } else {
                $(this).hide();
                console.log('Hiding product');
            }
        });
    });

    // Handle product card click
    $('.product-card').click(function() {
        let productId = $(this).data('id');
        let productName = $(this).data('name');
        let productPrice = parseFloat($(this).data('price'));
        
        console.log('Product clicked:', { id: productId, name: productName, price: productPrice });
        
        $('#quantityModal').modal('show');
        // Store the selected product data in the modal
        $('#quantityModal').data('selectedProduct', {
            id: productId,
            name: productName,
            price: productPrice
        });
    });

    // Add to order
    $('#addToOrder').click(function() {
        let selectedProduct = $('#quantityModal').data('selectedProduct');
        let quantity = parseInt($('#productQuantity').val());
        
        console.log('Adding to order:', selectedProduct, 'Quantity:', quantity);
        
        if (!selectedProduct) {
            console.error('No product selected');
            return;
        }
        
        if (quantity > 0) {
            let existingItem = currentOrder.find(item => item.id === selectedProduct.id);
            
            if (existingItem) {
                existingItem.quantity += quantity;
            } else {
                currentOrder.push({
                    id: selectedProduct.id,
                    name: selectedProduct.name,
                    price: selectedProduct.price,
                    quantity: quantity
                });
            }
            
            console.log('Current order:', currentOrder);
            updateOrderDisplay();
            $('#quantityModal').modal('hide');
            $('#productQuantity').val(1);
        }
    });

    // Update quantity buttons in order
    $(document).on('click', '.quantity-btn', function() {
        let index = $(this).closest('.order-item').data('index');
        let change = $(this).hasClass('btn-decrease') ? -1 : 1;
        
        if (currentOrder[index].quantity + change > 0) {
            currentOrder[index].quantity += change;
            updateOrderDisplay();
        }
    });

    // Remove item
    $(document).on('click', '.remove-item', function() {
        let index = $(this).closest('.order-item').data('index');
        currentOrder.splice(index, 1);
        updateOrderDisplay();
    });

    // Clear order
    $('#clearOrder').click(function() {
        if (currentOrder.length > 0) {
            if (confirm('Are you sure you want to clear the current order?')) {
                currentOrder = [];
                updateOrderDisplay();
            }
        }
    });

    // Calculate change
    $('#amountPaid').on('input', function() {
        let amountPaid = parseFloat($(this).val()) || 0;
        let total = calculateTotal();
        let change = amountPaid - total;
        $('#change').val(change >= 0 ? change.toFixed(2) : '0.00');
    });

    // Process order
    $('#processOrder').click(function() {
        if (currentOrder.length === 0) {
            alert('Please add items to the order first.');
            return;
        }

        let amountPaid = parseFloat($('#amountPaid').val()) || 0;
        let total = calculateTotal();

        if (amountPaid < total) {
            alert('Insufficient amount paid.');
            return;
        }

        // Disable the process button and show loading state
        let $btn = $(this);
        let originalText = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Processing...');

        // Prepare the order data
        let orderData = {
            order: currentOrder.map(item => ({
                id: parseInt(item.id),
                name: item.name,
                price: parseFloat(item.price),
                quantity: parseInt(item.quantity)
            })),
            amount_paid: amountPaid,
            total: total
        };

        console.log('Sending order data:', orderData);

        // Send order to server
        $.ajax({
            url: 'process_order.php',
            method: 'POST',
            dataType: 'json',
            data: JSON.stringify(orderData),
            contentType: 'application/json',
            success: function(response) {
                console.log('Server response:', response);
                if (response.success) {
                    // Show success message with order details
                    Swal.fire({
                        icon: 'success',
                        title: 'Order Processed!',
                        html: `
                            <div class="text-start">
                                <p>Order #${response.order_id}</p>
                                <p>Total: ₱${response.total.toFixed(2)}</p>
                                <p>Amount Paid: ₱${amountPaid.toFixed(2)}</p>
                                <p>Change: ₱${response.change.toFixed(2)}</p>
                            </div>
                        `,
                        confirmButtonText: 'Print Receipt',
                        showCancelButton: true,
                        cancelButtonText: 'Close'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Print receipt
                            window.open(`print_receipt.php?order_id=${response.order_id}`, '_blank');
                        }
                    });

                    // Clear the order
                    currentOrder = [];
                    updateOrderDisplay();
                    $('#amountPaid').val('');
                    $('#change').val('');
                } else {
                    // Show error message
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Failed to process order. Please try again.'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText
                });
                // Show error message
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to process order. Please try again.'
                });
            },
            complete: function() {
                // Re-enable the process button
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });

    function updateOrderDisplay() {
        let orderHtml = '';
        let subtotal = 0;

        currentOrder.forEach((item, index) => {
            let itemTotal = item.price * item.quantity;
            subtotal += itemTotal;

            orderHtml += `
                <div class="order-item" data-index="${index}">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">${item.name}</h6>
                        <button class="btn btn-sm btn-outline-danger remove-item">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <button class="btn btn-sm btn-outline-secondary quantity-btn btn-decrease me-2">-</button>
                            <span class="order-item-quantity">${item.quantity}</span>
                            <button class="btn btn-sm btn-outline-secondary quantity-btn btn-increase ms-2">+</button>
                        </div>
                        <div class="text-end">
                            <div>₱${item.price.toFixed(2)}</div>
                            <div class="text-primary">₱${itemTotal.toFixed(2)}</div>
                        </div>
                    </div>
                </div>
            `;
        });

        $('#orderItems').html(orderHtml || '<p class="text-muted text-center">No items in order</p>');

        let tax = subtotal * TAX_RATE;
        let total = subtotal + tax;

        $('#subtotal').text('₱' + subtotal.toFixed(2));
        $('#tax').text('₱' + tax.toFixed(2));
        $('#total').text('₱' + total.toFixed(2));

        // Recalculate change
        $('#amountPaid').trigger('input');
    }

    function calculateTotal() {
        let subtotal = currentOrder.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        return subtotal + (subtotal * TAX_RATE);
    }
});
</script> 