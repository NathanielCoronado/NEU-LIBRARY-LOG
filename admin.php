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
<body onload="updateCharts();, initCharts();">

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

    <!-- ===== VISITOR SECTION ===== -->
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
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
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
                        <th>Date &amp; Time In</th>
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
/**
 * VISITOR LOGS QUERIES
 *
 * Confirmed columns from debug:
 *   active_sessions : id, user_type, id_number, contact, first_name, middle_name,
 *                     last_name, email, suffix, course, reason, others_detail,
 *                     time_in, date_visited
 *                     NOTE: NO time_out column — use NULL AS time_out
 *
 *   library_logs    : id, user_type, id_number, contact, first_name, middle_name,
 *                     last_name, email, suffix, course, time_in, time_out, reason,
 *                     others_detail, date_visited, status
 *
 *   users           : id, first_name, last_name, email, status, profile_pic,
 *                     password, reset_token, token_expiry, verification_code,
 *                     is_verified, created_at, is_blocked, role
 *                     NOTE: NO middle_name column in users table
 */

$rows_all = [];

// --- Query 1: Currently inside (active_sessions) ---
$sql_active = "
    SELECT
        a.id,
        a.reason,
        a.others_detail,
        a.time_in,
        NULL        AS time_out,
        'Inside'    AS current_status,
        a.date_visited,
        a.id_number,
        a.first_name,
        a.middle_name,
        a.last_name,
        a.suffix,
        a.user_type,
        a.course,
        a.email,
        a.contact,
        u.is_blocked,
        u.role
    FROM active_sessions a
    LEFT JOIN users u ON a.email = u.email
    WHERE (u.role IS NULL OR u.role != 'ADMIN')
    ORDER BY a.date_visited DESC, a.time_in DESC
";

// --- Query 2: Logged out (library_logs) ---
$sql_logs = "
    SELECT
        l.id,
        l.reason,
        l.others_detail,
        l.time_in,
        l.time_out,
        'Out'       AS current_status,
        l.date_visited,
        l.id_number,
        l.first_name,
        l.middle_name,
        l.last_name,
        l.suffix,
        l.user_type,
        l.course,
        l.email,
        l.contact,
        u.is_blocked,
        u.role
    FROM library_logs l
    LEFT JOIN users u ON l.email = u.email
    WHERE (u.role IS NULL OR u.role != 'ADMIN')
    ORDER BY l.date_visited DESC, l.time_in DESC
";

$r1 = $conn->query($sql_active);
if ($r1) {
    while ($row = $r1->fetch_assoc()) $rows_all[] = $row;
} else {
    error_log("active_sessions query error: " . $conn->error);
}

$r2 = $conn->query($sql_logs);
if ($r2) {
    while ($row = $r2->fetch_assoc()) $rows_all[] = $row;
} else {
    error_log("library_logs query error: " . $conn->error);
}

// Sort merged results: newest first
usort($rows_all, function($a, $b) {
    return strcmp(
        $b['date_visited'] . ' ' . $b['time_in'],
        $a['date_visited'] . ' ' . $a['time_in']
    );
});

/** --- RENDER ROWS --- **/
if (!empty($rows_all)):
    foreach ($rows_all as $row):

        // Build middle initial from middle_name
        $mname = trim($row['middle_name'] ?? '');
        $mi = '';
        if (!empty($mname)) {
            foreach (explode(' ', $mname) as $w)
                $mi .= strtoupper(substr($w, 0, 1)) . '.';
            $mi = ' ' . $mi;
        }
        $sfx      = !empty($row['suffix']) ? ' ' . $row['suffix'] : '';
        $fullName = strtoupper(($row['last_name'] ?? '') . ', ' . ($row['first_name'] ?? '') . $mi . $sfx);

        $isBlocked = (isset($row['is_blocked']) && $row['is_blocked'] == 1);
        $isInside  = ($row['current_status'] === 'Inside');

        $formattedIn  = date('M d, Y', strtotime($row['date_visited']))
                      . ' | '
                      . date('h:i A', strtotime($row['time_in']));

        $timeOutDisplay = $isInside
            ? '<span style="color:#999;">--:--</span>'
            : '<span style="color:#e74c3c;font-weight:bold;">'
              . date('h:i A', strtotime($row['time_out'] ?? '00:00:00'))
              . '</span>';
