<?php
include_once 'db_conn.php';

// Set header para alam ng browser na JSON ang output natin
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

$response = ['status' => 'error', 'message' => 'Invalid action'];

if ($action == 'add') {
    $stmt = $conn->prepare("INSERT INTO visitor_flags (full_name, id_number, reason, status) VALUES (?, ?, ?, 'active')");
    $stmt->bind_param("sss", $data['name'], $data['id_number'], $data['reason']);
    if ($stmt->execute()) {
        $response = ['status' => 'success', 'id' => $stmt->insert_id];
    }
} 

elseif ($action == 'unflag') {
    // Ina-update ang status sa 'cleared'
    $stmt = $conn->prepare("UPDATE visitor_flags SET status = 'cleared' WHERE id = ?");
    $stmt->bind_param("i", $data['id']);
    if ($stmt->execute()) {
        $response = ['status' => 'success'];
    }
}

elseif ($action == 'edit') {
    // Bago itong action na ito para sa editing ng reason
    $stmt = $conn->prepare("UPDATE visitor_flags SET reason = ? WHERE id = ?");
    $stmt->bind_param("si", $data['reason'], $data['id']);
    if ($stmt->execute()) {
        $response = ['status' => 'success'];
    }
}

elseif ($action == 'delete') {
    $stmt = $conn->prepare("DELETE FROM visitor_flags WHERE id = ?");
    $stmt->bind_param("i", $data['id']);
    if ($stmt->execute()) {
        $response = ['status' => 'success'];
    }
}

// I-output ang final response
echo json_encode($response);
?>