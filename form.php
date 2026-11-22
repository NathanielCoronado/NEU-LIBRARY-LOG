<?php
/**
 * FILE: form.php
 * PURPOSE: Visitor "time-in" form for logged-in users.
 */
require_once 'auth.php';

/** --- 1. ACCESS CONTROL --- **/
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
if ($_SESSION['user_id'] === 'ADMIN_01') { // master admin has no visitor form
    header("Location: admin.php");
    exit();
}

/** --- 2. SECURITY & ROLE CHECK --- **/
$current_uid = $_SESSION['user_id'];
$security_check = $conn->prepare("SELECT is_blocked, role FROM users WHERE id = ? LIMIT 1");
$security_check->bind_param("i", $current_uid);
$security_check->execute();
$user_status = $security_check->get_result()->fetch_assoc();
$security_check->close();

if (!$user_status) {
    session_unset(); session_destroy();
    header("Location: index.php");
    exit();
}
if ((int) $user_status['is_blocked'] === 1) {
    session_unset(); session_destroy();
    header("Location: login.php?status=blocked");
    exit();
}
if (strtoupper(trim($user_status['role'] ?? '')) === 'ADMIN') {
    $_SESSION['role'] = 'ADMIN'; // keep session in sync, avoids a redirect loop
    header("Location: admin.php");
    exit();
}

/** --- 3. ALREADY INSIDE? GO STRAIGHT TO THE "OUT" SCREEN --- **/
$inside = $conn->prepare("SELECT id FROM active_sessions WHERE email = ? ORDER BY id DESC LIMIT 1");
$inside->bind_param("s", $_SESSION['email']);
$inside->execute();
if ($open = $inside->get_result()->fetch_assoc()) {
    $_SESSION['active_id'] = $open['id'];
    header("Location: out.php");
    exit();
}
$inside->close();

/** --- 5. SAVED PROFILE (what the visitor filled in before, or what an admin edited) --- **/
require_once 'profile.php';
$email   = $_SESSION['email'] ?? '';
$profile = get_profile($email) ?? array_fill_keys(PROFILE_FIELDS, '');

$first_name  = $profile['first_name'];
$last_name   = $profile['last_name'];
$profile_pic = $profile['profile_pic'] ?? '';
$id_no       = $profile['id_number'];          // locked once set; only an admin can change it
$id_locked   = $id_no !== '';
$_SESSION['first_name'] = $first_name;
$_SESSION['last_name']  = $last_name;

// Values shown in the form (re-filled from POST if the form had an error)
$val = [
    'user_type' => $profile['user_type'],
    'contact'   => $profile['contact'],
    'mname'     => $profile['middle_name'],
    'suffix'    => $profile['suffix'],
    'course'    => $profile['course'],
    'reason'    => canonical($profile['last_reason'] ?? '', array_keys($REASONS)),
    'others'    => $profile['last_others'] ?? '',
];

// Avatar fallback
if (empty($profile_pic)) {
    $profile_pic = "https://ui-avatars.com/api/?name=" . urlencode("$first_name $last_name") . "&background=1e7d32&color=fff&bold=true&size=512";
}

