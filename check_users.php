<?php
require_once 'config/database.php';

try {
    // Get a user
    $stmt = $pdo->query("SELECT email, password FROM users LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get an admin
    $stmt = $pdo->query("SELECT username, password FROM admins LIMIT 1");
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "User Email: " . ($user['email'] ?? 'None') . "\n";
    // Note: We can't easily get the plain text password if it's hashed. 
    // But for testing, maybe we can create a test user/admin with a known password if none exist or if we can't use existing ones.
    // Let's check if we can just create a test user/admin.
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
