<?php
// Manual account creation script
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    die("Please login first");
}

try {
    // Check if user already has account
    $stmt = $pdo->prepare("SELECT * FROM accounts WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    
    if ($stmt->fetch()) {
        echo "User already has an account.";
    } else {
        // Create account
        $accountNumber = 'WB' . str_pad($_SESSION['user_id'], 8, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("INSERT INTO accounts (user_id, account_number, balance, status) VALUES (?, ?, 0.00, 'active')");
        $stmt->execute([$_SESSION['user_id'], $accountNumber]);
        echo "Account created successfully! Account Number: " . $accountNumber;
    }
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

