<?php
// Database initialization script
$host = 'localhost';
$username = 'root';
$password = '';

try {
    // Connect to MySQL without selecting a database
    $pdo = new PDO("mysql:host=$host;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS webank");
    $pdo->exec("USE webank");
    
    // Create users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    
    // Create transactions table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type ENUM('deposit', 'withdrawal', 'transfer', 'credit', 'debit', 'payment') NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            description TEXT,
            status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
            reference_id VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_type (type),
            INDEX idx_status (status),
            INDEX idx_created_at (created_at)
        )
    ");
    
    // Create accounts table (for future use)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS accounts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            account_number VARCHAR(20) UNIQUE NOT NULL,
            account_type ENUM('savings', 'checking', 'business') DEFAULT 'savings',
            balance DECIMAL(10,2) DEFAULT 0.00,
            status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_account_number (account_number)
        )
    ");
    
    echo "Database and tables created successfully!";
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
