<?php
/**
 * FILE: check_session.php
 * PURPOSE: Monitor account status AND notify for new/edited flags.
 */
session_start();
require_once 'db_conn.php'; 

$response = ['status' => 'active'];

if (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];
    $sid = isset($_SESSION['active_id']) ? $_SESSION['active_id'] : 0;

    /** --- 1. USER & SESSION SECURITY CHECK --- **/
    $user_check = $conn->prepare("SELECT email, is_blocked, role FROM users WHERE email = ?");
    $user_check->bind_param("s", $email);
    $user_check->execute();
    $user_result = $user_check->get_result();

    if ($user_result->num_rows === 0) {
        $response['status'] = 'DELETED';
        session_unset(); session_destroy();
    } else {
        $user_data = $user_result->fetch_assoc();
        
        if ($user_data['is_blocked'] == 1) {
            $response['status'] = 'BLOCKED';
            session_unset(); session_destroy();
        } 
        else if (strtoupper($user_data['role']) === 'ADMIN') {
            $response['status'] = 'PROMOTED_TO_ADMIN';
            session_unset(); session_destroy();
        }
        else if ($sid !== 0) {
            $sess_check = $conn->prepare("SELECT id FROM active_sessions WHERE id = ?");
            $sess_check->bind_param("i", $sid);
            $sess_check->execute();
            if ($sess_check->get_result()->num_rows === 0) {
                $response['status'] = 'FORCED_OUT';
                session_unset(); session_destroy();
            }
        }

        /** --- 2. FLAG NOTIFICATION DETECTION --- **/
        $notif_query = $conn->query("SELECT id, student_name, reason, flagged_at FROM visitor_flags ORDER BY flagged_at DESC LIMIT 1");
        
        if ($notif_query && $notif_query->num_rows > 0) {
            $latest_flag = $notif_query->fetch_assoc();
            
            // Unique token for change detection
            $current_flag_token = $latest_flag['id'] . '_' . $latest_flag['flagged_at'];

            // Initialize token if first run
            if (!isset($_SESSION['last_flag_token'])) {
                $_SESSION['last_flag_token'] = $current_flag_token;
            }

            // Compare token to detect add or edit
            if ($current_flag_token !== $_SESSION['last_flag_token']) {
                $response['new_update'] = true;
                $response['update_info'] = [
                    'name' => $latest_flag['student_name'],
                    'reason' => $latest_flag['reason'],
                    'time' => $latest_flag['flagged_at']
                ];
                
                $_SESSION['last_flag_token'] = $current_flag_token;
            }
        }
    }
} else {
    $response['status'] = 'session_expired';
}

/** --- 3. JSON OUTPUT --- **/
header('Content-Type: application/json');
echo json_encode($response);
exit();