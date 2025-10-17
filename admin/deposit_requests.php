<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

$success = '';
$error = '';

// Handle deposit request approval/rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $request_id = (int)$_POST['request_id'];
    $action = $_POST['action'];
    $admin_notes = trim($_POST['admin_notes'] ?? '');
    
    try {
        // Get the deposit request
        $stmt = $pdo->prepare("
            SELECT dr.*, u.first_name, u.last_name, u.email 
            FROM deposit_requests dr 
            JOIN users u ON dr.user_id = u.id 
            WHERE dr.id = ? AND dr.status = 'pending'
        ");
        $stmt->execute([$request_id]);
        $request = $stmt->fetch();
        
        if (!$request) {
            throw new Exception('Deposit request not found or already processed.');
        }
        
        $pdo->beginTransaction();
        
        if ($action === 'approve') {
            // Update deposit request status
            $stmt = $pdo->prepare("
                UPDATE deposit_requests 
                SET status = 'approved', admin_notes = ?, processed_by = ?, processed_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            $stmt->execute([$admin_notes, $_SESSION['admin_id'], $request_id]);
            
            // Get or create user's account
            $stmt = $pdo->prepare("SELECT id, balance FROM accounts WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$request['user_id']]);
            $account = $stmt->fetch();
            
            if (!$account) {
                // Create account for user
                $accountNumber = 'WB' . str_pad($request['user_id'], 8, '0', STR_PAD_LEFT);
                $stmt = $pdo->prepare("INSERT INTO accounts (user_id, account_number, balance, status) VALUES (?, ?, 0.00, 'active')");
                $stmt->execute([$request['user_id'], $accountNumber]);
                $account_id = $pdo->lastInsertId();
                $current_balance = 0.00;
            } else {
                $account_id = $account['id'];
                $current_balance = $account['balance'];
            }
            
            // Update account balance
            $new_balance = $current_balance + $request['amount'];
            $stmt = $pdo->prepare("UPDATE accounts SET balance = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$new_balance, $account_id]);
            
            // Log transaction
            $stmt = $pdo->prepare("
                INSERT INTO transactions (user_id, type, amount, description, status, reference_id) 
                VALUES (?, 'deposit', ?, ?, 'completed', ?)
            ");
            $reference_id = 'DEPOSIT_' . $request_id . '_' . time();
            $description = 'Deposit approved: ' . ($request['description'] ?: 'Deposit request');
            $stmt->execute([$request['user_id'], $request['amount'], $description, $reference_id]);
            
            $success = "Deposit request approved. ₱" . number_format($request['amount'], 2) . " has been added to " . $request['first_name'] . " " . $request['last_name'] . "'s account.";
            
        } elseif ($action === 'reject') {
            // Update deposit request status
            $stmt = $pdo->prepare("
                UPDATE deposit_requests 
                SET status = 'rejected', admin_notes = ?, processed_by = ?, processed_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            $stmt->execute([$admin_notes, $_SESSION['admin_id'], $request_id]);
            
            $success = "Deposit request rejected for " . $request['first_name'] . " " . $request['last_name'] . ".";
        }
        
        $pdo->commit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Failed to process deposit request: ' . $e->getMessage();
    }
}

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
        LEFT JOIN users admin ON dr.processed_by = admin.id
        WHERE dr.status != 'pending'
        ORDER BY dr.processed_at DESC 
        LIMIT 20
    ");
    $processed_requests = $stmt->fetchAll();
} catch(PDOException $e) {
    $processed_requests = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deposit Requests - WeBank Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-red-600 shadow-md">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <a href="dashboard.php" class="text-red-100 hover:text-white mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                    </a>
                    <h1 class="text-2xl font-bold text-white">WeBank Admin</h1>
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
                                
                                <form method="POST" class="flex space-x-4">
                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                    <div class="flex-1">
                                        <label for="admin_notes_<?php echo $request['id']; ?>" class="block text-sm font-medium text-gray-700 mb-2">Admin Notes (Optional)</label>
                                        <textarea id="admin_notes_<?php echo $request['id']; ?>" name="admin_notes" rows="2"
                                                  class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500"
                                                  placeholder="Add notes about this request..."></textarea>
                                    </div>
                                    <div class="flex flex-col space-y-2">
                                        <button type="submit" name="action" value="approve" 
                                                class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition duration-300 font-medium">
                                            Approve
                                        </button>
                                        <button type="submit" name="action" value="reject" 
                                                class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition duration-300 font-medium">
                                            Reject
                                        </button>
                                    </div>
                                </form>
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
                                            <div class="text-sm font-semibold text-gray-900">
                                                ₱<?php echo number_format($request['amount'], 2); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $statusClass = $request['status'] === 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                                            $statusText = ucfirst($request['status']);
                                            ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusClass; ?>">
                                                <?php echo $statusText; ?>
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

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8 mt-12">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; 2024 WeBank Admin. All rights reserved.</p>
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
