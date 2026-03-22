<?php
/** --- 1. CONFIGURATION & HEADERS --- **/
header('Content-Type: application/json');
include 'db_conn.php'; 

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB Connection Failed']);
    exit;
}

/** --- 2. INPUT HANDLING & VALIDATION --- **/
$session_id    = $_POST['session_id'] ?? '';
$new_id        = trim($_POST['id_number'] ?? ''); 
$old_id        = trim($_POST['old_id_number'] ?? ''); 
$first_name    = trim($_POST['first_name'] ?? '');
$middle_name   = trim($_POST['middle_name'] ?? '');
$last_name     = trim($_POST['last_name'] ?? '');
$suffix        = trim($_POST['suffix'] ?? '');
$contact       = trim($_POST['contact'] ?? '');
$u_type        = trim($_POST['user_type'] ?? ''); 
$course        = trim($_POST['course'] ?? '');    
$reason        = trim($_POST['reason'] ?? '');    
$others_detail = trim($_POST['others_detail'] ?? ''); 

if (empty($session_id) || empty($new_id) || empty($old_id)) {
    echo json_encode(['success' => false, 'message' => 'Missing ID (New, Old, or Session)']);
    exit;
}

/** --- 3. DATABASE TRANSACTION --- **/
$conn->begin_transaction();

try {
    /** --- 3.1 PHASE 1: GLOBAL PROFILE UPDATE --- **/
    $tables = ['active_sessions', 'library_logs', 'visitor_masterlist'];
    
    foreach ($tables as $table) {
        $sql = "UPDATE $table SET 
                id_number = ?, first_name = ?, middle_name = ?, last_name = ?, suffix = ?, 
                user_type = ?, course = ?, contact = ? 
                WHERE id_number = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssss", $new_id, $first_name, $middle_name, $last_name, $suffix, $u_type, $course, $contact, $old_id);
        $stmt->execute();
        $stmt->close();
    }

    /** --- 3.2 PHASE 2: ROW-SPECIFIC VISIT UPDATE --- **/
    // Update active_sessions
    $sql_act = "UPDATE active_sessions SET reason = ?, others_detail = ? WHERE id = ? AND id_number = ?";
    $stmt1 = $conn->prepare($sql_act);
    $stmt1->bind_param("ssis", $reason, $others_detail, $session_id, $new_id);
    $stmt1->execute();
    $stmt1->close();

    // Update library_logs
    $sql_lib = "UPDATE library_logs SET reason = ?, others_detail = ? WHERE id = ? AND id_number = ?";
    $stmt2 = $conn->prepare($sql_lib);
    $stmt2->bind_param("ssis", $reason, $others_detail, $session_id, $new_id);
    $stmt2->execute();
    $stmt2->close();

    /** --- 4. EXECUTION & RESPONSE --- **/
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Profile and ID updated across all records.']);

} catch (Exception $e) {
    if ($conn->in_transaction) { $conn->rollback(); }
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()]);
}

/** --- 5. CLEANUP --- **/
$conn->close();
?>