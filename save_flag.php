<?php
/** --- 1. CONFIGURATION & HEADERS --- **/
include_once 'db_conn.php';
header('Content-Type: application/json');

/** --- 2. INPUT DECODING --- **/
$data   = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$response = ['status' => 'error', 'message' => 'Invalid action'];

/** --- 3. ACTION DISPATCHER --- **/

// ADD FLAG
if ($action == 'add') {
    $stmt = $conn->prepare("INSERT INTO visitor_flags (full_name, id_number, reason, status) VALUES (?, ?, ?, 'active')");
    $stmt->bind_param("sss", $data['name'], $data['id_number'], $data['reason']);
    if ($stmt->execute()) {
        $response = ['status' => 'success', 'id' => $stmt->insert_id];
    }
} 

// CLEAR FLAG
elseif ($action == 'unflag') {
    $stmt = $conn->prepare("UPDATE visitor_flags SET status = 'cleared' WHERE id = ?");
    $stmt->bind_param("i", $data['id']);
    if ($stmt->execute()) {
        $response = ['status' => 'success'];
    }
}

// EDIT REASON
elseif ($action == 'edit') {
    $stmt = $conn->prepare("UPDATE visitor_flags SET reason = ? WHERE id = ?");
    $stmt->bind_param("si", $data['reason'], $data['id']);
    if ($stmt->execute()) {
        $response = ['status' => 'success'];
    }
}

// DELETE RECORD
elseif ($action == 'delete') {
    $stmt = $conn->prepare("DELETE FROM visitor_flags WHERE id = ?");
    $stmt->bind_param("i", $data['id']);
    if ($stmt->execute()) {
        $response = ['status' => 'success'];
    }
}

/** --- 4. OUTPUT RESPONSE --- **/
echo json_encode($response);
?>