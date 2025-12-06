<?php
$pageTitle = 'Home';
include 'includes/header.php';
include 'includes/navbar_landing.php';
?>

    <!-- Main Content -->
    <main class="flex-grow pt-20">
        <!-- Hero Section -->
        <section id="home" class="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-20">
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

        <!-- Features Section (Services) -->
        <section id="services" class="py-16 bg-white">
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

        <!-- About Section -->
        <section id="about" class="py-16 bg-gray-50">
            <div class="container mx-auto px-4">
                <div class="max-w-3xl mx-auto text-center">
                    <h3 class="text-3xl font-bold text-gray-800 mb-6">About WeBank</h3>
                    <p class="text-gray-600 text-lg mb-8">
                        Founded with a mission to make banking accessible, secure, and simple for everyone. 
                        WeBank combines cutting-edge technology with a customer-first approach to deliver 
                        financial services that empower you to achieve your goals.
                    </p>
                    <div class="grid md:grid-cols-3 gap-8 text-center">
                        <div>
                            <h4 class="text-4xl font-bold text-blue-600 mb-2">1M+</h4>
                            <p class="text-gray-600">Happy Users</p>
                        </div>
                        <div>
                            <h4 class="text-4xl font-bold text-blue-600 mb-2">$5B+</h4>
                            <p class="text-gray-600">Transactions Processed</p>
                        </div>
                        <div>
                            <h4 class="text-4xl font-bold text-blue-600 mb-2">99.9%</h4>
                            <p class="text-gray-600">Uptime</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contact Section -->
        <section id="contact" class="py-16 bg-white">
            <div class="container mx-auto px-4">
                <h3 class="text-3xl font-bold text-center text-gray-800 mb-12">Contact Us</h3>
                <div class="grid md:grid-cols-2 gap-12 max-w-4xl mx-auto">
                    <div class="bg-gray-50 p-8 rounded-lg">
                        <h4 class="text-xl font-bold text-gray-800 mb-6">Get in Touch</h4>
                        <div class="space-y-4">
                            <div class="flex items-start">
                                <svg class="w-6 h-6 text-blue-600 mt-1 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <div>
                                    <h5 class="font-semibold text-gray-800">Visit Us</h5>
                                    <p class="text-gray-600">123 Banking District, Financial City, FC 12345</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <svg class="w-6 h-6 text-blue-600 mt-1 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                <div>
                                    <h5 class="font-semibold text-gray-800">Email Us</h5>
                                    <p class="text-gray-600">support@webank.com</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <svg class="w-6 h-6 text-blue-600 mt-1 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                                <div>
                                    <h5 class="font-semibold text-gray-800">Call Us</h5>
                                    <p class="text-gray-600">+1 (555) 123-4567</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-8 rounded-lg shadow-md">
                        <form onsubmit="event.preventDefault(); alert('Thank you for your message! We will get back to you soon.');">
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="name">Name</label>
                                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="name" type="text" placeholder="Your Name" required>
                            </div>
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="email">Email</label>
                                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="email" type="email" placeholder="Your Email" required>
                            </div>
                            <div class="mb-6">
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="message">Message</label>
                                <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="message" rows="4" placeholder="How can we help?" required></textarea>
                            </div>
                            <div class="flex items-center justify-between">
                                <button class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-300" type="submit">
                                    Send Message
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>

<?php include 'includes/footer.php'; ?>