<?php
/**
 * FILE: delete_user.php
 * PURPOSE: Permanently remove user and all associated library data (Logs, Sessions, Flags).
 */
include_once 'db_conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Input Sanitization
    $email = isset($_POST['email']) ? mysqli_real_escape_string($conn, $_POST['email']) : '';
    $id_number = isset($_POST['id_number']) ? mysqli_real_escape_string($conn, $_POST['id_number']) : '';

    if (!empty($email)) {
        // Start Transaction
        $conn->begin_transaction();

        try {
            // 1. CORE TABLE: Remove main account
            $conn->query("DELETE FROM users WHERE email = '$email'");

            // 2. ACTIVITY TABLES: Remove logs and sessions
            $conn->query("DELETE FROM library_logs WHERE email = '$email' OR id_number = '$id_number'");
            $conn->query("DELETE FROM active_sessions WHERE email = '$email' OR id_number = '$id_number'");

            // 3. FLAGGING SYSTEM: Remove visitor flags
            if (!empty($id_number)) {
                $conn->query("DELETE FROM visitor_flags WHERE student_id = '$id_number'");
            }

            // Commit Transaction
            $conn->commit();
            echo "success";

        } catch (Exception $e) {
            // Rollback on Failure
            $conn->rollback();
            echo "error";
        }
    } else {
        // Validation Error
        echo "missing_email";
    }
}
?>