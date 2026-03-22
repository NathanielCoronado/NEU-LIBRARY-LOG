<?php
/** --- 1. INITIALIZATION & DATA DECODING --- **/
session_start();
include 'db_conn.php';

if (isset($_GET['token'])) {
    $decoded_data = base64_decode($_GET['token']);
    $token_data = json_decode($decoded_data, true);

    if (!$token_data) {
        header("Location: LOGIN.PHP?error=invalid_token");
        exit();
    }

    $email      = mysqli_real_escape_string($conn, $token_data['em']);
    $first_name = mysqli_real_escape_string($conn, $token_data['fn']);
    $last_name  = mysqli_real_escape_string($conn, $token_data['ln']);
    $password   = $token_data['pw'] ?? '';

    /** --- 2. VERIFICATION STATUS CHECK --- **/
    $stmt = $conn->prepare("SELECT is_verified FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && $user['is_verified'] == 1) {
        header("Location: LOGIN.PHP?status=link_expired");
        exit();
    }

    /** --- 3. USER RECORD SYNCHRONIZATION --- **/
    $email_hash = md5(strtolower(trim($email)));
    $smart_photo = "https://www.gravatar.com/avatar/" . $email_hash . "?s=500&d=identicon";

    if ($user) {
        $update_stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, profile_pic = ? WHERE email = ?");
        $update_stmt->bind_param("ssss", $first_name, $last_name, $smart_photo, $email);
        $update_stmt->execute();
    } else {
        $insert_stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, profile_pic, is_verified) VALUES (?, ?, ?, ?, ?, 0)");
        $insert_stmt->bind_param("sssss", $first_name, $last_name, $email, $password, $smart_photo);
        $insert_stmt->execute();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Confirmed</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /** --- CSS: THEME & GLOBAL --- **/
        :root { 
            --neu-gradient: linear-gradient(135deg, #004d40 0%, #00796b 100%);
            --neu-green: #004d40; 
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body, html { 
            width: 100%; height: 100%; overflow: hidden; 
            background-color: #fff; 
            font-family: 'Inter', 'Segoe UI', sans-serif; 
        }

        /** --- CSS: PAGE BORDERS --- **/
        .page-border {
            position: fixed; left: 0; width: 100%; height: 36px; z-index: 9999;
            animation: slideInOnce 1.2s ease-out forwards;
        }
        .border-top { 
            top: 0; 
            background: linear-gradient(to bottom, #1e7d32 33.33%, #ffffff 33.33% 66.66%, #c62828 66.66%); 
            transform: translateX(-100%);
        }
        .border-bottom { 
            bottom: 0; 
            background: linear-gradient(to top, #1e7d32 33.33%, #ffffff 33.33% 66.66%, #c62828 66.66%); 
            transform: translateX(100%);
        }
        @keyframes slideInOnce { to { transform: translateX(0); } }

        /** --- CSS: LAYOUT & BACKGROUND --- **/
        .header-container {
            position: relative; width: 100%; height: 100vh;
            display: flex; justify-content: center; align-items: center;
        }
        .background-image {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 1;
            background-image: url('https://github.com/NathanielCoronado/NEU-LIBRARY-LOG/blob/main/NEU%20LIBRARY.jpg?raw=true');
            background-size: cover; background-position: center;
            filter: blur(8px) brightness(1.1);
            transform: scale(1.1);
        }
        .overlay {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            z-index: 2; background: rgba(0,0,0,0.2);
        }

        /** --- CSS: POPUP UI --- **/
        .neu-curved-popup { 
            border-radius: 25px !important; 
            padding: 30px !important;
            background: rgba(255, 255, 255, 0.98) !important;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3) !important;
            border-top: 6px solid #00796b !important;
        }
        .swal2-icon.swal2-success { border-color: #00796b !important; }
        .swal2-title-green {
            color: #1a202c !important;
            font-weight: 800 !important;
            font-size: 24px !important;
        }

        /** --- CSS: BUTTON STATES --- **/
        .neu-curved-btn { 
            border-radius: 12px !important; 
            padding: 16px 40px !important; 
            font-weight: 700 !important; 
            background: var(--neu-gradient) !important;
            color: white !important;
            text-transform: uppercase;
            border: none !important; 
            outline: none !important; 
            box-shadow: none !important;
            transition: all 0.2s ease !important;
        }
        .neu-curved-btn:active {
            border: 3px solid #808080 !important; 
            box-shadow: 0 0 8px rgba(128, 128, 128, 0.5) !important;
            transform: scale(0.98);
        }

        /** --- CSS: RESPONSIVE QUERIES --- **/
        @media (max-width: 768px) {
            .page-border { height: 24px; }
            .border-bottom { display: none; }
            .neu-curved-popup { width: 90% !important; }
            .neu-curved-btn { width: 100% !important; }
        }
        @media (max-width: 480px) {
            .background-image { filter: blur(6px) brightness(1.1); }
        }
    </style>
</head>
<body>

    <div class="page-border border-top"></div>
    <div class="page-border border-bottom"></div>

    <div class="header-container">
        <div class="background-image"></div>
        <div class="overlay"></div>
    </div>

    <script>
        Swal.fire({
            title: 'Email Confirmed!',
            html: `
                <div style="font-family: 'Inter', sans-serif; font-size: 15px; color: #4a5568; line-height: 1.6; margin-top: 10px;">
                    Greetings, <strong style="color: #00796b;"><?php echo htmlspecialchars($first_name); ?></strong>!<br><br>
                    Verification complete. You may now synchronize your profile via Google to access the library system.
                </div>
            `,
            icon: 'success',
            iconColor: '#00796b',
            confirmButtonText: 'PROCEED TO LOGIN',
            allowOutsideClick: false,
            customClass: { 
                popup: 'neu-curved-popup', 
                confirmButton: 'neu-curved-btn',
                title: 'swal2-title-green'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'LOGIN.PHP?verify_success=true&email=<?php echo urlencode($email); ?>';
            }
        });
    </script>
</body>
</html>

<?php 
    exit(); 
} else {
    /** --- 4. FALLBACK REDIRECTION --- **/
    header("Location: LOGIN.PHP");
    exit();
}
?>