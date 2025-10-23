<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$balance = 0.00;
$lastTransaction = null;
$accountNumber = '';
$error = '';

try {
    // Get user's account information
    $stmt = $pdo->prepare("
        SELECT a.balance, a.account_number, a.updated_at as last_updated
        FROM accounts a 
        WHERE a.user_id = ? AND a.status = 'active'
        ORDER BY a.created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $account = $stmt->fetch();
    
    if ($account) {
        $balance = $account['balance'];
        $accountNumber = $account['account_number'];
        $lastTransaction = $account['last_updated'];
    } else {
        // If no account exists, create one for the user
        $accountNumber = 'WB' . str_pad($_SESSION['user_id'], 8, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("
            INSERT INTO accounts (user_id, account_number, balance, status) 
            VALUES (?, ?, 0.00, 'active')
        ");
        $stmt->execute([$_SESSION['user_id'], $accountNumber]);
        $balance = 0.00;
        $lastTransaction = date('Y-m-d H:i:s');
    }
    
    // Get the most recent transaction timestamp
    $stmt = $pdo->prepare("
        SELECT created_at 
        FROM transactions 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $transaction = $stmt->fetch();
    if ($transaction) {
        $lastTransaction = $transaction['created_at'];
    }
    
} catch(PDOException $e) {
    $error = 'Unable to retrieve balance information. Please try again.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Balance - WeBank</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body * {
                visibility: hidden !important;
            }
            .print-content,
            .print-content * {
                visibility: visible !important;
            }
            .print-content {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                background: white !important;
                color: black !important;
                font-family: Arial, sans-serif !important;
                font-size: 12pt !important;
                line-height: 1.4 !important;
                margin: 0 !important;
                padding: 20px !important;
                page-break-inside: avoid !important;
            }
            .print-statement {
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .print-statement h1 {
                font-size: 18pt !important;
                font-weight: bold !important;
                margin-bottom: 20px !important;
                text-align: center !important;
            }
            .print-statement h2 {
                font-size: 16pt !important;
                font-weight: bold !important;
                margin: 20px 0 10px 0 !important;
            }
            .print-statement h3 {
                font-size: 14pt !important;
                font-weight: bold !important;
                margin: 20px 0 10px 0 !important;
            }
            .print-statement p {
                margin: 5px 0 !important;
                font-size: 12pt !important;
            }
            .print-statement strong {
                font-weight: bold !important;
            }
            .print-statement em {
                font-style: italic !important;
            }
        }
        .print-content {
            display: none;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-blue-600 text-white shadow-lg no-print">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <a href="dashboard.php" class="flex items-center space-x-2 text-white hover:text-blue-100 transition duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        <span>Back to Dashboard</span>
                    </a>
                    <h1 class="text-2xl font-bold text-white ml-6">Check Balance</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-blue-100">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
                    <!-- Settings Dropdown -->
                    <div class="relative" id="settingsDropdown">
                        <button onclick="toggleDropdown()" class="bg-blue-700 text-white px-4 py-2 rounded-lg hover:bg-blue-800 transition duration-300 flex items-center space-x-2">
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
            <!-- Page Title -->
            <div class="mb-8">
                <h2 class="text-3xl font-bold text-gray-800 mb-2">Account Balance</h2>
                <p class="text-gray-600">View your current account balance and recent activity</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Print Button -->
            <div class="mb-6 flex justify-end no-print">
                <button onclick="printBalance()" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition duration-300 font-medium flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                    </svg>
                    <span>Print Balance</span>
                </button>
            </div>

            <!-- Balance Overview -->
            <div class="grid md:grid-cols-2 gap-6 mb-8">
                <!-- Current Balance -->
                <div class="bg-white rounded-lg shadow-md p-8">
                    <div class="text-center">
                        <div class="bg-green-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-2">Available Balance</h3>
                        <p class="text-4xl font-bold text-green-600 mb-4">$<?php echo number_format($balance, 2); ?></p>
                        <p class="text-sm text-gray-500">Account: <?php echo htmlspecialchars($accountNumber); ?></p>
                    </div>
                </div>

                <!-- Account Information -->
                <div class="bg-white rounded-lg shadow-md p-8">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Account Information</h3>
                    <div class="space-y-4">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Account Number:</span>
                            <span class="font-semibold"><?php echo htmlspecialchars($accountNumber); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Account Type:</span>
                            <span class="font-semibold">Savings</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Status:</span>
                            <span class="font-semibold text-green-600">Active</span>
                        </div>
                        <?php if ($lastTransaction): ?>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Last Activity:</span>
                            <span class="font-semibold"><?php echo date('M j, Y g:i A', strtotime($lastTransaction)); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>


            <!-- Recent Transactions Preview -->
            <div class="bg-white rounded-lg shadow-md p-8">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-gray-800">Recent Transactions</h3>
                    <a href="transaction_history.php" class="text-blue-600 hover:text-blue-800 font-medium">
                        View All →
                    </a>
                </div>
                
                <?php
                try {
                    $stmt = $pdo->prepare("
                        SELECT type, amount, description, status, created_at 
                        FROM transactions 
                        WHERE user_id = ? 
                        ORDER BY created_at DESC 
                        LIMIT 3
                    ");
                    $stmt->execute([$_SESSION['user_id']]);
                    $transactions = $stmt->fetchAll();
                    
                    if (empty($transactions)): ?>
                        <div class="text-center py-8">
                            <div class="bg-gray-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                            </div>
                            <p class="text-gray-500">No transactions yet</p>
                            <p class="text-sm text-gray-400">Your transaction history will appear here</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($transactions as $transaction): ?>
                                <div class="flex justify-between items-center py-3 border-b border-gray-100 last:border-b-0">
                                    <div class="flex items-center">
                                        <div class="bg-blue-100 p-2 rounded-full mr-3">
                                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-800"><?php echo ucfirst($transaction['type']); ?></p>
                                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($transaction['description'] ?: 'No description'); ?></p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <?php 
                                        // For deposits and credits, always show positive
                                        // For other transactions, check if amount is positive or negative
                                        if ($transaction['type'] === 'deposit' || $transaction['type'] === 'credit') {
                                            $isPositive = true;
                                            $displayAmount = $transaction['amount'];
                                        } else {
                                            $isPositive = $transaction['amount'] > 0;
                                            $displayAmount = abs($transaction['amount']);
                                        }
                                        $sign = $isPositive ? '+' : '-';
                                        $color = $isPositive ? 'text-green-600' : 'text-red-600';
                                        ?>
                                        <p class="font-semibold <?php echo $color; ?>">
                                            <?php echo $sign; ?>₱<?php echo number_format($displayAmount, 2); ?>
                                        </p>
                                        <p class="text-sm text-gray-500"><?php echo date('M j, g:i A', strtotime($transaction['created_at'])); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif;
                } catch(PDOException $e) {
                    echo '<div class="text-center py-8"><p class="text-gray-500">Unable to load recent transactions</p></div>';
                }
                ?>
                
                <!-- View All Transactions Link -->
                <div class="mt-4 text-center">
                    <a href="transaction_history.php" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition duration-300 font-medium">
                        View All Transactions
                    </a>
                </div>
            </div>
        </div>
    </main>

    <!-- Print Content (Hidden by default) -->
    <div class="print-content">
        <div class="print-statement">
            <h1>WeBank - Account Balance Statement</h1>
            <p>Generated on: <?php echo date('F j, Y \a\t g:i A'); ?></p>
            <p>Account Holder: <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
            <p>Account Number: <?php echo htmlspecialchars($accountNumber); ?></p>
            
            <br>
            
            <h2>Current Balance: $<?php echo number_format($balance, 2); ?></h2>
            
            <br>
            
            <h3>Recent Transactions (Last 3):</h3>
            <?php
            try {
                $stmt = $pdo->prepare("
                    SELECT type, amount, description, status, created_at 
                    FROM transactions 
                    WHERE user_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT 3
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $printTransactions = $stmt->fetchAll();
                
                if (empty($printTransactions)): ?>
                    <p>No transactions found.</p>
                <?php else: ?>
                    <?php foreach ($printTransactions as $transaction): ?>
                        <p><strong><?php echo date('M j, Y g:i A', strtotime($transaction['created_at'])); ?></strong></p>
                        <p>Type: <?php echo ucfirst($transaction['type']); ?></p>
                        <p>Description: <?php echo htmlspecialchars($transaction['description'] ?: 'No description'); ?></p>
                        <p>Amount: <?php echo ($transaction['type'] === 'deposit' || $transaction['type'] === 'credit') ? '+' : '-'; ?>$<?php echo number_format($transaction['amount'], 2); ?></p>
                        <p>Status: <?php echo ucfirst($transaction['status']); ?></p>
                        <br>
                    <?php endforeach; ?>
                <?php endif;
            } catch(PDOException $e) {
                echo '<p>Unable to load transactions for printing.</p>';
            }
            ?>
            
            <br>
            <p><em>This is a computer-generated statement. No signature required.</em></p>
            <p><em>WeBank - Your Trusted Banking Partner</em></p>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8 mt-12 no-print">
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

        // Print function
        function printBalance() {
            // Show print content before printing
            const printContent = document.querySelector('.print-content');
            printContent.style.display = 'block';
            
            // Trigger print dialog
            window.print();
            
            // Hide print content after printing
            setTimeout(() => {
                printContent.style.display = 'none';
            }, 100);
        }
    </script>
</body>
</html>

