<?php
/**
 * FILE: check_session.php
 * GINAGAMIT ITO NG JAVASCRIPT PARA MAG-CHECK SA BACKGROUND
 */
session_start();
require_once 'db_conn.php'; // Siguraduhin na tama ang filename ng connection mo

$response = ['status' => 'active'];

if (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];
    $sid = isset($_SESSION['active_id']) ? $_SESSION['active_id'] : 0;

    // 1. CHECK KUNG BINURA ANG USER SA DATABASE (ACCOUNT DELETION)
    $user_check = $conn->prepare("SELECT email FROM users WHERE email = ?");
    $user_check->bind_param("s", $email);
    $user_check->execute();
    $user_result = $user_check->get_result();

    if ($user_result->num_rows === 0) {
        // Wala na ang user sa database
        $response['status'] = 'account_deleted';
        session_unset();
        session_destroy();
    } 
    
    // 2. CHECK KUNG SINIPA NG ADMIN SA ACTIVE SESSIONS (FORCE LOGOUT)
    else if ($sid !== 0) {
        $sess_check = $conn->prepare("SELECT id FROM active_sessions WHERE id = ?");
        $sess_check->bind_param("i", $sid);
        $sess_check->execute();
        $sess_result = $sess_check->get_result();

        if ($sess_result->num_rows === 0) {
            // Wala na ang session record (binura ng Admin sa table)
            $response['status'] = 'admin_forced_out';
            session_unset();
            session_destroy();
        }
    }
} else {
    // Kung wala nang session (e.g. nag-expire), sipa na rin
    $response['status'] = 'session_expired';
}

// I-output bilang JSON para mabasa ng JavaScript
header('Content-Type: application/json');
echo json_encode($response);
exit();