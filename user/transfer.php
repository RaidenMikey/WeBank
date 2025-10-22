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
    $recipient_identifier = trim($_POST['recipient_identifier']);
    $amount = floatval($_POST['amount']);
    $description = trim($_POST['description']);
    $password = $_POST['password'] ?? '';
    
    if (empty($recipient_identifier) || $amount <= 0) {
        $_SESSION['message'] = 'Please fill in all required fields with valid values.';
        $_SESSION['messageType'] = 'error';
    } elseif (empty($password)) {
        $_SESSION['message'] = 'Password is required to confirm this transfer.';
        $_SESSION['messageType'] = 'error';
    } else {
        try {
            // Verify user's password first
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($password, $user['password'])) {
                $_SESSION['message'] = 'Invalid password. Please try again.';
                $_SESSION['messageType'] = 'error';
            } else {
                // Get sender's current balance and account number
                $stmt = $pdo->prepare("SELECT balance, account_number FROM accounts WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1");
                $stmt->execute([$_SESSION['user_id']]);
                $senderAccount = $stmt->fetch();
                
                if (!$senderAccount) {
                    // Create account for sender if it doesn't exist
                    $accountNumber = 'WB' . str_pad($_SESSION['user_id'], 8, '0', STR_PAD_LEFT);
                    $stmt = $pdo->prepare("INSERT INTO accounts (user_id, account_number, balance, status) VALUES (?, ?, 0.00, 'active')");
                    $stmt->execute([$_SESSION['user_id'], $accountNumber]);
                    $senderAccount = ['balance' => 0.00, 'account_number' => $accountNumber];
                }
            
                if ($senderAccount['balance'] < $amount) {
                    $_SESSION['message'] = 'Insufficient balance. Your current balance is ₱' . number_format($senderAccount['balance'], 2);
                    $_SESSION['messageType'] = 'error';
                } else {
                    // Find recipient by account number or username/email
                    $stmt = $pdo->prepare("
                        SELECT u.id, u.first_name, u.last_name, u.email, a.account_number, a.balance 
                        FROM users u 
                        LEFT JOIN accounts a ON u.id = a.user_id AND a.status = 'active'
                        WHERE a.account_number = ? OR u.email = ? OR CONCAT(u.first_name, ' ', u.last_name) = ?
                        ORDER BY a.created_at DESC LIMIT 1
                    ");
                    $stmt->execute([$recipient_identifier, $recipient_identifier, $recipient_identifier]);
                    $recipient = $stmt->fetch();
                    
                    if (!$recipient) {
                        $_SESSION['message'] = 'Recipient not found. Please check the account number, email, or name.';
                        $_SESSION['messageType'] = 'error';
                    } elseif ($recipient['id'] == $_SESSION['user_id']) {
                        $_SESSION['message'] = 'You cannot transfer money to yourself.';
                        $_SESSION['messageType'] = 'error';
                    } else {
                        // Start transaction
                        $pdo->beginTransaction();
                        
                        try {
                            // Deduct amount from sender's balance
                            $stmt = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE user_id = ? AND status = 'active'");
                            $stmt->execute([$amount, $_SESSION['user_id']]);
                            
                            // Create or update recipient's account
                            if (!$recipient['account_number']) {
                                // Create account for recipient
                                $recipientAccountNumber = 'WB' . str_pad($recipient['id'], 8, '0', STR_PAD_LEFT);
                                $stmt = $pdo->prepare("INSERT INTO accounts (user_id, account_number, balance, status) VALUES (?, ?, 0.00, 'active')");
                                $stmt->execute([$recipient['id'], $recipientAccountNumber]);
                                $recipient['account_number'] = $recipientAccountNumber;
                                $recipient['balance'] = 0.00;
                            }
                            
                            // Add amount to recipient's balance
                            $stmt = $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE user_id = ? AND status = 'active'");
                            $stmt->execute([$amount, $recipient['id']]);
                            
                            // Generate reference IDs
                            $senderReference = 'TRANSFER_OUT_' . str_pad(rand(100000, 999999), 8, '0', STR_PAD_LEFT);
                            $recipientReference = 'TRANSFER_IN_' . str_pad(rand(100000, 999999), 8, '0', STR_PAD_LEFT);
                            
                            // Log sender's transaction (outgoing) - negative amount
                            $stmt = $pdo->prepare("
                                INSERT INTO transactions (user_id, type, amount, description, status, reference_id) 
                                VALUES (?, 'transfer', ?, ?, 'completed', ?)
                            ");
                            $senderDescription = "Transfer to " . $recipient['first_name'] . " " . $recipient['last_name'] . " (Account #" . $recipient['account_number'] . ")";
                            if (!empty($description)) {
                                $senderDescription .= " - " . $description;
                            }
                            $stmt->execute([$_SESSION['user_id'], -$amount, $senderDescription, $senderReference]);
                            
                            // Log recipient's transaction (incoming) - positive amount
                            $stmt = $pdo->prepare("
                                INSERT INTO transactions (user_id, type, amount, description, status, reference_id) 
                                VALUES (?, 'transfer', ?, ?, 'completed', ?)
                            ");
                            $recipientDescription = "Received ₱" . number_format($amount, 2) . " from " . $_SESSION['user_name'] . " (Account #" . $senderAccount['account_number'] . ")";
                            if (!empty($description)) {
                                $recipientDescription .= " - " . $description;
                            }
                            $stmt->execute([$recipient['id'], $amount, $recipientDescription, $recipientReference]);
                            
                            $pdo->commit();
                            
                            $_SESSION['message'] = 'Transfer of ₱' . number_format($amount, 2) . ' to ' . $recipient['first_name'] . ' ' . $recipient['last_name'] . ' has been processed successfully!<br>Reference ID: <strong>' . $senderReference . '</strong>';
                            $_SESSION['messageType'] = 'success';
                            
                        } catch (Exception $e) {
                            $pdo->rollback();
                            $_SESSION['message'] = 'Transfer failed: ' . $e->getMessage() . '. Please try again.';
                            $_SESSION['messageType'] = 'error';
                        }
                    }
                }
            }
        } catch (PDOException $e) {
            $_SESSION['message'] = 'Transfer processing failed: ' . $e->getMessage() . '. Please try again.';
            $_SESSION['messageType'] = 'error';
        }
    }
    
    // Redirect to prevent form resubmission
    header('Location: transfer.php');
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfer Money - WeBank</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
                    <h1 class="text-2xl font-bold text-white ml-6">Transfer Money</h1>
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
    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Current Balance -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800">Current Balance</h2>
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

        <!-- Transfer Form -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Send Money</h2>
            
            <form method="POST" id="transferForm" class="space-y-6">
                <!-- Hidden password field -->
                <input type="hidden" name="password" id="passwordField">
                
                <!-- Recipient Information -->
                <div>
                    <label for="recipient_identifier" class="block text-sm font-medium text-gray-700 mb-2">Recipient</label>
                    <input type="text" name="recipient_identifier" id="recipient_identifier" required 
                           value="<?php echo isset($_POST['recipient_identifier']) ? htmlspecialchars($_POST['recipient_identifier']) : ''; ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Account number, email, or full name">
                    <p class="text-sm text-gray-500 mt-1">Enter recipient's account number (WB00000001), email, or full name</p>
                </div>

                <!-- Amount -->
                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">Amount (₱)</label>
                    <input type="number" name="amount" id="amount" step="0.01" min="1" max="50000" required 
                           value="<?php echo isset($_POST['amount']) ? htmlspecialchars($_POST['amount']) : ''; ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Enter amount">
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description (Optional)</label>
                    <textarea name="description" id="description" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Add a note for the recipient"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end">
                    <button type="button" id="confirmTransferBtn" class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition duration-300 font-medium">
                        Send Money
                    </button>
                </div>
            </form>
        </div>

        <!-- Transfer Guidelines -->
        <div class="bg-white rounded-lg shadow-md p-6 mt-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Transfer Guidelines</h3>
            <div class="space-y-3 text-sm text-gray-600">
                <div class="flex items-start space-x-2">
                    <svg class="w-5 h-5 text-blue-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span><strong>Recipient Identification:</strong> You can find recipients by account number (WB00000001), email address, or full name.</span>
                </div>
                <div class="flex items-start space-x-2">
                    <svg class="w-5 h-5 text-blue-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span><strong>Instant Transfer:</strong> Money is transferred immediately and both parties receive notifications.</span>
                </div>
                <div class="flex items-start space-x-2">
                    <svg class="w-5 h-5 text-blue-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span><strong>Transaction History:</strong> All transfers are recorded in your transaction history with reference numbers.</span>
                </div>
                <div class="flex items-start space-x-2">
                    <svg class="w-5 h-5 text-blue-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span><strong>Security:</strong> You cannot transfer money to yourself. All transfers are verified and logged.</span>
                </div>
            </div>
        </div>
    </main>

    <!-- Transfer Confirmation Modal -->
    <div id="transferModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <!-- Modal Header -->
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Confirm Transfer</h3>
                    <button id="closeModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <!-- Transfer Details -->
                <div class="bg-gray-50 rounded-lg p-4 mb-4">
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-600">Recipient:</span>
                            <span id="modalRecipient" class="text-sm text-gray-900"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-600">Amount:</span>
                            <span id="modalAmount" class="text-sm font-bold text-green-600"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-600">Description:</span>
                            <span id="modalDescription" class="text-sm text-gray-900"></span>
                        </div>
                    </div>
                </div>
                
                <!-- Password Input -->
                <div class="mb-4">
                    <label for="passwordInput" class="block text-sm font-medium text-gray-700 mb-2">
                        Enter your password to confirm this transfer
                    </label>
                    <input type="password" id="passwordInput" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500"
                           placeholder="Enter your password">
                </div>
                
                <!-- Modal Actions -->
                <div class="flex justify-end space-x-3">
                    <button id="cancelTransfer" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition duration-300">
                        Cancel
                    </button>
                    <button id="confirmTransfer" 
                            class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition duration-300">
                        Confirm Transfer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-6 no-print">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
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

        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('settingsDropdown');
            const dropdownMenu = document.getElementById('dropdownMenu');
            if (!dropdown.contains(event.target)) {
                dropdownMenu.classList.add('hidden');
                document.getElementById('dropdownArrow').style.transform = 'rotate(0deg)';
            }
        });

        // Transfer confirmation modal
        document.getElementById('confirmTransferBtn').addEventListener('click', function() {
            const recipient = document.getElementById('recipient_identifier').value;
            const amount = document.getElementById('amount').value;
            const description = document.getElementById('description').value;
            
            if (!recipient || !amount) {
                alert('Please fill in all required fields.');
                return;
            }
            
            // Show confirmation modal
            document.getElementById('transferModal').classList.remove('hidden');
            document.getElementById('modalRecipient').textContent = recipient;
            document.getElementById('modalAmount').textContent = '₱' + parseFloat(amount).toFixed(2);
            document.getElementById('modalDescription').textContent = description || 'No description';
        });

        // Close modal
        document.getElementById('closeModal').addEventListener('click', function() {
            document.getElementById('transferModal').classList.add('hidden');
            document.getElementById('passwordInput').value = '';
        });

        // Cancel transfer
        document.getElementById('cancelTransfer').addEventListener('click', function() {
            document.getElementById('transferModal').classList.add('hidden');
            document.getElementById('passwordInput').value = '';
        });

        // Confirm transfer
        document.getElementById('confirmTransfer').addEventListener('click', function() {
            const password = document.getElementById('passwordInput').value;
            
            if (!password) {
                alert('Please enter your password.');
                return;
            }
            
            // Set password in hidden field and submit form
            document.getElementById('passwordField').value = password;
            document.getElementById('transferForm').submit();
        });

        // Close modal when clicking outside
        document.getElementById('transferModal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
                document.getElementById('passwordInput').value = '';
            }
        });
    </script>
</body>
</html>
