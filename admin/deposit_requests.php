<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

// Get messages from session (for display after redirect from process_deposit.php)
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';

// Clear messages from session
unset($_SESSION['success'], $_SESSION['error']);

// Get all pending deposit requests
try {
    $stmt = $pdo->query("
        SELECT dr.*, u.first_name, u.last_name, u.email 
        FROM deposit_requests dr 
        JOIN users u ON dr.user_id = u.id 
        WHERE dr.status = 'pending'
        ORDER BY dr.created_at ASC
    ");
    $pending_requests = $stmt->fetchAll();
} catch(PDOException $e) {
    $pending_requests = [];
}

// Get recent processed requests
try {
    $stmt = $pdo->query("
        SELECT dr.*, u.first_name, u.last_name, u.email, admin.first_name as admin_first_name, admin.last_name as admin_last_name
        FROM deposit_requests dr 
        JOIN users u ON dr.user_id = u.id 
        LEFT JOIN admins admin ON dr.processed_by = admin.id
        WHERE dr.status = 'processed'
        ORDER BY dr.processed_at DESC 
        LIMIT 20
    ");
    $processed_requests = $stmt->fetchAll();
} catch(PDOException $e) {
    $processed_requests = [];
}

$pageTitle = 'Deposit Requests';
include '../includes/header.php';
include '../includes/navbar_admin.php';
?>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto">
            <!-- Page Title -->
            <div class="mb-8">
                <h2 class="text-3xl font-bold text-gray-800 mb-2">Deposit Requests</h2>
                <p class="text-gray-600">Review and approve user deposit requests</p>
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

            <!-- Pending Requests -->
            <div class="bg-white rounded-lg shadow-md p-8 mb-8">
                <h3 class="text-2xl font-bold text-gray-800 mb-6">Pending Requests (<?php echo count($pending_requests); ?>)</h3>
                
                <?php if (empty($pending_requests)): ?>
                    <div class="text-center py-8">
                        <div class="bg-gray-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                        <p class="text-gray-500">No pending deposit requests</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-6">
                        <?php foreach ($pending_requests as $request): ?>
                            <div class="border border-gray-200 rounded-lg p-6">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h4 class="text-lg font-semibold text-gray-800">
                                            <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?>
                                        </h4>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($request['email']); ?></p>
                                        <p class="text-sm font-mono text-gray-700"><strong>Reference:</strong> <?php echo htmlspecialchars($request['reference_number'] ?? 'N/A'); ?></p>
                                        <p class="text-sm text-gray-500">Requested: <?php echo date('M j, Y g:i A', strtotime($request['created_at'])); ?></p>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-2xl font-bold text-green-600">
                                            ₱<?php echo number_format($request['amount'], 2); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if ($request['description']): ?>
                                    <div class="mb-4">
                                        <p class="text-sm text-gray-600"><strong>Description:</strong> <?php echo htmlspecialchars($request['description']); ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="flex justify-end">
                                    <a href="process_deposit.php?id=<?php echo $request['id']; ?>" 
                                       class="bg-green-600 text-white px-8 py-2 rounded-lg hover:bg-green-700 transition duration-300 font-medium">
                                        Process Deposit
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Processed Requests -->
            <div class="bg-white rounded-lg shadow-md p-8">
                <h3 class="text-2xl font-bold text-gray-800 mb-6">Recent Processed Requests</h3>
                
                <?php if (empty($processed_requests)): ?>
                    <div class="text-center py-8">
                        <p class="text-gray-500">No processed requests yet</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference Number</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Processed By</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($processed_requests as $request): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                <?php echo htmlspecialchars($request['email']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-mono font-semibold text-gray-900">
                                                <?php echo htmlspecialchars($request['reference_number'] ?? 'N/A'); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-semibold text-gray-900">
                                                ₱<?php echo number_format($request['amount'], 2); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                Processed
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo $request['admin_first_name'] ? htmlspecialchars($request['admin_first_name'] . ' ' . $request['admin_last_name']) : 'System'; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('M j, Y g:i A', strtotime($request['processed_at'])); ?>
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
