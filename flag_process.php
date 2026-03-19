<?php
header('Content-Type: application/json');
// Database connection
$conn = new mysqli("localhost", "root", "", "neu_library_signup_db");

if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Connection failed']));
}

$student_id = $_POST['student_id'] ?? '';
$student_name = $_POST['student_name'] ?? '';
$reason = $_POST['reason'] ?? '';
$action = $_POST['action'] ?? '';

if ($action === 'flag') {
    // I-insert sa table. status = 1 (Active Flag)
    $stmt = $conn->prepare("INSERT INTO flagged_students (student_id, student_name, reason, status) VALUES (?, ?, ?, 'FLAGGED') ON DUPLICATE KEY UPDATE reason = ?, status = 'FLAGGED', flagged_at = CURRENT_TIMESTAMP");
    $stmt->bind_param("ssss", $student_id, $student_name, $reason, $reason);
} else {
    // I-update ang status sa table
    $stmt = $conn->prepare("UPDATE flagged_students SET status = 'CLEARED' WHERE student_id = ?");
    $stmt->bind_param("s", $student_id);
}

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Database updated successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => $conn->error]);
}

$stmt->close();
$conn->close();
?>