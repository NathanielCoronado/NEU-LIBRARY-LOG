<?php
/** --- 1. INITIALIZATION --- **/
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'db_conn.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NEU Library - Admin Dashboard</title>
    <link rel="stylesheet" href="admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body onload="updateCharts(); initCharts();">

<div class="sidebar">
    <div class="admin-logo"></div>
    <h2>Library Admin</h2>
    <div class="nav-links">
        <div class="nav-link active" onclick="showSection('visitor-section', this)">📊 Visitor Logs</div>
        <div class="nav-link" onclick="showSection('user-section', this)">👥 User Management</div>
        <div class="nav-link" onclick="showSection('flagged-section', this)">🚩 Flagged Records</div>
    </div>

    <button class="btn-logout" onclick="confirmLogout()" style="margin-top: auto; border: none; cursor: pointer;">
        Logout
    </button>
</div>

<div class="main-content">
    
    <div class="header-bar">
        <div>
            <h1 style="font-size: 1.3rem; color: var(--neu-blue); text-transform: uppercase;">Library Monitoring System</h1>
            <p style="font-size: 0.8rem; color: #888; text-transform: uppercase;">Real-time tracking and automated flagging</p>
        </div>
        <div style="text-align: right;">
            <div class="live-clock" id="clock">00:00:00 AM</div>
            <div class="live-date" id="date">--</div>
        </div>
    </div>

    <div id="visitor-section" class="content-section active">
        
        <div class="stats-grid">
            <div class="stat-card" style="border-left-color: var(--neu-blue);"><h3>Current Inside</h3><span class="stat-main" id="capacityCount">0</span></div>
            <div class="stat-card" style="border-left-color: var(--neu-green);"><h3>Total Today</h3><span class="stat-main" id="todayCount">0</span></div>
            <div class="stat-card" style="border-left-color: var(--neu-yellow);"><h3>Last Week</h3><span class="stat-main" id="lastWeekCount">0</span></div>
            <div class="stat-card" style="border-left-color: #9c27b0;"><h3>Last Month</h3><span class="stat-main" id="lastMonthCount">0</span></div>
            <div class="stat-card" style="border-left-color: var(--neu-red);"><h3>Flagged Records</h3><span class="stat-main" id="flaggedCount">0</span></div>
        </div>

        <div class="analytics-container">
            <div class="chart-card">
                <h3 style="font-size: 0.8rem; color: #555; margin-bottom: 10px; text-transform: uppercase;">Weekly Visitors Trend</h3>
                <canvas id="visitorChart"></canvas>
            </div>
            <div class="chart-card">
                <h3 style="font-size: 0.8rem; color: #555; margin-bottom: 10px; text-transform: uppercase;">Purpose Distribution</h3>
                <canvas id="purposeChart"></canvas>
            </div>
        </div>

        <div class="table-container">
            <div class="filter-group">
                <button class="filter-btn active" data-range="all" onclick="filterByTime('all', this)">ALL</button>
                <button class="filter-btn" id="todayFilterBtn" onclick="filterByTime('today', this)">Today</button>
                <button class="filter-btn" onclick="filterByTime('lastweek', this)">Last Week</button>
                <button class="filter-btn" onclick="filterByTime('lastmonth', this)">Last Month</button>
                
                <button class="filter-btn" onclick="openModal()" 
                    style="background: var(--neu-blue); color: white; border-color: var(--neu-blue); display: inline-flex; align-items: center; gap: 8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    ADD VISITOR
                </button>

                <button class="filter-btn" onclick="exportTableToCSV()" 
                    style="background: var(--neu-green); color: white; border-color: var(--neu-green); display: inline-flex; align-items: center; gap: 8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    EXPORT DATA
                </button>
                <input type="text" class="search-bar" id="tableSearch" placeholder="SEARCH NAME OR ID..." onkeyup="searchTable()">
            </div>

            <table id="visitorTable">
                <thead>
                    <tr>
                        <th>Date & Time In</th>
                        <th>Time Out</th>
                        <th>Full Name</th>
                        <th>ID Number</th>
                        <th>Category</th>
                        <th>Program/Course</th>
                        <th>Purpose</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
<?php
/** --- 3. DATABASE QUERY: SYNCED LOGS & PROFILE DATA --- **/
$sql = "
SELECT 
    main.id,
    main.reason,
    main.others_detail,
    main.time_in,
    main.time_out,
    main.current_status,
    main.date_visited,
    
    latest.id_number,
    latest.first_name,
    latest.middle_name,
    latest.last_name,
    latest.suffix,
    latest.user_type,
    latest.course,
    latest.email,
    latest.contact,

    u.is_blocked,
    u.role 

FROM (
    /** STEP 1: COMBINED LOGS **/
    SELECT id, id_number, email, reason, others_detail, time_in, NULL AS time_out, 'Inside' AS current_status, date_visited FROM active_sessions
    UNION ALL
    SELECT id, id_number, email, reason, others_detail, time_in, time_out, 'Out' AS current_status, date_visited FROM library_logs
) AS main

/** STEP 2: PROFILE SYNCING **/
LEFT JOIN (
    SELECT cs.id_number, cs.first_name, cs.middle_name, cs.last_name, cs.suffix, cs.user_type, cs.course, cs.email, cs.contact
    FROM (
        SELECT id_number, first_name, middle_name, last_name, suffix, user_type, course, email, contact, date_visited, time_in
        FROM active_sessions
        UNION ALL
        SELECT id_number, first_name, middle_name, last_name, suffix, user_type, course, email, contact, date_visited, time_in
        FROM library_logs
    ) AS cs
    INNER JOIN (
        SELECT id_number, MAX(CONCAT(date_visited, ' ', time_in)) AS max_datetime
        FROM (
            SELECT id_number, date_visited, time_in FROM active_sessions
            UNION ALL
            SELECT id_number, date_visited, time_in FROM library_logs
        ) AS dt
        GROUP BY id_number
    ) AS mx ON cs.id_number = mx.id_number AND CONCAT(cs.date_visited, ' ', cs.time_in) = mx.max_datetime
) AS latest ON main.id_number = latest.id_number

/** STEP 3: MASTER JOIN & HIDE ADMIN **/
LEFT JOIN users u ON latest.email = u.email
WHERE (u.role IS NULL OR u.role != 'ADMIN')

/** STEP 4: FINAL SORTING **/
ORDER BY main.date_visited DESC, main.time_in DESC;
";

/** --- 4. DATA DISPLAY LOGIC --- **/
$result = $conn->query($sql);

