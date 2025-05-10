<?php
header('Content-Type: application/json');
include_once '../includes/config.php';
include_once '../includes/functions.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get the JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data provided']);
    exit;
}

// Validate required fields
$required_fields = ['polyclinic_id', 'nik', 'name', 'address', 'phone'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

// Sanitize input
$polyclinic_id = (int)$data['polyclinic_id'];
$nik = sanitize_input($conn, $data['nik']);
$name = sanitize_input($conn, $data['name']);
$address = sanitize_input($conn, $data['address']);
$phone = sanitize_input($conn, $data['phone']);

// Check NIK format (16 digits)
if (!preg_match('/^\d{16}$/', $nik)) {
    echo json_encode(['success' => false, 'message' => 'NIK must be 16 digits']);
    exit;
}

// Check if polyclinic exists and has available quota
$stmt = $conn->prepare("SELECT * FROM polyclinics WHERE id = ? AND is_active = 1");
$stmt->bind_param("i", $polyclinic_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Selected polyclinic is not available']);
    exit;
}

$polyclinic = $result->fetch_assoc();
$stmt->close();

if ($polyclinic['available_quota'] <= 0) {
    echo json_encode(['success' => false, 'message' => 'This polyclinic has reached its daily quota']);
    exit;
}

// Check if patient with the same NIK already registered for this polyclinic today
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT p.* FROM patients p 
                         JOIN queues q ON p.id = q.patient_id 
                         WHERE p.nik = ? AND p.polyclinic_id = ? AND DATE(q.created_at) = ? 
                         AND q.status NOT IN ('completed', 'cancelled')");
$stmt->bind_param("sis", $nik, $polyclinic_id, $today);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $patient = $result->fetch_assoc();
    echo json_encode([
        'success' => false, 
        'message' => 'You are already registered for this polyclinic today. Your registration code is ' . $patient['registration_code']
    ]);
    exit;
}
$stmt->close();

// Start transaction
$conn->begin_transaction();

try {
    // Generate unique registration code
    $registration_code = generate_unique_registration_code($conn);
    
    // Insert patient
    $stmt = $conn->prepare("INSERT INTO patients (nik, name, address, phone, registration_code, polyclinic_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssi", $nik, $name, $address, $phone, $registration_code, $polyclinic_id);
    $stmt->execute();
    $patient_id = $stmt->insert_id;
    $stmt->close();
    
    // Insert queue entry
    $stmt = $conn->prepare("INSERT INTO queues (patient_id, polyclinic_id, queue_number, status) VALUES (?, ?, '', 'pending')");
    $stmt->bind_param("ii", $patient_id, $polyclinic_id);
    $stmt->execute();
    $stmt->close();
    
    // Update available quota
    $stmt = $conn->prepare("UPDATE polyclinics SET available_quota = available_quota - 1 WHERE id = ?");
    $stmt->bind_param("i", $polyclinic_id);
    $stmt->execute();
    $stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful',
        'registration_code' => $registration_code,
        'polyclinic_name' => $polyclinic['name'],
        'available_quota' => $polyclinic['available_quota'] - 1
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
}

$conn->close();