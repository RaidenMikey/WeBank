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

// Handle admin deposit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'deposit') {
    $user_id = (int)$_POST['user_id'];
    $amount = (float)$_POST['amount'];
    $description = trim($_POST['description']);
    
    if ($user_id <= 0 || $amount <= 0) {
        $_SESSION['error'] = 'Invalid user ID or amount.';
    } else {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Check if user exists
            $stmt = $pdo->prepare("SELECT id, first_name, last_name, email FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if (!$user) {
                throw new Exception('User not found.');
            }
            
            // Get or create user's account
            $stmt = $pdo->prepare("SELECT id, balance FROM accounts WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$user_id]);
            $account = $stmt->fetch();
            
            if (!$account) {
                // Create account for user
                $accountNumber = 'WB' . str_pad($user_id, 8, '0', STR_PAD_LEFT);
                $stmt = $pdo->prepare("INSERT INTO accounts (user_id, account_number, balance, status) VALUES (?, ?, 0.00, 'active')");
                $stmt->execute([$user_id, $accountNumber]);
                $account_id = $pdo->lastInsertId();
                $current_balance = 0.00;
            } else {
                $account_id = $account['id'];
                $current_balance = $account['balance'];
            }
            
            // Update account balance
            $new_balance = $current_balance + $amount;
            $stmt = $pdo->prepare("UPDATE accounts SET balance = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$new_balance, $account_id]);
            
            // Log transaction
            $stmt = $pdo->prepare("
                INSERT INTO transactions (user_id, type, amount, description, status, reference_id) 
                VALUES (?, 'deposit', ?, ?, 'completed', ?)
            ");
            $reference_id = 'ADMIN_' . time() . '_' . $user_id;
            $stmt->execute([$user_id, $amount, $description ?: 'Admin deposit', $reference_id]);
            
            $pdo->commit();
            $_SESSION['success'] = "Successfully deposited ₱" . number_format($amount, 2) . " to " . $user['first_name'] . " " . $user['last_name'] . "'s account. New balance: ₱" . number_format($new_balance, 2);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Deposit failed: ' . $e->getMessage();
        }
    }
    
    // Redirect to prevent form resubmission
    header('Location: deposit.php');
    exit();
}

// Get messages from session
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';

// Clear messages from session
unset($_SESSION['success'], $_SESSION['error']);

// Get all users for selection
try {
    $stmt = $pdo->query("SELECT id, first_name, last_name, email, created_at FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll();
} catch(PDOException $e) {
    $users = [];
    $error = 'Unable to load users.';
}

$pageTitle = 'Fund User Account';
include '../includes/header.php';
?>
    <!-- Header -->
    <header class="bg-red-600 text-white shadow-lg no-print">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <a href="dashboard.php" class="flex items-center space-x-2 text-white hover:text-red-100 transition duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        <span>Back to Dashboard</span>
                    </a>
                    <h1 class="text-2xl font-bold text-white ml-6">Fund User Account</h1>
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
        <div class="max-w-4xl mx-auto">
            <!-- Page Title -->
            <div class="mb-8">
                <div class="flex items-center">

                    <div>
                        <h2 class="text-3xl font-bold text-gray-800">Fund User Account</h2>
                        <p class="text-gray-600">Add funds directly to a user's account</p>
                    </div>
                </div>
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

            <!-- Admin Deposit Form -->
            <div class="bg-white rounded-lg shadow-md p-8 mb-8">
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="deposit">
                    
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label for="user_id" class="block text-sm font-medium text-gray-700 mb-2">Select User</label>
                            <select id="user_id" name="user_id" required 
                                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500">
                                <option value="">Choose a user...</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>">
                                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'] . ' (' . $user['email'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">Amount (₱)</label>
                            <input id="amount" name="amount" type="number" step="0.01" min="0.01" required 
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500"
                                   placeholder="0.00">
                        </div>
                    </div>
                    
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <input id="description" name="description" type="text" 
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500"
                               placeholder="e.g., Initial funding, Salary credit, Test deposit">
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" 
                                class="bg-red-600 text-white px-8 py-3 rounded-lg hover:bg-red-700 transition duration-300 font-medium">
                            Process Deposit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

<?php include '../includes/footer.php'; ?>
