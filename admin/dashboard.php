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
    $stmt = $pdo->query("SELECT SUM(amount) FROM transactions WHERE type = 'deposit' AND status = 'completed'");
    $total_deposits = $stmt->fetchColumn() ?: 0.00;

} catch(PDOException $e) {
    $total_users = 0;
    $pending_requests = 0;
    $total_deposits = 0;
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
include '../includes/header.php';
include '../includes/navbar_admin.php';
?>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto">
            <!-- Welcome Section -->
            <div class="bg-white rounded-lg shadow-md p-8 mb-8">
                <h2 class="text-3xl font-bold text-gray-800 mb-4">Welcome, Admin</h2>
                <p class="text-gray-600 text-lg">
                    Hello <?php echo htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_username']); ?>! 
                    You have full control over the WeBank system.
                </p>
            </div>

            <!-- Quick Stats -->
            <div class="grid md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="bg-blue-100 p-3 rounded-full">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-800">Total Users</h3>
                            <p class="text-2xl font-bold text-blue-600"><?php echo number_format($total_users); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="bg-yellow-100 p-3 rounded-full">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-800">Pending Requests</h3>
                            <p class="text-2xl font-bold text-yellow-600"><?php echo number_format($pending_requests); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="bg-green-100 p-3 rounded-full">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-800">Total Deposits</h3>
                            <p class="text-2xl font-bold text-green-600">₱<?php echo number_format($total_deposits, 2); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Admin Actions -->
            <div class="bg-white rounded-lg shadow-md p-8 mb-8">
                <h3 class="text-2xl font-bold text-gray-800 mb-6">Admin Actions</h3>
                <div class="grid md:grid-cols-3 gap-6">
                    <!-- Fund User -->
                    <div class="bg-red-50 border-2 border-red-200 rounded-lg p-6 text-center relative">
                        <div class="bg-red-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-800 mb-2">Fund User Account</h4>
                        <p class="text-gray-600 text-sm mb-4">Directly add funds to a user's account</p>
                        <a href="deposit.php" class="w-full bg-red-600 text-white py-2 px-4 rounded-lg hover:bg-red-700 transition duration-300 font-medium inline-block text-center">
                            Fund User
                        </a>
                    </div>

                    <!-- Review Requests -->
                    <div class="bg-red-50 border-2 border-red-200 rounded-lg p-6 text-center relative">
                        <?php if ($pending_requests > 0): ?>
                            <div class="absolute top-2 right-2">
                                <span class="bg-yellow-500 text-white text-xs px-2 py-1 rounded-full"><?php echo $pending_requests; ?> Pending</span>
                            </div>
                        <?php endif; ?>
                        <div class="bg-red-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-800 mb-2">Review Requests</h4>
                        <p class="text-gray-600 text-sm mb-4">Approve or reject deposit requests</p>
                        <a href="deposit_requests.php" class="w-full bg-red-600 text-white py-2 px-4 rounded-lg hover:bg-red-700 transition duration-300 font-medium inline-block text-center">
                            Review Requests
                        </a>
                    </div>

                    <!-- Manage Users -->
                    <div class="bg-red-50 border-2 border-red-200 rounded-lg p-6 text-center relative">
                        <div class="bg-red-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-800 mb-2">Manage Users</h4>
                        <p class="text-gray-600 text-sm mb-4">View and manage user accounts</p>
                        <a href="users.php" class="w-full bg-red-600 text-white py-2 px-4 rounded-lg hover:bg-red-700 transition duration-300 font-medium inline-block text-center">
                            Manage Users
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

<?php include '../includes/footer.php'; ?>

