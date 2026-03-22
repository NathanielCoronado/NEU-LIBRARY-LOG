<?php
/* --- 1. CONFIG & HEADERS --- */
include 'db_conn.php';
header('Content-Type: application/json');

/* --- 2. VALIDATION & SANITIZATION --- */
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Sanitize inputs
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $role  = strtoupper(mysqli_real_escape_string($conn, $_POST['role'])); 

    // Validate role
    if (!in_array($role, ['ADMIN', 'USER'])) {
        echo json_encode(['status' => 'error', 'message' => 'INVALID ROLE TYPE.']);
        exit;
    }

    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        /* --- 3. CORE UPDATE: USER ROLE --- */
        $sql_update_role = "UPDATE users SET role = '$role' WHERE email = '$email'";
        if (!mysqli_query($conn, $sql_update_role)) throw new Exception("FAILED TO UPDATE ROLE.");

        /* --- 4. ADMIN LOGIC: AUTO-OUT --- */
        if ($role === 'ADMIN') {
            // Check for active sessions
            $check_active = mysqli_query($conn, "SELECT * FROM active_sessions WHERE email = '$email'");
            
            if (mysqli_num_rows($check_active) > 0) {
                $row = mysqli_fetch_assoc($check_active);
                $time_out = date('H:i:s');

                // Transfer session to logs
                $insert_log = "INSERT INTO library_logs 
                    (id_number, first_name, middle_name, last_name, suffix, user_type, course, email, reason, others_detail, time_in, time_out, date_visited) 
                    VALUES (
                        '{$row['id_number']}', '{$row['first_name']}', '{$row['middle_name']}', '{$row['last_name']}', 
                        '{$row['suffix']}', '{$row['user_type']}', '{$row['course']}', '{$row['email']}', 
                        '{$row['reason']}', '{$row['others_detail']}', '{$row['time_in']}', '$time_out', '{$row['date_visited']}'
                    )";
                
                if (!mysqli_query($conn, $insert_log)) throw new Exception("TRANSFER TO LOGS FAILED.");

                // Delete active session record
                if (!mysqli_query($conn, "DELETE FROM active_sessions WHERE email = '$email'")) 
                    throw new Exception("CLEAR SESSION FAILED.");
            }
        }

        /* --- 5. COMMIT & RESPONSE --- */
        mysqli_commit($conn);
        $msg = ($role === 'ADMIN') ? "PROMOTED TO ADMIN & AUTO-OUT SUCCESS." : "ROLE UPDATED TO USER.";
        echo json_encode(['status' => 'success', 'message' => $msg]);

    } catch (Exception $e) {
        // Rollback on failure
        mysqli_rollback($conn);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    // Handle invalid request
    echo json_encode(['status' => 'error', 'message' => 'INVALID REQUEST.']);
}
?>