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
$userInfo = null;
$accountInfo = null;

// Get user and account information
try {
    // Get user information
    $stmt = $pdo->prepare("
        SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.created_at,
               a.account_number, a.balance, a.status as account_status
        FROM users u 
        LEFT JOIN accounts a ON u.id = a.user_id AND a.status = 'active'
        WHERE u.id = ?
        ORDER BY a.created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $userInfo = $stmt->fetch();
    
    if (!$userInfo) {
        $message = 'User information not found.';
        $messageType = 'error';
    } else {
        $accountInfo = $userInfo;
    }
} catch(PDOException $e) {
    $message = 'Unable to retrieve user information.';
    $messageType = 'error';
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $firstName = trim($_POST['first_name']);
        $lastName = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        
        if (empty($firstName) || empty($lastName) || empty($email)) {
            $_SESSION['message'] = 'Please fill in all required fields.';
            $_SESSION['messageType'] = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['message'] = 'Please enter a valid email address.';
            $_SESSION['messageType'] = 'error';
        } else {
            try {
                // Check if email is already taken by another user
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $_SESSION['user_id']]);
                
                if ($stmt->fetch()) {
                    $_SESSION['message'] = 'This email is already taken by another user.';
                    $_SESSION['messageType'] = 'error';
                } else {
                    // Update user information
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET first_name = ?, last_name = ?, email = ?, phone = ?, updated_at = CURRENT_TIMESTAMP 
                        WHERE id = ?
                    ");
                    $stmt->execute([$firstName, $lastName, $email, $phone, $_SESSION['user_id']]);
                    
                    // Update session name
                    $_SESSION['user_name'] = $firstName . ' ' . $lastName;
                    
                    $_SESSION['message'] = 'Profile updated successfully!';
                    $_SESSION['messageType'] = 'success';
                }
            } catch(PDOException $e) {
                $_SESSION['message'] = 'Failed to update profile. Please try again.';
                $_SESSION['messageType'] = 'error';
            }
        }
    } elseif ($action === 'change_password') {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $_SESSION['message'] = 'Please fill in all password fields.';
            $_SESSION['messageType'] = 'error';
        } elseif ($newPassword !== $confirmPassword) {
            $_SESSION['message'] = 'New passwords do not match.';
            $_SESSION['messageType'] = 'error';
        } elseif (strlen($newPassword) < 6) {
            $_SESSION['message'] = 'New password must be at least 6 characters long.';
            $_SESSION['messageType'] = 'error';
        } else {
            try {
                // Verify current password
                $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($currentPassword, $user['password'])) {
                    // Update password
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
                    
                    $_SESSION['message'] = 'Password changed successfully!';
                    $_SESSION['messageType'] = 'success';
                } else {
                    $_SESSION['message'] = 'Incorrect current password.';
                    $_SESSION['messageType'] = 'error';
                }
            } catch(PDOException $e) {
                $_SESSION['message'] = 'Failed to change password. Please try again.';
                $_SESSION['messageType'] = 'error';
            }
        }
    }
    
    // Redirect to prevent form resubmission
    header('Location: settings.php');
    exit();
}

// Get messages from session
$message = $_SESSION['message'] ?? '';
$messageType = $_SESSION['messageType'] ?? '';

// Clear messages from session
unset($_SESSION['message'], $_SESSION['messageType']);

// Get user and account information (refresh after update)
try {
    // Get user information
    $stmt = $pdo->prepare("
        SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.created_at,
               a.account_number, a.balance, a.status as account_status
        FROM users u 
        LEFT JOIN accounts a ON u.id = a.user_id AND a.status = 'active'
        WHERE u.id = ?
        ORDER BY a.created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $userInfo = $stmt->fetch();
    
    if (!$userInfo) {
        $message = 'User information not found.';
        $messageType = 'error';
    } else {
        $accountInfo = $userInfo;
    }
} catch(PDOException $e) {
    $message = 'Unable to retrieve user information.';
    $messageType = 'error';
}

$pageTitle = 'Settings';
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
                        <h2 class="text-3xl font-bold text-gray-800">Account Settings</h2>
                        <p class="text-gray-600">Manage your account information and preferences</p>
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

        <?php if ($userInfo): ?>
            <!-- Account Information -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Account Information</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Account Number -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-blue-800">Account Number</h3>
                                <p class="text-2xl font-bold text-blue-900"><?php echo htmlspecialchars($userInfo['account_number'] ?: 'Not assigned'); ?></p>
                            </div>
                            <div class="bg-blue-100 p-3 rounded-full">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Current Balance -->
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-green-800">Current Balance</h3>
                                <p class="text-2xl font-bold text-green-900">₱<?php echo number_format($userInfo['balance'] ?: 0, 2); ?></p>
                            </div>
                            <div class="bg-green-100 p-3 rounded-full">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Settings -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Profile Settings</h2>
                
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- First Name -->
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                            <input type="text" name="first_name" id="first_name" required 
                                   value="<?php echo htmlspecialchars($userInfo['first_name']); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Last Name -->
                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                            <input type="text" name="last_name" id="last_name" required 
                                   value="<?php echo htmlspecialchars($userInfo['last_name']); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                            <input type="email" name="email" id="email" required 
                                   value="<?php echo htmlspecialchars($userInfo['email']); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Phone -->
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                            <input type="tel" name="phone" id="phone" 
                                   value="<?php echo htmlspecialchars($userInfo['phone'] ?: ''); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Optional">
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition duration-300 font-medium">
                            Update Profile
                        </button>
                    </div>
                </form>
            </div>

            <!-- Change Password -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Change Password</h2>
                
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="change_password">
                    
                    <!-- Current Password -->
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                        <input type="password" name="current_password" id="current_password" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- New Password -->
                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                            <input type="password" name="new_password" id="new_password" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <p class="text-sm text-gray-500 mt-1">Must be at least 6 characters long</p>
                        </div>

                        <!-- Confirm Password -->
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                            <input type="password" name="confirm_password" id="confirm_password" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="bg-red-600 text-white px-6 py-3 rounded-lg hover:bg-red-700 transition duration-300 font-medium">
                            Change Password
                        </button>
                    </div>
                </form>
            </div>

            <!-- Account Statistics -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Account Statistics</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center">
                        <div class="bg-purple-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-3">
                            <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-800">Member Since</h3>
                        <p class="text-gray-600"><?php echo date('F j, Y', strtotime($userInfo['created_at'])); ?></p>
                    </div>
                    
                    <div class="text-center">
                        <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-3">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-800">Account Status</h3>
                        <p class="text-gray-600"><?php echo ucfirst(htmlspecialchars($userInfo['account_status'] ?: 'Active')); ?></p>
                    </div>
                    
                    <div class="text-center">
                        <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-3">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-800">Account Type</h3>
                        <p class="text-gray-600">Savings</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
        </div>
    </main>

<?php include '../includes/footer.php'; ?>
