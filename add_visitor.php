<?php
/** --- 1. CONFIGURATION & HEADERS --- **/
header('Content-Type: application/json');
include 'db_conn.php';
date_default_timezone_set('Asia/Manila');

/** --- 2. REQUEST HANDLING --- **/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /** --- 2.1 DATA COLLECTION --- **/
    $id_num        = strtoupper(trim($_POST['id_number'] ?? 'N/A'));
    if(empty($id_num)) $id_num = 'N/A';

    $f_name        = strtoupper(trim($_POST['first_name'] ?? ''));
    $m_name        = strtoupper(trim($_POST['middle_name'] ?? ''));
    $l_name        = strtoupper(trim($_POST['last_name'] ?? ''));
    $suffix        = strtoupper(trim($_POST['suffix'] ?? ''));
    $contact       = trim($_POST['contact'] ?? '');
    $u_type        = strtoupper(trim($_POST['user_type'] ?? 'VISITOR'));
    
    $course        = !empty(trim($_POST['course'] ?? '')) ? strtoupper(trim($_POST['course'])) : 'N/A';
    $reason        = !empty(trim($_POST['reason'] ?? '')) ? trim($_POST['reason']) : '';
    $others_detail = trim($_POST['others_detail'] ?? ''); 
    $date_visited  = date('Y-m-d');

    /** --- 2.2 INPUT VALIDATION --- **/
    if (empty($f_name) || empty($l_name) || empty($u_type) || empty($reason)) {
        echo json_encode(['status' => 'error', 'message' => 'Required fields: First Name, Last Name, User Type, and Reason.']);
        exit;
    }

    /** --- 3. DATABASE TRANSACTIONS --- **/
    try {
        $conn->begin_transaction();

        /** --- 3.1 ACTIVE SESSION CHECK --- **/
        if ($id_num !== 'N/A') {
            $checkActive = $conn->prepare("SELECT id FROM active_sessions WHERE id_number = ? LIMIT 1");
            $checkActive->bind_param("s", $id_num);
            $checkActive->execute();
            $resCheck = $checkActive->get_result();
            
            if ($resCheck->num_rows > 0) {
                $conn->rollback();
                echo json_encode(['status' => 'error', 'message' => 'USER IS STILL INSIDE THE LIBRARY.']);
                exit;
            }
            $checkActive->close();
        }

        /** --- 3.2 MASTERLIST UPSERT --- **/
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
        $stmtMaster->close();

        /** --- 3.3 SESSION RECORDING --- **/
        $sqlActive = "INSERT INTO active_sessions 
                      (id_number, first_name, middle_name, last_name, suffix, user_type, course, reason, others_detail, contact, date_visited, time_in) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmtActive = $conn->prepare($sqlActive);
        $stmtActive->bind_param("sssssssssss", 
            $id_num, $f_name, $m_name, $l_name, $suffix, $u_type, $course, $reason, $others_detail, $contact, $date_visited
        );
        
        /** --- 4. EXECUTION & RESPONSE --- **/
        if ($stmtActive->execute()) {
            $conn->commit();
            echo json_encode(['status' => 'success', 'message' => 'Recorded for ' . $date_visited]);
        } else {
            throw new Exception("Failed to start active session.");
        }
        $stmtActive->close();

    } catch (Exception $e) {
        if ($conn->in_transaction) { $conn->rollback(); }
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

/** --- 5. CLEANUP --- **/
$conn->close();
?>