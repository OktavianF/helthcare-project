<?php
include_once '../includes/config.php';
include_once '../includes/functions.php';

// Check if admin is logged in
require_admin_login();

$message = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'add' || $action === 'edit') {
            $name = sanitize_input($conn, $_POST['name']);
            $description = sanitize_input($conn, $_POST['description']);
            $daily_quota = (int)$_POST['daily_quota'];
            $start_time = sanitize_input($conn, $_POST['start_time']);
            $end_time = sanitize_input($conn, $_POST['end_time']);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            if (empty($name) || empty($description) || $daily_quota <= 0) {
                $message = "Please fill all required fields. Daily quota must be greater than 0.";
            } else {
                if ($action === 'add') {
                    // Add new polyclinic
                    $stmt = $conn->prepare("INSERT INTO polyclinics (name, description, daily_quota, available_quota, start_time, end_time, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssiissi", $name, $description, $daily_quota, $daily_quota, $start_time, $end_time, $is_active);
                    
                    if ($stmt->execute()) {
                        $message = "Polyclinic added successfully.";
                    } else {
                        $message = "Error adding polyclinic: " . $stmt->error;
                    }
                } else {
                    // Edit existing polyclinic
                    $id = (int)$_POST['id'];
                    
                    // Get current available quota
                    $stmt = $conn->prepare("SELECT daily_quota, available_quota FROM polyclinics WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    
                    // Calculate new available quota
                    $old_daily_quota = $row['daily_quota'];
                    $old_available_quota = $row['available_quota'];
                    $used_quota = $old_daily_quota - $old_available_quota;
                    $new_available_quota = $daily_quota - $used_quota;
                    
                    if ($new_available_quota < 0) {
                        $new_available_quota = 0;
                    }
                    
                    // Update polyclinic
                    $stmt = $conn->prepare("UPDATE polyclinics SET name = ?, description = ?, daily_quota = ?, available_quota = ?, start_time = ?, end_time = ?, is_active = ? WHERE id = ?");
                    $stmt->bind_param("ssiissii", $name, $description, $daily_quota, $new_available_quota, $start_time, $end_time, $is_active, $id);
                    
                    if ($stmt->execute()) {
                        $message = "Polyclinic updated successfully.";
                    } else {
                        $message = "Error updating polyclinic: " . $stmt->error;
                    }
                }
            }
        } elseif ($action === 'reset_quota') {
            $id = (int)$_POST['id'];
            
            // Reset available quota to daily quota
            $stmt = $conn->prepare("UPDATE polyclinics SET available_quota = daily_quota WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $message = "Quota reset successfully.";
            } else {
                $message = "Error resetting quota: " . $stmt->error;
            }
        }
    }
}

