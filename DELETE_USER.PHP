<?php
include_once 'db_conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? mysqli_real_escape_string($conn, $_POST['email']) : '';
    $id_number = isset($_POST['id_number']) ? mysqli_real_escape_string($conn, $_POST['id_number']) : '';

    if (!empty($email)) {
        $conn->begin_transaction();
        try {
            // Burahin sa lahat ng tables
            $conn->query("DELETE FROM users WHERE email = '$email'");
            $conn->query("DELETE FROM library_logs WHERE email = '$email' OR id_number = '$id_number'");
            $conn->query("DELETE FROM active_sessions WHERE email = '$email' OR id_number = '$id_number'");

            $conn->commit();
            echo "success"; // Ito ang babasahin ng Fetch sa JS
        } catch (Exception $e) {
            $conn->rollback();
            echo "error";
        }
    }
}
?>