    <footer class="bg-gray-800 text-white py-8 mt-auto <?php echo strpos($_SERVER['PHP_SELF'], '/admin/') === false ? '' : ''; // Optional constraint ?>">
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
                <p>&copy; <?php echo date('Y'); ?> WeBank. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <?php if (strpos($_SERVER['PHP_SELF'], '/admin/') !== false): ?>
        </div> <!-- End Admin Main Wrapper -->
    <?php endif; ?>
    
    <!-- Shared Scripts -->
    <script src="/WeBank/assets/js/main.js"></script>
</body>
</html>
