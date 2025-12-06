<aside id="sidebar" class="bg-red-900 text-white w-64 space-y-6 py-7 px-2 absolute inset-y-0 left-0 transform -translate-x-full md:relative md:translate-x-0 transition duration-200 ease-in-out z-20 flex flex-col h-screen fixed overflow-y-auto print:hidden">
    <!-- Logo -->
    <div class="flex items-center space-x-2 px-4 mb-6">
        <div class="bg-white p-1 rounded-full">
            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
            </svg>
        </div>
        <span class="text-2xl font-extrabold">WeBank Admin</span>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-2 space-y-2">
        <?php
        $current_page = basename($_SERVER['PHP_SELF']);
        
        $nav_items = [
            'dashboard.php' => [
                'label' => 'Dashboard',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>'
            ],
            'users.php' => [
                'label' => 'Manage Users',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>'
            ],
            'deposit.php' => [
                'label' => 'Fund User',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>'
            ],
            'deposit_requests.php' => [
                'label' => 'Review Requests',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>'
            ]
        ];

        foreach ($nav_items as $page => $item):
            $active = ($current_page === $page);
            $bg_class = $active ? 'bg-red-800 text-white' : 'text-gray-300 hover:bg-red-800 hover:text-white';
        ?>
            <a href="<?php echo $page; ?>" class="group flex items-center px-4 py-3 rounded-md transition duration-200 <?php echo $bg_class; ?>">
                <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <?php echo $item['icon']; ?>
                </svg>
                <span class="font-medium"><?php echo $item['label']; ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Bottom Actions -->
    <div class="border-t border-red-800 pt-4 px-2">
        <a href="settings.php" class="group flex items-center px-4 py-3 text-gray-300 hover:bg-red-800 hover:text-white rounded-md transition duration-200">
             <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            <span class="font-medium">Settings</span>
        </a>
        <a href="logout.php" class="group flex items-center px-4 py-3 text-gray-300 hover:bg-red-800 hover:text-white rounded-md transition duration-200 mt-2">
            <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
            </svg>
            <span class="font-medium">Logout</span>
        </a>
    </div>
</aside>

<!-- Mobile Overlay -->
<div id="sidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black opacity-50 z-10 hidden md:hidden"></div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        
        if (sidebar.classList.contains('-translate-x-full')) {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
        } else {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        }
    }
</script>