?>
    <tr data-id="<?php echo (int)$row['id']; ?>"
        data-fname="<?php echo htmlspecialchars($row['first_name']   ?? ''); ?>"
        data-mname="<?php echo htmlspecialchars($row['middle_name']  ?? ''); ?>"
        data-lname="<?php echo htmlspecialchars($row['last_name']    ?? ''); ?>"
        data-suffix="<?php echo htmlspecialchars($row['suffix']      ?? ''); ?>"
        data-category="<?php echo htmlspecialchars($row['user_type'] ?? ''); ?>"
        data-program="<?php echo htmlspecialchars($row['course']     ?? ''); ?>"
        data-email="<?php echo htmlspecialchars($row['email']        ?? ''); ?>"
        data-contact="<?php echo htmlspecialchars($row['contact']    ?? ''); ?>"
        data-in-time="<?php echo date('Y-m-d', strtotime($row['date_visited'])); ?>"
        class="<?php echo $isBlocked ? 'blocked-row' : ($isInside ? 'active-row' : 'logged-out-row'); ?>"
        style="<?php echo $isBlocked ? 'background-color:#fcfcfc;' : ''; ?>">

        <td class="dt-cell"><?php echo $formattedIn; ?></td>
        <td class="time-out-cell"><?php echo $timeOutDisplay; ?></td>
        <td class="name-cell" style="font-weight:600;"><?php echo htmlspecialchars($fullName); ?></td>
        <td class="id-cell"><?php echo htmlspecialchars($row['id_number'] ?? ''); ?></td>
        <td><?php echo htmlspecialchars($row['user_type'] ?? ''); ?></td>
        <td><?php echo htmlspecialchars($row['course']    ?? ''); ?></td>
        <td class="purpose-cell"
            data-original-purpose="<?php echo htmlspecialchars($row['reason']        ?? ''); ?>"
            data-others-info="<?php echo htmlspecialchars($row['others_detail'] ?? ''); ?>">
            <?php
                if (strtoupper($row['reason'] ?? '') === 'OTHERS' && !empty($row['others_detail'])) {
                    echo '<i style="color:#555;">' . htmlspecialchars($row['others_detail']) . '</i>';
                } else {
                    echo htmlspecialchars($row['reason'] ?? '');
                }
            ?>
        </td>
        <td>
            <?php if ($isBlocked): ?>
                <span class="badge" style="background-color:#fcebea;color:#7f231c;padding:4px 8px;border-radius:4px;font-weight:bold;font-size:0.75rem;border:1px solid #f9d6d5;">
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
                <?php if ($isBlocked): ?>
                    <button class="btn-action btn-edit"   onclick="editRow(this)">📝 Edit</button>
                    <button class="btn-action btn-delete" onclick="deleteRow(this)">🗑️ Del</button>
                <?php else: ?>
                    <?php if ($isInside): ?>
                        <button class="btn-action btn-out"  onclick="handleOut(<?php echo (int)$row['id']; ?>)">🚪 Out</button>
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
<?php endforeach; else: ?>
    <tr><td colspan="9" style="text-align:center;">No records found.</td></tr>
<?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ===== USER MANAGEMENT SECTION ===== -->
    <div id="user-section" class="content-section">
        <div class="table-container">
            <h2 style="margin-bottom:20px;color:var(--neu-blue);text-transform:uppercase;">User Account Management</h2>
            <div class="filter-group">
                <input type="text" class="search-bar" id="userSearch" placeholder="SEARCH USERS..."
                    style="margin-left:0;" onkeyup="searchUsers()">
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
/**
 * USER MANAGEMENT QUERY
 *
 * users table columns confirmed:
 *   id, first_name, last_name, email, status, profile_pic, password,
 *   reset_token, token_expiry, verification_code, is_verified,
 *   created_at, is_blocked, role
 *
 * NOTE: users DOES have first_name and last_name but NO middle_name.
 * We prefer names from activity logs (more up-to-date), falling back
 * to users table values.
 */
$sqlUser = "
    SELECT
        u.email,
        u.is_blocked,
        u.role,
        COALESCE(logs.first_name,  u.first_name,  '')                AS final_fname,
        COALESCE(logs.middle_name, '')                               AS final_mname,
        COALESCE(logs.last_name,   u.last_name,   '')                AS final_lname,
        COALESCE(logs.id_number,   'PENDING/NO LOGS')                AS final_id,
        COALESCE(logs.contact,     'N/A')                            AS final_contact,
        logs.last_seen
    FROM users u
    LEFT JOIN (
        SELECT
            email,
            first_name,
            middle_name,
            last_name,
            id_number,
            contact,
            MAX(date_visited) AS last_seen
        FROM (
            SELECT email, first_name, middle_name, last_name, id_number, contact, date_visited
            FROM active_sessions
            UNION ALL
            SELECT email, first_name, middle_name, last_name, id_number, contact, date_visited
            FROM library_logs
        ) AS combined
        GROUP BY email
    ) AS logs ON u.email = logs.email
    WHERE u.email IS NOT NULL AND u.email != ''
    ORDER BY logs.last_seen DESC, u.last_name ASC
";

$resUser = $conn->query($sqlUser);

if ($resUser && $resUser->num_rows > 0):
    while ($urow = $resUser->fetch_assoc()):
        $isBlocked   = (isset($urow['is_blocked']) && $urow['is_blocked'] == 1);
        $rawEmail    = $urow['email'];
        $noLogs      = is_null($urow['last_seen']);
        $currentRole = strtoupper($urow['role'] ?? '');

        // Fallback: if still no name, use email prefix
        $fname = !empty($urow['final_fname']) ? strtoupper($urow['final_fname']) : strtoupper(explode('@', $rawEmail)[0]);
        $mname = strtoupper($urow['final_mname'] ?: '-');
        $lname = strtoupper($urow['final_lname'] ?: '-');
