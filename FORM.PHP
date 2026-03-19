<?php
/* --- SESSION & TIMEZONE --- */
session_start();
date_default_timezone_set('Asia/Manila');

/* --- ACCESS CONTROL --- */
if (!isset($_SESSION['user_id'])) {
    header("Location: INTRO1.PHP");
    exit();
}

/* --- DATABASE CONNECTION --- */
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "neu_library_signup_db"; 
$conn = new mysqli($servername, $username, $password, $dbname);
mysqli_set_charset($conn, "utf8mb4");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/* --- SECURITY CHECK --- */
$current_uid = $_SESSION['user_id'];
$security_check = $conn->prepare("SELECT is_blocked FROM users WHERE id = ? LIMIT 1");
$security_check->bind_param("i", $current_uid);
$security_check->execute();
$security_result = $security_check->get_result();

if ($user_status = $security_result->fetch_assoc()) {
    if ((int)$user_status['is_blocked'] === 1) {
        session_unset();
        session_destroy();
        header("Location: login.php?status=blocked");
        exit();
    }
}
$security_check->close();

/* --- FETCH USER DATA & PREVIOUS ID --- */
$email = $_SESSION['email'] ?? ''; 
$first_name = $_SESSION['first_name'] ?? ''; 
$last_name = $_SESSION['last_name'] ?? '';
$profile_pic = $_SESSION['profile_pic'] ?? ''; 
$id_no = ""; 

