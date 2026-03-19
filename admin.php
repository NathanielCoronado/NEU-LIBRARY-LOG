<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NEU Library - Admin Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.sheetjs.com/xlsx-0.19.3/package/dist/xlsx.full.min.js"></script>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <div class="sidebar">
    <div class="sidebar-header" style="text-align: center; padding: 20px 10px;">
        <img src="https://github.com/NathanielCoronado/NEU-LIBRARY-LOG/blob/main/NEU%20LOGO.jpg?raw=true" alt="NEU Logo" style="width: 80px; margin-bottom: 10px;">
        <h2 style="color: white; font-size: 1.1rem; letter-spacing: 1px; text-transform: uppercase;">Library Admin</h2>
    </div>

    <div class="nav-item">
        <a href="#" class="nav-link active" onclick="showSection('visitor-logs-section', this)">
            <i class="fas fa-users"></i> VISITOR LOGS
        </a>
    </div>

    <div class="nav-item">
        <a href="#" class="nav-link" onclick="showSection('user-management-section', this)">
            <i class="fas fa-user-cog"></i> USER MANAGEMENT
        </a>
    </div>

    <div class="nav-item">
        <a href="#" class="nav-link" onclick="showSection('flagged-records-section', this)">
            <i class="fas fa-flag"></i> FLAGGED RECORDS
        </a>
    </div>

    <button class="btn-logout" onclick="logout()" style="margin-top: auto;">Logout</button>
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

        <div id="visitor-logs-section" class="content-section active">
            
            <div class="stats-grid">
                <div class="stat-card" style="border-left-color: var(--neu-blue);"><h3>Current Inside</h3><span class="stat-main" id="capacityCount">0</span></div>
                <div class="stat-card" style="border-left-color: var(--neu-green);"><h3>Total Today</h3><span class="stat-main" id="todayCount">0</span></div>
                <div class="stat-card" style="border-left-color: var(--neu-yellow);"><h3>Last Week</h3><span class="stat-main" id="weekCount">0</span></div>
                <div class="stat-card" style="border-left-color: #9c27b0;"><h3>Last Month</h3><span class="stat-main" id="monthCount">0</span></div>
                <div class="stat-card" style="border-left-color: var(--neu-red);"><h3>Flagged Records</h3><span class="stat-main" id="flagCount">0</span></div>
            </div>

            <div class="analytics-container">
                <div class="chart-card">
                    <h3 style="font-size: 0.8rem; color: #555; margin-bottom: 10px; text-transform: uppercase;">Weekly Visitors Trend</h3>
                    <canvas id="visitorChart"></canvas>
                </div>
                <div class="chart-card">
                    <h3 style="font-size: 0.65rem; color: #777; text-transform: uppercase; margin-bottom: 10px;">PURPOSE DISTRIBUTION</h3>
                    <div style="position: relative; height: 220px; width: 100%;">
                        <canvas id="purposeChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <div class="filter-group" style="display: flex; gap: 8px; margin-bottom: 15px; align-items: center;">
                    <button class="filter-btn active" data-filter="all" onclick="filterByTime('all', this)">All</button>
                    <button class="filter-btn" data-filter="today" onclick="filterByTime('today', this)">Today</button>
                    <button class="filter-btn" data-filter="week" onclick="filterByTime('week', this)">Last Week</button>
                    <button class="filter-btn" data-filter="month" onclick="filterByTime('month', this)">Last Month</button>
                    
                    <button class="filter-btn" onclick="resetForm(); openModal();" style="background: var(--neu-blue, #007bff); color: white; border: 1px solid var(--neu-blue, #007bff); padding: 0 12px; border-radius: 4px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-weight: 600; font-size: 13px; height: 36px; transition: all 0.2s;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/><path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm0 1a6 6 0 1 1 0 12A6 6 0 0 1 8 2z"/></svg>
                        ADD VISITOR
                    </button>

                    <button type="button" onclick="exportToExcel()" class="filter-btn" style="background: #1D6F42; color: white; border: solid 1px #1D6F42; padding: 0 12px; border-radius: 4px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-weight: 600; font-size: 13px; height: 36px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/><path d="M5.186 4.857A.3.3 0 0 0 5 5.148v5.704c0 .167.134.298.3.298h.626c.167 0 .3-.134.3-.298V9.332h1.209c.403 0 .73-.11.981-.33.25-.222.375-.53.375-.926 0-.39-.125-.694-.375-.913-.249-.22-.577-.328-.981-.328H5.186zm1.411 1.138h.893c.183 0 .324.043.424.129a.44.44 0 0 1 .15.357.44.44 0 0 1-.15.357c-.1.084-.241.127-.424.127h-.893v-1.17z"/></svg>
                        EXCEL REPORT
                    </button>
                    
                    <input type="text" class="search-bar" id="tableSearch" placeholder="SEARCH NAME OR ID..." onkeyup="searchTable()">
                </div>

                <?php
                /** DB CONNECTION **/
                $host = "localhost"; $username = "root"; $password = ""; $dbname = "neu_Library_signup_db";
                $conn = new mysqli($host, $username, $password, $dbname);
                if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

                /** SQL FETCH VISITOR LOGS **/
                $sql_logs = "
                    SELECT 
                        combined.id, combined.id_number, combined.reason, combined.others_detail,
                        combined.date_visited, combined.time_in, combined.time_out, combined.status_label,
                        latest_info.first_name, latest_info.last_name, latest_info.middle_name,
                        latest_info.suffix, latest_info.user_type, latest_info.course, latest_info.contact
                    FROM (
                        SELECT id, id_number, first_name, last_name, middle_name, suffix, user_type, course, reason, others_detail, date_visited, time_in, NULL AS time_out, 'INSIDE' AS status_label, contact 
                        FROM active_sessions
                        UNION ALL
                        SELECT id, id_number, first_name, last_name, middle_name, suffix, user_type, course, reason, others_detail, date_visited, time_in, time_out, 'OUT' AS status_label, contact 
                        FROM library_logs
                    ) AS combined
                    INNER JOIN (
                        SELECT id_number, first_name, last_name, middle_name, suffix, user_type, course, contact
                        FROM (
                            SELECT id_number, first_name, last_name, middle_name, suffix, user_type, course, contact, date_visited, time_in,
                                   ROW_NUMBER() OVER (PARTITION BY id_number ORDER BY date_visited DESC, time_in DESC) as rn
                            FROM (
                                SELECT id_number, first_name, last_name, middle_name, suffix, user_type, course, contact, date_visited, time_in FROM active_sessions
                                UNION ALL
                                SELECT id_number, first_name, last_name, middle_name, suffix, user_type, course, contact, date_visited, time_in FROM library_logs
                            ) AS raw
                        ) AS ranked WHERE rn = 1
                    ) AS latest_info ON combined.id_number = latest_info.id_number
                    ORDER BY combined.date_visited DESC, combined.time_in DESC";
                $result = $conn->query($sql_logs);

                /** SQL FETCH CHART DATA **/
                $sql_chart = "SELECT reason, COUNT(*) as count FROM (
                                SELECT reason FROM active_sessions
                                UNION ALL
                                SELECT reason FROM library_logs
                              ) AS total_purpose GROUP BY reason";
                $chart_result = $conn->query($sql_chart);
                $labels = []; $counts = [];
                while($row = $chart_result->fetch_assoc()) {
                    $labels[] = strtoupper($row['reason']);
                    $counts[] = (int)$row['count'];
                }
                ?>

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
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): 
                        
                        /** FLAG & DATETIME LOGIC **/
                        $studentIdForQuery = $row['id_number'];
                        $flagQuery = "SELECT reason FROM visitor_flags WHERE student_id = '$studentIdForQuery' AND status = 'FLAG' LIMIT 1";
                        $flagResult = $conn->query($flagQuery);
                        $isFlagged = ($flagResult && $flagResult->num_rows > 0);
                        
                        $dateDisplay = !empty($row['date_visited']) ? date('M d, Y', strtotime($row['date_visited'])) : "No Date";
                        $timeIn = !empty($row['time_in']) ? date('h:i A', strtotime($row['time_in'])) : "--:--";
                        $timeOutDisplay = !empty($row['time_out']) ? date('h:i A', strtotime($row['time_out'])) : '--:--';
                        
                        /** NAME CONSTRUCTION **/
                        $fName = $row['first_name'] ?? "";
                        $lName = $row['last_name'] ?? "";
                        $mName = trim($row['middle_name'] ?? "");
                        $suffix = !empty($row['suffix']) ? " " . $row['suffix'] : "";
                        $mi = "";
                        if (!empty($mName)) {
                            $words = explode(" ", $mName);
                            foreach ($words as $w) { $mi .= strtoupper(substr($w, 0, 1)) . "."; }
                            $mi = " " . $mi; 
                        }
                        $fullName = strtoupper(trim("$fName$mi $lName$suffix"));
                        if (empty(trim($fName . $lName))) { $fullName = "VISITOR / UNREGISTERED"; }

                        /** ROW STYLING LOGIC **/
                        $reasonKey = trim($row['reason'] ?? '');
                        $othersDetail = trim($row['others_detail'] ?? '');
                        $displayPurpose = (strcasecmp($reasonKey, 'OTHERS') === 0 && !empty($othersDetail)) ? $othersDetail : $reasonKey;
                        $statusLabel = $row['status_label'] ?? 'OUT';
                        $isInside = ($statusLabel == 'INSIDE');
                        $statusBg = $isInside ? '#d4edda' : '#f8f9fa'; 
                        $statusColor = $isInside ? '#155724' : '#6c757d'; 
                        $statusBorder = $isInside ? '#c3e6cb' : '#dee2e6';
                        $rowClass = $isFlagged ? 'flagged-row' : '';
                        $idColor = $isFlagged ? '#dc3545' : '#007bff';
                        $dateTimeCombined = $row['date_visited'] . ' ' . $row['time_in'];
                        $filterPurpose = strtoupper($row['reason'] ?? 'OTHERS');
                    ?>
                    <tr class="<?php echo $rowClass; ?>" 
                        data-id="<?php echo $row['id']; ?>" 
                        data-in-time="<?php echo $dateTimeCombined; ?>"
                        data-purpose="<?php echo $filterPurpose; ?>"
                        data-fname="<?php echo htmlspecialchars($row['first_name'] ?? ''); ?>"
                        data-mname="<?php echo htmlspecialchars($row['middle_name'] ?? ''); ?>"
                        data-lname="<?php echo htmlspecialchars($row['last_name'] ?? ''); ?>"
                        data-suffix="<?php echo htmlspecialchars($row['suffix'] ?? ''); ?>"
                        data-status="<?php echo $statusLabel; ?>">
                        
                        <td>
                            <span style="font-weight: 500;"><?php echo $dateDisplay; ?></span><br>
                            <small style="color: #666;"><?php echo $timeIn; ?></small>
                        </td>
                        <td class="time-out-cell" style="color: #dc3545; font-weight: bold;"><?php echo $timeOutDisplay; ?></td>
                        <td class="name-cell" style="font-weight: 600;"><?php echo htmlspecialchars($fullName); ?></td>
                        <td class="id-cell" style="color: <?php echo $idColor; ?>; font-weight: bold;">
                            <div style="display: flex; align-items: center; gap: 5px;">
                                <?php if ($isFlagged): ?>
                                    <svg class="flag-icon" style="width:14px; fill:#dc3545;" viewBox="0 0 24 24"><path d="M14.4 6L14 4H5v17h2v-7h5.6l.4 2h7V6h-5.6z"/></svg>
                                <?php endif; ?>
                                <span><?php echo htmlspecialchars($row['id_number'] ?: '-'); ?></span>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($row['user_type'] ?: 'VISITOR'); ?></td>
                        <td style="color: #2c3e50; font-weight: bold;"><?php echo htmlspecialchars($row['course'] ?: '-'); ?></td>
                        <td class="purpose-cell" data-original-purpose="<?php echo htmlspecialchars($reasonKey); ?>" data-others-detail="<?php echo htmlspecialchars($othersDetail); ?>">
                            <?php echo htmlspecialchars($displayPurpose ?: '-'); ?>
                        </td>
                        <td>
                            <span class="badge" style="background-color: <?php echo $statusBg; ?>; color: <?php echo $statusColor; ?>; border: 1px solid <?php echo $statusBorder; ?>; padding: 3px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: bold; display: inline-block;">
                                <?php echo $statusLabel; ?>
                            </span>
                        </td>
                        <td style="white-space: nowrap; width: 1%; padding: 10px;">
                            <div class="action-buttons" style="display: flex; gap: 6px;">
                                <?php if ($isInside): ?>
                                    <button class="btn-action" onclick="handleOut(this)" style="background-color: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 11px; font-weight: 700;">OUT</button>
                                    <?php if ($isFlagged): ?>
                                        <button class="btn-action active-flag" onclick="toggleManualFlag(this)" style="background-color: #28a745; color: white; border: none; padding: 5px 10px; border-radius: 4px; font-size: 11px; font-weight: bold; cursor: pointer;">🚩 UNFLAG</button>
                                    <?php else: ?>
                                        <button class="btn-action" onclick="toggleManualFlag(this)" style="background-color: #6f42c1; color: white; border: none; padding: 5px 10px; border-radius: 4px; font-size: 11px; font-weight: bold; cursor: pointer;">FLAG</button>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <button class="btn-action" onclick="editRow(this)" style="background-color: #fd7e14; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 11px; font-weight: 700;">EDIT</button>
                                <button class="btn-action" onclick="deleteRow(this, '<?php echo $row['id']; ?>')" style="background-color: #6c757d; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 11px; font-weight: 700;">DELETE</button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="9" style="text-align: center; padding: 30px; color: #999;">NO RECORDS FOUND.</td></tr>
                <?php endif; ?>
                </tbody>
                </table>
            </div>
        </div>

        <div id="user-management-section" class="content-section">
            <?php
            /** SQL FETCH USERS LOGIC **/
            $sql_users = "
                SELECT u.first_name, u.last_name, u.email, u.created_at, u.is_blocked, latest_log.id_number, latest_log.middle_name, latest_log.suffix, latest_log.contact 
                FROM users u 
                LEFT JOIN (
                    SELECT t1.email, t1.id_number, t1.middle_name, t1.suffix, t1.contact
                    FROM (
                        SELECT id, email, id_number, middle_name, suffix, contact FROM active_sessions
                        UNION ALL
                        SELECT id, email, id_number, middle_name, suffix, contact FROM library_logs
                    ) AS t1
                    INNER JOIN (
                        SELECT email, MAX(combined_id) as max_id
                        FROM (
                            SELECT email, id as combined_id FROM active_sessions
                            UNION ALL
                            SELECT email, id as combined_id FROM library_logs
                        ) AS all_ids
                        GROUP BY email
                    ) AS t2 ON t1.id = t2.max_id AND t1.email = t2.email
                ) AS latest_log ON u.email = latest_log.email 
                GROUP BY u.email 
                ORDER BY u.created_at DESC";
            $user_result = $conn->query($sql_users);
            ?>
            <div class="table-container">
                <h2 style="margin-bottom: 20px; color: var(--neu-blue); text-transform: uppercase;">User Account Management</h2>
                <div class="filter-group">
                    <input type="text" class="search-bar" id="userSearch" placeholder="SEARCH USERS..." style="width: 100%; max-width: 400px; margin-left: 0;">
                </div>
                <table id="userTable">
                    <thead>
                        <tr>
                            <th>First Name</th><th>Middle Name</th><th>Last Name</th><th>ID Number</th><th>Contact</th><th>Email Address</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="userTableBody">
                        <?php if ($user_result && $user_result->num_rows > 0): ?>
                            <?php while ($u_row = $user_result->fetch_assoc()): 
                                $idNum = !empty($u_row['id_number']) ? htmlspecialchars($u_row['id_number']) : "PENDING";
                                $email = htmlspecialchars($u_row['email']);
                                $isBlocked = ($u_row['is_blocked'] == 1);
                            ?>
                            <tr>
                                <td><?php echo strtoupper($u_row['first_name']); ?></td>
                                <td><?php echo strtoupper($u_row['middle_name'] ?: '-'); ?></td>
                                <td><?php echo strtoupper($u_row['last_name'] . ($u_row['suffix'] ? " " . $u_row['suffix'] : "")); ?></td>
                                <td style="color: #007bff; font-weight: bold;"><?php echo $idNum; ?></td>
                                <td><?php echo $u_row['contact'] ?: "-"; ?></td>
                                <td><?php echo $email; ?></td>
                                <td>
                                    <div class="action-buttons" style="display: flex; gap: 5px;">
                                        <button class="btn-action" onclick="RESET_PASSWORD('<?php echo $idNum; ?>', '<?php echo $email; ?>')" style="background-color: #ffc107; color: #000; border: none; padding: 5px 10px; border-radius: 4px; font-size: 11px; font-weight: bold; cursor: pointer;">RESET</button>
                                        <?php if ($isBlocked): ?>
                                            <button class="btn-action" onclick="UNBLOCK_USER('<?php echo $email; ?>')" style="background-color: #e67e22; color: white; border: none; padding: 5px 10px; border-radius: 4px; font-size: 11px; font-weight: bold; cursor: pointer;">UNBLOCK</button>
                                        <?php else: ?>
                                            <button class="btn-action" onclick="BLOCK_USER('<?php echo $email; ?>')" style="background-color: #008080; color: white; border: none; padding: 5px 10px; border-radius: 4px; font-size: 11px; font-weight: bold; cursor: pointer;">BLOCK</button>
                                        <?php endif; ?>
                                        <button class="btn-action" onclick="DELETE_USER('<?php echo $email; ?>')" style="background-color: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 4px; font-size: 11px; font-weight: bold; cursor: pointer;">DELETE</button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" style="text-align: center; padding: 30px; color: #999;">NO USERS FOUND.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="flagged-records-section" class="content-section">
            <div class="table-container">
                <h2 style="text-transform: uppercase; color: var(--neu-red); margin-bottom: 10px;">Flagged Records History</h2>
                <p style="font-size: 0.8rem; color: #666; margin-bottom: 20px;">ALL FLAGGED LOGS WILL APPEAR HERE FOR REVIEW AND AUDIT PURPOSES.</p>
                <table id="flagTable">
                    <thead>
                        <tr>
                            <th>Date Flagged</th><th>Full Name</th><th>ID Number</th><th>Reason for Flagging</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $historyQuery = mysqli_query($conn, "SELECT * FROM visitor_flags ORDER BY flagged_at DESC");
                        while($flag = mysqli_fetch_assoc($historyQuery)): 
                        ?>
                        <tr>
                            <td><?php echo date('M d, Y h:i A', strtotime($flag['flagged_at'])); ?></td>
                            <td><?php echo htmlspecialchars($flag['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($flag['idstudent']); ?></td>
                            <td><?php echo htmlspecialchars($flag['reason']); ?></td>
                            <td>
                                <button onclick="editFlag(<?php echo $flag['id']; ?>, '<?php echo addslashes($flag['reason']); ?>')" style="background:#ffc107; border:none; padding:4px 8px; border-radius:3px; cursor:pointer;">Edit</button>
                                <button onclick="deleteFlag(this, <?php echo $flag['id']; ?>)" style="background:#dc3545; color:white; border:none; padding:4px 8px; border-radius:3px; cursor:pointer;">Delete</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div> 

    <div class="modal" id="addModal">
        <div class="modal-content" style="max-width: 700px; width: 95%; border-radius: 15px; padding: 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.2);">
            <h2 id="modalTitle" style="color: #0056b3; font-size: 1.5rem; margin-bottom: 20px; text-transform: uppercase; font-weight: bold; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px;">Add New Visitor</h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div class="form-group"><label>FIRST NAME <span style="color: red;">*</span></label><input type="text" id="mFirstName" placeholder="FIRST NAME"></div>
                <div class="form-group"><label>MIDDLE NAME</label><input type="text" id="mMiddleName" placeholder="MIDDLE NAME"></div>
                <div class="form-group"><label>LAST NAME <span style="color: red;">*</span></label><input type="text" id="mLastName" placeholder="LAST NAME"></div>
            </div>
            <div style="display: grid; grid-template-columns: 0.6fr 1.2fr 1.2fr; gap: 15px; margin-bottom: 15px;">
                <div class="form-group"><label>SUFFIX</label><input type="text" id="mSuffix" placeholder="OPT."></div>
                <div class="form-group"><label>ID NUMBER </label><input type="text" id="mID" placeholder="XX-XXXXX-XXX"></div>
                <div class="form-group"><label>CONTACT NUMBER <span style="color: red;">*</span></label><input type="text" id="mContact" placeholder="09XXXXXXXXX"></div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div class="form-group">
                    <label>CATEGORY <span style="color: red;">*</span></label>
                    <select id="mCategory"><option value="" disabled selected>SELECT CATEGORY</option><option>GUEST</option><option>STUDENT</option><option>FACULTY</option><option>EMPLOYEE</option></select>
                </div>
                <div class="form-group"><label>PROGRAM/COURSE</label><input type="text" id="mProgram" placeholder="E.G. BS INFORMATION TECHNOLOGY"></div>
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label>PURPOSE <span style="color: red;">*</span></label>
                <select id="mPurpose" onchange="toggleOthersInput(this)">
                    <option value="" disabled selected>SELECT PURPOSE</option>
                    <option value="STUDY">STUDY</option><option value="BORROWING">BORROWING</option><option value="RETURNING">RETURNING</option><option value="INTERNET USE">INTERNET USE</option><option value="MEETING">MEETING</option><option value="RESEARCH">RESEARCH</option><option value="GROUP STUDY">GROUP STUDY</option><option value="PRINTING/PHOTOCOPY">PRINTING / PHOTOCOPY</option><option value="ACADEMIC REQUIREMENT">ACADEMIC REQUIREMENT</option><option value="OTHERS">OTHERS (PLEASE SPECIFY)</option>
                </select>
            </div>
            <div class="form-group" id="otherPurposeContainer" style="display: none; margin-bottom: 15px;">
                <label>PLEASE SPECIFY PURPOSE <span style="color: red;">*</span></label>
                <input type="text" id="mOtherPurpose" placeholder="TYPE YOUR PURPOSE HERE...">
            </div>
            <div class="modal-actions" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 25px;">
                <button class="btn-cancel" onclick="closeModal()">CANCEL</button>
                <button class="btn-save" id="saveBtn" onclick="saveVisitor()">SAVE VISITOR</button>
            </div>
        </div>
    </div>

    <div id="flagModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
        <div style="background:white; padding:20px; border-radius:8px; width:350px;">
            <h3 style="margin-top:0;">FLAG USER</h3>
            <label style="font-size:12px;">REASON FOR FLAGGING:</label>
            <textarea id="flagReasonInput" style="width:100%; height:80px; margin-top:5px; padding:5px; box-sizing:border-box;"></textarea>
            <div style="display:flex; gap:10px; margin-top:15px; justify-content:flex-end;">
                <button onclick="closeFlagModal()" style="background:#6c757d; color:white; border:none; padding:8px 15px; border-radius:4px; cursor:pointer;">CANCEL</button>
                <button id="confirmFlagBtn" style="background:#dc3545; color:white; border:none; padding:8px 15px; border-radius:4px; cursor:pointer;">SUBMIT FLAG</button>
            </div>
        </div>
    </div>

    <script>
/* --- GLOBAL CONFIG & STATE --- */
let vChart = null;
let pChart = null;
let editMode = false;
let editingRow = null;

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

/* --- SYSTEM & UI NAVIGATION --- */

function showSection(sectionId, element) {
    // 1. Itago lahat ng sections
    const sections = document.querySelectorAll('.content-section');
    sections.forEach(section => {
        section.classList.remove('active');
        section.style.display = 'none'; // Siguradong hidden
    });

    // 2. Ipakita ang napiling section
    const targetSection = document.getElementById(sectionId);
    if (targetSection) {
        targetSection.classList.add('active');
        targetSection.style.display = 'block'; // Gawing visible
    } else {
        console.error("Section ID not found: " + sectionId);
    }

    // 3. I-update ang itsura ng sidebar links (Highlighting)
    const links = document.querySelectorAll('.nav-link');
    links.forEach(link => {
        link.classList.remove('active');
    });
    
    if (element) {
        element.classList.add('active');
    }
}

function updateLiveInfo() {
    const now = new Date();
    const clockEl = document.getElementById('clock');
    const dateEl = document.getElementById('date');
    if(clockEl) clockEl.innerText = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true }).toUpperCase();
    if(dateEl) dateEl.innerText = now.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'short', day: '2-digit' }).toUpperCase();
}
setInterval(updateLiveInfo, 1000);

