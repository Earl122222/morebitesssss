<?php
/**
 * Database Functions for POS System
 */

/**
 * Password validation and security functions
 */

/**
 * Validate password strength
 * @param string $password Password to validate
 * @return array Array with 'valid' => bool and 'message' => string
 */
function validatePasswordStrength($password) {
    $errors = [];
    
    // Minimum length check
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    // Maximum length check (prevent DOS attacks)
    if (strlen($password) > 128) {
        $errors[] = "Password cannot exceed 128 characters";
    }
    
    // Complexity requirements
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }
    
    // Check for common passwords
    $common_passwords = ['password123', 'admin123', '12345678', 'qwerty123'];
    if (in_array(strtolower($password), $common_passwords)) {
        $errors[] = "This password is too common. Please choose a stronger password";
    }
    
    // Check for sequential characters
    if (preg_match('/(.)\1{2,}/', $password)) {
        $errors[] = "Password cannot contain repeated characters";
    }
    
    return [
        'valid' => empty($errors),
        'message' => empty($errors) ? 'Password is valid' : implode('<br>', $errors)
    ];
}

/**
 * Sanitize password input
 * @param string $password Raw password input
 * @return string Sanitized password
 */
function sanitizePassword($password) {
    // Remove any whitespace
    $password = trim($password);
    
    // Remove any HTML tags
    $password = strip_tags($password);
    
    // Convert special characters to HTML entities
    $password = htmlspecialchars($password, ENT_QUOTES, 'UTF-8');
    
    return $password;
}

/**
 * Hash password with secure algorithm
 * @param string $password Plain text password
 * @return string Hashed password
 */
function hashPassword($password) {
    // Use PHP's built-in password_hash with recommended options
    $options = [
        'cost' => 12, // Recommended cost factor
        'algo' => PASSWORD_BCRYPT // Use BCRYPT algorithm
    ];
    
    return password_hash($password, PASSWORD_BCRYPT, $options);
}

/**
 * Verify password against hash
 * @param string $password Plain text password
 * @param string $hash Hashed password from database
 * @return bool True if password matches
 */
function verifyPassword($password, $hash) {
    // Use PHP's built-in password_verify
    return password_verify($password, $hash);
}

/**
 * Generate a secure random password
 * @param int $length Length of password (default: 12)
 * @return string Generated password
 */
function generateSecurePassword($length = 12) {
    // Define character sets
    $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $lowercase = 'abcdefghijklmnopqrstuvwxyz';
    $numbers = '0123456789';
    $special = '!@#$%^&*()_+-=[]{}|;:,.<>?';
    
    // Ensure at least one of each type
    $password = $uppercase[random_int(0, strlen($uppercase) - 1)];
    $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
    $password .= $numbers[random_int(0, strlen($numbers) - 1)];
    $password .= $special[random_int(0, strlen($special) - 1)];
    
    // Fill remaining length with random characters
    $all = $uppercase . $lowercase . $numbers . $special;
    for ($i = strlen($password); $i < $length; $i++) {
        $password .= $all[random_int(0, strlen($all) - 1)];
    }
    
    // Shuffle the password
    return str_shuffle($password);
}

/**
 * Get user by email
 * @param PDO $pdo Database connection
 * @param string $email User's email
 * @return array|false User data or false if not found
 */
function getUserByEmail($pdo, $email) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM pos_user WHERE user_email = ? AND user_status = 'Active'");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error in getUserByEmail: ' . $e->getMessage());
        return false;
    }
}

/**
 * Record login attempt
 * @param PDO $pdo Database connection
 * @param string $email User's email
 * @return bool Success status
 */
