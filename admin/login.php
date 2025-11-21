<?php
session_start();
require_once '../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        try {
            // Check admin credentials in database
            $stmt = $pdo->prepare("
                SELECT id, username, password, first_name, last_name, email, role, status 
                FROM admins 
                WHERE username = ? AND status = 'active'
            ");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($password, $admin['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_name'] = $admin['first_name'] . ' ' . $admin['last_name'];
                $_SESSION['admin_role'] = $admin['role'];
                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'Invalid admin credentials.';
            }
        } catch(PDOException $e) {
            $error = 'Login failed. Please try again.';
        }
    }
}

$pageTitle = 'Admin Login';
$bodyClass = 'bg-gray-50 min-h-screen flex items-center justify-center';
include '../includes/header.php';
?>

    <div class="max-w-md w-full space-y-8 p-8">
        <div class="text-center">
            <h1 class="text-3xl font-bold text-red-600 mb-2">WeBank Admin</h1>
            <h2 class="text-2xl font-semibold text-gray-900">Admin Login</h2>
            <p class="mt-2 text-sm text-gray-600">
                Administrative access only
            </p>
        </div>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form class="mt-8 space-y-6" method="POST">
            <div class="space-y-4">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">Admin Username</label>
                    <input id="username" name="username" type="text" required 
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500"
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input id="password" name="password" type="password" required 
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500">
                </div>
            </div>
            
            <div>
                <button type="submit" 
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    Admin Login
                </button>
            </div>
        </form>
        
        <div class="text-center">
            <a href="../index.php" class="text-sm text-gray-600 hover:text-gray-900">
                ← Back to Main Site
            </a>
        </div>
    </div>
</body>
</html>

