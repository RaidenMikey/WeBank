<?php
require_once '../includes/security.php';
secure_session_start();
require_once '../config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF Verification
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid session. Please refresh and try again.';
    } else {
        $firstName = trim($_POST['first_name']);
        $lastName = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $password = trim($_POST['password']);
        $confirmPassword = trim($_POST['confirm_password']);
        
        // Validation
        if (empty($firstName) || empty($lastName) || empty($email) || empty($password) || empty($confirmPassword)) {
            $_SESSION['error'] = 'Please fill in all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Please enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $_SESSION['error'] = 'Password must be at least 6 characters long.';
        } elseif ($password !== $confirmPassword) {
            $_SESSION['error'] = 'Passwords do not match.';
        } else {
            try {
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                
                if ($stmt->fetch()) {
                    $_SESSION['error'] = 'An account with this email already exists.';
                } else {
                    // Hash password and insert user
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, phone, password) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$firstName, $lastName, $email, $phone, $hashedPassword]);
                    
                    // Get the new user's ID
                    $newUserId = $pdo->lastInsertId();
                    
                    // Create account for the new user
                    $accountNumber = 'WB' . str_pad($newUserId, 8, '0', STR_PAD_LEFT);
                    $stmt = $pdo->prepare("INSERT INTO accounts (user_id, account_number, balance, status) VALUES (?, ?, 0.00, 'active')");
                    $stmt->execute([$newUserId, $accountNumber]);
                    
                    $_SESSION['success'] = 'Account created successfully! Your account number is <strong>' . $accountNumber . '</strong><br>You can now <a href="login.php" class="text-blue-600 hover:underline">sign in</a>.';
                }
            } catch(PDOException $e) {
                $_SESSION['error'] = 'Registration failed. Please try again.';
            }
        }
    }
    
    // Redirect to prevent form resubmission
    header('Location: signup.php');
    exit();
}

// Get messages from session
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';

// Clear messages from session
unset($_SESSION['error'], $_SESSION['success']);

$pageTitle = 'Sign Up';
$bodyClass = 'bg-gray-50 min-h-screen flex items-center justify-center py-12';
include '../includes/header.php';
?>

    <div class="max-w-md w-full space-y-8 p-8">
        <div class="text-center">
            <h1 class="text-3xl font-bold text-blue-600 mb-2">WeBank</h1>
            <h2 class="text-2xl font-semibold text-gray-900">Create your account</h2>
            <p class="mt-2 text-sm text-gray-600">
                Already have an account? <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500">Sign in</a>
            </p>
        </div>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <form class="mt-8 space-y-6" method="POST">
            <?php echo csrf_field(); ?>
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700">First Name *</label>
                        <input id="first_name" name="first_name" type="text" required 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                    </div>
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name *</label>
                        <input id="last_name" name="last_name" type="text" required 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                    </div>
                </div>
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email Address *</label>
                    <input id="email" name="email" type="email" required 
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                    <input id="phone" name="phone" type="tel" 
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password *</label>
                    <input id="password" name="password" type="password" required 
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1 text-sm text-gray-500">Must be at least 6 characters long</p>
                </div>
                
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm Password *</label>
                    <input id="confirm_password" name="confirm_password" type="password" required 
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
            
            <div class="flex items-center">
                <input id="terms" name="terms" type="checkbox" required 
                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                <label for="terms" class="ml-2 block text-sm text-gray-900">
                    I agree to the <a href="#" class="text-blue-600 hover:text-blue-500">Terms and Conditions</a>
                </label>
            </div>
            
            <div>
                <button type="submit" 
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Create Account
                </button>
            </div>
        </form>
        
        <div class="text-center">
            <a href="../index.php" class="text-sm text-gray-600 hover:text-gray-900">
                ← Back to Home
            </a>
        </div>
    </div>
</body>
</html>

