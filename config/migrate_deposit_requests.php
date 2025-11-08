<?php
// Migration script to update deposit_requests table
// This adds reference_number column and updates status enum
require_once 'database.php';

try {
    $pdo->beginTransaction();
    
    echo "<h2>Database Migration: deposit_requests table</h2>";
    echo "<p>Starting migration...</p>";
    
    // Check if reference_number column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM deposit_requests LIKE 'reference_number'");
    if ($stmt->rowCount() == 0) {
        echo "<p>Adding reference_number column...</p>";
        $pdo->exec("ALTER TABLE deposit_requests ADD COLUMN reference_number VARCHAR(50) UNIQUE AFTER description");
        echo "<p>✅ reference_number column added</p>";
    } else {
        echo "<p>✅ reference_number column already exists</p>";
    }
    
    // Check current status enum values
    $stmt = $pdo->query("SHOW COLUMNS FROM deposit_requests WHERE Field = 'status'");
    $column = $stmt->fetch();
    $currentEnum = $column['Type'];
    
    // Check if we need to update the enum
    if (strpos($currentEnum, 'approved') !== false || strpos($currentEnum, 'rejected') !== false) {
        echo "<p>Updating status enum and existing records...</p>";
        
        // First, update existing 'approved' and 'rejected' records to 'processed'
        $pdo->exec("UPDATE deposit_requests SET status = 'processed' WHERE status IN ('approved', 'rejected')");
        echo "<p>✅ Updated existing records: approved/rejected → processed</p>";
        
        // Now alter the enum type
        $pdo->exec("ALTER TABLE deposit_requests MODIFY COLUMN status ENUM('pending', 'processed') DEFAULT 'pending'");
        echo "<p>✅ Status enum updated to: pending, processed</p>";
    } else {
        echo "<p>✅ Status enum is already up to date</p>";
    }
    
    // Check if index on reference_number exists
    $stmt = $pdo->query("SHOW INDEX FROM deposit_requests WHERE Key_name = 'idx_reference_number'");
    if ($stmt->rowCount() == 0) {
        echo "<p>Adding index on reference_number...</p>";
        $pdo->exec("ALTER TABLE deposit_requests ADD INDEX idx_reference_number (reference_number)");
        echo "<p>✅ Index added on reference_number</p>";
    } else {
        echo "<p>✅ Index on reference_number already exists</p>";
    }
    
    // Fix foreign key for processed_by to reference admins instead of users
    echo "<p>Checking processed_by foreign key...</p>";
    $stmt = $pdo->query("
        SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME 
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'deposit_requests' 
        AND COLUMN_NAME = 'processed_by'
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    $fk = $stmt->fetch();
    
    if ($fk && $fk['REFERENCED_TABLE_NAME'] === 'users') {
        echo "<p>Updating processed_by foreign key to reference admins table...</p>";
        // Drop the old foreign key
        $pdo->exec("ALTER TABLE deposit_requests DROP FOREIGN KEY " . $fk['CONSTRAINT_NAME']);
        // Add new foreign key referencing admins
        $pdo->exec("ALTER TABLE deposit_requests ADD FOREIGN KEY (processed_by) REFERENCES admins(id) ON DELETE SET NULL");
        echo "<p>✅ Foreign key updated to reference admins table</p>";
    } else if ($fk && $fk['REFERENCED_TABLE_NAME'] === 'admins') {
        echo "<p>✅ Foreign key already references admins table</p>";
    } else {
        echo "<p>⚠️ No foreign key found for processed_by (this is okay if it's a new table)</p>";
    }
    
    $pdo->commit();
    
    echo "<p><strong>✅ Migration completed successfully!</strong></p>";
    echo "<p><a href='../index.php'>← Back to Home</a></p>";
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "<p><strong>❌ Migration failed:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check the error and try again.</p>";
}
?>

