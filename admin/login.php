<?php
include_once '../includes/config.php';
include_once '../includes/functions.php';

$error = '';

// Check if admin is already logged in
if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Process login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($conn, $_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        // Get admin by email
        $stmt = $conn->prepare("SELECT * FROM admins WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $admin['password'])) {
                // Login successful
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                $_SESSION['admin_role'] = $admin['role'];
                
                // Update last login
                $stmt = $conn->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
                $stmt->bind_param("i", $admin['id']);
                $stmt->execute();
                
                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }
        
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Healthcare Clinic Queue Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Source+Sans+Pro:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body class="admin-body">
    <header class="site-header admin-header">
        <div class="container">
            <div class="logo">
                <a href="../index.php">
                    <span class="logo-icon">+</span>
                    <span class="logo-text">HealthQueue</span>
                </a>
            </div>
            <nav class="main-nav">
                <ul>
                    <li><a href="../index.php">Home</a></li>
                    <li><a href="../queue-status.php">Queue Status</a></li>
                </ul>
            </nav>
            <button class="mobile-menu-toggle">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>
        </div>
    </header>

    <main class="container">
        <div class="login-container">
            <div class="login-form">
                <div class="login-logo">
                    <span class="logo-icon">+</span>
                </div>
                <h2 class="login-title">Admin Login</h2>
                
                <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <form method="post" action="login.php">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
                </form>
            </div>
        </div>
    </main>

    <script>
        // Toggle mobile menu
        const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
        const mainNav = document.querySelector('.main-nav');
        
        if (mobileMenuToggle) {
            mobileMenuToggle.addEventListener('click', () => {
                mobileMenuToggle.classList.toggle('active');
                mainNav.classList.toggle('active');
            });
        }
    </script>
</body>
</html>