<?php
// --- 1. CONFIGURATION & HEADERS ---
include 'db_conn.php';
header('Content-Type: application/json');

// --- 2. REQUEST VALIDATION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- 3. DATA INPUT & PROCESSING ---
    $email = $_POST['email'] ?? '';
    $action = strtolower($_POST['action'] ?? ''); 
    
    if (empty($email)) {
        echo json_encode(['status' => 'error', 'message' => 'Email is required.']);
        exit;
    }

    // Map status: 1 for block, 0 for unblock
    $status = ($action === 'block') ? 1 : 0;

    // --- 4. DATABASE PREPARATION ---
    $sql = "UPDATE users SET is_blocked = ? WHERE email = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param("is", $status, $email);

    // --- 5. EXECUTION & RESPONSE ---
    if ($stmt->execute()) {
        /* FIX: Success returned as long as no execution error occurs */
        echo json_encode([
            'status' => 'success', 
            'message' => 'User ' . ($status ? 'blocked' : 'unblocked') . ' successfully.',
            'new_status' => $status
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $stmt->error]);
    }
    
    // --- 6. CLEANUP ---
    $stmt->close();
    $conn->close();

} else {
    // Handle non-POST requests
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>