/** --- 6. LOG SUBMISSION HANDLING --- **/
$error_msg = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_log'])) {

    $val = [
        'user_type' => trim($_POST['user_type'] ?? ''),
        'contact'   => trim($_POST['contact'] ?? ''),
        'mname'     => trim($_POST['mname'] ?? ''),
        'suffix'    => trim($_POST['suffix'] ?? ''),
        'course'    => trim($_POST['course'] ?? ''),
        'reason'    => trim($_POST['reason'] ?? ''),
        'others'    => trim($_POST['others_detail'] ?? ''),
    ];
    if (!$id_locked) $id_no = strtoupper(trim($_POST['id_no'] ?? ''));

    if ($val['user_type'] === '' || $val['contact'] === '' || $val['course'] === '' || $val['reason'] === '') {
        $error_msg = "Please complete all required fields.";
    } elseif ($val['reason'] === 'Others' && $val['others'] === '') {
        $error_msg = "Please specify your reason.";
    } elseif ($id_no !== '' && ($owner = id_owner_conflict($id_no, $email))) {
        $error_msg = "THIS I.D. NUMBER IS LINKED TO ANOTHER ACCOUNT. PLEASE USE YOUR OWN I.D.";
    } else {
        $check_active = $conn->prepare("SELECT id FROM active_sessions WHERE id_number = ? AND id_number <> 'N/A' AND id_number <> '' LIMIT 1");
        $check_active->bind_param("s", $id_no);
        $check_active->execute();

        if ($check_active->get_result()->num_rows > 0) {
            $error_msg = "ID ALREADY IN USE: This ID is currently logged in the library.";
        } else {
            $conn->begin_transaction();
            try {
                // Save the profile so it is pre-filled next time (and synced to old records)
                $p = save_profile($email, [
                    'first_name' => $first_name, 'last_name' => $last_name,
                    'middle_name' => $val['mname'], 'suffix' => $val['suffix'],
                    'id_number' => $id_no, 'contact' => $val['contact'],
                    'user_type' => $val['user_type'], 'course' => $val['course'],
                ]);

                // Start the visit
                $time_in = date("h:i:s A");
                $today   = date("Y-m-d");
                $idLog   = $p['id_number'] !== '' ? $p['id_number'] : 'N/A';
                $reason  = canonical($val['reason'], array_keys($REASONS));
                $others  = $reason === 'Others' ? $val['others'] : '';

                $stmt = $conn->prepare("INSERT INTO active_sessions (user_type, id_number, contact, first_name, middle_name, last_name, suffix, course, reason, others_detail, time_in, date_visited, email)
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssssssssss",
                    $p['user_type'], $idLog, $p['contact'], $p['first_name'], $p['middle_name'], $p['last_name'],
                    $p['suffix'], $p['course'], $reason, $others, $time_in, $today, $email);
                $stmt->execute();
                $_SESSION['active_id'] = $conn->insert_id;
                $conn->commit();

                header("Location: out.php");
                exit();
            } catch (Exception $ex) {
                $conn->rollback();
                $error_msg = "Could not save your log. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NEU Library Form - Official</title>
    <style>

/* =========================================
   1. GLOBAL RESET & BASE STYLES
   ========================================= */
* { 
    margin: 0; 
    padding: 0; 
    box-sizing: border-box; 
}

body {
    font-family: 'Segoe UI', Arial, sans-serif;
    height: 100vh;
    display: flex;
    flex-direction: column;
    overflow-x: hidden; 
    background-color: #004aad; 
}

/* =========================================
   2. TRICOLOR BORDERS & ANIMATIONS
   ========================================= */
body::before, body::after {
    content: "";
    width: 100%;
    height: 36px;
    flex-shrink: 0;
    z-index: 100;
    position: fixed;
    left: 0;
    animation: slideInOnce 1.2s ease-out forwards;
}

body::before { 
    top: 0; 
    background: linear-gradient(to bottom, #1e7d32 33.33%, #ffffff 33.33% 66.66%, #c62828 66.66%); 
    transform: translateX(-100%);
}

body::after { 
    bottom: 0; 
    background: linear-gradient(to bottom, #c62828 33.33%, #ffffff 33.33% 66.66%, #1e7d32 66.66%); 
    transform: translateX(100%);
}

@keyframes slideInOnce { 
    to { transform: translateX(0); } 
}

/* =========================================
   3. LAYOUT WRAPPERS & GRID
   ========================================= */
.main-wrapper {
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0 40px 40px 40px;
    position: relative;
    overflow: visible; 
}

.form-content {
    width: 100%;
    max-width: 1200px;
    display: flex;
    flex-direction: column;
    margin-top: 80px; 
}

.layout-grid {
    display: grid;
    grid-template-columns: 1.8fr 1fr;
    gap: 30px;
    align-items: start;
}

/* =========================================
   4. HEADER & LOGO
   ========================================= */
.header {
    width: 100%;
    text-align: center;
    margin-bottom: 30px;
    padding-left: 150px; 
}

.header h1 {
    color: white;
    font-size: clamp(1.8rem, 4vw, 3rem);
    text-transform: uppercase;
    font-weight: 900;
    letter-spacing: 3px;
    text-shadow: 2px 2px 10px rgba(0,0,0,0.3);
}

.logo {
    position: absolute;
    top: 10px;
    left: 50px;
    width: 150px; 
    height: 150px;
    background-color: white; 
    border-radius: 50%;
    border: 6px solid #fff;
    background-image: url('neu-logo.jpg');
    background-size: cover;
    background-position: center;
    box-shadow: 0 10px 30px rgba(0,0,0,0.4);
    z-index: 1001; 
}

/* =========================================
   5. FORM INPUTS & SELECTS
   ========================================= */
.inputs-section { display: flex; flex-direction: column; gap: 15px; }
.row { display: flex; gap: 15px; align-items: flex-end; }

input, select, textarea {
    padding: 12px 15px;
    border: none;
    border-radius: 4px;
    font-size: 15px;
    width: 100%;
    background-color: white;
    outline: none;
}

textarea { 
    resize: none; 
    height: 80px;
    min-height: 80px; 
    width: 100%;
    margin-top: 5px;
    padding: 10px;
    border-radius: 4px;
    border: none;
    font-family: inherit;
    display: block; 
}

select {
    appearance: none; 
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23333' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 16px;
    padding-right: 35px;
}

label { color: white; font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block; }
.req { color: #ff4d4d; margin-left: 2px; }
#others-section { display: none; margin-top: 8px; width: 100%; }

/* Flexbox Utilities */
.flex-user-type { flex: 0.8; } 
.flex-id { flex: 1.2; }        
.flex-contact { flex: 1.2; }   
.flex-1 { flex: 1; }

/* =========================================
   6. AVATAR & INTERACTIVE BUTTONS
   ========================================= */
.photo-side { 
    display: flex; 
    flex-direction: column; 
    align-items: center; 
    gap: 20px; 
}

.avatar-container {
    width: 300px; height: 300px;
    background-color: #f0f0f0;
    border-radius: 12px;
    border: 4px solid #fff;
    box-shadow: 0 10px 25px rgba(0,0,0,0.4);
    overflow: hidden;
    display: flex; align-items: center; justify-content: center;
}

.user-photo { width: 100%; height: 100%; object-fit: cover; }
.initials-avatar { 
    width: 100%; height: 100%; background-color: #1e7d32; color: white; 
    display: flex; align-items: center; justify-content: center; 
    font-size: 100px; font-weight: bold; 
}

.btn-submit {
    background-color: #000080;
    color: white;
    padding: 15px 0;
    width: 100%;
    max-width: 300px;
    border: none;
    font-weight: bold;
    font-size: 1.2rem;
    cursor: pointer;
    border-radius: 6px;
    transition: 0.3s;
    text-transform: uppercase;
    display: block;
    margin-top: 10px;
}

.btn-submit:hover { background-color: #ffffff; color: #004aad; }

.btn-logout { 
    position: fixed; bottom: 50px; right: 20px; z-index: 100;
    background-color: #c62828; color: white; padding: 10px 25px; 
    border: none; font-weight: bold; border-radius: 4px; text-decoration: none;
    display: inline-block; transition: 0.3s;
}

.btn-logout:hover { background-color: #b71c1c; transform: scale(1.05); }

/* =========================================
   7. RESPONSIVE BREAKPOINTS (MOBILE/TABLET)
   ========================================= */
@media (max-width: 1024px) {
    body { 
        overflow-y: auto; 
        height: auto; 
        min-height: 100vh; 
        padding-top: 36px; 
    }

    body::before { 
        height: 24px; 
        position: fixed; 
        top: 0;
        left: 0;
        z-index: 1000; 
        display: block;
        transform: translateX(-100%); 
        animation: slideInOnce 1.2s ease-out forwards;
    }

    body::after { display: none !important; }

    .main-wrapper { 
        padding: 0 20px 120px 20px; 
        overflow: visible; 
    }

    .form-content { margin-top: 5px !important; }

    .logo { 
        position: relative; 
        left: auto; 
        top: 0; 
        margin: 15px auto 5px auto; 
        width: 120px; 
        height: 120px; 
        display: block;
        z-index: 999; 
        transform: none; 
    }

    .header { 
        padding-left: 0; 
        margin-bottom: 20px; 
        text-align: center;
    }

    .header h1 { 
        font-size: 1.4rem; 
        letter-spacing: 1px;
        line-height: 1.2;
    }

    .layout-grid { 
        display: flex !important; 
        flex-direction: column !important; 
        gap: 0; 
    }

    .photo-side { display: contents; }

    .avatar-container { 
        order: 1; 
        width: 200px; 
        height: 200px;
        margin: 0 auto 20px auto;
        z-index: 1; 
    }

    .inputs-section { 
        order: 2; 
        width: 100%; 
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .row { 
        flex-direction: column !important; 
        gap: 15px; 
        width: 100%;
    }

    .flex-user-type, .flex-id, .flex-contact, .flex-1, 
    div[style*="width: 80px"], 
    div[style*="width: 150px"] {
        width: 100% !important; 
        max-width: 100% !important;
        flex: none !important;
    }

    .btn-submit { 
        order: 3; 
        width: 100%; 
        max-width: 100%; 
        margin: 20px 0 30px 0; 
        height: 55px;
    }

    .btn-logout {
        position: fixed; 
        bottom: 50px; 
        right: 20px; 
        z-index: 1100;
        box-shadow: 0 4px 10px rgba(0,0,0,0.3);
    }
}

</style>
    <link rel="icon" href="favicon.ico">
    <meta name="theme-color" content="#004aad">
    <link rel="stylesheet" href="responsive.css">
    <link rel="stylesheet" href="tricolor.css">
</head>
<body class="form-page">
    <div class="main-wrapper">
        <div class="logo"></div>

        <form class="form-content" id="libraryForm" method="POST" action="">
            <div class="header">
                <h1>LIBRARY VISITOR INFORMATION FORM</h1>
            </div>

            <div class="layout-grid">
                <div class="inputs-section">
                    
                    <div class="row">
                        <div class="flex-user-type">
                            <label>User Type<span class="req">*</span></label>
                            <select name="user_type" required>
                                <option value="" disabled <?php echo $val['user_type'] === '' ? 'selected' : ''; ?>>Select Type</option>
                                <?php render_options($USER_TYPES, $val['user_type']); ?>
                            </select>
                        </div>

                        <div class="flex-id">
                            <label>I.D. Number</label> 

                            <?php if (!empty($error_msg)): ?>
                                <div id="error-display" style="color: #dc3545; font-size: 0.8rem; font-weight: bold; margin-bottom: 5px; text-transform: uppercase;">
                                    ⚠️ <?php echo e($error_msg); ?>
                                </div>
                            <?php endif; ?>
                            
                            <input type="text" id="id_input" name="id_no" placeholder="XX-XXXXX-XXX"
                                   oninput="clearError()" value="<?php echo e($id_no); ?>"
                                   <?php echo $id_locked ? 'readonly title="Contact the library staff to change your ID number" style="background-color:#e9ecef;cursor:not-allowed;"' : ''; ?>
                                   <?php echo $error_msg ? 'style="border:2px solid #dc3545;"' : ''; ?>>
                        </div>

                        <div class="flex-contact">
                            <label>Contact Number<span class="req">*</span></label>
                            <input type="tel" name="contact" placeholder="09XXXXXXXXX" value="<?php echo e($val['contact']); ?>" required>
                        </div>
                    </div>

                   <div class="row">
    <div class="flex-1">
        <label>First Name</label>
        <input type="text" 
               name="fname" 
               value="<?php echo htmlspecialchars($first_name); ?>" 
               readonly 
               style="background-color: #e9ecef; cursor: not-allowed;" 
               required>
    </div>
    
    <div class="flex-1">
        <label>Middle Name</label>
        <input type="text" name="mname" placeholder="Optional" value="<?php echo e($val['mname']); ?>">
    </div>
    
    <div class="flex-1">
        <label>Last Name</label>
        <input type="text" 
               name="lname" 
               value="<?php echo htmlspecialchars($last_name); ?>" 
               readonly 
               style="background-color: #e9ecef; cursor: not-allowed;" 
               required>
    </div>
                        
                        <div style="width: 80px;">
                            <label>Suffix</label>
                            <input type="text" name="suffix" placeholder="Opt." value="<?php echo e($val['suffix']); ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="flex-1">
                            <label>Program<span class="req">*</span></label>
                            <select name="course" required>
                                <option value="" disabled <?php echo $val['course'] === '' ? 'selected' : ''; ?>>Select Program / Course</option>
                                <?php render_programs($val['course']); ?>
                            </select>
                        </div>
                        
                        <div style="width: 150px;">
                            <label>Time In</label>
                            <input type="text" id="timeIn" name="time_in_display" readonly>
                        </div>
                    </div>

                    <div>
                        <label>Reason for Using the Library:<span class="req">*</span></label>
                        <select id="reason-select" name="reason" onchange="toggleOthers()" required>
                            <option value="" disabled <?php echo $val['reason'] === '' ? 'selected' : ''; ?>>Select Reason</option>
                            <?php render_options($REASONS, $val['reason'], true); ?>
                        </select>

                        <div id="others-section">
                            <label style="margin-top: 8px;">Others (Please Specify):<span class="req">*</span></label>
                            <textarea id="others-text" name="others_detail" placeholder="Please specify..."><?php echo e($val['others']); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="photo-side">
                    <div class="avatar-container">
                        <?php if (!empty($profile_pic)): ?>
                            <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile" class="user-photo">
                        <?php else: ?>
                            <div class="initials-avatar">
                                <?php echo strtoupper(substr($first_name ?? 'U', 0, 1) . substr($last_name ?? 'N', 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <input type="hidden" name="user_photo_url" value="<?php echo htmlspecialchars($profile_pic ?? ''); ?>">
                    <button type="submit" name="submit_log" class="btn-submit">Submit Form</button>
                </div>
            </div>
        </form>
    </div>

    <div class="logout-wrapper">
        <a href="logout.php" class="btn-logout">LOGOUT</a>
    </div>

<script>
        /* --- CLOCK SYSTEM --- */
        function updateTime() {
            const timeField = document.getElementById('timeIn');
            if (timeField) {
                const now = new Date();
                timeField.value = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            }
        }
        setInterval(updateTime, 1000);
        updateTime();

        /* --- UI TOGGLE LOGIC --- */
        function toggleOthers() {
            const select = document.getElementById('reason-select');
            const othersSection = document.getElementById('others-section');
            const othersText = document.getElementById('others-text');
            if (select.value === 'Others') {
                othersSection.style.display = 'block';
                othersText.required = true;
            } else {
                othersSection.style.display = 'none';
                othersText.required = false;
            }
        }

        /* --- ERROR DISPLAY HANDLING --- */
        function clearError() {
            const errorDiv = document.getElementById('error-display');
            const inputField = document.getElementById('id_input');
            
            if (errorDiv) {
                errorDiv.style.display = 'none';
                inputField.style.border = '1px solid #ccc'; 
            }
        }

        /* --- BROWSER HISTORY MANAGEMENT --- */
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        /* Saved details are pre-filled by the server, so show "Others" box if needed */
        toggleOthers();

        /* --- SESSION CLEANUP (LOGOUT/SUBMIT) --- */
        const logoutBtn = document.querySelector('.btn-logout');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', function() {
                sessionStorage.clear();
            });
        }

        document.getElementById('libraryForm').addEventListener('submit', function() {
            sessionStorage.removeItem('libraryFormData'); 
        });
    </script>
</body>
</html>