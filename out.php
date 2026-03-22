<?php
/** --- 1. CORE SETUP --- **/
session_start();
date_default_timezone_set('Asia/Manila');

if (file_exists('db_conn.php')) {
    require_once 'db_conn.php';
} else {
    die("Error: Connection file 'db_conn.php' not found.");
}

/** --- 2. ACCESS CONTROL --- **/
if (!isset($_SESSION['active_id']) || !isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

$current_email = $_SESSION['email'];
$session_id = $_SESSION['active_id'];

/** --- 3. SECURITY VALIDATION --- **/
$stmt_u = $conn->prepare("SELECT id, is_blocked FROM users WHERE email = ?");
$stmt_u->bind_param("s", $current_email);
$stmt_u->execute();
$user_res = $stmt_u->get_result();

if ($user_res->num_rows === 0) {
    session_unset(); session_destroy();
    echo "<script>localStorage.setItem('admin_action', 'DELETED'); window.location.href = 'index.php';</script>";
    exit();
}

$user_info = $user_res->fetch_assoc();
if ($user_info['is_blocked'] == 1) {
    session_unset(); session_destroy();
    echo "<script>localStorage.setItem('admin_action', 'BLOCKED'); window.location.href = 'index.php';</script>";
    exit();
}

$stmt_s = $conn->prepare("SELECT id FROM active_sessions WHERE id = ?");
$stmt_s->bind_param("i", $session_id);
$stmt_s->execute();
if ($stmt_s->get_result()->num_rows === 0) {
    session_unset(); session_destroy();
    echo "<script>localStorage.setItem('admin_action', 'FORCED_OUT'); window.location.href = 'index.php';</script>";
    exit();
}

/** --- 4. LOGOUT PROCESSING --- **/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'logout_user') {
    $stmt_get = $conn->prepare("SELECT * FROM active_sessions WHERE id = ?");
    $stmt_get->bind_param("i", $session_id);
    $stmt_get->execute();
    $data = $stmt_get->get_result()->fetch_assoc();

    if ($data) {
        $time_out = date("h:i:s A"); 
        $status_completed = 'Completed';
        $sql_log = "INSERT INTO library_logs (user_type, id_number, contact, first_name, middle_name, last_name, email, suffix, course, time_in, time_out, reason, others_detail, date_visited, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_log = $conn->prepare($sql_log);
        $stmt_log->bind_param("sssssssssssssss", $data['user_type'], $data['id_number'], $data['contact'], $data['first_name'], $data['middle_name'], $data['last_name'], $data['email'], $data['suffix'], $data['course'], $data['time_in'], $time_out, $data['reason'], $data['others_detail'], $data['date_visited'], $status_completed);
        
        if ($stmt_log->execute()) {
            $stmt_del = $conn->prepare("DELETE FROM active_sessions WHERE id = ?");
            $stmt_del->bind_param("i", $session_id);
            $stmt_del->execute();
        }
    }
    session_unset(); session_destroy();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NEU Library - Active Session</title>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /** --- CSS: CORE & ANIMATIONS --- **/
        @font-face { font-family: 'ITC Benguiat Std'; src: url('fonts/ITCBenguiatStd-Bold.woff2') format('woff2'); font-weight: bold; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; height: 100vh; width: 100vw; display: flex; flex-direction: column; overflow: hidden; background-color: #004aad; }
        body::before, body::after { content: ""; width: 100%; height: 36px; z-index: 100; position: fixed; left: 0; animation: slideInOnce 1.2s ease-out forwards; }
        body::before { top: 0; background: linear-gradient(to bottom, #1e7d32 33.33%, #ffffff 33.33% 66.66%, #c62828 66.66%); transform: translateX(-100%); }
        body::after { bottom: 0; background: linear-gradient(to bottom, #c62828 33.33%, #ffffff 33.33% 66.66%, #1e7d32 66.66%); transform: translateX(100%); }
        @keyframes slideInOnce { to { transform: translateX(0); } }

        /** --- CSS: LAYOUT --- **/
        .main-wrapper { flex-grow: 1; display: grid; grid-template-columns: 1fr 2fr 1fr; grid-template-rows: 1fr 1fr; align-items: center; padding: 60px 80px; z-index: 10; }
        .logo { width: 180px; height: 180px; background: white url('https://github.com/NathanielCoronado/NEU-LIBRARY-LOG/blob/main/NEU%20LOGO.jpg?raw=true') no-repeat center; background-size: 100%; border-radius: 100%; border: 6px solid #fff; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .out-button { background-color: #c62828; color: white; padding: 12px 40px; border: none; font-weight: bold; font-size: 1.5rem; cursor: pointer; border-radius: 9px; transition: 0.3s; text-transform: uppercase; box-shadow: 0 8px 25px rgba(0,0,0,0.5); }
        .out-button:hover { background-color: #ffffff; color: #c62828; transform: translateY(-3px); }
        .welcome-content { grid-column: 2; grid-row: 1 / span 2; text-align: center; }
        .welcome-text { font-family: 'ITC Benguiat Std', serif; color: #ffcc00; font-size: clamp(2.0rem, 6vw, 6rem); font-weight: bold; text-transform: uppercase; line-height: 0.85; text-shadow: 5px 5px 0px rgba(0, 0, 0, 0.9); }
        .live-clock { color: white; font-family: 'Cinzel', serif; font-size: 2.2rem; font-weight: bold; text-shadow: 2px 2px 10px rgba(0,0,0,0.8); }
        .logo-box { grid-column: 1; grid-row: 1; align-self: start; justify-self: start; }
        .clock-box { grid-column: 1; grid-row: 2; align-self: end; justify-self: start; }
        .btn-box { grid-column: 3; grid-row: 2; align-self: end; justify-self: end; }

        /** --- CSS: MODALS --- **/
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.85); backdrop-filter: blur(8px); display: none; justify-content: center; align-items: center; z-index: 2000; }
        .modal-card { background: #fff; width: 450px; border-radius: 15px; overflow: hidden; box-shadow: 0 25px 50px rgba(0,0,0,0.5); animation: modalPop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); border-top: 12px solid #c62828; }
        @keyframes modalPop { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .modal-header { padding: 30px 25px 10px; text-align: center; }
        .modal-header h2 { font-family: 'ITC Benguiat Std', serif; color: #004aad; font-size: 1.6rem; text-transform: uppercase; }
        .modal-body { padding: 10px 40px 25px; text-align: center; color: #444; font-size: 1.15rem; }
        .modal-footer { padding: 0 35px 35px; display: flex; gap: 15px; }
        .btn-modal { flex: 1; padding: 14px; border: none; border-radius: 8px; font-weight: 700; font-family: 'Inter'; cursor: pointer; text-transform: uppercase; transition: 0.2s; }
        .btn-yes { background: #c62828; color: #fff; }
        .btn-no { background: #e0e0e0; color: #444; }
        .modal-card.success-mode { border-top-color: #1e7d32; }
        .modal-card.success-mode h2 { color: #1e7d32; }
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
            <button type="button" class="out-button" onclick="showLogoutModal()">Out</button>
        </div>
    </div>

    <div id="customModal" class="modal-overlay">
        <div class="modal-card" id="modalCard">
            <div class="modal-header">
                <h2 id="modalTitle">Confirm Session End</h2>
            </div>
            <div class="modal-body" id="modalBody">
                <p id="modalMessage">Are you sure you want to log out of your current library session?</p>
            </div>
            <div class="modal-footer" id="modalFooter">
                <button class="btn-modal btn-no" id="cancelBtn" onclick="closeModal()">Cancel</button>
                <button class="btn-modal btn-yes" id="confirmBtn" onclick="submitLogout()">Yes, Logout</button>
            </div>
        </div>
    </div>

<script>
    /** --- JS: GLOBALS --- **/
    const modal = document.getElementById('customModal');
    const modalCard = document.getElementById('modalCard');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');
    const modalFooter = document.getElementById('modalFooter');

    /** --- JS: UTILS --- **/
    function updateClock() {
        const el = document.getElementById('clock');
        if(el) el.textContent = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
    }
    setInterval(updateClock, 1000); updateClock();

    function launchConfetti() {
        var duration = 3 * 1000, end = Date.now() + duration;
        (function frame() {
            confetti({ particleCount: 3, angle: 60, spread: 55, origin: { x: 0 }, colors: ['#ffcc00', '#ffffff', '#1e7d32'] });
            confetti({ particleCount: 3, angle: 120, spread: 55, origin: { x: 1 }, colors: ['#c62828', '#ffffff', '#004aad'] });
            if (Date.now() < end) requestAnimationFrame(frame);
        }());
    }

    /** --- JS: SESSION INIT --- **/
    window.addEventListener('load', () => {
        const adminAction = localStorage.getItem('pending_admin_action');
        if (adminAction) { showForceLogoutModal(adminAction); return; }
        checkNotificationQueue();
    });

    /** --- JS: FLAG SYSTEM --- **/
    function checkNotificationQueue() {
        const queue = JSON.parse(localStorage.getItem('flag_queue') || "[]");
        if (localStorage.getItem('pending_admin_action')) return;
        if (queue.length > 0) showFlagNotification(queue[0], queue.length);
    }

    function showFlagNotification(info, queueLength) {
        modalCard.classList.remove('success-mode');
        modalCard.style.borderTop = "10px solid #e74c3c"; 
        modalTitle.innerHTML = "<span style='color: #e74c3c;'>🚩 FLAG SYSTEM UPDATE</span>";
        const badge = queueLength > 1 ? `<p style="color: #e74c3c; font-weight: bold; font-size: 0.8rem;">PENDING: ${queueLength}</p>` : "";
        modalMessage.innerHTML = `<div style="text-align: left; background: #fdf2f2; padding: 15px; border-radius: 8px; border-left: 5px solid #e74c3c;"><p><strong>NAME:</strong> ${info.name}</p><p><strong>REASON:</strong> <span style="color: #c0392b;">${info.reason}</span></p><p style="font-size: 0.85rem; color: #7f8c8d;"><strong>TIME:</strong> ${info.time}</p></div><p style="margin-top: 15px;">Record updated in flagging system.</p>${badge}`;
        modalFooter.innerHTML = `<button class="btn-modal btn-yes" style="width: 100%; background-color: #e74c3c;" onclick="acknowledgeFlag()">ACKNOWLEDGE</button>`;
        modal.style.display = 'flex';
    }

    function acknowledgeFlag() {
        let queue = JSON.parse(localStorage.getItem('flag_queue') || "[]");
        queue.shift();
        localStorage.setItem('flag_queue', JSON.stringify(queue));
        modalCard.style.borderTop = "none"; modal.style.display = 'none';
        setTimeout(checkNotificationQueue, 300);
    }

    /** --- JS: MODAL UI --- **/
    function showForceLogoutModal(status) {
        localStorage.setItem('pending_admin_action', status);
        localStorage.removeItem('flag_queue');
        modalCard.classList.remove('success-mode');
        modalCard.style.borderTop = "10px solid #c62828"; 
        modal.style.display = 'flex';
        let title = "", msg = "", icon = "", btnText = "OK, I UNDERSTAND";

        if (status === 'BLOCKED') { title = "ACCOUNT BLOCKED"; icon = "🚫"; msg = "Your account has been blocked. Contact Library Staff."; }
        else if (status === 'DELETED') { title = "ACCOUNT REMOVED"; icon = "❌"; msg = "Account removed. Access denied."; }
        else if (status === 'PROMOTED_TO_ADMIN') { title = "ACCESS UPDATED"; icon = "🔑"; msg = "Promoted to Admin. Please re-login."; btnText = "PROCEED TO LOGIN"; if(!sessionStorage.getItem('confetti_done')) { launchConfetti(); sessionStorage.setItem('confetti_done', 'true'); } }
        else { title = "SESSION TERMINATED"; icon = "⚠️"; msg = "Active session ended by Administrator."; }

        modalTitle.innerText = title;
        modalMessage.innerHTML = `<div style='font-size: 3.5rem; margin-bottom: 15px;'>${icon}</div>${msg}`;
        modalFooter.innerHTML = `<button class="btn-modal btn-yes" style="width: 100%;" onclick="finalRedirect('${status}')">${btnText}</button>`;
    }

    function showLogoutModal() {
        if(localStorage.getItem('pending_admin_action') || JSON.parse(localStorage.getItem('flag_queue') || "[]").length > 0) return; 
        modalCard.classList.remove('success-mode');
        modalCard.style.borderTop = "none";
        modalTitle.innerText = "Confirm Session End";
        modalMessage.innerText = "Are you sure you want to log out?";
        modalFooter.innerHTML = `<button class="btn-modal btn-no" onclick="closeModal()">Cancel</button><button class="btn-modal btn-yes" id="confirmBtn" onclick="submitLogout()">Yes, Logout</button>`;
        modal.style.display = 'flex';
    }

    function closeModal() { if(!localStorage.getItem('pending_admin_action')) modal.style.display = 'none'; }

    /** --- JS: DATA ACTIONS --- **/
    function submitLogout() {
        const btn = document.getElementById('confirmBtn');
        if(btn) { btn.innerText = "Processing..."; btn.disabled = true; }
        const formData = new FormData(); formData.append('action', 'logout_user');
        fetch(window.location.href, { method: 'POST', body: formData })
        .then(res => res.json()).then(data => {
            if(data.status === 'success') {
                modalCard.classList.add('success-mode'); modalCard.style.borderTop = "10px solid #1e7d32";
                modalTitle.innerText = "Session Ended";
                modalMessage.innerHTML = "<div style='font-size: 3.5rem; margin-bottom: 15px;'>✅</div>Logout successful.";
                modalFooter.innerHTML = `<button class="btn-modal btn-yes" style="width: 100%; background: #1e7d32;" onclick="finalRedirect('MANUAL')">OK</button>`;
                confetti({ particleCount: 100, spread: 70, origin: { y: 0.6 } });
            }
        }).catch(() => { window.location.href = 'index.php'; });
    }

    function finalRedirect(status) {
        localStorage.removeItem('pending_admin_action'); localStorage.removeItem('flag_queue');
        sessionStorage.removeItem('confetti_done');
        if(status !== 'MANUAL') localStorage.setItem('admin_action_notice', status);
        window.location.href = 'index.php';
    }

    /** --- JS: BACKGROUND TASKS --- **/
    setInterval(() => {
        fetch('check_session.php').then(res => res.json()).then(data => {
            if (data.status !== 'active' && data.status !== 'session_expired') {
                if (!localStorage.getItem('pending_admin_action')) showForceLogoutModal(data.status);
            }
            if (data.new_update) {
                let queue = JSON.parse(localStorage.getItem('flag_queue') || "[]");
                if (!queue.some(item => item.time === data.update_info.time)) {
                    queue.push(data.update_info); localStorage.setItem('flag_queue', JSON.stringify(queue));
                    if (modal.style.display !== 'flex') checkNotificationQueue();
                }
            }
        }).catch(() => console.log("Lost connection"));
    }, 4000);

    /** --- JS: NAVIGATION --- **/
    history.pushState(null, null, location.href);
    window.onpopstate = () => { 
        if(localStorage.getItem('pending_admin_action')) showForceLogoutModal(localStorage.getItem('pending_admin_action'));
        else if (JSON.parse(localStorage.getItem('flag_queue') || "[]").length > 0) checkNotificationQueue();
        else showLogoutModal();
        history.pushState(null, null, location.href); 
    };
</script>
</body>
</html>