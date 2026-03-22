<?php
/* --- 1. CONFIGURATION & HEADERS --- */
include 'db_conn.php';
header('Content-Type: application/json');

/* --- 2. REQUEST VALIDATION --- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $email = $_POST['email'] ?? '';
    $action = strtolower($_POST['action'] ?? ''); 
    
    if (empty($email)) {
        echo json_encode(['status' => 'error', 'message' => '⚠️ EMAIL IS REQUIRED.']);
        exit;
    }

    $status = ($action === 'block') ? 1 : 0;
    $auto_out_triggered = false;

    /* --- 3. START TRANSACTION --- */
    $conn->begin_transaction();

    try {
        /* STEP A: UPDATE BLOCK STATUS */
        $sql_update = "UPDATE users SET is_blocked = ? WHERE email = ?";
        $stmt = $conn->prepare($sql_update);
        $stmt->bind_param("is", $status, $email);
        $stmt->execute();

        /* STEP B: AUTO-OUT LOGIC (IF ACTION IS BLOCK) */
        if ($status === 1) {
            // 1. Hanapin kung ang user ay kasalukuyang 'Inside' (active_sessions)
            $check_active = $conn->prepare("SELECT * FROM active_sessions WHERE email = ?");
            $check_active->bind_param("s", $email);
            $check_active->execute();
            $result = $check_active->get_result();

            if ($result->num_rows > 0) {
                $session_data = $result->fetch_assoc();
                
                // 2. I-insert sa library_logs na may 'Blocked' status at current timestamp as time_out
                $insert_log = $conn->prepare("INSERT INTO library_logs 
                    (id_number, first_name, middle_name, last_name, suffix, user_type, course, email, reason, others_detail, time_in, time_out, date_visited, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, 'Blocked')");
                
                $insert_log->bind_param("ssssssssssss", 
                    $session_data['id_number'], $session_data['first_name'], $session_data['middle_name'], 
                    $session_data['last_name'], $session_data['suffix'], $session_data['user_type'], 
                    $session_data['course'], $session_data['email'], $session_data['reason'], 
                    $session_data['others_detail'], $session_data['time_in'], $session_data['date_visited']
                );
                $insert_log->execute();

                // 3. Tanggalin na sa active_sessions
                $delete_active = $conn->prepare("DELETE FROM active_sessions WHERE email = ?");
                $delete_active->bind_param("s", $email);
                $delete_active->execute();

                $auto_out_triggered = true;
            }
        }

        /* --- 4. COMMIT & RESPONSE --- */
        $conn->commit();
        
        $msg = 'USER ' . ($status ? 'BLOCKED' : 'UNBLOCKED') . ' SUCCESSFULLY.';
        echo json_encode([
            'status' => 'success', 
            'message' => $msg,
            'new_status' => $status,
            'auto_out_triggered' => $auto_out_triggered
        ]);

    } catch (Exception $e) {
        /* ROLLBACK IF ERROR OCCURS */
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => '❌ TRANSACTION FAILED: ' . $e->getMessage()]);
    }

    $conn->close();

} else {
    echo json_encode(['status' => 'error', 'message' => '⚠️ INVALID REQUEST METHOD.']);
}
?>