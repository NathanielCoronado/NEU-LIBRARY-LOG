<?php
/**
 * DEPENDENCIES & CONFIGURATION
 */
include 'db_conn.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

/**
 * SIGNUP FORM PROCESSING
 */
if (isset($_POST['signup'])) {

    // Validate privacy agreement
    if (!isset($_POST['privacy'])) {
        echo "<script>
                alert('You must agree to the Data Privacy Policy to register.');
                window.history.back();
              </script>";
        exit();
    }

    // Sanitize and hash inputs
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name  = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email      = mysqli_real_escape_string($conn, $_POST['email']);
    $password   = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check for existing email
    $checkEmail = "SELECT id FROM users WHERE email='$email' LIMIT 1";
    $result = $conn->query($checkEmail);

    if ($result->num_rows > 0) {
        echo "<script>
                alert('This email address is already registered.');
                window.location.href='login.php';
              </script>";
        exit();
    } 

    /**
     * AVATAR & TOKEN GENERATION
     */
    // Generate profile picture URL
    $colors = ['1e7d32', '2e5a88', 'a02c2c', '6a1b9a', 'ef6c00', '00838f', '4527a0'];
    $random_bg = $colors[array_rand($colors)];
    $email_hash = md5(strtolower(trim($email)));

    $initials_url = "https://ui-avatars.com/api/?name=" . urlencode($first_name . " " . $last_name) . "&background=$random_bg&color=fff&size=512&bold=true";
    $image_address = "https://www.gravatar.com/avatar/$email_hash?s=512&d=" . urlencode($initials_url);

    // Create encoded verification token
    $timestamp = time();
    $userData = base64_encode(json_encode([
        'fn'   => $first_name,
        'ln'   => $last_name,
        'em'   => $email,
        'pw'   => $password,
        'pic'  => $image_address,
        'time' => $timestamp
    ]));

    // Construct verification link
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $currentDir = str_replace('\\', '/', dirname($_SERVER['REQUEST_URI']));
    $verifyLink = $protocol . "://" . $host . rtrim($currentDir, '/') . "/verify.php?token=$userData";

    /**
     * EMAIL DISPATCH (PHPMailer)
     */
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'nathaniel.coronado@neu.edu.ph';
        $mail->Password   = 'fqvl bscb aibo bait'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // SSL fix for local development
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true
            ]
        ];

        // Recipients and headers
        $mail->setFrom('noreply@neu.edu.ph', 'NEU Library');
        $mail->addAddress($email, $first_name);
        $mail->isHTML(true);
        $mail->Subject = 'Account Activation - NEU Library';

        // HTML Content
        $mail->Body = "
        <html>
        <body style=\"margin: 0; padding: 0; font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f7f7; color: #333;\">
            <div style='padding: 40px 20px;'>
                <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 1px solid #e1e8e8;'>
                    
                    <div style='background: linear-gradient(135deg, #004d40 0%, #00796b 100%); padding: 50px 20px; text-align: center;'>
                        <img src='https://github.com/NathanielCoronado/NEU-LIBRARY-LOG/blob/main/NEU%20LOGO.jpg?raw=true' 
                             alt='NEU Logo' 
                             style='width: 90px; height: 90px; border-radius: 50%; border: 4px solid rgba(255,255,255,0.2); background: #ffffff; object-fit: cover;'>
                        <h1 style='color: #ffffff; margin: 15px 0 0 0; font-size: 24px; font-weight: 700; letter-spacing: 1.5px;'>NEU LIBRARY</h1>
                        <p style='color: #b2dfdb; margin: 5px 0 0 0; font-size: 12px; text-transform: uppercase; letter-spacing: 3px;'>Visitor Attendance System</p>
                    </div>

                    <div style='padding: 45px 35px; text-align: center;'>
                        <div style='display: inline-block; padding: 7px 18px; background-color: #e0f2f1; color: #00796b; border-radius: 50px; font-size: 11px; font-weight: 800; margin-bottom: 25px; text-transform: uppercase;'>
                            Account Verification
                        </div>
                        
                        <h2 style='color: #263238; margin: 0 0 15px 0; font-size: 26px;'>Greetings, $first_name!</h2>
                        
                        <p style='color: #546e7a; line-height: 1.8; margin: 0 0 35px 0; font-size: 16px;'>
                            To finalize your access to the <strong>NEU Library Visitor Log</strong>, we need to verify your identity. 
                            Please click the button below to secure your account.
                        </p>
                        
                        <a href='$verifyLink' style='background: linear-gradient(to right, #00796b, #004d40); color: #ffffff; padding: 20px 45px; text-decoration: none; border-radius: 10px; font-weight: bold; font-size: 16px; display: inline-block;'>
                            VERIFY MY PROFILE
                        </a>
                        
                        <p style='color: #90a4ae; font-size: 13px; margin-top: 40px; font-style: italic;'>
                            If you did not request this registration, you can safely ignore this email.
                        </p>
                    </div>

                    <div style='padding: 35px; text-align: center; background-color: #fafafa; border-top: 1px solid #f0f0f0;'>
                        <p style='color: #78909c; font-size: 12px; margin: 0;'>
                            <strong style='color: #455a64;'>&copy; " . date("Y") . " New Era University Library System</strong><br>
                            #9 Central Avenue, New Era, Quezon City, 1107 Metro Manila, Philippines<br><br>
                            <span style='color: #455a64; font-size: 10px; text-transform: uppercase;'>This is an automated message. Please do not reply.</span>
                        </p>
                    </div>
                </div>
            </div>
        </body>
        </html>";

        $mail->send();

        echo "<script>
                alert('Activation link sent to $email. Please check your inbox.');
                window.location.href='login.php';
              </script>";
        exit();

    } catch (Exception $e) {
        echo "<script>
                alert('Mail Error: " . addslashes($mail->ErrorInfo) . "');
                window.history.back();
              </script>";
        exit();
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
            background-image: url('https://github.com/NathanielCoronado/NEU-LIBRARY-LOG/blob/main/NEU%20LIBRARY.jpg?raw=true');
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
</head>
<body>
    <div class="header-container">
        <div class="background-image"></div>
        <div class="overlay"></div>

        <div class="signup-card">
            
            <div class="logo-container">
                <img src="https://github.com/NathanielCoronado/NEU-LIBRARY-LOG/blob/main/NEU%20LOGO.jpg?raw=true" 
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
                     data-client_id="814210630472-5tdooa2at4t3jph9djk91kb4eandpbcm.apps.googleusercontent.com"
                     data-callback="handleCredentialResponse">
                </div>
                <div class="g_id_signin" data-type="standard"></div>
            </div>

            <div class="login-link">
                Already have an account? <a href="LOGIN.PHP">Log in</a>
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
        window.location.href = "INTRO2.PHP";
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