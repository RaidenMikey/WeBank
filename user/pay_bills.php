<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $biller_id = $_POST['biller_id'];
    $amount = floatval($_POST['amount']);
    $reference_number = trim($_POST['reference_number']);
    
    if (empty($biller_id) || $amount <= 0 || empty($reference_number)) {
        $_SESSION['message'] = 'Please fill in all fields with valid values.';
        $_SESSION['messageType'] = 'error';
    } else {
        try {
            // Get user's current balance
            $stmt = $pdo->prepare("SELECT balance FROM accounts WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$_SESSION['user_id']]);
            $account = $stmt->fetch();
            
            if (!$account) {
                // Create account for user if it doesn't exist
                $accountNumber = 'WB' . str_pad($_SESSION['user_id'], 8, '0', STR_PAD_LEFT);
                $stmt = $pdo->prepare("INSERT INTO accounts (user_id, account_number, balance, status) VALUES (?, ?, 0.00, 'active')");
                $stmt->execute([$_SESSION['user_id'], $accountNumber]);
                $account = ['balance' => 0.00];
            }
            
            if ($account['balance'] < $amount) {
                $_SESSION['message'] = 'Insufficient balance. Your current balance is ₱' . number_format($account['balance'], 2);
                $_SESSION['messageType'] = 'error';
            } else {
                // Get biller information
                $stmt = $pdo->prepare("SELECT biller_name, category FROM billers WHERE id = ? AND status = 'active'");
                $stmt->execute([$biller_id]);
                $biller = $stmt->fetch();
                
                if (!$biller) {
                    $_SESSION['message'] = 'Selected biller is not available.';
                    $_SESSION['messageType'] = 'error';
                } else {
                    // Start transaction
                    $pdo->beginTransaction();
                    
                    try {
                        // Deduct amount from user's balance
                        $stmt = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE user_id = ?");
                        $stmt->execute([$amount, $_SESSION['user_id']]);
                        
                        // Generate reference_id with biller name
                        $reference_id = strtoupper($biller['biller_name']) . '_' . $biller_id . '_' . str_pad(rand(100000, 999999), 8, '0', STR_PAD_LEFT);
                        
                        // Log the transaction
                        $stmt = $pdo->prepare("
                            INSERT INTO transactions (user_id, type, amount, description, status, reference_id, biller_id, biller_reference) 
                            VALUES (?, 'bill_payment', ?, ?, 'completed', ?, ?, ?)
                        ");
                        $description = "Bill payment to " . $biller['biller_name'] . " (" . $biller['category'] . ")";
                        $stmt->execute([$_SESSION['user_id'], $amount, $description, $reference_id, $biller_id, $reference_number]);
                        
                        $pdo->commit();
                        
                        $_SESSION['message'] = 'Bill payment of ₱' . number_format($amount, 2) . ' to ' . $biller['biller_name'] . ' has been processed successfully!<br>Reference ID: <strong>' . $reference_id . '</strong>';
                        $_SESSION['messageType'] = 'success';
                        
                    } catch (Exception $e) {
                        $pdo->rollback();
                        $_SESSION['message'] = 'Payment failed: ' . $e->getMessage() . '. Please try again.';
                        $_SESSION['messageType'] = 'error';
                    }
                }
            }
        } catch (PDOException $e) {
            $_SESSION['message'] = 'Payment processing failed: ' . $e->getMessage() . '. Please try again.';
            $_SESSION['messageType'] = 'error';
        }
    }
    
    // Redirect to prevent form resubmission
    header('Location: pay_bills.php');
    exit();
}

// Get messages from session
$message = $_SESSION['message'] ?? '';
$messageType = $_SESSION['messageType'] ?? '';

// Clear messages from session
unset($_SESSION['message'], $_SESSION['messageType']);

// Get user's current balance
$stmt = $pdo->prepare("SELECT balance FROM accounts WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$account = $stmt->fetch();
$currentBalance = $account ? $account['balance'] : 0;

// Get all active billers
$stmt = $pdo->prepare("SELECT * FROM billers WHERE status = 'active' ORDER BY category, biller_name");
$stmt->execute();
$billers = $stmt->fetchAll();

$pageTitle = 'Pay Bills';
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
                        <h2 class="text-3xl font-bold text-gray-800">Pay Bills</h2>
                        <p class="text-gray-600">Pay your bills conveniently and securely</p>
                    </div>
                </div>
            </div>

            <!-- Current Balance -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Current Balance</h3>
                        <p class="text-3xl font-bold text-green-600">₱<?php echo number_format($currentBalance, 2); ?></p>
                    </div>
                    <div class="text-right">
                        <a href="check_balance.php" class="text-blue-600 hover:text-blue-800 font-medium">View Details</a>
                    </div>
                </div>
            </div>

            <!-- Message -->
            <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 text-green-700 border border-green-300' : 'bg-red-100 text-red-700 border border-red-300'; ?>">
                    <?php if ($messageType === 'success'): ?>
                        <?php echo $message; ?>
                    <?php else: ?>
                        <?php echo htmlspecialchars($message); ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Bill Payment Form -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-2xl font-bold text-gray-800 mb-6">Pay Your Bills</h3>
            
            <form method="POST" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Biller Selection -->
                    <div>
                        <label for="biller_id" class="block text-sm font-medium text-gray-700 mb-2">Select Biller</label>
                        <select name="biller_id" id="biller_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Choose a biller...</option>
                            <?php 
                            $currentCategory = '';
                            foreach ($billers as $biller): 
                                if ($biller['category'] !== $currentCategory):
                                    if ($currentCategory !== ''): ?>
                                        </optgroup>
                                    <?php endif; ?>
                                    <optgroup label="<?php echo htmlspecialchars($biller['category']); ?>">
                                    <?php $currentCategory = $biller['category']; ?>
                                <?php endif; ?>
                                <option value="<?php echo $biller['id']; ?>" <?php echo (isset($_POST['biller_id']) && $_POST['biller_id'] == $biller['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($biller['biller_name']); ?>
                                </option>
                            <?php endforeach; ?>
                            </optgroup>
                        </select>
                    </div>

                    <!-- Amount -->
                    <div>
                        <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">Amount (₱)</label>
                        <input type="number" name="amount" id="amount" step="0.01" min="1" max="50000" required 
                               value="<?php echo isset($_POST['amount']) ? htmlspecialchars($_POST['amount']) : ''; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Enter amount">
                    </div>
                </div>

                <!-- Reference Number -->
                <div>
                    <label for="reference_number" class="block text-sm font-medium text-gray-700 mb-2">Reference Number</label>
                    <input type="text" name="reference_number" id="reference_number" required 
                           value="<?php echo isset($_POST['reference_number']) ? htmlspecialchars($_POST['reference_number']) : ''; ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Enter your account/reference number">
                    <p class="text-sm text-gray-500 mt-1">This is your account number with the selected biller</p>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition duration-300 font-medium">
                        Pay Bill
                    </button>
                </div>
            </form>
        </div>

        <!-- Available Billers Info -->
        <div class="bg-white rounded-lg shadow-md p-6 mt-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Available Billers</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php 
                $billersByCategory = [];
                foreach ($billers as $biller) {
                    $billersByCategory[$biller['category']][] = $biller;
                }
                foreach ($billersByCategory as $category => $categoryBillers): ?>
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h4 class="font-medium text-gray-800 mb-2"><?php echo htmlspecialchars($category); ?></h4>
                        <ul class="space-y-1">
                            <?php foreach ($categoryBillers as $biller): ?>
                                <li class="text-sm text-gray-600"><?php echo htmlspecialchars($biller['biller_name']); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    </main>

<?php include '../includes/footer.php'; ?>