function logout() {
    if(confirm("ARE YOU SURE YOU WANT TO LOGOUT?")) {
        window.location.href = "login.php"; 
    }
}

/* --- VISITOR MODAL CONTROLS --- */

function openModal() { document.getElementById('addModal').style.display = 'flex'; }

function closeModal() { 
    document.getElementById('addModal').style.display = 'none'; 
    resetForm(); 
}

function resetForm() {
    editMode = false;
    editingRow = null;
    document.getElementById('modalTitle').innerText = "ADD NEW VISITOR";
    document.getElementById('saveBtn').innerText = "SAVE VISITOR";
    const fields = ['mFirstName', 'mMiddleName', 'mLastName', 'mSuffix', 'mID', 'mContact', 'mCategory', 'mProgram', 'mPurpose', 'mOtherPurpose'];
    fields.forEach(id => {
        const el = document.getElementById(id);
        if(el) el.value = '';
    });
    document.getElementById('otherPurposeContainer').style.display = 'none';
}

function toggleOthersInput(selectElement) {
    const otherContainer = document.getElementById('otherPurposeContainer');
    const otherInput = document.getElementById('mOtherPurpose');
    if (selectElement.value === "OTHERS") {
        otherContainer.style.display = 'block';
        otherInput.required = true;
    } else {
        otherContainer.style.display = 'none';
        otherInput.value = '';
        otherInput.required = false;
    }
}