?>
                <tr data-email="<?php echo htmlspecialchars($rawEmail); ?>"
                    style="<?php echo $isBlocked ? 'background-color:#fff5f5;' : ($noLogs ? 'background-color:#fffdf0;' : ''); ?>">

                    <td><?php echo htmlspecialchars($fname); ?></td>
                    <td><?php echo htmlspecialchars($mname); ?></td>
                    <td><?php echo htmlspecialchars($lname); ?></td>
                    <td class="id-cell" style="font-weight:bold;color:#2c3e50;">
                        <?php echo htmlspecialchars($urow['final_id']); ?>
                    </td>
                    <td><?php echo htmlspecialchars($urow['final_contact']); ?></td>
                    <td style="text-transform:lowercase;">
                        <strong><?php echo htmlspecialchars($rawEmail); ?></strong>
                        <?php if ($noLogs): ?>
                            <br><small style="color:#e67e22;font-style:italic;font-weight:bold;">(Registered - No Logs Found)</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="action-buttons" style="display:flex;gap:5px;">
                            <button class="btn-action btn-reset"
                                onclick="resetPassword('<?php echo htmlspecialchars($urow['final_id']); ?>', '<?php echo htmlspecialchars($rawEmail); ?>')">
                                Reset
                            </button>
                            <button class="btn-action btn-block" onclick="toggleBlock(this)"
                                <?php echo $isBlocked ? 'style="background:#2ecc71;border-color:#2ecc71;color:white;"' : ''; ?>>
                                <?php echo $isBlocked ? 'Unblock' : 'Block'; ?>
                            </button>
                            <?php if ($currentRole === 'ADMIN'): ?>
                                <button class="btn-action"
                                    onclick="changeRole('<?php echo htmlspecialchars($rawEmail); ?>', 'USER')"
                                    style="background:#34495e;color:white;border:none;padding:5px 10px;border-radius:4px;cursor:pointer;font-size:0.7rem;font-weight:bold;">
                                    REVOKE ADMIN
                                </button>
                            <?php else: ?>
                                <button class="btn-action"
                                    onclick="changeRole('<?php echo htmlspecialchars($rawEmail); ?>', 'ADMIN')"
                                    style="background:#9b59b6;color:white;border:none;padding:5px 10px;border-radius:4px;cursor:pointer;font-size:0.7rem;font-weight:bold;">
                                    MAKE ADMIN
                                </button>
                            <?php endif; ?>
                            <button class="btn-action btn-delete" onclick="deleteUser(this)">Delete</button>
                        </div>
                    </td>
                </tr>
<?php endwhile; else: ?>
                <tr><td colspan="7" style="text-align:center;padding:20px;">No registered users found.</td></tr>
<?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ===== FLAGGED RECORDS SECTION ===== -->
    <div id="flagged-section" class="content-section">
        <div class="table-container">
            <h2 style="text-transform:uppercase;color:var(--neu-red);margin-bottom:10px;">Flagged Records History</h2>
            <div style="margin-bottom:20px;">
                <input type="text" id="flagSearch" class="search-bar" onkeyup="searchFlaggedTable()"
                    placeholder="SEARCH NAME OR ID NUMBER..." style="max-width:400px;text-transform:uppercase;">
            </div>
            <p style="font-size:0.8rem;color:#666;margin-bottom:20px;">
                ALL FLAGGED LOGS WILL APPEAR HERE FOR REVIEW AND AUDIT PURPOSES.
            </p>
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
/** FLAGGED RECORDS — uses its own variable names to avoid collision **/
$sqlFlags    = "SELECT * FROM visitor_flags ORDER BY flagged_at DESC";
$resultFlags = $conn->query($sqlFlags);

if ($resultFlags && $resultFlags->num_rows > 0):
    while ($frow = $resultFlags->fetch_assoc()):
        $fDate = strtoupper(date('M d, Y | h:i A', strtotime($frow['flagged_at'])));
        $fid   = (int)$frow['id'];
?>
                <tr>
                    <td><?php echo $fDate; ?></td>
                    <td class="name-cell"><?php echo htmlspecialchars(strtoupper($frow['student_name'])); ?></td>
                    <td class="id-cell"><?php echo htmlspecialchars(strtoupper($frow['student_id'])); ?></td>
                    <td class="reason-text" style="color:#c62828;font-weight:bold;">
                        <?php echo htmlspecialchars(strtoupper($frow['reason'])); ?>
                    </td>
                    <td>
                        <div style="display:flex;gap:8px;">
                            <button class="btn-action btn-edit" style="background-color:#00897b;"
                                onclick="editFlagReason(this, <?php echo $fid; ?>)">EDIT</button>
                            <button class="btn-action btn-flag"
                                onclick="removeFlag(this, <?php echo $fid; ?>)">REMOVE</button>
                        </div>
                    </td>
                </tr>
<?php endwhile; else: ?>
                <tr class="no-records">
                    <td colspan="5" style="text-align:center;">NO FLAGGED RECORDS FOUND</td>
                </tr>
<?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- /.main-content -->

<!-- ===== ADD / EDIT VISITOR MODAL ===== -->
<div class="modal" id="addModal">
    <div class="modal-content" style="max-width:450px;max-height:90vh;overflow-y:auto;padding:20px;">
        <h2 id="modalTitle" style="margin-top:0;">Add New Visitor</h2>

        <!-- Hidden field required for edit mode -->
        <input type="hidden" id="mSessionId" value="">

        <div style="display:flex;gap:10px;">
            <div class="form-group" style="flex:1;">
                <label>First Name <span style="color:red;">*</span></label>
                <input type="text" id="mFirstName" placeholder="FIRST NAME">
            </div>
            <div class="form-group" style="flex:1;">
                <label>Middle Name</label>
                <input type="text" id="mMiddleName" placeholder="MIDDLE NAME">
            </div>
        </div>
        <div style="display:flex;gap:10px;">
            <div class="form-group" style="flex:2;">
                <label>Last Name <span style="color:red;">*</span></label>
                <input type="text" id="mLastName" placeholder="LAST NAME">
            </div>
            <div class="form-group" style="flex:1;">
                <label>Suffix</label>
                <input type="text" id="mSuffix" placeholder="JR, III...">
            </div>
        </div>
        <div class="form-group">
            <label>Contact Number</label>
            <input type="text" id="mContact" placeholder="09XXXXXXXXX">
        </div>
        <div class="form-group">
            <label>ID Number</label>
            <input type="text" id="mID" placeholder="XX-XXXXX-XXX">
        </div>
        <div style="display:flex;gap:10px;">
            <div class="form-group" style="flex:1;">
                <label>Category <span style="color:red;">*</span></label>
                <select id="mCategory">
                    <option value="" disabled selected>SELECT</option>
                    <option>GUEST</option>
                    <option>STUDENT</option>
                    <option>FACULTY</option>
                    <option>EMPLOYEE</option>
                </select>
            </div>
            <div class="form-group" style="flex:1;">
                <label>Program</label>
                <input type="text" id="mProgram" placeholder="E.G. BSIT">
            </div>
        </div>
        <div class="form-group">
            <label>Purpose <span style="color:red;">*</span></label>
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
        <div class="form-group" id="otherPurposeContainer" style="display:none;margin-top:10px;">
            <label>Please Specify <span style="color:red;">*</span></label>
            <input type="text" id="mOtherPurpose" placeholder="TYPE YOUR PURPOSE HERE...">
        </div>
        <div class="modal-actions">
            <button class="btn-cancel" onclick="closeModal()">Cancel</button>
            <button class="btn-save" id="saveBtn" onclick="saveVisitor()">Save Visitor</button>
        </div>
    </div>
