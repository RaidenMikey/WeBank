<?php
require_once 'config/database.php';

$password = password_hash('password123', PASSWORD_DEFAULT);

try {
    // Create Test User
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (first_name, last_name, email, password, phone) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['Test', 'User', 'testuser@webank.com', $password, '09123456789']);
    echo "Test User Created/Exists.\n";

    // Create Test Admin
    $stmt = $pdo->prepare("INSERT IGNORE INTO admins (username, password, first_name, last_name, email, role) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['testadmin', $password, 'Test', 'Admin', 'testadmin@webank.com', 'superadmin']);
    echo "Test Admin Created/Exists.\n";

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
