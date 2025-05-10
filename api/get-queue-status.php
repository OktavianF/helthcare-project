<?php
header('Content-Type: application/json');
include_once '../includes/config.php';
include_once '../includes/functions.php';

// Get registration code from query parameter
$code = isset($_GET['code']) ? sanitize_input($conn, $_GET['code']) : '';

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'No registration code provided']);
    exit;
}

// Get patient data
$stmt = $conn->prepare("SELECT p.*, q.status, q.queue_number, q.verified_at, pc.name as polyclinic_name 
                         FROM patients p 
                         JOIN queues q ON p.id = q.patient_id 
                         JOIN polyclinics pc ON p.polyclinic_id = pc.id
                         WHERE p.registration_code = ?");
$stmt->bind_param("s", $code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Registration code not found']);
    exit;
}

$patient = $result->fetch_assoc();
$stmt->close();

// Get current serving number for this polyclinic
$current_serving = get_current_serving_patient($conn, $patient['polyclinic_id']);
$current_number = $current_serving ? $current_serving['queue_number'] : null;

// Calculate position in queue if verified
$position = 0;
if ($patient['status'] === 'verified') {
    $stmt = $conn->prepare("SELECT COUNT(*) as position 
                             FROM queues 
                             WHERE polyclinic_id = ? AND status = 'verified' 
                             AND verified_at <= ?");
    $stmt->bind_param("is", $patient['polyclinic_id'], $patient['verified_at']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $position = $row['position'];
    $stmt->close();
}

// Format registration time
$registration_time = date('Y-m-d H:i:s', strtotime($patient['created_at']));

echo json_encode([
    'success' => true,
    'name' => $patient['name'],
    'polyclinic_name' => $patient['polyclinic_name'],
    'registration_time' => $registration_time,
    'status' => $patient['status'],
    'queue_number' => $patient['queue_number'],
    'current_number' => $current_number,
    'position' => $position
]);

$conn->close();