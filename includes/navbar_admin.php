<!-- Sidebar -->
<?php include 'sidebar_admin.php'; ?>

<!-- Main Content Wrapper -->
<div class="relative flex flex-col flex-1 overflow-y-auto overflow-x-hidden h-screen bg-gray-50">
    <!-- Top Header -->
    <header class="bg-white shadow-sm z-10 py-4 px-6 no-print">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                 <!-- Mobile Menu Button -->
                 <button onclick="toggleSidebar()" class="md:hidden text-gray-500 hover:text-red-600 focus:outline-none mr-4 transition duration-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                    </svg>
                </button>
                <h1 class="text-2xl font-bold text-gray-800">
                    <?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Admin Dashboard'; ?>
                </h1>
            </div>
            <div class="flex items-center space-x-4">
                 <span class="text-gray-600 hidden sm:block">Welcome, <strong class="text-red-600"><?php echo htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_username']); ?></strong></span>
            </div>
        </div>
    </header>
