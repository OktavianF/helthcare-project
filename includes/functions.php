<?php
// Generate random 8-digit registration code (alphanumeric)
function generate_registration_code() {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Excluded confusing characters like O, 0, I, 1
    $code = '';
    $length = strlen($chars) - 1;
    
    for ($i = 0; $i < 8; $i++) {
        $code .= $chars[rand(0, $length)];
    }
    
    return $code;
}

// Generate unique registration code
function generate_unique_registration_code($conn) {
    global $conn;
    
    $code = generate_registration_code();
    
    // Check if code already exists
    $stmt = $conn->prepare("SELECT registration_code FROM patients WHERE registration_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // If code exists, generate a new one
    if ($result->num_rows > 0) {
        $stmt->close();
        return generate_unique_registration_code($conn);
    }
    
    $stmt->close();
    return $code;
}

// Generate queue number based on polyclinic
function generate_queue_number($conn, $polyclinic_id) {
    global $conn;
    
    // Get polyclinic prefix
    $stmt = $conn->prepare("SELECT name FROM polyclinics WHERE id = ?");
    $stmt->bind_param("i", $polyclinic_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $polyclinic = $result->fetch_assoc();
    $stmt->close();
    
    // Generate prefix from first letters of each word in polyclinic name
    $words = explode(' ', $polyclinic['name']);
    $prefix = '';
    foreach ($words as $word) {
        $prefix .= strtoupper(substr($word, 0, 1));
    }
    
    // Get current date in Y-m-d format
    $today = date('Y-m-d');
    
    // Get the count of queue numbers for this polyclinic today
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM queues q 
                            JOIN patients p ON q.patient_id = p.id 
                            WHERE q.polyclinic_id = ? AND DATE(q.created_at) = ?");
    $stmt->bind_param("is", $polyclinic_id, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $count = $row['count'] + 1; // Start from 1
    $stmt->close();
    
    return $prefix . $count;
}

// Sanitize input data
function sanitize_input($conn, $data) {
    global $conn;
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = $conn->real_escape_string($data);
    
    return $data;
}

// Check if admin is logged in
function is_admin_logged_in() {
    return isset($_SESSION['admin_id']) && $_SESSION['admin_id'] > 0;
}

// Redirect if not logged in
function require_admin_login() {
    if (!is_admin_logged_in()) {
        header("Location: login.php");
        exit;
    }
}

// Get next pending patient for a polyclinic
function get_next_pending_patient($conn, $polyclinic_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT p.id, p.name, p.nik, p.registration_code, q.id as queue_id, q.created_at 
                            FROM patients p 
                            JOIN queues q ON p.id = q.patient_id 
                            WHERE q.polyclinic_id = ? AND q.status = 'pending' 
                            ORDER BY q.created_at ASC 
                            LIMIT 1");
    $stmt->bind_param("i", $polyclinic_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Get current serving patient for a polyclinic
function get_current_serving_patient($conn, $polyclinic_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT p.name, q.queue_number, q.verified_at 
                            FROM patients p 
                            JOIN queues q ON p.id = q.patient_id 
                            WHERE q.polyclinic_id = ? AND q.status = 'serving' 
                            ORDER BY q.verified_at ASC 
                            LIMIT 1");
    $stmt->bind_param("i", $polyclinic_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    // If no currently serving patient, get the last verified patient
    $stmt = $conn->prepare("SELECT p.name, q.queue_number, q.verified_at 
                            FROM patients p 
                            JOIN queues q ON p.id = q.patient_id 
                            WHERE q.polyclinic_id = ? AND q.status = 'verified' 
                            ORDER BY q.verified_at ASC 
                            LIMIT 1");
    $stmt->bind_param("i", $polyclinic_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Reset daily quotas at midnight
function reset_daily_quotas($conn) {
    global $conn;
    
    $sql = "UPDATE polyclinics SET available_quota = daily_quota";
    $conn->query($sql);
}

// Check if quota needs to be reset (new day)
function check_reset_quotas($conn) {
    global $conn;
    
    // Get last reset date from a potential settings table or create one
    $sql = "SHOW TABLES LIKE 'settings'";
    $result = $conn->query($sql);
    
    if ($result->num_rows == 0) {
        // Create settings table
        $sql = "CREATE TABLE IF NOT EXISTS `settings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `key` VARCHAR(50) UNIQUE NOT NULL,
            `value` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $conn->query($sql);
        
        // Insert initial last_quota_reset
        $today = date('Y-m-d');
        $sql = "INSERT INTO settings (`key`, `value`) VALUES ('last_quota_reset', '$today')";
        $conn->query($sql);
    } else {
        // Check if last_quota_reset exists
        $sql = "SELECT value FROM settings WHERE `key` = 'last_quota_reset'";
        $result = $conn->query($sql);
        
        if ($result->num_rows == 0) {
            // Insert initial last_quota_reset
            $today = date('Y-m-d');
            $sql = "INSERT INTO settings (`key`, `value`) VALUES ('last_quota_reset', '$today')";
            $conn->query($sql);
        } else {
            // Get last reset date
            $row = $result->fetch_assoc();
            $last_reset = $row['value'];
            $today = date('Y-m-d');
            
            // If new day, reset quotas
            if ($last_reset != $today) {
                reset_daily_quotas($conn);
                
                // Update last reset date
                $sql = "UPDATE settings SET value = '$today' WHERE `key` = 'last_quota_reset'";
                $conn->query($sql);
            }
        }
    }
}

// Call the quota reset check on every page load
check_reset_quotas($conn);