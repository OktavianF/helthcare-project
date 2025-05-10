<?php
include_once '../includes/config.php';
include_once '../includes/functions.php';

// Check if admin is logged in
require_admin_login();

// Get counts
$pending_count = 0;
$verified_count = 0;
$completed_count = 0;
$total_count = 0;

$sql = "SELECT 
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = 'verified' OR status = 'serving' THEN 1 ELSE 0 END) as verified_count,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
            COUNT(*) as total_count
        FROM queues 
        WHERE DATE(created_at) = CURDATE()";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $pending_count = $row['pending_count'];
    $verified_count = $row['verified_count'];
    $completed_count = $row['completed_count'];
    $total_count = $row['total_count'];
}

// Get polyclinic counts
$polyclinic_counts = [];
$sql = "SELECT p.name, p.daily_quota, p.available_quota, 
        (SELECT COUNT(*) FROM queues q WHERE q.polyclinic_id = p.id AND DATE(q.created_at) = CURDATE()) as registration_count
        FROM polyclinics p";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $polyclinic_counts[] = $row;
    }
}

// Get recent registrations
$recent_registrations = [];
$sql = "SELECT p.name as patient_name, p.registration_code, pc.name as polyclinic_name, q.status, q.created_at 
        FROM patients p 
        JOIN queues q ON p.id = q.patient_id 
        JOIN polyclinics pc ON q.polyclinic_id = pc.id 
        ORDER BY q.created_at DESC 
        LIMIT 10";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $recent_registrations[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Healthcare Clinic Queue Management</title>
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
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="verify-patients.php">Verify Patients</a></li>
                    <li><a href="manage-polyclinics.php">Manage Polyclinics</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
            <button class="mobile-menu-toggle">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>
        </div>
    </header>

    <main class="container admin-dashboard">
        <div class="admin-welcome">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>!</h2>
            <p>Today's Date: <?php echo date('l, F j, Y'); ?></p>
        </div>
        
        <div class="dashboard-tabs">
            <button class="dashboard-tab active" data-tab="overview">Overview</button>
            <button class="dashboard-tab" data-tab="polyclinics">Polyclinics</button>
            <button class="dashboard-tab" data-tab="recent">Recent Registrations</button>
        </div>
        
        <div class="dashboard-content active" id="overview-tab">
            <div class="admin-cards">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <div class="admin-card-icon">P</div>
                        <h3>Pending</h3>
                    </div>
                    <div class="admin-card-value"><?php echo $pending_count; ?></div>
                    <p>Patients awaiting verification</p>
                    <a href="verify-patients.php" class="btn btn-primary" style="margin-top: auto;">Verify Now</a>
                </div>
                
                <div class="admin-card">
                    <div class="admin-card-header">
                        <div class="admin-card-icon">V</div>
                        <h3>Verified</h3>
                    </div>
                    <div class="admin-card-value"><?php echo $verified_count; ?></div>
                    <p>Patients in queue</p>
                    <a href="verify-patients.php?filter=verified" class="btn btn-primary" style="margin-top: auto;">View Queue</a>
                </div>
                
                <div class="admin-card">
                    <div class="admin-card-header">
                        <div class="admin-card-icon">C</div>
                        <h3>Completed</h3>
                    </div>
                    <div class="admin-card-value"><?php echo $completed_count; ?></div>
                    <p>Patients served today</p>
                </div>
                
                <div class="admin-card">
                    <div class="admin-card-header">
                        <div class="admin-card-icon">T</div>
                        <h3>Total</h3>
                    </div>
                    <div class="admin-card-value"><?php echo $total_count; ?></div>
                    <p>Total registrations today</p>
                </div>
            </div>
        </div>
        
        <div class="dashboard-content" id="polyclinics-tab">
            <div class="admin-table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Polyclinic</th>
                            <th>Daily Quota</th>
                            <th>Available Quota</th>
                            <th>Registrations Today</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($polyclinic_counts as $pc): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($pc['name']); ?></td>
                            <td><?php echo $pc['daily_quota']; ?></td>
                            <td><?php echo $pc['available_quota']; ?></td>
                            <td><?php echo $pc['registration_count']; ?></td>
                            <td>
                                <?php if ($pc['available_quota'] <= 0): ?>
                                <span class="status-badge status-cancelled">Full</span>
                                <?php elseif ($pc['available_quota'] <= 5): ?>
                                <span class="status-badge status-serving">Limited</span>
                                <?php else: ?>
                                <span class="status-badge status-completed">Available</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="text-align: center; margin-top: 16px;">
                <a href="manage-polyclinics.php" class="btn btn-primary">Manage Polyclinics</a>
            </div>
        </div>
        
        <div class="dashboard-content" id="recent-tab">
            <div class="admin-table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Patient Name</th>
                            <th>Registration Code</th>
                            <th>Polyclinic</th>
                            <th>Status</th>
                            <th>Registration Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_registrations as $reg): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($reg['patient_name']); ?></td>
                            <td><?php echo htmlspecialchars($reg['registration_code']); ?></td>
                            <td><?php echo htmlspecialchars($reg['polyclinic_name']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $reg['status']; ?>">
                                    <?php echo ucfirst($reg['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('H:i', strtotime($reg['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
        
        // Dashboard tabs
        const dashboardTabs = document.querySelectorAll('.dashboard-tab');
        const dashboardContents = document.querySelectorAll('.dashboard-content');
        
        dashboardTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                // Remove active class from all tabs and contents
                dashboardTabs.forEach(t => t.classList.remove('active'));
                dashboardContents.forEach(c => c.classList.remove('active'));
                
                // Add active class to clicked tab and corresponding content
                tab.classList.add('active');
                const tabId = tab.getAttribute('data-tab');
                document.getElementById(`${tabId}-tab`).classList.add('active');
            });
        });
    </script>
</body>
</html>