</div>

<script>
/** ============================================================
 *  GLOBAL STATE
 * ============================================================ */
let vChart, pChart;
let editMode       = false;
let editingRow     = null;
let oldIDForUpdate = '';

const purposeConfig = {
    'STUDY':                { label: 'STUDY',                color: '#e53935' },
    'BORROWING':            { label: 'BORROWING',            color: '#fdd835' },
    'RETURNING':            { label: 'RETURNING',            color: '#43a047' },
    'INTERNET USE':         { label: 'INTERNET USE',         color: '#1e88e5' },
    'MEETING':              { label: 'MEETING',              color: '#00897b' },
    'RESEARCH':             { label: 'RESEARCH',             color: '#fb8c00' },
    'GROUP STUDY':          { label: 'GROUP STUDY',          color: '#8e24aa' },
    'PRINTING/PHOTOCOPY':   { label: 'PRINTING / PHOTOCOPY', color: '#6d4c41' },
    'ACADEMIC REQUIREMENT': { label: 'ACADEMIC REQUIREMENT', color: '#546e7a' },
    'OTHERS':               { label: 'OTHERS',               color: '#f06292' }
};

/** ============================================================
 *  INIT
 * ============================================================ */
document.addEventListener('DOMContentLoaded', () => {
    initCharts();
    refreshDashboard();
    setInterval(updateLiveInfo, 1000);
    showSection(localStorage.getItem('lastSection') || 'visitor-section', null);
});

function refreshDashboard() {
    updateStats();
    updateCharts();
}

function updateLiveInfo() {
    const now = new Date();
    const c = document.getElementById('clock');
    const d = document.getElementById('date');
    if (c) c.innerText = now.toLocaleTimeString([], { hour:'2-digit', minute:'2-digit', second:'2-digit', hour12:true }).toUpperCase();
    if (d) d.innerText = now.toLocaleDateString('en-US', { weekday:'long', year:'numeric', month:'short', day:'2-digit' }).toUpperCase();
}

/** ============================================================
 *  NAVIGATION
 * ============================================================ */
function showSection(id, el) {
    document.querySelectorAll('.content-section').forEach(s => { s.classList.remove('active'); s.style.display = 'none'; });
    const sec = document.getElementById(id);
    if (sec) { sec.classList.add('active'); sec.style.display = 'block'; }
    document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
    if (el) {
        el.classList.add('active');
    } else {
        const lnk = document.querySelector(`.nav-link[onclick*="${id}"]`);
        if (lnk) lnk.classList.add('active');
    }
    localStorage.setItem('lastSection', id);
}

/** ============================================================
 *  MODAL
 * ============================================================ */
