<?php
// Database connection configuration
$db_host = 'localhost';
$db_user = 'root';
$db_password = '';
$db_name = 'healthcare_queue';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if database exists, if not create it
$sql = "CREATE DATABASE IF NOT EXISTS " . $db_name;
if ($conn->query($sql) === FALSE) {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db($db_name);

// Create tables if they don't exist
$tables = [
    "CREATE TABLE IF NOT EXISTS `polyclinics` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `description` TEXT,
        `daily_quota` INT NOT NULL DEFAULT 30,
        `available_quota` INT NOT NULL DEFAULT 30,
        `start_time` TIME NOT NULL DEFAULT '08:00:00',
        `end_time` TIME NOT NULL DEFAULT '16:00:00',
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS `patients` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `nik` VARCHAR(16) NOT NULL,
        `name` VARCHAR(100) NOT NULL,
        `address` TEXT NOT NULL,
        `phone` VARCHAR(20) NOT NULL,
        `registration_code` VARCHAR(8) UNIQUE NOT NULL,
        `polyclinic_id` INT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`polyclinic_id`) REFERENCES `polyclinics`(`id`)
    )",
    
    "CREATE TABLE IF NOT EXISTS `queues` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `patient_id` INT NOT NULL,
        `polyclinic_id` INT NOT NULL,
        `queue_number` VARCHAR(10) NOT NULL,
        `status` ENUM('pending', 'verified', 'serving', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
        `verified_at` TIMESTAMP NULL,
        `completed_at` TIMESTAMP NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`),
        FOREIGN KEY (`polyclinic_id`) REFERENCES `polyclinics`(`id`)
    )",
    
    "CREATE TABLE IF NOT EXISTS `admins` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `email` VARCHAR(100) UNIQUE NOT NULL,
        `password` VARCHAR(255) NOT NULL,
        `name` VARCHAR(100) NOT NULL,
        `role` ENUM('super_admin', 'admin', 'staff') NOT NULL DEFAULT 'staff',
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `last_login` TIMESTAMP NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )"
];

foreach ($tables as $sql) {
    if ($conn->query($sql) === FALSE) {
        die("Error creating table: " . $conn->error);
    }
}

// Check if default admin exists, if not create one
$admin_check = $conn->query("SELECT * FROM admins WHERE email = 'admin@example.com'");
if ($admin_check->num_rows == 0) {
    $password_hash = password_hash("admin123", PASSWORD_DEFAULT);
    $sql = "INSERT INTO admins (email, password, name, role) VALUES ('admin@example.com', '$password_hash', 'Admin User', 'super_admin')";
    if ($conn->query($sql) === FALSE) {
        die("Error creating default admin: " . $conn->error);
    }
}

// Check if default polyclinics exist, if not create them
$polyclinic_check = $conn->query("SELECT * FROM polyclinics");
if ($polyclinic_check->num_rows == 0) {
    $polyclinics = [
        ["General Medicine", "Primary healthcare services for general illnesses and health concerns.", 30],
        ["Pediatrics", "Healthcare services for infants, children, and adolescents.", 25],
        ["Obstetrics & Gynecology", "Healthcare services for women, including pregnancy and reproductive health.", 20],
        ["Cardiology", "Specialized care for heart and cardiovascular conditions.", 15],
        ["Orthopedics", "Healthcare services for musculoskeletal system and injuries.", 15]
    ];
    
    foreach ($polyclinics as $p) {
        $name = $p[0];
        $description = $p[1];
        $quota = $p[2];
        $sql = "INSERT INTO polyclinics (name, description, daily_quota, available_quota) VALUES ('$name', '$description', $quota, $quota)";
        if ($conn->query($sql) === FALSE) {
            die("Error creating default polyclinic: " . $conn->error);
        }
    }
}

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Start or resume session
session_start();