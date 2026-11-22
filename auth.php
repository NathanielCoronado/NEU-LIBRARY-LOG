<?php
/**
 * FILE: auth.php
 * PURPOSE: Session + access-control helpers shared by all pages and endpoints.
 *
 * USAGE:
 *   require_once 'auth.php';
 *   require_admin();        // admin-only PAGE  (redirects to login)
 *   require_admin_api();    // admin-only AJAX endpoint (returns JSON 403)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}
require_once __DIR__ . '/db_conn.php';

/** True when the logged-in session belongs to an admin that still exists and is not blocked. */
function is_admin() {
    global $conn;
    if (strtoupper($_SESSION['role'] ?? '') !== 'ADMIN') return false;

    // Master admin from config.php
    if (($_SESSION['user_id'] ?? '') === 'ADMIN_01') return true;

    // Database admin: re-check every request so a revoked/blocked admin loses access immediately
    $stmt = $conn->prepare("SELECT role, is_blocked FROM users WHERE email = ? LIMIT 1");
    $email = $_SESSION['email'] ?? '';
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row && strtoupper(trim($row['role'] ?? '')) === 'ADMIN' && (int) $row['is_blocked'] !== 1;
}

/** Protects an admin page. */
function require_admin() {
    if (!is_admin()) {
        header("Location: login.php");
        exit();
    }
}

/** Protects an admin AJAX endpoint. */
function require_admin_api() {
    if (!is_admin()) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Unauthorized. Please log in as admin.', 'error' => 'Unauthorized']);
        exit();
    }
}
