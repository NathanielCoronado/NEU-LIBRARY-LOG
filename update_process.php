<?php
/** --- 1. CONFIGURATION & HEADERS --- **/
header('Content-Type: application/json');
require_once 'auth.php';
require_admin_api();
require_once 'profile.php';
ensure_profile_schema();


/** --- 2. INPUT HANDLING & VALIDATION --- **/
$session_id    = $_POST['session_id'] ?? '';
$new_id        = trim($_POST['id_number'] ?? ''); 
$old_id        = trim($_POST['old_id_number'] ?? ''); 
$first_name    = trim($_POST['first_name'] ?? '');
$middle_name   = trim($_POST['middle_name'] ?? '');
$last_name     = trim($_POST['last_name'] ?? '');
$suffix        = trim($_POST['suffix'] ?? '');
$contact       = trim($_POST['contact'] ?? '');
$norm          = normalize_profile(['user_type' => $_POST['user_type'] ?? '', 'course' => $_POST['course'] ?? '']);
$u_type        = $norm['user_type'];
$course        = $norm['course'] ?: 'Not Applicable';
$reason        = canonical($_POST['reason'] ?? '', array_keys($REASONS));
$others_detail = trim($_POST['others_detail'] ?? ''); 

if (empty($session_id) || empty($new_id) || empty($old_id)) {
    echo json_encode(['success' => false, 'message' => 'Missing ID (New, Old, or Session)']);
    exit;
}

/** --- 3. DATABASE TRANSACTION --- **/
$conn->begin_transaction();

try {
    $source = $_POST['source'] ?? '';
    $visitTables = $source === 'active' ? ['active_sessions']
                 : ($source === 'log'   ? ['library_logs'] : ['active_sessions', 'library_logs']);

    /** --- 3.1 PHASE 1: PROFILE UPDATE --- **/
    $profileSet = "id_number = ?, first_name = ?, middle_name = ?, last_name = ?, suffix = ?, user_type = ?, course = ?, contact = ?";

    if (strtoupper($old_id) === 'N/A') {
        // Walk-in without an ID: only edit THIS row, otherwise every "N/A" visitor would be renamed
        foreach ($visitTables as $table) {
            $stmt = $conn->prepare("UPDATE $table SET $profileSet WHERE id = ? AND id_number = ?");
            $stmt->bind_param("ssssssssis", $new_id, $first_name, $middle_name, $last_name, $suffix, $u_type, $course, $contact, $session_id, $old_id);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        // Real ID number: keep the person's profile in sync across every record
        foreach (['active_sessions', 'library_logs', 'visitor_masterlist'] as $table) {
            $stmt = $conn->prepare("UPDATE $table SET $profileSet WHERE id_number = ?");
            $stmt->bind_param("sssssssss", $new_id, $first_name, $middle_name, $last_name, $suffix, $u_type, $course, $contact, $old_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    /** --- 3.2 PHASE 2: ROW-SPECIFIC VISIT UPDATE --- **/
    // Only touch the table the row came from (IDs are not shared between the two tables)
    foreach ($visitTables as $table) {
        $stmtV = $conn->prepare("UPDATE $table SET reason = ?, others_detail = ? WHERE id = ? AND id_number = ?");
        $stmtV->bind_param("ssis", $reason, $others_detail, $session_id, $new_id);
        $stmtV->execute();
        $stmtV->close();
    }

    /** --- 3.3 PHASE 3: KEEP REGISTERED ACCOUNTS IN SYNC --- **/
    // If these records belong to a registered user, save the edit to their profile too,
    // so their form is pre-filled with the corrected details next time they log in.
    $emails = [];
    foreach ($visitTables as $table) {
        $q = $conn->prepare("SELECT DISTINCT email FROM $table WHERE (id = ? OR (id_number = ? AND id_number <> 'N/A')) AND email IS NOT NULL AND email <> ''");
        $q->bind_param("is", $session_id, $new_id);
        $q->execute();
        $r = $q->get_result();
        while ($row = $r->fetch_assoc()) $emails[$row['email']] = true;
    }
    foreach (array_keys($emails) as $em) {
        save_profile($em, [
            'first_name' => $first_name, 'middle_name' => $middle_name, 'last_name' => $last_name,
            'suffix' => $suffix, 'id_number' => $new_id, 'contact' => $contact,
            'user_type' => $u_type, 'course' => $course,
        ]);
    }

    /** --- 4. EXECUTION & RESPONSE --- **/
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Profile and ID updated across all records.']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()]);
}

/** --- 5. CLEANUP --- **/
$conn->close();
?>