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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match.';
    } elseif (strlen($new_password) < 8) {
        $error = 'New password must be at least 8 characters long.';
    } else {
        try {
            // Get current admin password
            $stmt = $pdo->prepare("SELECT password FROM admins WHERE id = ?");
            $stmt->execute([$_SESSION['admin_id']]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($current_password, $admin['password'])) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
                $updateStmt->execute([$hashed_password, $_SESSION['admin_id']]);
                
                $success = 'Password updated successfully.';
            } else {
                $error = 'Incorrect current password.';
            }
        } catch(PDOException $e) {
            $error = 'An error occurred. Please try again.';
        }
    }
}

$pageTitle = 'Admin Settings';
$bodyClass = 'bg-gray-50 flex h-screen overflow-hidden';
include '../includes/header.php';
include '../includes/navbar_admin.php';
?>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <div class="max-w-xl mx-auto">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Account Settings</h2>

            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Change Password</h3>

                <?php if ($success): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="space-y-4">
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                            <input type="password" name="current_password" id="current_password" required 
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500">
                        </div>

                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                            <input type="password" name="new_password" id="new_password" required minlength="8"
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500">
                            <p class="mt-1 text-xs text-gray-500">Must be at least 8 characters long.</p>
                        </div>

                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                            <input type="password" name="confirm_password" id="confirm_password" required minlength="8"
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500">
                        </div>

                        <div class="pt-4">
                            <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                Update Password
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>

<?php include '../includes/footer.php'; ?>
