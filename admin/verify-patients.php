<?php
include_once '../includes/config.php';
include_once '../includes/functions.php';

// Check if admin is logged in
require_admin_login();

// Get all polyclinics for filter
$polyclinics = [];
$sql = "SELECT * FROM polyclinics ORDER BY name";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $polyclinics[] = $row;
    }
}

// Set default filter
$status_filter = isset($_GET['filter']) ? $_GET['filter'] : 'pending';
$polyclinic_filter = isset($_GET['polyclinic']) ? (int)$_GET['polyclinic'] : 0;

// Process verification
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $queue_id = (int)$_POST['queue_id'];
    $action = $_POST['action'];
    
    if ($action === 'verify') {
        // Get queue details
        $stmt = $conn->prepare("SELECT q.*, p.polyclinic_id FROM queues q JOIN patients p ON q.patient_id = p.id WHERE q.id = ?");
        $stmt->bind_param("i", $queue_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $queue = $result->fetch_assoc();
            $polyclinic_id = $queue['polyclinic_id'];
            
            // Generate queue number
            $queue_number = generate_queue_number($conn, $polyclinic_id);
            
            // Update queue status to verified
            $stmt = $conn->prepare("UPDATE queues SET status = 'verified', queue_number = ?, verified_at = NOW() WHERE id = ?");
            $stmt->bind_param("si", $queue_number, $queue_id);
            if ($stmt->execute()) {
                $message = "Patient verified successfully and assigned queue number: " . $queue_number;
            } else {
                $message = "Error verifying patient: " . $stmt->error;
            }
        }
    } elseif ($action === 'serve') {
        // Update queue status to serving
        $stmt = $conn->prepare("UPDATE queues SET status = 'serving' WHERE id = ?");
        $stmt->bind_param("i", $queue_id);
        if ($stmt->execute()) {
            $message = "Patient is now being served.";
        } else {
            $message = "Error updating status: " . $stmt->error;
        }
    } elseif ($action === 'complete') {
        // Update queue status to completed
        $stmt = $conn->prepare("UPDATE queues SET status = 'completed', completed_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $queue_id);
        if ($stmt->execute()) {
            $message = "Patient service completed.";
        } else {
            $message = "Error completing service: " . $stmt->error;
        }
    } elseif ($action === 'cancel') {
        // Update queue status to cancelled
        $stmt = $conn->prepare("UPDATE queues SET status = 'cancelled' WHERE id = ?");
        $stmt->bind_param("i", $queue_id);
        if ($stmt->execute()) {
            $message = "Patient registration cancelled.";
        } else {
            $message = "Error cancelling registration: " . $stmt->error;
        }
    }
}

// Get patients based on filter
$patients = [];
$sql = "SELECT p.*, q.id as queue_id, q.status, q.queue_number, q.created_at, q.verified_at, q.completed_at, pc.name as polyclinic_name 
        FROM patients p 
        JOIN queues q ON p.id = q.patient_id 
        JOIN polyclinics pc ON p.polyclinic_id = pc.id 
        WHERE 1=1";

if ($status_filter) {
    $sql .= " AND q.status = '$status_filter'";
}

if ($polyclinic_filter) {
    $sql .= " AND p.polyclinic_id = $polyclinic_filter";
}

$sql .= " ORDER BY";

if ($status_filter === 'pending') {
    $sql .= " q.created_at ASC";
} elseif ($status_filter === 'verified') {
    $sql .= " q.verified_at ASC";
} elseif ($status_filter === 'completed') {
    $sql .= " q.completed_at DESC";
} else {
    $sql .= " q.created_at DESC";
}

