<?php
// Replace these with your actual database credentials
$host = 'localhost';
$dbname = 'pos';
$username = 'root';
$password = ''; // Your MySQL password

try {
    // Create a new PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Enable error mode
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Fetch as associative array
        PDO::ATTR_PERSISTENT => true // Use persistent connection
    ]);

    echo "✅ Database connection successful!";
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage();
}
?>
