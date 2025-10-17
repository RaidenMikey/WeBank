<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$user = null;
$success = '';
$error = '';

// Get user information
if ($user_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $error = 'User not found.';
        }
    } catch(PDOException $e) {
        $error = 'Unable to load user information.';
    }
}

// Handle deposit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $user) {
    $amount = (float)$_POST['amount'];
    $description = trim($_POST['description']);
    
    if ($amount <= 0) {
        $error = 'Invalid amount.';
    } else {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Get or create user's account
            $stmt = $pdo->prepare("SELECT id, balance FROM accounts WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$user_id]);
            $account = $stmt->fetch();
            
            if (!$account) {
                // Create account for user
                $accountNumber = 'WB' . str_pad($user_id, 8, '0', STR_PAD_LEFT);
                $stmt = $pdo->prepare("INSERT INTO accounts (user_id, account_number, balance, status) VALUES (?, ?, 0.00, 'active')");
                $stmt->execute([$user_id, $accountNumber]);
                $account_id = $pdo->lastInsertId();
                $current_balance = 0.00;
            } else {
                $account_id = $account['id'];
                $current_balance = $account['balance'];
            }
            
            // Update account balance
            $new_balance = $current_balance + $amount;
            $stmt = $pdo->prepare("UPDATE accounts SET balance = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$new_balance, $account_id]);
            
            // Log transaction
            $stmt = $pdo->prepare("
                INSERT INTO transactions (user_id, type, amount, description, status, reference_id) 
                VALUES (?, 'deposit', ?, ?, 'completed', ?)
            ");
            $reference_id = 'ADMIN_' . time() . '_' . $user_id;
            $stmt->execute([$user_id, $amount, $description ?: 'Admin deposit', $reference_id]);
            
            $pdo->commit();
            $success = "Successfully deposited ₱" . number_format($amount, 2) . " to " . $user['first_name'] . " " . $user['last_name'] . "'s account. New balance: ₱" . number_format($new_balance, 2);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Deposit failed: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Deposit - WeBank Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-red-600 shadow-md">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <a href="users.php" class="text-red-100 hover:text-white mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                    </a>
                    <h1 class="text-2xl font-bold text-white">Quick Deposit</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-red-100">Admin: <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                    <!-- Settings Dropdown -->
                    <div class="relative" id="settingsDropdown">
                        <button onclick="toggleDropdown()" class="bg-red-700 text-white px-4 py-2 rounded-lg hover:bg-red-800 transition duration-300 flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span>Settings</span>
                            <svg class="w-4 h-4 transition-transform duration-200" id="dropdownArrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div id="dropdownMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-50 hidden">
                            <div class="py-1">
                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    <span>Account</span>
                                </a>
                                <a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                    </svg>
                                    <span>Logout</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <?php if (!$user): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    User not found. <a href="users.php" class="underline">Go back to users</a>
                </div>
            <?php else: ?>
                <!-- User Info -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">User Information</h3>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Name</p>
                            <p class="font-semibold"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Email</p>
                            <p class="font-semibold"><?php echo htmlspecialchars($user['email']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Phone</p>
                            <p class="font-semibold"><?php echo htmlspecialchars($user['phone'] ?: 'Not provided'); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Member Since</p>
                            <p class="font-semibold"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Messages -->
                <?php if ($success): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Deposit Form -->
                <div class="bg-white rounded-lg shadow-md p-8">
                    <h3 class="text-2xl font-bold text-gray-800 mb-6">Deposit Funds</h3>
                    
                    <form method="POST" class="space-y-6">
                        <div>
                            <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">Amount (₱)</label>
                            <input id="amount" name="amount" type="number" step="0.01" min="0.01" required 
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500"
                                   placeholder="0.00">
                        </div>
                        
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <input id="description" name="description" type="text" 
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500"
                                   placeholder="e.g., Initial funding, Salary credit, Test deposit">
                        </div>
                        
                        <div class="flex justify-end space-x-4">
                            <a href="users.php" class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition duration-300">
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="bg-red-600 text-white px-8 py-3 rounded-lg hover:bg-red-700 transition duration-300 font-medium">
                                Process Deposit
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function toggleDropdown() {
            const dropdown = document.getElementById('dropdownMenu');
            const arrow = document.getElementById('dropdownArrow');
            
            if (dropdown.classList.contains('hidden')) {
                dropdown.classList.remove('hidden');
                arrow.style.transform = 'rotate(180deg)';
            } else {
                dropdown.classList.add('hidden');
                arrow.style.transform = 'rotate(0deg)';
            }
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('settingsDropdown');
            const dropdownMenu = document.getElementById('dropdownMenu');
            
            if (!dropdown.contains(event.target)) {
                dropdownMenu.classList.add('hidden');
                document.getElementById('dropdownArrow').style.transform = 'rotate(0deg)';
            }
        });
    </script>
</body>
</html>


