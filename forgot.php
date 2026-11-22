<?php
/**
 * FILE: forgot.php
 * PURPOSE: User requests a password reset link by e-mail.
 */
session_start();
require_once 'mailer.php';

$flash = null; // ['type' => 'success'|'error', 'text' => '...']

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['forgot_password'])) {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);

    if (!$email) {
        $flash = ['type' => 'error', 'text' => 'Please enter a valid email address.'];
    } else {
        $result = send_password_reset($email);
        $flash  = ['type' => $result['status'], 'text' => $result['message']];
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
            background-image: url('neu-library.jpg');
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

        .flash { padding: 12px 14px; border-radius: 10px; font-size: 14px; margin-bottom: 18px; text-align: left; line-height: 1.4; }
        .flash-success { background: #e8f5e9; color: #1e7d32; border-left: 4px solid #1e7d32; }
        .flash-error { background: #fdecea; color: #b71c1c; border-left: 4px solid #c62828; }

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
    <link rel="icon" href="favicon.ico">
    <meta name="theme-color" content="#004aad">
    <link rel="stylesheet" href="responsive.css">
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

            <?php if ($flash): ?>
                <div class="flash flash-<?php echo e($flash['type']); ?>"><?php echo e($flash['text']); ?></div>
            <?php endif; ?>

            <form method="POST" action="" onsubmit="showLoading(this)">
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
