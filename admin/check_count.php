<?php
require_once __DIR__ . '/../config/database.php';
$cnt = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
echo "Total Users: " . $cnt;
?>