/* --- VISITOR CORE OPERATIONS --- */

function saveVisitor() {
    const fName = document.getElementById('mFirstName').value.trim().toUpperCase();
    const mName = document.getElementById('mMiddleName').value.trim().toUpperCase();
    const lName = document.getElementById('mLastName').value.trim().toUpperCase();
    const suffix = document.getElementById('mSuffix').value.trim().toUpperCase();
    const id = document.getElementById('mID').value.trim().toUpperCase();
    const contact = document.getElementById('mContact').value.trim().toUpperCase();
    const cat = document.getElementById('mCategory').value.toUpperCase();
    const prog = document.getElementById('mProgram').value.trim().toUpperCase();
    let purpSelect = document.getElementById('mPurpose').value.toUpperCase();
    const otherPurp = document.getElementById('mOtherPurpose').value.trim().toUpperCase();

    if(!fName || !lName || !id || !contact || !cat || !purpSelect) { 
        alert("PLEASE FILL IN ALL REQUIRED FIELDS."); 
        return; 
    }
    if (purpSelect === "OTHERS" && !otherPurp) { 
        alert("PLEASE SPECIFY YOUR PURPOSE."); 
        document.getElementById('mOtherPurpose').focus();
        return; 
    }

    let fullName = [fName, mName, lName, suffix].filter(Boolean).join(" ");
    let displayPurpose = (purpSelect === "OTHERS") ? otherPurp : purpSelect;

    if (editMode && editingRow) {
        editingRow.querySelector('.name-cell').innerText = fullName;
        editingRow.querySelector('.id-cell').innerText = id;
        editingRow.cells[4].innerText = cat;
        editingRow.cells[5].innerText = prog || '-';
        const pCell = editingRow.querySelector('.purpose-cell');
        pCell.innerText = displayPurpose;
        pCell.setAttribute('data-original-purpose', purpSelect);
        editingRow.setAttribute('data-contact', contact);
        editingRow.setAttribute('data-fname', fName);
        editingRow.setAttribute('data-mname', mName);
        editingRow.setAttribute('data-lname', lName);
        editingRow.setAttribute('data-suffix', suffix);
        alert("RECORD UPDATED SUCCESSFULLY!");
    } else {
        const now = new Date();
        const timeInStr = now.toLocaleString('en-US', { month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: true }).toUpperCase();
        const tbody = document.querySelector("#visitorTable tbody");
        
        if (tbody.rows.length === 1 && tbody.rows[0].cells.length === 1) { tbody.innerHTML = ''; }

        const newRow = document.createElement('tr');
        newRow.setAttribute('data-contact', contact);
        newRow.setAttribute('data-fname', fName);
        newRow.setAttribute('data-mname', mName);
        newRow.setAttribute('data-lname', lName);
        newRow.setAttribute('data-suffix', suffix);
        
        newRow.innerHTML = `
            <td>${timeInStr}</td>
            <td class="time-out-cell">--:--</td>
            <td class="name-cell" style="font-weight: 600;">${fullName}</td>
            <td class="id-cell" style="color: #007bff; font-weight: bold;">${id}</td>
            <td>${cat}</td>
            <td style="font-weight: bold;">${prog || '-'}</td>
            <td class="purpose-cell" data-original-purpose="${purpSelect}">${displayPurpose}</td>
            <td><span class="badge" style="background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; font-weight: bold;">INSIDE</span></td>
            <td>
                <div class="action-buttons" style="display: flex; gap: 6px;">
                    <button class="btn-action" onclick="handleOut(this)" style="background-color: #dc3545; color: white; padding: 5px 10px; border-radius: 4px; font-size: 11px; font-weight: 700;">OUT</button>
                    <button class="btn-action" onclick="editRow(this)" style="background-color: #fd7e14; color: white; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 700;">EDIT</button>
                    <button class="btn-action" onclick="deleteRow(this)" style="background-color: #6c757d; color: white; padding: 5px 10px; border-radius: 4px; font-size: 11px; font-weight: 700;">DELETE</button>
                </div>
            </td>`;
        tbody.prepend(newRow);
    }
    updateStats();
    closeModal();
}

