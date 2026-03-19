<?php
session_start();
date_default_timezone_set('Asia/Manila');
require_once 'db_conn.php'; 

/**
 * 1. SECURITY GATEKEEPER
 */
if (!isset($_SESSION['active_id']) || !isset($_SESSION['email'])) {
    header("Location: INTRO1.PHP");
    exit();
}

/**
 * 2. ADMIN DELETION CHECK
 */
$current_user_email = $_SESSION['email'];
$stmt_check = $conn->prepare("SELECT email FROM users WHERE email = ?");
$stmt_check->bind_param("s", $current_user_email);
$stmt_check->execute();
if ($stmt_check->get_result()->num_rows === 0) {
    session_unset(); session_destroy();
    echo "<script>localStorage.setItem('account_deleted', 'true'); window.location.href = 'INTRO1.PHP';</script>";
    exit();
}

/**
 * 3. LOGOUT & LOGGING LOGIC
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'logout_user') {
    if (isset($_SESSION['active_id'])) {
        $session_db_id = $_SESSION['active_id'];
        $stmt_get = $conn->prepare("SELECT * FROM active_sessions WHERE id = ?");
        $stmt_get->bind_param("i", $session_db_id);
        $stmt_get->execute();
        $data = $stmt_get->get_result()->fetch_assoc();

        if ($data) {
            $time_out = date("h:i:s A"); 
            $sql_log = "INSERT INTO library_logs (user_type, id_number, contact, first_name, middle_name, last_name, suffix, course, time_in, time_out, reason, others_detail, date_visited, email) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_log = $conn->prepare($sql_log);
            $stmt_log->bind_param("ssssssssssssss", $data['user_type'], $data['id_number'], $data['contact'], $data['first_name'], $data['middle_name'], $data['last_name'], $data['suffix'], $data['course'], $data['time_in'], $time_out, $data['reason'], $data['others_detail'], $data['date_visited'], $data['email']);
            
            if ($stmt_log->execute()) {
                $stmt_del = $conn->prepare("DELETE FROM active_sessions WHERE id = ?");
                $stmt_del->bind_param("i", $session_db_id);
                $stmt_del->execute();
            }
        }
        
        session_unset(); session_destroy();
        // Removed logout_msg storage to skip success alert
        echo "<script>window.location.href = 'INTRO1.PHP';</script>";
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NEU Library Welcome</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Inter:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* FONTS & RESET */
        @font-face { font-family: 'ITC Benguiat Std'; src: url('fonts/ITCBenguiatStd-Bold.woff2') format('woff2'); font-weight: bold; }
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body { 
            font-family: 'Inter', sans-serif; 
            height: 100vh; width: 100vw;
            display: flex; flex-direction: column;
            overflow: hidden; 
            background-color: #004aad;
        }

        /* TRICOLOR BORDERS */
        body::before, body::after {
            content: ""; width: 100%; height: 36px; z-index: 100;
            position: fixed; left: 0; animation: slideInOnce 1.2s ease-out forwards;
        }
        body::before { top: 0; background: linear-gradient(to bottom, #1e7d32 33.33%, #ffffff 33.33% 66.66%, #c62828 66.66%); transform: translateX(-100%); }
        body::after { bottom: 0; background: linear-gradient(to bottom, #c62828 33.33%, #ffffff 33.33% 66.66%, #1e7d32 66.66%); transform: translateX(100%); }
        @keyframes slideInOnce { to { transform: translateX(0); } }

        /* ALIGNMENT GRID */
        .main-wrapper { 
            flex-grow: 1; display: grid;
            grid-template-columns: 1fr 2fr 1fr; grid-template-rows: 1fr 1fr;
            align-items: center; padding: 60px 80px; z-index: 10;
        }

        .logo {
            width: 180px; height: 180px;
            background: white url('https://github.com/NathanielCoronado/NEU-LIBRARY-LOG/blob/main/NEU%20LOGO.jpg?raw=true') no-repeat center;
            background-size: 100%; border-radius: 100%; border: 6px solid #fff;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }

        .out-button {
            background-color: #c62828; color: white; padding: 10px 35px; 
            border: none; font-weight: bold; font-size: 1.5rem; 
            cursor: pointer; border-radius: 9px; 
            transition: 0.3s; text-transform: uppercase; 
            box-shadow: 0 8px 25px rgba(0,0,0,0.5);
        }
        .out-button:hover { background-color: #ffffff; color: #c62828; transform: translateY(-3px); }

        .welcome-content { grid-column: 2; grid-row: 1 / span 2; text-align: center; }
        .welcome-text {
            font-family: 'ITC Benguiat Std', serif; color: #ffcc00; 
            font-size: clamp(2.0rem, 6vw, 6rem); font-weight: bold; 
            text-transform: uppercase; line-height: 0.85;
            text-shadow: 5px 5px 0px rgba(0, 0, 0, 0.9);
        }

        .live-clock { color: white; font-family: 'Cinzel', serif; font-size: 2.0rem; font-weight: bold; text-shadow: 2px 2px 10px rgba(0,0,0,0.8); }

        /* POSITIONS */
        .logo-box { grid-column: 1; grid-row: 1; align-self: start; justify-self: start; }
        .clock-box { grid-column: 1; grid-row: 2; align-self: end; justify-self: start; }
        .btn-box { grid-column: 3; grid-row: 2; align-self: end; justify-self: end; }

        /* --- SWEETALERT DESIGN (Red Top with Curve) --- */
        .neu-swal-popup {
            border-radius: 20px 20px 0px 0px !important; 
            border-top: 10px solid #c62828 !important; 
            border-left: 1px solid #ddd; border-right: 1px solid #ddd; border-bottom: 1px solid #ddd;
            padding-bottom: 20px;
        }
        .neu-swal-btn { border-radius: 5px !important; font-weight: bold; text-transform: uppercase; padding: 10px 25px !important; }
    </style>
</head>
<body>

    <div class="main-wrapper">
        <div class="logo-box"><div class="logo"></div></div>
        
        <div class="welcome-content">
            <h1 class="welcome-text">WELCOME TO<br>NEU LIBRARY!</h1>
            <p style="color: white; font-family: 'Inter'; letter-spacing: 12px; border-top: 2px solid white; margin-top: 15px; padding-top: 15px; font-weight: bold;">S.Y. 2025-2026</p>
        </div>

        <div class="clock-box"><div id="clock" class="live-clock">00:00:00 AM</div></div>

        <div class="btn-box">
            <button type="button" class="out-button" onclick="confirmLogout()">Out</button>
            <form method="POST" action="" id="logoutForm" style="display:none;">
                <input type="hidden" name="action" value="logout_user">
            </form>
        </div>
    </div>

<script>
    function updateClock() {
        const el = document.getElementById('clock');
        if(el) el.textContent = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
    }
    setInterval(updateClock, 1000); updateClock();

    const swalConfig = {
        customClass: {
            popup: 'neu-swal-popup',
            confirmButton: 'neu-swal-btn',
            cancelButton: 'neu-swal-btn'
        }
    };

    // Logout Confirmation via "Out" Button
    function confirmLogout() {
        Swal.fire({
            ...swalConfig,
            title: 'CONFIRM OUT',
            text: 'Are you sure you want to end your library session?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#c62828',
            cancelButtonColor: '#666',
            confirmButtonText: 'YES, LOGOUT',
            cancelButtonText: 'CANCEL'
        }).then((result) => {
            if (result.isConfirmed) document.getElementById('logoutForm').submit();
        });
    }

    // BACK BUTTON LOGIC
    history.pushState(null, null, location.href);
    window.onpopstate = function () {
        Swal.fire({
            ...swalConfig,
            title: 'END SESSION?',
            text: 'Going back will automatically record your time out. Proceed?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#c62828',
            cancelButtonColor: '#ffcc00',
            confirmButtonText: 'Yes, log me out',
            cancelButtonText: 'Stay here'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('logoutForm').submit();
            } else {
                history.pushState(null, null, location.href);
            }
        });
    };

    window.onload = function() {
        const isDeleted = localStorage.getItem('account_deleted');

        // Only show alert if admin deleted the account
        if (isDeleted) {
            Swal.fire({ 
                ...swalConfig,
                title: 'ACCOUNT REMOVED', 
                text: 'Your account was deleted by the administrator.', 
                icon: 'error', 
                confirmButtonColor: '#c62828'
            }).then(() => { localStorage.clear(); window.location.replace('INTRO1.PHP'); });
        }
    };
</script>
</body>
</html>