<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$accountInfo = null;
$error = '';

try {
    // Get user's account information
    $stmt = $pdo->prepare("
        SELECT a.account_number, a.balance, a.account_type, a.status, a.created_at, a.updated_at,
               u.first_name, u.last_name, u.email, u.phone
        FROM accounts a 
        JOIN users u ON a.user_id = u.id
        WHERE a.user_id = ? AND a.status = 'active'
        ORDER BY a.created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $accountInfo = $stmt->fetch();
    
    if (!$accountInfo) {
        // Create account if it doesn't exist
        $accountNumber = 'WB' . str_pad($_SESSION['user_id'], 8, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("INSERT INTO accounts (user_id, account_number, balance, status) VALUES (?, ?, 0.00, 'active')");
        $stmt->execute([$_SESSION['user_id'], $accountNumber]);
        
        // Get the newly created account info
        $stmt = $pdo->prepare("
            SELECT a.account_number, a.balance, a.account_type, a.status, a.created_at, a.updated_at,
                   u.first_name, u.last_name, u.email, u.phone
            FROM accounts a 
            JOIN users u ON a.user_id = u.id
            WHERE a.user_id = ? AND a.status = 'active'
            ORDER BY a.created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $accountInfo = $stmt->fetch();
    }
    
} catch(PDOException $e) {
    $error = 'Unable to retrieve account information. Please try again.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Details - WeBank</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-red-600 text-white shadow-lg no-print">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <a href="dashboard.php" class="flex items-center space-x-2 text-white hover:text-red-100 transition duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        <span>Back to Dashboard</span>
                    </a>
                    <h1 class="text-2xl font-bold text-white ml-6">Account Details</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-red-100">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
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
    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if ($error): ?>
            <div class="mb-6 p-4 rounded-lg bg-red-100 text-red-700 border border-red-300">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($accountInfo): ?>
            <!-- Account Information -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Account Information</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Account Number -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-blue-800">Account Number</h3>
                                <p class="text-2xl font-bold text-blue-900"><?php echo htmlspecialchars($accountInfo['account_number']); ?></p>
                            </div>
                            <div class="bg-blue-100 p-3 rounded-full">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Current Balance -->
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-green-800">Current Balance</h3>
                                <p class="text-2xl font-bold text-green-900">₱<?php echo number_format($accountInfo['balance'], 2); ?></p>
                            </div>
                            <div class="bg-green-100 p-3 rounded-full">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Account Details -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Account Details</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 mb-2">Account Holder</h4>
                        <p class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($accountInfo['first_name'] . ' ' . $accountInfo['last_name']); ?></p>
                    </div>
                    
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 mb-2">Email</h4>
                        <p class="text-lg text-gray-800"><?php echo htmlspecialchars($accountInfo['email']); ?></p>
                    </div>
                    
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 mb-2">Phone</h4>
                        <p class="text-lg text-gray-800"><?php echo htmlspecialchars($accountInfo['phone'] ?: 'Not provided'); ?></p>
                    </div>
                    
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 mb-2">Account Type</h4>
                        <p class="text-lg text-gray-800"><?php echo ucfirst(htmlspecialchars($accountInfo['account_type'])); ?></p>
                    </div>
                    
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 mb-2">Account Status</h4>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $accountInfo['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo ucfirst(htmlspecialchars($accountInfo['status'])); ?>
                        </span>
                    </div>
                    
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 mb-2">Member Since</h4>
                        <p class="text-lg text-gray-800"><?php echo date('F j, Y', strtotime($accountInfo['created_at'])); ?></p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Quick Actions</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <a href="check_balance.php" class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center hover:bg-blue-100 transition duration-300">
                        <div class="bg-blue-100 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <h4 class="font-semibold text-gray-800">Check Balance</h4>
                        <p class="text-sm text-gray-600">View detailed balance</p>
                    </a>
                    
                    <a href="transaction_history.php" class="bg-orange-50 border border-orange-200 rounded-lg p-4 text-center hover:bg-orange-100 transition duration-300">
                        <div class="bg-orange-100 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <h4 class="font-semibold text-gray-800">Transaction History</h4>
                        <p class="text-sm text-gray-600">View all transactions</p>
                    </a>
                    
                    <a href="transfer.php" class="bg-purple-50 border border-purple-200 rounded-lg p-4 text-center hover:bg-purple-100 transition duration-300">
                        <div class="bg-purple-100 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                            </svg>
                        </div>
                        <h4 class="font-semibold text-gray-800">Transfer Money</h4>
                        <p class="text-sm text-gray-600">Send money to others</p>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-6 no-print">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p>&copy; 2024 WeBank. All rights reserved.</p>
        </div>
    </footer>

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
