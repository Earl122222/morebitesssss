<?php
// Start session at the beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!file_exists('db_connect.php')) {
    header('Location: install.php');
    exit;
}

require_once 'db_connect.php';
require_once 'auth_function.php';
require_once 'db_functions.php'; // Include the db_functions.php file

// Function to check and reconnect if needed
function checkConnection($pdo) {
    try {
        $pdo->query('SELECT 1');
    } catch (PDOException $e) {
        if ($e->getCode() == 'HY000' || strpos($e->getMessage(), 'server has gone away') !== false) {
            // Reconnect
            require 'db_connect.php';
            return $GLOBALS['pdo'];
        }
    }
    return $pdo;
}

redirectIfLoggedIn();

$errors = [];
$email = isset($_POST['user_email']) ? trim($_POST['user_email']) : '';
$isLocked = false;
$time_remaining = 0;

// Always show CAPTCHA
$show_captcha = true;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['user_email']);
    
    // Check for login attempts and lockout first
    try {
        // Ensure connection is active
        $pdo = checkConnection($pdo);

        // Clean up old attempts
        $cleanup = $pdo->prepare("DELETE FROM login_attempts WHERE timestamp < DATE_SUB(NOW(), INTERVAL 2 MINUTE)");
        $cleanup->execute();

        // Check recent attempts
        $check_attempts = $pdo->prepare("SELECT COUNT(*) as attempts, MAX(timestamp) as last_attempt FROM login_attempts WHERE email = ? AND timestamp > DATE_SUB(NOW(), INTERVAL 1 MINUTE)");
        $check_attempts->execute([$email]);
        $attempt_result = $check_attempts->fetch(PDO::FETCH_ASSOC);
        $attempt_count = $attempt_result['attempts'];
        
        if ($attempt_count >= 3) {
            $last_attempt = strtotime($attempt_result['last_attempt']);
            $current_time = time();
            $time_passed = $current_time - $last_attempt;
            $time_remaining = max(120 - $time_passed, 0); // Ensure time_remaining is never negative
            
            if ($time_remaining > 0) {
                $isLocked = true;
                $errors[] = "Too many failed attempts. Account is now locked for 2 minutes.";
                $errors[] = "Please wait " . gmdate("i:s", $time_remaining) . " before trying again.";
                goto show_form;
            }
        }
    } catch (PDOException $e) {
        error_log('PDOException during login attempts check: ' . $e->getMessage());
        if ($e->getCode() == 'HY000' || strpos($e->getMessage(), 'server has gone away') !== false) {
            $errors[] = "Connection lost. Please try again.";
        } else {
            $errors[] = "System error. Please try again later.";
        }
        goto show_form;
    }

    // Only process login if not locked
    if (!$isLocked) {
        $password = trim($_POST['user_password']);
        $user_captcha = isset($_POST['captcha']) ? trim($_POST['captcha']) : '';

        // Validate email format
        if (empty($email)) {
            $errors[] = "Email is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        }

        // Validate password
        if (empty($password)) {
            $errors[] = "Password is required.";
        }

        // Validate CAPTCHA
        if (empty($user_captcha)) {
            $errors[] = "CAPTCHA is required.";
        } elseif (!isset($_SESSION['captcha_code'])) {
            $errors[] = "CAPTCHA session expired. Please try again.";
        } elseif ($user_captcha !== $_SESSION['captcha_code']) {
            // Record CAPTCHA failure attempt
            $record_attempt = $pdo->prepare("INSERT INTO login_attempts (email, timestamp) VALUES (?, NOW())");
            $record_attempt->execute([$email]);
            
            // Check attempts after CAPTCHA failure
            $check_attempts->execute([$email]);
            $new_attempt_count = $check_attempts->fetch(PDO::FETCH_ASSOC)['attempts'];
            
            $errors[] = "Invalid CAPTCHA code. Please match the exact case (uppercase/lowercase) as shown.";
            
            if ($new_attempt_count >= 3) {
                $errors[] = "Too many failed attempts. Account is now locked for 2 minutes.";
                $isLocked = true;
                $last_attempt = strtotime($attempt_result['last_attempt']);
                $time_passed = time() - $last_attempt;
                $time_remaining = max(120 - $time_passed, 0);
                $minutes = floor($time_remaining / 60);
                $seconds = $time_remaining % 60;
                $formatted_time = sprintf("%02d:%02d", $minutes, $seconds);
                $errors[] = "Please wait {$formatted_time} before trying again.";
                goto show_form;
            } else {
                $remaining_attempts = 3 - $new_attempt_count;
                $errors[] = "CAPTCHA verification failed. {$remaining_attempts} attempts remaining before lockout.";
            }
            goto show_form;
        }

        if (empty($errors)) {
            try {
                // Ensure connection is active before login attempt
                $pdo = checkConnection($pdo);
                
                $stmt = $pdo->prepare("SELECT * FROM pos_user WHERE user_email = ? AND user_status = 'Active'");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Debug: Log the user data
                error_log('User data from database: ' . print_r($user, true));
                
                if ($user) {
                    if (password_verify($password, $user['user_password'])) {
                        // Debug: Log password verification success
                        error_log('Password verification successful for user: ' . $user['user_email']);
                        
                        // Ensure connection is active before clearing attempts
                        $pdo = checkConnection($pdo);
                        
                        // Clear login attempts on successful login
                        $clear_attempts = $pdo->prepare("DELETE FROM login_attempts WHERE email = ?");
                        $clear_attempts->execute([$email]);
                        
                        // Store session variables
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['user_type'] = $user['user_type'];
                        $_SESSION['user_logged_in'] = true;
                        $_SESSION['user_name'] = $user['user_name'];
                        
                        // Clear CAPTCHA session after successful login
                        unset($_SESSION['captcha_code']);
                        
                        // Debug: Log the session data
                        error_log('Login successful. Session data: ' . print_r($_SESSION, true));
                        
                        // Redirect to dashboard
                        header('Location: dashboard.php');
                        exit();
                    } else {
                        // Debug: Log password verification failure
                        error_log('Password verification failed for user: ' . $user['user_email']);
                        $errors[] = "Invalid password.";
                    }
                } else {
                    // Debug: Log user not found or inactive
                    error_log('User not found or inactive for email: ' . $email);
                    $errors[] = "Invalid email or account inactive.";
                }

                // Ensure connection is active before recording attempt
                $pdo = checkConnection($pdo);
                
                // Record failed attempt
                $record_attempt = $pdo->prepare("INSERT INTO login_attempts (email, timestamp) VALUES (?, NOW())");
                $record_attempt->execute([$email]);

                // Check if this attempt should trigger lockout
                $check_attempts->execute([$email]);
                $new_attempt_count = $check_attempts->fetch(PDO::FETCH_ASSOC)['attempts'];
                if ($new_attempt_count >= 3) {
                    $errors[] = "Too many failed attempts. Account is now locked for 2 minutes.";
                    $isLocked = true;
                    $last_attempt = strtotime($attempt_result['last_attempt']);
                    $time_passed = time() - $last_attempt;
                    $time_remaining = max(120 - $time_passed, 0);
                    $minutes = floor($time_remaining / 60);
                    $seconds = $time_remaining % 60;
                    $formatted_time = sprintf("%02d:%02d", $minutes, $seconds);
                    $errors[] = "Please wait {$formatted_time} before trying again.";
                    goto show_form;
                } else {
                    $remaining_attempts = 3 - $new_attempt_count;
                    $errors[] = "Login failed. {$remaining_attempts} attempts remaining before lockout.";
                }
            } catch (PDOException $e) {
                // Log the specific PDO exception message
                error_log('PDOException during login attempt: ' . $e->getMessage());
                if ($e->getCode() == 'HY000' || strpos($e->getMessage(), 'server has gone away') !== false) {
                    $errors[] = "Connection lost. Please try again.";
                } else {
                    $errors[] = "Database error. Please try again later.";
                }
            }
        }
    }
}

