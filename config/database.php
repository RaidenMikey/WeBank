<?php
// Database configuration
// Load configuration
$config = require_once 'config.php';

$host = $config['DB_HOST'];
$dbname = $config['DB_NAME'];
$username = $config['DB_USER'];
$password = $config['DB_PASS'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch(PDOException $e) {
    // Log error internally but show generic message to user
    error_log("Database connection failed: " . $e->getMessage());
    die("System error. Please try again later.");
}
?>
