<?php
session_start();
if (!isset($_SESSION['user_type'])) {
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

<!-- Add required scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<style>
.chart-container {
    min-height: 300px;
    height: 100%;
    width: 100%;
    position: relative;
    margin-bottom: 1rem;
}
.card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 1rem;
    height: 100%;
}
.card-body {
    padding: 1.25rem;
    height: 100%;
}
.text-muted {
    color: #6c757d;
}
.stats-value {
    font-size: 2rem;
    font-weight: 600;
    margin: 0.5rem 0;
}
.more-link {
    color: #007bff;
    text-decoration: none;
}
</style>

<div class="container-fluid">
    <!-- Product Statistics Cards -->
    <div class="row mb-4">
        <!-- Total Products -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Count of all products</h6>
                    <?php
                    $sql = "SELECT COUNT(*) as total FROM ingredients";
                    $result = $conn->query($sql);
                    $row = $result->fetch_assoc();
                    ?>
                    <div class="stats-value"><?php echo $row['total']; ?> products</div>
                    <a href="#" class="more-link">More</a>
                </div>
            </div>
        </div>

        <!-- Out of Stock Products -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Products out of stock</h6>
                    <?php
                    $sql = "SELECT COUNT(*) as total FROM ingredients WHERE quantity <= 0";
                    $result = $conn->query($sql);
                    $row = $result->fetch_assoc();
                    ?>
                    <div class="stats-value"><?php echo $row['total']; ?> products</div>
                    <a href="#" class="more-link">More</a>
                </div>
            </div>
        </div>

        <!-- Overstocked Products -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Products overstocked</h6>
                    <?php
                    $sql = "SELECT COUNT(*) as total FROM ingredients WHERE quantity > (min_stock * 2)";
                    $result = $conn->query($sql);
                    $row = $result->fetch_assoc();
                    ?>
                    <div class="stats-value"><?php echo $row['total']; ?> products</div>
                    <a href="#" class="more-link">More</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row">
        <!-- Product Value by Category -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-3">Product value by category</h6>
                    <div class="chart-container">
                        <canvas id="productValueByCategory"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Value by Location -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-3">Product value by location</h6>
                    <div class="chart-container">
                        <canvas id="productValueByLocation"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Count by Category -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-3">Product count by category</h6>
                    <div class="chart-container">
                        <canvas id="productCountByCategory"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Debug flag
    const debug = true;

    // Function to create charts
    function createChart(canvasId, type, labels, data, options = {}) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) {
            console.error(`Canvas element ${canvasId} not found`);
            return;
        }

        const defaultOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                }
            }
        };

        return new Chart(ctx, {
            type: type,
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: [
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF',
                        '#FF9F40'
                    ]
                }]
            },
            options: { ...defaultOptions, ...options }
        });
    }

    // Fetch data and create charts
    fetch('get_dashboard_stats.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text(); // Get the raw text first
        })
        .then(text => {
            try {
                return JSON.parse(text); // Try to parse it as JSON
            } catch (e) {
                console.error('JSON Parse Error:', e);
                console.error('Raw Response:', text);
                throw new Error('Failed to parse JSON response');
            }
        })
        .then(response => {
            if (debug) console.log('API Response:', response);

            if (!response.success || !response.data) {
                throw new Error(response.message || 'Invalid data received from API');
            }

            const data = response.data;

            // Create Product Value by Category Chart
            if (data.categoryValue?.labels?.length > 0) {
                createChart('productValueByCategory', 'doughnut', 
                    data.categoryValue.labels, 
                    data.categoryValue.data,
                    { cutout: '60%' }
                );
            }

            // Create Product Value by Location Chart
            if (data.locationValue?.labels?.length > 0) {
                createChart('productValueByLocation', 'doughnut',
                    data.locationValue.labels,
                    data.locationValue.data,
                    { cutout: '60%' }
                );
            }

            // Create Product Count by Category Chart
            if (data.categoryCount?.labels?.length > 0) {
                createChart('productCountByCategory', 'bar',
                    data.categoryCount.labels,
                    data.categoryCount.data,
                    {
                        indexAxis: 'y',
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                grid: {
                                    display: true,
                                    color: '#f0f0f0'
                                }
                            },
                            y: {
                                grid: { display: false }
                            }
                        }
                    }
                );
            }
        })
        .catch(error => {
            console.error('Error loading chart data:', error);
            document.querySelectorAll('.chart-container').forEach(container => {
                container.innerHTML = `
                    <div class="alert alert-danger">
                        <strong>Error loading chart data:</strong><br>
                        ${error.message}<br>
                        Please check the console for more details.
                    </div>`;
            });
        });
});
</script>

<?php include 'footer.php'; ?>