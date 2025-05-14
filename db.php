<?php
$host = 'localhost'; // Usually stays as localhost for cPanel
$dbname = 'your_cpanel_database_name'; // Your cPanel database name
$username = 'your_cpanel_database_username'; // Your cPanel database username
$password = 'your_cpanel_database_password'; // Your cPanel database password

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Add error logging for debugging
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please check your configuration.");
}
?>