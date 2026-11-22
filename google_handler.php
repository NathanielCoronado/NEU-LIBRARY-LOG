<?php
// --- INITIALIZATION ---
require_once 'auth.php';
header('Content-Type: application/json');
$response_data = [];

// --- REQUEST VALIDATION ---
if (isset($_POST['id_token'])) {
    $id_token = $_POST['id_token'];
    $mode = $_POST['mode'] ?? 'login';
    $agreed = $_POST['agreed'] ?? 'false';

    // --- TOKEN VERIFICATION (Google checks the signature, we check it was issued for OUR app) ---
    $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . urlencode($id_token);
    $response = false;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
        $response = curl_exec($ch);
        curl_close($ch);
    } else {
        $response = @file_get_contents($url);
    }
    $payload = $response ? json_decode($response, true) : null;

    $valid = $payload && isset($payload['sub'], $payload['email'])
          && ($payload['aud'] ?? '') === cfg('google_client_id')
          && in_array($payload['email_verified'] ?? '', ['true', true], true);

    // --- DATA PROCESSING ---
    if ($valid) {
        $email      = $payload['email'];
        $first_name = $payload['given_name']  ?? explode('@', $email)[0];
        $last_name  = $payload['family_name'] ?? '';

        $raw_pic     = $payload['picture'] ?? '';
        $hd_photo    = str_replace(["=s96-c", "/s96-c"], ["=s1000-c", "/s1000-c"], $raw_pic);
        $profile_pic = $hd_photo;

        // --- DATABASE QUERY ---
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        // --- EXISTING USER LOGIC ---
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $user_id = $user['id'];

            // Restriction Check
            if (isset($user['is_blocked']) && (int)$user['is_blocked'] === 1) {
                $response_data = ["status" => "blocked", "message" => "Your account is restricted."];
                echo json_encode($response_data);
                exit();
            }

            // Sunday restriction (admins may still log in)
            if (date('l') === 'Sunday' && strtoupper(trim($user['role'] ?? '')) !== 'ADMIN') {
                echo json_encode(["status" => "closed", "message" => "The library is closed on Sundays."]);
                exit();
            }

            // Profile Update
            $update_stmt = $conn->prepare("UPDATE users SET profile_pic = ?, is_verified = 1 WHERE id = ?");
            $update_stmt->bind_param("si", $profile_pic, $user_id);
            $update_stmt->execute();
            
            // Session Setup
            session_regenerate_id(true);
            $_SESSION['role']         = strtoupper(trim($user['role'] ?? 'USER')) ?: 'USER';
            $_SESSION['user_id']      = $user_id;
            $_SESSION['email']        = $email;
            $_SESSION['first_name']   = $user['first_name'];
            $_SESSION['last_name']    = $user['last_name'];
            $_SESSION['profile_pic']  = $hd_photo; 
            $_SESSION['is_verified']  = 1;

            $response_data = ["status" => "success", "role" => $_SESSION['role']];

        // --- NEW USER LOGIC ---
        } else {
            if ($mode === 'login') {
                $response_data = ["status" => "not_found"];
            } else {
                if ($agreed !== 'true') {
                    $response_data = ["status" => "needs_agreement"];
                } else {
                    // Registration Execution
                    $insert_stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, profile_pic, is_verified) VALUES (?, ?, ?, ?, 1)");
                    $insert_stmt->bind_param("ssss", $first_name, $last_name, $email, $profile_pic);
                    
                    if ($insert_stmt->execute()) {
                        session_regenerate_id(true);
                        $_SESSION['role']         = 'USER';
                        $_SESSION['user_id']      = $conn->insert_id;
                        $_SESSION['email']        = $email;
                        $_SESSION['first_name']   = $first_name;
                        $_SESSION['last_name']    = $last_name;
                        $_SESSION['profile_pic']  = $hd_photo;
                        $_SESSION['is_verified']  = 1;
                        $response_data = ["status" => "success"];
                    } else {
                        $response_data = ["status" => "error", "message" => "Database Write Failed"];
                    }
                }
            }
        }
    } else {
        $response_data = ["status" => "error", "message" => "Invalid Identity Token"];
    }
} else {
    $response_data = ["status" => "error", "message" => "Auth Request Missing Token"];
}

// --- FINAL OUTPUT ---
echo json_encode($response_data);
exit();
?>