// Get all polyclinics
$polyclinics = [];
$sql = "SELECT * FROM polyclinics ORDER BY name";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $polyclinics[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Polyclinics - Healthcare Clinic Queue Management</title>
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

    <main class="container polyclinic-management">
        <div class="management-header">
            <h1>Manage Polyclinics</h1>
            <button id="add-polyclinic-btn" class="btn btn-primary add-polyclinic-btn">Add New Polyclinic</button>
        </div>
        
        <?php if ($message): ?>
        <div class="alert <?php echo strpos($message, 'Error') !== false ? 'alert-error' : 'alert-success'; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <div id="add-form" class="polyclinic-form">
            <div class="admin-form">
                <h2 class="admin-form-title">Add New Polyclinic</h2>
                <form method="post" action="manage-polyclinics.php">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label for="name">Polyclinic Name*</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description*</label>
                        <textarea id="description" name="description" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="daily_quota">Daily Quota*</label>
                        <input type="number" id="daily_quota" name="daily_quota" min="1" value="20" required>
                    </div>
                    
                    <div class="time-inputs">
                        <div class="form-group">
                            <label for="start_time">Start Time</label>
                            <input type="time" id="start_time" name="start_time" value="08:00">
                        </div>
                        
                        <div class="form-group">
                            <label for="end_time">End Time</label>
                            <input type="time" id="end_time" name="end_time" value="16:00">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_active" checked>
                            Active
                        </label>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Add Polyclinic</button>
                        <button type="button" id="cancel-add" class="btn btn-secondary">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div id="edit-form" class="polyclinic-form">
            <div class="admin-form">
                <h2 class="admin-form-title">Edit Polyclinic</h2>
                <form method="post" action="manage-polyclinics.php">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" id="edit-id" name="id" value="">
                    
                    <div class="form-group">
                        <label for="edit-name">Polyclinic Name*</label>
                        <input type="text" id="edit-name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-description">Description*</label>
                        <textarea id="edit-description" name="description" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-daily_quota">Daily Quota*</label>
                        <input type="number" id="edit-daily_quota" name="daily_quota" min="1" required>
                    </div>
                    
                    <div class="time-inputs">
                        <div class="form-group">
                            <label for="edit-start_time">Start Time</label>
                            <input type="time" id="edit-start_time" name="start_time">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-end_time">End Time</label>
                            <input type="time" id="edit-end_time" name="end_time">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="edit-is_active" name="is_active">
                            Active
                        </label>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Update Polyclinic</button>
                        <button type="button" id="cancel-edit" class="btn btn-secondary">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="admin-table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Daily Quota</th>
                        <th>Available Quota</th>
                        <th>Hours</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($polyclinics as $poly): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($poly['name']); ?></td>
                        <td><?php echo htmlspecialchars(substr($poly['description'], 0, 50)) . (strlen($poly['description']) > 50 ? '...' : ''); ?></td>
                        <td><?php echo $poly['daily_quota']; ?></td>
                        <td><?php echo $poly['available_quota']; ?></td>
                        <td><?php echo substr($poly['start_time'], 0, 5) . ' - ' . substr($poly['end_time'], 0, 5); ?></td>
                        <td>
                            <?php if ($poly['is_active']): ?>
                            <span class="status-badge status-completed">Active</span>
                            <?php else: ?>
                            <span class="status-badge status-cancelled">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions">
                            <button class="admin-btn admin-btn-primary edit-btn" 
                                    data-id="<?php echo $poly['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($poly['name']); ?>"
                                    data-description="<?php echo htmlspecialchars($poly['description']); ?>"
                                    data-daily_quota="<?php echo $poly['daily_quota']; ?>"
                                    data-start_time="<?php echo $poly['start_time']; ?>"
                                    data-end_time="<?php echo $poly['end_time']; ?>"
                                    data-is_active="<?php echo $poly['is_active']; ?>">
                                Edit
                            </button>
                            
                            <form method="post" action="manage-polyclinics.php" style="display:inline;">
                                <input type="hidden" name="action" value="reset_quota">
                                <input type="hidden" name="id" value="<?php echo $poly['id']; ?>">
                                <button type="submit" class="admin-btn admin-btn-warning" onclick="return confirm('Are you sure you want to reset the quota for this polyclinic?')">
                                    Reset Quota
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
        
        // Add polyclinic form toggle
        const addBtn = document.getElementById('add-polyclinic-btn');
        const addForm = document.getElementById('add-form');
        const cancelAdd = document.getElementById('cancel-add');
        
        addBtn.addEventListener('click', () => {
            addForm.classList.add('active');
            editForm.classList.remove('active');
        });
        
        cancelAdd.addEventListener('click', () => {
            addForm.classList.remove('active');
        });
        
        // Edit polyclinic
        const editForm = document.getElementById('edit-form');
        const cancelEdit = document.getElementById('cancel-edit');
        const editBtns = document.querySelectorAll('.edit-btn');
        
        editBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                // Fill the edit form with data
                document.getElementById('edit-id').value = btn.getAttribute('data-id');
                document.getElementById('edit-name').value = btn.getAttribute('data-name');
                document.getElementById('edit-description').value = btn.getAttribute('data-description');
                document.getElementById('edit-daily_quota').value = btn.getAttribute('data-daily_quota');
                document.getElementById('edit-start_time').value = btn.getAttribute('data-start_time');
                document.getElementById('edit-end_time').value = btn.getAttribute('data-end_time');
                document.getElementById('edit-is_active').checked = btn.getAttribute('data-is_active') === '1';
                
                // Show edit form, hide add form
                editForm.classList.add('active');
                addForm.classList.remove('active');
            });
        });
        
        cancelEdit.addEventListener('click', () => {
            editForm.classList.remove('active');
        });
    </script>
</body>
</html>