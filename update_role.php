<?php
/**
 * FILE: update_role.php
 * PURPOSE: Admin-only. Promote a user to ADMIN or revert to USER.
 *          Promoted users who are inside the library are automatically timed out.
 */
require_once 'auth.php';
require_admin_api();
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['status' => 'error', 'message' => 'INVALID REQUEST.']);
    exit;
}

$email = trim($_POST['email'] ?? '');
$role  = strtoupper(trim($_POST['role'] ?? ''));

if ($email === '' || !in_array($role, ['ADMIN', 'USER'], true)) {
    echo json_encode(['status' => 'error', 'message' => 'INVALID ROLE OR EMAIL.']);
    exit;
}
if ($email === ($_SESSION['email'] ?? '') && $role === 'USER') {
    echo json_encode(['status' => 'error', 'message' => 'YOU CANNOT REVOKE YOUR OWN ADMIN ACCESS.']);
    exit;
}

$conn->begin_transaction();
try {
    /* --- 1. UPDATE ROLE --- */
    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE email = ?");
    $stmt->bind_param("ss", $role, $email);
    $stmt->execute();

    /* --- 2. AUTO-OUT WHEN PROMOTED --- */
    if ($role === 'ADMIN') {
        $find = $conn->prepare("SELECT * FROM active_sessions WHERE email = ?");
        $find->bind_param("s", $email);
        $find->execute();
        $res = $find->get_result();

        while ($row = $res->fetch_assoc()) {
            $time_out = date('h:i:s A');
            $ins = $conn->prepare("INSERT INTO library_logs
                (id_number, first_name, middle_name, last_name, suffix, user_type, course, email, contact, reason, others_detail, time_in, time_out, date_visited)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $ins->bind_param("ssssssssssssss",
                $row['id_number'], $row['first_name'], $row['middle_name'], $row['last_name'], $row['suffix'],
                $row['user_type'], $row['course'], $row['email'], $row['contact'], $row['reason'],
                $row['others_detail'], $row['time_in'], $time_out, $row['date_visited']);
            $ins->execute();
        }

        $del = $conn->prepare("DELETE FROM active_sessions WHERE email = ?");
        $del->bind_param("s", $email);
        $del->execute();
    }

    $conn->commit();
    $msg = ($role === 'ADMIN') ? "PROMOTED TO ADMIN & AUTO-OUT SUCCESS." : "ROLE UPDATED TO USER.";
    echo json_encode(['status' => 'success', 'message' => $msg]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => 'FAILED TO UPDATE ROLE.']);
}
