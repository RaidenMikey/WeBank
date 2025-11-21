<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$transactions = [];
$error = '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

try {
    // Build query based on filter
    $whereClause = "WHERE user_id = ?";
    $params = [$_SESSION['user_id']];
    
    if ($filter !== 'all') {
        $whereClause .= " AND type = ?";
        $params[] = $filter;
    }
    
    // Get all transactions for the user
    $stmt = $pdo->prepare("
        SELECT type, amount, description, status, created_at, reference_id
        FROM transactions 
        $whereClause
        ORDER BY created_at DESC
    ");
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = 'Unable to load transaction history. Please try again.';
}

$pageTitle = 'Transaction History';
include '../includes/header.php';
include '../includes/navbar_user.php';
?>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <div class="max-w-7xl mx-auto">
            <!-- Page Title -->
            <div class="mb-8">
                <div class="flex items-center">
                    <a href="dashboard.php" class="text-blue-600 hover:text-blue-800 mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                    </a>
                    <div>
                        <h2 class="text-3xl font-bold text-gray-800">Transaction History</h2>
                        <p class="text-gray-600">View all your past transactions</p>
                    </div>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Filter Options -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Filter Transactions</h3>
                <div class="flex flex-wrap gap-2">
                    <a href="?filter=all" class="px-4 py-2 rounded-lg <?php echo $filter === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> transition duration-300">
                        All Transactions
                    </a>
                    <a href="?filter=deposit" class="px-4 py-2 rounded-lg <?php echo $filter === 'deposit' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> transition duration-300">
                        Deposits
                    </a>
                    <a href="?filter=transfer" class="px-4 py-2 rounded-lg <?php echo $filter === 'transfer' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> transition duration-300">
                        Transfers
                    </a>
                    <a href="?filter=bill_payment" class="px-4 py-2 rounded-lg <?php echo $filter === 'bill_payment' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> transition duration-300">
                        Bill Payments
                    </a>
                </div>
            </div>

            <!-- Transaction History -->
            <div class="bg-white rounded-lg shadow-md p-8">
                <?php if (empty($transactions)): ?>
                    <div class="text-center py-12">
                        <div class="bg-gray-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">No Transactions Yet</h3>
                        <p class="text-gray-600 mb-6">Your transaction history will appear here once you start using your account.</p>
                        <a href="dashboard.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition duration-300 font-medium">
                            Back to Dashboard
                        </a>
                    </div>
                <?php else: ?>
                    <div class="mb-6">
                        <h3 class="text-xl font-bold text-gray-800 mb-4">
                            <?php 
                            $filterText = $filter === 'all' ? 'All Transactions' : ucfirst($filter) . ' Transactions';
                            echo $filterText . ' (' . count($transactions) . ' total)';
                            ?>
                        </h3>
                        
                        <!-- Transaction Summary -->
                        <?php if ($filter === 'all'): ?>
                            <?php
                            $depositCount = 0;
                            $transferCount = 0;
                            $billCount = 0;
                            $totalDeposits = 0;
                            $totalTransfers = 0;
                            $totalBills = 0;
                            
                            foreach ($transactions as $transaction) {
                                if ($transaction['type'] === 'deposit') {
                                    $depositCount++;
                                    $totalDeposits += $transaction['amount'];
                                } elseif ($transaction['type'] === 'transfer') {
                                    $transferCount++;
                                    $totalTransfers += abs($transaction['amount']);
                                } elseif ($transaction['type'] === 'bill_payment') {
                                    $billCount++;
                                    $totalBills += abs($transaction['amount']);
                                }
                            }
                            ?>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                    <div class="flex items-center">
                                        <div class="bg-green-100 p-2 rounded-full mr-3">
                                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm text-green-600 font-medium">Deposits</p>
                                            <p class="text-lg font-bold text-green-800">₱<?php echo number_format($totalDeposits, 2); ?></p>
                                            <p class="text-xs text-green-600"><?php echo $depositCount; ?> transactions</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                    <div class="flex items-center">
                                        <div class="bg-blue-100 p-2 rounded-full mr-3">
                                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm text-blue-600 font-medium">Transfers</p>
                                            <p class="text-lg font-bold text-blue-800">₱<?php echo number_format($totalTransfers, 2); ?></p>
                                            <p class="text-xs text-blue-600"><?php echo $transferCount; ?> transactions</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                                    <div class="flex items-center">
                                        <div class="bg-purple-100 p-2 rounded-full mr-3">
                                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm text-purple-600 font-medium">Bill Payments</p>
                                            <p class="text-lg font-bold text-purple-800">₱<?php echo number_format($totalBills, 2); ?></p>
                                            <p class="text-xs text-purple-600"><?php echo $billCount; ?> transactions</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($transactions as $transaction): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="bg-blue-100 p-2 rounded-full mr-3">
                                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                                    </svg>
                                                </div>
                                                <span class="text-sm font-medium text-gray-900">
                                                    <?php echo ucfirst($transaction['type']); ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                <?php echo htmlspecialchars($transaction['description'] ?: 'No description'); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
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
                                            <div class="text-sm font-semibold <?php echo $color; ?>">
                                                <?php echo $sign; ?>₱<?php echo number_format($displayAmount, 2); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $transaction['status'] === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                                <?php echo ucfirst($transaction['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('M j, Y g:i A', strtotime($transaction['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($transaction['reference_id']); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-6 flex justify-between items-center">
                        <a href="dashboard.php" class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition duration-300 font-medium">
                            Back to Dashboard
                        </a>
                        <a href="check_balance.php" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition duration-300 font-medium">
                            Check Balance
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

<?php include '../includes/footer.php'; ?>
