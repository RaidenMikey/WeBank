<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WeBank - Your Trusted Banking Partner</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <!-- Header -->
    <header class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-blue-600">WeBank</h1>
                </div>
                <nav class="hidden md:flex space-x-6">
                    <a href="#" class="text-gray-700 hover:text-blue-600 transition duration-300">Home</a>
                    <a href="#" class="text-gray-700 hover:text-blue-600 transition duration-300">Services</a>
                    <a href="#" class="text-gray-700 hover:text-blue-600 transition duration-300">About</a>
                    <a href="#" class="text-gray-700 hover:text-blue-600 transition duration-300">Contact</a>
                </nav>
                <div class="flex space-x-4">
                    <a href="user/login.php" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition duration-300 font-medium">
                        Login
                    </a>
                    <a href="admin/login.php" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition duration-300 font-medium">
                        Admin
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow">
        <!-- Hero Section -->
        <section class="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-20">
            <div class="container mx-auto px-4 text-center">
                <h2 class="text-4xl md:text-6xl font-bold mb-6">Welcome to WeBank</h2>
                <p class="text-xl md:text-2xl mb-8 max-w-3xl mx-auto">
                    Your trusted partner for all your banking needs. Experience secure, fast, and reliable financial services.
                </p>
                <div class="space-x-4">
                    <a href="user/signup.php" class="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition duration-300 inline-block">
                        Get Started
                    </a>
                    <a href="user/login.php" class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-blue-600 transition duration-300 inline-block">
                        Login
                    </a>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="py-16 bg-white">
            <div class="container mx-auto px-4">
                <h3 class="text-3xl font-bold text-center text-gray-800 mb-12">Why Choose WeBank?</h3>
                <div class="grid md:grid-cols-3 gap-8">
                    <div class="text-center p-6">
                        <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                        <h4 class="text-xl font-semibold mb-2">Secure Banking</h4>
                        <p class="text-gray-600">Your financial data is protected with industry-leading security measures.</p>
                    </div>
                    <div class="text-center p-6">
                        <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <h4 class="text-xl font-semibold mb-2">Fast Transactions</h4>
                        <p class="text-gray-600">Experience lightning-fast transactions and instant transfers.</p>
                    </div>
                    <div class="text-center p-6">
                        <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192L5.636 18.364M12 2.25a9.75 9.75 0 100 19.5 9.75 9.75 0 000-19.5z"></path>
                            </svg>
                        </div>
                        <h4 class="text-xl font-semibold mb-2">24/7 Support</h4>
                        <p class="text-gray-600">Round-the-clock customer support whenever you need assistance.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-4 gap-8">
                <div>
                    <h4 class="text-xl font-bold mb-4">WeBank</h4>
                    <p class="text-gray-400">Your trusted banking partner for secure and reliable financial services.</p>
                </div>
                <div>
                    <h5 class="font-semibold mb-4">Services</h5>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white transition duration-300">Personal Banking</a></li>
                        <li><a href="#" class="hover:text-white transition duration-300">Business Banking</a></li>
                        <li><a href="#" class="hover:text-white transition duration-300">Loans</a></li>
                        <li><a href="#" class="hover:text-white transition duration-300">Investment</a></li>
                    </ul>
                </div>
                <div>
                    <h5 class="font-semibold mb-4">Support</h5>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white transition duration-300">Help Center</a></li>
                        <li><a href="#" class="hover:text-white transition duration-300">Contact Us</a></li>
                        <li><a href="#" class="hover:text-white transition duration-300">FAQ</a></li>
                        <li><a href="#" class="hover:text-white transition duration-300">Security</a></li>
                    </ul>
                </div>
                <div>
                    <h5 class="font-semibold mb-4">Connect</h5>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white transition duration-300">Facebook</a></li>
                        <li><a href="#" class="hover:text-white transition duration-300">Twitter</a></li>
                        <li><a href="#" class="hover:text-white transition duration-300">LinkedIn</a></li>
                        <li><a href="#" class="hover:text-white transition duration-300">Instagram</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; 2024 WeBank. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>