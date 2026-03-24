<?php
/**
 * 1. INITIALIZATION & DATABASE CONNECTION
 */
include 'db_conn.php';

$valid_token = false;
$email_from_url = "";
$token_from_url = "";

/**
 * 2. TOKEN & EMAIL VERIFICATION
 */
if (isset($_GET['token']) && isset($_GET['email'])) {
    $email_from_url = mysqli_real_escape_string($conn, $_GET['email']);
    $token_from_url = mysqli_real_escape_string($conn, $_GET['token']);

    $checkToken = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email_from_url' AND reset_token = '$token_from_url' AND reset_token IS NOT NULL AND token_expiry > NOW()");

    if (mysqli_num_rows($checkToken) > 0) {
        $valid_token = true;
    }
}

/**
 * 3. PASSWORD UPDATE PROCESSING
 */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_password'])) {
    $new_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    $updateQuery = "UPDATE users SET password = '$new_password', reset_token = NULL, token_expiry = NULL WHERE email = '$email'";

    if (mysqli_query($conn, $updateQuery)) {
        echo "<script>alert('Password successfully updated!'); window.location='login.php';</script>";
        exit();
    } else {
        echo "<script>alert('Error updating password. Please try again.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>

    <style>
        /* --- GLOBAL RESET & BASE --- */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body, html { width: 100%; height: 100%; overflow: hidden; background-color: #f0f4f4; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        /* --- LAYOUT & BORDERS --- */
        .header-container { position: relative; width: 100%; height: 100vh; display: flex; justify-content: center; align-items: center; overflow: hidden; }
        .header-container::before, .header-container::after { content: ""; position: absolute; left: 0; width: 100%; height: 36px; z-index: 100; animation: slideInOnce 1.2s ease-out forwards; }
        .header-container::before { top: 0; background: linear-gradient(to bottom, #00695c 33.33%, #ffffff 33.33% 66.66%, #c62828 66.66%); transform: translateX(-100%); }
        .header-container::after { bottom: 0; background: linear-gradient(to bottom, #c62828 33.33%, #ffffff 33.33% 66.66%, #00695c 66.66%); transform: translateX(100%); }

        /* --- BACKGROUND EFFECTS --- */
        .background-image { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 1; background-image: url('https://github.com/NathanielCoronado/NEU-LIBRARY-LOG/blob/main/NEU%20LIBRARY.jpg?raw=true'); background-size: cover; background-position: center; filter: blur(8px); transform: scale(1.1); }
        .overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 2; background: rgba(0,0,0,0.2); }

        /* --- CARD & TYPOGRAPHY --- */
        .reset-card { position: relative; z-index: 10; width: 90%; max-width: 420px; padding: 40px 30px; background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); border-radius: 25px; box-shadow: 0 15px 35px rgba(0,0,0,0.3); text-align: center; max-height: calc(100vh - 100px); overflow-y: auto; border-top: 5px solid #00695c; }
        .icon-container { width: 70px; height: 70px; margin: 0 auto 20px; background: #e0f2f1; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #00695c; }
        h2 { margin-bottom: 10px; color: #333; font-size: 24px; font-weight: 800; }
        .subtitle { font-size: 14px; color: #555; line-height: 1.5; margin-bottom: 25px; }

        /* --- FORM CONTROLS --- */
        .form-group { margin-bottom: 15px; position: relative; text-align: left; }
        label { display: block; font-size: 13px; font-weight: bold; color: #444; margin-bottom: 5px; margin-left: 5px; }
        input { width: 100%; padding: 14px 45px 14px 15px; border: 1px solid #cfd8dc; border-radius: 10px; font-size: 15px; outline: none; transition: 0.3s; }
        input:focus { border-color: #00695c; box-shadow: 0 0 8px rgba(0, 105, 92, 0.2); }
        .eye-icon { position: absolute; right: 12px; bottom: 12px; cursor: pointer; color: #777; z-index: 15; background: none; border: none; }
        
        /* --- VALIDATION UI --- */
        #psw-message { display: none; background: #fdfdfd; padding: 15px; margin-top: 10px; border-radius: 10px; border: 1px solid #e0e0e0; }
        #psw-message p { font-size: 12px; padding: 2px 0; display: flex; align-items: center; }
        .valid { color: #00695c; }
        .valid::before { content: "✔"; margin-right: 10px; }
        .invalid { color: #c62828; }
        .invalid::before { content: "✖"; margin-right: 10px; }

        /* --- BUTTONS --- */
        .btn-update { width: 100%; padding: 14px; background: #00695c; color: white; border: none; border-radius: 10px; font-weight: bold; cursor: pointer; font-size: 16px; transition: 0.3s; text-transform: uppercase; letter-spacing: 1px; margin-top: 10px; }
        .btn-update:hover { background: #004d40; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0, 77, 64, 0.3); }

        /* --- KEYFRAMES & RESPONSIVENESS --- */
        @keyframes slideInOnce { to { transform: translateX(0); } }
        @media (max-width: 480px) { .reset-card { padding: 30px 20px; width: 92%; } h2 { font-size: 20px; } .header-container::before, .header-container::after { height: 24px; } }
    </style>
</head>
<body>

<div class="header-container">
    <div class="background-image"></div>
    <div class="overlay"></div>

    <div class="reset-card">
        <?php if ($valid_token): ?>
            <div class="icon-container">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M11 1a2 2 0 0 0-2 2v4a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h5V3a3 3 0 0 1 6 0v4a.5.5 0 0 1-1 0V3a2 2 0 0 0-2-2zM3 8a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V9a1 1 0 0 0-1-1H3z"/>
                </svg>
            </div>
            <h2>Set New Password</h2>
            <p class="subtitle">Secure your account by creating a new password.</p>

            <form id="resetForm" method="POST" action="">
                <input type="hidden" name="email" value="<?php echo $email_from_url; ?>">
                
                <div class="form-group">
                    <label>New Password</label>
                    <div style="position: relative;">
                        <input type="password" name="password" id="newPassword" placeholder="Enter New Password" required autocomplete="off">
                        <span class="eye-icon" onclick="toggleVisibility('newPassword', this)">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="22">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </span>
                    </div>

                    <div id="psw-message">
                        <p style="font-weight: bold; margin-bottom: 5px;">Password must:</p>
                        <p id="length" class="invalid">Be at least 8 characters long</p>
                        <p id="capital" class="invalid">Contain at least one uppercase letter (A-Z)</p>
                        <p id="letter" class="invalid">Contain at least one lowercase letter (a-z)</p>
                        <p id="number" class="invalid">Contain at least one number (0-9)</p>
                        <p id="special" class="invalid">Contain at least one special character (!@#$%^&*)</p>
                    </div>
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <div style="position: relative;">
                        <input type="password" id="confirmPassword" placeholder="Re-enter New Password" required>
                        <span class="eye-icon" onclick="toggleVisibility('confirmPassword', this)">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="22">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </span>
                    </div>
                </div>

                <button type="submit" name="update_password" id="submitBtn" class="btn-update">Update Password</button>
            </form>

        <?php else: ?>
            <div class="icon-container" style="background: #f8d7da; color: #c62828;">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                    <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                </svg>
            </div>
            <h2>Link Expired</h2>
            <p class="subtitle">The password reset link is invalid or has already expired. Please request a new one.</p>
            <a href="forgot.php" class="btn-update" style="text-decoration: none; display: block;">Back to Forgot Password</a>
        <?php endif; ?>
    </div>
</div>

<script>
    /* --- ELEMENT SELECTORS --- */
    const myInput = document.getElementById("newPassword");
    const letter = document.getElementById("letter");
    const capital = document.getElementById("capital");
    const number = document.getElementById("number");
    const length = document.getElementById("length");
    const special = document.getElementById("special");
    const messageBox = document.getElementById("psw-message");

    /* --- PASSWORD VALIDATION --- */
    myInput.onfocus = function() { messageBox.style.display = "block"; }
    myInput.onblur = function() { messageBox.style.display = "none"; }

    myInput.onkeyup = function() {
        const val = myInput.value;
        val.match(/[a-z]/g) ? letter.classList.replace("invalid", "valid") : letter.classList.replace("valid", "invalid");
        val.match(/[A-Z]/g) ? capital.classList.replace("invalid", "valid") : capital.classList.replace("valid", "invalid");
        val.match(/[0-9]/g) ? number.classList.replace("invalid", "valid") : number.classList.replace("valid", "invalid");
        val.length >= 8 ? length.classList.replace("invalid", "valid") : length.classList.replace("valid", "invalid");
        val.match(/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/g) ? special.classList.replace("invalid", "valid") : special.classList.replace("valid", "invalid");
    }

    /* --- PASSWORD VISIBILITY --- */
    function toggleVisibility(inputId, el) {
        const input = document.getElementById(inputId);
        if (input.type === "password") {
            input.type = "text";
            el.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="22"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.036 12.322a1.012 1.012 0 010-.644C3.412 8.122 7.126 5 12 5c4.874 0 8.588 3.122 9.964 6.678a1.012 1.012 0 010 .644C20.588 15.878 16.874 19 12 19c-4.874 0-8.588-3.122-9.964-6.678z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>`;
        } else {
            input.type = "password";
            el.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="22"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>`;
        }
    }

    /* --- FORM SUBMISSION --- */
    document.getElementById('resetForm').addEventListener('submit', function(e) {
        const newPass = document.getElementById('newPassword').value;
        const confirmPass = document.getElementById('confirmPassword').value;
        if (newPass !== confirmPass) {
            e.preventDefault();
            alert('Passwords do not match.');
        } else {
            document.getElementById('submitBtn').innerText = "Updating...";
        }
    });
</script>

</body>
</html>
