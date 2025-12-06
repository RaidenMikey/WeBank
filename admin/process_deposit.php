<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

$request_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user = null;
$account = null;
$current_request = null;
$pending_requests = [];
$processed_requests = [];
$error = '';
$success = '';

// Handle deposit request processing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'process') {
    $post_request_id = (int)($_POST['request_id'] ?? 0);
    $get_request_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $admin_notes = trim($_POST['admin_notes'] ?? '');
    $transaction_started = false;
    
    // Use POST request_id, but validate it matches GET id if both are present
    $request_id = $post_request_id;
    
    // Check if admin_id is set
    if (!isset($_SESSION['admin_id'])) {
        $error = 'Admin session not found. Please log in again.';
    } elseif ($request_id <= 0) {
        $error = 'Invalid deposit request ID.';
    } elseif ($get_request_id > 0 && $post_request_id !== $get_request_id) {
        // If both GET and POST have IDs, they should match
        $error = 'Request ID mismatch. Please refresh the page and try again.';
    } else {
        try {
        // First check if the request exists at all
        $stmt = $pdo->prepare("
            SELECT dr.*, u.first_name, u.last_name, u.email 
            FROM deposit_requests dr 
            JOIN users u ON dr.user_id = u.id 
            WHERE dr.id = ?
        ");
        $stmt->execute([$request_id]);
        $request = $stmt->fetch();
        
        if (!$request) {
            throw new Exception('Deposit request not found.');
        }
        
        // Check if already processed (trim and normalize status)
        $status = trim(strtolower($request['status'] ?? ''));
        if ($status !== 'pending') {
            $_SESSION['error'] = 'This deposit request has already been processed.';
            header('Location: process_deposit.php?id=' . $request_id);
            exit();
        }
        
        $pdo->beginTransaction();
        $transaction_started = true;
        
        // Update deposit request status (only if still pending to prevent double-processing)
        $stmt = $pdo->prepare("
            UPDATE deposit_requests 
            SET status = 'processed', admin_notes = ?, processed_by = ?, processed_at = CURRENT_TIMESTAMP 
            WHERE id = ? AND status = 'pending'
        ");
        $stmt->execute([$admin_notes, $_SESSION['admin_id'], $request_id]);
        
        // Check if the update actually affected a row
        if ($stmt->rowCount() === 0) {
            throw new Exception('Unable to update deposit request. The request may have been processed by another admin or the status may have changed.');
        }
        
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
        $reference_id = $request['reference_number'] ?? 'DEPOSIT_' . $request_id . '_' . time();
        $description = 'Deposit processed: ' . ($request['description'] ?: 'Deposit request');
        $stmt->execute([$request['user_id'], $request['amount'], $description, $reference_id]);
        
        $pdo->commit();
        $transaction_started = false;
        
        $_SESSION['success'] = "Deposit request processed. ₱" . number_format($request['amount'], 2) . " has been added to " . $request['first_name'] . " " . $request['last_name'] . "'s account.";
        header('Location: process_deposit.php?id=' . $request_id);
        exit();
        
        } catch (Exception $e) {
            if ($transaction_started) {
                $pdo->rollBack();
            }
            $error = 'Failed to process deposit request: ' . $e->getMessage();
        }
    }
}

// Get messages from session
$success = $_SESSION['success'] ?? '';
$error = $error ?: ($_SESSION['error'] ?? '');
unset($_SESSION['success'], $_SESSION['error']);

// Get deposit request and user information
if ($request_id > 0) {
    try {
        // Get the current deposit request with user info
        $stmt = $pdo->prepare("
            SELECT dr.*, u.* 
            FROM deposit_requests dr 
            JOIN users u ON dr.user_id = u.id 
            WHERE dr.id = ?
        ");
        $stmt->execute([$request_id]);
        $current_request = $stmt->fetch();
        
        if (!$current_request) {
            $error = 'Deposit request not found.';
        } else {
            // Check if status is "processed" but transaction might not have completed
            // Verify if the deposit was actually processed by checking for transaction record
            if (trim(strtolower($current_request['status'])) === 'processed') {
                // Check for transaction with matching reference number
                $ref_number = $current_request['reference_number'] ?? '';
                $verify_stmt = $pdo->prepare("
                    SELECT COUNT(*) as tx_count 
                    FROM transactions 
                    WHERE user_id = ? 
                    AND reference_id = ? 
                    AND type = 'deposit'
                    AND status = 'completed'
                ");
                $verify_stmt->execute([$current_request['user_id'], $ref_number]);
                $tx_check = $verify_stmt->fetch();
                
                // If status is processed but no transaction found, reset to pending
                if ($tx_check['tx_count'] == 0) {
                    // Reset status back to pending so it can be processed again
                    $reset_stmt = $pdo->prepare("
                        UPDATE deposit_requests 
                        SET status = 'pending', processed_by = NULL, processed_at = NULL 
                        WHERE id = ? AND status = 'processed'
                    ");
                    $reset_stmt->execute([$request_id]);
                    
                    // Reload the request data
                    $stmt = $pdo->prepare("
                        SELECT dr.*, u.* 
                        FROM deposit_requests dr 
                        JOIN users u ON dr.user_id = u.id 
                        WHERE dr.id = ?
                    ");
                    $stmt->execute([$request_id]);
                    $current_request = $stmt->fetch();
                    
                    // Silently reset without showing error message - it will appear as pending now
                }
            }
            // Get user details
            $user = [
                'id' => $current_request['user_id'],
                'first_name' => $current_request['first_name'],
                'last_name' => $current_request['last_name'],
                'email' => $current_request['email'],
                'phone' => $current_request['phone'],
                'created_at' => $current_request['created_at']
            ];
            
            // Get user's account information
            $stmt = $pdo->prepare("
                SELECT * FROM accounts 
                WHERE user_id = ? AND status = 'active' 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$user['id']]);
            $account = $stmt->fetch();
            
            // Get all pending deposit requests for this user
            $stmt = $pdo->prepare("
                SELECT dr.*, admin.first_name as admin_first_name, admin.last_name as admin_last_name
                FROM deposit_requests dr
                LEFT JOIN admins admin ON dr.processed_by = admin.id
                WHERE dr.user_id = ? AND dr.status = 'pending'
                ORDER BY dr.created_at DESC
            ");
            $stmt->execute([$user['id']]);
            $pending_requests = $stmt->fetchAll();
            
            // Get all processed deposit requests for this user
            $stmt = $pdo->prepare("
                SELECT dr.*, admin.first_name as admin_first_name, admin.last_name as admin_last_name
                FROM deposit_requests dr
                LEFT JOIN admins admin ON dr.processed_by = admin.id
                WHERE dr.user_id = ? AND dr.status = 'processed'
                ORDER BY dr.processed_at DESC
            ");
            $stmt->execute([$user['id']]);
            $processed_requests = $stmt->fetchAll();
        }
    } catch(PDOException $e) {
        $error = 'Unable to load deposit request information.';
    }
} else {
    $error = 'Invalid deposit request ID.';
}
?>

$pageTitle = 'Process Deposit Request';
$bodyClass = 'bg-gray-50 flex h-screen overflow-hidden';
include '../includes/header.php';
include '../includes/navbar_admin.php';
?>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto">
            <!-- Back Button -->
            <div class="mb-6">
                 <a href="deposit_requests.php" class="inline-flex items-center text-gray-600 hover:text-red-600 transition duration-300">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Deposit Requests
                </a>
            </div>


            <?php if ($error || !$current_request): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <?php echo htmlspecialchars($error ?: 'Deposit request not found.'); ?>
                    <a href="deposit_requests.php" class="underline ml-2">Go back to deposit requests</a>
                </div>
            <?php else: ?>
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

                <!-- User Details Section -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">User Details</h2>
                    
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

                    <?php if ($account): ?>
                        <div class="mt-6 pt-6 border-t border-gray-200">
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
                                    <p class="text-sm text-gray-500 mb-1">Current Balance</p>
                                    <p class="text-2xl font-bold text-green-600">
                                        ₱<?php echo number_format($account['balance'], 2); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <p class="text-sm text-gray-500">No active account found. Account will be created when deposit is processed.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Current Deposit Request Section -->
                <?php if ($current_request['status'] === 'pending'): ?>
                    <div class="bg-white rounded-lg shadow-md p-6 mb-6 border-2 border-yellow-300">
                        <h2 class="text-2xl font-bold text-gray-800 mb-6">Deposit Request to Process</h2>
                        
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <p class="text-sm font-mono text-gray-700 mb-2"><strong>Reference Number:</strong> <?php echo htmlspecialchars($current_request['reference_number'] ?? 'N/A'); ?></p>
                                    <p class="text-sm text-gray-600">Requested: <?php echo date('M j, Y g:i A', strtotime($current_request['created_at'])); ?></p>
                                </div>
                                <div class="text-right">
                                    <div class="text-3xl font-bold text-green-600">
                                        ₱<?php echo number_format($current_request['amount'], 2); ?>
                                    </div>
                                    <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        Pending
                                    </span>
                                </div>
                            </div>
                            
                            <?php if ($current_request['description']): ?>
                                <div class="mb-4">
                                    <p class="text-sm text-gray-600"><strong>Description:</strong> <?php echo htmlspecialchars($current_request['description']); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" class="mt-4" id="processForm">
                                <input type="hidden" name="request_id" id="form_request_id" value="<?php echo (int)$current_request['id']; ?>">
                                <input type="hidden" name="action" value="process">
                                <div class="mb-4">
                                    <label for="admin_notes" class="block text-sm font-medium text-gray-700 mb-2">Admin Notes (Optional)</label>
                                    <textarea id="admin_notes" name="admin_notes" rows="3"
                                              class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500"
                                              placeholder="Add notes about this request..."><?php echo htmlspecialchars($current_request['admin_notes'] ?? ''); ?></textarea>
                                </div>
                                <div class="flex justify-end">
                                    <button type="submit" 
                                            class="bg-green-600 text-white px-8 py-3 rounded-lg hover:bg-green-700 transition duration-300 font-medium">
                                        Process Deposit
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                        <h2 class="text-2xl font-bold text-gray-800 mb-6">Deposit Request Details</h2>
                        
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <p class="text-sm font-mono text-gray-700 mb-2"><strong>Reference Number:</strong> <?php echo htmlspecialchars($current_request['reference_number'] ?? 'N/A'); ?></p>
                                    <p class="text-sm text-gray-600">Requested: <?php echo date('M j, Y g:i A', strtotime($current_request['created_at'])); ?></p>
                                    <?php if ($current_request['processed_at']): ?>
                                        <p class="text-sm text-gray-600">Processed: <?php echo date('M j, Y g:i A', strtotime($current_request['processed_at'])); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="text-right">
                                    <div class="text-3xl font-bold text-green-600">
                                        ₱<?php echo number_format($current_request['amount'], 2); ?>
                                    </div>
                                    <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Processed
                                    </span>
                                </div>
                            </div>
                            
                            <?php if ($current_request['description']): ?>
                                <div class="mb-4">
                                    <p class="text-sm text-gray-600"><strong>Description:</strong> <?php echo htmlspecialchars($current_request['description']); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($current_request['admin_notes']): ?>
                                <div class="mb-4">
                                    <p class="text-sm text-gray-600"><strong>Admin Notes:</strong> <?php echo htmlspecialchars($current_request['admin_notes']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Deposit Request History Section -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Deposit Request History</h2>
                    
                    <!-- Tabs -->
                    <div class="border-b border-gray-200 mb-6">
                        <nav class="-mb-px flex space-x-8">
                            <button onclick="showTab('pending')" id="tab-pending" class="tab-button border-b-2 border-red-600 py-4 px-1 text-sm font-medium text-red-600">
                                Pending (<?php echo count($pending_requests); ?>)
                            </button>
                            <button onclick="showTab('processed')" id="tab-processed" class="tab-button border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                                Processed (<?php echo count($processed_requests); ?>)
                            </button>
                        </nav>
                    </div>

                    <!-- Pending Tab Content -->
                    <div id="content-pending" class="tab-content">
                        <?php if (empty($pending_requests)): ?>
                            <div class="text-center py-8">
                                <p class="text-gray-500">No pending deposit requests</p>
                            </div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference Number</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Requested</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($pending_requests as $request): ?>
                                            <tr class="hover:bg-gray-50 <?php echo $request['id'] == $current_request['id'] ? 'bg-yellow-50' : ''; ?>">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-mono font-semibold text-gray-900">
                                                    <?php echo htmlspecialchars($request['reference_number'] ?? 'N/A'); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                                    ₱<?php echo number_format($request['amount'], 2); ?>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-900">
                                                    <?php echo htmlspecialchars($request['description'] ?: 'No description'); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?php echo date('M j, Y g:i A', strtotime($request['created_at'])); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Processed Tab Content -->
                    <div id="content-processed" class="tab-content hidden">
                        <?php if (empty($processed_requests)): ?>
                            <div class="text-center py-8">
                                <p class="text-gray-500">No processed deposit requests</p>
                            </div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference Number</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Processed By</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Processed</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($processed_requests as $request): ?>
                                            <tr class="hover:bg-gray-50 <?php echo $request['id'] == $current_request['id'] ? 'bg-green-50' : ''; ?>">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-mono font-semibold text-gray-900">
                                                    <?php echo htmlspecialchars($request['reference_number'] ?? 'N/A'); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                                    ₱<?php echo number_format($request['amount'], 2); ?>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-900">
                                                    <?php echo htmlspecialchars($request['description'] ?: 'No description'); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?php 
                                                    if ($request['processed_by'] && $request['admin_first_name']) {
                                                        echo htmlspecialchars($request['admin_first_name'] . ' ' . $request['admin_last_name']);
                                                    } else {
                                                        echo 'N/A';
                                                    }
                                                    ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?php echo $request['processed_at'] ? date('M j, Y g:i A', strtotime($request['processed_at'])) : 'N/A'; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
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

        // Tab functionality
        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            
            // Remove active styles from all tabs
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('border-red-600', 'text-red-600');
                button.classList.add('border-transparent', 'text-gray-500');
            });
            
            // Show selected tab content
            document.getElementById('content-' + tabName).classList.remove('hidden');
            
            // Add active styles to selected tab
            const activeTab = document.getElementById('tab-' + tabName);
            activeTab.classList.remove('border-transparent', 'text-gray-500');
            activeTab.classList.add('border-red-600', 'text-red-600');
        }

        // Ensure form always has the correct request ID from URL
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const urlId = urlParams.get('id');
            const formRequestId = document.getElementById('form_request_id');
            
            if (urlId && formRequestId) {
                // Ensure the form uses the ID from the URL
                formRequestId.value = urlId;
                
                // Add form validation before submit
                const processForm = document.getElementById('processForm');
                if (processForm) {
                    processForm.addEventListener('submit', function(e) {
                        const formId = formRequestId.value;
                        if (formId !== urlId) {
                            e.preventDefault();
                            alert('Request ID mismatch detected. Please refresh the page and try again.');
                            return false;
                        }
                    });
                }
            }
        });
    </script>
<?php include '../includes/footer.php'; ?>

