<?php
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "neu_Library_signup_db");

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Connection Failed']);
    exit;
}

$action = $_POST['action'] ?? '';

// 1. INSERT FLAG
if ($action === 'flag') {
    $id = $_POST['id_number'];
    $name = $_POST['student_name'];
    $reason = $_POST['reason'];

    $stmt = $conn->prepare("INSERT INTO visitor_flags (idstudent, student_name, reason, flagged_at, status) VALUES (?, ?, ?, NOW(), 'FLAG')");
    $stmt->bind_param("sss", $id, $name, $reason);
    echo json_encode(['success' => $stmt->execute()]);
}

// 2. EDIT REASON
else if ($action === 'edit_reason') {
    $flag_id = $_POST['flag_id'];
    $reason = $_POST['reason'];

    $stmt = $conn->prepare("UPDATE visitor_flags SET reason = ? WHERE id = ?");
    $stmt->bind_param("si", $reason, $flag_id);
    echo json_encode(['success' => $stmt->execute()]);
}

// 3. DELETE/UNFLAG
else if ($action === 'delete_flag') {
    $mode = $_POST['mode'];

    if ($mode === 'record') {
        // Pag delete sa History Table
        $flag_id = $_POST['flag_id'];
        $stmt = $conn->prepare("DELETE FROM visitor_flags WHERE id = ?");
        $stmt->bind_param("i", $flag_id);
    } else {
        // Pag UNFLAG sa Main Table
        $id_number = $_POST['id_number'];
        $stmt = $conn->prepare("DELETE FROM visitor_flags WHERE idstudent = ? AND status = 'FLAG'");
        $stmt->bind_param("s", $id_number);
    }
    echo json_encode(['success' => $stmt->execute()]);
}

$conn->close();
?>