<?php
require_once __DIR__ . '/../config/database.php';
try {
    for($i=0; $i<10; $i++) {
        $uniq = uniqid();
        $email = "gen_$uniq@test.com";
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if($stmt->fetch()) continue;

        $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password) VALUES (?, ?, ?, ?)");
        $stmt->execute(['Pagination', "Test_$uniq", $email, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi']); // password = password (hashed example)
    }
    echo "Successfully generated 10 users.";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