// Add this label before the HTML part
show_form:
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>More Bites</title>
    <link href="asset/vendor/bootstrap/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="styles/login.css" rel="stylesheet" type="text/css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://db.onlinewebfonts.com/c/07bc241768c969f6b6a27a7bf0dfb490?family=Cooper+Black" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/login.js"></script>
</head>
<body>
    <main>
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-4">
                    <div class="card custom-card shadow-lg">
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <!-- Logo image located in asset/images -->
                                <img src="asset/images/logo.png" alt="Logo" class="logo mb-3">
                            </div>
                            <h2 class="text-center text-white">Welcome to MoreBites!</h2>
                            <form method="post" action="">
                                <div class="mb-3">
                                    <label for="user_email" class="form-label">Username:</label>
                                    <input type="email" id="user_email" name="user_email" class="form-control" required value="<?php echo htmlspecialchars($email); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="user_password" class="form-label">Password:</label>
                                    <div class="password-container position-relative">
                                        <input type="password" id="user_password" name="user_password" class="form-control" required>
                                        <span class="password-toggle">
                                            <svg class="eye-icon" viewBox="0 0 24 24" width="24" height="24">
                                                <path class="eye-open" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                <circle class="eye-open" cx="12" cy="12" r="3"/>
                                                <path class="eye-closed" d="M2 2l20 20"/>
                                                <path class="eye-closed" d="M6.71 6.71C3.93 8.59 2 12 2 12s4.5 7 10 7c2.1 0 3.98-.63 5.29-1.71"/>
                                                <path class="eye-closed" d="M15.5 15.5c-.88.52-1.91.82-3 .82-3.31 0-6-2.69-6-6 0-1.09.3-2.12.82-3"/>
                                            </svg>
                                        </span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">CAPTCHA:</label>
                                    <div class="captcha-container">
                                        <div class="captcha-text">
                                            <?php 
                                            // Generate CAPTCHA code with mixed case
                                            $chars = '23456789abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
                                            $captcha_string = '';
                                            for ($i = 0; $i < 6; $i++) {
                                                $captcha_string .= $chars[rand(0, strlen($chars) - 1)];
                                            }
                                            $_SESSION['captcha_code'] = $captcha_string;
                                            
                                            // Display CAPTCHA with random colors
                                            $colors = ['#3498db', '#e74c3c', '#2ecc71', '#f1c40f', '#9b59b6', '#34495e'];
                                            for ($i = 0; $i < strlen($captcha_string); $i++) {
                                                $color = $colors[rand(0, count($colors)-1)];
                                                echo '<span style="color: '.$color.'; transform: rotate('.rand(-5, 5).'deg) translateY('.rand(-2, 2).'px); display: inline-block;">'
                                                    .htmlspecialchars($captcha_string[$i]).'</span>';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <input type="text" name="captcha" class="form-control mt-2" placeholder="Enter the code exactly as shown above" required>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <span class="button-content">
                                        <span class="button-text">Login</span>
                                        <div class="spinner-wrapper d-none">
                                            <i class="fas fa-pizza-slice pizza-spinner"></i>
                                        </div>
                                    </span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Error Modal -->
    <?php if (!empty($errors)): ?>
    <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content error-modal">
                <div class="modal-header">
                    <h5 class="modal-title" id="errorModalLabel">Login Error</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php foreach ($errors as $error): ?>
                        <?php if (!strpos($error, "Please wait")): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php if ($isLocked && $time_remaining > 0): ?>
                        <div class="countdown-timer mt-3 text-center">
                            <div class="timer-display">
                                <div class="clock-face">
                                    <div class="clock-marks">
                                        <?php for($i = 0; $i < 12; $i++): ?>
                                            <div class="mark" style="transform: rotate(<?php echo $i * 30; ?>deg)"></div>
                                        <?php endfor; ?>
                                    </div>
                                    <span id="timer" class="time-text">--:--</span>
                                    <svg class="progress-ring" width="120" height="120">
                                        <circle class="progress-ring-circle" stroke="#e74c3c" stroke-width="4" fill="transparent" r="52" cx="60" cy="60"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <style>
                            .countdown-timer {
                                display: flex;
                                justify-content: center;
                                align-items: center;
                                padding: 20px;
                            }
                            
                            .clock-face {
                                position: relative;
                                width: 120px;
                                height: 120px;
                                display: flex;
                                justify-content: center;
                                align-items: center;
                                background: rgba(0, 0, 0, 0.2);
                                border-radius: 50%;
                                box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
                                border: 2px solid rgba(255, 255, 255, 0.1);
                            }

                            .clock-marks {
                                position: absolute;
                                width: 100%;
                                height: 100%;
                            }

                            .mark {
                                position: absolute;
                                width: 2px;
                                height: 8px;
                                background: rgba(255, 255, 255, 0.3);
                                left: 50%;
                                margin-left: -1px;
                                transform-origin: 50% 60px;
                            }
                            
                            .time-text {
                                font-size: 28px;
                                font-weight: bold;
                                color: #fff;
                                text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
                                z-index: 2;
                                font-family: 'Courier New', monospace;
                            }
                            
                            .progress-ring {
                                position: absolute;
                                top: 0;
                                left: 0;
                                transform: rotate(-90deg);
                            }
                            
                            .progress-ring-circle {
                                transition: stroke-dashoffset 0.35s;
                                transform-origin: 50% 50%;
                                stroke: #e74c3c;
                                stroke-linecap: round;
                            }
                        </style>
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const errorModal = document.getElementById('errorModal');
                                let timeLeft = <?php echo $time_remaining; ?>;
                                const totalTime = 120; // 2 minutes in seconds
                                const form = document.querySelector('form');
                                const inputs = form.querySelectorAll('input, button');
                                const modalCloseBtn = document.querySelector('.btn-close');
                                const modalDismissBtn = document.querySelector('.btn-secondary');
                                let countdownInterval;

                                // Progress ring setup
                                const circle = document.querySelector('.progress-ring-circle');
                                const radius = circle.r.baseVal.value;
                                const circumference = radius * 2 * Math.PI;
                                
                                circle.style.strokeDasharray = `${circumference} ${circumference}`;
                                circle.style.strokeDashoffset = circumference;
                                
                                function setProgress(percent) {
                                    const offset = circumference - (percent / 100 * circumference);
                                    circle.style.strokeDashoffset = offset;
                                }
                                
                                function updateTimerDisplay() {
                                    const minutes = Math.floor(timeLeft / 60);
                                    const seconds = timeLeft % 60;
                                    const display = document.getElementById('timer');
                                    if (display) {
                                        display.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                                        const progressPercent = (timeLeft / totalTime) * 100;
                                        setProgress(progressPercent);
                                    }
                                }
                                
                                function enableForm() {
                                    inputs.forEach(el => el.disabled = false);
                                    if (modalCloseBtn) modalCloseBtn.disabled = false;
                                    if (modalDismissBtn) modalDismissBtn.disabled = false;
                                    clearInterval(countdownInterval);
                                    location.reload();
                                }
                                
                                function startCountdown() {
                                    // Disable form inputs and modal close buttons
                                    inputs.forEach(el => el.disabled = true);
                                    if (modalCloseBtn) modalCloseBtn.disabled = true;
                                    if (modalDismissBtn) modalDismissBtn.disabled = true;
                                    
                                    // Clear any existing interval
                                    if (countdownInterval) {
                                        clearInterval(countdownInterval);
                                    }
                                    
                                    // Show initial time immediately
                                    updateTimerDisplay();
                                    
                                    // Start the countdown
                                    countdownInterval = setInterval(() => {
                                        timeLeft--;
                                        
                                        if (timeLeft <= 0) {
                                            clearInterval(countdownInterval);
                                            enableForm();
                                            return;
                                        }
                                        
                                        updateTimerDisplay();
                                    }, 1000);
                                }

                                // Start countdown when modal is shown
                                errorModal.addEventListener('shown.bs.modal', function () {
                                    startCountdown();
                                });

                                // Clean up when modal is hidden
                                errorModal.addEventListener('hidden.bs.modal', function () {
                                    if (countdownInterval) {
                                        clearInterval(countdownInterval);
                                    }
                                });
                                
                                // Start the countdown immediately if the modal is already visible
                                if (errorModal.classList.contains('show')) {
                                    startCountdown();
                                }
                            });
                        </script>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

    <script>
        $(document).ready(function() {
            const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
            errorModal.show();
            
            // Updated form submission handler with pizza spinner
            $('form').on('submit', function() {
                const submitBtn = $(this).find('button[type="submit"]');
                const spinnerWrapper = submitBtn.find('.spinner-wrapper');
                
                // Show loading state
                submitBtn.addClass('loading');
                spinnerWrapper.removeClass('d-none');
                submitBtn.prop('disabled', true);
                
                // Reset button after timeout (10 seconds)
                setTimeout(function() {
                    if (!submitBtn.prop('disabled')) return;
                    submitBtn.removeClass('loading');
                    spinnerWrapper.addClass('d-none');
                    submitBtn.prop('disabled', false);
                }, 10000);
            });

            // Pagination functions
            let currentPage = 1;
            const rowsPerPage = 10;
            const table = $('.table');
            const rows = table.find('tbody tr');
            const totalPages = Math.ceil(rows.length / rowsPerPage);

            function showPage(page) {
                const start = (page - 1) * rowsPerPage;
                const end = start + rowsPerPage;

                rows.hide();
                rows.slice(start, end).show();

                // Update pagination buttons state
                $('.pagination-btn.prev').toggleClass('disabled', page === 1);
                $('.pagination-btn.next').toggleClass('disabled', page === totalPages);
                $('.pagination-btn.number').removeClass('active');
                $(`.pagination-btn.number[data-page="${page}"]`).addClass('active');

                // Update showing entries text
                const totalRows = rows.length;
                const showingStart = Math.min(start + 1, totalRows);
                const showingEnd = Math.min(end, totalRows);
                $('.showing-entries').text(`Showing ${showingStart} to ${showingEnd} of ${totalRows} entries`);

                currentPage = page;
            }

            // Previous button click handler
            $('.pagination-btn.prev').on('click', function() {
                if (currentPage > 1) {
                    showPage(currentPage - 1);
                }
            });

            // Next button click handler
            $('.pagination-btn.next').on('click', function() {
                if (currentPage < totalPages) {
                    showPage(currentPage + 1);
                }
            });

            // Number button click handler
            $('.pagination-btn.number').on('click', function() {
                const page = parseInt($(this).data('page'));
                showPage(page);
            });

            // Initialize first page
            showPage(1);
        });

        // Password toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const passwordToggle = document.querySelector('.password-toggle');
            const passwordInput = document.getElementById('user_password');

            if (passwordToggle && passwordInput) {
                passwordToggle.addEventListener('click', function() {
                    // Toggle password visibility
                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        this.classList.add('active');
                    } else {
                        passwordInput.type = 'password';
                        this.classList.remove('active');
                    }
                });
            }
        });
    </script>

    <style>
        @import url("https://db.onlinewebfonts.com/c/07bc241768c969f6b6a27a7bf0dfb490?family=Cooper+Black");

        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            overflow: hidden;
            font-family: "Cooper Black", "Cooper Std", serif;
        }

        .container {
            height: 100vh;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .custom-card {
            background: rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border: none;
            width: 90%;
            max-width: 360px;
            margin: 0 auto;
            padding: 12px;
        }

        .captcha-container {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
            padding: 10px 15px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 10px;
            min-height: 60px;
            overflow: hidden;
        }
        
        .captcha-text {
            font-family: 'Courier New', monospace;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 6px;
            padding: 8px 15px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            user-select: none;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.2);
            white-space: nowrap;
            overflow: hidden;
            min-width: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .captcha-text span {
            display: inline-block;
            color: rgba(255, 255, 255, 0.95);
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            transform-origin: center;
            margin: 0 1px;
        }

        .card-body {
            padding: 1rem;
        }

        .mb-3 {
            margin-bottom: 0.6rem !important;
        }

        .mb-4 {
            margin-bottom: 0.8rem !important;
        }

        .logo {
            max-width: 100px;
            height: auto;
            margin-bottom: 0.6rem !important;
        }

        h2 {
            font-family: "Cooper Black", "Cooper Std", serif;
            font-size: 1.3rem;
            margin-bottom: 0.8rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
            letter-spacing: 0.5px;
            color: #ffffff;
            font-weight: normal;
        }

        .form-control {
            padding: 0.35rem 0.7rem;
            height: auto;
            font-family: "Cooper Black", "Cooper Std", serif;
            font-size: 0.9rem;
            font-weight: normal;
        }

        .form-control::placeholder {
            font-family: "Cooper Black", "Cooper Std", serif;
            font-size: 0.85rem;
            font-weight: normal;
            opacity: 0.7;
        }

        .btn-primary {
            min-height: 35px;
            position: relative;
            font-family: "Cooper Black", "Cooper Std", serif;
            font-size: 1rem;
            font-weight: normal;
        }

        .button-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .spinner-wrapper {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
        }
        
        .pizza-spinner {
            font-size: 24px;
            color: #fff;
            animation: spin-pizza 1s linear infinite;
            filter: drop-shadow(0 0 2px rgba(0, 0, 0, 0.3));
        }

        @keyframes spin-pizza {
            0% { transform: rotate(0deg) scale(1); }
            50% { transform: rotate(180deg) scale(1.1); }
            100% { transform: rotate(360deg) scale(1); }
        }
        
        .btn-primary.loading .button-text {
            display: none;
        }
        
        .btn-primary.loading .spinner-wrapper {
            display: inline-flex !important;
        }
        
        .btn-primary.loading {
            pointer-events: none;
            opacity: 0.9;
            transform: scale(0.98);
            transition: all 0.3s ease;
        }

        .form-label {
            color: white;
            font-family: "Cooper Black", "Cooper Std", serif;
            font-size: 1rem;
            font-weight: normal;
        }

        .password-container {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: rgba(255, 255, 255, 0.7);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            z-index: 2;
            pointer-events: auto;
        }

        .password-toggle:hover {
            color: #ffffff;
        }

        .eye-icon {
            width: 20px;
            height: 20px;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
            pointer-events: none;
        }

        .eye-closed {
            display: none;
        }

        .password-toggle.active .eye-open {
            display: none;
        }

        .password-toggle.active .eye-closed {
            display: block;
        }

        #user_password {
            padding-right: 40px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transition: all 0.3s ease;
        }

        #user_password:focus {
            border-color: rgba(255, 255, 255, 0.4);
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.1);
        }

        @keyframes ripple {
            from {
                transform: scale(0);
                opacity: 1;
            }
            to {
                transform: scale(4);
                opacity: 0;
            }
        }

        .ripple {
            position: absolute;
            width: 10px;
            height: 10px;
            background: rgba(255, 255, 255, 0.4);
            border-radius: 50%;
            pointer-events: none;
            animation: ripple 1s linear;
        }

        .table-pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 1rem;
            padding: 1rem;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .pagination-info {
            color: #666;
            font-size: 0.9rem;
        }

        .pagination-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .pagination-buttons {
            display: flex;
            gap: 0.25rem;
        }

        .pagination-btn {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            background: #fff;
            color: #333;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .pagination-btn:hover:not(.disabled) {
            background: #f0f0f0;
            border-color: #ccc;
        }

        .pagination-btn.active {
            background: #007bff;
            color: #fff;
            border-color: #0056b3;
        }

        .pagination-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }
    </style>
</body>
</html>
