<?php
/* --- 1. CONFIG & CONNECTION --- */
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "", "neu_Library_signup_db");

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'error' => 'Database Connection Failed']));
}

/* --- 2. INPUT MAPPING & AUTO-CAPS --- */
$action       = $_POST['action'] ?? '';
$student_id   = strtoupper($_POST['student_id'] ?? '');
$student_name = strtoupper($_POST['student_name'] ?? '');
$reason       = strtoupper($_POST['reason'] ?? '');
$flag_id      = $_POST['flag_id'] ?? ''; 

/* --- 3. CREATE: FLAG ACTION --- */
if ($action === 'flag') {
    $stmt = $conn->prepare("INSERT INTO visitor_flags (student_id, student_name, reason, flagged_at, status) VALUES (?, ?, ?, NOW(), 'FLAG')");
    $stmt->bind_param("sss", $student_id, $student_name, $reason);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'id' => $conn->insert_id]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    $stmt->close();
} 

/* --- 4. UPDATE: EDIT ACTION --- */
else if ($action === 'edit_reason') {
    $stmt = $conn->prepare("UPDATE visitor_flags SET reason = ? WHERE id = ?");
    $stmt->bind_param("si", $reason, $flag_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    $stmt->close();
}

/* --- 5. DELETE: UNFLAG ACTION --- */
else if ($action === 'delete_flag') {
    $stmt = $conn->prepare("DELETE FROM visitor_flags WHERE id = ?");
    $stmt->bind_param("i", $flag_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    $stmt->close();
}

/* --- 6. CLEANUP --- */
$conn->close();
?>