function recordLoginAttempt($pdo, $email) {
    try {
        $stmt = $pdo->prepare("INSERT INTO login_attempts (email, timestamp) VALUES (?, NOW())");
        return $stmt->execute([$email]);
    } catch (PDOException $e) {
        error_log('Error in recordLoginAttempt: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get recent login attempts count
 * @param PDO $pdo Database connection
 * @param string $email User's email
 * @return int Number of attempts
 */
function getRecentLoginAttempts($pdo, $email) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as attempts FROM login_attempts WHERE email = ? AND timestamp > DATE_SUB(NOW(), INTERVAL 1 MINUTE)");
        $stmt->execute([$email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['attempts'];
    } catch (PDOException $e) {
        error_log('Error in getRecentLoginAttempts: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Clear login attempts for user
 * @param PDO $pdo Database connection
 * @param string $email User's email
 * @return bool Success status
 */
function clearLoginAttempts($pdo, $email) {
    try {
        $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE email = ?");
        return $stmt->execute([$email]);
    } catch (PDOException $e) {
        error_log('Error in clearLoginAttempts: ' . $e->getMessage());
        return false;
    }
}

/**
 * Clean up old login attempts
 * @param PDO $pdo Database connection
 * @return bool Success status
 */
function cleanupOldLoginAttempts($pdo) {
    try {
        $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE timestamp < DATE_SUB(NOW(), INTERVAL 2 MINUTE)");
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log('Error in cleanupOldLoginAttempts: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get user's last login attempt timestamp
 * @param PDO $pdo Database connection
 * @param string $email User's email
 * @return string|null Timestamp or null if no attempts
 */
function getLastLoginAttempt($pdo, $email) {
    try {
        $stmt = $pdo->prepare("SELECT MAX(timestamp) as last_attempt FROM login_attempts WHERE email = ?");
        $stmt->execute([$email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['last_attempt'] ?? null;
    } catch (PDOException $e) {
        error_log('Error in getLastLoginAttempt: ' . $e->getMessage());
        return null;
    }
}

/**
 * Generate a secure verification token
 * @return string Verification token
 */
function generateVerificationToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Create unverified user account
 * @param PDO $pdo Database connection
 * @param array $user_data User information
 * @param string $verification_token Verification token
 * @return bool Success status
 */
function createUnverifiedUser($pdo, $user_data, $verification_token) {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Insert into pos_user with unverified status
        $stmt = $pdo->prepare("INSERT INTO pos_user (user_name, user_email, user_password, user_type, user_status, verification_token, created_at) 
                              VALUES (:user_name, :user_email, :user_password, :user_type, 'Unverified', :verification_token, NOW())");
        
        $stmt->execute([
            'user_name' => $user_data['user_name'],
            'user_email' => $user_data['user_email'],
            'user_password' => $user_data['user_password'],
            'user_type' => $user_data['user_type'],
            'verification_token' => $verification_token
        ]);
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('Error in createUnverifiedUser: ' . $e->getMessage());
        return false;
    }
}

/**
 * Send verification email
 * @param string $email User's email
 * @param string $verification_token Verification token
 * @return bool Success status
 */
function sendVerificationEmail($email, $verification_token) {
    try {
        $to = $email;
        $subject = "Verify Your Account - MoreBites POS System";
        
        // Create verification link
        $verification_link = "http://" . $_SERVER['HTTP_HOST'] . "/pos_system/verify_account.php?token=" . $verification_token;
        
        // Email content
        $message = "
        <html>
        <head>
            <title>Verify Your Account</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .button { display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; }
                .footer { margin-top: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2>Welcome to MoreBites POS System!</h2>
                <p>Thank you for creating an account. Please verify your email address by clicking the button below:</p>
                <p style='text-align: center;'>
                    <a href='{$verification_link}' class='button'>Verify Email Address</a>
                </p>
                <p>Or copy and paste this link into your browser:</p>
                <p>{$verification_link}</p>
                <p>This link will expire in 24 hours.</p>
                <p>If you didn't create an account, please ignore this email.</p>
                <div class='footer'>
                    <p>This is an automated message, please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Email headers
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: noreply@morebites.com\r\n";
        $headers .= "Reply-To: noreply@morebites.com\r\n";
        
        // Send email
        return mail($to, $subject, $message, $headers);
    } catch (Exception $e) {
        error_log('Error in sendVerificationEmail: ' . $e->getMessage());
        return false;
    }
}

/**
 * Verify user account
 * @param PDO $pdo Database connection
 * @param string $token Verification token
 * @return array Status and message
 */
function verifyUserAccount($pdo, $token) {
    try {
        // Check if token exists and is not expired
        $stmt = $pdo->prepare("SELECT user_id, user_email FROM pos_user 
                              WHERE verification_token = ? 
                              AND user_status = 'Unverified' 
                              AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Invalid or expired verification token.'
            ];
        }
        
        // Update user status
        $update = $pdo->prepare("UPDATE pos_user 
                                SET user_status = 'Active', 
                                    verification_token = NULL, 
                                    verified_at = NOW() 
                                WHERE user_id = ?");
        $update->execute([$user['user_id']]);
        
        return [
            'success' => true,
            'message' => 'Account verified successfully. You can now log in.'
        ];
    } catch (PDOException $e) {
        error_log('Error in verifyUserAccount: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred while verifying your account.'
        ];
    }
}

/**
 * Check if user is verified
 * @param PDO $pdo Database connection
 * @param string $email User's email
 * @return bool True if user is verified
 */
function isUserVerified($pdo, $email) {
    try {
        $stmt = $pdo->prepare("SELECT user_status FROM pos_user WHERE user_email = ?");
        $stmt->execute([$email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['user_status'] === 'Active';
    } catch (PDOException $e) {
        error_log('Error in isUserVerified: ' . $e->getMessage());
        return false;
    }
} 