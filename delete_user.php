<?php
/**
 * FILE: delete_user.php
 * PURPOSE: Admin-only. Permanently remove a user and all their library data (logs, sessions, flags).
 */
require_once 'auth.php';
require_admin_api();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "invalid_request";
    exit();
}

$email     = trim($_POST['email'] ?? '');
$id_number = trim($_POST['id_number'] ?? '');
if (in_array($id_number, ['PENDING/NO LOGS', 'NOT SET'], true)) $id_number = '';

if ($email === '') {
    echo "missing_email";
    exit();
}

$conn->begin_transaction();
try {
    // 1. Main account
    $stmt = $conn->prepare("DELETE FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    // 2. Logs and active sessions (by email, plus by ID number if known)
    foreach (['library_logs', 'active_sessions'] as $table) {
        $stmt = $conn->prepare("DELETE FROM $table WHERE email = ? OR (? <> '' AND id_number = ?)");
        $stmt->bind_param("sss", $email, $id_number, $id_number);
        $stmt->execute();
    }

    // 3. Flags
    if ($id_number !== '') {
        $stmt = $conn->prepare("DELETE FROM visitor_flags WHERE student_id = ?");
        $stmt->bind_param("s", $id_number);
        $stmt->execute();
    }

    $conn->commit();
    echo "success";
} catch (Exception $e) {
    $conn->rollback();
    echo "error";
}
