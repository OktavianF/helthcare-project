<?php
header('Content-Type: application/json');
include_once '../includes/config.php';
include_once '../includes/functions.php';

// Get specific polyclinic if ID provided
$polyclinic_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$query = "SELECT * FROM polyclinics WHERE is_active = 1";

if ($polyclinic_id > 0) {
    $query .= " AND id = $polyclinic_id";
}

$query .= " ORDER BY name";

$result = $conn->query($query);

if ($result->num_rows > 0) {
    $polyclinics = [];
    
    while ($row = $result->fetch_assoc()) {
        $polyclinics[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'daily_quota' => $row['daily_quota'],
            'available_quota' => $row['available_quota'],
            'start_time' => $row['start_time'],
            'end_time' => $row['end_time']
        ];
    }
    
    echo json_encode(['success' => true, 'polyclinics' => $polyclinics]);
} else {
    echo json_encode(['success' => true, 'polyclinics' => []]);
}

$conn->close();