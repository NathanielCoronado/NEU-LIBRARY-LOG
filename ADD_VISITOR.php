<?php
header('Content-Type: application/json');
include 'db_conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* --- 1. DATA COLLECTION --- */
    $id_num        = trim($_POST['id_number'] ?? '');
    $f_name        = trim($_POST['first_name'] ?? '');
    $m_name        = trim($_POST['middle_name'] ?? '');
    $l_name        = trim($_POST['last_name'] ?? '');
    $suffix        = trim($_POST['suffix'] ?? '');
    $contact       = trim($_POST['contact'] ?? '');
    $u_type        = trim($_POST['user_type'] ?? 'VISITOR');
    
    // Fallback logic para sa Course at Reason
    $course        = !empty(trim($_POST['course'] ?? '')) ? trim($_POST['course']) : 'N/A';
    $reason        = !empty(trim($_POST['reason'] ?? '')) ? trim($_POST['reason']) : 'General Purpose';
    
    $others_detail = trim($_POST['others_detail'] ?? ''); 
    $today         = date('Y-m-d');

    /* --- 2. VALIDATION --- */
    if (empty($id_num)) {
        echo json_encode(['status' => 'error', 'message' => 'ID Number is required.']);
        exit;
    }

    try {
        $conn->begin_transaction();

        /* --- 3. DUPLICATE CHECK --- */
        $checkActive = $conn->prepare("SELECT id FROM active_sessions WHERE id_number = ? LIMIT 1");
        $checkActive->bind_param("s", $id_num);
        $checkActive->execute();
        
        if ($checkActive->get_result()->num_rows > 0) {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'USER IS STILL INSIDE THE LIBRARY.']);
            exit;
        }

        /* --- 4. MASTERLIST UPSERT --- */
        $sqlMaster = "INSERT INTO visitor_masterlist 
                      (id_number, first_name, middle_name, last_name, suffix, contact, user_type, course) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?) 
                      ON DUPLICATE KEY UPDATE 
                      first_name=VALUES(first_name), 
                      middle_name=VALUES(middle_name), 
                      last_name=VALUES(last_name), 
                      suffix=VALUES(suffix), 
                      contact=VALUES(contact), 
                      user_type=VALUES(user_type), 
                      course=VALUES(course)";
        
        $stmtMaster = $conn->prepare($sqlMaster);
        $stmtMaster->bind_param("ssssssss", $id_num, $f_name, $m_name, $l_name, $suffix, $contact, $u_type, $course);
        $stmtMaster->execute();

        /* --- 5. SESSION INSERT --- */
        $sqlActive = "INSERT INTO active_sessions 
                      (id_number, first_name, middle_name, last_name, suffix, user_type, course, reason, others_detail, contact, date_visited, time_in) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmtActive = $conn->prepare($sqlActive);
        $stmtActive->bind_param("sssssssssss", 
            $id_num,        // 1
            $f_name,        // 2
            $m_name,        // 3
            $l_name,        // 4
            $suffix,        // 5
            $u_type,        // 6
            $course,        // 7
            $reason,        // 8
            $others_detail, // 9
            $contact,       // 10
            $today          // 11
        );
        
        /* --- 6. EXECUTION & RESPONSE --- */
        if ($stmtActive->execute()) {
            $conn->commit();
            echo json_encode(['status' => 'success']);
        } else {
            throw new Exception("SQL Execute Error: " . $stmtActive->error);
        }

    } catch (Exception $e) {
        /* --- 7. ERROR HANDLING --- */
        if ($conn->in_transaction) { $conn->rollback(); }
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>