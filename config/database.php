<?php
// Prevent direct access to this file
if (!defined('ALLOW_ACCESS')) {
    header("HTTP/1.0 403 Forbidden");
    echo "Direct access not permitted.";
    exit;
}

// Database configuration constants
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pos');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
?> 