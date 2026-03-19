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

/**
 * 2. POST REQUEST HANDLING
 */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['forgot_password'])) {
    // Sanitize input
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    // Check if user exists
    $stmt = $conn->prepare("SELECT first_name FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $fname = $user['first_name'];

        // Generate token and 2-minute expiry
        $token = bin2hex(random_bytes(32));
        $expiry = date("Y-m-d H:i:s", strtotime("+2 minutes"));

        // Update database with reset token
        $updateStmt = $conn->prepare("UPDATE users SET reset_token = ?, token_expiry = ? WHERE email = ?");
        $updateStmt->bind_param("sss", $token, $expiry, $email);

        if ($updateStmt->execute()) {
            $mail = new PHPMailer(true);
            try {
                // SMTP Configuration
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'nathaniel.coronado@neu.edu.ph'; 
                $mail->Password   = 'fqvl bscb aibo bait'; 
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                // Recipients
                $mail->setFrom('noreply@neu.edu.ph', 'NEU Library');
                $mail->addAddress($email, $fname);

                // Email Content
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

                // Send email and notify user
                $mail->send();
                echo "<script>alert('Success! A password reset link has been dispatched to your email. (Valid for 2 mins)'); window.location.href='login.php';</script>";
            
            } catch (Exception $e) {
                // Handle PHPMailer errors
                echo "<script>alert('Mailer Error: {$mail->ErrorInfo}');</script>";
            }
        }
    } else {
        // Handle unregistered email
        echo "<script>alert('The provided email address is not registered in our system.'); window.location.href='forgot.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <style>
        /* --- GLOBAL STYLES --- */
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }

        body, html {
            width: 100%; 
            height: 100%; 
            overflow: hidden;
            background-color: #fff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* --- LAYOUT CONTAINERS --- */
        .header-container {
            position: relative; 
            width: 100%; 
            height: 100vh;
            display: flex; 
            justify-content: center; 
            align-items: center;
            overflow: hidden;
        }

        /* --- DECORATIVE BORDERS --- */
        .header-container::before, 
        .header-container::after {
            content: ""; 
            position: absolute; 
            left: 0; 
            width: 100%; 
            height: 36px; 
            z-index: 100;
            animation: slideInOnce 1.2s ease-out forwards;
        }
        
        .header-container::before { 
            top: 0; 
            background: linear-gradient(to bottom, #1e7d32 33.33%, #ffffff 33.33% 66.66%, #c62828 66.66%); 
            transform: translateX(-100%);
        }
        
        .header-container::after { 
            bottom: 0; 
            background: linear-gradient(to bottom, #c62828 33.33%, #ffffff 33.33% 66.66%, #1e7d32 66.66%); 
            transform: translateX(100%);
        }

        /* --- BACKGROUND & OVERLAY --- */
        .background-image {
            position: absolute; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            z-index: 1;
            background-image: url('https://github.com/NathanielCoronado/NEU-LIBRARY-LOG/blob/main/NEU%20LIBRARY.jpg?raw=true');
            background-size: cover; 
            background-position: center;
            filter: blur(8px); 
            transform: scale(1.1);
        }

        .overlay {
            position: absolute; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%;
            z-index: 2; 
            background: rgba(0,0,0,0.2);
        }

        /* --- CARD STYLING --- */
        .forgot-card {
            position: relative; 
            z-index: 10; 
            width: 90%; 
            max-width: 420px;
            padding: 40px 30px;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 25px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            text-align: center;
            border-top: 5px solid #c62828; 
        }

        .icon-container {
            width: 70px; 
            height: 70px;
            margin: 0 auto 20px;
            background: #f8d7da;
            border-radius: 50%;
            display: flex; 
            align-items: center; 
            justify-content: center;
            color: #c62828;
        }

        h2 { 
            margin-bottom: 10px; 
            color: #333; 
            font-size: 24px; 
            font-weight: 800; 
        }

        .subtitle { 
            font-size: 14px; 
            color: #555; 
            line-height: 1.5; 
            margin-bottom: 25px; 
        }

        /* --- FORM ELEMENTS --- */
        .form-group { 
            margin-bottom: 20px; 
        }

        input {
            width: 100%;
            padding: 14px 15px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 15px;
            transition: 0.3s;
        }

        input:focus { 
            border-color: #c62828; 
            box-shadow: 0 0 8px rgba(198, 40, 40, 0.2); 
            outline: none;
        }

        .btn-send {
            width: 100%;
            padding: 14px;
            background: #c62828;
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: bold;
            cursor: pointer;
            font-size: 16px;
            transition: 0.3s;
            text-transform: uppercase;
        }

        .btn-send:hover { 
            background: #a51d1d; 
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(198, 40, 40, 0.3);
        }

        .back-to-login { 
            margin-top: 25px; 
            font-size: 14px; 
        }

        .back-to-login a { 
            color: #1e7d32; 
            font-weight: bold; 
            text-decoration: none; 
        }

        .back-to-login a:hover { 
            text-decoration: underline; 
        }

        /* --- ANIMATIONS & QUERIES --- */
        @keyframes slideInOnce {
            to { transform: translateX(0); }
        }

        @media (max-width: 768px) {
            .forgot-card { max-width: 380px; }
        }

        @media (max-width: 480px) {
            .forgot-card { padding: 30px 20px; width: 92%; }
            h2 { font-size: 20px; }
            .subtitle { font-size: 13px; }
            .header-container::before, .header-container::after { height: 24px; }
            input, .btn-send { padding: 12px; font-size: 14px; }
        }
    </style>
</head>
<body>

    <div class="header-container">
        <div class="background-image"></div>
        <div class="overlay"></div>

        <div class="forgot-card">
            <div class="icon-container">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/>
                </svg>
            </div>

            <h2>Forgot Password?</h2>
            <p class="subtitle">Enter your registered or institutional email address to receive a secure reset link.</p>

            <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" onsubmit="showLoading(this)">
                <div class="form-group">
                    <input type="email" name="email" id="userEmail" placeholder="e.g. name@neu.edu.ph" required>
                </div>
                <button type="submit" name="forgot_password" id="submitBtn" class="btn-send">Send Reset Link</button>
            </form>

            <div class="back-to-login">
                <a href="login.php">← Back to Login</a>
            </div>
        </div>
    </div>

    <script>
        function showLoading(form) {
            const btn = document.getElementById('submitBtn');
            btn.innerText = "Sending...";
            btn.style.opacity = "0.7";
            btn.style.pointerEvents = "none";
        }
    </script>

</body>
</html>