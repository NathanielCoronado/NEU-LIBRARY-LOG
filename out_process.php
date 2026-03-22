<?php
// --- 1. CONFIGURATION & HEADERS ---
require_once 'db_conn.php';
date_default_timezone_set('Asia/Manila');
header('Content-Type: application/json');

// --- 2. REQUEST VALIDATION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['session_id'])) {
    $session_id = $_POST['session_id'];

    // --- 3. FETCH ACTIVE SESSION ---
    $stmt = $conn->prepare("SELECT * FROM active_sessions WHERE id = ?");
    $stmt->bind_param("i", $session_id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();

    if ($data) {
        // Set timestamp
        $time_out = date("h:i:s A");

        // --- 4. TRANSFER TO LIBRARY LOGS ---
        $sql_insert = "INSERT INTO library_logs (user_type, id_number, contact, first_name, middle_name, last_name, suffix, course, time_in, time_out, reason, others_detail, date_visited, email) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_log = $conn->prepare($sql_insert);
        $stmt_log->bind_param("ssssssssssssss", 
            $data['user_type'], $data['id_number'], $data['contact'], $data['first_name'], 
            $data['middle_name'], $data['last_name'], $data['suffix'], $data['course'], 
            $data['time_in'], $time_out, $data['reason'], $data['others_detail'], 
            $data['date_visited'], $data['email']
        );

        if ($stmt_log->execute()) {
            // --- 5. DELETE FROM ACTIVE SESSIONS ---
            $stmt_del = $conn->prepare("DELETE FROM active_sessions WHERE id = ?");
            $stmt_del->bind_param("i", $session_id);
            $stmt_del->execute();

            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database log insert failed']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'No active record found']);
    }
    exit();
}

// --- 6. ERROR RESPONSE ---
echo json_encode(['success' => false, 'error' => 'Invalid Request']);
?>