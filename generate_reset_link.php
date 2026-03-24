<?php
/**
 * 1. INITIALIZATION & DEPENDENCIES
 */
session_start();
date_default_timezone_set('Asia/Manila');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

include 'db_conn.php';

header('Content-Type: application/json');

/**
 * 2. REQUEST VALIDATION
 */
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = isset($_POST['email']) ? filter_var($_POST['email'], FILTER_SANITIZE_EMAIL) : '';

    if (empty($email)) {
        echo json_encode(['status' => 'error', 'message' => 'Email is required.']);
        exit;
    }

    /**
     * 3. DATABASE CHECK & TOKEN GENERATION
     */
    $stmt = $conn->prepare("SELECT first_name FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $fname = $user['first_name'];

        $token = bin2hex(random_bytes(32));
        $expiry = date("Y-m-d H:i:s", strtotime("+2 minutes"));

        $updateStmt = $conn->prepare("UPDATE users SET reset_token = ?, token_expiry = ? WHERE email = ?");
        $updateStmt->bind_param("sss", $token, $expiry, $email);

        /**
         * 4. SMTP CONFIGURATION & EMAIL SENDING
         */
        if ($updateStmt->execute()) {
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'nathaniel.coronado@neu.edu.ph'; 
                $mail->Password   = 'qfbe nosw udgp zofg'; 
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
                $mail->Port       = 587;

                $mail->setFrom('noreply@neu.edu.ph', 'NEU Library');
                $mail->addAddress($email, $fname);

                $mail->isHTML(true);
                $mail->Subject = 'Security Notice: Password Reset Request';
                
                $reset_link = "http://localhost/reset.php?token=$token&email=" . urlencode($email);

                $mail->Body = "
                <div style='background-color: #f4f7f6; padding: 40px 10px; font-family: \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif;'>
                    <div style='max-width: 550px; background: #ffffff; margin: auto; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: 1px solid #e1e1e1;'>
                        <div style='background: linear-gradient(135deg, #4b0000 0%, #c62828 50%, #ff5722 100%); padding: 35px 20px; text-align: center;'>
                            <h1 style='color: white; margin: 0; font-size: 24px; letter-spacing: 1px; text-transform: uppercase; font-weight: 700;'>NEU Library Security</h1>
                        </div>
                        <div style='padding: 40px; text-align: left; color: #2d3436;'>
                            <h2 style='color: #c62828; margin-top: 0; font-size: 20px;'>Hello, $fname!</h2>
                            <p style='font-size: 16px; line-height: 1.6; color: #4a4a4a;'>We received a request to reset your account password. To maintain the security of your library account, please click the button below:</p>
                            <div style='text-align: center; margin: 40px 0;'>
                                <a href='$reset_link' style='background: linear-gradient(135deg, #c62828 0%, #ff5722 100%); color: white; padding: 16px 40px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block; box-shadow: 0 4px 12px rgba(198, 40, 40, 0.25);'>RESET PASSWORD</a>
                            </div>
                            <div style='background-color: #fff8f6; border-left: 4px solid #ff5722; padding: 15px; margin-bottom: 25px; border-radius: 4px;'>
                                <p style='margin: 0; font-size: 14px; color: #b71c1c;'>
                                    <strong>Security Alert:</strong> This secure link is only valid for <span style='color: #ff5722; font-weight: bold;'>2 minutes</span>.
                                </p>
                            </div>
                            <p style='font-size: 13px; color: #7f8c8d; line-height: 1.5;'>If you did not request this, you can safely ignore this email. Your account remains secure.</p>
                            <hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>
                            <p style='font-size: 11px; color: #95a5a5; text-align: center;'>
                                &copy; " . date("Y") . " New Era University Library System
                            </p>
                        </div>
                    </div>
                </div>";

                $mail->send();
                echo json_encode(['status' => 'success', 'message' => 'Reset link sent to ' . $email]);

            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => 'Mailer Error: ' . $mail->ErrorInfo]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database update failed.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'The provided email is not registered.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
