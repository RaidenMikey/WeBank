<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch user's creation date and account balance
try {
    $stmt = $pdo->prepare("SELECT created_at FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    $memberSince = $user ? $user['created_at'] : date('Y-m-d');
    
    // Get account balance
    $stmt = $pdo->prepare("SELECT balance FROM accounts WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $account = $stmt->fetch();
    $balance = $account ? $account['balance'] : 0.00;
} catch(PDOException $e) {
    $memberSince = date('Y-m-d');
    $balance = 0.00;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - WeBank</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-blue-600">WeBank</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
                    <!-- Settings Dropdown -->
                    <div class="relative" id="settingsDropdown">
                        <button onclick="toggleDropdown()" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition duration-300 flex items-center space-x-2">
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
        <div class="max-w-4xl mx-auto">
            <!-- Welcome Section -->
            <div class="bg-white rounded-lg shadow-md p-8 mb-8">
                <h2 class="text-3xl font-bold text-gray-800 mb-4">Welcome to Your Dashboard</h2>
                <p class="text-gray-600 text-lg">
                    Hello <?php echo htmlspecialchars($_SESSION['user_name']); ?>! 
                    You are successfully logged into your WeBank account.
                </p>
            </div>

            <!-- Quick Stats -->
            <div class="grid md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="bg-blue-100 p-3 rounded-full">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-800">Account Balance</h3>
                            <p class="text-2xl font-bold text-blue-600">$<?php echo number_format($balance, 2); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="bg-green-100 p-3 rounded-full">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-800">Account Status</h3>
                            <p class="text-2xl font-bold text-green-600">Active</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="bg-purple-100 p-3 rounded-full">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-800">Member Since</h3>
                            <p class="text-2xl font-bold text-purple-600"><?php echo date('M j, Y', strtotime($memberSince)); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Banking Services -->
            <div class="bg-white rounded-lg shadow-md p-8">
                <h3 class="text-2xl font-bold text-gray-800 mb-6">Banking Services</h3>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Check Balance - Active -->
                    <div class="bg-green-50 border-2 border-green-200 rounded-lg p-6 text-center relative">
                        <div class="absolute top-2 right-2">
                            <span class="bg-green-500 text-white text-xs px-2 py-1 rounded-full">✅ Active</span>
                        </div>
                        <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-800 mb-2">Check Balance</h4>
                        <p class="text-gray-600 text-sm mb-4">View your real-time available balance</p>
                        <a href="check_balance.php" class="w-full bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition duration-300 font-medium inline-block text-center">
                            Check Balance
                        </a>
                    </div>

                    <!-- Withdrawal - Coming Soon -->
                    <div class="bg-gray-50 border-2 border-gray-200 rounded-lg p-6 text-center relative opacity-60">
                        <div class="absolute top-2 right-2">
                            <span class="bg-gray-500 text-white text-xs px-2 py-1 rounded-full">🚧 Coming Soon</span>
                        </div>
                        <div class="bg-gray-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-600 mb-2">Withdrawal / Cash-out</h4>
                        <p class="text-gray-500 text-sm mb-4">Cash out to agents like GCash</p>
                        <button class="w-full bg-gray-400 text-white py-2 px-4 rounded-lg cursor-not-allowed font-medium" disabled>
                            Coming Soon
                        </button>
                    </div>

                    <!-- Payment - Active -->
                    <div class="bg-blue-50 border-2 border-blue-200 rounded-lg p-6 text-center relative">
                        <div class="absolute top-2 right-2">
                            <span class="bg-blue-500 text-white text-xs px-2 py-1 rounded-full">✅ Active</span>
                        </div>
                        <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-800 mb-2">Payment / Pay Bills</h4>
                        <p class="text-gray-600 text-sm mb-4">Pay utilities, tuition, credit cards</p>
                        <button class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition duration-300 font-medium">
                            Pay Bills
                        </button>
                    </div>

                    <!-- Transfer - Active -->
                    <div class="bg-purple-50 border-2 border-purple-200 rounded-lg p-6 text-center relative">
                        <div class="absolute top-2 right-2">
                            <span class="bg-purple-500 text-white text-xs px-2 py-1 rounded-full">✅ Active</span>
                        </div>
                        <div class="bg-purple-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                            </svg>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-800 mb-2">Transfer</h4>
                        <p class="text-gray-600 text-sm mb-4">Send money to other users</p>
                        <button class="w-full bg-purple-600 text-white py-2 px-4 rounded-lg hover:bg-purple-700 transition duration-300 font-medium">
                            Transfer Money
                        </button>
                    </div>

                    <!-- Transaction History - Active -->
                    <div class="bg-orange-50 border-2 border-orange-200 rounded-lg p-6 text-center relative">
                        <div class="absolute top-2 right-2">
                            <span class="bg-orange-500 text-white text-xs px-2 py-1 rounded-full">✅ Active</span>
                        </div>
                        <div class="bg-orange-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-800 mb-2">Transaction History</h4>
                        <p class="text-gray-600 text-sm mb-4">View all past transactions</p>
                        <button class="w-full bg-orange-600 text-white py-2 px-4 rounded-lg hover:bg-orange-700 transition duration-300 font-medium">
                            View History
                        </button>
                    </div>

                    <!-- Add Money - Active -->
                    <div class="bg-indigo-50 border-2 border-indigo-200 rounded-lg p-6 text-center relative">
                        <div class="absolute top-2 right-2">
                            <span class="bg-indigo-500 text-white text-xs px-2 py-1 rounded-full">✅ Active</span>
                        </div>
                        <div class="bg-indigo-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-800 mb-2">Add Money</h4>
                        <p class="text-gray-600 text-sm mb-4">Deposit funds to your account</p>
                        <button class="w-full bg-indigo-600 text-white py-2 px-4 rounded-lg hover:bg-indigo-700 transition duration-300 font-medium">
                            Add Money
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8 mt-12">
        <div class="container mx-auto px-4 text-center">
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
