<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user = null;
$account = null;
$transactions = [];
$deposit_requests = [];
$error = '';

// Get user information
if ($user_id > 0) {
    try {
        // Get user details
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $error = 'User not found.';
        } else {
            // Get account information
            $stmt = $pdo->prepare("
                SELECT * FROM accounts 
                WHERE user_id = ? AND status = 'active' 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$user_id]);
            $account = $stmt->fetch();
            
            // Get transaction history
            $stmt = $pdo->prepare("
                SELECT type, amount, description, status, created_at, reference_id
                FROM transactions 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 20
            ");
            $stmt->execute([$user_id]);
            $transactions = $stmt->fetchAll();
            
            // Get deposit requests
            $stmt = $pdo->prepare("
                SELECT dr.*, admin.first_name as admin_first_name, admin.last_name as admin_last_name
                FROM deposit_requests dr
                LEFT JOIN admins admin ON dr.processed_by = admin.id
                WHERE dr.user_id = ? 
                ORDER BY dr.created_at DESC 
                LIMIT 20
            ");
            $stmt->execute([$user_id]);
            $deposit_requests = $stmt->fetchAll();
        }
    } catch(PDOException $e) {
        $error = 'Unable to load user information.';
    }
} else {
    $error = 'Invalid user ID.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Information - WeBank Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-red-600 text-white shadow-lg no-print">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <a href="users.php" class="flex items-center space-x-2 text-white hover:text-red-100 transition duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        <span>Back to Users</span>
                    </a>
                    <h1 class="text-2xl font-bold text-white ml-6">User Information</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-red-100">Welcome, <?php echo htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_username']); ?>!</span>
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
        <div class="max-w-6xl mx-auto">
            <?php if ($error || !$user): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <?php echo htmlspecialchars($error ?: 'User not found.'); ?>
                    <a href="users.php" class="underline ml-2">Go back to users</a>
                </div>
            <?php else: ?>
                <!-- User Information Section -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">User Details</h2>
                        <div class="flex items-center space-x-4">
                            <a href="deposit.php?user_id=<?php echo $user['id']; ?>" 
                               class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition duration-300 text-sm font-medium">
                                Make Deposit
                            </a>
                        </div>
                    </div>
                    
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <div class="mb-4">
                                <p class="text-sm text-gray-500 mb-1">Full Name</p>
                                <p class="text-lg font-semibold text-gray-900">
                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                </p>
                            </div>
                            <div class="mb-4">
                                <p class="text-sm text-gray-500 mb-1">Email Address</p>
                                <p class="text-lg font-semibold text-gray-900">
                                    <?php echo htmlspecialchars($user['email']); ?>
                                </p>
                            </div>
                        </div>
                        <div>
                            <div class="mb-4">
                                <p class="text-sm text-gray-500 mb-1">Phone Number</p>
                                <p class="text-lg font-semibold text-gray-900">
                                    <?php echo htmlspecialchars($user['phone'] ?: 'Not provided'); ?>
                                </p>
                            </div>
                            <div class="mb-4">
                                <p class="text-sm text-gray-500 mb-1">Member Since</p>
                                <p class="text-lg font-semibold text-gray-900">
                                    <?php echo date('F j, Y', strtotime($user['created_at'])); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Account Information Section -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Account Information</h2>
                    
                    <?php if ($account): ?>
                        <div class="grid md:grid-cols-3 gap-6">
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Account Number</p>
                                <p class="text-lg font-semibold text-gray-900">
                                    <?php echo htmlspecialchars($account['account_number']); ?>
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Account Type</p>
                                <p class="text-lg font-semibold text-gray-900">
                                    <?php echo ucfirst($account['account_type']); ?>
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Balance</p>
                                <p class="text-2xl font-bold text-green-600">
                                    ₱<?php echo number_format($account['balance'], 2); ?>
                                </p>
                            </div>
                        </div>
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <div class="grid md:grid-cols-2 gap-6">
                                <div>
                                    <p class="text-sm text-gray-500 mb-1">Account Status</p>
                                    <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full 
                                        <?php echo $account['status'] === 'active' ? 'bg-green-100 text-green-800' : 
                                                    ($account['status'] === 'suspended' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'); ?>">
                                        <?php echo ucfirst($account['status']); ?>
                                    </span>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 mb-1">Account Created</p>
                                    <p class="text-sm font-semibold text-gray-900">
                                        <?php echo date('F j, Y g:i A', strtotime($account['created_at'])); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <div class="bg-gray-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                </svg>
                            </div>
                            <p class="text-gray-500">No active account found</p>
                            <p class="text-sm text-gray-400">This user does not have an active account</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Deposit Requests Section -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Deposit Requests</h2>
                    
                    <?php if (empty($deposit_requests)): ?>
                        <div class="text-center py-8">
                            <div class="bg-gray-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <p class="text-gray-500">No deposit requests found</p>
                            <p class="text-sm text-gray-400">This user has no deposit request history</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference Number</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Requested</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Processed By</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Processed Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Admin Notes</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($deposit_requests as $request): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-mono font-semibold text-gray-900">
                                                <?php echo htmlspecialchars($request['reference_number'] ?? 'N/A'); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo date('M j, Y g:i A', strtotime($request['created_at'])); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                                ₱<?php echo number_format($request['amount'], 2); ?>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900">
                                                <?php echo htmlspecialchars($request['description'] ?: 'No description'); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php
                                                $statusColors = [
                                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                                    'processed' => 'bg-green-100 text-green-800'
                                                ];
                                                $statusClass = $statusColors[$request['status']] ?? 'bg-gray-100 text-gray-800';
                                                ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusClass; ?>">
                                                    <?php echo ucfirst($request['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php 
                                                if ($request['processed_by'] && $request['admin_first_name']) {
                                                    echo htmlspecialchars($request['admin_first_name'] . ' ' . $request['admin_last_name']);
                                                } else {
                                                    echo $request['status'] === 'pending' ? 'Pending' : 'N/A';
                                                }
                                                ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo $request['processed_at'] ? date('M j, Y g:i A', strtotime($request['processed_at'])) : 'N/A'; ?>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500">
                                                <?php echo htmlspecialchars($request['admin_notes'] ?: 'N/A'); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Transaction History Section -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Transaction History</h2>
                    
                    <?php if (empty($transactions)): ?>
                        <div class="text-center py-8">
                            <div class="bg-gray-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                            </div>
                            <p class="text-gray-500">No transactions found</p>
                            <p class="text-sm text-gray-400">This user has no transaction history</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($transactions as $transaction): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo date('M j, Y g:i A', strtotime($transaction['created_at'])); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    <?php 
                                                    $typeColors = [
                                                        'deposit' => 'bg-green-100 text-green-800',
                                                        'withdrawal' => 'bg-red-100 text-red-800',
                                                        'transfer' => 'bg-blue-100 text-blue-800',
                                                        'credit' => 'bg-green-100 text-green-800',
                                                        'debit' => 'bg-red-100 text-red-800',
                                                        'payment' => 'bg-yellow-100 text-yellow-800',
                                                        'bill_payment' => 'bg-purple-100 text-purple-800'
                                                    ];
                                                    echo $typeColors[$transaction['type']] ?? 'bg-gray-100 text-gray-800';
                                                    ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $transaction['type'])); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900">
                                                <?php echo htmlspecialchars($transaction['description'] ?: 'No description'); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold 
                                                <?php echo ($transaction['type'] === 'deposit' || $transaction['type'] === 'credit') ? 'text-green-600' : 'text-red-600'; ?>">
                                                <?php echo ($transaction['type'] === 'deposit' || $transaction['type'] === 'credit') ? '+' : '-'; ?>₱<?php echo number_format($transaction['amount'], 2); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    <?php 
                                                    $statusColors = [
                                                        'completed' => 'bg-green-100 text-green-800',
                                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                                        'failed' => 'bg-red-100 text-red-800',
                                                        'cancelled' => 'bg-gray-100 text-gray-800'
                                                    ];
                                                    echo $statusColors[$transaction['status']] ?? 'bg-gray-100 text-gray-800';
                                                    ?>">
                                                    <?php echo ucfirst($transaction['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($transaction['reference_id'] ?: 'N/A'); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
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

