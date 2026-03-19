<?php
/* --- 1. CONFIGURATION --- */
header('Content-Type: application/json');
include 'db_conn.php'; 

/* --- 2. REQUEST HANDLING --- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id']; 
    $id_number = null;
    $success = false;

    /* --- 3. FETCH USER ID --- */
    $stmtFind = $conn->prepare("
        SELECT id_number FROM active_sessions WHERE id = ? 
        UNION 
        SELECT id_number FROM library_logs WHERE id = ? 
        LIMIT 1
    ");
    $stmtFind->bind_param("ii", $id, $id);
    $stmtFind->execute();
    $res = $stmtFind->get_result();
    if ($row = $res->fetch_assoc()) {
        $id_number = $row['id_number'];
    }
    $stmtFind->close();

    /* --- 4. EXECUTE DELETION --- */
    $stmt1 = $conn->prepare("DELETE FROM active_sessions WHERE id = ?");
    $stmt1->bind_param("i", $id); 
    if($stmt1->execute() && $stmt1->affected_rows > 0) {
        $success = true;
    }

    $stmt2 = $conn->prepare("DELETE FROM library_logs WHERE id = ?");
    $stmt2->bind_param("i", $id);
    if($stmt2->execute() && $stmt2->affected_rows > 0) {
        $success = true;
    }

    /* --- 5. MASTERLIST CLEANUP --- */
    if ($id_number) {
        $stmtClean = $conn->prepare("
            DELETE FROM visitor_masterlist 
            WHERE id_number = ? 
            AND NOT EXISTS (SELECT 1 FROM active_sessions WHERE id_number = ?)
            AND NOT EXISTS (SELECT 1 FROM library_logs WHERE id_number = ?)
        ");
        $stmtClean->bind_param("sss", $id_number, $id_number, $id_number);
        $stmtClean->execute();
        $stmtClean->close();
    }

    /* --- 6. OUTPUT RESPONSE --- */
    if ($success) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No record found.']);
    }

    /* --- 7. CONNECTION CLEANUP --- */
    $stmt1->close();
    $stmt2->close();
    $conn->close();
}
?>