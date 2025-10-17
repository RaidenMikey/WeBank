<?php
// Database connection test script
require_once 'config/database.php';

echo "<h2>Database Connection Test</h2>";

try {
    // Test basic connection
    echo "<p>✅ Database connection successful</p>";
    
    // Test if tables exist
    $tables = ['users', 'accounts', 'transactions'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p>✅ Table '$table' exists</p>";
        } else {
            echo "<p>❌ Table '$table' missing</p>";
        }
    }
    
    // Test user session (if logged in)
    session_start();
    if (isset($_SESSION['user_id'])) {
        echo "<p>✅ User session active (ID: " . $_SESSION['user_id'] . ")</p>";
        
        // Test if user has account
        $stmt = $pdo->prepare("SELECT * FROM accounts WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $account = $stmt->fetch();
        
        if ($account) {
            echo "<p>✅ User has account (Balance: $" . number_format($account['balance'], 2) . ")</p>";
        } else {
            echo "<p>⚠️ User has no account - will be created automatically</p>";
        }
    } else {
        echo "<p>⚠️ No user session - please login first</p>";
    }
    
} catch(PDOException $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='index.php'>← Back to Home</a></p>";
?>