if ($result && $result->num_rows > 0):
    while($row = $result->fetch_assoc()):
        // Name Formatting (Middle Initial)
        $mname = trim($row['middle_name'] ?? '');
        $mi = "";
        if (!empty($mname)) {
            $words = explode(' ', $mname);
            foreach ($words as $w) { 
                $mi .= strtoupper(substr($w, 0, 1)) . "."; 
            }
            $mi = " " . $mi; 
        }
        $suffix = !empty($row['suffix']) ? " " . $row['suffix'] : "";
        $fullName = strtoupper($row['last_name'] . ", " . $row['first_name'] . $mi . $suffix);

        // Status & Formatting Logic
        $isBlocked = (isset($row['is_blocked']) && $row['is_blocked'] == 1);
        $isInside = ($row['current_status'] == 'Inside');
        $formattedDateTimeIn = date("M d, Y", strtotime($row['date_visited'])) . " | " . date("h:i A", strtotime($row['time_in']));
        
        // Time Out Display Cell
        $timeOutDisplay = $isInside ? 
            '<span style="color: #999;">--:--</span>' : 
            '<span style="color: #e74c3c; font-weight: bold;">' . date("h:i A", strtotime($row['time_out'])) . '</span>';
?>
    <tr data-id="<?php echo $row['id']; ?>" 
        data-fname="<?php echo htmlspecialchars($row['first_name']); ?>"
        data-mname="<?php echo htmlspecialchars($row['middle_name']); ?>"
        data-lname="<?php echo htmlspecialchars($row['last_name']); ?>"
        data-suffix="<?php echo htmlspecialchars($row['suffix']); ?>"
        data-category="<?php echo htmlspecialchars($row['user_type']); ?>" 
        data-program="<?php echo htmlspecialchars($row['course']); ?>"
        data-email="<?php echo htmlspecialchars($row['email'] ?? ''); ?>"
        data-contact="<?php echo htmlspecialchars($row['contact'] ?? ''); ?>"
        data-in-time="<?php echo date('Y-m-d', strtotime($row['date_visited'])); ?>" 
        class="<?php echo $isBlocked ? 'blocked-row' : ($isInside ? 'active-row' : 'logged-out-row'); ?>"
        style="<?php echo $isBlocked ? 'background-color: #fcfcfc;' : ''; ?>">
    
        <td class="dt-cell"><?php echo $formattedDateTimeIn; ?></td>
        <td class="time-out-cell"><?php echo $timeOutDisplay; ?></td>
    
        <td class="name-cell" style="font-weight: 600;">
            <?php echo htmlspecialchars($fullName); ?>
        </td>

        <td class="id-cell"><?php echo htmlspecialchars($row['id_number']); ?></td>
        <td><?php echo htmlspecialchars($row['user_type']); ?></td>
        <td><?php echo htmlspecialchars($row['course']); ?></td>
    
        <td class="purpose-cell"
            data-original-purpose="<?php echo htmlspecialchars($row['reason']); ?>"
            data-others-info="<?php echo htmlspecialchars($row['others_detail'] ?? ''); ?>">
            <?php 
                if (strtoupper($row['reason']) === 'OTHERS' && !empty($row['others_detail'])) {
                    echo '<i style="color: #555;">' . htmlspecialchars($row['others_detail']) . '</i>';
                } else {
                    echo htmlspecialchars($row['reason']);
                }
            ?>
        </td>
    
<td>
            <?php if($isBlocked): ?>
                <span class="badge" style="background-color: #fcebea; color: #7f231c; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 0.75rem; border: 1px solid #f9d6d5;">
                    BLOCKED
                </span>
            <?php else: ?>
                <span class="badge <?php echo $isInside ? 'status-active' : 'status-out'; ?>">
                    <?php echo $row['current_status']; ?>
                </span>
            <?php endif; ?>
        </td>
    
        <td>
            <div class="action-buttons">
                <?php if($isBlocked): ?>
                    <button class="btn-action btn-edit" onclick="editRow(this)">📝 Edit</button>
                    <button class="btn-action btn-delete" onclick="deleteRow(this)">🗑️ Del</button>
                <?php else: ?>
                    <?php if($isInside): ?>
                        <button class="btn-action btn-out" onclick="handleOut(<?php echo $row['id']; ?>)">🚪 Out</button>
                        <button class="btn-action btn-edit" onclick="editRow(this)">📝 Edit</button>
                        <button class="btn-action btn-flag" onclick="toggleManualFlag(this)">🚩 FLAG</button>
                    <?php else: ?>
                        <button class="btn-action btn-edit" onclick="editRow(this)">📝 Edit</button>
                    <?php endif; ?>
                    <button class="btn-action btn-delete" onclick="deleteRow(this)">🗑️ Del</button>
                <?php endif; ?>
            </div>
        </td>
    </tr>
    <?php endwhile; else: ?>
        <tr><td colspan="9" style="text-align:center;">No records found.</td></tr>
    <?php endif; ?>
</tbody>
</table>
            </div>
        </div>

