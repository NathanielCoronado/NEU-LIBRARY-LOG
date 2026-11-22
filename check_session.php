<?php
/**
 * FILE: check_session.php
 * PURPOSE: Monitor account status AND notify for new/edited flags.
 */
require_once 'auth.php';

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
        else if (strtoupper($user_data['role'] ?? '') === 'ADMIN') {
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

        /** --- 2. FLAG NOTIFICATION DETECTION (only flags about THIS visitor) --- **/
        $my_id = '';
        if ($response['status'] === 'active' && $sid !== 0) {
            $idq = $conn->prepare("SELECT id_number FROM active_sessions WHERE id = ? LIMIT 1");
            $idq->bind_param("i", $sid);
            $idq->execute();
            $my_id = strtoupper(trim($idq->get_result()->fetch_assoc()['id_number'] ?? ''));
        }

        if ($my_id !== '' && $my_id !== 'N/A') {
            $nq = $conn->prepare("SELECT id, student_name, reason, flagged_at FROM visitor_flags WHERE UPPER(student_id) = ? ORDER BY flagged_at DESC, id DESC LIMIT 1");
            $nq->bind_param("s", $my_id);
            $nq->execute();
            $latest_flag = $nq->get_result()->fetch_assoc();

            if ($latest_flag) {
                // Unique token for change detection (new flag or edited reason)
                $current_flag_token = $latest_flag['id'] . '_' . $latest_flag['flagged_at'] . '_' . md5($latest_flag['reason']);

                if (!isset($_SESSION['last_flag_token'])) {
                    $_SESSION['last_flag_token'] = $current_flag_token;
                } elseif ($current_flag_token !== $_SESSION['last_flag_token']) {
                    $response['new_update'] = true;
                    $response['update_info'] = [
                        'name'   => $latest_flag['student_name'],
                        'reason' => $latest_flag['reason'],
                        'time'   => $latest_flag['flagged_at'],
                    ];
                    $_SESSION['last_flag_token'] = $current_flag_token;
                }
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