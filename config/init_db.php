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
    
    // Create admins table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            role ENUM('super_admin', 'admin', 'moderator') DEFAULT 'admin',
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    
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
    
    // Create billers table (must be created before transactions table)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS billers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            biller_name VARCHAR(100) NOT NULL,
            category VARCHAR(50) NOT NULL,
            account_number VARCHAR(50) NOT NULL,
            description TEXT,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_category (category),
            INDEX idx_status (status)
        )
    ");
    
    // Create transactions table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type ENUM('deposit', 'withdrawal', 'transfer', 'credit', 'debit', 'payment', 'bill_payment') NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            description TEXT,
            status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
            reference_id VARCHAR(100),
            biller_id INT NULL,
            biller_reference VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (biller_id) REFERENCES billers(id) ON DELETE SET NULL,
            INDEX idx_user_id (user_id),
            INDEX idx_type (type),
            INDEX idx_status (status),
            INDEX idx_created_at (created_at),
            INDEX idx_biller_id (biller_id)
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
    
    // Create deposit_requests table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS deposit_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            description TEXT,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            admin_notes TEXT,
            processed_by INT NULL,
            processed_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_user_id (user_id),
            INDEX idx_status (status),
            INDEX idx_created_at (created_at)
        )
    ");
    
    // Create default admin account if it doesn't exist
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE username = 'admin'");
    $stmt->execute();
    $adminExists = $stmt->fetchColumn();
    
    if ($adminExists == 0) {
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO admins (username, password, first_name, last_name, email, role) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute(['admin', $hashedPassword, 'System', 'Administrator', 'admin@webank.com', 'super_admin']);
        echo "Default admin account created!<br>";
    }
    
    // Create default billers if they don't exist
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM billers");
    $stmt->execute();
    $billersExist = $stmt->fetchColumn();
    
    if ($billersExist == 0) {
        $defaultBillers = [
            ['Meralco', 'Electric', '12345', 'Electric Utility'],
            ['PLDT', 'Internet', '67890', 'Internet Provider'],
            ['Maynilad', 'Water', '11223', 'Water Utility'],
            ['Smart', 'Telecom', '44556', 'Mobile Postpaid'],
            ['Globe', 'Telecom', '77889', 'Mobile Postpaid'],
            ['Converge', 'Internet', '33445', 'Internet Provider'],
            ['Manila Water', 'Water', '55667', 'Water Utility'],
            ['Sky Cable', 'Cable', '88990', 'Cable TV Service']
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO billers (biller_name, category, account_number, description) 
            VALUES (?, ?, ?, ?)
        ");
        
        foreach ($defaultBillers as $biller) {
            $stmt->execute($biller);
        }
        echo "Default billers created!<br>";
    }
    
    echo "Database and tables created successfully!";
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