function editRow(btn) {
    editMode = true;
    editingRow = btn.closest('tr');
    
    document.getElementById('modalTitle').innerText = "EDIT VISITOR RECORD";
    document.getElementById('saveBtn').innerText = "UPDATE VISITOR";
    
    document.getElementById('mFirstName').value = editingRow.getAttribute('data-fname') || '';
    document.getElementById('mMiddleName').value = editingRow.getAttribute('data-mname') || '';
    document.getElementById('mLastName').value = editingRow.getAttribute('data-lname') || '';
    document.getElementById('mSuffix').value = editingRow.getAttribute('data-suffix') || '';
    document.getElementById('mID').value = editingRow.querySelector('.id-cell').innerText.replace('🚩', '').trim();
    document.getElementById('mContact').value = editingRow.getAttribute('data-contact') || '';
    document.getElementById('mCategory').value = editingRow.cells[4].innerText.trim();
    
    const prog = editingRow.cells[5].innerText.trim();
    document.getElementById('mProgram').value = (prog === '-' || prog === 'N/A') ? '' : prog;
    
    const pCell = editingRow.querySelector('.purpose-cell');
    const purpKey = pCell.getAttribute('data-original-purpose') || '';
    const mPurpose = document.getElementById('mPurpose');
    mPurpose.value = purpKey;

    toggleOthersInput(mPurpose);
    if (purpKey === "OTHERS") {
        document.getElementById('mOtherPurpose').value = pCell.innerText.trim();
    }
    
    openModal();
}

