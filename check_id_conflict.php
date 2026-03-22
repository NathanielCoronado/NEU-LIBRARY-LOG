<?php
/* --- 1. CONFIGURATION & DATABASE CONNECTION --- */
include 'db_conn.php';

/* --- 2. INPUT INITIALIZATION --- */
$id_number = $_GET['id_number'] ?? '';
$response = ['exists' => false, 'name' => ''];

/* --- 3. MASTERLIST SEARCH LOGIC --- */
if (!empty($id_number)) {
    // Query masterlist for matching ID number
    $stmt = $conn->prepare("SELECT first_name, last_name FROM visitor_masterlist WHERE id_number = ? LIMIT 1");
    $stmt->bind_param("s", $id_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $response['exists'] = true;
        $response['name'] = $row['first_name'] . " " . $row['last_name'];
    }
}

/* --- 4. JSON OUTPUT DELIVERY --- */
echo json_encode($response);
?>