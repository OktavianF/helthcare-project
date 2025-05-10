<?php
header('Content-Type: application/json');
include_once '../includes/config.php';
include_once '../includes/functions.php';

// Get current date
$today = date('Y-m-d');

// Get active polyclinics with current queue info
$sql = "SELECT p.id, p.name as polyclinic_name,
        (SELECT q.queue_number FROM queues q 
         JOIN patients pat ON q.patient_id = pat.id 
         WHERE q.polyclinic_id = p.id AND q.status = 'serving' 
         ORDER BY q.verified_at ASC LIMIT 1) as current_number,
        (SELECT COUNT(*) FROM queues q 
         WHERE q.polyclinic_id = p.id AND (q.status = 'verified' OR q.status = 'serving') 
         AND DATE(q.created_at) = '$today') as waiting_count,
        (SELECT COUNT(*) FROM queues q 
         WHERE q.polyclinic_id = p.id AND q.status = 'completed' 
         AND DATE(q.created_at) = '$today') as completed_count
        FROM polyclinics p
        WHERE p.is_active = 1
        ORDER BY p.name";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $queues = [];
    
    while ($row = $result->fetch_assoc()) {
        // Only include polyclinics that have activity today
        if ($row['waiting_count'] > 0 || $row['completed_count'] > 0 || $row['current_number']) {
            $queues[] = [
                'polyclinic_id' => $row['id'],
                'polyclinic_name' => $row['polyclinic_name'],
                'current_number' => $row['current_number'],
                'waiting_count' => (int)$row['waiting_count'],
                'completed_count' => (int)$row['completed_count']
            ];
        }
    }
    
    echo json_encode(['success' => true, 'queues' => $queues]);
} else {
    echo json_encode(['success' => true, 'queues' => []]);
}

$conn->close();