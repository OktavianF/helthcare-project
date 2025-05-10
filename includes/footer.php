<footer class="site-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-logo">
                    <span class="logo-icon">+</span>
                    <span class="logo-text">HealthQueue</span>
                </div>
                <div class="footer-info">
                    <p>&copy; <?php echo date('Y'); ?> Healthcare Clinic Queue Management System</p>
                    <p>Opening Hours: Monday-Friday, 8:00 AM - 4:00 PM</p>
                </div>
                <div class="footer-links">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="queue-status.php">Queue Status</a></li>
                        <li><a href="admin/login.php">Admin Login</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>
    
    <script src="assets/js/main.js"></script>
    <?php
    // Load specific scripts based on the current page
    $current_file = basename($_SERVER['PHP_SELF']);
    
    switch ($current_file) {
        case 'index.php':
            echo '<script src="assets/js/polyclinics.js"></script>';
            break;
        case 'register.php':
            echo '<script src="assets/js/registration.js"></script>';
            break;
        case 'queue-status.php':
            echo '<script src="assets/js/queue.js"></script>';
            break;
    }
    
    if (strpos($_SERVER['PHP_SELF'], 'admin/') !== false) {
        echo '<script src="../assets/js/admin.js"></script>';
    }
    ?>
</body>
</html>