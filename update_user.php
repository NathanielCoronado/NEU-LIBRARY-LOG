<?php
/**
 * FILE: update_user.php
 * PURPOSE: Admin-only. Edit a registered user's details (User Management > Edit).
 *          Saved to the account and copied to all of the user's visit records,
 *          so the visitor form is pre-filled with the new details on next login.
 */
require_once 'auth.php';
require_admin_api();
require_once 'profile.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
    exit;
}

$email = trim($_POST['email'] ?? '');
$data  = [
    'first_name'  => $_POST['first_name']  ?? '',
    'middle_name' => $_POST['middle_name'] ?? '',
    'last_name'   => $_POST['last_name']   ?? '',
    'suffix'      => $_POST['suffix']      ?? '',
    'id_number'   => $_POST['id_number']   ?? '',
    'contact'     => $_POST['contact']     ?? '',
    'user_type'   => $_POST['user_type']   ?? '',
    'course'      => $_POST['course']      ?? '',
];

if ($email === '' || trim($data['first_name']) === '' || trim($data['last_name']) === '') {
    echo json_encode(['status' => 'error', 'message' => 'FIRST AND LAST NAME ARE REQUIRED.']);
    exit;
}

ensure_profile_schema(); // before the transaction (ALTER TABLE can't run inside one)

if ($owner = id_owner_conflict($data['id_number'], $email)) {
    echo json_encode(['status' => 'error', 'message' => "ID NUMBER IS ALREADY USED BY " . strtoupper($owner) . "."]);
    exit;
}

$conn->begin_transaction();
try {
    $exists = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $exists->bind_param("s", $email);
    $exists->execute();
    if (!$exists->get_result()->fetch_assoc()) throw new Exception('USER NOT FOUND.');

    save_profile($email, $data);
    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'USER DETAILS UPDATED EVERYWHERE.']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage() ?: 'UPDATE FAILED.']);
}