function handleOut(btn) {
    const row = btn.closest('tr');
    if (confirm("ARE YOU SURE YOU WANT TO TIME-OUT THIS VISITOR?")) {
        const timeOutCell = row.querySelector('.time-out-cell');
        const now = new Date();
        const timeString = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });
        
        if(timeOutCell) timeOutCell.innerText = timeString;
        
        const statusBadge = row.querySelector('.badge');
        if(statusBadge) {
            statusBadge.innerText = "OUT";
            statusBadge.style.backgroundColor = "#f8f9fa";
            statusBadge.style.color = "#6c757d";
            statusBadge.style.borderColor = "#dee2e6";
        }
        btn.remove(); 
        updateStats();
    }
}

function deleteRow(btn) {
    if(confirm("ARE YOU SURE YOU WANT TO PERMANENTLY DELETE THIS RECORD?")) {
        const row = btn.closest('tr');
        row.style.transition = "all 0.3s ease";
        row.style.transform = "scale(0.8)";
        row.style.opacity = "0";
        setTimeout(() => {
            row.remove();
            updateStats();
        }, 300);
    }
}

/* --- ANALYTICS & CHARTS --- */

function initCharts() {
    const vCtx = document.getElementById('visitorChart')?.getContext('2d');
    const pCtx = document.getElementById('purposeChart')?.getContext('2d');

    if (vCtx) {
        vChart = new Chart(vCtx, {
            type: 'line',
            data: {
                labels: ['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'],
                datasets: [{
                    label: 'VISITORS',
                    data: [0, 0, 0, 0, 0, 0],
                    borderColor: '#1e88e5',
                    tension: 0.3,
                    fill: true,
                    backgroundColor: 'rgba(30, 136, 229, 0.1)'
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    }

    if (pCtx) {
        pChart = new Chart(pCtx, {
            type: 'doughnut',
            data: {
                labels: [],
                datasets: [{ data: [], backgroundColor: [] }]
            },
            options: { responsive: true, maintainAspectRatio: false, cutout: '65%' }
        });
    }
}

function updateStats() {
    const rows = document.querySelectorAll("#visitorTable tbody tr");
    const now = new Date();
    const todayStr = now.toDateString();
    
    let inside = 0, today = 0, week = 0, month = 0;

    const lastMonday = new Date(now);
    const dayNum = now.getDay();
    const diffToMon = now.getDate() - dayNum + (dayNum === 0 ? -6 : 1);
    lastMonday.setDate(diffToMon);
    lastMonday.setHours(0, 0, 0, 0);

    rows.forEach(row => {
        if (row.cells.length < 8 || row.id === "no-filter-results") return;
        
        const dateText = row.cells[0].innerText.split('\n')[0]; 
        const rowDate = new Date(dateText);
        const status = row.cells[7].innerText.trim().toUpperCase();

        if (status === 'INSIDE') inside++;

        if(!isNaN(rowDate)) {
            const rowDateStr = rowDate.toDateString();
            if (rowDateStr === todayStr) today++;
            else if (rowDate >= lastMonday) week++;
            else month++;
        }
    });

    const setEl = (id, val) => { if(document.getElementById(id)) document.getElementById(id).innerText = val; };
    setEl('capacityCount', inside);
    setEl('todayCount', today);
    setEl('weekCount', week);
    setEl('monthCount', month);
    
    updateCharts();
}

function updateCharts() {
    if (!vChart || !pChart) return;
    
    const rows = document.querySelectorAll('#visitorTable tbody tr');
    const weeklyTrend = new Array(6).fill(0); 
    const purposeCounts = {};
    Object.keys(purposeConfig).forEach(key => { purposeCounts[key] = 0; });
    
    let visibleData = false;

    rows.forEach(row => {
        if (row.style.display === 'none' || row.cells.length < 7) return;
        
        visibleData = true;
        const dateText = row.cells[0].innerText.split('\n')[0];
        const d = new Date(dateText);
        if(!isNaN(d)) {
            const dayIndex = d.getDay(); 
            if (dayIndex !== 0) weeklyTrend[dayIndex - 1]++;
        }
        
        const pCell = row.querySelector('.purpose-cell');
        const purposeKey = pCell?.getAttribute('data-original-purpose');
        if (purposeKey && purposeCounts.hasOwnProperty(purposeKey)) {
            purposeCounts[purposeKey]++;
        }
    });

    vChart.data.datasets[0].data = weeklyTrend;
    vChart.update();
    
    if (visibleData) {
        const keys = Object.keys(purposeConfig);
        pChart.data.labels = keys.map(k => purposeConfig[k].label);
        pChart.data.datasets[0].data = keys.map(k => purposeCounts[k]);
        pChart.data.datasets[0].backgroundColor = keys.map(k => purposeConfig[k].color);
    } else {
        pChart.data.labels = ["NO DATA"];
        pChart.data.datasets[0].data = [1];
        pChart.data.datasets[0].backgroundColor = ["#e0e0e0"];
    }
    pChart.update();
}

/* --- FILTER & SEARCH --- */

function filterByTime(range, btn) {
    const rows = document.querySelectorAll("#visitorTable tbody tr");
    const now = new Date();
    const todayStr = now.toDateString();
    
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    if(btn) btn.classList.add('active');

    rows.forEach(row => {
        if (row.id === "no-filter-results") return;
        const dateText = row.cells[0].innerText.split('\n')[0];
        const rowDate = new Date(dateText);
        let show = (range === 'all');

        if (!show && !isNaN(rowDate)) {
            if (range === 'today') show = rowDate.toDateString() === todayStr;
            // Add other range logic as needed
        }
        row.style.display = show ? "" : "none";
    });
    updateStats();
}

function searchTable() {
    const filter = document.getElementById("tableSearch").value.toUpperCase();
    const rows = document.querySelectorAll("#visitorTable tbody tr:not(#no-filter-results)");
    let visibleCount = 0;

    rows.forEach(row => {
        const name = row.cells[2].innerText.toUpperCase();
        const id = row.cells[3].innerText.toUpperCase();
        if (name.includes(filter) || id.includes(filter)) {
            row.style.display = "";
            visibleCount++;
        } else {
            row.style.display = "none";
        }
    });

    const noRes = document.getElementById("no-filter-results");
    if(noRes) noRes.style.display = (visibleCount === 0) ? "" : "none";
    updateStats();
}

/* --- INITIALIZATION --- */

document.addEventListener('DOMContentLoaded', function() {
    initCharts();
    updateStats();
    updateLiveInfo();
});
    </script>
</body>
</html>