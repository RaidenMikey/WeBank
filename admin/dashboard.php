<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

// Get Quick Stats
try {
    // Total Users
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $total_users = $stmt->fetchColumn();

    // Pending Deposit Requests
    $stmt = $pdo->query("SELECT COUNT(*) FROM deposit_requests WHERE status = 'pending'");
    $pending_requests = $stmt->fetchColumn();

    // Total Deposits (Completed)
    $stmt = $pdo->query("SELECT SUM(amount) FROM deposit_requests WHERE status = 'processed'");
    $total_deposits = $stmt->fetchColumn() ?: 0;

} catch(PDOException $e) {
    $total_users = 0;
    $pending_requests = 0;
    $total_deposits = 0;
}

// System Statistics Queries
try {
    // Total System Assets (Sum of all user balances)
    $stmt = $pdo->query("SELECT SUM(balance) FROM accounts");
    $total_system_assets = $stmt->fetchColumn() ?: 0;

    // Active Users Count (All users are considered active/users as per current schema)
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $active_users_count = $stmt->fetchColumn() ?: 0;

    // Total Transaction Flow (All time volume)
    $stmt = $pdo->query("SELECT SUM(ABS(amount)) FROM transactions WHERE status = 'completed'");
    $total_transaction_volume = $stmt->fetchColumn() ?: 0;

    // Pending Requests Count
    $stmt = $pdo->query("SELECT COUNT(*) FROM deposit_requests WHERE status = 'pending'");
    $pending_requests = $stmt->fetchColumn() ?: 0;
    
    // Top Billers (Keep this for the table for now, or fetch via API? Let's keep for SSR Table)
    $stmt = $pdo->query("
        SELECT b.biller_name, b.category, COUNT(t.id) as payment_count, SUM(ABS(t.amount)) as total_paid
        FROM transactions t
        JOIN billers b ON t.biller_id = b.id
        WHERE t.type = 'bill_payment' AND t.status = 'completed'
        GROUP BY b.id, b.biller_name, b.category
        ORDER BY payment_count DESC
        LIMIT 5
    ");
    $top_billers = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    // Handle errors gracefully
    $total_system_assets = 0;
    $active_users_count = 0;
    $total_transaction_volume = 0;
    $pending_requests = 0;
    $top_billers = [];
}

// Get recent admin transactions
try {
    $stmt = $pdo->query("
        SELECT t.*, u.first_name, u.last_name 
        FROM transactions t 
        JOIN users u ON t.user_id = u.id 
        WHERE t.reference_id LIKE 'ADMIN_%' 
        ORDER BY t.created_at DESC 
        LIMIT 10
    ");
    $recent_transactions = $stmt->fetchAll();
} catch(PDOException $e) {
    $recent_transactions = [];
}

$pageTitle = 'Admin Dashboard';
$bodyClass = 'bg-gray-50 flex h-screen overflow-hidden';
include '../includes/header.php';
include '../includes/navbar_admin.php';
?>

    <!-- Main Content -->
    <script src="js/chart.min.js"></script>
    <main class="container mx-auto px-4 py-8">
        <div class="max-w-7xl mx-auto">
            <!-- Page Title -->
            <div class="mb-8 flex justify-between items-center">
                <div>
                    <h2 class="text-3xl font-bold text-gray-800 mb-2">Dashboard Overview</h2>
                    <p class="text-gray-600">System performance and financial statistics</p>
                </div>
                <div class="text-sm text-gray-500">
                    Last updated: <?php echo date('M j, Y g:i A'); ?>
                </div>
            </div>

            <!-- Overview Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total System Assets -->
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Total System Assets</p>
                            <h3 class="text-2xl font-bold text-gray-800">₱<?php echo number_format($total_system_assets, 2); ?></h3>
                        </div>
                        <div class="p-3 bg-green-100 rounded-full">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total Transaction Volume -->
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-purple-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Total Transaction Vol.</p>
                            <h3 class="text-2xl font-bold text-gray-800">₱<?php echo number_format($total_transaction_volume, 2); ?></h3>
                        </div>
                        <div class="p-3 bg-purple-100 rounded-full">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Active Users -->
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Active Users</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($active_users_count); ?></h3>
                            <p class="text-xs text-gray-500">Total: <?php echo number_format($total_users); ?></p>
                        </div>
                        <div class="p-3 bg-blue-100 rounded-full">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Pending Requests -->
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-yellow-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Pending Requests</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($pending_requests); ?></h3>
                            <a href="deposit_requests.php" class="text-xs text-yellow-600 hover:text-yellow-800 mt-1 inline-block">Review Requests &rarr;</a>
                        </div>
                        <div class="p-3 bg-yellow-100 rounded-full">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
                <!-- Transaction History Chart -->
                <div class="bg-white rounded-lg shadow-md p-6 lg:col-span-2">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Transaction Volume (Last 7 Days)</h3>
                    <div class="relative h-64 w-full">
                        <canvas id="transactionChart"></canvas>
                    </div>
                </div>

                <!-- Transaction Types Chart -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Transaction Distribution</h3>
                    <div class="relative h-64 w-full flex justify-center">
                        <canvas id="typeChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- New Advanced Charts -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-8">
                <!-- User Growth -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">User Growth (30 Days)</h3>
                    <div class="relative h-64 w-full">
                        <canvas id="userGrowthChart"></canvas>
                    </div>
                </div>

                <!-- Deposit Status -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Deposit Request Status</h3>
                    <div class="relative h-64 w-full flex justify-center">
                        <canvas id="depositStatusChart"></canvas>
                    </div>
                </div>

                <!-- Bill Categories -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Spending by Category</h3>
                    <div class="relative h-64 w-full flex justify-center">
                        <canvas id="billCategoryChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
                <!-- Top Billers -->
                <div class="bg-white rounded-lg shadow-md p-6 lg:col-span-1">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Top 5 Billers</h3>
                    <?php if (empty($top_billers)): ?>
                        <p class="text-gray-500 text-sm">No bill payment data available.</p>
                    <?php else: ?>
                        <div class="overflow-hidden">
                            <table class="min-w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Biller</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php foreach ($top_billers as $biller): ?>
                                        <tr>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                                                <div class="font-medium"><?php echo htmlspecialchars($biller['biller_name']); ?></div>
                                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($biller['category']); ?></div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-medium text-gray-900">
                                                ₱<?php echo number_format($biller['total_paid']); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Admin Action Quick Links -->
                 <div class="bg-white rounded-lg shadow-md p-6 lg:col-span-2">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Quick Actions</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <a href="deposit.php" class="flex flex-col items-center justify-center p-6 bg-green-50 rounded-lg hover:bg-green-100 transition duration-300 border border-green-200 group">
                            <div class="bg-green-100 p-3 rounded-full mb-3 group-hover:bg-green-200 transition">
                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                            </div>
                            <span class="text-green-800 font-semibold">Fund User</span>
                            <span class="text-xs text-green-600 mt-1">Manual Deposit</span>
                        </a>

                        <a href="deposit_requests.php" class="flex flex-col items-center justify-center p-6 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition duration-300 border border-yellow-200 group">
                            <div class="bg-yellow-100 p-3 rounded-full mb-3 group-hover:bg-yellow-200 transition">
                                <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <span class="text-yellow-800 font-semibold">Review Requests</span>
                            <span class="text-xs text-yellow-600 mt-1">Pending Deposits</span>
                        </a>

                        <a href="users.php" class="flex flex-col items-center justify-center p-6 bg-blue-50 rounded-lg hover:bg-blue-100 transition duration-300 border border-blue-200 group">
                            <div class="bg-blue-100 p-3 rounded-full mb-3 group-hover:bg-blue-200 transition">
                                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                            </div>
                            <span class="text-blue-800 font-semibold">Manage Users</span>
                            <span class="text-xs text-blue-600 mt-1">View & Edit Users</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Admin Transactions -->
            <div class="bg-white rounded-lg shadow-md p-8">
                <h3 class="text-2xl font-bold text-gray-800 mb-6">Recent Admin Transactions</h3>
                
                <?php if (empty($recent_transactions)): ?>
                    <div class="text-center py-8">
                        <div class="bg-gray-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                        <p class="text-gray-500">No admin transactions yet</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($recent_transactions as $transaction): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($transaction['first_name'] . ' ' . $transaction['last_name']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-semibold text-green-600">
                                                +₱<?php echo number_format($transaction['amount'], 2); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                <?php echo htmlspecialchars($transaction['description']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                <?php echo ucfirst($transaction['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('M j, Y g:i A', strtotime($transaction['created_at'])); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="js/dashboard_charts.js?v=<?php echo time(); ?>"></script>
<?php include '../includes/footer.php'; ?>