if (!empty($email)) {
    // Basic Profile Query
    $stmt = $conn->prepare("SELECT first_name, last_name, profile_pic FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user_result = $stmt->get_result();

    if ($user_data = $user_result->fetch_assoc()) {
        $first_name = $user_data['first_name'];
        $last_name = $user_data['last_name'];
        if(!empty($user_data['profile_pic'])) $profile_pic = $user_data['profile_pic'];
        
        $_SESSION['first_name'] = $first_name;
        $_SESSION['last_name'] = $last_name;
        $_SESSION['profile_pic'] = $profile_pic;
    }
    $stmt->close();

    // Previous ID Lookup
    $id_stmt = $conn->prepare("SELECT id_number FROM library_logs WHERE email = ? ORDER BY id DESC LIMIT 1");
    $id_stmt->bind_param("s", $email);
    $id_stmt->execute();
    $id_result = $id_stmt->get_result();
    
    if ($prev_log = $id_result->fetch_assoc()) {
        $id_no = $prev_log['id_number']; 
    }
    $id_stmt->close();
}

/* --- AVATAR FALLBACK --- */
if (empty($profile_pic)) {
    $name_param = urlencode($first_name . " " . $last_name);
    $profile_pic = "https://ui-avatars.com/api/?name=$name_param&background=1e7d32&color=fff&bold=true&size=512";
}

/* --- LOG SUBMISSION HANDLING --- */
$error_msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_log'])) {
    
    $id_no = trim($_POST['id_no']);
    $session_fname = $_SESSION['first_name'];
    $session_lname = $_SESSION['last_name'];
    $session_email = $_SESSION['email'];

    // Duplicate Session Check
    $check_active = $conn->prepare("SELECT id FROM active_sessions WHERE id_number = ? LIMIT 1");
    $check_active->bind_param("s", $id_no);
    $check_active->execute();
    $res_active = $check_active->get_result();

    if ($res_active->num_rows > 0) {
        $error_msg = "ID ALREADY IN USE: This ID is currently logged in the library.";
    } else {
        // Ownership Validation
        $check_owner = $conn->prepare("
            SELECT first_name, last_name FROM (
                SELECT id_number, first_name, last_name, email FROM library_logs
                UNION
                SELECT id_number, first_name, last_name, email FROM active_sessions
            ) AS combined_history 
            WHERE id_number = ? 
            AND email != ? 
            LIMIT 1
        ");
        $check_owner->bind_param("ss", $id_no, $session_email);
        $check_owner->execute();
        $res_owner = $check_owner->get_result();

        if ($res_owner->num_rows > 0) {
            $error_msg = "THIS I.D. NUMBER IS LINKED TO ANOTHER ACCOUNT. PLEASE USE YOUR OWN I.D.";
        } else {
            // Final Log Insertion
            $time_in_val = date("h:i:s A");
            $date_visited_val = date("Y-m-d");

            $sql = "INSERT INTO active_sessions (user_type, id_number, contact, first_name, middle_name, last_name, suffix, course, reason, others_detail, time_in, date_visited, email) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $mname = strtoupper($_POST['mname'] ?? '');
            $suffix = strtoupper($_POST['suffix'] ?? '');
            $u_type = $_POST['user_type'];
            $contact = $_POST['contact'];
            $course = $_POST['course'];
            $reason = $_POST['reason'];
            $others = $_POST['others_detail'];
            
            $stmt->bind_param("sssssssssssss", 
                $u_type, $id_no, $contact, 
                $session_fname, $mname, $session_lname, 
                $suffix, $course, $reason, 
                $others, $time_in_val, $date_visited_val, $session_email
            );

            if ($stmt->execute()) {
                $_SESSION['active_id'] = $conn->insert_id;
                header("Location: OUT.php");
                exit();
            } else {
                $error_msg = "Database Error: " . $conn->error;
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
    background-image: url('https://github.com/NathanielCoronado/NEU-LIBRARY-LOG/blob/main/NEU%20LOGO.jpg?raw=true');
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
</head>
<body>
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
                                <option value="" disabled selected>Select Type</option>
                                <option value="Student">Student</option>
                                <option value="Guest">Guest</option>
                                <option value="Faculty Member">Faculty Member</option>
                                <option value="Employee">Employee</option>
                            </select>
                        </div>

                        <div class="flex-id">
                            <label>I.D. Number</label> 

                            <?php if (!empty($error_msg)): ?>
                                <div id="error-display" style="color: #dc3545; font-size: 0.8rem; font-weight: bold; margin-bottom: 5px; text-transform: uppercase;">
                                    ⚠️ <?php echo $error_msg; ?>
                                </div>
                            <?php endif; ?>
                            
                            <input type="text" id="id_input" name="id_no" placeholder="XX-XXXXX-XXX" 
                                   oninput="clearError()" 
                                   value="<?php echo !empty($id_no) ? htmlspecialchars($id_no) : (isset($_POST['id_no']) ? htmlspecialchars($_POST['id_no']) : ''); ?>" 
                                   
                                   <?php 
                                     if (!empty($id_no) && empty($error_msg)) {
                                         echo 'readonly style="background-color: #e9ecef; cursor: not-allowed;"';
                                     } 
                                     
                                     if (!empty($error_msg)) {
                                         echo 'style="border: 2px solid #dc3545;"';
                                     }
                                   ?>>
                        </div>

                        <div class="flex-contact">
                            <label>Contact Number<span class="req">*</span></label>
                            <input type="tel" name="contact" placeholder="09XXXXXXXXX" required>
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
        <input type="text" name="mname" placeholder="Optional">
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
                            <input type="text" name="suffix" placeholder="Opt.">
                        </div>
                    </div>

                    <div class="row">
                        <div class="flex-1">
                            <label>Program<span class="req">*</span></label>
                            <select name="course" required>
                                <option value="" selected disabled>Select Program / Course</option>
                                <option value="Not Applicable">Not Applicable (Faculty / Staff / Guest)</option>
                                <optgroup label="UNDERGRADUATE PROGRAMS">
                                    <option value="B Elementary Education">B Elementary Education</option>
                                    <option value="B Library and Information Science">B Library and Information Science</option>
                                    <option value="B Music in Choral Conducting">B Music in Choral Conducting</option>
                                    <option value="B Music in Music Education">B Music in Music Education</option>
                                    <option value="B Music in Piano">B Music in Piano</option>
                                    <option value="B Music in Voice">B Music in Voice</option>
                                    <option value="B Public Administration">B Public Administration</option>
                                    <option value="BA Broadcasting">BA Broadcasting</option>
                                    <option value="BA Communication">BA Communication</option>
                                    <option value="BA Economics">BA Economics</option>
                                    <option value="BA Foreign Service">BA Foreign Service</option>
                                    <option value="BA Journalism">BA Journalism</option>
                                    <option value="BA Political Science">BA Political Science</option>
                                    <option value="BEEd with Specialization in Preschool Education">BEEd with Specialization in Preschool Education</option>
                                    <option value="BEEd with Specialization in Special Education">BEEd with Specialization in Special Education</option>
                                    <option value="BS Accountancy">BS Accountancy</option>
                                    <option value="BS Accounting Information System">BS Accounting Information System</option>
                                    <option value="BS Agriculture Major in Animal Science">BS Agriculture Major in Animal Science</option>
                                    <option value="BS Agriculture Major in Crop Science">BS Agriculture Major in Crop Science</option>
                                    <option value="BS Architecture">BS Architecture</option>
                                    <option value="BS Astronomy">BS Astronomy</option>
                                    <option value="BS Biology">BS Biology</option>
                                    <option value="BS Civil Engineering">BS Civil Engineering</option>
                                    <option value="BS Computer Science">BS Computer Science</option>
                                    <option value="BS Criminology">BS Criminology</option>
                                    <option value="BS Electrical Engineering">BS Electrical Engineering</option>
                                    <option value="BS Electronics Engineering">BS Electronics Engineering</option>
                                    <option value="BS Entrepreneurship">BS Entrepreneurship</option>
                                    <option value="BS Industrial Engineering">BS Industrial Engineering</option>
                                    <option value="BS Information System">BS Information System</option>
                                    <option value="BS Information Technology">BS Information Technology</option>
                                    <option value="BS Mechanical Engineering">BS Mechanical Engineering</option>
                                    <option value="BS Medical Technology">BS Medical Technology</option>
                                    <option value="BS Nursing">BS Nursing</option>
                                    <option value="BS Physical Therapy">BS Physical Therapy</option>
                                    <option value="BS Psychology">BS Psychology</option>
                                    <option value="BS Real Estate Management">BS Real Estate Management</option>
                                    <option value="BS Respiratory Therapy">BS Respiratory Therapy</option>
                                    <option value="BSBA Major in Financial Management">BSBA Major in Financial Management</option>
                                    <option value="BSBA Major in Human Resource Development Management">BSBA Major in Human Resource Development Management</option>
                                    <option value="BSBA Major in Legal Management">BSBA Major in Legal Management</option>
                                    <option value="BSBA Major in Marketing Management">BSBA Major in Marketing Management</option>
                                    <option value="BSEd Major in English">BSEd Major in English</option>
                                    <option value="BSEd Major in Filipino">BSEd Major in Filipino</option>
                                    <option value="BSEd Major in Mathematics">BSEd Major in Mathematics</option>
                                    <option value="BSEd Major in Music, Arts, and Physical Education">BSEd Major in Music, Arts, and Physical Education</option>
                                    <option value="BSEd Major in Science">BSEd Major in Science</option>
                                    <option value="BSEd Major in Social Studies">BSEd Major in Social Studies</option>
                                    <option value="BSEd Major in Technology and Livelihood Education">BSEd Major in Technology and Livelihood Education</option>
                                    <option value="BSEMC with Specialization in Digital Animation Technology">BSEMC with Specialization in Digital Animation Technology</option>
                                    <option value="BSEMC with Specialization in Game Development">BSEMC with Specialization in Game Development</option>
                                    <option value="Diploma in Midwifery">Diploma in Midwifery</option>
                                </optgroup>
                                <optgroup label="GRADUATE PROGRAMS">
                                    <option value="Doctor in Business Administration">Doctor in Business Administration</option>
                                    <option value="Doctor of Medicine">Doctor of Medicine</option>
                                    <option value="Juris Doctor Program">Juris Doctor Program</option>
                                    <option value="MAEd Major in Early Childhood Education">MAEd Major in Early Childhood Education</option>
                                    <option value="MAEd Major in Educational Management">MAEd Major in Educational Management</option>
                                    <option value="MAEd Major in Educational Psychology">MAEd Major in Educational Psychology</option>
                                    <option value="MAEd Major in Educational Technology">MAEd Major in Educational Technology</option>
                                    <option value="MAEd Major in Environmental Education">MAEd Major in Environmental Education</option>
                                    <option value="MAEd Major in Filipino">MAEd Major in Filipino</option>
                                    <option value="MAEd Major in Guidance and Counseling">MAEd Major in Guidance and Counseling</option>
                                    <option value="MAEd Major in Language Education">MAEd Major in Language Education</option>
                                    <option value="MAEd Major in Mathematics Education">MAEd Major in Mathematics Education</option>
                                    <option value="MAEd Major in Reading Education">MAEd Major in Reading Education</option>
                                    <option value="MAEd Major in Science Education">MAEd Major in Science Education</option>
                                    <option value="MAEd Major in Social Science">MAEd Major in Social Science</option>
                                    <option value="MAEd Major in Special Education and Inclusive Education">MAEd Major in Special Education and Inclusive Education</option>
                                    <option value="Master in Business Administration">Master in Business Administration</option>
                                    <option value="MBA Major in Human Resource Management">MBA Major in Human Resource Management</option>
                                    <option value="MBA Major in Organizational Development">MBA Major in Organizational Development</option>
                                    <option value="PhD in Education Major in Bilingual Education">PhD in Education Major in Bilingual Education</option>
                                    <option value="PhD in Education Major in Early Childhood Education">PhD in Education Major in Early Childhood Education</option>
                                    <option value="PhD in Education Major in Educational Leadership">PhD in Education Major in Educational Leadership</option>
                                    <option value="PhD in Education Major in Educational Management">PhD in Education Major in Educational Management</option>
                                    <option value="PhD in Education Major in Guidance & Counseling">PhD in Education Major in Guidance & Counseling</option>
                                    <option value="PhD in Education Major in Instructional Leadership">PhD in Education Major in Instructional Leadership</option>
                                    <option value="PhD in Education Major in Special Education and Inclusive Education">PhD in Education Major in Special Education and Inclusive Education</option>
                                </optgroup>
                                <optgroup label="COMPLETE BASIC EDUCATION (K-Preschool)">
                                    <option value="Preschool">Preschool</option>
                                    <option value="Grade School">Grade School</option>
                                    <option value="Junior High School">Junior High School</option>
                                    <option value="Senior High School">Senior High School</option>
                                    <option value="Special Education Program">Special Education Program</option>
                                </optgroup>
                                <optgroup label="OTHER PROGRAMS / ETEEAP / MCLE">
                                    <option value="Alternative Learning System (ALS)">Alternative Learning System (ALS)</option>
                                    <option value="Certificate Program for Sign Language Interpreters">Certificate Program for Sign Language Interpreters</option>
                                    <option value="Certificate Program in International Relations and ASEAN Studies">Certificate Program in International Relations and ASEAN Studies</option>
                                    <option value="Certificate Program in Korean Language">Certificate Program in Korean Language</option>
                                    <option value="Certificate Program in Mandarin Language">Certificate Program in Mandarin Language</option>
                                    <option value="Certificate Program in Russian Language">Certificate Program in Russian Language</option>
                                    <option value="Distance Education">Distance Education</option>
                                    <option value="MAEd Major Educational Management">MAEd Major Educational Management</option>
                                    <option value="Expanded Tertiary Education Equivalency and Accreditation Program (ETEEAP)">Expanded Tertiary Education Equivalency and Accreditation Program (ETEEAP)</option>
                                    <option value="BSBA Major in Financial Management (ETEEAP)">BSBA Major in Financial Management (ETEEAP)</option>
                                    <option value="BSBA Major in Marketing Management (ETEEAP)">BSBA Major in Marketing Management (ETEEAP)</option>
                                    <option value="BSBA Major in Human Resource Development Management (ETEEAP)">BSBA Major in Human Resource Development Management (ETEEAP)</option>
                                    <option value="Mandatory Continuing Legal Education (MCLE)">Mandatory Continuing Legal Education (MCLE)</option>
                                </optgroup>
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
                            <option value="" selected disabled>Select Reason</option>
                            <option value="Research">Research</option>
                            <option value="Study">Study</option>
                            <option value="Borrowing">Borrowing Books</option>
                            <option value="Returning">Returning Books</option>
                            <option value="Group Study">Group Study</option>
                            <option value="Meeting">Meeting</option>
                            <option value="Internet Use">Internet Use</option>
                            <option value="Printing/Photocopy">Printing/Photocopy</option>
                            <option value="Academic Requirement">Academic Requirement</option>
                            <option value="Others">Others</option>
                        </select>

                        <div id="others-section">
                            <label style="margin-top: 8px;">Others (Please Specify):<span class="req">*</span></label>
                            <textarea id="others-text" name="others_detail" placeholder="Please specify..."></textarea>
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
        <a href="INTRO1.php" class="btn-logout">LOGOUT</a>
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

        /* --- SESSION PERSISTENCE (SAVE) --- */
        document.getElementById('libraryForm').addEventListener('input', function(e) {
            const formData = new FormData(this);
            const data = {};
            formData.forEach((value, key) => {
                if(key !== 'time_in_display') {
                    data[key] = value;
                }
            });
            sessionStorage.setItem('libraryFormData', JSON.stringify(data));
        });

        /* --- SESSION PERSISTENCE (RESTORE) --- */
        window.addEventListener('load', function() {
            const savedData = sessionStorage.getItem('libraryFormData');
            if (savedData) {
                const data = JSON.parse(savedData);
                const form = document.getElementById('libraryForm');
                
                Object.keys(data).forEach(key => {
                    const input = form.elements[key];
                    if (input) {
                        input.value = data[key];
                    }
                });
                
                if (typeof toggleOthers === "function") toggleOthers();
            }
        });

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