<div id="user-section" class="content-section">
    <div class="table-container">
        <h2 style="margin-bottom: 20px; color: var(--neu-blue); text-transform: uppercase;">User Account Management</h2>
        <div class="filter-group">
            <input type="text" class="search-bar" id="userSearch" placeholder="SEARCH USERS..." style="margin-left: 0;" onkeyup="searchUsers()">
        </div>
        <table id="userTable">
            <thead>
                <tr>
                    <th>First Name</th>
                    <th>Middle Name</th>
                    <th>Last Name</th>
                    <th>ID Number</th>
                    <th>Contact Number</th>
                    <th>Email Address</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                /** SQL: Sync Users with Latest Logs **/
                $sqlUser = "SELECT 
                                u.email, 
                                u.is_blocked,
                                u.role, 
                                COALESCE(combined.first_name, u.first_name) AS final_fname,
                                COALESCE(combined.middle_name, '') AS final_mname,
                                COALESCE(combined.last_name, u.last_name) AS final_lname,
                                COALESCE(combined.id_number, 'PENDING/NO LOGS') AS final_id,
                                COALESCE(combined.contact, 'N/A') AS final_contact,
                                combined.last_seen
                            FROM users u
                            LEFT JOIN (
                                SELECT first_name, middle_name, last_name, id_number, contact, email, MAX(date_visited) as last_seen
                                FROM (
                                    SELECT first_name, middle_name, last_name, id_number, contact, email, date_visited FROM active_sessions 
                                    UNION ALL 
                                    SELECT first_name, middle_name, last_name, id_number, contact, email, date_visited FROM library_logs
                                ) AS union_table
                                GROUP BY email
                            ) AS combined ON u.email = combined.email 
                            WHERE u.email IS NOT NULL AND u.email != '' 
                            ORDER BY combined.last_seen DESC, u.last_name ASC";
                
                $resUser = $conn->query($sqlUser);
                
                if ($resUser && $resUser->num_rows > 0):
                    while($row = $resUser->fetch_assoc()):
                        $isBlocked = (isset($row['is_blocked']) && $row['is_blocked'] == 1);
                        $rawEmail = $row['email'];
                        $noLogs = is_null($row['last_seen']);
                        $currentRole = strtolower($row['role']); 
                ?>
                <tr data-email="<?php echo htmlspecialchars($rawEmail); ?>" 
                    style="<?php echo $isBlocked ? 'background-color: #fff5f5;' : ($noLogs ? 'background-color: #fffdf0;' : ''); ?>">
                    
                    <td><?php echo strtoupper(htmlspecialchars($row['final_fname'])); ?></td>
                    <td><?php echo strtoupper(htmlspecialchars($row['final_mname'] ?: '-')); ?></td>
                    <td><?php echo strtoupper(htmlspecialchars($row['final_lname'])); ?></td>
                    
                    <td class="id-cell" style="font-weight: bold; color: #2c3e50;">
                        <?php echo htmlspecialchars($row['final_id']); ?>
                    </td>
                    
                    <td><?php echo htmlspecialchars($row['final_contact']); ?></td>
                    
                    <td style="text-transform: lowercase;">
                        <strong><?php echo htmlspecialchars($rawEmail); ?></strong>
                        <?php if($noLogs): ?>
                            <br><small style="color: #e67e22; font-style: italic; font-weight: bold;">(Registered - No Logs Found)</small>
                        <?php endif; ?>
                    </td>
                    
                    <td>
                        <div class="action-buttons" style="display: flex; gap: 5px;">
                            <button class="btn-action btn-reset" onclick="resetPassword('<?php echo $row['final_id']; ?>', '<?php echo $rawEmail; ?>')">
                                Reset
                            </button>

                            <button class="btn-action btn-block" onclick="toggleBlock(this)" <?php echo $isBlocked ? 'style="background: #2ecc71; border-color: #2ecc71; color: white;"' : ''; ?>>
                                <?php echo $isBlocked ? 'Unblock' : 'Block'; ?>
                            </button>

                            <?php if ($currentRole === 'ADMIN'): ?>
                                <button class="btn-action" 
                                        onclick="changeRole('<?php echo $row['email']; ?>', 'USER')" 
                                        style="background: #34495e; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; text-transform: uppercase; font-size: 0.7rem; font-weight: bold;">
                                    REVOKE ADMIN
                                </button>
                            <?php else: ?>
                                <button class="btn-action" 
                                        onclick="changeRole('<?php echo $row['email']; ?>', 'ADMIN')" 
                                        style="background: #9b59b6; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; text-transform: uppercase; font-size: 0.7rem; font-weight: bold;">
                                    MAKE ADMIN
                                </button>
                            <?php endif; ?>

                            <button class="btn-action btn-delete" onclick="deleteUser(this)">
                                Delete
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="7" style="text-align:center; padding: 20px;">No registered users found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="flagged-section" class="content-section">
    <div class="table-container">
        <h2 style="text-transform: uppercase; color: var(--neu-red); margin-bottom: 10px;">Flagged Records History</h2>
        
        <div style="margin-bottom: 20px;">
            <input type="text" id="flagSearch" class="search-bar" onkeyup="searchFlaggedTable()" placeholder="SEARCH NAME OR ID NUMBER..." style="max-width: 400px; text-transform: uppercase;">
        </div>

        <p style="font-size: 0.8rem; color: #666; margin-bottom: 20px;">ALL FLAGGED LOGS WILL APPEAR HERE FOR REVIEW AND AUDIT PURPOSES.</p>
        
        <table id="flagTable">
            <thead>
                <tr>
                    <th>Date Flagged</th>
                    <th>Full Name</th>
                    <th>ID Number</th>
                    <th>Reason for Flagging</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                
                    $sql = "SELECT * FROM visitor_flags ORDER BY flagged_at DESC";
                    $result = $conn->query($sql);

                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $formattedDate = strtoupper(date("M d, Y | h:i A", strtotime($row['flagged_at'])));
                            $db_id = $row['id'];
                            
                            echo "<tr>";
                            echo "<td>" . $formattedDate . "</td>";
                            echo "<td class='name-cell'>" . htmlspecialchars(strtoupper($row['student_name'])) . "</td>";
                            echo "<td class='id-cell'>" . htmlspecialchars(strtoupper($row['student_id'])) . "</td>";
                            echo "<td class='reason-text' style='color: #c62828; font-weight: bold;'>" . htmlspecialchars(strtoupper($row['reason'])) . "</td>";
                            echo "<td>
                                    <div style='display: flex; gap: 8px;'>
                                        <button class='btn-action btn-edit' style='background-color: #00897b;' onclick=\"editFlagReason(this, '$db_id')\">EDIT</button>
                                        <button class='btn-action btn-flag' onclick=\"removeFlag(this, '$db_id')\">REMOVE</button>
                                    </div>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr class='no-records'><td colspan='5' style='text-align:center;'>NO FLAGGED RECORDS FOUND</td></tr>";
                    }
                
                ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal" id="addModal">
    <div class="modal-content" style="max-width: 450px; max-height: 90vh; overflow-y: auto; padding: 20px;">
        
        <h2 id="modalTitle" style="margin-top: 0;">Add New Visitor</h2>

        <div style="display: flex; gap: 10px;">
            <div class="form-group" style="flex: 1;"><label>First Name <span style="color: red;">*</span></label><input type="text" id="mFirstName" placeholder="FIRST NAME"></div>
            <div class="form-group" style="flex: 1;"><label>Middle Name</label><input type="text" id="mMiddleName" placeholder="MIDDLE NAME"></div>
        </div>
        
        <div style="display: flex; gap: 10px;">
            <div class="form-group" style="flex: 2;"><label>Last Name <span style="color: red;">*</span></label><input type="text" id="mLastName" placeholder="LAST NAME"></div>
            <div class="form-group" style="flex: 1;"><label>Suffix</label><input type="text" id="mSuffix" placeholder="JR, III, ET."></div>
        </div>

        <div class="form-group"><label>Contact Number</label><input type="text" id="mContact" placeholder="09XXXXXXXXX"></div>
        <div class="form-group"><label>ID Number</label><input type="text" id="mID" placeholder="XX-XXXXX-XXX"></div>

        <div style="display: flex; gap: 10px;">
            <div class="form-group" style="flex: 1;">
                <label>Category <span style="color: red;">*</span></label>
                <select id="mCategory">
                    <option value="" disabled selected>SELECT</option>
                    <option>GUEST</option><option>STUDENT</option><option>FACULTY</option><option>EMPLOYEE</option>
                </select>
            </div>
            <div class="form-group" style="flex: 1;"><label>Program</label><input type="text" id="mProgram" placeholder="E.G. BSIT"></div>
        </div>

        <div class="form-group">
            <label>Purpose <span style="color: red;">*</span></label>
            <select id="mPurpose" onchange="toggleOthersInput(this)">
                <option value="" disabled selected>SELECT PURPOSE</option>
                <option value="STUDY">STUDY</option>
                <option value="BORROWING">BORROWING</option>
                <option value="RETURNING">RETURNING</option>
                <option value="INTERNET USE">INTERNET USE</option>
                <option value="MEETING">MEETING</option>
                <option value="RESEARCH">RESEARCH</option>
                <option value="GROUP STUDY">GROUP STUDY</option>
                <option value="PRINTING/PHOTOCOPY">PRINTING / PHOTOCOPY</option>
                <option value="ACADEMIC REQUIREMENT">ACADEMIC REQUIREMENT</option>
                <option value="OTHERS">OTHERS (PLEASE SPECIFY)</option>
            </select>
        </div>

        <div class="form-group" id="otherPurposeContainer" style="display: none; margin-top: 10px;">
            <label>Please Specify Purpose <span style="color: red;">*</span></label>
            <input type="text" id="mOtherPurpose" placeholder="TYPE YOUR PURPOSE HERE...">
        </div>

        <div class="modal-actions">
            <button class="btn-cancel" onclick="closeModal()">Cancel</button>
            <button class="btn-save" id="saveBtn" onclick="saveVisitor()">Save Visitor</button>
        </div>

    </div>