function openModal() {
    document.getElementById('addModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('addModal').style.display = 'none';
    editMode = false; editingRow = null; oldIDForUpdate = '';
    document.getElementById('modalTitle').innerText = '➕ ADD NEW VISITOR';
    document.getElementById('saveBtn').innerText    = 'SAVE VISITOR';
    ['mFirstName','mMiddleName','mLastName','mSuffix','mID','mOtherPurpose','mProgram','mContact','mSessionId']
        .forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
    ['mCategory','mPurpose'].forEach(id => { const el = document.getElementById(id); if (el) el.selectedIndex = 0; });
    const oc = document.getElementById('otherPurposeContainer');
    if (oc) oc.style.display = 'none';
}

function toggleOthersInput(sel) {
    document.getElementById('otherPurposeContainer').style.display = sel.value === 'OTHERS' ? 'block' : 'none';
}

/** ============================================================
 *  CHARTS
 * ============================================================ */
function initCharts() {
    const c1 = document.getElementById('visitorChart');
    const c2 = document.getElementById('purposeChart');
    if (!c1 || !c2) return;
    if (vChart instanceof Chart) vChart.destroy();
    if (pChart instanceof Chart) pChart.destroy();

    vChart = new Chart(c1.getContext('2d'), {
        type: 'line',
        data: {
            labels: ['MON','TUE','WED','THU','FRI','SAT'],
            datasets: [{
                label: 'VISITORS', data: [0,0,0,0,0,0],
                borderColor: '#004aad', tension: 0.4, fill: true,
                backgroundColor: 'rgba(0,74,173,0.1)'
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });

    pChart = new Chart(c2.getContext('2d'), {
        type: 'doughnut',
        data: { labels: [], datasets: [{ data: [], backgroundColor: [] }] },
        options: {
            maintainAspectRatio: false, cutout: '70%',
            plugins: { legend: { display: true, position: 'right',
                labels: { boxWidth: 10, padding: 8, font: { size: 9, weight: '600' } } } }
        }
    });
}

function updateCharts() {
    const rows = document.querySelectorAll('#visitorTable tbody tr');
    const weeklyData = [0,0,0,0,0,0];
    const purposeCounts = {};
    let total = 0;
    const uPerDay    = [new Set(),new Set(),new Set(),new Set(),new Set(),new Set()];
    const uPurpose   = new Set();
    Object.keys(purposeConfig).forEach(k => purposeCounts[k] = 0);
    const yr = new Date().getFullYear();

    rows.forEach(row => {
        if (row.style.display === 'none') return;
        const di = row.getAttribute('data-in-time');
        if (!di || di === '-') return;
        const sid = row.querySelector('.id-cell')?.innerText.trim();
        const p   = di.split('-');
        const rd  = new Date(p[0], p[1]-1, p[2]);
        const day = rd.getDay();
        if (rd.getFullYear() === yr && day >= 1 && day <= 6) {
            const idx = day - 1;
            if (!uPerDay[idx].has(sid)) { uPerDay[idx].add(sid); weeklyData[idx]++; }
            if (!uPurpose.has(sid)) {
                uPurpose.add(sid);
                const pc = row.querySelector('.purpose-cell');
                if (pc) {
                    const pn = (pc.getAttribute('data-original-purpose') || pc.innerText).trim().toUpperCase();
                    const ck = Object.keys(purposeConfig).find(k => k === pn || purposeConfig[k].label === pn);
                    if (ck) { purposeCounts[ck]++; total++; }
                    else    { purposeCounts['OTHERS']++; total++; }
                }
            }
        }
    });

    if (vChart) { vChart.data.datasets[0].data = weeklyData; vChart.update(); }
    if (pChart) {
        if (total === 0) {
            pChart.data.labels = ['NO DATA'];
            pChart.data.datasets[0].data = [1];
            pChart.data.datasets[0].backgroundColor = ['#e0e0e0'];
        } else {
            pChart.data.labels = Object.keys(purposeConfig).map(k => purposeConfig[k].label);
            pChart.data.datasets[0].data  = Object.keys(purposeConfig).map(k => purposeCounts[k]);
            pChart.data.datasets[0].backgroundColor = Object.keys(purposeConfig).map(k => purposeConfig[k].color);
        }
        pChart.update();
    }
}

/** ============================================================
 *  STATS
 * ============================================================ */
function updateStats() {
    const rows = document.querySelectorAll('#visitorTable tbody tr');
    const iS=new Set(), tS=new Set(), wS=new Set(), mS=new Set();
    const now = new Date();
    const todStr  = now.toDateString();
    const sot     = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const s7      = new Date(sot); s7.setDate(sot.getDate() - 7);
    const flm     = new Date(now.getFullYear(), now.getMonth()-1, 1);
    const llm     = new Date(now.getFullYear(), now.getMonth(), 0);

    rows.forEach(row => {
        const da  = row.getAttribute('data-in-time');
        if (!da || da === '-') return;
        const sid = row.querySelector('.id-cell')?.innerText.trim();
        const rd  = new Date(da);
        const st  = row.querySelector('.badge');
        if (st && st.innerText.trim().toUpperCase() === 'INSIDE') iS.add(sid);
        if (rd.toDateString() === todStr) tS.add(sid);
        if (rd < sot && rd >= s7) wS.add(sid);
        if (rd >= flm && rd <= llm) mS.add(sid);
    });

    const setEl = (id, v) => { const e = document.getElementById(id); if (e) e.innerText = v; };
    setEl('capacityCount',  iS.size);
    setEl('todayCount',     tS.size);
    setEl('lastWeekCount',  wS.size);
    setEl('lastMonthCount', mS.size);
    setEl('flaggedCount',   document.querySelectorAll('#flagTable tbody tr:not(.no-records)').length);
}

/** ============================================================
 *  FILTER
 * ============================================================ */
function filterByTime(range, btn) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');

    const rows = document.querySelectorAll('#visitorTable tbody tr');
    const now  = new Date(); now.setHours(0,0,0,0);
    const todayStr   = `${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}-${String(now.getDate()).padStart(2,'0')}`;
    const yesterday  = new Date(now); yesterday.setDate(now.getDate()-1);
    const dow        = now.getDay();
    const lastMonday = new Date(now); lastMonday.setDate(now.getDate() - ((dow===0?6:dow-1)+7));
    const flm        = new Date(now.getFullYear(), now.getMonth()-1, 1);
    const llm        = new Date(now.getFullYear(), now.getMonth(), 0);

    rows.forEach(row => {
        const da = row.getAttribute('data-in-time');
        if (!da || da === '-') { row.style.display = 'none'; return; }
        const p  = da.split('-');
        const rd = new Date(p[0], p[1]-1, p[2]); rd.setHours(0,0,0,0);
        let show = false;
        if      (range === 'all')       show = (rd >= flm);
        else if (range === 'today')     show = (da === todayStr);
        else if (range === 'lastweek')  show = (rd >= lastMonday && rd <= yesterday);
        else if (range === 'lastmonth') show = (rd >= flm && rd <= llm);
        row.style.display = show ? '' : 'none';
    });
    updateStats();
    updateCharts();
}

/** ============================================================
 *  SAVE / EDIT VISITOR
 * ============================================================ */
async function saveVisitor() {
    const fName  = document.getElementById('mFirstName').value.trim().toUpperCase();
    const lName  = document.getElementById('mLastName').value.trim().toUpperCase();
    const cat    = document.getElementById('mCategory').value.toUpperCase();
    const purp   = document.getElementById('mPurpose').value;
    const idNum  = document.getElementById('mID').value.trim().toUpperCase() || 'N/A';

    if (!fName || !lName || !cat || !purp) {
        alert('⚠️ PLEASE FILL IN ALL REQUIRED FIELDS.');
        return;
    }
    if (editMode && editingRow) {
        const st = editingRow.querySelector('.badge');
        if (st && st.innerText.trim().toUpperCase() === 'INSIDE') {
            if (!confirm('🔔 VISITOR IS CURRENTLY INSIDE. CHANGES WILL REFLECT ON ACTIVE SESSION. CONTINUE?')) return;
        }
    }
    if (idNum !== 'N/A' && (!editMode || idNum !== oldIDForUpdate)) {
        try {
            const cr = await fetch(`check_id_conflict.php?id_number=${encodeURIComponent(idNum)}`);
            const cd = await cr.json();
            if (cd.exists && !confirm(`⚠️ ID [${idNum}] BELONGS TO: ${cd.name}. MERGE?`)) return;
        } catch(e) { console.error(e); }
    }

    const fd = new FormData();
    fd.append('first_name',    fName);
    fd.append('middle_name',   document.getElementById('mMiddleName').value.trim().toUpperCase());
    fd.append('last_name',     lName);
    fd.append('suffix',        document.getElementById('mSuffix').value.trim().toUpperCase());
    fd.append('id_number',     idNum);
    fd.append('contact',       document.getElementById('mContact').value.trim());
    fd.append('user_type',     cat);
    fd.append('course',        document.getElementById('mProgram').value.trim().toUpperCase() || 'N/A');
    fd.append('reason',        purp);
    fd.append('others_detail', document.getElementById('mOtherPurpose').value.trim().toUpperCase());

    let url = 'add_visitor.php';
    if (!editMode) {
        fd.append('date_visited', new Date().toLocaleDateString('en-CA'));
    } else {
        url = 'update_process.php';
        fd.append('old_id_number', oldIDForUpdate);
        fd.append('session_id', document.getElementById('mSessionId')?.value || editingRow.getAttribute('data-id'));
    }

    fetch(url, { method: 'POST', body: fd })
        .then(r => { if (!r.ok) throw new Error('NETWORK ERROR'); return r.json(); })
        .then(d => {
            if (d.success || d.status === 'success') {
                alert(editMode ? '✅ RECORDS UPDATED!' : '✅ VISITOR ADDED!');
                location.reload();
            } else {
                alert('❌ ERROR: ' + (d.message || 'UNKNOWN ERROR'));
            }
        })
        .catch(e => { console.error(e); alert('⚠️ CONNECTION ERROR.'); });
}

function editRow(btn) {
    editMode       = true;
    editingRow     = btn.closest('tr');
    oldIDForUpdate = editingRow.querySelector('.id-cell').innerText.trim();

    const pc = editingRow.querySelector('.purpose-cell');
    const pk = pc.getAttribute('data-original-purpose');

    document.getElementById('mSessionId').value  = editingRow.getAttribute('data-id');
    document.getElementById('mFirstName').value  = editingRow.getAttribute('data-fname')    || '';
    document.getElementById('mMiddleName').value = editingRow.getAttribute('data-mname')    || '';
    document.getElementById('mLastName').value   = editingRow.getAttribute('data-lname')    || '';
    document.getElementById('mSuffix').value     = editingRow.getAttribute('data-suffix')   || '';
    document.getElementById('mContact').value    = editingRow.getAttribute('data-contact')  || '';
    document.getElementById('mID').value         = oldIDForUpdate;
    document.getElementById('mCategory').value   = editingRow.getAttribute('data-category') || '';
    document.getElementById('mProgram').value    = editingRow.getAttribute('data-program')  || '';
    document.getElementById('mPurpose').value    = pk;

    const oc = document.getElementById('otherPurposeContainer');
    if (pk === 'OTHERS') {
        oc.style.display = 'block';
        document.getElementById('mOtherPurpose').value = pc.getAttribute('data-others-info') || '';
    } else {
        oc.style.display = 'none';
        document.getElementById('mOtherPurpose').value = '';
    }
    document.getElementById('modalTitle').innerText = '📝 EDIT VISITOR RECORD';
    document.getElementById('saveBtn').innerText    = 'UPDATE RECORD';
    openModal();
}

/** ============================================================
 *  SESSION MANAGEMENT
 * ============================================================ */
function handleOut(sessionId) {
    if (!confirm('CONFIRM USER LOGOUT?')) return;
    const fd = new FormData(); fd.append('session_id', sessionId);
    fetch('out_process.php', { method:'POST', body:fd })
        .then(r => r.json())
        .then(d => { if (d.success) { alert('LOGGED OUT.'); location.reload(); } else alert('ERROR: ' + d.error); })
        .catch(e => { console.error(e); alert('SERVER ERROR.'); });
}

function deleteRow(btn) {
    const row  = btn.closest('tr');
    const id   = row.getAttribute('data-id');
    const name = row.querySelector('.name-cell')?.innerText || 'THIS RECORD';
    if (!id) { alert('ERROR: ID NOT FOUND.'); return; }
    if (!confirm(`DELETE LOG FOR ${name}?`)) return;
    const fd = new FormData(); fd.append('id', id);
    fetch('delete_visitor.php', { method:'POST', body:fd })
        .then(r => r.json())
        .then(d => {
            if (d.status === 'success') {
                alert('DELETED.');
                row.style.transition = 'all 0.3s'; row.style.transform = 'scale(0.8)'; row.style.opacity = '0';
                setTimeout(() => { row.remove(); refreshDashboard(); }, 300);
            } else { alert('ERROR: ' + (d.message || 'FAILED.')); }
        })
        .catch(e => { console.error(e); alert('CONNECTION ERROR.'); });
}

/** ============================================================
 *  FLAGGING
 * ============================================================ */
function toggleManualFlag(btn) {
    const row   = btn.closest('tr');
    const name  = row.querySelector('.name-cell').innerText.toUpperCase();
    const idNum = row.querySelector('.id-cell').innerText.toUpperCase();
    const raw   = prompt(`REASON FOR FLAGGING ${name}:`, '');
    if (!raw || !raw.trim()) return;
    const fd = new FormData();
    fd.append('student_id', idNum);
    fd.append('student_name', name);
    fd.append('reason', raw.trim().toUpperCase());
    fd.append('action', 'flag');
    fetch('flag_process.php', { method:'POST', body:fd })
        .then(r => r.json())
        .then(d => { if (d.success) { alert('✅ FLAGGED.'); location.reload(); } else alert('❌ ' + (d.error || 'Error.')); })
        .catch(e => console.error(e));
}

function removeFlag(btn, flagId) {
    if (!confirm('REMOVE THIS FLAG?')) return;
    const fd = new FormData();
    fd.append('flag_id', flagId);
    fd.append('action', 'delete_flag');
    fetch('flag_process.php', { method:'POST', body:fd })
        .then(r => r.json())
        .then(d => { if (d.success) { btn.closest('tr').remove(); alert('✅ REMOVED.'); location.reload(); } else alert('❌ FAILED.'); })
        .catch(e => console.error(e));
}

function editFlagReason(btn, flagId) {
    const row = btn.closest('tr');
    const raw = prompt('NEW REASON:', '');
    if (!raw || !raw.trim()) return;
    const nr = raw.trim().toUpperCase();
    const fd = new FormData();
    fd.append('flag_id', flagId);
    fd.append('reason', nr);
    fd.append('action', 'edit_reason');
    fetch('flag_process.php', { method:'POST', body:fd })
        .then(r => r.json())
        .then(d => { if (d.success) { row.querySelector('.reason-text').innerText = nr; alert('✅ UPDATED.'); } else alert('❌ FAILED.'); })
        .catch(e => console.error(e));
}

/** ============================================================
 *  USER MANAGEMENT
 * ============================================================ */
function toggleBlock(btn) {
    const row    = btn.closest('tr');
    const email  = row.getAttribute('data-email');
    if (!email)  { alert('⚠️ INVALID EMAIL.'); return; }
    const action = btn.innerText.toLowerCase().includes('unblock') ? 'unblock' : 'block';
    if (!confirm(`${action.toUpperCase()} THIS USER?`)) return;
    const fd = new FormData();
    fd.append('email', email); fd.append('action', action);
    if (action === 'block') fd.append('auto_out', 'true');
    fetch('toggle_block.php', { method:'POST', body:fd })
        .then(r => r.json())
        .then(d => { if (d.status === 'success') { alert('✅ ' + d.message.toUpperCase()); location.reload(); } else alert('❌ ' + d.message); })
        .catch(e => { console.error(e); alert('CONNECTION ERROR.'); });
}

function deleteUser(btn) {
    const row   = btn.closest('tr');
    const email = row.getAttribute('data-email');
    const idNum = row.querySelector('.id-cell').innerText.trim();
    if (!email) { alert('NO VALID EMAIL.'); return; }
    if (!confirm(`DELETE ACCOUNT FOR ${email}?`)) return;
    if (!confirm('FINAL WARNING: ALL LOGS WILL ALSO BE REMOVED. PROCEED?')) return;
    const fd = new FormData();
    fd.append('email', email); fd.append('id_number', idNum);
    fetch('delete_user.php', { method:'POST', body:fd })
        .then(r => r.text())
        .then(d => {
            if (d.trim() === 'success') {
                alert('DELETED.');
                row.style.transition='all 0.4s'; row.style.transform='translateX(20px)'; row.style.opacity='0';
                setTimeout(() => { row.remove(); refreshDashboard(); }, 400);
            } else { alert('DATABASE ERROR.'); }
        })
        .catch(e => { console.error(e); alert('CONNECTION ERROR.'); });
}

function resetPassword(idNum, email) {
    if (!email) { alert('NO VALID EMAIL.'); return; }
    if (!confirm(`SEND RESET LINK TO ${email}? EXPIRES IN 2 MINUTES.`)) return;
    const btn = event.target; btn.innerText = 'Sending...'; btn.disabled = true;
    const fd = new FormData(); fd.append('email', email);
    fetch('generate_reset_link.php', { method:'POST', body:fd })
        .then(r => r.json())
        .then(d => alert(d.status === 'success' ? 'SUCCESS: ' + d.message : 'ERROR: ' + d.message))
        .catch(e => { console.error(e); alert('CONNECTION ERROR.'); })
        .finally(() => { btn.innerText = 'Reset'; btn.disabled = false; });
}

function changeRole(email, newRole) {
    const r = newRole.toUpperCase();
    if (!confirm(`${r==='ADMIN' ? 'PROMOTE TO ADMIN' : 'REVOKE ADMIN'}?`)) return;
    const fd = new FormData(); fd.append('email', email); fd.append('role', r);
    fetch('update_role.php', { method:'POST', body:fd })
        .then(res => res.json())
        .then(d => {
            if (d.status === 'success' || d.status === 'ok') { alert('✅ ' + d.message.toUpperCase()); location.reload(); }
            else { alert('❌ ' + (d.message || 'FAILED')); }
        })
        .catch(e => { console.error(e); alert('SYSTEM ERROR.'); });
}

/** ============================================================
 *  SEARCH
 * ============================================================ */
function searchTable() {
    const t = document.getElementById('tableSearch').value.toUpperCase();
    document.querySelectorAll('#visitorTable tbody tr').forEach(r => {
        r.style.display = r.innerText.toUpperCase().includes(t) ? '' : 'none';
    });
}
function searchUsers() {
    const t = document.getElementById('userSearch').value.toUpperCase();
    document.querySelectorAll('#userTable tbody tr').forEach(r => {
        r.style.display = r.innerText.toUpperCase().includes(t) ? '' : 'none';
    });
}
function searchFlaggedTable() {
    const t = document.getElementById('flagSearch').value.toUpperCase();
    document.querySelectorAll('#flagTable tbody tr').forEach(r => {
        r.style.display = r.innerText.toUpperCase().includes(t) ? '' : 'none';
    });
}

function confirmLogout() { window.location.href = 'logout.php'; }

/** ============================================================
 *  EXPORT TO XLS
 * ============================================================ */
function exportTableToCSV() {
    const table = document.getElementById('visitorTable');
    if (!table) return;
    const ab  = document.querySelector('.filter-btn.active');
    const now = new Date(); const yr = now.getFullYear();
    let label = `ALL_RECORDS_${yr}`;
    if (ab) {
        const t = ab.innerText.toUpperCase().trim();
        if (t.includes('TODAY')) {
            label = `TODAY_${now.getMonth()+1}-${now.getDate()}-${yr}`;
        } else if (t.includes('LAST WEEK')) {
            const lm = new Date(now); lm.setDate(now.getDate() - ((now.getDay()+6)%7 + 7));
            const ls = new Date(lm);  ls.setDate(lm.getDate() + 5);
            label = `LASTWEEK_${lm.getMonth()+1}-${lm.getDate()}-${ls.getDate()}-${yr}`;
        } else if (t.includes('LAST MONTH')) {
            const lmn = new Date(now.getFullYear(), now.getMonth()-1, 1);
            label = `LASTMONTH_${lmn.toLocaleString('en-US',{month:'long'}).toUpperCase()}_${yr}`;
        }
    }

    const rows = Array.from(table.querySelectorAll('tr'));
    // NOTE: Split '<?' and '?>' so PHP does not interpret them as PHP tags
    const xmlDecl = '<' + '?xml version="1.0" encoding="UTF-8"?' + '>'
                  + '<' + '?mso-application progid="Excel.Sheet"?' + '>';
    let xml = xmlDecl + `
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
 <Styles>
  <Style ss:ID="h">
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
   <Font ss:Bold="1" ss:Color="#FFFFFF"/>
   <Interior ss:Color="#2E7D32" ss:Pattern="Solid"/>
  </Style>
  <Style ss:ID="c"><Alignment ss:Vertical="Center" ss:WrapText="1"/></Style>
 </Styles>
 <Worksheet ss:Name="Library Logs"><Table>
 <Column ss:Width="140"/><Column ss:Width="90"/><Column ss:Width="120"/>
 <Column ss:Width="110"/><Column ss:Width="120"/><Column ss:Width="110"/>
 <Column ss:Width="100"/><Column ss:Width="200"/><Column ss:Width="200"/><Column ss:Width="90"/>`;

    let xr = '';
    rows.forEach((row, ri) => {
        if (row.offsetParent === null) return;
        const cols = row.querySelectorAll('th,td');
        const isH  = ri === 0;
        xr += isH ? '<Row ss:Height="25">' : '<Row ss:Height="22">';
        for (let i = 0; i < cols.length - 1; i++) {
            const s = `ss:StyleID="${isH ? 'h' : 'c'}"`;
            if (i === 2) {
                if (isH) {
                    xr += `<Cell ${s}><Data ss:Type="String">FIRST NAME</Data></Cell>`;
                    xr += `<Cell ${s}><Data ss:Type="String">MIDDLE NAME</Data></Cell>`;
                    xr += `<Cell ${s}><Data ss:Type="String">LAST NAME</Data></Cell>`;
                } else {
                    const fn = (row.getAttribute('data-fname')  || '').toUpperCase();
                    const mn = (row.getAttribute('data-mname')  || '').toUpperCase();
                    const ln = (row.getAttribute('data-lname')  || '').toUpperCase();
                    const sx = (row.getAttribute('data-suffix') || '').toUpperCase();
                    xr += `<Cell ${s}><Data ss:Type="String">${fn}</Data></Cell>`;
                    xr += `<Cell ${s}><Data ss:Type="String">${mn}</Data></Cell>`;
                    xr += `<Cell ${s}><Data ss:Type="String">${sx ? ln+' '+sx : ln}</Data></Cell>`;
                }
                continue;
            }
            const v = cols[i].innerText.trim()
                .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
                .replace(/'/g,'&apos;').replace(/"/g,'&quot;');
            xr += `<Cell ${s}><Data ss:Type="String">${v.toUpperCase()}</Data></Cell>`;
        }
        xr += '</Row>';
    });

    const blob = new Blob([xml + xr + '</Table></Worksheet></Workbook>'], { type: 'application/vnd.ms-excel' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `NEU_Library_${label}.xls`;
    document.body.appendChild(a); a.click(); document.body.removeChild(a);
}
</script>
</body>
</html>
