<?php
/**
 * FILE: generate_reset_link.php
 * PURPOSE: Admin-only. Sends a password reset link to a user (User Management > Reset).
 */
require_once 'auth.php';
require_once 'mailer.php';
require_admin_api();

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
if (!$email) {
    echo json_encode(['status' => 'error', 'message' => 'A valid email is required.']);
    exit;
}

echo json_encode(send_password_reset($email));
