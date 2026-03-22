<?php
/** --- 1. SESSION & DATABASE --- **/
session_start();
include 'db_conn.php';

// Set timezone to ensure correct day detection
date_default_timezone_set('Asia/Manila');
$current_day = date('l'); // Returns 'Sunday', 'Monday', etc.

/** --- 2. AUTHENTICATION --- **/
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];

    /** --- 2.1 ADMIN OVERRIDE --- **/
    // We allow the master admin to bypass the Sunday restriction for maintenance
    if ($email === 'neulib@login.2526' && $password === '2526123NEUlib') {
        session_unset(); 
        session_regenerate_id(true);

        $_SESSION['user_id']    = 'ADMIN_01';
        $_SESSION['email']      = $email;
        $_SESSION['first_name'] = 'System';
        $_SESSION['last_name']  = 'Admin';
        $_SESSION['role']       = 'ADMIN'; 

        header("Location: admin.php");
        exit();
    }

    /** --- 2.2 SUNDAY RESTRICTION --- **/
    if ($current_day === 'Sunday') {
        header("Location: login.php?status=library_closed");
        exit();
    }

    /** --- 2.3 DATABASE VERIFICATION --- **/
    $sql = "SELECT * FROM users WHERE email = '$email' LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        /** --- 2.4 PASSWORD & ROLE --- **/
        if (password_verify($password, $user['password'])) {
            $db_role = isset($user['role']) ? strtoupper(trim($user['role'])) : 'USER';
            
            session_unset(); 
            session_regenerate_id(true);
            
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['email']      = $user['email'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name']  = $user['last_name'];
            $_SESSION['role']       = $db_role;

            /** --- 3. REDIRECTION --- **/
            if ($db_role === 'ADMIN') {
                header("Location: admin.php");
            } else {
                header("Location: form.php");
            }
            exit();
            
        } else {
            /** --- 4. ERROR: PASSWORD --- **/
            header("Location: login.php?status=invalid_password&email=" . urlencode($email));
            exit();
        }
    } else {
        /** --- 5. ERROR: NOT FOUND --- **/
        header("Location: login.php?status=not_found");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | NEU Library</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /** --- GLOBAL STYLES --- **/
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body, html { width: 100%; height: 100%; overflow: hidden; background-color: #fff; font-family: 'Segoe UI', sans-serif; }

        /** --- BACKGROUND & LAYOUT --- **/
        .header-container { position: relative; width: 100%; height: 100vh; display: flex; justify-content: center; align-items: center; overflow: hidden; }
        .header-container::before, .header-container::after { content: ""; position: absolute; left: 0; width: 100%; height: 36px; z-index: 100; animation: slideInOnce 1.2s ease-out forwards; }
        .header-container::before { top: 0; background: linear-gradient(to bottom, #1e7d32 33.33%, #ffffff 33.33% 66.66%, #c62828 66.66%); transform: translateX(-100%); }
        .header-container::after { bottom: 0; background: linear-gradient(to bottom, #c62828 33.33%, #ffffff 33.33% 66.66%, #1e7d32 66.66%); transform: translateX(100%); }
        
        .background-image { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 1; background-image: url('https://github.com/NathanielCoronado/NEU-LIBRARY-LOG/blob/main/NEU%20LIBRARY.jpg?raw=trueA'); background-size: cover; background-position: center; filter: blur(6px); transform: scale(1.1); }
        .overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 2; background: rgba(255, 255, 255, 0.1); }

        /** --- CARD COMPONENTS --- **/
        .login-card { position: relative; z-index: 10; width: 90%; max-width: 400px; padding: 30px; background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); border-radius: 25px; box-shadow: 0 15px 35px rgba(0,0,0,0.3); text-align: center; border-top: 5px solid #1e7d32; }
        .logo-container { width: 85px; height: 85px; margin: 0 auto 10px; background: white; border-radius: 50%; border: 3px solid #fff; box-shadow: 0 5px 15px rgba(0,0,0,0.1); overflow: hidden; }
        .logo-image { width: 100%; height: 100%; object-fit: cover; }
        h2 { color: #333; margin-bottom: 5px; font-size: 24px; font-weight: 700; }
        .subtitle { font-size: 13px; color: #666; margin-bottom: 20px; }

        /** --- FORMS & INPUTS --- **/
        .form-group { margin-bottom: 15px; position: relative; }
        input { width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 12px; font-size: 14px; outline: none; transition: 0.3s; font-family: 'Inter', sans-serif; }
        input:focus { border-color: #1e7d32; box-shadow: 0 0 8px rgba(30,125,50,0.2); }
        .btn-login { width: 100%; padding: 12px; background: #1e7d32; color: white; border: none; border-radius: 12px; font-weight: bold; cursor: pointer; font-size: 16px; transition: 0.3s; text-transform: uppercase; letter-spacing: 0.5px; }
        .btn-login:hover { background: #145a24; transform: translateY(-1px); }
        .eye-icon { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; width: 20px; height: 20px; color: #666; display: flex; align-items: center; }
        .forgot { text-align: right; font-size: 12px; margin: -10px 0 15px; }
        .forgot a { color: #c62828; text-decoration: none; font-weight: bold; }
        
        /** --- SOCIAL & FOOTER --- **/
        .divider { margin: 15px 0; color: #999; font-size: 12px; display: flex; align-items: center; }
        .divider::before, .divider::after { content: ""; flex: 1; height: 1px; background: #ddd; margin: 0 10px; }
        .google-wrapper { display: flex; justify-content: center; margin-top: 10px; transition: all 0.4s ease; border-radius: 4px; }
        .login-link { margin-top: 20px; font-size: 13px; color: #444; }
        .login-link a { color: #1e7d32; font-weight: bold; text-decoration: none; }

        /** --- ANIMATIONS --- **/
        @keyframes slideInOnce { to { transform: translateX(0); } }
        @keyframes googlePulse {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(30, 125, 50, 0.7); }
            70% { transform: scale(1.05); box-shadow: 0 0 0 15px rgba(30, 125, 50, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(30, 125, 50, 0); }
        }
        .pulse-active { animation: googlePulse 1.5s infinite; }

        /** --- SWEETALERT OVERRIDES --- **/
        .neu-curved-popup { border-radius: 20px !important; box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important; border-top: 5px solid #1e7d32 !important; }
        .neu-curved-btn { border-radius: 10px !important; padding: 10px 20px !important; font-family: 'Inter', sans-serif !important; font-weight: 600 !important; }
        .border-red { border-top-color: #c62828 !important; }
        .swal2-title { font-family: 'Inter', sans-serif !important; color: #333 !important; }
        .swal2-title-red { color: #c62828 !important; }

        @media (max-width: 480px) { .login-card { width: 95%; padding: 25px 20px; } }
    </style>
</head>
<body>
    <div class="header-container">
        <div class="background-image"></div>
        <div class="overlay"></div>

        <div class="login-card">
            <div class="logo-container">
                <img src="https://github.com/NathanielCoronado/NEU-LIBRARY-LOG/blob/main/NEU%20LOGO.jpg?raw=true" class="logo-image" alt="Logo">
            </div>

            <h2>Welcome!</h2>
            <p class="subtitle">NEU Library Management System</p>

            <form action="" method="POST">
                <div class="form-group">
                    <input type="email" name="email" placeholder="Email Address" required>
                </div>

                <div class="form-group">
                    <input type="password" name="password" id="passwordField" placeholder="Password" required>
                    <div class="eye-icon" onclick="togglePassword()" id="eyeBtn">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>

                <div class="forgot">
                    <a href="forgot.php">Forgot Password?</a>
                </div>

                <button type="submit" name="login" class="btn-login">LOGIN</button>
            </form>

            <div class="divider">OR</div>

            <div class="google-wrapper" id="googleBtnWrapper">
                <div id="g_id_onload"
                     data-client_id="814210630472-5tdooa2at4t3jph9djk91kb4eandpbcm.apps.googleusercontent.com"
                     data-callback="handleCredentialResponse"
                     data-auto_prompt="false">
                </div>
                <div class="g_id_signin" data-type="standard" data-size="large" data-theme="outline" data-text="signin_with" data-shape="rectangular" data-logo_alignment="left"></div>
            </div>

            <div class="login-link">
                Don't have an account? <a href="signup.php">Sign Up</a>
            </div>
        </div>
    </div>

<script>
    /** --- PAGE INITIALIZATION --- **/
    document.addEventListener('DOMContentLoaded', function() {
        // DETECT REFRESH: Redirects to index.php if user reloads the page
        if (performance.getEntriesByType("navigation")[0].type === "reload") {
            window.location.href = 'index.php';
            return; // Stop further execution
        }

        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');
        const verifySuccess = urlParams.get('verify_success');
        const userEmail = urlParams.get('email');

        // Pulse Logic
        if (verifySuccess === 'true') { sessionStorage.setItem('should_pulse', 'true'); }
        if (sessionStorage.getItem('should_pulse') === 'true') {
            const wrapper = document.getElementById('googleBtnWrapper');
            if (wrapper) wrapper.classList.add('pulse-active');
        }

        // Form Prefill
        if (userEmail) { document.querySelector('input[name="email"]').value = decodeURIComponent(userEmail); }

        /** --- ALERT HANDLERS --- **/
        
        // NEW: Handler for Sunday/Closed Library
        if (status === 'library_closed') {
            Swal.fire({
                title: 'Library is Closed', 
                text: 'Today is Sunday. The library and login system are currently unavailable. Please visit us again on Monday!', 
                icon: 'info',
                confirmButtonText: 'UNDERSTOOD', 
                confirmButtonColor: '#c62828',
                customClass: { 
                    popup: 'neu-curved-popup border-red', 
                    title: 'swal2-title-red', 
                    confirmButton: 'neu-curved-btn' 
                }
            });
        }

        if (verifySuccess === 'true') {
            Swal.fire({
                title: 'Email Verified!',
                text: 'Authentication complete. Please sign in with Google to synchronize your library profile.',
                icon: 'success', confirmButtonColor: '#1e7d32', confirmButtonText: 'PROCEED',
                customClass: { popup: 'neu-curved-popup', confirmButton: 'neu-curved-btn' }
            });
        }

        if (status === 'link_expired') {
            Swal.fire({
                title: 'Link Expired',
                text: 'This verification link is no longer valid or you have already verified your email.',
                icon: 'warning', confirmButtonColor: '#1e7d32', confirmButtonText: 'LOGIN NOW',
                customClass: { popup: 'neu-curved-popup', confirmButton: 'neu-curved-btn' }
            });
        }

        if (status === 'invalid_token') {
            Swal.fire({
                title: 'Invalid Link',
                text: 'The verification token is broken or malformed. Please request a new link.',
                icon: 'error', confirmButtonColor: '#c62828', confirmButtonText: 'CLOSE',
                customClass: { popup: 'neu-curved-popup border-red', title: 'swal2-title-red', confirmButton: 'neu-curved-btn' }
            });
        }

        if (status === 'not_found') {
            Swal.fire({
                title: 'Account Not Found', 
                text: 'No account is linked to this email address.', 
                icon: 'question',
                showCancelButton: true, 
                confirmButtonText: 'SIGN UP NOW', 
                cancelButtonText: 'TRY AGAIN', 
                confirmButtonColor: '#1e7d32',
                customClass: { popup: 'neu-curved-popup', confirmButton: 'neu-curved-btn' }
            }).then((result) => { if (result.isConfirmed) { window.location.href = 'signup.php'; } });
        }

        if (status === 'blocked') {
            Swal.fire({
                title: 'Access Restricted', 
                text: 'Your account has been blocked. Please contact the library staff for assistance.', 
                icon: 'warning',
                confirmButtonText: 'UNDERSTOOD', confirmButtonColor: '#c62828',
                customClass: { popup: 'neu-curved-popup border-red', title: 'swal2-title-red', confirmButton: 'neu-curved-btn' }
            });
        }

        if (status === 'unverified') {
            Swal.fire({
                title: 'Account Unverified', 
                text: 'Please check your inbox and verify your email before logging in.', 
                icon: 'info',
                confirmButtonText: 'OKAY', confirmButtonColor: '#1e7d32',
                customClass: { popup: 'neu-curved-popup', confirmButton: 'neu-curved-btn' }
            });
        }

        if (status === 'invalid_password') {
            Swal.fire({
                title: 'Incorrect Password', 
                text: 'The password you entered is incorrect. Please try again.', 
                icon: 'error',
                showCancelButton: true, confirmButtonText: 'RESET PASSWORD', cancelButtonText: 'TRY AGAIN', confirmButtonColor: '#c62828',
                customClass: { popup: 'neu-curved-popup border-red', title: 'swal2-title-red', confirmButton: 'neu-curved-btn' }
            }).then((result) => { if (result.isConfirmed) { window.location.href = 'forgot.php'; } });
        }

        if (status || verifySuccess) { window.history.replaceState({}, document.title, window.location.pathname); }
    });

    /** --- EXTERNAL AUTH HANDLERS --- **/
    async function handleCredentialResponse(response) {
        // NEW: Client-side Sunday check for Google Login
        const today = new Date().toLocaleDateString('en-US', { weekday: 'long' });
        if (today === 'Sunday') {
            Swal.fire({
                title: 'Notice',
                text: 'Google Sign-in is also unavailable today while the library is closed.',
                icon: 'info', confirmButtonColor: '#c62828',
                customClass: { popup: 'neu-curved-popup border-red' }
            });
            return;
        }

        Swal.fire({
            title: 'Authenticating', 
            html: 'Verifying with Google...', 
            allowOutsideClick: false, 
            showConfirmButton: false,
            didOpen: () => { Swal.showLoading(); },
            customClass: { popup: 'neu-curved-popup' }
        });

        try {
            const formData = new FormData();
            formData.append('id_token', response.credential);
            formData.append('mode', 'login');

            const res = await fetch('google_handler.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.status === 'success') {
                sessionStorage.removeItem('should_pulse'); 
                window.location.href = 'form.php';
            } else if (data.status === 'blocked') {
                Swal.fire({ 
                    title: 'Access Restricted', 
                    text: 'Your account is currently blocked by the administrator.', 
                    icon: 'warning',
                    confirmButtonColor: '#c62828', 
                    customClass: { popup: 'neu-curved-popup border-red', title: 'swal2-title-red', confirmButton: 'neu-curved-btn' }
                });
            } else {
                Swal.fire({ 
                    title: 'Account Not Found', 
                    text: 'No account is linked to this Google profile. Please register first.', 
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'SIGN UP NOW',
                    cancelButtonText: 'TRY AGAIN',
                    confirmButtonColor: '#1e7d32',
                    customClass: { popup: 'neu-curved-popup', confirmButton: 'neu-curved-btn' }
                }).then((result) => { if (result.isConfirmed) { window.location.href = 'signup.php'; } });
            }
        } catch (err) { 
            Swal.fire({ 
                title: 'Connection Error', 
                text: 'Could not connect to the server. Please try again later.', 
                icon: 'error', 
                confirmButtonColor: '#c62828', 
                customClass: { popup: 'neu-curved-popup border-red' } 
            });
        }
    }

    /** --- UI HELPERS --- **/
    function togglePassword() {
        const pwd = document.getElementById('passwordField');
        const eyeBtn = document.getElementById('eyeBtn');
        const isPwd = pwd.type === 'password';
        pwd.type = isPwd ? 'text' : 'password';
        eyeBtn.innerHTML = isPwd ? 
            `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:20px;"><path d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" stroke-linecap="round" stroke-linejoin="round"/><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round"/></svg>` : 
            `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:20px;"><path d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
    }
    </script>
</body>
</html>