</div>
    
<script>
/* --- 1. GLOBAL STATE & CONFIG --- */
let vChart, pChart;
let editMode = false;
let editingRow = null;
let oldID = "";

const purposeConfig = {
    "STUDY": { label: "STUDY", color: "#e53935" },
    "BORROWING": { label: "BORROWING", color: "#fdd835" },
    "RETURNING": { label: "RETURNING", color: "#43a047" },
    "INTERNET USE": { label: "INTERNET USE", color: "#1e88e5" },
    "MEETING": { label: "MEETING", color: "#00897b" }, 
    "RESEARCH": { label: "RESEARCH", color: "#fb8c00" },
    "GROUP STUDY": { label: "GROUP STUDY", color: "#8e24aa" },
    "PRINTING/PHOTOCOPY": { label: "PRINTING / PHOTOCOPY", color: "#6d4c41" },
    "ACADEMIC REQUIREMENT": { label: "ACADEMIC REQUIREMENT", color: "#546e7a" },
    "OTHERS": { label: "OTHERS", color: "#f06292" }
};

/* --- 2. INITIALIZATION & REFRESH --- */
document.addEventListener('DOMContentLoaded', () => {
    initCharts(); 
    refreshDashboard(); 
    const uSearch = document.getElementById('userSearch');
    if(uSearch) uSearch.addEventListener('keyup', searchUsers);
    setInterval(updateLiveInfo, 1000); 
});

function refreshDashboard() {
    updateStats(); 
    updateCharts();
}

function updateLiveInfo() {
    const now = new Date();
    const clockEl = document.getElementById('clock');
    const dateEl = document.getElementById('date');
    if(clockEl) clockEl.innerText = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true }).toUpperCase();
    if(dateEl) dateEl.innerText = now.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'short', day: '2-digit' }).toUpperCase();
}

/* --- 3. NAVIGATION CONTROLS --- */
function showSection(sectionId, element) {
    document.querySelectorAll('.content-section').forEach(s => {
        s.classList.remove('active');
        s.style.display = 'none'; 
    });

    const activeSection = document.getElementById(sectionId);
    if (activeSection) {
        activeSection.classList.add('active');
        activeSection.style.display = 'block';
    }

    document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
    
    if (element) {
        element.classList.add('active');
    } else {
        const linkToHighlight = document.querySelector(`.nav-link[onclick*="${sectionId}"]`);
        if (linkToHighlight) linkToHighlight.classList.add('active');
    }

    localStorage.setItem('lastSection', sectionId);
}

window.addEventListener('DOMContentLoaded', () => {
    const savedSection = localStorage.getItem('lastSection');
    if (savedSection) {
        showSection(savedSection, null);
    } else {
        showSection('visitor-section', null); 
    }
});

/* --- 4. MODAL & FORM UI --- */
function openModal() { document.getElementById('addModal').style.display = 'flex'; }

function closeModal() {
    document.getElementById('addModal').style.display = "none";
    editMode = false;
    editingRow = null;
    oldIDForUpdate = ""; 
    document.getElementById('modalTitle').innerText = "➕ ADD NEW VISITOR";
    document.getElementById('saveBtn').innerText = "SAVE VISITOR";
    const ids = ['mFirstName', 'mMiddleName', 'mLastName', 'mSuffix', 'mID', 'mOtherPurpose', 'mProgram', 'mContact', 'mSessionId'];
    ids.forEach(id => {
        const el = document.getElementById(id);
        if(el) el.value = "";
    });
    ['mCategory', 'mPurpose'].forEach(id => {
        const el = document.getElementById(id);
        if(el) el.selectedIndex = 0;
    });
    const otherContainer = document.getElementById('otherPurposeContainer');
    if(otherContainer) otherContainer.style.display = 'none';
}

function resetForm() {
    editMode = false; editingRow = null;
    document.getElementById('modalTitle').innerText = "ADD NEW VISITOR";
    document.getElementById('saveBtn').innerText = "SAVE VISITOR";
    document.querySelectorAll('#addModal input, #addModal select').forEach(input => input.value = '');
    document.getElementById('otherPurposeContainer').style.display = 'none';
}

function toggleOthersInput(select) {
    document.getElementById('otherPurposeContainer').style.display = select.value === 'OTHERS' ? 'block' : 'none';
}

