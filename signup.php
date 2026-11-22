<?php
/**
 * FILE: signup.php
 * PURPOSE: Manual registration. Sends an activation e-mail; the account is only
 *          created after the user clicks the (signed, 24-hour) link in verify.php.
 */
session_start();
require_once 'mailer.php';

function signup_alert($msg, $redirect = null) {
    $js = json_encode($msg);
    $go = $redirect ? "window.location.href=" . json_encode($redirect) . ";" : "window.history.back();";
    echo "<script>alert($js); $go</script>";
    exit();
}

if (isset($_POST['signup'])) {

    /* --- 1. VALIDATION --- */
    if (!isset($_POST['privacy'])) {
        signup_alert('You must agree to the Data Privacy Policy to register.');
    }

    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $email      = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $pw         = $_POST['password'] ?? '';

    if ($first_name === '' || $last_name === '' || !$email) {
        signup_alert('Please complete your name and a valid email address.');
    }
    if (strlen($pw) < 8 || !preg_match('/[A-Z]/', $pw) || !preg_match('/[a-z]/', $pw)
        || !preg_match('/[0-9]/', $pw) || !preg_match('/[^A-Za-z0-9]/', $pw)) {
        signup_alert('Password does not meet the requirements.');
    }

    /* --- 2. DUPLICATE CHECK --- */
    $check = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $check->bind_param("s", $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        signup_alert('This email address is already registered.', 'login.php');
    }
    $check->close();

    /* --- 3. AVATAR + SIGNED VERIFICATION TOKEN --- */
    $colors       = ['1e7d32', '2e5a88', 'a02c2c', '6a1b9a', 'ef6c00', '00838f', '4527a0'];
    $initials_url = "https://ui-avatars.com/api/?name=" . urlencode("$first_name $last_name")
                  . "&background=" . $colors[array_rand($colors)] . "&color=fff&size=512&bold=true";
    $image        = "https://www.gravatar.com/avatar/" . md5(strtolower($email)) . "?s=512&d=" . urlencode($initials_url);

    $token = make_signed_token([
        'fn'   => $first_name,
        'ln'   => $last_name,
        'em'   => $email,
        'pw'   => password_hash($pw, PASSWORD_DEFAULT),
        'pic'  => $image,
        'time' => time(),
    ]);
    $verifyLink = app_url() . "/verify.php?token=" . urlencode($token);

    /* --- 4. SEND ACTIVATION E-MAIL --- */
    $fn   = e($first_name);
    $body = "<h2 style='margin-top:0;color:#263238;'>Greetings, $fn!</h2>
             <p>To finalize your access to the <strong>NEU Library Visitor Log</strong>, please verify your e-mail address.</p>"
          . email_button($verifyLink, 'VERIFY MY PROFILE', '#00796b') .
            "<p style='font-size:13px;color:#90a4ae;'>This link expires in 24 hours. If you did not request this registration, you can ignore this email.</p>";

    try {
        $mail = make_mailer();
        $mail->addAddress($email, $first_name);
        $mail->Subject = 'Account Activation - NEU Library';
        $mail->Body    = email_layout('linear-gradient(135deg,#004d40,#00796b)', 'NEU Library', $body);
        $mail->AltBody = "Verify your NEU Library account: $verifyLink";
        $mail->send();
        signup_alert("Activation link sent to $email. Please check your inbox (and spam folder).", 'login.php');
    } catch (Exception $ex) {
        signup_alert('Mail Error: ' . $ex->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>

    <script src="https://accounts.google.com/gsi/client" async defer></script>

    <style>
        /* 1. RESET & BASE STYLES */
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
            font-family: 'Segoe UI', sans-serif; 
        }

        /* 2. MAIN CONTAINER & ANIMATION */
        .header-container { 
            position: relative; 
            width: 100%; 
            height: 100vh; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            overflow: hidden; 
        }

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
            background: linear-gradient(to bottom,
                #1e7d32 33.33%,
                #ffffff 33.33% 66.66%,
                #c62828 66.66%); 
            transform: translateX(-100%); 
        }

        .header-container::after { 
            bottom: 0; 
            background: linear-gradient(to bottom,
                #c62828 33.33%,
                #ffffff 33.33% 66.66%,
                #1e7d32 66.66%); 
            transform: translateX(100%); 
        }

        @keyframes slideInOnce { 
            to { transform: translateX(0); } 
        }

        /* 3. BACKGROUND & OVERLAY */
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
            filter: blur(6px); 
            transform: scale(1.1); 
        }

        .overlay { 
            position: absolute; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            z-index: 2; 
            background: rgba(255, 255, 255, 0.1); 
        }

        /* 4. SIGNUP CARD DESIGN */
        .signup-card { 
            position: relative; 
            z-index: 10; 
            width: 90%; 
            max-width: 420px; 
            max-height: calc(100vh - 100px); 
            padding: 30px; 
            background: rgba(255, 255, 255, 0.95); 
            backdrop-filter: blur(10px); 
            border-radius: 25px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.3); 
            text-align: center; 
            overflow-y: auto; 
            border-top: 5px solid #1e7d32; 
        }

        /* 5. SWEETALERT THEME */
        .neu-curved-popup {
            border-radius: 20px !important;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1) !important;
            border-top: 6px solid #1e7d32 !important;
        }

        .neu-curved-btn {
            border-radius: 12px !important;
            padding: 12px 30px !important;
            font-weight: 700 !important;
            font-family: 'Inter', sans-serif !important;
        }

        /* 6. LOGO & TITLE */
        .logo-container { 
            width: 80px; 
            height: 80px; 
            margin: 0 auto 10px; 
            background: white; 
            border-radius: 50%; 
            border: 3px solid #fff; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); 
            overflow: hidden; 
        }

        .logo-image { 
            width: 100%; 
            height: 100%; 
            object-fit: cover; 
        }

        h2 { 
            margin-bottom: 10px; 
            color: #333; 
            font-size: 20px; 
        }

        /* 7. FORM LAYOUT */
        .name-row { 
            display: flex; 
            gap: 10px; 
            margin-bottom: 10px; 
        }

        .form-group { 
            margin-bottom: 10px; 
            position: relative; 
        }

        input { 
            width: 100%; 
            padding: 10px 15px; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            font-size: 14px; 
            outline: none; 
        }

        input:focus { 
            border-color: #1e7d32; 
            border-width: 2px; 
        }

        /* 8. PASSWORD VISIBILITY */
        .password-wrapper { 
            position: relative; 
            text-align: left; 
        }

        .input-with-eye { 
            position: relative; 
            width: 100%; 
        }

        .eye-icon { 
            position: absolute; 
            right: 12px; 
            top: 50%; 
            transform: translateY(-50%); 
            cursor: pointer; 
            width: 20px; 
            color: #666; 
            z-index: 5; 
            display: flex; 
            align-items: center; 
        }

        /* 9. PASSWORD REQUIREMENTS */
        .password-requirements { 
            text-align: left; 
            background: #f9f9f9; 
            padding: 10px; 
            border-radius: 8px; 
            margin-top: 5px; 
            font-size: 12px; 
            display: none; 
        }

        .requirement { 
            color: #888; 
            display: block; 
            margin-bottom: 2px; 
        }

        .requirement.valid { 
            color: #1e7d32; 
            font-weight: bold; 
        }

        .requirement.valid::before { 
            content: "✔ "; 
        }

        .requirement.invalid::before { 
            content: "✖ "; 
            color: #c62828; 
        }

        /* 10. BUTTONS & LINKS */
        .btn-signup { 
            width: 100%; 
            padding: 12px; 
            background: #1e7d32; 
            color: white; 
            border: none; 
            border-radius: 8px; 
            font-weight: bold; 
            cursor: pointer; 
            font-size: 15px; 
            transition: 0.3s; 
        }

        .btn-signup:disabled { 
            background: #888; 
            opacity: 0.6; 
            cursor: not-allowed; 
        }

        .google-wrapper { 
            margin-top: 15px; 
            display: flex; 
            justify-content: center; 
        }

        .login-link { 
            margin-top: 15px; 
            font-size: 13px; 
        }

        .login-link a { 
            color: #1e7d32; 
            font-weight: bold; 
            text-decoration: none; 
        }
        .divider { margin: 15px 0; color: #999; font-size: 12px; display: flex; align-items: center; }
        .divider::before, .divider::after { content: ""; flex: 1; height: 1px; background: #ddd; margin: 0 10px; }

        /* 11. PRIVACY CHECKBOX */
        .privacy-row { 
            display: flex; 
            align-items: flex-start; 
            gap: 8px; 
            margin: 10px 0; 
            font-size: 11px; 
            text-align: left; 
        }

        .privacy-row input { 
            width: auto; 
            margin-top: 2px; 
        }

        .privacy-row a { 
            color: #c62828; 
            font-weight: bold; 
            cursor: pointer; 
            text-decoration: underline; 
        }

        /* 12. MODAL UI */
        .modal-overlay {
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%;
            background: rgba(0, 0, 0, 0.4); 
            display: none;
            justify-content: center; 
            align-items: center; 
            z-index: 1000;
        }

        .modal-content {
            background: #fff; 
            width: 90%; 
            max-width: 500px;
            padding: 30px; 
            border-radius: 20px; 
            position: relative;
            text-align: left; 
            line-height: 1.6; 
            font-size: 14px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .modal-content h3 { 
            margin-bottom: 15px; 
            color: #333; 
            border-bottom: 2px solid #1e7d32; 
            padding-bottom: 5px; 
        }

        .modal-content p { 
            margin-bottom: 12px; 
            color: #555; 
        }

        .close-modal { 
            position: absolute; 
            top: 15px; 
            right: 20px; 
            cursor: pointer; 
            font-weight: bold; 
            font-size: 20px; 
            color: #888; 
        }

        /* 13. RESPONSIVE QUERIES */
        @media (max-width: 768px) {
            .signup-card { max-width: 380px; padding: 25px; }
        }

        @media (max-width: 480px) {
            .header-container { padding: 50px 15px; }
            .header-container::before, .header-container::after { height: 20px; }
            .signup-card { padding: 20px 15px; border-radius: 20px; }
            .name-row { flex-direction: column; gap: 12px; }
            h2 { font-size: 20px; }
            .logo-container { width: 70px; height: 70px; }
            .privacy-row { font-size: 12px; }
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

        <div class="signup-card">
            
            <div class="logo-container">
                <img src="neu-logo.jpg" 
                     alt="NEU Logo" 
                     class="logo-image">
            </div>

            <h2>Sign Up</h2>

            <form action="" method="POST" id="registrationForm" onsubmit="return handleRegistration(event)">
                
                <div class="name-row">
                    <input type="text" name="first_name" placeholder="First Name" required>
                    <input type="text" name="last_name" placeholder="Last Name" required>
                </div>

                <div class="form-group">
                    <input type="email" name="email" placeholder="Email Address" required>
                </div>

                <div id="passwordSection" class="password-wrapper form-group">
                    <div class="input-with-eye">
                        <input type="password" name="password" id="passwordField" placeholder="Password" required autocomplete="new-password">
                        <div class="eye-icon" onclick="togglePassword(event)" id="eyeBtn">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </div>

                    <div id="passwordChecklist" class="password-requirements">
                        <p style="margin-bottom: 5px; font-weight: bold;">Password must:</p>
                        <span id="length" class="requirement">Be at least 8 characters long</span>
                        <span id="upper" class="requirement">Contain at least one uppercase letter (A-Z)</span>
                        <span id="lower" class="requirement">Contain at least one lowercase letter (a-z)</span>
                        <span id="number" class="requirement">Contain at least one number (0-9)</span>
                        <span id="special" class="requirement">Contain at least one special character</span>
                    </div>
                </div>

                <div class="privacy-row">
                    <input type="checkbox" id="privacy" name="privacy" required>
                    <span>
                        I agree to the 
                        <a href="javascript:void(0)" onclick="openModal('privacyModal')">Data Privacy Policy</a>
                        and 
                        <a href="javascript:void(0)" onclick="openModal('termsModal')">Terms and Conditions</a>
                    </span>
                </div>

                <button type="submit" name="signup" class="btn-signup" disabled>
                    CREATE ACCOUNT
                </button>
            </form>

            <div class="divider">OR</div>

            <div class="google-wrapper">
                <div id="g_id_onload"
                     data-client_id="<?php echo e(cfg('google_client_id')); ?>"
                     data-callback="handleCredentialResponse">
                </div>
                <div class="g_id_signin" data-type="standard"></div>
            </div>

            <div class="login-link">
                Already have an account? <a href="login.php">Log in</a>
            </div>
        </div>
    </div>

    <div id="privacyModal" class="modal-overlay">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('privacyModal')">×</span>
            <h3>Data Privacy Policy</h3>
            <p>This system collects and processes personal information such as full name, institutional email address, student or employee ID, and other relevant academic details for registration, authentication, and academic services.</p>
            <p>All collected data will be handled with strict confidentiality and will only be used for legitimate academic and administrative purposes.</p>
            <p>The system complies with the Data Privacy Act of 2012 (Republic Act No. 10173). Personal information will not be shared with unauthorized individuals or organizations.</p>
            <p>By registering, you consent to the collection and processing of your personal information in accordance with school policies and applicable privacy laws.</p>
        </div>
    </div>

    <div id="termsModal" class="modal-overlay">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('termsModal')">×</span>
            <h3>Terms and Conditions</h3>
            <p>By creating an account, you agree to use the system responsibly and only for academic and authorized school-related activities.</p>
            <p>Users are responsible for maintaining the confidentiality of their login credentials.</p>
            <p>Sharing of accounts is strictly prohibited. Any misuse, unauthorized access, or violation of school policies may result in account suspension, disciplinary action, or termination of access.</p>
            <p>The school reserves the right to update system policies when necessary.</p>
        </div>
    </div>

</body>

<script>
    /* 1. DOM ELEMENTS */
    const privacyCheckbox  = document.getElementById("privacy");
    const pwdField         = document.getElementById("passwordField");
    const checklist        = document.getElementById("passwordChecklist");
    const passwordSection  = document.getElementById("passwordSection");
    const signupBtn        = document.querySelector(".btn-signup");

    /* 2. NAVIGATION & LOAD HANDLERS */
    if (performance.navigation.type === performance.navigation.TYPE_RELOAD) {
        window.location.href = "intro2.php";
    }

    window.onload = function() {
        const registrationForm = document.getElementById("registrationForm");
        if (registrationForm) {
            registrationForm.reset();
        }
        if (signupBtn) {
            signupBtn.disabled = true;
        }
    };

    /* 3. VALIDATION LOGIC */
    function getRules(val) {
        return {
            length  : val.length >= 8,
            upper   : /[A-Z]/.test(val),
            lower   : /[a-z]/.test(val),
            number  : /[0-9]/.test(val),
            special : /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(val)
        };
    }

    function checkValidity() {
        const rules = getRules(pwdField.value);
        const allPassed = Object.values(rules).every(r => r === true);

        if (signupBtn) {
            signupBtn.disabled = !(allPassed && privacyCheckbox.checked);
        }
        return allPassed;
    }

    /* 4. PASSWORD FIELD EVENTS */
    if (pwdField) {
        pwdField.addEventListener("input", () => {
            const val = pwdField.value;
            checklist.style.display = "block";
            const rules = getRules(val);

            Object.keys(rules).forEach(key => {
                const el = document.getElementById(key);
                if (el) {
                    el.className = rules[key]
                        ? "requirement valid"
                        : "requirement invalid";
                }
            });
            checkValidity();
        });

        pwdField.addEventListener("focus", () => {
            checklist.style.display = "block";
        });
    }

    /* 5. PRIVACY & UI INTERACTION */
    if (privacyCheckbox) {
        privacyCheckbox.addEventListener("change", checkValidity);
    }

    document.addEventListener("mousedown", (e) => {
        if (passwordSection && !passwordSection.contains(e.target)) {
            checklist.style.display = "none";
        }
    });

    /* 6. GOOGLE AUTHENTICATION */
    function handleCredentialResponse(response) {
        const formData = new FormData();
        formData.append("id_token", response.credential);
        formData.append("mode", "signup");
        formData.append("agreed", document.getElementById("privacy").checked);

        fetch("google_handler.php", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                window.location.href = "form.php";
            } 
            else if (data.status === "needs_agreement") {
                alert("This is your first time. Please agree to the Data Privacy Policy first.");
                document.getElementById("privacy").focus();
            } 
            else if (data.status === "not_found") {
                alert("Account not found. Please sign up first.");
            }
            else {
                alert("Error: " + data.message);
            }
        })
        .catch(err => {
            console.error("Fetch Error:", err);
            alert("Connection failed. Please check if your server is running or if the file path is correct.");
        });
    }

    /* 7. MODAL FUNCTIONS */
    function openModal(id) {
        document.getElementById(id).style.display = "flex";
    }

    function closeModal(id) {
        document.getElementById(id).style.display = "none";
    }

    /* 8. FORM SUBMISSION */
    function handleRegistration(event) {
        if (!checkValidity()) {
            event.preventDefault();
            alert("Please meet all password requirements and agree to privacy policy.");
            return false;
        }
    }

    /* 9. PASSWORD VISIBILITY TOGGLE */
    function togglePassword(event) {
        event.preventDefault();
        const isPwd = pwdField.type === "password";
        pwdField.type = isPwd ? "text" : "password";

        document.getElementById("eyeBtn").innerHTML = isPwd
            ? `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>`
            : `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395 M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498 M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
    }
</script>
</body>
</html>
