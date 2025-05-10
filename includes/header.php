<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Healthcare Clinic Queue Management</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <?php if (strpos($_SERVER['PHP_SELF'], 'admin/') !== false): ?>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Source+Sans+Pro:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <header class="site-header">
        <div class="container">
            <div class="logo">
                <a href="<?php echo strpos($_SERVER['PHP_SELF'], 'admin/') !== false ? '../index.php' : 'index.php'; ?>">
                    <span class="logo-icon">+</span>
                    <span class="logo-text">HealthQueue</span>
                </a>
            </div>
            <nav class="main-nav">
                <ul>
                    <li><a href="<?php echo strpos($_SERVER['PHP_SELF'], 'admin/') !== false ? '../index.php' : 'index.php'; ?>">Home</a></li>
                    <li><a href="<?php echo strpos($_SERVER['PHP_SELF'], 'admin/') !== false ? '../queue-status.php' : 'queue-status.php'; ?>">Queue Status</a></li>
                    <?php if (isset($_SESSION['admin_id'])): ?>
                    <li><a href="<?php echo strpos($_SERVER['PHP_SELF'], 'admin/') !== false ? 'dashboard.php' : 'admin/dashboard.php'; ?>">Admin Dashboard</a></li>
                    <li><a href="<?php echo strpos($_SERVER['PHP_SELF'], 'admin/') !== false ? 'logout.php' : 'admin/logout.php'; ?>">Logout</a></li>
                    <?php else: ?>
                    <li><a href="<?php echo strpos($_SERVER['PHP_SELF'], 'admin/') !== false ? 'login.php' : 'admin/login.php'; ?>">Admin Login</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <button class="mobile-menu-toggle">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>
        </div>
    </header>