/* --- 5. DATA VISUALIZATION --- */
function initCharts() {
    const canvas1 = document.getElementById('visitorChart');
    const canvas2 = document.getElementById('purposeChart');
    if (!canvas1 || !canvas2) return; 
    if (vChart instanceof Chart) { vChart.destroy(); }
    if (pChart instanceof Chart) { pChart.destroy(); }
    const ctx1 = canvas1.getContext('2d');
    vChart = new Chart(ctx1, {
        type: 'line',
        data: {
            labels: ['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'],
            datasets: [{ 
                label: 'VISITORS', 
                data: [0, 0, 0, 0, 0, 0], 
                borderColor: '#004aad', 
                tension: 0.4, 
                fill: true, 
                backgroundColor: 'rgba(0, 74, 173, 0.1)' 
            }]
        },
        options: { 
            maintainAspectRatio: false, 
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });
    const ctx2 = canvas2.getContext('2d');
    pChart = new Chart(ctx2, {
        type: 'doughnut',
        data: { labels: [], datasets: [{ data: [], backgroundColor: [] }] },
        options: { 
            maintainAspectRatio: false,
            cutout: '70%', 
            plugins: {
                legend: {
                    display: true,
                    position: 'right',
                    labels: { boxWidth: 10, padding: 8, font: { size: 9, weight: '600' } }
                }
            }
        }
    });
}

function updateCharts() {
    const rows = document.querySelectorAll("#visitorTable tbody tr");
    const weeklyData = [0, 0, 0, 0, 0, 0]; 
    const purposeCounts = {};
    let totalPurposeCount = 0;
    
    const uniqueUsersPerDay = [new Set(), new Set(), new Set(), new Set(), new Set(), new Set()];
    const uniqueUsersPurpose = new Set();

    Object.keys(purposeConfig).forEach(key => purposeCounts[key] = 0);
    const now = new Date();
    const currentYear = now.getFullYear();

    rows.forEach(row => {
        if (row.style.display === 'none') return;
        const dataIn = row.getAttribute('data-in-time'); 
        if (!dataIn || dataIn === "-") return;

        const studentId = row.querySelector('.id-cell')?.innerText.trim();
        const p = dataIn.split('-'); 
        const rowDate = new Date(p[0], p[1] - 1, p[2]);
        const day = rowDate.getDay(); 

        if (rowDate.getFullYear() === currentYear && day >= 1 && day <= 6) {
            const dayIndex = day - 1;

            if (!uniqueUsersPerDay[dayIndex].has(studentId)) {
                uniqueUsersPerDay[dayIndex].add(studentId);
                weeklyData[dayIndex]++;
            }

            if (!uniqueUsersPurpose.has(studentId)) {
                uniqueUsersPurpose.add(studentId);
                const pCell = row.querySelector('.purpose-cell');
                if (pCell) {
                    let pName = (pCell.getAttribute('data-original-purpose') || pCell.innerText).trim().toUpperCase();
                    const configKey = Object.keys(purposeConfig).find(k => k === pName || purposeConfig[k].label === pName);
                    
                    if (configKey) {
                        purposeCounts[configKey]++;
                        totalPurposeCount++;
                    } else {
                        purposeCounts["OTHERS"]++; 
                        totalPurposeCount++;
                    }
                }
            }
        }
    });

    if (vChart) {
        vChart.data.datasets[0].data = weeklyData;
        vChart.update();
    }

    if (pChart) {
        if (totalPurposeCount === 0) {
            pChart.data.labels = ["NO DATA"];
            pChart.data.datasets[0].data = [1];
            pChart.data.datasets[0].backgroundColor = ["#e0e0e0"];
        } else {
            pChart.data.labels = Object.keys(purposeConfig).map(k => purposeConfig[k].label);
            pChart.data.datasets[0].data = Object.keys(purposeConfig).map(k => purposeCounts[k]);
            pChart.data.datasets[0].backgroundColor = Object.keys(purposeConfig).map(k => purposeConfig[k].color);
        }
        pChart.update();
    }
}

/* --- 6. STATISTICS CALCULATION --- */
function updateStats() {
    const rows = document.querySelectorAll("#visitorTable tbody tr");
    
    const insideSet = new Set();
    const todaySet = new Set();
    const weekSet = new Set();
    const monthSet = new Set();

    const now = new Date();
    const todayStr = now.toDateString();
    
    const startOfToday = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const sevenDaysAgo = new Date(startOfToday);
    sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 7);
    
    const firstDayOfLastMonth = new Date(now.getFullYear(), now.getMonth() - 1, 1);
    const lastDayOfLastMonth = new Date(now.getFullYear(), now.getMonth(), 0);

    rows.forEach(row => {
        const dataIn = row.getAttribute('data-in-time');
        if (!dataIn || dataIn === "-") return;

        const studentId = row.querySelector('.id-cell')?.innerText.trim();
        const rowDate = new Date(dataIn);
        const status = row.querySelector('.badge');

        if (status && status.innerText.trim().toUpperCase() === 'INSIDE') {
            insideSet.add(studentId);
        }

        if (rowDate.toDateString() === todayStr) {
            todaySet.add(studentId);
        }

        if (rowDate < startOfToday && rowDate >= sevenDaysAgo) {
            weekSet.add(studentId);
        }

        if (rowDate >= firstDayOfLastMonth && rowDate <= lastDayOfLastMonth) {
            monthSet.add(studentId);
        }
    });

    if (document.getElementById('capacityCount')) document.getElementById('capacityCount').innerText = insideSet.size;
    if (document.getElementById('todayCount')) document.getElementById('todayCount').innerText = todaySet.size;
    if (document.getElementById('lastWeekCount')) document.getElementById('lastWeekCount').innerText = weekSet.size;
    if (document.getElementById('lastMonthCount')) document.getElementById('lastMonthCount').innerText = monthSet.size;
    
    const flaggedCount = document.querySelectorAll("#flagTable tbody tr:not(.no-records)").length;
    if (document.getElementById('flaggedCount')) document.getElementById('flaggedCount').innerText = flaggedCount;
}