$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $patients[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Patients - Healthcare Clinic Queue Management</title>
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

    <main class="container verification-section">
        <h1>Patient Verification</h1>
        
        <?php if ($message): ?>
        <div class="alert <?php echo strpos($message, 'Error') !== false ? 'alert-error' : 'alert-success'; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <div class="verification-filter">
            <div>
                <label for="status-filter">Status:</label>
                <select id="status-filter" onchange="updateFilter()">
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="verified" <?php echo $status_filter === 'verified' ? 'selected' : ''; ?>>Verified</option>
                    <option value="serving" <?php echo $status_filter === 'serving' ? 'selected' : ''; ?>>Serving</option>
                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            
            <div>
                <label for="polyclinic-filter">Polyclinic:</label>
                <select id="polyclinic-filter" onchange="updateFilter()">
                    <option value="0">All Polyclinics</option>
                    <?php foreach ($polyclinics as $poly): ?>
                    <option value="<?php echo $poly['id']; ?>" <?php echo $polyclinic_filter === $poly['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($poly['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <?php if (empty($patients)): ?>
        <div class="alert alert-info">
            No patients found with the selected filters.
        </div>
        <?php else: ?>
        
        <?php foreach ($patients as $patient): ?>
        <div class="patient-card">
            <div class="patient-header">
                <h3><?php echo htmlspecialchars($patient['name']); ?></h3>
                <div>
                    <span class="status-badge status-<?php echo $patient['status']; ?>">
                        <?php echo ucfirst($patient['status']); ?>
                    </span>
                    <?php if ($patient['queue_number']): ?>
                    <span class="queue-number"><?php echo $patient['queue_number']; ?></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="patient-info">
                <p><strong>NIK:</strong> <?php echo htmlspecialchars($patient['nik']); ?></p>
                <p><strong>Registration Code:</strong> <?php echo htmlspecialchars($patient['registration_code']); ?></p>
                <p><strong>Polyclinic:</strong> <?php echo htmlspecialchars($patient['polyclinic_name']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($patient['phone']); ?></p>
                <p><strong>Address:</strong> <?php echo htmlspecialchars($patient['address']); ?></p>
                <p><strong>Registration Time:</strong> <?php echo date('Y-m-d H:i:s', strtotime($patient['created_at'])); ?></p>
                <?php if ($patient['verified_at']): ?>
                <p><strong>Verified At:</strong> <?php echo date('Y-m-d H:i:s', strtotime($patient['verified_at'])); ?></p>
                <?php endif; ?>
                <?php if ($patient['completed_at']): ?>
                <p><strong>Completed At:</strong> <?php echo date('Y-m-d H:i:s', strtotime($patient['completed_at'])); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="verification-actions">
                <form method="post" action="verify-patients.php?filter=<?php echo $status_filter; ?>&polyclinic=<?php echo $polyclinic_filter; ?>">
                    <input type="hidden" name="queue_id" value="<?php echo $patient['queue_id']; ?>">
                    
                    <?php if ($patient['status'] === 'pending'): ?>
                    <button type="submit" name="action" value="verify" class="admin-btn admin-btn-primary">Verify & Assign Queue</button>
                    <button type="submit" name="action" value="cancel" class="admin-btn admin-btn-danger" onclick="return confirm('Are you sure you want to cancel this registration?')">Cancel</button>
                    
                    <?php elseif ($patient['status'] === 'verified'): ?>
                    <button type="submit" name="action" value="serve" class="admin-btn admin-btn-warning">Call & Serve</button>
                    <button type="submit" name="action" value="cancel" class="admin-btn admin-btn-danger" onclick="return confirm('Are you sure you want to cancel this queue?')">Cancel</button>
                    
                    <?php elseif ($patient['status'] === 'serving'): ?>
                    <button type="submit" name="action" value="complete" class="admin-btn admin-btn-success">Complete Service</button>
                    
                    <?php endif; ?>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php endif; ?>
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
        
        // Update filter
        function updateFilter() {
            const status = document.getElementById('status-filter').value;
            const polyclinic = document.getElementById('polyclinic-filter').value;
            
            window.location.href = `verify-patients.php?filter=${status}&polyclinic=${polyclinic}`;
        }
    </script>
</body>
</html>