<?php
header('Content-Type: application/json');

/** 1. DATABASE CONNECTION **/
include 'db_conn.php'; 

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB Connection Failed']);
    exit;
}

/** 2. INPUT HANDLING **/
// Kunin ang data base sa names na ginamit sa FormData sa admin.js
$session_id    = $_POST['session_id'] ?? '';
$id_number     = trim($_POST['id_number'] ?? '');
$first_name    = trim($_POST['first_name'] ?? '');
$middle_name   = trim($_POST['middle_name'] ?? '');
$last_name     = trim($_POST['last_name'] ?? '');
$suffix        = trim($_POST['suffix'] ?? '');
$contact       = trim($_POST['contact'] ?? '');
$u_type        = trim($_POST['user_type'] ?? ''); // Mula sa 'user_type' sa JS
$course        = trim($_POST['course'] ?? '');    // Mula sa 'course' sa JS
$reason        = trim($_POST['reason'] ?? '');    // Mula sa 'reason' sa JS
$others_detail = trim($_POST['others_detail'] ?? ''); 

// Basic Validation
if (empty($session_id) || empty($id_number)) {
    echo json_encode(['success' => false, 'message' => 'Missing Required Fields (ID or Session)']);
    exit;
}

/** 3. DATABASE TRANSACTION **/
$conn->begin_transaction();

try {
    /* --- Phase 1: Update Masterlist --- */
    $sql_master = "INSERT INTO visitor_masterlist 
                  (id_number, first_name, middle_name, last_name, suffix, user_type, course, contact) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                  ON DUPLICATE KEY UPDATE 
                  first_name = VALUES(first_name),
                  middle_name = VALUES(middle_name),
                  last_name = VALUES(last_name),
                  suffix = VALUES(suffix),
                  user_type = VALUES(user_type),
                  course = VALUES(course),
                  contact = VALUES(contact)";
    
    $stmt_m = $conn->prepare($sql_master);
    $stmt_m->bind_param("ssssssss", $id_number, $first_name, $middle_name, $last_name, $suffix, $u_type, $course, $contact);
    $stmt_m->execute();

    /* --- Phase 2: Update Active Sessions --- */
    // In-update natin ang lahat ng fields para mag-reflect agad sa UI table
    $sql1 = "UPDATE active_sessions SET 
            first_name = ?, middle_name = ?, last_name = ?, suffix = ?, 
            user_type = ?, course = ?, reason = ?, others_detail = ?, contact = ?
            WHERE id = ? AND id_number = ?";
    
    $stmt1 = $conn->prepare($sql1);
    // Bind param: 11 placeholders (?) = 11 variables
    // Gamit ang "sssssssssis" (s = string, i = integer para sa session_id)
    $stmt1->bind_param("sssssssssis", 
        $first_name, $middle_name, $last_name, $suffix, 
        $u_type, $course, $reason, $others_detail, $contact, 
        $session_id, $id_number
    );
    $stmt1->execute();

    /* --- Phase 3: Update Library Logs --- */
    $sql2 = "UPDATE library_logs SET 
            reason = ?, others_detail = ? 
            WHERE id = ? AND id_number = ?";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("ssis", $reason, $others_detail, $session_id, $id_number);
    $stmt2->execute();

    /* --- Finalize Transaction --- */
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Record updated successfully']);

} catch (Exception $e) {
    if ($conn->in_transaction) { $conn->rollback(); }
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()]);
}

/** 4. CLOSE CONNECTION **/
if(isset($stmt_m)) $stmt_m->close();
if(isset($stmt1)) $stmt1->close();
if(isset($stmt2)) $stmt2->close();
$conn->close();
?>