/** --- 1. DATA FILTERING --- **/
function filterByTime(range, btn) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    if(btn) btn.classList.add('active');
    const rows = document.querySelectorAll('#visitorTable tbody tr');
    const now = new Date();
    now.setHours(0, 0, 0, 0); 
    const currentYear = now.getFullYear();
    const todayStr = `${currentYear}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
    const yesterday = new Date(now);
    yesterday.setDate(now.getDate() - 1);
    const dayOfWeek = now.getDay(); 
    const diffToLastMonday = (dayOfWeek === 0 ? 6 : dayOfWeek - 1) + 7;
    const lastMonday = new Date(now);
    lastMonday.setDate(now.getDate() - diffToLastMonday);
    const firstDayLastMonth = new Date(now.getFullYear(), now.getMonth() - 1, 1);
    const lastDayLastMonth = new Date(now.getFullYear(), now.getMonth(), 0);

    rows.forEach(row => {
        const rowDateAttr = row.getAttribute('data-in-time');
        if (!rowDateAttr || rowDateAttr === "-") { row.style.display = "none"; return; }
        const p = rowDateAttr.split('-');
        const rowDate = new Date(p[0], p[1] - 1, p[2]);
        rowDate.setHours(0, 0, 0, 0);
        let show = false;
        if (range === 'all') { show = (rowDate >= firstDayLastMonth); } 
        else if (range === 'today') { show = (rowDateAttr === todayStr); } 
        else if (range === 'lastweek') { show = (rowDate >= lastMonday && rowDate <= yesterday); } 
        else if (range === 'lastmonth') { show = (rowDate >= firstDayLastMonth && rowDate <= lastDayLastMonth); }
        row.style.display = show ? "" : "none";
    });
    if (typeof updateStats === "function") updateStats();
    if (typeof updateCharts === "function") updateCharts();
}

/** --- 2. VISITOR DATA PERSISTENCE --- **/
async function saveVisitor() {
    const fName = document.getElementById('mFirstName').value.trim().toUpperCase();
    const lName = document.getElementById('mLastName').value.trim().toUpperCase();
    const cat   = document.getElementById('mCategory').value.toUpperCase();
    const purpSelect = document.getElementById('mPurpose').value;
    const idNum = document.getElementById('mID').value.trim().toUpperCase() || "N/A";

    if(!fName || !lName || !cat || !purpSelect) { 
        alert("⚠️ PLEASE FILL IN ALL REQUIRED FIELDS (FIRST NAME, LAST NAME, USER TYPE, AND REASON)."); 
        return; 
    }

    if (editMode && editingRow) {
        const statusEl = editingRow.querySelector('.badge') || editingRow.querySelector('.status-cell');
        const currentStatus = statusEl ? statusEl.innerText.trim().toUpperCase() : "";
        if (currentStatus === 'INSIDE') {
            const proceed = confirm("🔔 NOTICE: THIS VISITOR IS CURRENTLY INSIDE THE LIBRARY. PROFILE CHANGES WILL REFLECT ON THE ACTIVE SESSION. CONTINUE?");
            if (!proceed) return;
        }
    }

    if (idNum !== "N/A" && (!editMode || (editMode && idNum !== oldIDForUpdate))) {
        try {
            const checkResponse = await fetch(`check_id_conflict.php?id_number=${encodeURIComponent(idNum)}`);
            const checkData = await checkResponse.json();
            if (checkData.exists) {
                const msg = `⚠️ ID CONFLICT DETECTED!\n\nID [${idNum}] IS ALREADY OWNED BY: ${checkData.name}.\nMERGE THIS RECORD?`;
                if (!confirm(msg)) return;
            }
        } catch (e) { console.error("CONFLICT CHECK FAILED", e); }
    }

    const formData = new FormData();
    formData.append('first_name', fName);
    formData.append('middle_name', document.getElementById('mMiddleName').value.trim().toUpperCase());
    formData.append('last_name', lName);
    formData.append('suffix', document.getElementById('mSuffix').value.trim().toUpperCase());
    formData.append('id_number', idNum);
    formData.append('contact', document.getElementById('mContact').value.trim());
    formData.append('user_type', cat);
    formData.append('course', document.getElementById('mProgram').value.trim().toUpperCase() || 'N/A');
    formData.append('reason', purpSelect);
    formData.append('others_detail', document.getElementById('mOtherPurpose').value.trim().toUpperCase());

    let url = 'add_visitor.php';
    if (!editMode) {
        const today = new Date();
        formData.append('date_visited', today.toLocaleDateString('en-CA')); 
    } else if (editMode && editingRow) {
        url = 'update_process.php';
        formData.append('old_id_number', oldIDForUpdate);
        const sessionId = document.getElementById('mSessionId')?.value || editingRow.getAttribute('data-id');
        formData.append('session_id', sessionId);
    }

    fetch(url, { method: 'POST', body: formData })
    .then(response => {
        if (!response.ok) throw new Error('NETWORK ERROR');
        return response.json();
    })
    .then(data => {
        if (data.success || data.status === 'success') {
            alert(editMode ? "✅ RECORDS UPDATED SUCCESSFULLY!" : "✅ VISITOR ADDED SUCCESSFULLY!");
            location.reload(); 
        } else {
            alert("❌ ERROR: " + (data.message || "UNKNOWN ERROR"));
        }
    })
    .catch(error => {
        console.error("ERROR:", error);
        alert("⚠️ CONNECTION ERROR: FAILED TO SAVE RECORD.");
    });
}

function editRow(btn) {
    editMode = true;
    editingRow = btn.closest('tr');
    const sessionId = editingRow.getAttribute('data-id');
    const fName = editingRow.getAttribute('data-fname');
    const mName = editingRow.getAttribute('data-mname');
    const lName = editingRow.getAttribute('data-lname');
    const suffix = editingRow.getAttribute('data-suffix');
    const category = editingRow.getAttribute('data-category');
    const program = editingRow.getAttribute('data-program');
    const contact = editingRow.getAttribute('data-contact'); 
    const currentID = editingRow.querySelector('.id-cell').innerText.trim();
    oldIDForUpdate = currentID; 
    const purposeCell = editingRow.querySelector('.purpose-cell');
    const purpKey = purposeCell.getAttribute('data-original-purpose');
    const othersInfo = purposeCell.getAttribute('data-others-info');

    if(document.getElementById('mSessionId')) document.getElementById('mSessionId').value = sessionId;
    document.getElementById('mFirstName').value = fName || "";
    document.getElementById('mMiddleName').value = mName || "";
    document.getElementById('mLastName').value = lName || "";
    if(document.getElementById('mSuffix')) document.getElementById('mSuffix').value = suffix || "";
    if(document.getElementById('mContact')) document.getElementById('mContact').value = contact || "";
    document.getElementById('mID').value = currentID; 
    document.getElementById('mCategory').value = category;
    document.getElementById('mProgram').value = program;
    document.getElementById('mPurpose').value = purpKey;

    const otherContainer = document.getElementById('otherPurposeContainer');
    if(purpKey === 'OTHERS') {
        if(otherContainer) otherContainer.style.display = 'block';
        document.getElementById('mOtherPurpose').value = othersInfo || "";
    } else {
        if(otherContainer) otherContainer.style.display = 'none';
        document.getElementById('mOtherPurpose').value = "";
    }
    document.getElementById('modalTitle').innerText = "📝 EDIT VISITOR RECORD";
    document.getElementById('saveBtn').innerText = "UPDATE RECORD";
    openModal();
}

/** --- 3. SESSION MANAGEMENT --- **/
function handleOut(sessionId) {
    if (!confirm("CONFIRM USER LOGOUT?")) return;
    const formData = new FormData();
    formData.append('session_id', sessionId);
    fetch('out_process.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("SUCCESSFULLY LOGGED OUT.");
            location.reload(); 
        } else {
            alert("ERROR: " + data.error);
        }
    })
    .catch(error => {
        console.error('ERROR:', error);
        alert("SERVER COMMUNICATION FAILED.");
    });
}

function deleteRow(btn) {
    const row = btn.closest('tr');
    const id = row.getAttribute('data-id'); 
    const visitorName = row.querySelector('.name-cell') ? row.querySelector('.name-cell').innerText : "THIS RECORD";
    if (!id) { alert("ERROR: RECORD ID NOT FOUND. CANNOT DELETE."); return; }
    if (!confirm(`ARE YOU SURE YOU WANT TO DELETE THE LOG FOR ${visitorName}?\n\nTHIS WILL ALSO CLEAN UP THE MASTERLIST IF THEY HAVE NO OTHER LOGS.`)) return;
    const formData = new FormData();
    formData.append('id', id);
    fetch('delete_visitor.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert("RECORD DELETED SUCCESSFULLY.");
            row.style.transition = "all 0.3s ease";
            row.style.transform = "scale(0.8)";
            row.style.opacity = "0";
            setTimeout(() => {
                row.remove();
                if (typeof refreshDashboard === 'function') refreshDashboard();
            }, 300);
        } else {
            alert("SERVER ERROR: " + (data.message || "FAILED TO DELETE RECORD."));
        }
    })
    .catch(error => {
        console.error('ERROR:', error);
        alert("CONNECTION ERROR: COULD NOT CONNECT TO THE DELETION SCRIPT.");
    });
}

/** --- 4. FLAGGING SYSTEM --- **/
function toggleManualFlag(btn) {
    const row = btn.closest('tr');
    const name = row.querySelector('.name-cell').innerText.toUpperCase();
    const idNum = row.querySelector('.id-cell').innerText.toUpperCase();
    const flagTableBody = document.querySelector("#flagTable tbody");

    let rawReason = prompt(`REASON FOR FLAGGING ${name}:`, "");
    
    if (rawReason !== null && rawReason.trim() !== "") {
        const reason = rawReason.toUpperCase(); 

        const formData = new FormData();
        formData.append('student_id', idNum);
        formData.append('student_name', name);
        formData.append('reason', reason);
        formData.append('action', 'flag');

        fetch('flag_process.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const db_id = data.id; 
                const newFlagRow = `
                    <tr>
                        <td>${new Date().toLocaleString().toUpperCase()}</td>
                        <td>${name}</td>
                        <td>${idNum}</td>
                        <td class="reason-text" style="color: #e74c3c; font-weight: bold;">${reason}</td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <button class="btn-action btn-edit" style="background-color: #00897b;" onclick="editFlagReason(this, '${db_id}')">EDIT</button>
                                <button class="btn-action btn-flag" onclick="removeFlag(this, '${db_id}')">REMOVE</button>
                            </div>
                        </td>
                    </tr>`;
                
                if(flagTableBody) flagTableBody.insertAdjacentHTML('afterbegin', newFlagRow);
                
                alert("✅ RECORD FLAGGED SUCCESSFULLY.");
                location.reload();
            } else {
                alert("❌ ERROR: " + (data.error || "Could not save flag."));
            }
        })
        .catch(err => console.error("Fetch Error:", err));
    }
}

function removeFlag(btn, flagId) {
    if(!confirm("REMOVE THIS SPECIFIC FLAG RECORD?")) return;

    const formData = new FormData();
    formData.append('flag_id', flagId); 
    formData.append('action', 'delete_flag');

    fetch('flag_process.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            btn.closest('tr').remove(); 
            alert("✅ FLAG REMOVED.");
            location.reload();
        } else {
            alert("❌ DELETE FAILED.");
        }
    })
    .catch(err => console.error("Fetch Error:", err));
}

function editFlagReason(btn, flagId) {
    const row = btn.closest('tr');
    const rawReason = prompt("ENTER NEW REASON FOR FLAGGING:", "");

    if (rawReason !== null && rawReason.trim() !== "") {
        const newReason = rawReason.toUpperCase(); 
        const formData = new FormData();
        formData.append('flag_id', flagId);
        formData.append('reason', newReason);
        formData.append('action', 'edit_reason');

        fetch('flag_process.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                row.querySelector('.reason-text').innerText = newReason;
                alert("✅ REASON UPDATED SUCCESSFULLY.");
            } else {
                alert("❌ UPDATE FAILED.");
            }
        })
        .catch(err => console.error("Fetch Error:", err));
    }
}

/** --- 1. USER ACCOUNT CONTROLS --- **/
function toggleBlock(btn) {
    const row = btn.closest('tr');
    const email = row.getAttribute('data-email');
    const isUnblocking = btn.innerText.toLowerCase().includes('unblock');
    const action = isUnblocking ? 'unblock' : 'block';

    if (!email || email === 'no-email@neu.edu.ph') {
        alert("⚠️ ERROR: INVALID OR MISSING EMAIL ADDRESS.");
        return;
    }

    if (!confirm(`⚠️ ARE YOU SURE YOU WANT TO ${action.toUpperCase()} THIS USER?`)) return;

    const formData = new FormData();
    formData.append('email', email);
    formData.append('action', action);
    
    if (action === 'block') {
        formData.append('auto_out', 'true');
    }

    fetch('toggle_block.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const successMsg = data.auto_out_triggered ? 
                "✅ USER BLOCKED & AUTOMATICALLY LOGGED OUT." : 
                "✅ SUCCESS: " + data.message.toUpperCase();
            
            alert(successMsg);
            location.reload(); 
        } else {
            alert("❌ SERVER ERROR: " + data.message.toUpperCase());
        }
    })
    .catch(error => {
        console.error('FETCH ERROR:', error);
        alert("🚨 CONNECTION ERROR: FAILED TO REACH THE SERVER.");
    });
}

function deleteUser(btn) {
    const row = btn.closest('tr');
    const email = row.getAttribute('data-email');
    const idNum = row.querySelector('.id-cell').innerText.trim();
    if (!email || email === 'no-email@neu.edu.ph') { alert("CRITICAL ERROR: CANNOT DELETE ACCOUNT WITHOUT A VALID EMAIL REFERENCE."); return; }
    if (!confirm(`⚠️ WARNING: ARE YOU SURE YOU WANT TO PERMANENTLY DELETE THE ACCOUNT FOR ${email}?`)) return;
    if (!confirm("FINAL WARNING: THIS WILL ALSO REMOVE ALL THEIR LIBRARY LOGS AND ACTIVE SESSIONS. THIS ACTION CANNOT BE UNDONE. PROCEED?")) return;
    const formData = new FormData();
    formData.append('email', email);
    formData.append('id_number', idNum);
    fetch('delete_user.php', { method: 'POST', body: formData })
    .then(response => response.text())
    .then(data => {
        if (data.trim() === "success") {
            alert("ACCOUNT AND ALL ASSOCIATED RECORDS HAVE BEEN DELETED.");
            row.style.transition = "all 0.4s ease";
            row.style.transform = "translateX(20px)";
            row.style.opacity = "0";
            setTimeout(() => {
                row.remove();
                if (typeof refreshDashboard === 'function') refreshDashboard();
            }, 400);
        } else {
            alert("DATABASE ERROR: THE SERVER COULD NOT COMPLETE THE DELETION.");
        }
    })
    .catch(error => {
        console.error('ERROR:', error);
        alert("CONNECTION ERROR: FAILED TO REACH THE DELETION SERVICE.");
    });
}

function resetPassword(idNum, email) {
    if (!email || email === 'no-email@neu.edu.ph' || email === '') { alert("RESET DENIED: THIS USER DOES NOT HAVE A VALID EMAIL ADDRESS REGISTERED."); return; }
    if (!confirm(`SEND A PASSWORD RESET LINK TO ${email}?\n\nNOTE: THE LINK WILL EXPIRE IN 2 MINUTES.`)) return;
    const btn = event.target;
    const originalText = btn.innerText;
    btn.innerText = "Sending...";
    btn.disabled = true;
    const formData = new FormData();
    formData.append('email', email);
    fetch('generate_reset_link.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') { alert("SUCCESS: " + data.message); }
        else { alert("ERROR: " + data.message); }
    })
    .catch(error => {
        console.error('ERROR:', error);
        alert("CONNECTION ERROR: FAILED TO REACH THE PASSWORD RESET SERVICE.");
    })
    .finally(() => {
        btn.innerText = originalText;
        btn.disabled = false;
    });
}

function changeRole(email, newRole) {
    const roleUpper = newRole.toUpperCase(); 
    const actionText = (roleUpper === 'ADMIN') 
        ? "PROMOTE THIS USER TO ADMIN?" 
        : "REVOKE ADMIN PRIVILEGES AND SWITCH TO USER?";
    
    if (confirm("ARE YOU SURE? \n" + actionText)) {
        const formData = new FormData();
        formData.append('email', email);
        formData.append('role', roleUpper);

        fetch('update_role.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' || data.status === 'ok') {
                alert("✅ SUCCESS: " + data.message.toUpperCase());
                location.reload(); 
            } else {
                alert("❌ ERROR: " + (data.message ? data.message.toUpperCase() : 'FAILED TO UPDATE ROLE.'));
            }
        })
        .catch(err => {
            console.error("Fetch Error:", err);
            alert("❌ SYSTEM ERROR OCCURRED.");
        });
    }
}

/** --- 2. SEARCH & NAVIGATION --- **/
function searchTable() {
    const term = document.getElementById("tableSearch").value.toUpperCase();
    document.querySelectorAll("#visitorTable tbody tr").forEach(row => {
        row.style.display = row.innerText.toUpperCase().includes(term) ? "" : "none";
    });
}

function searchUsers() {
    const term = document.getElementById("userSearch").value.toUpperCase();
    document.querySelectorAll("#userTable tbody tr").forEach(row => {
        row.style.display = row.innerText.toUpperCase().includes(term) ? "" : "none";
    });
}

function confirmLogout() {
    window.location.href = 'logout.php';
}

/** --- 3. DATA EXPORT (XLS) --- **/
function exportTableToCSV() {
    const table = document.getElementById("visitorTable");
    if (!table) return;

    const activeButton = document.querySelector(".filter-btn.active");
    let filterLabel = "ALL_RECORDS"; 
    const now = new Date();
    const currentYear = 2026; 

    if (activeButton) {
        const selectedText = activeButton.innerText.toUpperCase().trim();
        if (selectedText.includes("TODAY")) {
            const month = now.getMonth() + 1;
            const day = now.getDate();
            filterLabel = `TODAY_${month}-${day}-${currentYear}`;
        } else if (selectedText.includes("LAST WEEK")) {
            const lastMon = new Date();
            lastMon.setDate(now.getDate() - (now.getDay() + 6) % 7 - 7); 
            const lastSat = new Date(lastMon);
            lastSat.setDate(lastMon.getDate() + 5); 
            const m = lastMon.getMonth() + 1;
            const dStart = lastMon.getDate();
            const dEnd = lastSat.getDate();
            filterLabel = `LASTWEEK_${m}-${dStart}-${dEnd}-${currentYear}`;
        } else if (selectedText.includes("LAST MONTH")) {
            const lastMonthDate = new Date();
            lastMonthDate.setMonth(now.getMonth() - 1);
            const monthName = lastMonthDate.toLocaleString('en-US', { month: 'long' }).toUpperCase();
            filterLabel = `LASTMONTH_${monthName}_${currentYear}`;
        } else {
            filterLabel = `ALL_RECORDS_${currentYear}`;
        }
    }

    const rows = Array.from(table.querySelectorAll("tr"));
    let excelTemplate = `<?xml version="1.0" encoding="UTF-8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:html="http://www.w3.org/TR/REC-html40">
 <Styles>
  <Style ss:ID="headerStyle">
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
   <Font ss:Bold="1" ss:Color="#FFFFFF"/>
   <Interior ss:Color="#2E7D32" ss:Pattern="Solid"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#FFFFFF"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#FFFFFF"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#FFFFFF"/>
   </Borders>
  </Style>
  <Style ss:ID="cellStyle">
   <Alignment ss:Vertical="Center" ss:WrapText="1"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#EEEEEE"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#EEEEEE"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#EEEEEE"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#EEEEEE"/>
   </Borders>
  </Style>
 </Styles>
 <Worksheet ss:Name="Library Logs">
  <Table>
   <Column ss:Width="140"/> <Column ss:Width="90"/>  <Column ss:Width="120"/> <Column ss:Width="110"/> <Column ss:Width="120"/> <Column ss:Width="110"/> <Column ss:Width="100"/> <Column ss:Width="250"/> <Column ss:Width="200"/> <Column ss:Width="90"/>  `;

    let excelRows = "";
    rows.forEach((row, rowIndex) => {
        if (row.offsetParent !== null) { 
            const cols = row.querySelectorAll("th, td");
            const isHeader = rowIndex === 0;
            excelRows += isHeader ? '<Row ss:Height="25">' : '<Row ss:Height="22">';
            for (let i = 0; i < cols.length - 1; i++) {
                let style = isHeader ? 'ss:StyleID="headerStyle"' : 'ss:StyleID="cellStyle"';
                if (i === 2 && !isHeader) {
                    let fname = (row.getAttribute('data-fname') || "").toUpperCase();
                    let mname = (row.getAttribute('data-mname') || "").toUpperCase();
                    let lname = (row.getAttribute('data-lname') || "").toUpperCase();
                    let suffix = (row.getAttribute('data-suffix') || "").toUpperCase();
                    let finalLname = suffix ? `${lname} ${suffix}` : lname;
                    excelRows += `<Cell ${style}><Data ss:Type="String">${fname}</Data></Cell>`;
                    excelRows += `<Cell ${style}><Data ss:Type="String">${mname}</Data></Cell>`;
                    excelRows += `<Cell ${style}><Data ss:Type="String">${finalLname}</Data></Cell>`;
                    continue;
                } else if (i === 2 && isHeader) {
                    excelRows += `<Cell ${style}><Data ss:Type="String">FIRST NAME</Data></Cell>`;
                    excelRows += `<Cell ${style}><Data ss:Type="String">MIDDLE NAME</Data></Cell>`;
                    excelRows += `<Cell ${style}><Data ss:Type="String">LAST NAME</Data></Cell>`;
                    continue;
                } else {
                    let data = cols[i].innerText.trim().replace(/[<>&'"]/g, c => {
                        switch (c) { case '<': return '&lt;'; case '>': return '&gt;'; case '&': return '&amp;'; case '\'': return '&apos;'; case '"': return '&quot;'; default: return c; }
                    });
                    excelRows += `<Cell ${style}><Data ss:Type="String">${data.toUpperCase()}</Data></Cell>`;
                }
            }
            excelRows += "</Row>";
        }
    });

    const finalXML = excelTemplate + excelRows + "</Table></Worksheet></Workbook>";
    const blob = new Blob([finalXML], { type: 'application/vnd.ms-excel' });
    const fileName = `NEU_Library_${filterLabel}.xls`;
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = fileName;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function searchFlaggedTable() {
    const term = document.getElementById("flagSearch").value.toUpperCase();
    document.querySelectorAll("#flagTable tbody tr").forEach(row =>{
        row.style.display = row.innerText.toUpperCase().includes(term) ? "" : "none";
    });
}
</script>
</body>
</html>
