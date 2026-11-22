<?php
/**
 * FILE: flag_process.php
 * PURPOSE: Admin-only. Add, edit or remove a visitor flag.
 *   (Previously connected to "localhost/root", which broke flagging on the live server.)
 */
require_once 'auth.php';
require_admin_api();
header('Content-Type: application/json');

$action       = $_POST['action'] ?? '';
$student_id   = strtoupper(trim($_POST['student_id'] ?? ''));
$student_name = strtoupper(trim($_POST['student_name'] ?? ''));
$reason       = strtoupper(trim($_POST['reason'] ?? ''));
$flag_id      = (int) ($_POST['flag_id'] ?? 0);

try {
    if ($action === 'flag') {
        if ($reason === '' || $student_name === '') throw new Exception('Missing name or reason.');
        $stmt = $conn->prepare("INSERT INTO visitor_flags (student_id, student_name, reason, flagged_at, status) VALUES (?, ?, ?, NOW(), 'FLAG')");
        $stmt->bind_param("sss", $student_id, $student_name, $reason);
        $stmt->execute();
        echo json_encode(['success' => true, 'id' => $conn->insert_id]);

    } elseif ($action === 'edit_reason') {
        if ($reason === '' || !$flag_id) throw new Exception('Missing reason.');
        // flagged_at is bumped so the flagged visitor gets notified of the change
        $stmt = $conn->prepare("UPDATE visitor_flags SET reason = ?, flagged_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $reason, $flag_id);
        $stmt->execute();
        echo json_encode(['success' => true]);

    } elseif ($action === 'delete_flag') {
        $stmt = $conn->prepare("DELETE FROM visitor_flags WHERE id = ?");
        $stmt->bind_param("i", $flag_id);
        $stmt->execute();
        echo json_encode(['success' => true]);

    } else {
        echo json_encode(['success' => false, 'error' => 'Unknown action.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
