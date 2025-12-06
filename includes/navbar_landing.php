    <style>
        html {
            scroll-behavior: smooth;
            scroll-padding-top: 5rem; /* Adjust based on header height */
        }
    </style>
    <!-- Header -->
    <header class="bg-white shadow-md fixed w-full z-50 top-0">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-blue-600">WeBank</h1>
                </div>
                <nav class="hidden md:flex space-x-6">
                    <a href="index.php#home" class="text-gray-700 hover:text-blue-600 transition duration-300">Home</a>
                    <a href="index.php#services" class="text-gray-700 hover:text-blue-600 transition duration-300">Services</a>
                    <a href="index.php#about" class="text-gray-700 hover:text-blue-600 transition duration-300">About</a>
                    <a href="index.php#contact" class="text-gray-700 hover:text-blue-600 transition duration-300">Contact</a>
                </nav>
                <div class="flex space-x-4">
                    <a href="user/login.php" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition duration-300 font-medium">
                        Login
                    </a>
                </div>
            </div>
        </div>
    </header>
